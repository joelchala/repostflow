<?php
/**
 * Smart post selector for choosing which content to share.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Post_Selector
 */
class RPF_Post_Selector {

	/**
	 * Select a random post that hasn't been shared recently.
	 *
	 * @param array $exclude_ids Additional post IDs to exclude.
	 * @return \WP_Post|null
	 */
	public function pick( $exclude_ids = array() ) {
		$post_types     = RePostFlow::get_option( 'general.post_types', array( 'post' ) );
		$cat_include    = RePostFlow::get_option( 'general.categories_include', array() );
		$cat_exclude    = RePostFlow::get_option( 'general.categories_exclude', array() );
		$min_age_days   = (int) RePostFlow::get_option( 'general.min_post_age_days', 0 );
		$cooldown_days  = (int) RePostFlow::get_option( 'general.cooldown_days', 7 );

		// Get recently shared post IDs from log.
		$logger            = new RPF_Logger();
		$recently_shared   = $logger->get_recently_shared_ids( $cooldown_days );
		$exclude_ids       = array_merge( $exclude_ids, $recently_shared );
		$exclude_ids       = array_unique( array_filter( array_map( 'absint', $exclude_ids ) ) );

		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'rand',
			'no_found_rows'  => true,
		);

		// Exclude recently shared.
		if ( ! empty( $exclude_ids ) ) {
			$query_args['post__not_in'] = $exclude_ids;
		}

		// Category filters.
		if ( ! empty( $cat_include ) ) {
			$query_args['category__in'] = array_map( 'absint', $cat_include );
		}
		if ( ! empty( $cat_exclude ) ) {
			$query_args['category__not_in'] = array_map( 'absint', $cat_exclude );
		}

		// Minimum post age.
		if ( $min_age_days > 0 ) {
			$query_args['date_query'] = array(
				array(
					'before' => $min_age_days . ' days ago',
				),
			);
		}

		/**
		 * Filter the post selection query arguments.
		 *
		 * @param array $query_args WP_Query arguments.
		 */
		$query_args = apply_filters( 'rpf_post_query_args', $query_args );

		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
			return $query->posts[0];
		}

		// Fallback: relax cooldown by half and try again.
		if ( $cooldown_days > 1 ) {
			$relaxed_shared = $logger->get_recently_shared_ids( intval( $cooldown_days / 2 ) );
			$query_args['post__not_in'] = array_unique( array_filter( array_map( 'absint', $relaxed_shared ) ) );

			$query = new WP_Query( $query_args );
			if ( $query->have_posts() ) {
				return $query->posts[0];
			}
		}

		return null;
	}

	/**
	 * Pick multiple posts at once (for batch operations).
	 *
	 * @param int   $count       Number of posts to pick.
	 * @param array $exclude_ids Additional post IDs to exclude.
	 * @return array Array of WP_Post objects.
	 */
	public function pick_multiple( $count = 1, $exclude_ids = array() ) {
		$posts = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$post = $this->pick( $exclude_ids );
			if ( $post ) {
				$posts[]       = $post;
				$exclude_ids[] = $post->ID;
			}
		}

		return $posts;
	}

	/**
	 * Get total eligible posts count.
	 *
	 * @return int
	 */
	public function get_eligible_count() {
		$post_types    = RePostFlow::get_option( 'general.post_types', array( 'post' ) );
		$cooldown_days = (int) RePostFlow::get_option( 'general.cooldown_days', 7 );
		$logger        = new RPF_Logger();
		$recently_shared = $logger->get_recently_shared_ids( $cooldown_days );

		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		if ( ! empty( $recently_shared ) ) {
			$query_args['post__not_in'] = $recently_shared;
		}

		$query = new WP_Query( $query_args );
		return $query->post_count;
	}
}
