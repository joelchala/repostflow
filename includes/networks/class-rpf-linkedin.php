<?php
/**
 * LinkedIn network adapter (v2 API).
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_LinkedIn
 */
class RPF_LinkedIn extends RPF_Network_Abstract {

	public function get_slug() {
		return 'linkedin';
	}

	public function get_label() {
		return 'LinkedIn';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
	}

	public function get_color() {
		return '#0A66C2';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'access_token' ) )
			&& ! empty( $this->get_credential( 'person_urn' ) );
	}

	public function get_settings_fields() {
		return array(
			'person_urn' => array(
				'label'       => __( 'Person URN or Organization URN', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'Example: urn:li:person:ABC123 or urn:li:organization:12345', 'repostflow' ),
			),
			'access_token' => array(
				'label'       => __( 'Access Token', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'OAuth 2.0 access token with w_member_social or w_organization_social scope.', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Post Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "{title}\n\n{excerpt}\n\n{url}",
				'description' => __( 'Max 3000 characters.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$token = $this->get_credential( 'access_token' );

		$response = $this->http_get( 'https://api.linkedin.com/v2/userinfo', array(
			'Authorization' => 'Bearer ' . $token,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === $response['code'] && isset( $response['body']['name'] ) ) {
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: LinkedIn user name */
					__( 'Connected as %s', 'repostflow' ),
					$response['body']['name']
				),
			);
		}

		return array( 'valid' => false, 'message' => __( 'Invalid credentials', 'repostflow' ) );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'LinkedIn not configured', 'repostflow' ) );
		}

		$token      = $this->get_credential( 'access_token' );
		$author_urn = $this->get_credential( 'person_urn' );
		$post_url   = get_permalink( $post_id );

		$body = array(
			'author'         => $author_urn,
			'lifecycleState' => 'PUBLISHED',
			'specificContent' => array(
				'com.linkedin.ugc.ShareContent' => array(
					'shareCommentary' => array(
						'text' => $message,
					),
					'shareMediaCategory' => 'ARTICLE',
					'media' => array(
						array(
							'status'       => 'READY',
							'originalUrl'  => $post_url,
						),
					),
				),
			),
			'visibility' => array(
				'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
			),
		);

		// Add thumbnail if available.
		if ( ! empty( $image_url ) ) {
			$body['specificContent']['com.linkedin.ugc.ShareContent']['media'][0]['thumbnails'] = array(
				array( 'url' => $image_url ),
			);
		}

		$response = $this->http_post_json( 'https://api.linkedin.com/v2/ugcPosts', $body, array(
			'Authorization'            => 'Bearer ' . $token,
			'X-Restli-Protocol-Version' => '2.0.0',
		) );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message() );
		}

		if ( isset( $response['body']['id'] ) ) {
			$li_id = $response['body']['id'];
			return $this->success( $li_id, '' );
		}

		$error = isset( $response['body']['message'] ) ? $response['body']['message'] : __( 'Unknown LinkedIn API error', 'repostflow' );
		return $this->error( $error );
	}
}
