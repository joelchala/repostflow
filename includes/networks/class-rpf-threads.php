<?php
/**
 * Threads network adapter (Meta Threads API).
 *
 * @package RePostFlow
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Threads
 */
class RPF_Threads extends RPF_Network_Abstract {

	public function get_slug() {
		return 'threads';
	}

	public function get_label() {
		return 'Threads';
	}

	public function get_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.186 24h-.007c-3.581-.024-6.337-1.256-8.194-3.662C2.128 17.933 1.182 14.741 1.17 10.76V10.744C1.182 6.758 2.128 3.565 4.015 1.65 5.903-.265 8.677-1.003 12.316-.924c2.406.052 4.446.531 6.079 1.425 1.421.78 2.52 1.861 3.267 3.216.781 1.415 1.209 3.119 1.272 5.065v.012c-.012 2.452-.635 4.488-1.851 6.05-1.256 1.615-3.062 2.689-5.365 3.19-.174.04-.348.076-.522.11-.053-.454-.122-.899-.206-1.333-.067-.347-.144-.688-.231-1.022 1.722-.405 3.035-1.127 3.909-2.15.929-1.082 1.397-2.545 1.407-4.36v-.01c-.05-1.53-.389-2.837-1.008-3.9-.58-1.032-1.398-1.826-2.431-2.363-1.23-.637-2.75-.969-4.521-.988-2.931-.064-5.123.581-6.574 1.936-1.414 1.318-2.146 3.361-2.175 6.075v.013c.01 3.289.837 5.774 2.462 7.383 1.471 1.456 3.558 2.202 6.205 2.221h.006c.466 0 .94-.017 1.412-.05.098-.007.196-.015.293-.024.022.45.033.89.033 1.322 0 .305-.006.625-.018.957-.099-.008.198-.016.296-.026 2.192-.178 4.013-.816 5.433-1.896.13-.099.258-.201.384-.306-.038.319-.08.63-.128.944-.399.34-.819.649-1.262.929-1.757 1.114-3.944 1.746-6.504 1.882-.215.011-.432.02-.648.025-.585.014-1.159.014-1.744 0zm1.07-5.4c-.397-.942-.888-1.808-1.468-2.586-.689-.925-1.513-1.753-2.468-2.484.178-.02.356-.038.534-.055 2.07-.192 3.82.127 5.206.949 1.105.655 1.937 1.577 2.481 2.744-1.13.568-2.506.966-4.11 1.184-.058.008-.117.015-.175.023zm.42-4.581c-.131-.056-.263-.11-.397-.16-.179-.067-.36-.131-.544-.19-.181-.059-.364-.115-.55-.168-.093-.027-.187-.053-.281-.078-1.244-.33-2.588-.521-4.012-.575-.358-.014-.72-.021-1.085-.021-.182 0-.365.001-.549.004-.186.003-.373.007-.562.014-.092.003-.185.006-.278.01.103-.079.207-.156.313-.23 1.43-1.007 3.147-1.619 5.107-1.828 1.975-.21 3.925.1 5.818.923-.543-.112-1.1-.199-1.673-.261-.213-.023-.428-.042-.644-.058z"/></svg>';
	}

	public function get_color() {
		return '#000000';
	}

	public function is_configured() {
		return $this->is_enabled()
			&& ! empty( $this->get_credential( 'access_token' ) );
	}

	public function get_settings_fields() {
		return array(
			'access_token' => array(
				'label'       => __( 'Access Token', 'repostflow' ),
				'type'        => 'password',
				'description' => __( 'Get it from Meta for Developers → Threads API → Generate Access Token', 'repostflow' ),
			),
			'user_id' => array(
				'label'       => __( 'User ID (optional)', 'repostflow' ),
				'type'        => 'text',
				'description' => __( 'Your Threads user ID. Leave empty to auto-detect.', 'repostflow' ),
			),
			'template' => array(
				'label'       => __( 'Post Template', 'repostflow' ),
				'type'        => 'textarea',
				'default'     => "{title}\n\n{url}\n\n{hashtags}",
				'description' => __( 'Max 500 characters. Available variables listed below.', 'repostflow' ),
			),
		);
	}

	public function validate_credentials() {
		$token = $this->get_credential( 'access_token' );

		$url      = 'https://graph.threads.net/v1.0/me?fields=id,username';
		$response = $this->http_get( $url, array(
			'Authorization' => 'Bearer ' . $token,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'valid' => false, 'message' => $response->get_error_message() );
		}

		if ( isset( $response['body']['id'] ) && isset( $response['body']['username'] ) ) {
			$user_id = $this->get_credential( 'user_id' );
			if ( empty( $user_id ) ) {
				RePostFlow::update_option( 'networks.threads.user_id', $response['body']['id'] );
			}
			return array(
				'valid'   => true,
				'message' => sprintf(
					/* translators: %s: Threads username */
					__( 'Connected as @%s', 'repostflow' ),
					$response['body']['username']
				),
			);
		}

		$error = isset( $response['body']['error']['message'] ) ? $response['body']['error']['message'] : __( 'Invalid access token', 'repostflow' );
		return array( 'valid' => false, 'message' => $error );
	}

	public function publish( $post_id, $message, $image_url = '' ) {
		if ( ! $this->is_configured() ) {
			return $this->error( __( 'Threads not configured', 'repostflow' ) );
		}

		$access_token    = $this->get_credential( 'access_token' );
		$user_id         = $this->get_credential( 'user_id' );

		if ( empty( $user_id ) ) {
			$me_response = $this->http_get( 'https://graph.threads.net/v1.0/me?fields=id', array(
				'Authorization' => 'Bearer ' . $access_token,
			) );
			if ( isset( $me_response['body']['id'] ) ) {
				$user_id = $me_response['body']['id'];
			} else {
				return $this->error( __( 'Could not retrieve user ID', 'repostflow' ) );
			}
		}

		$media_type = empty( $image_url ) ? 'TEXT' : 'IMAGE';

		$container_data = array(
			'media_type' => $media_type,
			'text'        => mb_substr( $message, 0, 500 ),
		);

		if ( ! empty( $image_url ) ) {
			$container_data['image_url'] = $image_url;
		}

		$container_url = "https://graph.threads.net/v1.0/{$user_id}/threads";

		$container_response = $this->http_post_json( $container_url, $container_data, array(
			'Authorization' => 'Bearer ' . $access_token,
		) );

		if ( is_wp_error( $container_response ) ) {
			return $this->error( $container_response->get_error_message() );
		}

		if ( ! isset( $container_response['body']['id'] ) ) {
			$error = isset( $container_response['body']['error']['message'] ) 
				? $container_response['body']['error']['message'] 
				: __( 'Failed to create thread container', 'repostflow' );
			return $this->error( $error );
		}

		$container_id = $container_response['body']['id'];

		$publish_data = array(
			'creation_id' => $container_id,
		);

		$publish_url = "https://graph.threads.net/v1.0/{$user_id}/threads_publish";

		$publish_response = $this->http_post_json( $publish_url, $publish_data, array(
			'Authorization' => 'Bearer ' . $access_token,
		) );

		if ( is_wp_error( $publish_response ) ) {
			return $this->error( $publish_response->get_error_message() );
		}

		if ( isset( $publish_response['body']['id'] ) ) {
			$thread_id    = $publish_response['body']['id'];
			$username_url = "https://graph.threads.net/v1.0/me?fields=username&access_token=" . $access_token;
			$username_resp = $this->http_get( $username_url );
			$username     = isset( $username_resp['body']['username'] ) ? $username_resp['body']['username'] : '';
			$thread_url   = "https://www.threads.net/@{$username}/post/{$thread_id}";

			return $this->success( $thread_id, $thread_url );
		}

		$error = isset( $publish_response['body']['error']['message'] ) 
			? $publish_response['body']['error']['message'] 
			: __( 'Unknown Threads API error', 'repostflow' );
		return $this->error( $error );
	}
}