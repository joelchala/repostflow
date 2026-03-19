<?php
/**
 * Settings page template.
 *
 * @package RePostFlow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( 'rpf_settings', array() );
$post_types = get_post_types( array( 'public' => true ), 'objects' );
$categories = get_categories( array( 'hide_empty' => false ) );
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
?>
<div class="wrap rpf-wrap">
	<h1 class="rpf-page-title">
		<span class="dashicons dashicons-admin-generic"></span>
		<?php esc_html_e( 'RePostFlow Settings', 'repostflow' ); ?>
	</h1>

	<!-- Tabs Navigation -->
	<nav class="nav-tab-wrapper rpf-tabs">
		<a href="?page=repostflow-settings&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'repostflow' ); ?>
		</a>
		<a href="?page=repostflow-settings&tab=schedule" class="nav-tab <?php echo 'schedule' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Schedule', 'repostflow' ); ?>
		</a>
		<a href="?page=repostflow-settings&tab=networks" class="nav-tab <?php echo 'networks' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Networks', 'repostflow' ); ?>
		</a>
		<a href="?page=repostflow-settings&tab=advanced" class="nav-tab <?php echo 'advanced' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Advanced', 'repostflow' ); ?>
		</a>
	</nav>

	<form method="post" action="">
		<?php wp_nonce_field( 'rpf_settings_nonce' ); ?>

		<!-- GENERAL TAB -->
		<?php if ( 'general' === $active_tab ) : ?>
		<table class="form-table rpf-form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Auto-Sharing', 'repostflow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="rpf_enabled" value="1" <?php checked( RePostFlow::get_option( 'general.enabled', false ) ); ?>>
						<?php esc_html_e( 'Automatically share posts to connected social networks', 'repostflow' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Post Types', 'repostflow' ); ?></th>
				<td>
					<?php
					$selected_types = RePostFlow::get_option( 'general.post_types', array( 'post' ) );
					foreach ( $post_types as $pt ) :
						if ( 'attachment' === $pt->name ) continue;
					?>
						<label style="display:block;margin-bottom:5px;">
							<input type="checkbox" name="rpf_post_types[]" value="<?php echo esc_attr( $pt->name ); ?>"
								<?php checked( in_array( $pt->name, $selected_types, true ) ); ?>>
							<?php echo esc_html( $pt->labels->name ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Select which post types to include in auto-sharing.', 'repostflow' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Include Categories', 'repostflow' ); ?></th>
				<td>
					<select name="rpf_cat_include[]" multiple style="min-width:300px;min-height:100px;">
						<?php
						$cat_include = RePostFlow::get_option( 'general.categories_include', array() );
						foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php echo in_array( $cat->term_id, $cat_include ) ? 'selected' : ''; ?>>
								<?php echo esc_html( $cat->name ); ?> (<?php echo esc_html( $cat->count ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Leave empty to include all categories. Hold Ctrl/Cmd to select multiple.', 'repostflow' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude Categories', 'repostflow' ); ?></th>
				<td>
					<select name="rpf_cat_exclude[]" multiple style="min-width:300px;min-height:100px;">
						<?php
						$cat_exclude = RePostFlow::get_option( 'general.categories_exclude', array() );
						foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php echo in_array( $cat->term_id, $cat_exclude ) ? 'selected' : ''; ?>>
								<?php echo esc_html( $cat->name ); ?> (<?php echo esc_html( $cat->count ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Minimum Post Age', 'repostflow' ); ?></th>
				<td>
					<input type="number" name="rpf_min_age" value="<?php echo esc_attr( RePostFlow::get_option( 'general.min_post_age_days', 0 ) ); ?>" min="0" max="365" class="small-text">
					<?php esc_html_e( 'days', 'repostflow' ); ?>
					<p class="description"><?php esc_html_e( 'Only share posts older than this many days. Set 0 to include all.', 'repostflow' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Cooldown Period', 'repostflow' ); ?></th>
				<td>
					<input type="number" name="rpf_cooldown" value="<?php echo esc_attr( RePostFlow::get_option( 'general.cooldown_days', 7 ) ); ?>" min="1" max="365" class="small-text">
					<?php esc_html_e( 'days', 'repostflow' ); ?>
					<p class="description"><?php esc_html_e( 'Don\'t re-share a post within this many days of its last share.', 'repostflow' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Posts Per Run', 'repostflow' ); ?></th>
				<td>
					<input type="number" name="rpf_posts_per_run" value="<?php echo esc_attr( RePostFlow::get_option( 'general.posts_per_run', 1 ) ); ?>" min="1" max="10" class="small-text">
					<p class="description"><?php esc_html_e( 'How many posts to share each time the schedule runs.', 'repostflow' ); ?></p>
				</td>
			</tr>
		</table>
		<?php endif; ?>

		<!-- SCHEDULE TAB -->
		<?php if ( 'schedule' === $active_tab ) : ?>
		<table class="form-table rpf-form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Sharing Frequency', 'repostflow' ); ?></th>
				<td>
					<select name="rpf_frequency">
						<?php
						$current_freq = RePostFlow::get_option( 'schedule.frequency', 'daily' );
						foreach ( $frequencies as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current_freq, $slug ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'How often RePostFlow should select and share posts.', 'repostflow' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Next Scheduled Run', 'repostflow' ); ?></th>
				<td>
					<?php
					$next = RPF_Scheduler::get_next_run();
					if ( $next['timestamp'] ) :
						echo esc_html( date_i18n( 'F j, Y g:i A', $next['timestamp'] ) );
						echo ' <em>(' . esc_html( $next['human'] ) . ')</em>';
					else :
						esc_html_e( 'Not scheduled. Save settings to schedule.', 'repostflow' );
					endif;
					?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'WP-Cron Note', 'repostflow' ); ?></th>
				<td>
					<div class="notice notice-info inline" style="margin:0;padding:8px 12px;">
						<p>
							<?php
							printf(
								/* translators: %s: example cron command */
								esc_html__( 'WordPress cron depends on site traffic. For reliable scheduling, set up a real cron job: %s', 'repostflow' ),
								'<code>*/15 * * * * wget -q -O /dev/null ' . esc_html( home_url( '/wp-cron.php' ) ) . '</code>'
							);
							?>
						</p>
					</div>
				</td>
			</tr>
		</table>
		<?php endif; ?>

		<!-- NETWORKS TAB -->
		<?php if ( 'networks' === $active_tab ) : ?>
		<div class="rpf-networks-settings">
			<?php foreach ( $networks as $slug => $network ) : ?>
			<div class="rpf-network-panel">
				<div class="rpf-network-panel-header" data-toggle="rpf-panel-<?php echo esc_attr( $slug ); ?>">
					<span class="rpf-network-panel-icon" style="color:<?php echo esc_attr( $network->get_color() ); ?>">
						<?php echo $network->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="rpf-network-panel-title"><?php echo esc_html( $network->get_label() ); ?></span>
					<span class="rpf-network-panel-status">
						<?php if ( $network->is_configured() ) : ?>
							<span style="color:#46b450;">● <?php esc_html_e( 'Connected', 'repostflow' ); ?></span>
						<?php else : ?>
							<span style="color:#999;">○ <?php esc_html_e( 'Not configured', 'repostflow' ); ?></span>
						<?php endif; ?>
					</span>
					<span class="rpf-network-panel-arrow dashicons dashicons-arrow-down-alt2"></span>
				</div>

				<div class="rpf-network-panel-body" id="rpf-panel-<?php echo esc_attr( $slug ); ?>" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable', 'repostflow' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="rpf_network_<?php echo esc_attr( $slug ); ?>_enabled" value="1"
										<?php checked( $network->is_enabled() ); ?>>
									<?php
									/* translators: %s: network name */
									printf( esc_html__( 'Enable sharing to %s', 'repostflow' ), esc_html( $network->get_label() ) );
									?>
								</label>
							</td>
						</tr>

						<?php foreach ( $network->get_settings_fields() as $field_key => $field ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $field['label'] ); ?></th>
							<td>
								<?php
								$field_name  = "rpf_network_{$slug}_{$field_key}";
								$field_value = RePostFlow::get_option( "networks.{$slug}.{$field_key}", isset( $field['default'] ) ? $field['default'] : '' );

								// Mask encrypted values.
								if ( 'password' === $field['type'] && ! empty( $field_value ) ) {
									$display_value = '••••••••';
								} else {
									$display_value = $field_value;
								}

								switch ( $field['type'] ) :
									case 'text':
									case 'password':
								?>
										<input type="<?php echo esc_attr( $field['type'] ); ?>"
											name="<?php echo esc_attr( $field_name ); ?>"
											value="<?php echo esc_attr( $display_value ); ?>"
											class="regular-text">
								<?php break;
									case 'textarea':
								?>
										<textarea name="<?php echo esc_attr( $field_name ); ?>"
											class="large-text" rows="4"><?php echo esc_textarea( $display_value ); ?></textarea>
								<?php break;
									case 'select':
								?>
										<select name="<?php echo esc_attr( $field_name ); ?>">
											<?php foreach ( $field['options'] as $opt_value => $opt_label ) : ?>
												<option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $field_value, $opt_value ); ?>>
													<?php echo esc_html( $opt_label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
								<?php break;
								endswitch;
								?>

								<?php if ( ! empty( $field['description'] ) ) : ?>
									<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>

						<tr>
							<th scope="row"><?php esc_html_e( 'Test Connection', 'repostflow' ); ?></th>
							<td>
								<button type="button" class="button rpf-test-connection" data-network="<?php echo esc_attr( $slug ); ?>">
									<?php esc_html_e( 'Test Connection', 'repostflow' ); ?>
								</button>
								<span class="rpf-test-result" id="rpf-test-<?php echo esc_attr( $slug ); ?>"></span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<?php endforeach; ?>

			<!-- Template Variables Reference -->
			<div class="rpf-section" style="margin-top:20px;">
				<h3><?php esc_html_e( 'Available Template Variables', 'repostflow' ); ?></h3>
				<table class="widefat striped" style="max-width:600px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Variable', 'repostflow' ); ?></th>
							<th><?php esc_html_e( 'Description', 'repostflow' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $variables as $var => $desc ) : ?>
						<tr>
							<td><code><?php echo esc_html( $var ); ?></code></td>
							<td><?php echo esc_html( $desc ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endif; ?>

		<!-- ADVANCED TAB -->
		<?php if ( 'advanced' === $active_tab ) : ?>
		<table class="form-table rpf-form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Include Featured Image', 'repostflow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="rpf_include_image" value="1" <?php checked( RePostFlow::get_option( 'advanced.include_image', true ) ); ?>>
						<?php esc_html_e( 'Attach the featured image to social media posts when available', 'repostflow' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'UTM Parameters', 'repostflow' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="rpf_utm_enabled" value="1" <?php checked( RePostFlow::get_option( 'advanced.utm_enabled', false ) ); ?>>
						<?php esc_html_e( 'Add UTM tracking parameters to shared URLs', 'repostflow' ); ?>
					</label>

					<div class="rpf-utm-fields" style="margin-top:10px;">
					<label style="display:block;margin-bottom:5px;">
						<strong><?php esc_html_e( 'utm_source:', 'repostflow' ); ?></strong>
						<input type="text" name="rpf_utm_source" value="<?php echo esc_attr( RePostFlow::get_option( 'advanced.utm_source', 'repostflow' ) ); ?>" class="regular-text">
					</label>
					<label style="display:block;margin-bottom:5px;">
						<strong><?php esc_html_e( 'utm_medium:', 'repostflow' ); ?></strong>
						<input type="text" name="rpf_utm_medium" value="<?php echo esc_attr( RePostFlow::get_option( 'advanced.utm_medium', 'social' ) ); ?>" class="regular-text">
					</label>
					<label style="display:block;margin-bottom:5px;">
						<strong><?php esc_html_e( 'utm_campaign:', 'repostflow' ); ?></strong>
						<input type="text" name="rpf_utm_campaign" value="<?php echo esc_attr( RePostFlow::get_option( 'advanced.utm_campaign', '' ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Optional', 'repostflow' ); ?>">
					</label>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Log Retention', 'repostflow' ); ?></th>
				<td>
					<input type="number" name="rpf_log_retention" value="<?php echo esc_attr( RePostFlow::get_option( 'advanced.log_retention_days', 90 ) ); ?>" min="7" max="365" class="small-text">
					<?php esc_html_e( 'days', 'repostflow' ); ?>
					<p class="description"><?php esc_html_e( 'Automatically delete share history older than this.', 'repostflow' ); ?></p>
				</td>
			</tr>
		</table>
		<?php endif; ?>

		<p class="submit">
			<input type="submit" name="rpf_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'repostflow' ); ?>">
		</p>
	</form>
</div>
