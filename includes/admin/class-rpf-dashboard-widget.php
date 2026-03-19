<?php
/**
 * WordPress dashboard widget.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Dashboard_Widget
 */
class RPF_Dashboard_Widget {

	/** @var RePostFlow */
	private $plugin;

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	public function init() {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
	}

	public function register() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'rpf_dashboard_widget',
			__( 'RePostFlow — Social Sharing', 'repostflow' ),
			array( $this, 'render' )
		);
	}

	public function render() {
		$stats    = $this->plugin->logger->get_stats();
		$next_run = RPF_Scheduler::get_next_run();
		$enabled  = RePostFlow::get_option( 'general.enabled', false );
		$recent   = $this->plugin->logger->get_history( array( 'per_page' => 5 ) );
		?>
		<div class="rpf-widget">
			<div class="rpf-widget-status" style="margin-bottom:12px;">
				<strong><?php esc_html_e( 'Status:', 'repostflow' ); ?></strong>
				<?php if ( $enabled ) : ?>
					<span style="color:#46b450;">● <?php esc_html_e( 'Active', 'repostflow' ); ?></span>
				<?php else : ?>
					<span style="color:#dc3232;">● <?php esc_html_e( 'Paused', 'repostflow' ); ?></span>
				<?php endif; ?>
				<span style="float:right;color:#999;">
					<?php
					/* translators: %s: human-readable time until next run */
					printf( esc_html__( 'Next run: %s', 'repostflow' ), esc_html( $next_run['human'] ) );
					?>
				</span>
			</div>

			<div class="rpf-widget-stats" style="display:flex;gap:15px;margin-bottom:12px;">
				<div><strong><?php echo esc_html( $stats['this_week'] ); ?></strong><br><small><?php esc_html_e( 'This week', 'repostflow' ); ?></small></div>
				<div><strong><?php echo esc_html( $stats['this_month'] ); ?></strong><br><small><?php esc_html_e( 'This month', 'repostflow' ); ?></small></div>
				<div><strong><?php echo esc_html( $stats['total_published'] ); ?></strong><br><small><?php esc_html_e( 'Total', 'repostflow' ); ?></small></div>
				<?php if ( $stats['failed_week'] > 0 ) : ?>
					<div style="color:#dc3232;"><strong><?php echo esc_html( $stats['failed_week'] ); ?></strong><br><small><?php esc_html_e( 'Failed', 'repostflow' ); ?></small></div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $recent ) ) : ?>
				<table class="widefat striped" style="margin-bottom:12px;">
					<thead><tr>
						<th><?php esc_html_e( 'Post', 'repostflow' ); ?></th>
						<th><?php esc_html_e( 'Network', 'repostflow' ); ?></th>
						<th><?php esc_html_e( 'Status', 'repostflow' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $recent as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( get_the_title( $entry->post_id ) ); ?></td>
							<td><?php echo esc_html( ucfirst( $entry->network ) ); ?></td>
							<td>
								<?php if ( 'published' === $entry->status ) : ?>
									<span style="color:#46b450;">✓</span>
								<?php elseif ( 'failed' === $entry->status ) : ?>
									<span style="color:#dc3232;">✗</span>
								<?php else : ?>
									<span style="color:#f0b849;">◌</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<p style="text-align:center;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=repostflow' ) ); ?>" class="button">
					<?php esc_html_e( 'Open RePostFlow', 'repostflow' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
