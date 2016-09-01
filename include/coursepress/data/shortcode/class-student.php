<?php
/**
 * Shortcode handlers.
 *
 * @package  CoursePress
 */

/**
 * Student-related shortcodes.
 */
class CoursePress_Data_Shortcode_Student {

	/**
	 * Register the shortcodes.
	 *
	 * @since  2.0.0
	 */
	public static function init() {
		add_shortcode(
			'courses_student_dashboard',
			array( __CLASS__, 'courses_student_dashboard' )
		);

		add_shortcode(
			'courses_student_settings',
			array( __CLASS__, 'courses_student_settings' )
		);

		add_shortcode(
			'coursepress_enrollment_templates',
			array( __CLASS__, 'coursepress_enrollment_templates' )
		);

		if ( ! CP_IS_CAMPUS ) {
			add_shortcode(
				'student_registration_form',
				array( __CLASS__, 'student_registration_form' )
			);
		}

		add_shortcode(
			'student_workbook_table',
			array( __CLASS__, 'student_workbook_table' )
		);

		// -- Course-progress.
		add_shortcode(
			'course_progress',
			array( __CLASS__, 'course_progress' )
		);
		add_shortcode(
			'course_unit_progress',
			array( __CLASS__, 'course_unit_progress' )
		);
		add_shortcode(
			'course_mandatory_message',
			array( __CLASS__, 'course_mandatory_message' )
		);
		add_shortcode(
			'course_unit_percent',
			array( __CLASS__, 'course_unit_percent' )
		);
	}

	public static function courses_student_dashboard( $atts ) {
		$content = CoursePress_Template_Student::dashboard();

		return $content;
	}

	public static function courses_student_settings( $atts ) {
		$content = CoursePress_Template_Student::student_settings();

		return $content;
	}

	public static function student_registration_form() {
		$content = CoursePress_Template_Student::registration_form();

		return $content;
	}

	public static function student_workbook_table( $args ) {
		$args = shortcode_atts(
			array(
				'course_id' => CoursePress_Helper_Utility::the_course( true ),
				'unit_id' => false,
				'module_column_title' => __( 'Element', 'cp' ),
				'title_column_title' => __( 'Title', 'cp' ),
				'submission_date_column_title' => __( 'Submitted', 'cp' ),
				'response_column_title' => __( 'Answer', 'cp' ),
				'grade_column_title' => __( 'Grade', 'cp' ),
				'comment_column_title' => __( 'Feedback', 'cp' ),
				'module_response_description_label' => __( 'Description', 'cp' ),
				'comment_label' => __( 'Comment', 'cp' ),
				'view_link_label' => __( 'View', 'cp' ),
				'view_link_class' => 'assessment-view-response-link button button-units',
				'comment_link_class' => 'assessment-view-response-link button button-units',
				'pending_grade_label' => __( 'Pending', 'cp' ),
				'unit_unread_label' => __( 'Unit Unread', 'cp' ),
				'unit_read_label' => __( 'Unit Read', 'cp' ),
				'single_correct_label' => __( 'Correct', 'cp' ),
				'single_incorrect_label' => __( 'Incorrect', 'cp' ),
				'no_content_label' => __( 'This unit has no activities.', 'cp' ),
				'non_assessable_label' => __( '**' ),
				'table_class' => 'widefat shadow-table assessment-archive-table workbook-table',
				'table_labels_th_class' => 'manage-column',
				'show_page' => false,
				'show_course_progress' => true,
			)
			,
			$args,
			'student_workbook_table'
		);

		$course_id = (int) $args['course_id'];
		$unit_id = (int) $args['unit_id'];
		$student_id = get_current_user_id();

		if ( empty( $course_id ) || empty( $student_id ) ) {
			return '';
		}

		$module_column_title = sanitize_text_field( $args['module_column_title'] );
		$title_column_title = sanitize_text_field( $args['title_column_title'] );
		$submission_date_column_title = sanitize_text_field( $args['submission_date_column_title'] );
		$response_column_title = sanitize_text_field( $args['response_column_title'] );
		$grade_column_title = sanitize_text_field( $args['grade_column_title'] );
		$comment_column_title = sanitize_text_field( $args['comment_column_title'] );
		$module_response_description_label = sanitize_text_field( $args['module_response_description_label'] );
		$comment_label = sanitize_text_field( $args['comment_label'] );
		$view_link_label = sanitize_text_field( $args['view_link_label'] );
		$view_link_class = sanitize_text_field( $args['view_link_class'] );
		$comment_link_class = sanitize_text_field( $args['comment_link_class'] );
		$pending_grade_label = sanitize_text_field( $args['pending_grade_label'] );
		$unit_unread_label = sanitize_text_field( $args['unit_unread_label'] );
		$unit_read_label = sanitize_text_field( $args['unit_read_label'] );
		$non_assessable_label = sanitize_text_field( $args['non_assessable_label'] );
		$no_content_label = sanitize_text_field( $args['no_content_label'] );
		$table_class = sanitize_text_field( $args['table_class'] );
		$table_labels_th_class = sanitize_text_field( $args['table_labels_th_class'] );
		$single_correct_label = sanitize_text_field( $args['single_correct_label'] );
		$single_incorrect_label = sanitize_text_field( $args['single_incorrect_label'] );
		$show_page = cp_is_true( $args['show_page'] );
		$show_course_progress = cp_is_true( $args['show_course_progress'] );

		$columns = array(
			'title' => $title_column_title,
			'submission_date' => $submission_date_column_title,
			'response' => $response_column_title,
			'grade' => $grade_column_title,
			'comment' => $comment_column_title,
		);

		$col_sizes = array(
			'45',
			'15',
			'12',
			'13',
			'15',
		);

		$student_progress = CoursePress_Data_Student::get_completion_data( $student_id, $course_id );

		$content = '';
		$unit_list = CoursePress_Data_Course::get_units_with_modules( $course_id );
		$unit_list = CoursePress_Helper_Utility::sort_on_key( $unit_list, 'order' );

		if ( ! empty( $unit_id ) && array_key_exists( $unit_id, $unit_list ) ) {
			$unit_list = array( $unit_list[ $unit_id ] );
		}

		if ( $show_course_progress && empty( $unit_id ) ) {
			$content .= '<h3 class="course-completion-progress">' . esc_html__( 'Course completion: ', 'cp' ) . '<small>' . CoursePress_Data_Student::get_course_progress( $student_id, $course_id, $student_progress ) . '%</small>' . '</h3>';
		}

		$student_progress_units = ( ! empty( $student_progress['units'] ) ) ? $student_progress['units'] : array();
		foreach ( $unit_list as $unit_id => $unit ) {
			if ( ! array_key_exists( $unit_id, $student_progress_units ) ) {
				continue;
			}

			$content .= '<div class="workbook-unit unit-' . $unit_id . '">';
			$content .= '<h3 class="unit-title">' . esc_html( $unit['unit']->post_title ) . '</h3>';

			$progress = CoursePress_Data_Student::get_unit_progress( $student_id, $course_id, $unit_id, $student_progress );
			$content .= '<div class="unit-progress">' . sprintf( __( 'Unit Progress: %s%%', 'cp' ), $progress ) . '</div>';

			$content .= '
			<table cellspacing="0" class="' . $table_class . '">
				<thead>
					<tr>';

			$n = 0;
			foreach ( $columns as $key => $col ) {
				$content .= '
						<th class="' . esc_attr( $table_labels_th_class ) . ' column-' . esc_attr( $key ) . '" width="' . esc_attr( $col_sizes[ $n ] ) . '%"  scope="col">' . esc_html( $col ) . '</th>
			';
				$n ++;
			}

			$content .= '
					</tr>
				</thead>';

			$content .= '
				<tbody>
			';

			$module_count = 0;
			if ( isset( $unit['pages'] ) ) {
				foreach ( $unit['pages'] as $page ) {
					if ( $show_page ) {
						$content .= '<tr class="page page-separator"><td colspan="5">' . $page['title'] . '</td></tr>';
					}

					foreach ( $page['modules'] as $module_id => $module ) {
						$attributes = CoursePress_Data_Module::attributes( $module_id );
						if ( 'output' === $attributes['mode'] ) {
							continue;
						}

						$module_count += 1;

						$title = empty( $module->post_title ) ? $module->post_content : $module->post_title;
						$response = CoursePress_Data_Student::get_response( $student_id, $course_id, $unit_id, $module_id, false, $student_progress );
						$grade = CoursePress_Data_Student::get_grade( $student_id, $course_id, $unit_id, $module_id, false, false, $student_progress );
						$feedback = CoursePress_Data_Student::get_feedback( $student_id, $course_id, $unit_id, $module_id, false, false, $student_progress );

						$response_display = $response['response'];

						switch ( $attributes['module_type'] ) {

							case 'input-checkbox':
								$response_display = '';
								if ( ! empty( $response['response'] ) && is_array( $response['response'] ) ) {
									foreach ( $response['response'] as $r ) {
										$response_display .= '<p class="answer list">' . $attributes['answers'][ (int) $r ] . '</p>';
									}
								}
								break;

							case 'input-radio':
							case 'input-select':
								$response_display = '';
								if ( isset( $response['response'] ) ) {
									$response_display = '<p class="answer">' . $attributes['answers'][ (int) $response['response'] ] . '</p>';
								}
								break;

							case 'input-upload':
								if ( $response ) {
									$url = $response['response']['url'];

									$file_size = isset( $response['response']['size'] ) ? $response['response']['size'] : false;
									$file_size = $file_size ? CoursePress_Helper_Utility::format_file_size( $file_size ) : '';
									$file_size = ! empty( $file_size ) ? '<small>(' . esc_html( $file_size ) . ')</small>' : '';

									$file_name = explode( '/', $url );
									$file_name = array_pop( $file_name );

									$url = CoursePress_Helper_Utility::encode( $url );
									$url = trailingslashit( home_url() ) . '?fdcpf=' . $url;

									$response_display = '<a href="' . esc_url( $url ) . '">' . esc_html( $file_name ) . ' ' . CoursePress_Helper_Utility::filter_content( $file_size ) . '</a>';
								} else {
									$response_display = '';
								}
								break;
							case 'input-quiz':
								$display = '';

								if ( $response_display ) {

									foreach ( $response_display as $q_index => $answers ) {
										foreach ( $answers as $a_index => $answer ) {
											if ( ! empty( $answer ) ) {
												$the_answer = CoursePress_Helper_Utility::get_array_val(
													$attributes,
													'questions/' . $q_index . '/options/answers/' . $a_index
												);
												$display .= sprintf( '<p class="answer">%s</p>', $the_answer );
											}
										}
									}
								}
								$response_display = $display;
								break;
						}

						$response_date = ! isset( $response['date'] ) ? '' : date_i18n( get_option( 'date_format' ), CoursePress_Data_Course::strtotime( $response['date'] ) );

						$grade = (-1 == $grade ? __( 'Ungraded', 'cp' ) : $grade );

						$mandatory = cp_is_true( $attributes['mandatory'] ) ? '<span class="dashicons dashicons-star-filled mandatory"></span>' : '';
						$non_assessable = cp_is_true( $attributes['assessable'] ) ? '' : '<span class="dashicons dashicons-star-filled non-assessable"></span>';

						$extra = $mandatory . $non_assessable;

						$first_last = CoursePress_Helper_Utility::get_user_name( (int) $feedback['feedback_by'] );

						$feedback_display = ! empty( $feedback['feedback'] ) ? '<div class="feedback"><div class="comment">' . $feedback['feedback'] . '</div><div class="instructor"> – <em>' . esc_html( $first_last ) . '</em></div></div>' : '';

						$grade_display = ! empty( $grade['grade'] ) || '0' == $grade['grade'] ? $grade['grade'] . '%' : '';
						$content .= '<tr>
							<td class="title">' . $title . ' ' . $extra . '</td>
							<td class="submit-date">' . $response_date . '</td>
							<td class="view-response ' . $attributes['module_type'] . '">' . $response_display . '</td>
							<td class="grade">' . $grade_display . '</td>
							<td class="feedback">' . $feedback_display . '</td>
						</tr>';

					}
				}
			}

			if ( empty( $module_count ) ) {
				$content .= '<tr class="empty"><td colspan="5">' . esc_html( $no_content_label ) . '</td></tr>';
			}

			$content .= '
				</tbody>
				<tfoot><tr class="footer-key"><td colspan="5"><span class="dashicons dashicons-star-filled mandatory"></span>' . esc_html__( 'Mandatory answers', 'cp' ) . '&nbsp;&nbsp;<span class="dashicons dashicons-star-filled non-assessable"></span>' . esc_html__( 'Non-assessable elements.', 'cp' ) . '</td></tr></tfoot>
			';

			$content .= '
			</table>
			';

			$content .= '</div>';  // .workbook-unit
		}

		return $content;
	}

	public static function coursepress_enrollment_templates( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id' => CoursePress_Helper_Utility::the_course( true ),
			),
			$atts,
			'course_page'
		);

		$course_id = (int) $atts['course_id'];

		if ( empty( $course_id ) ) {
			return '';
		}

		$nonce = wp_create_nonce( 'coursepress_enrollment_action' );

		$scode_1 = '[course_signup_form login_link_id="step2" show_submit="no"]';
		$scode_2 = '[course_signup_form signup_link_id="step1" show_submit="no" page="login"]';

		/**
		 * The filters below can be used to customize the output of the
		 * registration process.
		 */
		ob_start();
		?>
		<script type="text/template" id="modal-template">
			<div class="enrollment-modal-container"
				data-nonce="<?php echo esc_attr( $nonce ); ?>"
				data-course="<?php echo esc_attr( $course_id ); ?>"
				data-course-is-paid="<?php esc_html_e( intval( CoursePress_Data_Course::is_paid_course( $course_id ) ) ); ?>"
			></div>
		</script>

		<?php if ( apply_filters( 'coursepress_registration_form_step-1', true ) ) : ?>
		<script type="text/template" id="modal-view1-template" data-type="modal-step" data-modal-action="signup">
			<div class="bbm-modal-nonce signup" data-nonce="<?php echo wp_create_nonce( 'coursepress_enrollment_action_signup' ); ?>"></div>
			<div class="bbm-modal__topbar">
				<h3 class="bbm-modal__title">
					<?php esc_html_e( 'Create new account', 'cp' ); ?>
				</h3>
				<span id="error-messages"></span>
			</div>
			<div class="bbm-modal__section">
				<div class="modal-nav-link">
				<?php echo do_shortcode( $scode_1 ); ?>
				</div>
			</div>
			<div class="bbm-modal__bottombar">
			<input type="hidden" name="course_id" value="<?php esc_attr_e( $course_id ); ?>" />
			<input type="submit" class="bbm-button done signup button cta-button" value="<?php esc_attr_e( 'Create an account', 'cp' ); ?>" />
			<a href="#" class="cancel-link">
				<?php esc_html_e( 'Cancel', 'cp' ); ?>
			</a>
			</div>
		</script>
		<?php endif; ?>

		<?php if ( apply_filters( 'coursepress_registration_form_step-2', true ) ) : ?>
		<script type="text/template" id="modal-view2-template" data-type="modal-step" data-modal-action="login">
			<div class="bbm-modal-nonce login" data-nonce="<?php echo wp_create_nonce( 'coursepress_enrollment_action_login' ); ?>"></div>
			<div class="bbm-modal__topbar">
				<h3 class="bbm-modal__title">
					<?php esc_html_e( 'Login to your account', 'cp' ); ?>
				</h3>
				<span id="error-messages"></span>
			</div>
			<div class="bbm-modal__section">
				<div class="modal-nav-link">
				<?php echo do_shortcode( $scode_2 ); ?>
				</div>
			</div>
			<div class="bbm-modal__bottombar">
			<input type="submit" class="bbm-button done button cta-button" value="<?php esc_attr_e( 'Log in', 'cp' ); ?>" />
			<a href="#" class="cancel-link"><?php esc_html_e( 'Cancel', 'cp' ); ?></a>
			</div>
		</script>
		<?php endif; ?>

		<?php if ( apply_filters( 'coursepress_registration_form_step-3', true ) ) : ?>
		<script type="text/template" id="modal-view3-template" data-type="modal-step" data-modal-action="enrolled">
			<div class="bbm-modal__topbar">
				<h3 class="bbm-modal__title">
					<?php esc_html_e( 'Successfully enrolled.', 'cp' ); ?>
				</h3>
			</div>
			<div class="bbm-modal__section">
				<p>
					<?php esc_html_e( 'Congratulations! You have successfully enrolled. Click below to get started.', 'cp' ); ?>
				</p>
				<a href="<?php echo get_permalink( CoursePress_Helper_Utility::the_course( true ) ) . CoursePress_Core::get_slug( 'units' ); ?>">Start Learning</a>
			</div>
			<div class="bbm-modal__bottombar">
			</div>
		</script>
		<?php endif; ?>

		<?php
		do_action( 'coursepress_registration_form_end', $atts );
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Course Progress.
	 *
	 * @since 1.0.0
	 * @param  array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public static function course_progress( $atts ) {
		extract(
			shortcode_atts(
				array(
					'course_id' => CoursePress_Helper_Utility::the_course( true ),
					'decimal_places' => '0',
				),
				$atts,
				'course_progress'
			)
		);

		if ( $course_id  ) { $course_id = (int) $course_id; }
		$decimal_places = (int) $decimal_places ;

		return number_format_i18n(
			CoursePress_Data_Student::get_course_progress(
				get_current_user_id(),
				$course_id
			),
			$decimal_places
		);
	}

	/**
	 * Course Unit Progress
	 *
	 * @since 1.0.0
	 * @param  array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public static function course_unit_progress( $atts ) {
		extract( shortcode_atts( array(
			'course_id' => CoursePress_Helper_Utility::the_course( true ),
			'unit_id' => false,
			'decimal_places' => '0',
		), $atts, 'course_unit_progress' ) );

		if ( ! empty( $course_id ) ) {
			$course_id = (int) $course_id;
		}
		$unit_id = (int) $unit_id;

		$decimal_places = sanitize_text_field( $decimal_places );

		$progress = number_format_i18n(
			CoursePress_Data_Student::get_unit_progress(
				get_current_user_id(),
				$course_id,
				$unit_id
			),
			$decimal_places
		);

		return $progress;
	}

	/**
	 * Course Required Message.
	 *
	 * Text: "x of y required elements completed".
	 *
	 * @since 1.0.0
	 * @param  array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public static function course_mandatory_message( $atts ) {
		extract(
			shortcode_atts(
				array(
					'course_id' => CoursePress_Helper_Utility::the_course( true ),
					'unit_id' => CoursePress_Helper_Utility::the_post( true ),
					'message' => __( '%d of %d required elements completed.', 'cp' ),
				),
				$atts,
				'course_mandatory_message'
			)
		);

		$course_id = (int) $course_id;
		if ( empty( $course_id ) ) { return ''; }

		$unit_id = (int) $unit_id;
		if ( empty( $unit_id ) ) { return ''; }

		$student_id = get_current_user_id();
		if ( empty( $student_id ) ) { return ''; }

		$mandatory = CoursePress_Data_Student::get_mandatory_completion(
			$student_id,
			$course_id,
			$unit_id
		);
		if ( empty( $mandatory['required'] ) ) { return ''; }

		$message = sanitize_text_field( $message );
		$mandatory_required = (int) $mandatory['required'];
		if ( empty( $mandatory_required ) ) {
			return '';
		}

		return sprintf(
			$message,
			(int) $mandatory['completed'],
			$mandatory_required
		);
	}

	/**
	 * Display percentage of unit completion.
	 *
	 * @since  1.0.0
	 * @param  array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public static function course_unit_percent( $atts ) {
		$shortcode_atts = shortcode_atts( array(
			'course_id' => CoursePress_Helper_Utility::the_course( true ),
			'unit_id' => CoursePress_Helper_Utility::the_post( true ),
			'format' => false,
			'style' => 'flat',
			'tooltip_alt' => __( 'Percent of the unit completion', 'cp' ),
			'knob_animation' => '{ "duration": 1200 }',
			'knob_bg_color' => '#e0e6eb',
			'knob_data_height' => '60',
			'knob_data_thickness' => '0.18',
			'knob_data_width' => '60',
			'knob_empty_color' => 'rgba( 20, 20, 20, .1 )',
			'knob_fg_color' => '#24bde6',
			'knob_text_align' => 'center',
			'knob_text_color' => '#222',
			'knob_text_denominator' => 4.5,
			'knob_text_show' => true,
			'knob_text_prepend' => false,
		), $atts, 'course_unit_percent' );

		/**
		 * Filter progress_wheel attributes.
		 **/
		$shortcode_atts = apply_filters( 'coursepress_unit_progress_wheel_atts', $shortcode_atts );

		extract( $shortcode_atts );

		$course_id = (int) $course_id;
		$unit_id = (int) $unit_id;

		if ( empty( $course_id ) || empty( $unit_id ) ) {
			return 0;
		}

		$format = cp_is_true( $format );
		$style = sanitize_text_field( $style );
		$tooltip_alt = sanitize_text_field( $tooltip_alt );
		$knob_fg_color = sanitize_text_field( $knob_fg_color );
		$knob_bg_color = sanitize_text_field( $knob_bg_color );
		$knob_text_color = sanitize_text_field( $knob_text_color );
		$knob_data_thickness = sanitize_text_field( $knob_data_thickness );
		$knob_data_width = (int) $knob_data_width;
		$knob_data_height = (int) $knob_data_height;

		if ( empty( $knob_data_width ) && ! empty( $knob_data_height ) ) {
			$knob_data_width = $knob_data_height;
		}

		$knob_data_thickness = $knob_data_width * $knob_data_thickness;

		$percent_value = (int) CoursePress_Data_Student::get_unit_progress( get_current_user_id(), $course_id, $unit_id );

		$content = '';

		/**
		 * check is unit available?
		 */
		$is_unit_available = CoursePress_Data_Unit::is_unit_available( $course_id, $unit_id, null);

		if ( $is_unit_available ) {
			if ( 'flat' == $style ) {
				$content = '<span class="percentage">' . ( $format ? $percent_value . '%' : $percent_value ) . '</span>';
			} elseif ( 'none' == $style ) {
				$content = $percent_value;
			} else {
				$data_value = $percent_value / 100;
				$content = '<div class="course-progress-disc-container"><a class="tooltip" alt="' . $tooltip_alt . '">';
				/**
				 * Knob settings as date fields
				 */
				$knob_settings = array();
				foreach ( $shortcode_atts as $key => $value ) {
					if ( ! preg_match( '/^knob_/', $key ) ) {
						continue;
					}
					$knob_settings[] = sprintf(
						'data-%s="%s"',
						sanitize_text_field( preg_replace( '/_/', '-', $key ) ),
						esc_attr( $value )
					);
				}
				$content .= sprintf(
					'<div class="course-progress-disc" data-value="%s" data-start-angle="4.7" data-size="%s" data-thickness="%s" data-animation-start-value="1.0" data-fill="{ &quot;color&quot;: &quot;%s&quot; }" %s></div>',
					esc_attr( $data_value ),
					esc_attr( $knob_data_width ),
					esc_attr( $knob_data_thickness ),
					esc_attr( $knob_fg_color ),
					implode( ' ', $knob_settings )
				);
				$content .= '</a></div>';
			}
		} else {
			$content .= '<i class="fa fa-lock" aria-hidden="true"></i>';
		}

		/**
		 * Filter the progress wheel markup.
		 *
		 * @since 2.0
		 *
		 * @param (int) $markup
		 * @param (int) $course_id
		 * @param (int) $unit_id
		 * @param (int) $percent_value		The total percent value.
		 **/
		$content = apply_filters( 'coursepress_unit_progress_wheel', $content, $course_id, $unit_id, $percent_value );

		return $content;
	}
}