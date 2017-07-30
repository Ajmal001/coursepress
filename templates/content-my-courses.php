<?php
$user = coursepress_get_user();
$enrolled_courses = $user->get_user_enrolled_at();
$statuses = array(
	'ongoing' => __( 'On Going', 'cp' ),
	'failed' => __( 'Failed', 'cp' ),
	'incomplete' => __( 'Incomplete', 'cp' ),
	'completed' => __( 'Completed', 'cp' ),
	'pass' => __( 'Certified', 'cp' ),
);
$pagenow = remove_query_arg( 'dummy', add_query_arg( 'dummy', 1 ) );
$courses_link = coursepress_get_main_courses_url();
$courses_link = sprintf( '<a href="%1$s" rel="bookmark">%2$s</a>', esc_url( $courses_link ), __( 'Courses', 'cp' ) );
?>

<?php if ( ! empty( $enrolled_courses ) ) : ?>
	<h3><?php _e( 'My Courses', 'cp' ); ?></h3>
	<table class="coursepress-table courses-table">
		<thead>
		<th><?php _e( 'Course', 'cp' ); ?></th>
		<th><?php _e( 'Progress', 'cp' ); ?></th>
		<th colspan="2"><?php _e( 'Status', 'cp' ); ?></th>
		</thead>
		<tbody>
		<?php foreach ( $enrolled_courses as $course ) : ?>
			<tr>
				<td>
					<a href="<?php echo esc_url( $course->get_permalink() ); ?>" rel="bookmark"><?php echo $course->post_title; ?></a>
				</td>
				<td>
					<?php echo $user->get_course_progress( $course->ID ); ?>%
				</td>
				<td>
					<?php
					$completion_status = $user->get_course_completion_status( $course->ID );
					echo $statuses[ $completion_status ];
					?>
				</td>
				<td align="right">
					<div class="coursepress-dropdown">
						<label for="coursepress-dropdown-input-<?php echo $course->ID; ?>">
							<span class="screen-reader-text"><?php _e( 'Menu', 'cp' ); ?></span>
							<i class="fa fa-bars"></i>
						</label>
						<input type="checkbox" autocomplete="off" id="coursepress-dropdown-input-<?php echo $course->ID; ?>" />
						<ul class="coursepress-dropdown-menu">
							<li>
								<a href="<?php echo esc_url( $course->get_workbook_url() ); ?>"><?php _e( 'Workbook', 'cp' ); ?></a>
							</li>
							<li>
								<a href="<?php echo esc_url( $course->get_unenroll_url( $pagenow ) ); ?>"><?php _e( 'Withdraw', 'cp' ); ?></a>
							</li>
						</ul>
					</div>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
	<p class="description"><?php printf( __( 'You are not enrolled to any course. Go to %s and enroll now!', 'cp' ), $courses_link ); ?></p>
<?php endif; ?>
