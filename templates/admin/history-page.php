<?php
/**
 * Share history page template.
 *
 * @package RePostFlow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap rpf-wrap">
	<h1 class="rpf-page-title">
		<span class="dashicons dashicons-backup"></span>
		<?php esc_html_e( 'Share History', 'repostflow' ); ?>
	</h1>

	<!-- Filters -->
	<div class="rpf-filters" style="margin-bottom:15px;">
		<form method="get" style="display:inline-flex;gap:10px;align-items:center;">
			<input type="hidden" name="page" value="repostflow-history">

			<select name="network">
				<option value=""><?php esc_html_e( 'All Networks', 'repostflow' ); ?></option>
				<?php foreach ( $networks as $slug => $net ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( isset( $_GET['network'] ) ? $_GET['network'] : '', $slug ); ?>>
						<?php echo esc_html( $net->get_label() ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'repostflow' ); ?></option>
				<option value="published" <?php selected( isset( $_GET['status'] ) ? $_GET['status'] : '', 'published' ); ?>><?php esc_html_e( 'Published', 'repostflow' ); ?></option>
				<option value="failed" <?php selected( isset( $_GET['status'] ) ? $_GET['status'] : '', 'failed' ); ?>><?php esc_html_e( 'Failed', 'repostflow' ); ?></option>
				<option value="queued" <?php selected( isset( $_GET['status'] ) ? $_GET['status'] : '', 'queued' ); ?>><?php esc_html_e( 'Queued', 'repostflow' ); ?></option>
			</select>

			<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'repostflow' ); ?>">
		</form>
	</div>

	<?php if ( ! empty( $history ) ) : ?>
		<table class="widefat striped rpf-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Network', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Message', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Status', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Date', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Link', 'repostflow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $history as $entry ) :
					$net = isset( $networks[ $entry->network ] ) ? $networks[ $entry->network ] : null;
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $entry->post_id ) ); ?>">
							<?php echo esc_html( get_the_title( $entry->post_id ) ?: '#' . $entry->post_id ); ?>
						</a>
					</td>
					<td>
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
						if ( $net ) :
							?>
							<span style="color:<?php echo esc_attr( $net->get_color() ); ?>">
								<?php echo $net->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<?php echo esc_html( $net->get_label() ); ?>
						<?php else : ?>
							<?php echo esc_html( isset( $network_labels[ $entry->network ] ) ? $network_labels[ $entry->network ] : ucfirst( $entry->network ) ); ?>
						<?php endif; ?>
					</td>
					<td>
						<span class="rpf-message-preview" title="<?php echo esc_attr( $entry->message ); ?>">
							<?php echo esc_html( wp_trim_words( $entry->message, 10 ) ); ?>
						</span>
					</td>
					<td>
						<?php
						$status_labels = array(
							'published'  => __( 'Published', 'repostflow' ),
							'failed'     => __( 'Failed', 'repostflow' ),
							'queued'     => __( 'Queued', 'repostflow' ),
							'processing' => __( 'Processing', 'repostflow' ),
							'skipped'    => __( 'Skipped by filter', 'repostflow' ),
						);
						$status_label  = isset( $status_labels[ $entry->status ] ) ? $status_labels[ $entry->status ] : ucfirst( $entry->status );
						$badge_class   = 'published' === $entry->status ? 'rpf-badge-success' : ( 'failed' === $entry->status ? 'rpf-badge-error' : 'rpf-badge-pending' );
						?>
						<span class="rpf-badge <?php echo esc_attr( $badge_class ); ?>" <?php echo 'failed' === $entry->status && ! empty( $entry->response ) ? 'title="' . esc_attr( $entry->response ) . '"' : ''; ?>>
							<?php echo esc_html( $status_label ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $entry->shared_at ? $entry->shared_at : $entry->created_at ); ?></td>
					<td>
						<?php if ( ! empty( $entry->external_url ) ) : ?>
							<a href="<?php echo esc_url( $entry->external_url ); ?>" target="_blank" rel="noopener">↗ <?php esc_html_e( 'View', 'repostflow' ); ?></a>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				echo paginate_links( array(
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'current' => $current_page,
					'total'   => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				) );
				?>
			</div>
		</div>
		<?php endif; ?>

	<?php else : ?>
		<div class="rpf-empty-state">
			<span class="dashicons dashicons-backup" style="font-size:48px;width:48px;height:48px;color:#ccc;"></span>
			<h2><?php esc_html_e( 'No history yet', 'repostflow' ); ?></h2>
			<p><?php esc_html_e( 'Shared posts will appear here. Enable auto-sharing or use "Share Now" to get started.', 'repostflow' ); ?></p>
		</div>
	<?php endif; ?>
</div>
