<?php
/**
 * Instagram network adapter (Graph API via Facebook).
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Instagram
 */
class RPF_Instagram extends RPF_Network_Abstract {

	const API_VERSION = 'v18.0';

	public function get_slug() {
		return 'instagram';
	}

	public function get_label() {
		return 'Instagram';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>';
	}

	public function get_color() {
		return '#E4405F';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'ig_user_id' ) )
			&& ! empty( $this->get_credential( 'page_token' ) );
	}

	public function get_settings_fields() {
		return array(
			'ig_user_id' => array(
				'label'       => __( 'Instagram Business Account ID', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'Your Instagram Business/Creator Account ID (linked to Facebook Page).', 'repostflow' ),
			),
			'page_token' => array(
				'label'       => __( 'Facebook Page Access Token', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Same token used for Facebook (must have instagram_content_publish permission).', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Caption Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "🎮 {title}\n\n{excerpt}\n\n{hashtags}\n\n🔗 Link in bio",
				'description' => __( 'Max 2200 characters. Instagram requires an image for every post.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$ig_user_id = $this->get_credential( 'ig_user_id' );
		$token      = $this->get_credential( 'page_token' );

		$url      = sprintf( 'https://graph.facebook.com/%s/%s?fields=name,username,profile_picture_url&access_token=%s', self::API_VERSION, $ig_user_id, $token );
		$response = $this->http_get( $url );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['username'] ) ) {
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Instagram username */
					__( 'Connected as @%s', 'repostflow' ),
					$response['body']['username']
				),
			);
		}

		$error = isset( $response['body']['error']['message'] ) ? $response['body']['error']['message'] : __( 'Invalid credentials', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Instagram not configured', 'repostflow' ) );
		}

		// Instagram requires an image.
		if ( empty( $image_url ) ) {
			return $this->error( __( 'Instagram requires an image. Post skipped (no featured image).', 'repostflow' ) );
		}

		$ig_user_id = $this->get_credential( 'ig_user_id' );
		$token      = $this->get_credential( 'page_token' );

		// Step 1: Create media container.
		$create_url = sprintf( 'https://graph.facebook.com/%s/%s/media', self::API_VERSION, $ig_user_id );
		$create_response = $this->http_post( $create_url, array(
			'image_url'    => $image_url,
			'caption'      => $message,
			'access_token' => $token,
		) );

		if ( is_wp_error( $create_response ) ) {
			return $this->error( $create_response->get_error_message() );
		}

		if ( ! isset( $create_response['body']['id'] ) ) {
			$error = isset( $create_response['body']['error']['message'] ) ? $create_response['body']['error']['message'] : __( 'Failed to create media container', 'repostflow' );
			return $this->error( $error );
		}

		$creation_id = $create_response['body']['id'];

		// Step 2: Publish the container.
		$publish_url = sprintf( 'https://graph.facebook.com/%s/%s/media_publish', self::API_VERSION, $ig_user_id );
		$publish_response = $this->http_post( $publish_url, array(
			'creation_id'  => $creation_id,
			'access_token' => $token,
		) );

		if ( is_wp_error( $publish_response ) ) {
			return $this->error( $publish_response->get_error_message() );
		}

		if ( isset( $publish_response['body']['id'] ) ) {
			$ig_media_id = $publish_response['body']['id'];
			return $this->success( $ig_media_id, "https://www.instagram.com/p/{$ig_media_id}/" );
		}

		$error = isset( $publish_response['body']['error']['message'] ) ? $publish_response['body']['error']['message'] : __( 'Failed to publish to Instagram', 'repostflow' );
		return $this->error( $error );
	}
}
