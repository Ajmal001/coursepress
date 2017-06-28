<div class="wrap coursepress-wrap coursepress-settings">
	<h1 class="wp-heading-inline">CoursePress <?php _e( 'Settings', 'cp' ); ?></h1>

	<div id="coursepress-settings" class="coursepress-page course-steps-page">
		<div class="cp-menu-items course-menu-list">
			<ul class="course-menu">
				<?php
					$admin_menus = array(
						'general' => __( 'General', 'cp' ),
						'slugs' => __( 'URL Slugs', 'cp' ),
						'emails' => __( 'Emails', 'cp' ),
						'capabilities' => __( 'Capabilities', 'cp' ),
						'basic_certificate' => __( 'Certificate', 'cp' ),
						'shortcodes' => __( 'Shortcodes', 'cp' ),
						'extensions' => __( 'Extensions', 'cp' ),
						'import-export' => __( 'Import/Export', 'cp' ),
					);

					/**
					 * Fire to allow re-populations of CoursePress settings menus.
					 *
					 * @since 3.0
					 * @param array $admin_menus
					 */
					$admin_menus = apply_filters( 'coursepress_admin_settings', $admin_menus );

					foreach ( $admin_menus as $menu_id => $menu_label ) :
				?>
					<li class="cp-menu-item step setting-<?php echo $menu_id; ?>" data-setting="<?php echo $menu_id; ?>">
						<span class="menu-label"><?php echo $menu_label; ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="course-content">
			<?php foreach ( $admin_menus as $menu_id => $menu ) : ?>
				<div class="course-content-tab" id="coursepress-setting-<?php echo $menu_id; ?>"></div>
			<?php endforeach; ?>

            <div class="course-footer">
                <button type="button" class="cp-btn cp-btn-cancel step-cancel">
					<?php _e( 'Cancel', 'cp' ); ?>
                </button>

                <button type="button" class="cp-btn cp-btn-active save-coursepress-setting">
                    <i class="fa fa-circle-o-notch fa-spin"></i>
					<?php _e( 'Save Settings', 'cp' ); ?>
                </button>
            </div>
		</div>
	</div>
</div>