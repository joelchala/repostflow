<?php
/**
 * Bluesky network adapter (AT Protocol API).
 *
 * @package RePostFlow
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Bluesky
 */
class RPF_Bluesky extends RPF_Network_Abstract {

	public function get_slug() {
		return 'bluesky';
	}

	public function get_label() {
		return 'Bluesky';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 10.8c-1.087-2.114-4.042-6.053-6.481-7.946C3.3 1.203 1.817-.162 1.196.508.477 1.29.99 3.997 2.525 7.016c1.628 3.213 4.082 6.173 5.934 6.944l.283.118c-1.864.775-4.315 3.73-5.946 6.95C.99 20-.477 22.71.196 23.492c.62.67 2.109-.696 4.327-2.344 2.44-1.893 5.394-5.832 6.48-7.946l.027.055c1.087-2.114 4.042-6.053 6.481-7.946 2.218-1.648 3.706-3.014 4.327-2.344.673-.782-.793-3.492-2.6-6.522-1.63-3.22-4.082-6.18-5.934-6.95l-.283-.119c1.864-.775 4.315-3.73 5.946-6.95C23.01 4 24.477 1.29 23.804.508c-.62-.67-2.109.696-4.327 2.344-2.44 1.893-5.394 5.832-6.48 7.946zM12 10.8z"/></svg>';
	}

	public function get_color() {
		return '#0285FF';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'identifier' ) )
			&& ! empty( $this->get_credential( 'password' ) );
	}

	public function get_settings_fields() {
		return array(
			'identifier' => array(
				'label'       => __( 'Handle / Email', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'Your Bluesky handle (e.g., user.bsky.social) or email address.', 'repostflow' ),
			),
			'password' => array(
				'label'       => __( 'App Password', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Create an app password at Settings → App passwords in Bluesky.', 'repostflow' ),
			),
			'pds_url' => array(
				'label'       => __( 'PDS URL (optional)', 'repostflow' ),
				'type'        => 'text',
				'default'     => 'https://bsky.social',
				'description' => __( 'Custom PDS URL if using self-hosted. Default: https://bsky.social', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Post Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "{title}\n\n{url}\n\n{hashtags}",
				'description' => __( 'Max 300 characters. Available variables listed below.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$identifier = $this->get_credential( 'identifier' );
		$password   = $this->get_credential( 'password' );
		$pds_url    = $this->get_credential( 'pds_url' );
		
		if ( empty( $pds_url ) ) {
			$pds_url = 'https://bsky.social';
		}

		$response = $this->http_post_json( 
			$pds_url . '/xrpc/com.atproto.server.createSession',
			array(
				'identifier' => $identifier,
				'password'   => $password,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( isset( $response['body']['accessJwt'] ) && isset( $response['body']['handle'] ) ) {
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Bluesky handle */
					__( 'Connected as @%s', 'repostflow' ),
					$response['body']['handle']
				),
			);
		}

		$error = isset( $response['body']['message'] ) ? $response['body']['message'] : __( 'Invalid credentials', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	private function get_access_token() {
		$identifier = $this->get_credential( 'identifier' );
		$password   = $this->get_credential( 'password' );
		$pds_url    = $this->get_credential( 'pds_url' );
		
		if ( empty( $pds_url ) ) {
			$pds_url = 'https://bsky.social';
		}

		$response = $this->http_post_json(
			$pds_url . '/xrpc/com.atproto.server.createSession',
			array(
				'identifier' => $identifier,
				'password'   => $password,
			)
		);

		if ( isset( $response['body']['accessJwt'] ) ) {
			return array(
				'token'    => $response['body']['accessJwt'],
				'did'      => $response['body']['did'],
				'handle'   => $response['body']['handle'],
			);
		}

		return null;
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Bluesky not configured', 'repostflow' ) );
		}

		$auth = $this->get_access_token();
		if ( ! $auth ) {
			return $this->error( __( 'Could not authenticate with Bluesky', 'repostflow' ) );
		}

		$pds_url = $this->get_credential( 'pds_url' );
		if ( empty( $pds_url ) ) {
			$pds_url = 'https://bsky.social';
		}

		$post_data = array(
			'$type'     => 'app.bsky.feed.post',
			'text'      => mb_substr( $message, 0, 300 ),
			'createdAt' => gmdate( 'Y-m-d\TH:i:s.000\Z' ),
		);

		// Add facets for links and mentions parsing.
		$post_data['facets'] = $this->parse_facets( $message );

		if ( ! empty( $image_url ) ) {
			$blob = $this->upload_blob( $image_url, $auth['token'], $pds_url );
			if ( $blob ) {
				$post_data['embed'] = array(
					'$type'    => 'app.bsky.embed.images',
					'images'   => array(
						array(
							'alt'   => '',
							'image' => $blob,
						),
					),
				);
			}
		}

		$record = array(
			'repo'       => $auth['did'],
			'collection' => 'app.bsky.feed.post',
			'record'     => $post_data,
		);

		$response = $this->http_post_json(
			$pds_url . '/xrpc/com.atproto.repo.createRecord',
			$record,
			array(
				'Authorization' => 'Bearer ' . $auth['token'],
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if ( isset( $response['body']['uri'] ) ) {
			$uri    = $response['body']['uri'];
			$rkey   = basename( $uri );
			$post_url = "https://bsky.app/profile/{$auth['handle']}/post/{$rkey}";

			return $this->success( $uri, $post_url );
		}

		$error = isset( $response['body']['message'] ) ? $response['body']['message'] : __( 'Unknown Bluesky API error', 'repostflow' );
		return $this->error( $error );
	}

	private function upload_blob( $image_url, $token, $pds_url ) {
		$image_data = wp_remote_get( $image_url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $image_data ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $image_data );
		if ( empty( $body ) ) {
			return null;
		}

		$mime_type = wp_remote_retrieve_header( $image_data, 'content-type' );
		if ( empty( $mime_type ) ) {
			$mime_type = 'image/jpeg';
		}

		$response = wp_remote_post( $pds_url . '/xrpc/com.atproto.repo.uploadBlob', array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => $mime_type,
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $data['blob'] ) ) {
			return $data['blob'];
		}

		return null;
	}

	private function parse_facets( $text ) {
		$facets = array();

		preg_match_all( '/https?:\/\/[^\s]+/', $text, $matches, PREG_OFFSET_CAPTURE );
		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[0] as $match ) {
				$url = $match[0];
				$start = $match[1];
				$end = $start + strlen( $url );

				$facets[] = array(
					'index'    => array(
						'byteStart' => $this->count_utf8_bytes( substr( $text, 0, $start ) ),
						'byteEnd'   => $this->count_utf8_bytes( substr( $text, 0, $end ) ),
					),
					'features' => array(
						array(
							'$type' => 'app.bsky.richtext.facet#link',
							'uri'   => $url,
						),
					),
				);
			}
		}

		return $facets;
	}

	private function count_utf8_bytes( $string ) {
		return strlen( utf8_encode( $string ) );
	}
}