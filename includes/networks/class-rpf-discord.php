<?php
/**
 * Discord network adapter (Webhook API).
 *
 * @package RePostFlow
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Discord
 */
class RPF_Discord extends RPF_Network_Abstract {

	public function get_slug() {
		return 'discord';
	}

	public function get_label() {
		return 'Discord';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5174 9.0459-.0046 13.5659.0222 18.0746a.0855.0855 0 00.0311.0675c2.0457 1.5126 4.0321 2.4331 5.9852 3.0455a.0767.0767 0 00.0855-.0248c.4615-.6304.8731-1.2954 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2723-.5495-1.8729-.9014a.077.077 0 01-.0076-.1284c.1258-.0936.2517-.1922.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.0994.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.9014.0766.0766 0 00-.0407.1067c.3605.6985.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9486-1.5267 5.9943-3.0455a.077.077 0 00.0313-.0659c.0229-4.6391-.7699-9.1296-3.1551-13.6785a.0618.0618 0 00-.0312-.0278zM8.02 15.3312c-1.1825 0-2.1569-1.0853-2.1569-2.419 0-1.3332.9561-2.4189 2.1569-2.4189 1.2105 0 2.1752 1.0952 2.1568 2.419 0 1.3332-.956 2.4189-2.1568 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0853-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2105 0 2.1752 1.0952 2.1568 2.419 0 1.3332-.9465 2.4189-2.1568 2.4189z"/></svg>';
	}

	public function get_color() {
		return '#5865F2';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'webhook_url' ) );
	}

	public function get_settings_fields() {
		return array(
			'webhook_url' => array(
				'label'       => __( 'Webhook URL', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Create a webhook in your Discord channel: Settings → Integrations → Webhooks → New Webhook', 'repostflow' ),
			),
			'username' => array(
				'label'       => __( 'Bot Username (optional)', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'Custom username for the webhook. Leave empty to use the webhook default.', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Message Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "**{title}**\n\n{url}\n\n{hashtags}",
				'description' => __( 'Discord Markdown supported: **bold**, *italic*, [link](url). Available variables listed below.', 'repostflow' ),
			),
			'embed_enabled' => array(
				'label'       => __( 'Use Embed', 'repostflow' ),
				'type'        => 'checkbox',
				'default'     => true,
				'description' => __( 'Send as embedded message with image preview.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$webhook_url = $this->get_credential( 'webhook_url' );

		if ( empty( $webhook_url ) ) {
			return array( 'valid' => false, 'message' => __( 'Webhook URL is required', 'repostflow' ) );
		}

		if ( ! preg_match( '#^https://(?:ptb\.|canary\.)?discord\.com/api/webhooks/\d+/[\w-]+$#', $webhook_url ) ) {
			return array( 'valid' => false, 'message' => __( 'Invalid webhook URL format', 'repostflow' ) );
		}

		$url = $webhook_url . '?wait=true';

		$test_data = array(
			'content' => __( 'RePostFlow connection test successful!', 'repostflow' ),
		);

		$response = $this->http_post_json( $url, $test_data );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['id'] ) ) {
			$webhook_id = $response['body']['webhook_id'];
			$this->delete_webhook_message( $webhook_url, $response['body']['id'] );
			
			return array(
				'valid'   => true,
				'message' => __( 'Webhook connection successful!', 'repostflow' ),
			);
		}

		$error = isset( $response['body']['message'] ) ? $response['body']['message'] : __( 'Invalid webhook URL', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Discord not configured', 'repostflow' ) );
		}

		$webhook_url   = $this->get_credential( 'webhook_url' );
		$username      = $this->get_credential( 'username' );
		$embed_enabled = RePostFlow::get_option( 'networks.discord.embed_enabled', true );

		$post_url = get_permalink( $post_id );
		$post_title = get_the_title( $post_id );

		$data = array();

		if ( ! empty( $username ) ) {
			$data['username'] = $username;
		}

		if ( $embed_enabled && ! empty( $image_url ) ) {
			$data['embeds'] = array(
				array(
					'title'       => mb_substr( $post_title, 0, 256 ),
					'description'  => mb_substr( $message, 0, 2048 ),
					'url'          => $post_url,
					'image'        => array(
						'url' => $image_url,
					),
					'color'        => hexdec( '5865F2' ),
				),
			);
		} else {
			$data['content'] = mb_substr( $message, 0, 2000 );
		}

		$url = $webhook_url . '?wait=true';

		$response = $this->http_post_json( $url, $data );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['id'] ) ) {
			$message_id   = $response['body']['id'];
			$channel_id    = $response['body']['channel_id'];
			$message_url   = "https://discord.com/channels/@me/{$channel_id}/{$message_id}";

			return $this->success( $message_id, $message_url );
		}

		$error = isset( $response['body']['message'] ) ? $response['body']['message'] : __( 'Unknown Discord API error', 'repostflow' );
		return $this->error( $error );
	}

	private function delete_webhook_message( $webhook_url, $message_id ) {
		$url = preg_replace( '#/messages/[^/]*$#', '', $webhook_url );
		$delete_url = $url . '/messages/' . $message_id;
		
		wp_remote_request( $delete_url, array(
			'method'    => 'DELETE',
			'timeout'   => 10,
		) );
	}
}