<?php
/**
 * Telegram network adapter (Bot API).
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Telegram
 */
class RPF_Telegram extends RPF_Network_Abstract {

	public function get_slug() {
		return 'telegram';
	}

	public function get_label() {
		return 'Telegram';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>';
	}

	public function get_color() {
		return '#26A5E4';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'bot_token' ) )
			&& ! empty( $this->get_credential( 'chat_id' ) );
	}

	public function get_settings_fields() {
		return array(
			'bot_token' => array(
				'label'       => __( 'Bot Token', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Get it from @BotFather on Telegram. Example: 123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'repostflow' ),
			),
			'chat_id' => array(
				'label'       => __( 'Chat ID / Channel Username', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'Channel username (e.g., @mychannel) or numeric chat ID. The bot must be admin of the channel.', 'repostflow' ),
			),
			'parse_mode' => array(
				'label'       => __( 'Parse Mode', 'repostflow' ),
				'type'        => 'select',
				'options'     => array(
					'HTML'       => 'HTML',
					'Markdown'   => 'Markdown',
					'MarkdownV2' => 'MarkdownV2',
				),
				'default'     => 'HTML',
				'description' => __( 'Message formatting mode.', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Message Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "🎮 <b>{title}</b>\n\n{excerpt}\n\n👉 {url}",
				'description' => __( 'Max 4096 characters. Use HTML tags: <b>, <i>, <a>, <code>.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$token = $this->get_credential( 'bot_token' );

		$url      = "https://api.telegram.org/bot{$token}/getMe";
		$response = $this->http_get( $url );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( isset( $response['body']['ok'] ) && true === $response['body']['ok'] ) {
			$bot_name = $response['body']['result']['username'];
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Telegram bot username */
					__( 'Connected as @%s', 'repostflow' ),
					$bot_name
				),
			);
		}

		$error = isset( $response['body']['description'] ) ? $response['body']['description'] : __( 'Invalid bot token', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Telegram not configured', 'repostflow' ) );
		}

		$token      = $this->get_credential( 'bot_token' );
		$chat_id    = $this->get_credential( 'chat_id' );
		$parse_mode = RePostFlow::get_option( 'networks.telegram.parse_mode', 'HTML' );

		// If image available, send as photo with caption.
		if ( ! empty( $image_url ) ) {
			$url  = "https://api.telegram.org/bot{$token}/sendPhoto";
			$data = array(
				'chat_id'    => $chat_id,
				'photo'      => $image_url,
				'caption'    => $message,
				'parse_mode' => $parse_mode,
			);
		} else {
			$url  = "https://api.telegram.org/bot{$token}/sendMessage";
			$data = array(
				'chat_id'                  => $chat_id,
				'text'                     => $message,
				'parse_mode'               => $parse_mode,
				'disable_web_page_preview' => false,
			);
		}

		$response = $this->http_post( $url, $data );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if ( isset( $response['body']['ok'] ) && true === $response['body']['ok'] ) {
			$msg_id    = $response['body']['result']['message_id'];
			$chat_data = $response['body']['result']['chat'];

			// Build message URL if it's a channel.
			$external_url = '';
			if ( isset( $chat_data['username'] ) ) {
				$external_url = "https://t.me/{$chat_data['username']}/{$msg_id}";
			}

			return $this->success( (string) $msg_id, $external_url );
		}

		$error = isset( $response['body']['description'] ) ? $response['body']['description'] : __( 'Unknown Telegram API error', 'repostflow' );
		return $this->error( $error );
	}
}
