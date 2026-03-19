<?php
/**
 * Template engine for social media message formatting.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Template_Engine
 */
class RPF_Template_Engine {

	/**
	 * Character limits per network.
	 *
	 * @var array
	 */
	private $limits = array(
		'twitter'   => 280,
		'facebook'  => 5000,
		'instagram' => 2200,
		'linkedin'  => 3000,
		'telegram'  => 4096,
		'pinterest' => 500,
		'threads'   => 500,
		'bluesky'   => 300,
		'discord'   => 2000,
		'mastodon'  => 500,
	);

	/**
	 * Render a template with post data.
	 *
	 * @param string $template Template string with variables.
	 * @param int    $post_id  WordPress post ID.
	 * @param string $network  Network slug for character limit.
	 * @return string Rendered message.
	 */
	public function render( $template, $post_id, $network = '' ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$variables = $this->get_variables( $post );

		/**
		 * Allow adding custom template variables.
		 *
		 * @param array    $variables Template variables.
		 * @param \WP_Post $post      Post object.
		 * @param string   $network   Network slug.
		 */
		$variables = apply_filters( 'rpf_template_variables', $variables, $post, $network );

		// Replace variables in template.
		$message = $template;
		foreach ( $variables as $key => $value ) {
			$message = str_replace( '{' . $key . '}', $value, $message );
		}

		// Apply character limit if needed.
		if ( ! empty( $network ) && isset( $this->limits[ $network ] ) ) {
			$limit = $this->limits[ $network ];
			if ( mb_strlen( $message ) > $limit ) {
				$message = mb_substr( $message, 0, $limit - 3 ) . '...';
			}
		}

		return trim( $message );
	}

	/**
	 * Get all available template variables for a post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Key-value pairs of template variables.
	 */
	public function get_variables( $post ) {
		$url       = get_permalink( $post->ID );
		$utm       = $this->build_utm_url( $url );
		$excerpt   = has_excerpt( $post->ID )
			? wp_strip_all_tags( get_the_excerpt( $post ) )
			: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );

		$categories = get_the_category( $post->ID );
		$cat_names  = ! empty( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();

		$tags      = get_the_tags( $post->ID );
		$tag_names = ! empty( $tags ) ? wp_list_pluck( $tags, 'name' ) : array();

		$hashtags = array_map( function( $tag ) {
			return '#' . preg_replace( '/\s+/', '', $tag );
		}, $tag_names );

		return array(
			'title'        => get_the_title( $post->ID ),
			'url'          => $utm ? $utm : $url,
			'raw_url'      => $url,
			'excerpt'      => $excerpt,
			'author'       => get_the_author_meta( 'display_name', $post->post_author ),
			'date'         => get_the_date( '', $post->ID ),
			'categories'   => implode( ', ', $cat_names ),
			'tags'         => implode( ', ', $tag_names ),
			'hashtags'     => implode( ' ', $hashtags ),
			'site_name'    => get_bloginfo( 'name' ),
			'site_url'     => home_url( '/' ),
			'newline'      => "\n",
		);
	}

	/**
	 * Build URL with UTM parameters.
	 *
	 * @param string $url Original URL.
	 * @return string|false URL with UTM params or false if disabled.
	 */
	private function build_utm_url( $url ) {
		$enabled = RePostFlow::get_option( 'advanced.utm_enabled', false );
		if ( ! $enabled ) {
			return false;
		}

		$params = array(
			'utm_source'   => RePostFlow::get_option( 'advanced.utm_source', 'repostflow' ),
			'utm_medium'   => RePostFlow::get_option( 'advanced.utm_medium', 'social' ),
		);

		$campaign = RePostFlow::get_option( 'advanced.utm_campaign', '' );
		if ( ! empty( $campaign ) ) {
			$params['utm_campaign'] = $campaign;
		}

		return add_query_arg( $params, $url );
	}

	/**
	 * Get available variable names with descriptions.
	 *
	 * @return array
	 */
	public static function get_available_variables() {
		return array(
			'{title}'      => __( 'Post title', 'repostflow' ),
			'{url}'        => __( 'Post URL (with UTM if enabled)', 'repostflow' ),
			'{raw_url}'    => __( 'Post URL (without UTM)', 'repostflow' ),
			'{excerpt}'    => __( 'Post excerpt (max 30 words)', 'repostflow' ),
			'{author}'     => __( 'Post author name', 'repostflow' ),
			'{date}'       => __( 'Publication date', 'repostflow' ),
			'{categories}' => __( 'Comma-separated categories', 'repostflow' ),
			'{tags}'       => __( 'Comma-separated tags', 'repostflow' ),
			'{hashtags}'   => __( 'Tags as #hashtags', 'repostflow' ),
			'{site_name}'  => __( 'Site name', 'repostflow' ),
			'{site_url}'   => __( 'Site URL', 'repostflow' ),
			'{newline}'    => __( 'Line break', 'repostflow' ),
		);
	}
}
