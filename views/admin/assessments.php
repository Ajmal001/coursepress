<div class="wrap coursepress-wrap coursepress-assessments" id="coursepress-assessments">
	<h1 class="wp-heading-inline"><?php _e( 'Assessments', 'cp' ); ?></h1>

	<div class="coursepress-page">
		<form method="get" class="cp-search-form" id="cp-search-form">
			<div class="cp-flex">

				<div class="cp-div">
					<label class="label"><?php _e( 'Select course', 'cp' ); ?></label>
					<select name="course_id">
						<?php if ( ! empty( $courses ) ) : ?>
							<?php foreach ( $courses as $course ) : ?>
								<?php $selected_course = empty( $_GET['course_id'] ) ? 0 : $_GET['course_id']; ?>
								<option value="<?php echo $course->ID; ?>" <?php selected( $course->ID, $selected_course ); ?>><?php echo $course->post_title; ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>

				<div class="cp-div">
					<label class="label"><?php _e( 'Student progress', 'cp' ); ?></label>
					<select name="student_progress">
						<option value=""><?php _e( 'Show all assessable students', 'cp' ); ?></option>
						<?php if ( ! empty( $units ) ) : ?>
							<?php foreach ( $units as $unit ) : ?>
								<?php $selected_unit = empty( $_GET['unit'] ) ? 0 : $_GET['unit']; ?>
								<option value="<?php echo $unit->ID; ?>" <?php selected( $unit->ID, $selected_unit ); ?>><?php echo $unit->post_title; ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>

				<div class="cp-div cp-input-group-div">
					<ul class="cp-flex cp-input-group">
						<li class="cp-div-flex active">
							<label>
								<input type="radio" name="graded_ungraded" value="all" />
								<?php _e( 'All Students', 'cp' ); ?>
							</label>
						</li>
						<li class="cp-div-flex">
							<label>
								<input type="radio" name="graded_ungraded" value="graded" />
								<?php _e( 'Graded Students', 'cp' ); ?>
							</label>
						</li>
						<li class="cp-div-flex">
							<label>
								<input type="radio" name="graded_ungraded" value="ungraded" />
								<?php _e( 'Ungraded Students', 'cp' ); ?>
							</label>
						</li>
					</ul>
				</div>

			</div>

			<div class="cp-flex">

				<div class="cp-div">
					<label class="label"><?php _e( 'Search students by name, username or email.', 'cp' ); ?></label>
					<div class="cp-input-clear">
						<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
						<input type="text" name="s" placeholder="<?php _e( 'Enter search query here...', 'cp' ); ?>" value="<?php echo $search; ?>" />
						<button type="button" id="cp-search-clear" class="cp-btn-clear"><?php _e( 'Clear', 'cp' ); ?></button>
					</div>
					<button type="submit" class="cp-btn cp-btn-active"><?php _e( 'Search', 'cp' ); ?></button>
				</div>

			</div>
		</form>

		<ul class="cp-assessments-overview">
			<li><?php _e( 'Showing students'); ?>: <span class="cp-assessments-strong">3</span></li>
			<li><?php _e( 'Modules'); ?>: <span class="cp-assessments-strong">4</span></li>
			<li><?php _e( 'Pass grade'); ?>: <span class="cp-assessments-strong"><?php _e( '85%', 'cp' ); ?></span></li>
			<li><?php _e( 'Grade system'); ?>: <span class="cp-assessments-strong"><?php _e( 'total acquired grade % total number of gradable modules', 'cp' ); ?></span></li>
		</ul>

		<table class="coursepress-table" id="cp-assessments-table" cellspacing="0">
			<thead>
			<tr>
				<?php foreach ( $columns as $column_id => $column_label ) : ?>
					<th class="manage-column column-<?php echo $column_id; echo in_array( $column_id, $hidden_columns ) ? ' hidden': ''; ?>" id="<?php echo $column_id; ?>">
						<?php echo $column_label; ?>
					</th>
				<?php endforeach; ?>
			</tr>
			</thead>
			<tbody>
			<?php $odd = true; ?>
			<?php if ( ! empty( $assessments ) ) : ?>
				<?php foreach ( $assessments as $assessment ) : ?>
					<tr class="<?php echo $odd ? 'odd' : 'even cp-graded'; ?>">

						<?php foreach ( array_keys( $columns ) as $column_id ) : ?>
							<td class="column-<?php echo $column_id; echo in_array( $column_id, $hidden_columns ) ? ' hidden': ''; ?>">
								<?php
								switch( $column_id ) :
									// @todo Add profile link if required.
									case 'student' :
										echo '<div class="cp-flex">';
										echo '<span class="gravatar">';
										echo get_avatar( $assessment->ID, 30 );
										echo '</span>';
										echo ' ';
										echo '<span class="user_login">';
										echo $assessment->user_login;
										echo '</span>';
										echo ' ';
										echo '<span class="display_name">(';
										echo $assessment->get_name();
										echo ')</span>';
										echo '</div>';
										break;
									case 'last_active' :
										// Last activity time.
										$last_active = $assessment->get_last_activity_time();
										echo $last_active ? date_i18n( get_option( 'date_format' ), $last_active ) : '--';
										break;
									case 'grade' :
										echo print_r($assessment->get_completion_data(1298));
										break;
									case 'modules_progress' :
										echo '<div class="cp-assessment-progress-hidden">';
										echo '<a href="javascript:void(0);" class="cp-expand-collapse">' . __( 'Expand', 'cp' ) . '</a>';
										echo '|';
										echo '<a href="#">' . __( 'Open in new tab', 'cp' ) . '</a>';
										echo '</div>';
										echo '<div class="cp-assessment-progress-expand inactive">';
										echo '<button class="cp-expand-collapse cp-collapse-btn">Collapse</button>';
										echo '</div>';
										break;
									case 'reports' :
										echo '<a href="#">' . __( 'Download', 'cp' ) . '</a>';
										echo '|';
										echo '<a href="#">' . __( 'View', 'cp' ) . '</a>';
										break;
									default :
										/**
										 * Trigger to allow custom column value
										 *
										 * @since 3.0
										 * @param string $column_id
										 * @param CoursePress_Student object $student
										 */
										do_action( 'coursepress_studentlist_column', $column_id, $assessment );
										break;
								endswitch;
								?>
							</td>
						<?php endforeach; ?>
					</tr>
					<tr class="cp-assessments-details inactive">
						<td colspan="5" class="cp-tr-expanded">
							<ul class="cp-assessments-units-expanded">
								<li><span class="pull-left"><span class="cp-units-icon"></span>Terminal, Node, NPM, what's all this?</span>
									<span class="pull-right"><span class="cp-tick-icon">90%</span><span class="cp-plus-icon"></span></span></li>
								<li><span class="pull-left"><span class="cp-units-icon"></span>Installing Node and NPM</span>
									<span class="pull-right"><span class="cp-cross-icon">80%</span><span class="cp-plus-icon"></span></span></li>
								<li><span class="pull-left"><span class="cp-units-icon"></span>Terminal, Node, NPM, what's all this?</span>
									<span class="pull-right"><span class="cp-tick-icon">10%</span><span class="cp-plus-icon"></span></span>
									<div class="cp-assessments-table-container inactive">
										<table class="cp-assesments-questions-expanded">
											<tr>
												<th class="cp-assessments-strong">Question</th>
												<th class="cp-assessments-strong">Student answer</th>
												<th class="cp-assessments-strong">Correct answer</th>
											</tr>
											<tr>
												<td>What command do you need to run to create package.json</td>
												<td>How do you install Grunt CLI globally</td>
												<td>Multiple answer questions</td>
											</tr>
										</table>
									</div>
								</li>
							</ul>
						</td>
					</tr>
					<?php $odd = $odd ? false : true; ?>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="odd">
					<td><?php _e( 'No assessments found.', 'cp' ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php if ( ! empty( $list_table ) ) : ?>
			<div class="tablenav cp-admin-pagination">
				<?php $list_table->pagination( 'bottom' ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>