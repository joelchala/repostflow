<?php
/**
 * Encryption helper for API credentials.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Encryption
 */
class RPF_Encryption {

	/**
	 * Encryption method.
	 */
	const METHOD = 'aes-256-cbc';

	/**
	 * Get the encryption key derived from WordPress salts.
	 *
	 * @return string
	 */
	private static function get_key() {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'rpf-default-key-change-me';
		return hash( 'sha256', $salt, true );
	}

	/**
	 * Encrypt a value.
	 *
	 * @param string $value Plain text value.
	 * @return string Base64-encoded encrypted value.
	 */
	public static function encrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $value );
		}

		$iv     = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::METHOD ) );
		$cipher = openssl_encrypt( $value, self::METHOD, self::get_key(), 0, $iv );

		if ( false === $cipher ) {
			return base64_encode( $value );
		}

		return base64_encode( $iv . '::' . $cipher );
	}

	/**
	 * Decrypt a value.
	 *
	 * @param string $value Base64-encoded encrypted value.
	 * @return string Decrypted plain text.
	 */
	public static function decrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$data = base64_decode( $value );

		if ( ! function_exists( 'openssl_decrypt' ) || false === strpos( $data, '::' ) ) {
			return $data;
		}

		list( $iv, $cipher ) = explode( '::', $data, 2 );
		$decrypted = openssl_decrypt( $cipher, self::METHOD, self::get_key(), 0, $iv );

		return ( false === $decrypted ) ? '' : $decrypted;
	}
}
