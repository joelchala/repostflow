<?php
/**
 * Mastodon network adapter (Mastodon API).
 *
 * @package RePostFlow
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Mastodon
 */
class RPF_Mastodon extends RPF_Network_Abstract {

	public function get_slug() {
		return 'mastodon';
	}

	public function get_label() {
		return 'Mastodon';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M21.327 8.566c0-4.339-2.843-5.61-2.843-5.61-1.433-.659-3.894-.933-6.451-.954h-.063c-2.557.021-5.016.295-6.45.954 0 0-2.843 1.271-2.843 5.61 0 .993-.019 2.179.022 3.442.162 6.651 1.198 13.2 7.24 14.98 3.517 1.03 6.54 1.247 8.967.954 4.183-.534 6.537-3.33 6.537-3.33l-.112-.404c-1.89.379-2.654.404-2.654.404s1.22-2.336 1.663-4.742c.598-3.223.502-6.792.502-6.792-.012-.164-.025-.331-.04-.5-.22-2.016-.87-3.168-.87-3.168zm-9.327 9.434V8.566h4.5v2.833h-1.667v6.601z"/></svg>';
	}

	public function get_color() {
		return '#6364FF';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'instance_url' ) )
			&& ! empty( $this->get_credential( 'access_token' ) );
	}

	public function get_settings_fields() {
		return array(
			'instance_url' => array(
				'label'       => __( 'Instance URL', 'repostflow' ),
				'type'        => 'text',
				'default'     => 'https://mastodon.social',
				'description' => __( 'Your Mastodon instance URL (e.g., https://mastodon.social, https://fosstodon.org)', 'repostflow' ),
			),
			'access_token' => array(
				'label'       => __( 'Access Token', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Get it from your Mastodon instance: Settings → Development → New application → Your access token', 'repostflow' ),
			),
			'visibility' => array(
				'label'       => __( 'Post Visibility', 'repostflow' ),
				'type'        => 'select',
				'options'     => array(
					'public'   => __( 'Public', 'repostflow' ),
					'unlisted' => __( 'Unlisted', 'repostflow' ),
					'private'  => __( 'Followers only', 'repostflow' ),
				),
				'default'     => 'public',
				'description' => __( 'Who can see your posts.', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Toot Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "{title}\n\n{url}\n\n{hashtags}",
				'description' => __( 'Max 500 characters. Available variables listed below.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$instance_url = rtrim( $this->get_credential( 'instance_url' ), '/' );
		$access_token = $this->get_credential( 'access_token' );

		if ( empty( $instance_url ) || empty( $access_token ) ) {
			return array( 'valid' => false, 'message' => __( 'Instance URL and Access Token are required', 'repostflow' ) );
		}

		$url      = $instance_url . '/api/v1/accounts/verify_credentials';
		$response = $this->http_get( $url, array(
			'Authorization' => 'Bearer ' . $access_token,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['username'] ) ) {
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Mastodon username */
					__( 'Connected as @%s', 'repostflow' ),
					$response['body']['username']
				),
			);
		}

		$error = isset( $response['body']['error'] ) ? $response['body']['error'] : __( 'Invalid access token', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Mastodon not configured', 'repostflow' ) );
		}

		$instance_url = rtrim( $this->get_credential( 'instance_url' ), '/' );
		$access_token = $this->get_credential( 'access_token' );
		$visibility   = RePostFlow::get_option( 'networks.mastodon.visibility', 'public' );

		$media_ids = array();

		if ( ! empty( $image_url ) ) {
			$media_id = $this->upload_media( $image_url, $instance_url, $access_token );
			if ( $media_id ) {
				$media_ids[] = $media_id;
			}
		}

		$data = array(
			'status'     => mb_substr( $message, 0, 500 ),
			'visibility' => $visibility,
		);

		if ( ! empty( $media_ids ) ) {
			$data['media_ids'] = $media_ids;
		}

		$url = $instance_url . '/api/v1/statuses';

		$response = $this->http_post( $url, $data, array(
			'Authorization' => 'Bearer ' . $access_token,
			'Idempotency-Key' => md5( $post_id . time() ),
		) );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['id'] ) ) {
			$status_id   = $response['body']['id'];
			$status_url  = $response['body']['url'];
			
			if ( empty( $status_url ) && isset( $response['body']['account']['username'] ) ) {
				$username = $response['body']['account']['username'];
				$status_url = $instance_url . '/@' . $username . '/' . $status_id;
			}

			return $this->success( $status_id, $status_url );
		}

		$error = isset( $response['body']['error'] ) ? $response['body']['error'] : __( 'Unknown Mastodon API error', 'repostflow' );
		return $this->error( $error );
	}

	private function upload_media( $image_url, $instance_url, $access_token ) {
		$image_data = wp_remote_get( $image_url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $image_data ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $image_data );
		if ( empty( $body ) ) {
			return null;
		}

		$extension = pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
		if ( empty( $extension ) ) {
			$extension = 'jpg';
		}

		$filename = 'image.' . $extension;

		$boundary = wp_generate_password( 24, false );
		$payload  = '';

		$payload .= '--' . $boundary . "\r\n";
		$payload .= 'Content-Disposition: form-data; name="file"; filename="' . $filename . '"' . "\r\n";
		$payload .= "Content-Type: application/octet-stream\r\n\r\n";
		$payload .= $body . "\r\n";
		$payload .= '--' . $boundary . "--\r\n";

		$response = wp_remote_post( $instance_url . '/api/v2/media', array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
			),
			'body'    => $payload,
		) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['id'] ) ) {
			return $body['id'];
		}

		return null;
	}
}