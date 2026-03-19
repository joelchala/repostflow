<?php
/**
 * Facebook network adapter (Graph API).
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Facebook
 */
class RPF_Facebook extends RPF_Network_Abstract {

	const API_VERSION = 'v18.0';

	public function get_slug() {
		return 'facebook';
	}

	public function get_label() {
		return 'Facebook';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
	}

	public function get_color() {
		return '#1877F2';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'page_id' ) )
			&& ! empty( $this->get_credential( 'page_token' ) );
	}

	public function get_settings_fields() {
		return array(
			'page_id' => array(
				'label'       => __( 'Page ID', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'Your Facebook Page ID. Find it in Page Settings → About.', 'repostflow' ),
			),
			'page_token' => array(
				'label'       => __( 'Page Access Token', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Long-lived Page Access Token from Graph API Explorer or your app.', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Post Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "🎮 {title}\n\n{excerpt}\n\n👉 {url}",
				'description' => __( 'Available variables listed below.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$page_id = $this->get_credential( 'page_id' );
		$token   = $this->get_credential( 'page_token' );

		$url      = sprintf( 'https://graph.facebook.com/%s/%s?fields=name,id&access_token=%s', self::API_VERSION, $page_id, $token );
		$response = $this->http_get( $url );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['name'] ) ) {
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Facebook page name */
					__( 'Connected to page: %s', 'repostflow' ),
					$response['body']['name']
				),
			);
		}

		$error = isset( $response['body']['error']['message'] ) ? $response['body']['error']['message'] : __( 'Invalid credentials', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Facebook not configured', 'repostflow' ) );
		}

		$page_id = $this->get_credential( 'page_id' );
		$token   = $this->get_credential( 'page_token' );
		$post_url = get_permalink( $post_id );

		// If image available, post as photo with link in message.
		if ( ! empty( $image_url ) ) {
			$url  = sprintf( 'https://graph.facebook.com/%s/%s/photos', self::API_VERSION, $page_id );
			$data = array(
				'url'          => $image_url,
				'message'      => $message,
				'access_token' => $token,
			);

			$response = $this->http_post( $url, $data );
		} else {
			// Post as link share.
			$url  = sprintf( 'https://graph.facebook.com/%s/%s/feed', self::API_VERSION, $page_id );
			$data = array(
				'message'      => $message,
				'link'         => $post_url,
				'access_token' => $token,
			);

			$response = $this->http_post( $url, $data );
		}

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if ( isset( $response['body']['id'] ) ) {
			$fb_post_id  = $response['body']['id'];
			$fb_post_url = "https://www.facebook.com/{$fb_post_id}";
			return $this->success( $fb_post_id, $fb_post_url );
		}

		$error = isset( $response['body']['error']['message'] ) ? $response['body']['error']['message'] : __( 'Unknown Facebook API error', 'repostflow' );
		return $this->error( $error );
	}
}
