<?php
/**
 * Abstract base class for network adapters.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Network_Abstract
 */
abstract class RPF_Network_Abstract implements RPF_Network_Interface {

	/**
	 * Get a credential value (decrypted).
	 *
	 * @param string $key Credential key.
	 * @return string
	 */
	protected function get_credential( $key ) {
		$value = RePostFlow::get_option( 'networks.' . $this->get_slug() . '.' . $key, '' );
		if ( ! empty( $value ) && $this->is_encrypted_field( $key ) ) {
			return RPF_Encryption::decrypt( $value );
		}
		return $value;
	}

	/**
	 * Check if a field should be encrypted.
	 *
	 * @param string $key Field key.
	 * @return bool
	 */
	protected function is_encrypted_field( $key ) {
		$encrypted_fields = array(
			'api_key',
			'api_secret',
			'access_token',
			'access_secret',
			'bot_token',
			'page_token',
			'client_secret',
			'password',
			'webhook_url',
		);
		return in_array( $key, $encrypted_fields, true );
	}

	/**
	 * Check if network is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) RePostFlow::get_option( 'networks.' . $this->get_slug() . '.enabled', false );
	}

	/**
	 * Make an HTTP POST request.
	 *
	 * @param string $url     API endpoint.
	 * @param array  $args    Request arguments.
	 * @param array  $headers HTTP headers.
	 * @return array|WP_Error
	 */
	protected function http_post( $url, $args = array(), $headers = array() ) {
		$defaults = array(
			'timeout'   => 30,
			'headers'   => $headers,
			'body'      => $args,
			'sslverify' => true,
		);

		$response = wp_remote_post( $url, $defaults );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( $body, true );

		return array(
			'code' => $code,
			'body' => $data ? $data : $body,
			'raw'  => $body,
		);
	}

	/**
	 * Make an HTTP GET request.
	 *
	 * @param string $url     API endpoint.
	 * @param array  $headers HTTP headers.
	 * @return array|WP_Error
	 */
	protected function http_get( $url, $headers = array() ) {
		$response = wp_remote_get( $url, array(
			'timeout' => 30,
			'headers' => $headers,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( $body, true );

		return array(
			'code' => $code,
			'body' => $data ? $data : $body,
			'raw'  => $body,
		);
	}

	/**
	 * Make an HTTP POST request with JSON body.
	 *
	 * @param string $url     API endpoint.
	 * @param array  $data    JSON data.
	 * @param array  $headers HTTP headers.
	 * @return array|WP_Error
	 */
	protected function http_post_json( $url, $data = array(), $headers = array() ) {
		$headers = array_merge( array(
			'Content-Type' => 'application/json',
		), $headers );

		$response = wp_remote_post( $url, array(
			'timeout' => 30,
			'headers' => $headers,
			'body'    => wp_json_encode( $data ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( $body, true );

		return array(
			'code' => $code,
			'body' => $decoded ? $decoded : $body,
			'raw'  => $body,
		);
	}

	/**
	 * Format a success response.
	 *
	 * @param string $external_id  External post ID.
	 * @param string $external_url External post URL.
	 * @return array
	 */
	protected function success( $external_id = '', $external_url = '' ) {
		return array(
			'success'      => true,
			'external_id'  => $external_id,
			'external_url' => $external_url,
			'error'        => '',
		);
	}

	/**
	 * Format an error response.
	 *
	 * @param string $message Error message.
	 * @return array
	 */
	protected function error( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only when WP_DEBUG is on.
				 '[RePostFlow] ' . $this->get_slug() . ' error: ' . $message );
		}

		return array(
			'success'      => false,
			'external_id'  => '',
			'external_url' => '',
			'error'        => $message,
		);
	}

	/**
	 * Default template for this network.
	 *
	 * @return string
	 */
	public function get_default_template() {
		return '{title} - {url}';
	}
}
