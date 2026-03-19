<?php
/**
 * Pinterest network adapter (API v5).
 *
 * @package RePostFlow
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Pinterest
 */
class RPF_Pinterest extends RPF_Network_Abstract {

	public function get_slug() {
		return 'pinterest';
	}

	public function get_label() {
		return 'Pinterest';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.017 0C5.396 0 .017 5.379.017 12c0 5.004 3.056 9.301 7.408 11.172-.102-.884-.194-2.247.04-3.213.213-.884 1.378-5.844 1.378-5.844s-.352-.704-.352-1.743c0-1.633.947-2.851 2.127-2.851 1.003 0 1.489.754 1.489 1.657 0 1.009-.643 2.516-.976 3.911-.278 1.17.587 2.124 1.742 2.124 2.091 0 3.699-2.204 3.699-5.379 0-2.809-2.019-4.771-4.903-4.771-3.341 0-5.299 2.505-5.299 5.098 0 1.009.39 2.09.877 2.68.096.117.11.219.081.338-.09.375-.289 1.173-.329 1.334-.052.218-.172.264-.396.159-1.477-.688-2.399-2.847-2.399-4.583 0-3.729 2.713-7.153 7.825-7.153 4.107 0 7.302 2.927 7.302 6.839 0 4.084-2.575 7.373-6.136 7.373-1.197 0-2.321-.622-2.709-1.358 0 0-.593 2.258-.737 2.813-.267 1.025-.989 2.309-1.472 3.092C9.68 23.886 10.819 24 12 24c6.627 012-5.373 12-12S18.627 0 12 0z"/></svg>';
	}

	public function get_color() {
		return '#E60023';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'access_token' ) )
			&& ! empty( $this->get_credential( 'board_id' ) );
	}

	public function get_settings_fields() {
		return array(
			'access_token' => array(
				'label'       => __( 'Access Token', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Get it from Pinterest Developer Portal → Apps → Your App → Generate Token', 'repostflow' ),
			),
			'board_id' => array(
				'label'       => __( 'Board ID', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'The board ID to pin to. Format: 1234567890123456789 or board slug name.', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Pin Title', 'repostflow' ),
				'type'        => 'text',
				'default'     => '{title}',
				'description' => __( 'Pin title (max 100 chars). Available: {title}, {author}, {categories}', 'repostflow' ),
			),
			'description_template' => array(
				'label'       => __( 'Pin Description', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "{excerpt}\n\n{url}\n\n{hashtags}",
				'description' => __( 'Pin description (max 500 chars). Available variables listed below.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$token    = $this->get_credential( 'access_token' );
		$board_id = $this->get_credential( 'board_id' );

		if ( empty( $token ) ) {
			return array( 'valid' => false, 'message' => __( 'Access token is required', 'repostflow' ) );
		}

		if ( empty( $board_id ) ) {
			return array( 'valid' => false, 'message' => __( 'Board ID is required', 'repostflow' ) );
		}

		$url      = 'https://api.pinterest.com/v5/user_account';
		$response = $this->http_get( $url, array(
			'Authorization' => 'Bearer ' . $token,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['username'] ) ) {
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Pinterest username */
					__( 'Connected as @%s', 'repostflow' ),
					$response['body']['username']
				),
			);
		}

		$error = isset( $response['body']['message'] ) ? $response['body']['message'] : __( 'Invalid access token', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Pinterest not configured', 'repostflow' ) );
		}

		if ( empty( $image_url ) ) {
			return $this->error( __( 'Pinterest requires an image. Please set a featured image for this post.', 'repostflow' ) );
		}

		$access_token = $this->get_credential( 'access_token' );
		$board_id     = $this->get_credential( 'board_id' );

		if ( empty( $board_id ) ) {
			return $this->error( __( 'Board ID is required for Pinterest', 'repostflow' ) );
		}

		$title = RePostFlow::get_option( 'networks.pinterest.template', '{title}' );
		$description = RePostFlow::get_option( 'networks.pinterest.description_template', '{excerpt}' );

		// Render templates.
		$template_engine = new RPF_Template_Engine();
		$rendered_title = $template_engine->render( $title, $post_id );
		$rendered_desc  = $template_engine->render( $description, $post_id );

		// Get post URL for link.
		$link = get_permalink( $post_id );

		$data = array(
			'board_id'     => $board_id,
			'title'        => mb_substr( $rendered_title, 0, 100 ),
			'description'  => mb_substr( $rendered_desc, 0, 500 ),
			'media_source' => array(
				'source_type' => 'image_url',
				'url'          => $image_url,
			),
			'link'         => $link,
		);

		$url = 'https://api.pinterest.com/v5/pins';

		$response = $this->http_post_json( $url, $data, array(
			'Authorization' => 'Bearer' . $access_token,
		) );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if (isset( $response['body']['id'] ) ) {
			$pin_id      = $response['body']['id'];
			$pin_url     = "https://www.pinterest.com/pin/{$pin_id}/";

			return $this->success( $pin_id, $pin_url );
		}

		$error = isset( $response['body']['message'] ) ? $response['body']['message'] : __( 'Unknown Pinterest API error', 'repostflow' );
		if ( is_array( $error ) ) {
			$error = wp_json_encode( $error );
		}
		return $this->error( $error );
	}
}