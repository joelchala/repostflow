<?php
/**
 * Queue management page template.
 *
 * @package RePostFlow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap rpf-wrap">
	<h1 class="rpf-page-title">
		<span class="dashicons dashicons-list-view"></span>
		<?php esc_html_e( 'Sharing Queue', 'repostflow' ); ?>
	</h1>

	<?php if ( ! empty( $queue_items ) ) : ?>
		<div class="rpf-section">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
				<p>
					<?php
					printf(
						/* translators: %d: number of queued items */
						esc_html__( '%d items in queue', 'repostflow' ),
						count( $queue_items )
					);
					?>
				</p>
				<button type="button" class="button rpf-clear-queue" id="rpf-clear-queue">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Clear Queue', 'repostflow' ); ?>
				</button>
			</div>

			<table class="widefat striped rpf-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Post', 'repostflow' ); ?></th>
						<th><?php esc_html_e( 'Network', 'repostflow' ); ?></th>
						<th><?php esc_html_e( 'Message Preview', 'repostflow' ); ?></th>
						<th><?php esc_html_e( 'Scheduled', 'repostflow' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'repostflow' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$network_labels = array(
						'twitter'   => __( 'X (Twitter)', 'repostflow' ),
						'facebook'  => __( 'Facebook', 'repostflow' ),
						'instagram' => __( 'Instagram', 'repostflow' ),
						'linkedin'  => __( 'LinkedIn', 'repostflow' ),
						'telegram'  => __( 'Telegram', 'repostflow' ),
						'pinterest' => __( 'Pinterest', 'repostflow' ),
						'threads'   => __( 'Threads', 'repostflow' ),
						'bluesky'   => __( 'Bluesky', 'repostflow' ),
						'discord'   => __( 'Discord', 'repostflow' ),
						'mastodon'  => __( 'Mastodon', 'repostflow' ),
					);
					foreach ( $queue_items as $item ) :
						$network_label = isset( $network_labels[ $item->network ] ) ? $network_labels[ $item->network ] : ucfirst( $item->network );
					?>
					<tr id="queue-row-<?php echo esc_attr( $item->id ); ?>">
						<td>
							<strong><?php echo esc_html( get_the_title( $item->post_id ) ); ?></strong>
						</td>
						<td><?php echo esc_html( $network_label ); ?></td>
						<td>
							<span class="rpf-message-preview"><?php echo esc_html( wp_trim_words( $item->message, 15 ) ); ?></span>
						</td>
						<td><?php echo esc_html( $item->scheduled_at ? $item->scheduled_at : __( 'Next run', 'repostflow' ) ); ?></td>
						<td>
							<button type="button" class="button button-small rpf-delete-log" data-id="<?php echo esc_attr( $item->id ); ?>">
								<?php esc_html_e( 'Remove', 'repostflow' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div class="rpf-empty-state">
			<span class="dashicons dashicons-yes-alt" style="font-size:48px;width:48px;height:48px;color:#46b450;"></span>
			<h2><?php esc_html_e( 'Queue is empty', 'repostflow' ); ?></h2>
			<p><?php esc_html_e( 'No posts are waiting to be shared. New posts will be added when the schedule runs.', 'repostflow' ); ?></p>
		</div>
	<?php endif; ?>
</div>
