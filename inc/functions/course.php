<?php
/**
 * Course functions and definitions.
 *
 * @since 3.0
 * @package CoursePress
 */

/**
 * Get course data object.
 *
 * @param int|WP_Post $course_id
 *
 * @return WP_Error|CoursePress_Course
 */
function coursepress_get_course( $course_id = 0 ) {
	global $CoursePress_Course, $CoursePress_Core;

	if ( empty( $course_id ) ) {
		// Assume current course
		if ( $CoursePress_Course instanceof CoursePress_Course ) {
			return $CoursePress_Course;
		} else {
			// Try current post
			$course_id = get_the_ID();
		}
	}

	if ( $course_id instanceof WP_Post ) {
		$course_id = $course_id->ID; }

	if ( $CoursePress_Course instanceof CoursePress_Course
	     && $course_id == $CoursePress_Course->__get( 'ID' ) ) {
		return $CoursePress_Course; }

	if ( isset( $CoursePress_Core->courses[ $course_id ] ) ) {
		return $CoursePress_Core->courses[ $course_id ]; }

	$course = new CoursePress_Course( $course_id );

	if ( ! $course->__get( 'is_error' ) ) {
		$CoursePress_Core->courses[ $course_id ] = $course;

		return $course;
	}

	return $course->wp_error();
}

/**
 * Returns list courses.
 *
 * @param array $args  Arguments to pass to WP_Query.
 * @param int   $count This is not the count of resulted courses. This is the count
 *                     of total available courses without applying pagination limit.
 *                     This parameter does not expect incoming value. Total count will
 *                     be passed as reference, since this functions return value is an
 *                     array of course post objects.
 *
 * @return array Returns an array of courses where each course is an instance of CoursePress_Course object.
 */
function coursepress_get_courses( $args = array(), &$count = 0 ) {
	/** @var $CoursePress_Core CoursePress_Core */
	global $CoursePress_Core;

	// If courses per page is not set.
	if ( ! isset( $args['posts_per_page'] ) ) {
		// Set the courses per page from screen options.
		$posts_per_page = get_user_meta( get_current_user_id(), 'coursepress_course_per_page', true );
		// If screen option is not sert, default posts per page.
		if ( empty( $posts_per_page ) ) {
			$posts_per_page = coursepress_get_option( 'posts_per_page', 20 );
		}
		$args['posts_per_page'] = $posts_per_page;
		// Set the current page (get_query_var() won't work).
		$args['paged'] = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;
	}

	// If search query found.
	if ( isset( $_GET['s'] ) ) {
		$args['s'] = $_GET['s'];
	}

	$args = wp_parse_args( array(
		'post_type' => $CoursePress_Core->__get( 'course_post_type' ),
		'suppress_filters' => true,
		'fields' => 'ids',
		'orderby' => 'post_title',
		'order' => 'ASC',
	), $args );

	//$order_by = coursepress_get_setting( 'course/order_by', 'post_date' );
	//$order = coursepress_get_setting( 'course/order_by_direction', 'ASC' );

	// @todo: Apply orderby setting

	/**
	 * Filter course results.
	 *
	 * @since 3.0
	 * @param array $args
	 */
	$args = apply_filters( 'coursepress_pre_get_courses', $args );

	// Note: We need to use WP_Query to get total count.
	$query = new WP_Query();
	$results = $query->query( $args );
	// Update the total courses count (ignoring items per page).
	$count = $query->found_posts;

	$courses = array();

	if ( ! empty( $results ) ) {
		foreach ( $results as $result ) {
			$courses[ $result ] = coursepress_get_course( $result );
		}
	}

	return $courses;
}

function coursepress_get_course_statuses() {
	global $wpdb;

	$query = "SELECT `post_status` FROM `{$wpdb->posts}` WHERE `post_type`='course' AND `post_status` IN ('publish', 'draft')";
	$results = $wpdb->get_results( $query, 'OBJECT' );
	$status = array(
		'all' => 0,
		'publish' => 0,
		'draft' => 0,
	);

	if ( count( $results ) > 0 ) {
		foreach ( $results as $result ) {
			$status['all'] += 1;

			if ( 'publish' == $result->post_status ) {
				$status['publish'] += 1;
			} else {
				$status['draft'] += 1;
			}
		}
	}

	return $status;
}

/**
 * Get the course title.
 *
 * @param int $course_id Optional. If null will return course title of the current serve course.
 *
 * @return CoursePress_Course|null
 */
function coursepress_get_course_title( $course_id = 0 ) {
	$course = coursepress_get_course( $course_id );

	if ( ! is_wp_error( $course ) ) {
		return $course->__get( 'post_title' ); }

	return null;
}

/**
 * Get the course summary of the given course ID.
 *
 * @param int $course_id    Optional. If omitted, will return summary of the current serve course.
 * @param int $length       Optional. The character length of the summary to return.
 *
 * @return null|string
 */
function coursepress_get_course_summary( $course_id = 0, $length = 140 ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return null; }

	return $course->get_summary( $length );
}

/**
 * Helper function to get course description.
 *
 * @param int $course_id
 *
 * @return null|string
 */
function coursepress_get_course_description( $course_id = 0 ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return null; }

	return $course->get_description();
}

/**
 * Return's course media base on set settings.
 *
 * @param int $course_id
 * @param int $width
 * @param int $height
 *
 * @return null|string
 */
function coursepress_get_course_media( $course_id = 0, $width = 235, $height = 235 ) {
	$course = coursepress_get_course( $course_id );

	if ( ! is_wp_error( $course ) ) {
		return $course->get_media( $width, $height ); }

	return null;
}

/**
 * Returns course start and end date, separated by set separator.
 *
 * @param int $course_id
 * @param string $separator
 *
 * @return string|null
 */
function coursepress_get_course_availability_dates( $course_id = 0, $separator = ' - ' ) {
	$course = coursepress_get_course( $course_id );
	if ( is_wp_error( $course ) ) {
		return null;
	}
	return $course->get_course_dates( $separator );
}

/**
 * Returns course enrollment start and end date, separated by set separator.
 *
 * @param int $course_id
 * @param string $separator
 *
 * @return string|null
 */
function coursepress_get_course_enrollment_dates( $course_id = 0, $separator = ' - ' ) {
	$course = coursepress_get_course( $course_id );
	if ( is_wp_error( $course ) ) {
		return null;
	}
	return $course->get_enrollment_dates( $separator );
}

/**
 * Returns course enrollment button.
 *
 * @param int $course_id
 *
 * @return string
 */
function coursepress_get_course_enrollment_button( $course_id = 0, $args = array() ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return null;
	}

	$defaults = array(
		'access_text' => __( 'Start Learning', 'cp' ),
		'class' => '',
		'continue_learning_text' => __( 'Continue Learning', 'cp' ),
		'course_expired_text' => __( 'Not available', 'cp' ),
		'course_full_text' => __( 'Course Full', 'cp' ),
		'course_not_started' => __( 'Not yet available', 'cp' ),
		'details_text' => __( 'Details', 'cp' ),
		'enrollment_closed_text' => __( 'Enrollments Closed', 'cp' ),
		'enrollment_finished_text' => __( 'Enrollments Finished', 'cp' ),
		'enroll_text' => __( 'Enroll Now!', 'cp' ),
		'instructor_text' => __( 'Access Course', 'cp' ),
		'list_page' => false,
		'not_started_text' => __( 'Not Available', 'cp' ),
		'passcode_text' => __( 'Passcode Required', 'cp' ),
		'prerequisite_text' => __( 'Pre-requisite Required', 'cp' ),
		'signup_text' => __( 'Enroll Now!', 'cp' ),
	);

	$args = wp_parse_args( $args, $defaults );
	$button_option = 'enroll';
	$course_id = $course->__get( 'ID' );
	$user = coursepress_get_user();
	$link = '';
	$link_text = '';

	if ( $user->is_enrolled_at( $course_id ) ) {
		$link = $course->get_units_url();
		$link_text = $args['access_text'];
		$button_option = false;
	} else {
		if ( $course->user_can_enroll() ) {
			$enrollment_type = $course->__get( 'enrollment_type' );
			$button_option = 'enroll';

			$link_text = $args['enroll_text'];
			$link_args = array(
				'course_id' => $course_id,
				'action'    => 'coursepress_enroll',
				'_wpnonce' => wp_create_nonce( 'coursepress_nonce' ),
			);
			$link = add_query_arg( $link_args, admin_url( 'admin-ajax.php' ) );

			if ( ! is_user_logged_in() ) {
				// Redirect to login page??
				$use_custom_login = coursepress_get_setting( 'general/use_custom_login' );

				if ( $use_custom_login ) {
					$login_page = coursepress_get_setting( 'slugs/pages/login', 0 );

					if ( $login_page ) {
						$link = get_permalink( $login_page );
					} else {
						$slug = coursepress_get_setting( 'slugs/login', 'student-login' );
						$link = site_url( '/' ) . trailingslashit( $slug );
					}
				} else {
					$link = wp_login_url( $link );
				}
			} else {
				if ( 'prerequisite' == $enrollment_type ) {
					$courses = $course->__get( 'enrollment_prerequisite' );
					$messages = array();

					if ( is_array( $courses ) ) {
						foreach ( $courses as $_course_id ) {
							$_course = coursepress_get_course( $_course_id );
							$_course_link = coursepress_create_html(
								'a',
								array(
									'href' => $_course->get_permalink(),
									'target' => '_blank',
									'rel' => 'bookmark',
								),
								$_course->post_title
							);

							if ( ! $user->is_course_completed( $_course_id ) ) {
								$messages[] = coursepress_create_html(
									'p',
									array(),
									sprintf( __( 'You need to complete %s to take this course.', 'cp' ), $_course_link )
								);
							}
						}
					}
				} elseif ( 'passcode' == $enrollment_type ) {
					$link = '';
					$link_text = '';
					$args = array(
						'course_id' => $course_id,
						'cookie_name' => 'cp_incorrect_passcode_' . COOKIEHASH,
					);

					coursepress_render( 'views/front/passcode-form', $args );
				}

				if ( ! empty( $messages ) ) {
					echo implode( ' ', $messages );
					$link = '';
					$link_text = '';
				}
			}
		}
	}

	$attr = array(
		'class' => 'course-enroll-button ' . $args['class'],
	);

	if ( ! empty( $link ) ) {
		$attr['href'] = $link;
		$attr['_wpnonce'] = wp_create_nonce( 'coursepress_nonce' );

		$button = coursepress_create_html( 'a', $attr, $link_text );
	} else {
		$button = coursepress_create_html( 'span', $attr, $link_text );
	}

	$button = apply_filters( 'coursepress_enroll_button', $button, $course_id, $user->ID, $button_option );

	echo $button;
}

/**
 * Returns course instructor links, separated by set separator.
 *
 * @param int $course_id
 * @param string $sepatator
 *
 * @return string|null
 */
function course_get_course_instructor_links( $course_id = 0, $sepatator = ' ' ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return null; }

	$instructors = $course->get_instructors_link();

	if ( ! empty( $instructors ) ) {
		return implode( $sepatator, $instructors ); }

	return null;
}

/**
 * Returns course structure filter base on current user.
 *
 * @param int $course_id
 * @param bool $show_details
 *
 * @return null|string
 */
function coursepress_get_course_structure( $course_id = 0, $show_details = false ) {
	$course = coursepress_get_course( $course_id );

	if ( ! is_wp_error( $course ) ) {
		return $course->get_course_structure( $show_details );
	}

	return null;
}

/**
 * Returns CoursePress courses url.
 *
 * @return string
 */
function coursepress_get_main_courses_url() {
	$main_slug = coursepress_get_setting( 'slugs/course', 'courses' );

	return home_url( '/' ) . trailingslashit( $main_slug );
}

/**
 * Returns the course's URL structure.
 *
 * @param $course_id
 *
 * @return string
 */
function coursepress_get_course_permalink( $course_id = 0 ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return null; }

	return $course->get_permalink();
}

/**
 * Get the course's submenu links.
 *
 * @return array of submenu items.
 */
function coursepress_get_course_submenu() {
	$course = coursepress_get_course(); // Submenu only works on CoursePress pages

	if ( is_wp_error( $course ) || ! is_user_logged_in() ) {
		return null;
	}

	$course_id = $course->__get( 'ID' );
	$menus = array(
		'units' => array(
			'label' => __( 'Units', 'cp' ),
			'url' => coursepress_get_course_units_archive_url( $course_id ),
		),
	);

	if ( $course->__get( 'allow_discussion' ) ) {
		$menus['discussions'] = array(
			'label' => __( 'Forum', 'cp' ),
			'url' => esc_url_raw( $course->get_discussion_url() ),
		);
	}

	if ( $course->__get( 'allow_workbook' ) ) {
		$menus['workbook'] = array(
			'label' => __( 'Workbook', 'cp' ),
			'url' => esc_url_raw( $course->get_workbook_url() ),
		);
	}

	if ( $course->__get( 'allow_grades' ) ) {
		$menus['grades'] = array(
			'label' => __( 'Grades', 'cp' ),
			'url' => esc_url_raw( $course->get_grades_url() ),
		);
	}

	// Add course details link at the last
	$menus['course-details'] = array(
		'label' => __( 'Course Details', 'cp' ),
		'url' => esc_url_raw( $course->get_permalink() ),
	);

	/**
	 * Fired to allow adding course menu.
	 *
	 * @since 3.0
	 */
	$menus = apply_filters( 'coursepress_course_submenu', $menus, $course );

	return $menus;
}

/**
 * Returns the course's units archive link.
 *
 * @param int $course_id
 *
 * @return string|null
 */
function coursepress_get_course_units_archive_url( $course_id = 0 ) {
	$course_url = coursepress_get_course_permalink( $course_id );

	if ( ! $course_url ) {
		return null;
	}

	$units_slug = coursepress_get_setting( 'slugs/units', 'units' );

	return $course_url . trailingslashit( $units_slug );
}

/**
 * Gets the current serve course cycle.
 *
 * @since 3.0
 */
function coursepress_get_current_course_cycle() {
	/**
	 * @var array $_course_module An array of current module data.
	 * @var object $_course_step
	 */
	global $CoursePress_VirtualPage, $_course_module, $_course_step, $_coursepress_previous;

	$course = coursepress_get_course();

	if ( is_wp_error( $course ) ) {
		return null; }

	$unit = coursepress_get_unit();

	if ( is_wp_error( $unit ) ) {
		return null;
	}

	if ( ! $CoursePress_VirtualPage instanceof CoursePress_VirtualPage ) {
		return null;
	}

	$vp = $CoursePress_VirtualPage;
	$vp_type = $vp->__get( 'type' );
	$_coursepress_previous = $course->get_units_url();
	$course_id = $course->__get( 'ID' );
	$unit_id = $unit->__get( 'ID' );
	$user = coursepress_get_user();

	if ( ! in_array( $vp_type, array( 'unit', 'module', 'step' ) ) ) {
		return null;
	}

	$view_mode = $course->get_view_mode();

	$form_attr = array(
		'method' => 'post',
		'action' => admin_url( 'admin-ajax.php?action=coursepress_submit' ),
		'class' => 'coursepress-course coursepress-course-form',
	);
	$template = coursepress_create_html(
		'input',
		array(
			'type' => 'hidden',
			'name' => 'course_id',
			'value' => $course_id,
		)
	);
	$template .= coursepress_create_html(
		'input',
		array(
			'type' => 'hidden',
			'name' => 'unit_id',
			'value' => $unit->__get( 'ID' ),
		)
	);
	$template .= coursepress_create_html(
		'input',
		array(
			'type' => 'hidden',
			'name' => 'type',
			'value' => $vp_type,
		)
	);
	$template .= coursepress_create_html(
		'input',
		array(
			'type' => 'hidden',
			'name' => 'module_id',
			'value' => $_course_module ? $_course_module['id'] : 0,
		)
	);
	$template .= coursepress_create_html(
		'input',
		array(
			'type' => 'hidden',
			'name' => 'step_id',
			'value' => $_course_step ? $_course_step->__get( 'ID' ) : 0,
		)
	);
	$referer_url = '';
	$has_access = coursepress_has_access( $course_id, $unit_id );

	if ( $has_access['access'] ) {

		if ( 'focus' == $view_mode ) {
			if ( 'unit' == $vp_type ) {
				$template    .= $vp->create_html(
					'div',
					array( 'class' => 'unit-description' ),
					apply_filters( 'the_content', $unit->get_description() )
				);
				$referer_url = $unit->get_permalink();

			} elseif ( 'module' == $vp_type && $_course_module ) {
				$has_access = coursepress_has_access( $course_id, $unit_id, $_course_module['id'] );

				if ( $has_access['access'] ) {
					$template .= $vp->create_html(
						'h3',
						array(),
						$_course_module['title']
					);

					$description = htmlspecialchars_decode( $_course_module['description'] );
					$template    .= $vp->create_html(
						'div',
						array( 'class' => 'module-description' ),
						apply_filters( 'the_content', $description )
					);
					$referer_url = $_course_module['url'];

					// Record module visit
					$user->add_visited_module( $course_id, $unit_id, $_course_module['id'] );
				}
			} elseif ( 'step' == $vp_type && $_course_step ) {
				$has_access = coursepress_has_access( $course_id, $unit_id, $_course_module['id'], $_course_step->__get( 'ID' ) );

				if ( $has_access['access'] ) {
					$template    .= $_course_step->template();
					$referer_url = $_course_step->get_permalink();
					$user->add_visited_step( $course_id, $unit_id, $_course_step->__get( 'ID' ) );

					if ( 'input-upload' === $_course_step->__get( 'module_type' ) ) {
						$form_attr['enctype'] = 'multipart/form-data';
					}
				}
			}
		}
	}

	$previous = coursepress_create_html(
		'div',
		array( 'class' => 'course-previous-item' ),
		coursepress_get_previous_course_cycle_link()
	);

	$template .= coursepress_create_html(
		'input',
		array(
			'type' => 'hidden',
			'name' => 'redirect_url',
			'value' => coursepress_get_link_cycle( 'next' ),
		)
	);

	$template .= coursepress_create_html(
		'input',
		array(
			'type' => 'hidden',
			'name' => 'referer_url',
			'value' => $referer_url,
		)
	);

	if ( $has_access['access'] ) {
		$next = coursepress_create_html(
			'div',
			array( 'class' => 'course-next-item' ),
			coursepress_create_html(
				'button',
				array(
					'type'  => 'submit',
					'class' => 'button button-next coursepress-next-cycle',
					'name'  => 'submit_module',
					'value' => 1,
				),
				__( 'Next', 'cp' )
			)
		);
	} else {
		$next = '';
		$template .= coursepress_create_html(
			'p',
			array(
				'class' => 'description no-access-description',
			),
			$has_access['message']
		);
	}

	$template .= coursepress_create_html(
		'div',
		array( 'class' => 'course-step-nav' ),
		$previous . $next
	);

	$template = coursepress_create_html(
		'form',
		$form_attr,
		$template
	);

	return $template;
}

function coursepress_has_access( $course_id, $unit_id = 0, $module_id = 0, $step_id = 0 ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return null;
	}

	$unit = coursepress_get_unit( $unit_id );

	if ( is_wp_error( $unit ) ) {
		return null;
	}

	$user = coursepress_get_user();
	$force_current_unit_completion = $course->__get( 'force_current_unit_completion' );
	$force_current_unit_successful_completion = $course->__get( 'force_current_unit_successful_completion' );
	$with_modules = $course->is_with_modules();
	$has_access = true;
	$prevUnit = $unit->get_previous_unit();
	$message = '';
	$user_id = $user->ID;

	if ( $prevUnit ) {
		$has_access = $user->is_unit_completed( $course_id, $unit_id );

		if ( ! $has_access ) {
			$message = __( 'You need to complete the previous unit before this unit!', 'cp' );
		}
	}

	if ( $has_access ) {
		if ( $with_modules ) {
			if ( (int) $module_id > 0 ) {
				$modules = $unit->get_modules_with_steps();

				if ( $modules ) {
					foreach ( $modules as $id => $module ) {
						$module_completed = $user->is_module_completed( $course_id, $unit_id, $id );

						if ( $id !== $module_id && ! $module_completed ) {
							$has_access = false;
							$message = __( 'You need to complete the previous module(s) before this module!', 'cp' );
						}

						if ( $step_id > 0 && $module['steps'] ) {
							$steps = $module['steps'];

							if ( $steps ) {
								foreach ( $steps as $step ) {
									$_step_id = $step->__get( 'ID' );
									$is_step_completed = $user->is_step_completed( $course_id, $unit_id, $_step_id );

									if ( ! $is_step_completed && $step_id != $_step_id ) {
										$has_access = false;
										$message = __( 'You need to complete all previous steps before this step!', 'cp' );
										break;
									}

									if ( $step_id === $_step_id ) {
										break;
									}
								}
							}
						}

						if ( $id === $module_id ) {
							break;
						}
					}
				}
			}
		} else {
			$steps = $unit->get_steps();

			if ( $steps && (int) $step_id > 0 ) {
				foreach ( $steps as $step ) {
					$_step_id = $step->__get( 'ID' );

					$is_step_completed = $user->is_step_completed( $course_id, $unit_id, $_step_id );

					if ( ! $is_step_completed && $step_id != $_step_id ) {
						$has_access = false;
						$message = __( 'You need to complete all previous steps before this step!', 'cp' );
						break;
					}

					if ( $step_id === $_step_id ) {
						break;
					}
				}
			}
		}
	}

	return array( 'access' => $has_access, 'message' => $message );
}

function coursepress_get_link_cycle( $type = 'next' ) {
	global $CoursePress_VirtualPage, $_course_module, $_course_step;

	$course = coursepress_get_course();

	if ( is_wp_error( $course ) ) {
		return false;
	}

	$unit = coursepress_get_unit();

	if ( is_wp_error( $unit ) ) {
		return false;
	}

	$user = coursepress_get_user();

	$vp = $CoursePress_VirtualPage;
	$vp_type = $vp->__get( 'type' );
	$with_modules = $course->is_with_modules();
	$previous = $course->get_units_url();
	$next = '';
	$has_access = $user->has_access_at( $course->__get( 'ID' ) );

	if ( $with_modules ) {
		if ( 'unit' == $vp_type ) {
			if ( 'previous' == $type ) {
				$previousUnit = $unit->get_previous_unit();

				if ( $previousUnit ) {
					$previous = $previousUnit->get_unit_url();
				}
			} else {
				$modules = $unit->get_modules();
				$module = array_shift( $modules );

				if ( $module ) {
					$next = $module['url'];
				}
			}
		} elseif ( 'module' == $vp_type ) {
			$module_id = $_course_module['id'];

			if ( 'previous' == $type ) {
				$prevModule = $unit->get_previous_module( $module_id );

				if ( $prevModule ) {
					$prevSteps = $unit->get_steps( ! $has_access, true, (int) $prevModule['id'] );

					if ( $prevSteps ) {
						$prevStep = array_pop( $prevSteps );
						$previous = $prevStep->get_permalink();
					} else {
						$previous = $prevModule['url'];
					}
				} else {
					// Try previous unit
					$prevUnit = $unit->get_previous_unit();

					if ( $prevUnit ) {
						$previous = $prevUnit->get_unit_url();
					}
				}
			} else {
				$nextSteps = $unit->get_steps( ! $has_access, true, $module_id );

				if ( $nextSteps ) {
					$nextStep = array_shift( $nextSteps );
					$next = $nextStep->get_permalink();
				} else {
					// Try next module
					$nextModule = $unit->get_next_module( $module_id );

					if ( $nextModule ) {
						$next = $nextModule['url'];
					}
				}
			}
		} else {
			if ( 'previous' == $type ) {
				$prevStep = $_course_step->get_previous_step();

				if ( $prevStep ) {
					$previous = $prevStep->get_permalink();
				} else {
					$previous = $_course_module['url'];
				}
			} else {
				$nextStep = $_course_step->get_next_step();

				if ( $nextStep ) {
					$next = $nextStep->get_permalink();
				} else {
					$nextModule = $unit->get_next_module( $_course_module['id'] );

					if ( $nextModule ) {
						$next = $nextModule['url'];
					} else {
						// Try next unit
						$nextUnit = $unit->get_next_unit();

						if ( $nextUnit ) {
							$next = $nextUnit->get_unit_url();
						} else {
							$next = $course->get_permalink() . trailingslashit( 'completion/validate' );
						}
					}
				}
			}
		}
	} else {
		if ( 'previous' == $type ) {
			$prevStep = $_course_step->get_previous_step();

			if ( $prevStep ) {
				$previous = $prevStep->get_permalink();
			} else {
				$previous = $_course_module['url'];
			}
		} else {
			$nextStep = $_course_step->get_next_step();

			if ( $nextStep ) {
				$next = $nextStep->get_permalink();
			} else {
				// Try next unit
				$nextUnit = $unit->get_next_unit();

				if ( $nextUnit ) {
					$next = $nextUnit->get_unit_url();
				} else {
					$next = $course->get_permalink() . trailingslashit( 'completion/validate' );
				}
			}
		}
	}

	if ( 'previous' == $type ) {
		return $previous;
	} else {
		return $next;
	}
}

/**
 * Returns the course previous cycle.
 *
 * @param string $label
 *
 * @return null|string
 */
function coursepress_get_previous_course_cycle_link( $label = '' ) {
	global $CoursePress_VirtualPage;

	if ( ! $CoursePress_VirtualPage instanceof CoursePress_VirtualPage ) {
		return null;
	}

	$course = coursepress_get_course();

	if ( is_wp_error( $course ) ) {
		return null;
	}
	if ( empty( $label ) ) {
		$label = __( 'Previous', 'cp' );
	}

	$link = coursepress_get_link_cycle( 'previous' );

	return coursepress_create_html(
		'a',
		array(
			'href' => esc_url( $link ),
			'class' => 'button previous-button coursepress-previous-cycle',
		),
		$label
	);
}

/**
 * Returns the course next cycle.
 *
 * @param string $label
 *
 * @return null|string
 */
function coursepress_get_next_course_cycle_link( $label = '' ) {
	global $CoursePress_VirtualPage;

	if ( ! $CoursePress_VirtualPage instanceof CoursePress_VirtualPage ) {
		return null;
	}

	$course = coursepress_get_course();

	if ( is_wp_error( $course ) ) {
		return null;
	}

	if ( empty( $label ) ) {
		$label = __( 'Next', 'cp' );
	}

	$link = coursepress_get_link_cycle( 'next' );

	return coursepress_create_html(
		'a',
		array(
			'href' => esc_url( $link ),
			'class' => 'button next-button coursepress-next-cycle',
		),
		$label
	);
}

function coursepress_course_update_setting( $course_id, $settings = array() ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) || empty( $settings ) ) {
		return false;
	}

	$settings = wp_parse_args( $settings, $course->get_settings() );

	update_post_meta( $course_id, 'course_settings', $settings );

	return true;
}

/**
 * Change course status.
 *
 * @param int $course_id Course ID.
 * @param string $status New status (publish or draft).
 *
 * @return bool
 */
function coursepress_change_course_status( $course_id, $status ) {
	// Allowed statuses to change.
	$allowed_statuses = array( 'publish', 'draft', 'pending' );

	// @todo: Implement capability check.
	$capable = true;

	/**
	 * check is course
	 */
	if ( ! coursepress_is_course( $course_id ) ) {
		$capable = false;
	}

	if ( empty( $course_id ) || ! in_array( $status, $allowed_statuses ) || ! $capable ) {

		/**
		 * Perform actions when course status not changed.
		 *
		 * @param int $course_id Course ID.
		 * @param int $status Status.
		 *
		 * @since 1.2.1
		 */
		do_action( 'coursepress_course_status_change_fail', $course_id, $status );

		return false;
	}

	$post = array(
		'ID' => absint( $course_id ),
		'post_status' => $status,
	);

	// Update the course post status.
	if ( is_wp_error( wp_update_post( $post ) ) ) {

		// This action hook is documented above.
		do_action( 'coursepress_course_status_change_fail', $course_id, $status );

		return false;
	}

	/**
	 * Perform actions when course status is changed.
	 *
	 * var $course_id The course id.
	 * var $status The new status.
	 *
	 * @since 1.2.1
	 */
	do_action( 'coursepress_course_status_changed', $course_id, $status );

	return true;
}

/**
 * Create new course category from text.
 *
 * @param string $name New category name.
 *
 * @return bool|WP_Term
 */
function coursepress_create_course_category( $name = '' ) {

	if ( empty( $name ) ) {
		return false;
	}

	// @todo: Implement capability check.

	$result = wp_insert_term( $name, 'course_category' );

	// If term was created, return the term.
	if ( ! is_wp_error( $result ) && ! empty( $result['term_id'] ) ) {
		return get_term( $result['term_id'], 'course_category' );
	}

	return false;
}

/**
 * Get instructors list of the course.
 *
 * @param int $course_id Course ID.
 *
 * @return array
 */
function coursepress_get_course_instructors( $course_id ) {

	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return array();
	}

	$instructors = $course->get_instructors();

	if ( ! empty( $instructors ) ) {
		return $instructors;
	}

	return array();
}

/**
 * Get facilitators list of the course.
 *
 * @param int $course_id Course ID.
 * @param array $args
 *
 * @return array
 */
function coursepress_get_course_facilitators( $course_id ) {

	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return array();
	}

	$facilitators = $course->get_facilitators();

	if ( ! empty( $facilitators ) ) {
		return $facilitators;
	}

	return array();
}

function coursepress_delete_course( $course_id ) {
	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return $course;
	}

	$units = $course->get_units( false );

	if ( $units ) {
		foreach ( $units as $unit ) {
			$unit_id = $unit->__get( 'ID' );

			// Delete all steps
			$steps = $unit->get_steps( false );

			foreach ( $steps as $step ) {
				$step_id = $step->__get( 'ID' );

				if ( $step_id > 0 ) {
					wp_delete_post( $step_id, true );
				}
			}

			wp_delete_post( $unit_id );
		}
	}

	// Remove students of this course
	$students = $course->get_students();

	if ( $students ) {
		foreach ( $students as $student ) {
		}
	}

	/**
	 * Update course numbers
	 */
	$course->save_course_number( $course_id, $course->post_title, array( $course_id ) );

	// Now delete the course
	wp_delete_post( $course_id );

	/**
	 * Fired whenever a course is deleted.
	 */
	do_action( 'coursepress_course_deleted', $course_id, $course );
}

/**
 * Get units of a course.
 *
 * @param int $course_id Course ID.
 *
 * @return array
 */
function coursepress_get_course_units( $course_id ) {

	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return array();
	}

	$units = $course->get_units();

	if ( ! empty( $units ) ) {
		return $units;
	}

	return array();
}

/**
 * Change alert status to publish/draft.
 *
 * @param int $alert_id Alert ID.
 * @param string $status New status (publish or draft).
 *
 * @return bool
 */
function coursepress_change_course_alert_status( $alert_id, $status ) {

	// Allowed statuses to change.
	$allowed_statuses = array( 'publish', 'draft' );

	$capable = false;
	// Get author of the current alert.
	$author = get_post_field( 'post_author', $alert_id );
	// If current user is capable of updating any notification statuses.
	if ( current_user_can( 'coursepress_change_notification_status_cap' ) ) {
		$capable = true;
	} elseif ( $author == get_current_user_id() && current_user_can( 'coursepress_change_my_notification_status_cap' ) ) {
		$capable = true;
	}

	if ( empty( $alert_id ) || ! in_array( $status, $allowed_statuses ) || ! $capable ) {

		/**
		 * Perform actions when alert status not changed.
		 *
		 * @param int $alert_id Alert ID.
		 * @param int $status Status.
		 *
		 * @since 3.0.0
		 */
		do_action( 'coursepress_alert_status_change_fail', $alert_id, $status );

		return false;
	}

	$post = array(
		'ID' => absint( $alert_id ),
		'post_status' => $status,
	);

	// Update the alert post status.
	if ( is_wp_error( wp_update_post( $post ) ) ) {

		// This action hook is documented above.
		do_action( 'coursepress_alert_status_change_fail', $alert_id, $status );

		return false;
	}

	/**
	 * Perform actions when alert status is changed.
	 *
	 * var $alert_id The alert id.
	 * var $status The new status.
	 *
	 * @since 3.0.0
	 */
	do_action( 'coursepress_alert_status_changed', $alert_id, $status );

	return true;
}

function coursepress_get_course_step( $step_id = 0 ) {
	$step_type = get_post_meta( (int) $step_id, 'module_type', true );

	$class = array(
		'text' => 'CoursePress_Step_Text',
		'text_module' => 'CoursePress_Step_Text', // Legacy type
		'image' => 'CoursePress_Step_Image',
		'video' => 'CoursePress_Step_Video',
		'audio' => 'CoursePress_Step_Audio',
		'discussion' => 'CoursePress_Step_Discussion',
		'download' => 'CoursePress_Step_FileDownload',
		'zipped' => 'CoursePress_Step_Zip',
		'input-upload' => 'CoursePress_Step_FileUpload',
		'input-quiz' => 'CoursePress_Step_Quiz',
		'input-checkbox' => 'CoursePress_Step_Checkbox', // Legacy class
		'input-radio' => 'CoursePress_Step_Radio', // Legacy class
		'radio_input_module' => 'CoursePress_Step_Radio', // Legacy type
		'input-select' => 'CoursePress_Step_Select', // Legacy class
		'input-textarea' => 'CoursePress_Step_InputText',
		'input-text' => 'CoursePress_Step_InputText',
		'text_input_module' => 'CoursePress_Step_Written', // Legacy type
		'input-form' => 'CoursePress_Step_Form', // Legacy class
	);

	if ( isset( $class[ $step_type ] ) ) {
		$stepClass = $class[ $step_type ];
		$stepClass = new $stepClass( $step_id );

		return $stepClass;
	}

	return false;
}

function coursepress_get_post_object( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( 'module' === $post_type ) {
		return coursepress_get_course_step( $post_id );
	} elseif ( 'unit' === $post_type ) {
		return coursepress_get_unit( $post_id );
	} elseif ( 'course' === $post_type ) {
		return coursepress_get_course( $post_id );
	}

	return false;
}


function coursepress_get_course_object( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( 'module' === $post_type ) {
		$step = coursepress_get_course_step( $post_id );
		return $step->get_course();

	} elseif ( 'unit' === $post_type ) {
		$unit = coursepress_get_unit( $post_id );
		return $unit->get_course();

	} elseif ( 'course' === $post_type ) {
		return coursepress_get_course( $post_id );
	}

	return false;
}

/**
 * Create new course alert.
 *
 * @param int $course_id Course ID.
 * @param string $title Alert title.
 * @param string $content Alert content.
 *
 * @return int New alert id.
 */
function coursepress_create_course_alert( $course_id, $title, $content ) {

	$capable = false;
	// Get the course.
	$course = coursepress_get_course( $course_id );
	$user = coursepress_get_user( get_current_user_id() );
	// If current user is capable of updating any notification statuses.
	if ( is_wp_error( $course ) ) {
		$capable = false;
	} elseif ( $user->has_access_at( $course_id ) && current_user_can( 'coursepress_create_my_assigned_notification_cap' ) ) {
		$capable = true;
	} elseif ( $course->post_author == get_current_user_id() && current_user_can( 'coursepress_create_my_notification_cap' ) ) {
		$capable = true;
	}

	if ( ! $capable ) {

		/**
		 * Perform actions when alert could not be created.
		 *
		 * @param int $course_id Course ID.
		 * @param string $title Alert title.
		 * @param string $content Alert content.
		 *
		 * @since 3.0.0
		 */
		do_action( 'coursepress_alert_create_fail', $course_id, $title, $content );

		return false;
	}

	$post = array(
		'post_title' => sanitize_text_field( $title ),
		'post_content' => $content,
		'post_status' => 'publish',
		'post_type' => 'cp_notification',
	);

	$alert_id = wp_insert_post( $post );
	// If post creation failed.
	if ( is_wp_error( $alert_id ) ) {

		// This action hook is documented above.
		do_action( 'coursepress_alert_create_fail', $course_id, $title, $content );

		return false;
	}

	// Set alert course id.
	add_post_meta( $alert_id, 'alert_course', $course_id );

	/**
	 * Perform actions when alert creation was successful.
	 *
	 * @param int $alert_id Alert ID.
	 * @param int $course_id Course ID.
	 * @param string $title Alert title.
	 * @param string $content Alert content.
	 *
	 * @since 3.0.0
	 */
	do_action( 'coursepress_alert_create_success', $alert_id, $course_id, $title, $content );

	return $alert_id;
}

/**
 * Get modules by type
 *
 * @since 2.0.0
 *
 * @param string $type module type
 * @param integer $course_id course ID.
 * @return array Array of modules ids.
 */
function coursepress_get_all_modules_ids_by_type( $type, $course_id = null ) {
	global $CoursePress_Core;
	$args = array(
		'post_type' => $CoursePress_Core->__get( 'step_post_type' ),
		'fields' => 'ids',
		'suppress_filters' => true,
		'nopaging' => true,
		'meta_key' => 'module_type',
		'meta_value' => $type,
	);
	if ( ! empty( $course_id ) ) {
		$units = coursepress_get_units( $course_id );
		if ( empty( $units ) ) {
			return array();
		}
		$args['post_parent__in'] = $units;
	}
	$items = new WP_Query( $args );
	return $items->posts;
}

/**
 * Get units from course
 */
function coursepress_get_units( $course_id ) {
	global $CoursePress_Core;
	$args = array(
		'post_type' => $CoursePress_Core->__get( 'unit_post_type' ),
		'post_parent' => $course_id,
		'nopaging' => true,
		'fields' => 'ids',
	);
	$items = new WP_Query( $args );
	return $items->posts;
}

/**
 * Check is course post type.
 *
 * @param int|WP_Post Post object or post ID.
 * @return bool
 */
function coursepress_is_course( $course ) {
	global $CoursePress_Core;
	$post_type = get_post_type( $course );
	return $CoursePress_Core->course_post_type == $post_type;
}

function coursepress_discussion_module_link( $location, $comment ) {
	global $CoursePress_Core;
	/**
	 * Check WP_Comment class
	 */
	if ( ! is_a( $comment, 'WP_Comment' ) ) {
		return $location;
	}
	/**
	 * Check post type
	 */
	$unit_post_type = $CoursePress_Core->__get( 'step_post_type' );
	$post_type = get_post_type( $comment->comment_post_ID );
	if ( $unit_post_type !== $post_type ) {
		return $location;
	}
	/**
	 * Check module type
	 */
	$module_type = get_post_meta( $comment->comment_post_ID, 'module_type', true );
	if ( 'discussion' !== $module_type ) {
		return $location;
	}
	$unit_id = get_post_field( 'post_parent', $comment->comment_post_ID );
	$course_id = get_post_field( 'post_parent', $unit_id );
	$course_link = get_permalink( $course_id );
	$unit_slug = coursepress_get_setting( 'slugs/course', 'unit' );
	$location = esc_url_raw( $course_link . $unit_slug . get_post_field( 'post_name', $course_id ) . '#module-' . $comment->comment_post_ID );

	return $location;
}

function coursepress_invite_student( $course_id = 0, $student_data = array() ) {
	global $CoursePress;

	$course = coursepress_get_course( $course_id );

	if ( is_wp_error( $course ) ) {
		return false;
	}

	$email_type = 'course_invitation';

	if ( 'passcode' == $course->__get( 'enrollment_type' ) ) {
		$email_type = 'course_invitation_password';
	}

	$emailClass = $CoursePress->get_class( 'CoursePress_Email' );
	$email_data = $emailClass->get_email_data( $email_type );

	if ( empty( $email_data['enabled'] ) ) {
		return false;
	}

	$tokens = array(
		'COURSE_NAME' => $course->__get( 'post_title' ),
		'COURSE_EXCERPT' => $course->__get( 'post_excerpt' ),
		'COURSE_ADDRESS' => $course->get_permalink(),
		'WEBSITE_ADDRESS' => site_url( '/' ),
		'PASSCODE' => $course->__get( 'enrollment_passcode' ),
		'FIRST_NAME' => $student_data['first_name'],
		'LAST_NAME' => $student_data['last_name'],
	);
	$message = $course->replace_vars( $email_data['content'], $tokens );
	$email = sanitize_email( $student_data['email'] );

	$args = array(
		'message' => $message,
		'to' => $email,
	);
	$email_data = wp_parse_args( $email_data, $args );
	$emailClass->sendEmail( $email_type, $email_data );

	$student_data['date'] = $course->date( current_time( 'mysql' ) );
	$invited_students = $course->__get( 'invited_students' );

	if ( ! $invited_students ) {
		$invited_students = array();
	}

	$invited_students[ $email ] = $student_data;
	$course->update_setting( 'invited_students', $invited_students );

	return $student_data;
}

function coursepress_search_students( $args = array() ) {

	$is_search = ! empty( $args['search'] );
	$course_id = ! empty( $args['course_id'] ) ? (int) $args['course_id'] : 0;
	$found = array();

	if ( ! empty( $args['paged'] ) ) {
		$pagenum = (int) $args['paged'];
	}

	if ( $is_search ) {
		$search = $args['search'];
		$search_query = array(
			'search' => $search,
			'search_columns' => array(
				'ID',
				'user_login',
				'user_nicename',
				'user_email',
			),
		);
		$user_query = new WP_User_Query( $search_query );

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				if ( in_array( 'coursepress_student', $user->roles ) ) {
					$cp_user = coursepress_get_user( $user->ID );

					if ( $course_id > 0 ) {
						if ( $cp_user->is_enrolled_at( $course_id ) ) {
							$found[] = $cp_user;
						}
					} else {
						$found[] = $cp_user;
					}
				}
			}
		}
	}

	return $found;
}
/**
 * Get discussions.
 */
function coursepress_get_disscusions( $course_id ) {
		$args = array(
			'post_type' => 'discussions',
			'meta_query' => array(
				array(
					'key' => 'course_id',
					'value' => $course_id,
					'compare' => 'IN',
				),
			),
			'post_per_page' => 20,
		);
		$data = array();
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$post->course_id = (int) get_post_meta( $post->ID, 'course_id', true );
			$post->course_title = ! empty( $course_id ) ? get_the_title( $course_id ) : __( 'All courses', 'cp' );
			$post->course_id = ! empty( $course_id ) ? $course_id : 'all';

			$post->unit_id = (int) get_post_meta( $post->ID, 'unit_id', true );
			$post->unit_title = ! empty( $post->unit_id ) ? get_the_title( $post->unit_id ) : __( 'All units', 'cp' );
			$post->unit_id = ! empty( $post->unit_id ) ? $post->unit_id : 'course';
			$post->unit_id = 'all' === $post->course_id ? 'course' : $post->unit_id;
			$data[] = $post;
		}
		return $data;
}




