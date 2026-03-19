<?php
/**
 * X/Twitter network adapter (API v2).
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Twitter
 */
class RPF_Twitter extends RPF_Network_Abstract {

	public function get_slug() {
		return 'twitter';
	}

	public function get_label() {
		return 'X (Twitter)';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
	}

	public function get_color() {
		return '#000000';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'api_key' ) )
			&& ! empty( $this->get_credential( 'api_secret' ) )
			&& ! empty( $this->get_credential( 'access_token' ) )
			&& ! empty( $this->get_credential( 'access_secret' ) );
	}

	public function get_settings_fields() {
		return array(
			'api_key' => array(
				'label'       => __( 'API Key (Consumer Key)', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'From X Developer Portal → App → Keys and tokens', 'repostflow' ),
			),
			'api_secret' => array(
				'label'       => __( 'API Secret (Consumer Secret)', 'repostflow' ),
				'type'        => 'password',
				'description' => '',
			),
			'access_token' => array(
				'label'       => __( 'Access Token', 'repostflow' ),
				'type'        => 'password',
				'description' => '',
			),
			'access_secret' => array(
				'label'       => __( 'Access Token Secret', 'repostflow' ),
				'type'        => 'password',
				'description' => '',
			),
			'template' => array(
				'label'       => __( 'Post Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "🎮 {title}\n\n{url}\n\n{hashtags}",
				'description' => __( 'Max 280 characters. Available variables listed below.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$response = $this->make_oauth_request(
			'GET',
			'https://api.twitter.com/2/users/me',
			array()
		);

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['data']['username'] ) ) {
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Twitter username */
					__( 'Connected as @%s', 'repostflow' ),
					$response['body']['data']['username']
				),
			);
		}

		return array( 'valid' => false, 'message' => __( 'Invalid credentials', 'repostflow' ) );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Twitter not configured', 'repostflow' ) );
		}

		// Upload image if available.
		$media_id = '';
		if ( ! empty( $image_url ) ) {
			$media_id = $this->upload_media( $image_url );
		}

		$body = array( 'text' => $message );
		if ( ! empty( $media_id ) ) {
			$body['media'] = array( 'media_ids' => array( $media_id ) );
		}

		$response = $this->make_oauth_request(
			'POST',
			'https://api.twitter.com/2/tweets',
			$body,
			true
		);

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if ( isset( $response['body']['data']['id'] ) ) {
			$tweet_id = $response['body']['data']['id'];
			// Get username for URL.
			$user_response = $this->make_oauth_request( 'GET', 'https://api.twitter.com/2/users/me', array() );
			$username = isset( $user_response['body']['data']['username'] ) ? $user_response['body']['data']['username'] : '';
			$tweet_url = ! empty( $username ) ? "https://x.com/{$username}/status/{$tweet_id}" : '';

			return $this->success( $tweet_id, $tweet_url );
		}

		$error_msg = isset( $response['body']['detail'] ) ? $response['body']['detail'] : __( 'Unknown Twitter API error', 'repostflow' );
		return $this->error( $error_msg );
	}

	/**
	 * Make an OAuth 1.0a signed request to Twitter API.
	 *
	 * @param string $method  HTTP method.
	 * @param string $url     API endpoint.
	 * @param array  $data    Request data.
	 * @param bool   $is_json Whether to send as JSON body.
	 * @return array|WP_Error
	 */
	private function make_oauth_request( $method, $url, $data = array(), $is_json = false ) {
		$consumer_key    = $this->get_credential( 'api_key' );
		$consumer_secret = $this->get_credential( 'api_secret' );
		$token           = $this->get_credential( 'access_token' );
		$token_secret    = $this->get_credential( 'access_secret' );

		$oauth_params = array(
			'oauth_consumer_key'     => $consumer_key,
			'oauth_nonce'            => wp_generate_password( 32, false ),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp'        => time(),
			'oauth_token'            => $token,
			'oauth_version'          => '1.0',
		);

		// Build signature base string.
		$sig_params = $oauth_params;
		if ( ! $is_json && 'POST' === $method ) {
			$sig_params = array_merge( $sig_params, $data );
		}

		ksort( $sig_params );
		$param_string = http_build_query( $sig_params, '', '&', PHP_QUERY_RFC3986 );
		$base_string  = strtoupper( $method ) . '&' . rawurlencode( $url ) . '&' . rawurlencode( $param_string );
		$signing_key  = rawurlencode( $consumer_secret ) . '&' . rawurlencode( $token_secret );

		$oauth_params['oauth_signature'] = base64_encode( hash_hmac( 'sha1', $base_string, $signing_key, true ) );

		// Build Authorization header.
		$auth_parts = array();
		foreach ( $oauth_params as $key => $value ) {
			$auth_parts[] = rawurlencode( $key ) . '="' . rawurlencode( $value ) . '"';
		}
		$auth_header = 'OAuth ' . implode( ', ', $auth_parts );

		$headers = array( 'Authorization' => $auth_header );

		if ( 'GET' === $method ) {
			return $this->http_get( $url, $headers );
		}

		if ( $is_json ) {
			return $this->http_post_json( $url, $data, $headers );
		}

		return $this->http_post( $url, $data, $headers );
	}

	/**
	 * Upload media to Twitter (v1.1 endpoint).
	 *
	 * @param string $image_url Image URL.
	 * @return string Media ID or empty string.
	 */
	private function upload_media( $image_url ) {
		// Download the image.
		$image_data = wp_remote_get( $image_url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $image_data ) ) {
			return '';
		}

		$body       = wp_remote_retrieve_body( $image_data );
		$media_data = base64_encode( $body );

		$response = $this->make_oauth_request(
			'POST',
			'https://upload.twitter.com/1.1/media/upload.json',
			array( 'media_data' => $media_data )
		);

		if ( is_wp_error( $response ) || ! isset( $response['body']['media_id_string'] ) ) {
			return '';
		}

		return $response['body']['media_id_string'];
	}
}
