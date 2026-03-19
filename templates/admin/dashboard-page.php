<?php
/**
 * Dashboard page template.
 *
 * @package RePostFlow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap rpf-wrap">
	<h1 class="rpf-page-title">
		<span class="dashicons dashicons-share-alt2"></span>
		RePostFlow
		<span class="rpf-version">v<?php echo esc_html( RPF_VERSION ); ?></span>
	</h1>

	<!-- Status Banner -->
	<div class="rpf-status-banner <?php echo $enabled ? 'rpf-status-active' : 'rpf-status-paused'; ?>">
		<div class="rpf-status-left">
			<span class="rpf-status-dot"></span>
			<span class="rpf-status-text">
				<?php echo $enabled ? esc_html__( 'Auto-sharing is ACTIVE', 'repostflow' ) : esc_html__( 'Auto-sharing is PAUSED', 'repostflow' ); ?>
			</span>
		</div>
		<div class="rpf-status-right">
			<button type="button" class="button rpf-toggle-enabled" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rpf_admin_nonce' ) ); ?>">
				<?php echo $enabled ? esc_html__( 'Pause', 'repostflow' ) : esc_html__( 'Activate', 'repostflow' ); ?>
			</button>
		</div>
	</div>

	<!-- Stats Cards -->
	<div class="rpf-cards-grid">
		<div class="rpf-card">
			<div class="rpf-card-icon" style="color:#46b450;">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="rpf-card-content">
				<div class="rpf-card-number"><?php echo esc_html( $stats['total_published'] ); ?></div>
				<div class="rpf-card-label"><?php esc_html_e( 'Total Shares', 'repostflow' ); ?></div>
			</div>
		</div>

		<div class="rpf-card">
			<div class="rpf-card-icon" style="color:#0073aa;">
				<span class="dashicons dashicons-calendar-alt"></span>
			</div>
			<div class="rpf-card-content">
				<div class="rpf-card-number"><?php echo esc_html( $stats['this_week'] ); ?></div>
				<div class="rpf-card-label"><?php esc_html_e( 'This Week', 'repostflow' ); ?></div>
			</div>
		</div>

		<div class="rpf-card">
			<div class="rpf-card-icon" style="color:#826eb4;">
				<span class="dashicons dashicons-networking"></span>
			</div>
			<div class="rpf-card-content">
				<div class="rpf-card-number"><?php echo count( $configured ); ?>/<?php echo count( $networks ); ?></div>
				<div class="rpf-card-label"><?php esc_html_e( 'Networks Active', 'repostflow' ); ?></div>
			</div>
		</div>

		<div class="rpf-card">
			<div class="rpf-card-icon" style="color:#f0b849;">
				<span class="dashicons dashicons-clock"></span>
			</div>
			<div class="rpf-card-content">
				<div class="rpf-card-number"><?php echo esc_html( $next_run['human'] ); ?></div>
				<div class="rpf-card-label"><?php esc_html_e( 'Next Run', 'repostflow' ); ?></div>
			</div>
		</div>

		<div class="rpf-card">
			<div class="rpf-card-icon" style="color:#00a0d2;">
				<span class="dashicons dashicons-admin-post"></span>
			</div>
			<div class="rpf-card-content">
				<div class="rpf-card-number"><?php echo esc_html( $eligible ); ?></div>
				<div class="rpf-card-label"><?php esc_html_e( 'Eligible Posts', 'repostflow' ); ?></div>
			</div>
		</div>

		<?php if ( $stats['failed_week'] > 0 ) : ?>
		<div class="rpf-card rpf-card-warning">
			<div class="rpf-card-icon" style="color:#dc3232;">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="rpf-card-content">
				<div class="rpf-card-number"><?php echo esc_html( $stats['failed_week'] ); ?></div>
				<div class="rpf-card-label"><?php esc_html_e( 'Failed (7 days)', 'repostflow' ); ?></div>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<!-- Quick Actions -->
	<div class="rpf-section">
		<h2><?php esc_html_e( 'Quick Actions', 'repostflow' ); ?></h2>
		<div class="rpf-quick-actions">
			<button type="button" class="button button-primary rpf-share-now" id="rpf-share-now">
				<span class="dashicons dashicons-share"></span>
				<?php esc_html_e( 'Share Now', 'repostflow' ); ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=repostflow-settings' ) ); ?>" class="button">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'Settings', 'repostflow' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=repostflow-history' ) ); ?>" class="button">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'View History', 'repostflow' ); ?>
			</a>
		</div>
		<div id="rpf-share-result" class="rpf-share-result" style="display:none;"></div>
	</div>

	<!-- Connected Networks -->
	<div class="rpf-section">
		<h2><?php esc_html_e( 'Connected Networks', 'repostflow' ); ?></h2>
		<div class="rpf-networks-grid">
			<?php foreach ( $networks as $slug => $network ) : ?>
				<div class="rpf-network-card <?php echo $network->is_configured() ? 'rpf-network-active' : 'rpf-network-inactive'; ?>">
					<div class="rpf-network-icon" style="color:<?php echo esc_attr( $network->get_color() ); ?>">
						<?php echo $network->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="rpf-network-info">
						<strong><?php echo esc_html( $network->get_label() ); ?></strong>
						<span class="rpf-network-status">
							<?php if ( $network->is_configured() ) : ?>
								<span style="color:#46b450;">● <?php esc_html_e( 'Connected', 'repostflow' ); ?></span>
							<?php elseif ( $network->is_enabled() ) : ?>
								<span style="color:#f0b849;">● <?php esc_html_e( 'Needs setup', 'repostflow' ); ?></span>
							<?php else : ?>
								<span style="color:#999;">○ <?php esc_html_e( 'Disabled', 'repostflow' ); ?></span>
							<?php endif; ?>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Recent Activity -->
	<?php if ( ! empty( $recent ) ) : ?>
	<div class="rpf-section">
		<h2><?php esc_html_e( 'Recent Activity', 'repostflow' ); ?></h2>
		<table class="widefat striped rpf-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Network', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Status', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Date', 'repostflow' ); ?></th>
					<th><?php esc_html_e( 'Link', 'repostflow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent as $entry ) :
					$post_title = get_the_title( $entry->post_id );
					$net        = $this->plugin->networks->get_by_slug( $entry->network );
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $entry->post_id ) ); ?>">
							<?php echo esc_html( $post_title ? $post_title : '#' . $entry->post_id ); ?>
						</a>
					</td>
					<td>
						<?php if ( $net ) : ?>
							<span style="color:<?php echo esc_attr( $net->get_color() ); ?>">
								<?php echo $net->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<?php echo esc_html( $net->get_label() ); ?>
						<?php else : ?>
							<?php echo esc_html( ucfirst( $entry->network ) ); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( 'published' === $entry->status ) : ?>
							<span class="rpf-badge rpf-badge-success"><?php esc_html_e( 'Published', 'repostflow' ); ?></span>
						<?php elseif ( 'failed' === $entry->status ) : ?>
							<span class="rpf-badge rpf-badge-error" title="<?php echo esc_attr( $entry->response ); ?>"><?php esc_html_e( 'Failed', 'repostflow' ); ?></span>
						<?php elseif ( 'queued' === $entry->status ) : ?>
							<span class="rpf-badge rpf-badge-pending"><?php esc_html_e( 'Queued', 'repostflow' ); ?></span>
						<?php else : ?>
							<span class="rpf-badge"><?php echo esc_html( ucfirst( $entry->status ) ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $entry->shared_at ? $entry->shared_at : $entry->created_at ); ?></td>
					<td>
						<?php if ( ! empty( $entry->external_url ) ) : ?>
							<a href="<?php echo esc_url( $entry->external_url ); ?>" target="_blank" rel="noopener">↗</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</div>
