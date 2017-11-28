<?php
/**
 * Class CoursePress_User
 *
 * @since 3.0
 * @package CoursePress
 */
class CoursePress_User extends CoursePress_Utility {
	/**
	 * @var string
	 */
	protected $user_type = 'guest'; // Default to guest user

	/**
	 * @var array of user CP capabilities
	 */
	protected $user_caps = array();

	protected $progress_table;
	protected $student_table;
	private $is_error = false;

	/**
	 * @return bool
	 */
	public function is_error()
	{
		return $this->is_error;
	}

	/**
	 * CoursePress_User constructor.
	 *
	 * @param bool|int|WP_User $user
	 */
	public function __construct( $user = false ) {
		global $wpdb;

		if ( ! $user instanceof WP_User ) {
			$user = get_userdata( (int) $user );
		}

		if ( empty( $user ) || ! $user instanceof  WP_User ) {
			$this->is_error = true;
			return;
		}

		$this->progress_table = $wpdb->prefix . 'coursepress_student_progress';
		$this->student_table = $wpdb->prefix . 'coursepress_students';

		// Inherit WP_User object
		foreach ( $user as $key => $value ) {
			if ( 'data' == $key ) {
				foreach ( $value as $k => $v ) {
					$this->__set( $k, $v );
				}
			} else {
				$this->__set( $key, $value );
			}
		}

		$this->__set( 'first_name', get_user_meta( $user->ID, 'first_name', true ) );
		$this->__set( 'last_name', get_user_meta( $user->ID, 'last_name', true ) );
		$this->__set( 'description', get_user_meta( $user->ID, 'description', true ) );
		/**
		 * clear student data after delete student
		 */
		add_action( 'deleted_user', array( $this, 'clear_student_data' ) );
		add_action( 'remove_user_from_blog', array( $this, 'clear_student_data' ) );
	}

	/**
	 * Helper function to return WP_Error.
	 * @return WP_Error
	 */
	function wp_error() {
		return new WP_Error( 'wrong_param', __( 'Invalid user ID!', 'cp' ) );
	}

	/**
	 * Check if user is an administrator.
	 *
	 * @return bool
	 */
	function is_super_admin() {
		/**
		 * super admin with no role!
		 */
		if ( is_multisite() && is_super_admin( $this->ID ) ) {
			return true;
		}
		return isset( $this->roles ) && in_array( 'administrator', $this->roles );
	}

	/**
	 * Check if user is an instructor of any course.
	 *
	 * @return bool
	 */
	function is_instructor() {
		return isset( $this->roles ) && in_array( 'coursepress_instructor', $this->roles );
	}

	/**
	 * Check if user is a facilitator of any course.
	 *
	 * @return bool
	 */
	function is_facilitator() {
		return isset( $this->roles ) && in_array( 'coursepress_facilitator', $this->roles );
	}

	/**
	 * Check if user is an student of any course.
	 *
	 * @return bool
	 */
	function is_student() {
		return isset( $this->roles ) && in_array( 'coursepress_student', $this->roles );
	}

	/**
	 * Check if user is an instructor of the given course ID.
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function is_instructor_at( $course_id ) {
		$id = $this->__get( 'ID' );

		if ( ! $id ) {
			return false;
		}

		$instructor = get_user_meta( $id, 'instructor_' . $course_id, true );

		return $instructor == $id;
	}

	/**
	 * Check if user is a facilitator of the given course ID.
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function is_facilitator_at( $course_id ) {
		$id = $this->__get( 'ID' );

		if ( ! $id ) {
			return false;
		}

		$facilitator = get_user_meta( $id, 'facilitator_' . $course_id, true );

		return $facilitator == $id;
	}

	/**
	 * Check if user has administrator, instructor or facilitator access of the given course ID.
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function has_access_at( $course_id ) {
		if ( $this->is_super_admin()
			|| ( $this->is_instructor() && $this->is_instructor_at( $course_id ) )
			|| ( $this->is_facilitator() && $this->is_facilitator_at( $course_id ) )
		) {
			return true;
		}

		return false;
	}

	function get_name() {
		$id = $this->__get( 'ID' );

		$names = array(
			get_user_meta( $id, 'first_name', true ),
			get_user_meta( $id, 'last_name', true ),
		);

		$names = array_filter( $names );
		$display_name = $this->__get( 'display_name' );

		if ( empty( $names ) ) {
			return $display_name;
		}
		return implode( ' ', $names );
	}

	function get_avatar( $size = 42 ) {
		$avatar = get_avatar( $this->__get( 'user_email' ), $size );
		return $avatar;
	}

	function get_description() {
		$id = $this->__get( 'ID' );
		$description = get_user_meta( $id, 'description', true );
		return $description;
	}

	/**
	 * Get the list of courses where user is either an instructor or facilitator.
	 *
	 * @param bool $publish   Only published courses?
	 * @param bool $returnAll Should return all items?
	 * @param int  $count     Count of total courses (pass by ref.).
	 *
	 * @return array
	 */
	function get_accessible_courses( $publish = true, $returnAll = true, &$count = 0 ) {
		$courses = array();

		$args = array();

		if ( is_bool( $publish ) ) {
		    $args['post_status'] = $publish ? true : false;
		} else {
		    $args['post_status'] = $publish;
		}

		if ( $returnAll ) {
			$args['posts_per_page'] = -1;
		}
		if ( $this->is_super_admin() ) {
			$courses = coursepress_get_courses( $args, $count );
		} elseif ( $this->is_instructor() || $this->is_facilitator() ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'meta_key' => 'instructor',
					'meta_value' => $this->ID,
				),
				array(
					'meta_key' => 'facilitator',
					'meta_value' => $this->ID,
				),
			);
			$courses = coursepress_get_courses( $args, $count );
		}

			return $courses;
	}

	/************************************************
	 * USER AS STUDENT
	 ***********************************************/

	private function get_student_id( $course_id ) {
		global $wpdb;

		$id = $this->__get( 'ID' );

		if ( ! $id ) {
			return false;
		}

		$sql = $wpdb->prepare( "SELECT ID FROM `$this->student_table` WHERE `student_id`=%d AND `course_id`=%d", $id, $course_id );
		$student_id = $wpdb->get_var( $sql );

		return $student_id;
	}

	private function get_progress_id( $student_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT ID FROM `$this->progress_table` WHERE `student_id`=%d", $student_id );
		$progress_id = $wpdb->get_var( $sql );

		return (int) $progress_id;
	}

	function get_enrolled_courses_ids( $per_page = 0, $paged = 1 ) {
		global $wpdb;

		$id = $this->__get( 'ID' );
		$offset = ($per_page * $paged );
		$limit = $per_page * $paged;

		if ( ! $id ) {
			return null;
		}

		$sql = "SELECT `course_id` FROM `$this->student_table` WHERE `student_id`=%d";

		if ( $per_page > 0 ) {
			$sql .= ' LIMIT %d, %d';
			$sql = $wpdb->prepare( $sql, $id, $offset, $limit );
		} else {
			$sql = $wpdb->prepare( $sql, $id );
		}

		$results = $wpdb->get_results( $sql, OBJECT );
		$course_ids = array();

		if ( $results ) {
			foreach ( $results as $result ) {
				$course_ids[] = $result->course_id;
			}
		}

		return $course_ids;
	}

	/**
	 * Check if user is enrolled to the given course ID.
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function is_enrolled_at( $course_id ) {
		global $wpdb;

		$id = $this->__get( 'ID' );

		if ( ! $id ) {
			return false;
		}

		$student_id = $this->get_student_id( $course_id );

		return (int) $student_id > 0;
	}

	/**
	 * Add student to course
	 */
	public function add_course_student( $course_id ) {
		global $wpdb;
		if (  $this->is_enrolled_at( $course_id ) ) {
			return true;
		}
		$id = $this->__get( 'ID' );
		if ( empty( $id ) ) {
			return;
		}
		$array = array(
			'course_id' => $course_id,
			'student_id' => $id,
		);
		$wpdb->insert( $this->student_table, $array );
		return $wpdb->insert_id;
	}

	function remove_course_student( $course_id ) {
		global $wpdb;

		$id = $this->__get( 'ID' );

		if ( ! $id ) {
			return false;
		}

		$student_id = $this->get_student_id( $course_id );

		if ( (int) $student_id > 0 ) {
			// Delete as student
			$wpdb->delete( $this->student_table, array( 'ID' => $student_id ), array( '%d' ) );
			// Delete student progress
			$progress_id = $this->get_progress_id( $student_id );

			if ( $progress_id > 0 ) {
				$wpdb->delete( $this->progress_table, array( 'ID' => $progress_id ), array( '%d' ) );
			}
		}
	}

	function add_student_progress( $course_id = 0, $progress = array() ) {
		global $wpdb;

		if ( empty( $course_id ) || empty( $progress ) ) {
			return false;
		}

		$student_id = $this->get_student_id( $course_id );

		if ( (int) $student_id > 0 ) {
			$progress = maybe_serialize( $progress );

			$param = array(
				'course_id' => $course_id,
				'student_id' => $student_id,
				'progress' => $progress,
			);

			$progress_id = $this->get_progress_id( $student_id );

			if ( (int) 0 === $progress_id ) {
				$wpdb->insert( $this->progress_table, $param );
			} else {
				$wpdb->update( $this->progress_table, $param, array( 'ID' => $progress_id ) );
			}

			return true;
		}

		return false;
	}

	function get_course_progress_data( $course_id ) {
		global $wpdb;

		$id = $this->__get( 'ID' );

		if ( ! $id ) {
			return null;
		}

		if ( ! $this->is_enrolled_at( $course_id ) ) {
			return null;
		}

		$student_id = $this->get_student_id( $course_id );
		$progress_id = $this->get_progress_id( $student_id );

		if ( (int) $progress_id > 0 ) {
			$sql = $wpdb->prepare( "SELECT `progress` FROM `{$this->progress_table}` WHERE `ID`=%d", $progress_id );
			$progress = $wpdb->get_var( $sql );

			if ( ! empty( $progress ) ) {
				$progress = maybe_unserialize( $progress );

				return $progress;
			}
		}

		return null;
	}

	/**
	 * Returns an array courses where user is enrolled at.
	 *
	 * @param bool $published
	 * @param bool $returnAll
	 *
	 * @return array An array of CoursePress_Course object.
	 */
	function get_user_enrolled_at( $published = true, $returnAll = true ) {
		$posts_per_page = coursepress_get_option( 'posts_per_page', 20 );
		$course_ids = $this->get_enrolled_courses_ids();

		if ( empty( $course_ids ) ) {
			return $course_ids;
		}

		$args = array(
			'post_status' => $published ? 'publish' : 'any',
			'posts_per_page' => $returnAll ? -1 : $posts_per_page,
			'suppress_filters' => true,
			'post__in' => $course_ids,
		);

		return coursepress_get_courses( $args );
	}

	function get_completion_data( $course_id ) {
		global $CoursePress;

		$id = $this->__get( 'ID' );
		$defaults = array( 'version' => $CoursePress->version );
		$progress = $this->get_course_progress_data( $course_id );

		if ( empty( $progress ) ) {
			$progress = $defaults;
		}

		/**
		 * Fire before returning student's course progress.
		 *
		 * @since 2.0
		 */
		$progress = apply_filters( 'coursepress_get_student_progress', $progress, $id, $course_id );

		return $progress;
	}

	function add_visited_module( $course_id, $unit_id, $module_id ) {
		$progress = $this->get_completion_data( $course_id );
		$visited_pages = coursepress_get_array_val( $progress, 'units/' . $unit_id . '/visited_pages' );

		if ( ! $visited_pages ) {
			$visited_pages = array();
		}
		$visited_pages[ $module_id ] = $module_id;
		$progress = coursepress_set_array_val( $progress, 'units/' . $unit_id . '/visited_pages', $visited_pages );
		$this->add_student_progress( $course_id, $progress );

		return $progress;
	}

	function add_visited_step( $course_id, $unit_id, $step_id ) {
		$progress = $this->get_completion_data( $course_id );
		$modules_seen = coursepress_get_array_val( $progress, 'completion/' . $unit_id . '/modules_seen' );

		if ( ! $modules_seen ) {
			$modules_seen = array();
		}
		$modules_seen[ $step_id ] = $step_id;
		$progress = coursepress_set_array_val( $progress, 'completion/' . $unit_id . '/modules_seen', $modules_seen );
		$this->add_student_progress( $course_id, $progress );
	}

	function validate_completion_data( $course_id, $progress = array() ) {
		if ( ! $this->is_enrolled_at( $course_id ) ) {
			return false;
		}

		if ( ( $completion = $this->__get( 'completion_data' ) ) ) {
			return $completion;
		}

		if ( empty( $progress ) ) {
			$progress = $this->get_completion_data( $course_id );
		}

		$course = coursepress_get_course( $course_id );

		$is_done = coursepress_get_array_val( $progress, 'completion/completed' );
		$completion = coursepress_get_array_val( $progress, 'completion' );

		if ( empty( $completion ) ) {
			$completion = array();
		}

		$units = $course->get_units( true ); // Only validate published units
		$with_modules = $course->is_with_modules();
		$course_progress = 0;

		foreach ( $units as $unit ) {
			$unit_id = $unit->__get( 'ID' );
			$progress = $this->validate_unit( $unit, $with_modules, $progress );
			$unit_progress = coursepress_get_array_val( $progress, 'completion/' . $unit_id . '/progress' );

			$course_progress += (int) $unit_progress;
		}

		$progress = coursepress_set_array_val( $progress, 'completion/progress', $course_progress );

		return $progress;
	}

	function get_unit_progress_ratio( $unit, $with_modules ) {
		$count = 0;

		if ( $with_modules ) {
			$modules = $unit->get_modules_with_steps();
			$count += count( $modules );

			if ( $modules ) {
				foreach ( $modules as $module ) {
					if ( $module['steps'] ) {
						$steps = $module['steps'];
						$count += count( $steps );
					}
				}
			}
		} else {
			$steps = $unit->get_steps();
			$count = count( $steps );
		}

		if ( $count > 0 ) {
			return 100 / $count;
		}

		return 100;
	}

	/**
	 * Helper method to validate per unit user progress.
	 *
	 * @param CoursePress_Unit $unit
	 * @param bool $with_modules
	 * @param array $progress
	 *
	 * @return array
	 */
	private function validate_unit( $unit, $with_modules, $progress ) {
		$unit_id = $unit->__get( 'ID' );
		$course_id = $unit->__get( 'course_id' );
		$unit_progress = 0;
		$unit_grade = 0;
		$unit_gradable = 0;
		$unit_pass_grade = 0;
		$force_completion = $unit->__get( 'force_current_unit_completion' );
		$force_pass_completion = $unit->__get( 'force_current_unit_successful_completion' );
		$completion = coursepress_get_array_val( $progress, 'completion/' . $unit_id );
		$unit_ratio = $this->get_unit_progress_ratio( $unit, $with_modules );
		$unit_completion = coursepress_get_array_val( $progress, 'completion/' . $unit_id );

		if ( ! $unit_completion ) {
			$unit_completion = array();
		}

		if ( $with_modules ) {
			$modules = $unit->get_modules_with_steps();

			if ( $modules ) {
				foreach ( $modules as $module_id => $module ) {
					$module_progress = 0;
					$steps_count = 1 + count( $module['steps'] );
					$module_count = $steps_count;
					$module_ratio = 100 / $module_count;

					$module_seen = coursepress_get_array_val(
						$progress,
						'units/' . $unit_id . '/visited_pages/' . $module_id
					);

					if ( $module_seen ) {
						$module_progress += $module_ratio;
						$unit_progress += $unit_ratio;
					}

					if ( $module['steps'] ) {
						$steps_completion = $this->validate_steps( $module['steps'], $course_id, $unit_id, $progress, $force_completion, $force_pass_completion, $progress );

						if ( ! empty( $steps_completion['passed'] ) ) {
							$prev_passed = coursepress_get_array_val( $unit_completion, 'passed' );

							if ( ! $prev_passed ) {
								$prev_passed = array();
							}
							$passed = array_merge( $prev_passed, $steps_completion['passed'] );
							$unit_completion = coursepress_set_array_val( $unit_completion, 'passed', $passed );
						}

						if ( ! empty( $steps_completion['progress'] ) ) {
							$unit_progress += $steps_completion['progress'];
						}

						if ( ! empty( $steps_completion['module_progress'] ) ) {
							$module_progress = $module_progress + (int) $steps_completion['module_progress'];
						}

						if ( ! empty( $steps_completion['steps'] ) ) {
							foreach ( $steps_completion['steps'] as $step_id => $_step ) {
								$unit_completion = coursepress_set_array_val( $unit_completion, 'steps/' . $step_id, $_step );
							}
						}
					}

					//$unit_progress += $module_progress;
					$unit_completion = coursepress_set_array_val( $unit_completion, 'modules/' . $module['id'] . '/progress', $module_progress );
				}
			}
		} else {
			$steps = $unit->get_steps();

			if ( $steps ) {
				$steps_completion = $this->validate_steps( $steps, $course_id, $unit_id, $progress, $force_completion, $force_pass_completion, $progress );

				if ( ! empty( $steps_completion['passed'] ) ) {
					$prev_passed = coursepress_get_array_val( $unit_completion, 'passed' );

					if ( ! $prev_passed ) {
						$prev_passed = array();
					}
					$passed = array_merge( $prev_passed, $steps_completion['passed'] );
					$unit_completion = coursepress_set_array_val( $unit_completion, 'passed', $passed );
				}

				if ( ! empty( $steps_completion['progress'] ) ) {
					$unit_progress += $steps_completion['progress'];
				}

				if ( ! empty( $steps_completion['steps'] ) ) {
					foreach ( $steps_completion['steps'] as $step_id => $_step ) {
						$unit_completion = coursepress_set_array_val( $unit_completion, 'steps/' . $step_id, $_step );
					}
				}
			}
		}

		$unit_completion = coursepress_set_array_val( $unit_completion, 'progress', $unit_progress );
		$progress = coursepress_set_array_val( $progress, 'completion/' . $unit_id, $unit_completion );

		return $progress;
	}

	/**
	 * Helper method to validate course steps progress.
	 *
	 * @param array $steps
	 * @param int $course_id
	 * @param int $unit_id
	 * @param array $progress
	 * @param bool $force_completion
	 * @param bool $force_pass_completion
	 *
	 * @return array
	 */
	private function validate_steps( $steps, $course_id, $unit_id, $progress, $force_completion, $force_pass_completio ) {
		$total_steps = count( $steps );
		$required_steps = 0;
		$assessable_steps = 0;
		$step_progress = 0;
		$steps_grade = 0;
		$user_id = $this->__get( 'ID' );
		$passed = array();
		$answered = array();
		$seen = array();
		$gradable = 0;
		$completed = array();
		$steps_grades = array();
		$unit = coursepress_get_unit( $unit_id );
		$course = coursepress_get_course( $course_id );
		$unit_progress_ratio = $this->get_unit_progress_ratio( $unit, $course->is_with_modules() );
		$steps_completion = array();
		$module_count = 1 + count( $steps );
		$module_ratio = 100 / $module_count;
		$module_progress = 0;

		foreach ( $steps as $step ) {
			$step_id = $step->__get( 'ID' );
			$is_required = $step->__get( 'mandatory' );
			$is_assessable = $step->__get( 'assessable' );
			$minimum_grade = $step->__get( 'minimum_grade' );
			$step_type = $step->__get( 'module_type' );
			$is_answerable = $step->is_answerable();
			$step_seen = coursepress_get_array_val(
				$progress,
				'completion/' . $unit_id . '/modules_seen/' . $step_id
			);
			$valid = false;
			$count = 1;
			$item_progress = 0;

			if ( $is_required ) {
				$required_steps++;
				$count += 1;
			}
			if ( $is_assessable ) {
				$assessable_steps++;
				$count += 1;
			}
			$step_progress_ratio = $unit_progress_ratio / $count;
			$item_ratio = 100 / $count;
			$m_ratio = $module_ratio / $count;

			if ( $step_seen ) {
				$seen[ $step_id ] = $step_id;
				$step_progress += $step_progress_ratio;
				$item_progress += $item_ratio;
				$module_progress += $m_ratio;
			}

			if ( $is_answerable ) {
				$response = $this->get_response( $course_id, $unit_id, $step_id, $progress );

				if ( ! empty( $response ) ) {
					$grade = (int) coursepress_get_array_val( $response, 'grade' );
					$pass  = $grade >= $minimum_grade;
					$steps_grade += $grade;

					if ( $is_required ) {
						$step_progress += $step_progress_ratio;
						$item_progress += $item_ratio;
						$module_progress += $m_ratio;
					}

					if ( $pass ) {
						$passed[ $step_id ] = $step_id;

						if ( $is_assessable ) {
							$step_progress += $step_progress_ratio;
							$item_progress += $item_ratio;
							$module_progress += $m_ratio;
						}
					} else {
						if ( ! empty( $response['assessable'] ) ) {
							$step_progress += $step_progress_ratio;
							$item_progress += $item_ratio;
							$module_progress += $m_ratio;
						}
					}
				}
			} else {
				if ( 'discussion' == $step_type ) {
					$has_comments = coursepress_user_have_comments( $user_id, $step_id );

					if ( $is_required ) {
						if ( $has_comments ) {
							$step_progress += $step_progress_ratio;
							$item_progress += $item_ratio;
							$module_progress += $m_ratio;
						}
					} else {
						$step_progress += $step_progress_ratio;
						$item_progress += $item_ratio;
						$module_progress += $m_ratio;
					}
				} elseif ( 'video' == $step_type || 'audio' == $step_type ) {
					if ( ! $is_required ) {
						if ( $step_seen ) {
							$step_progress += $step_progress_ratio;
							$item_progress += $item_ratio;
							$module_progress += $m_ratio;
						}
					} elseif ( $step_seen ) {

					}
				} elseif ( ! $is_required ) {
					if ( $step_seen ) {
						$valid = true;
					} elseif ( ! $force_completion ) {
						$valid = true;
					}
				}
			}

			$steps_completion = coursepress_set_array_val( $steps_completion, $step_id . '/progress', $item_progress );
		}

		$completion = array(
			'required_steps' => $required_steps,
			'assessable_steps' => $assessable_steps,
			'total_steps' => $total_steps,
			'progress' => $step_progress,
			'passed' => $passed,
			'answered' => $answered,
			'average' => $steps_grade,
			'gradable' => $gradable,
			'completed_steps' => $completed,
			'steps_grades' => $steps_grades,
			'steps' => $steps_completion,
			'module_progress' => $module_progress,
		);

		return $completion;
	}

	/**
	 * Returns the user response.
	 *
	 * @param $course_id
	 * @param $unit_id
	 * @param $step_id
	 * @param bool $progress
	 *
	 * @return array|mixed|null|string
	 */
	function get_response( $course_id, $unit_id, $step_id, $progress = false ) {
		if ( ! $progress ) {
			$progress = $this->get_completion_data( $course_id );
		}

		$response = coursepress_get_array_val(
			$progress,
			'units/' . $unit_id . '/responses/' . $step_id
		);

		return $response;
	}

	/**
	 * Check if user had completed the given course ID.
	 * The completion status return here only provide status according to user interaction and
	 * course requisite. It does not tell if the user have pass nor failed the course.
	 *
	 * @param $course_id
	 *
	 * @return bool
	 */
	function is_course_completed( $course_id ) {
		$course_progress = $this->get_course_progress( $course_id );

		return $course_progress >= 100;
	}

	function get_date_enrolled($course_id)
	{
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');

		$date_enrolled = get_user_meta($this->__get('ID'), 'enrolled_course_date_' . $course_id);
		if (is_array($date_enrolled)) {
			$date_enrolled = array_pop($date_enrolled);
		}

		if (empty($date_enrolled)) {
			return esc_html__('Unknown enrolled date.', 'CP_TD');
		}

		$date_enrolled = date_i18n($date_format . ' ' . $time_format, strtotime($date_enrolled));

		return $date_enrolled;
	}

	/**
	 * Returns user's overall acquired grade.
	 *
	 * @param $course_id
	 *
	 * @return mixed|null|string
	 */
	function get_course_grade( $course_id ) {
		$progress = $this->get_completion_data( $course_id );
		return coursepress_get_array_val( $progress, 'completion/average' );
	}

	/**
	 * Returns user's course progress percentage.
	 *
	 * @param $course_id
	 *
	 * @return mixed|null|string
	 */
	function get_course_progress( $course_id = 0 ) {

		if ( ! $course_id ) {
			$course = coursepress_get_course();
			$course_id = $course->__get( 'ID' );
		}

		if ( ! $course_id ) {
			return false;
		}

		$progress = $this->get_completion_data( $course_id );

		return (int) coursepress_get_array_val( $progress, 'completion/progress' );
	}

	/**
	 * Returns user's course completion status. Statuses are `ongoing`|`passed`|`failed`.
	 * User is automatically mark as failed if the course had already ended.
	 *
	 * @param $course_id
	 *
	 * @return string
	 */
	function get_course_completion_status( $course_id, $progress = array() ) {
		if ( empty( $progress ) ) {
			$progress = $this->get_completion_data( $course_id );
		}

		$status = 'ongoing';

		if ( $this->is_course_completed( $course_id ) ) {
			$status = 'completed';

			// Check if user pass the course
			$completed = coursepress_get_array_val( $progress, 'completion/completed' );
			$failed = coursepress_get_array_val( $progress, 'completion/failed' );

			if ( $completed ) {
				$status = 'pass';
			} elseif ( $failed ) {
				$status = 'failed';
			}
		}

		if ( 'ongoing' == $status ) {
			$course = coursepress_get_course( $course_id );

			if ( $course->has_course_ended() ) {
				$status = 'incomplete';
			}
		}

		return $status;
	}

	function get_course_completion_url( $course_id ) {
		$progress = $this->get_course_progress( $course_id );
		$completion = coursepress_get_array_val( $progress, 'completion' );
		$course = coursepress_get_course( $course_id );

		if ( ! $completion ) {
			$completion = 'almost-there';
		}

		return $course->get_permalink() . trailingslashit( 'completion/' . $completion );
	}

	/**
	 * Returns user's grade of the given unit ID.
	 *
	 * @param $course_id
	 * @param $unit_id
	 *
	 * @return mixed|null|string
	 */
	function get_unit_grade( $course_id, $unit_id ) {
		$progress = $this->get_completion_data( $course_id );

		return coursepress_get_array_val( $progress, 'completion/' . $unit_id . '/average' );
	}

	/**
	 * Returns users' progress of the given unit ID.
	 *
	 * @param $course_id
	 * @param $unit_id
	 *
	 * @return mixed|null|string
	 */
	function get_unit_progress( $course_id, $unit_id ) {
		$progress = $this->get_completion_data( $course_id );

		return coursepress_get_array_val( $progress, 'completion/' . $unit_id . '/progress' );
	}

	/**
	 * Check if user have already seen the unit.
	 *
	 * @param $course_id
	 * @param $unit_id
	 *
	 * @return bool
	 */
	function is_unit_seen( $course_id, $unit_id ) {
		$progress = $this->get_completion_data( $course_id );
		$seen = coursepress_get_array_val( $progress, 'units/' . $unit_id );

		return ! empty( $seen );
	}

	/**
	 * Check if user has completed the unit.
	 *
	 * @param $course_id
	 * @param $unit_id
	 *
	 * @return bool
	 */
	function is_unit_completed( $course_id, $unit_id ) {
		$progress = $this->get_unit_progress( $course_id, $unit_id );

		return (int) $progress >= 100;
	}

	/**
	 * Check if user have pass the unit.
	 *
	 * @param $course_id
	 * @param $unit_id
	 *
	 * @return bool|mixed|null|string
	 */
	function has_pass_course_unit( $course_id, $unit_id ) {
		$is_completed = $this->is_unit_completed( $course_id, $unit_id );

		if ( ! $is_completed ) {
			return false;
		}

		$progress = $this->get_completion_data( $course_id );

		return coursepress_get_array_val( $progress, 'completion/' . $unit_id . '/completed' );
	}

	/**
	 * Returns progress percentage of the given module ID.
	 *
	 * @param $course_id
	 * @param $unit_id
	 * @param $module_id
	 *
	 * @return mixed|null|string
	 */
	function get_module_progress( $course_id, $unit_id, $module_id ) {
		$progress = $this->get_completion_data( $course_id );
		$path = 'completion/' . $unit_id . '/modules/' . $module_id . '/progress';

		return (int) coursepress_get_array_val( $progress, $path );
	}

	/**
	 * Check if user has seen the given module ID.
	 *
	 * @param $course_id
	 * @param $unit_id
	 * @param $module_id
	 *
	 * @return bool
	 */
	function is_module_seen( $course_id, $unit_id, $module_id ) {
		$progress = $this->get_completion_data( $course_id );
		$path = 'completion/' . $unit_id . '/course_module_seen/' . $module_id;

		$seen = coursepress_get_array_val( $progress, $path );

		return ! empty( $seen );
	}

	/**
	 * Check if user have completed the given module ID.
	 *
	 * @param $course_id
	 * @param $unit_id
	 * @param $module_id
	 *
	 * @return bool
	 */
	function is_module_completed( $course_id, $unit_id, $module_id ) {
		$module_progress = $this->get_module_progress( $course_id, $unit_id, $module_id );

		return (int) $module_progress >= 100;
	}

	/**
	 * Returns user's grade of the given step ID.
	 *
	 * @param $course_id
	 * @param $unit_id
	 * @param $step_id
	 *
	 * @returns int|null
	 */
	function get_step_grade( $course_id, $unit_id, $step_id ) {
		$response = $this->get_response( $course_id, $unit_id, $step_id );
		$grade = coursepress_get_array_val( $response, 'grade' );

		if ( ! empty( $response['assessable'] ) ) {
			$graded_by = coursepress_get_array_val( $response, 'graded_by' );

			if ( 'auto' === $graded_by ) {
				$grade = 'pending';
			}
		}

		return $grade;
	}

	function get_step_progress( $course_id, $unit_id, $step_id ) {
		$progress = $this->get_completion_data( $course_id );

		return coursepress_get_array_val( $progress, 'completion/' . $unit_id . '/steps/' . $step_id . '/progress' );
	}

	function is_step_seen( $course_id, $unit_id, $step_id ) {
		$progress = $this->get_completion_data( $course_id );
		$path = 'completion/' . $unit_id . '/modules_seen/' . $step_id;

		$seen = coursepress_get_array_val( $progress, $path );

		return ! empty( $seen );
	}

	function is_step_completed( $course_id, $unit_id, $step_id ) {
		$progress = $this->get_completion_data( $course_id );
		$path = 'completion/' . $unit_id . '/steps/' . $step_id . '/progress';

		$step_progress = coursepress_get_array_val( $progress, $path );

		return (int) $step_progress >= 100;
	}

	function get_step_status( $course_id, $unit_id, $step_id ) {
		$is_completed = $this->is_step_completed( $course_id, $unit_id, $step_id );

		if ( $is_completed ) {
			return 'completed';
		} else {
			$stepClass = coursepress_get_course_step( $step_id );
			$progress = $this->get_step_progress( $course_id, $unit_id, $step_id );

			if ( (int) $progress > 0 ) {

			}
		}
	}

	function get_step_grade_status( $course_id, $unit_id, $step_id ) {
		$status = '';
		$response = $this->get_response( $course_id, $unit_id, $step_id );

		if ( ! empty( $response ) ) {
			$grade     = $this->get_step_grade( $course_id, $unit_id, $step_id );
			$step      = coursepress_get_course_step( $step_id );
			$min_grade = $step->__get( 'minimum_grade' );
			$pass      = $grade >= $min_grade;
			$status    = '';

			if ( $pass ) {
				$status = 'pass';
			} else {
				if ( $step->is_assessable() ) {
					$status = 'failed';

					$is_assessable = coursepress_get_array_val( $response, 'assessable' );

					if ( $is_assessable ) {
						$graded_by = coursepress_get_array_val( $response, 'graded_by' );
						$status = 'pending';

						if ( 'auto' !== $graded_by ) {
							$status = 'failed';
						}
					}
				}
			}
		}

		return $status;
	}

	function record_response( $course_id, $unit_id, $step_id, $response, $graded_by = 'auto' ) {
		$date = current_time( 'mysql' );
		$response['date'] = $date;
		$response['graded_by'] = $graded_by;

		$progress = $this->get_completion_data( $course_id );
		$progress = coursepress_set_array_val( $progress, 'units/' . $unit_id . '/responses/' . $step_id, $response );

		//$progress = $this->validate_completion_data( $course_id, $progress );
		$this->add_student_progress( $course_id, $progress );
	}

	/*******************************************
	 * USER AS INSTRUCTOR
	 ******************************************/

	/**
	 * Returns courses where user is an instructor at.
	 *
	 * @param bool $published
	 * @param bool $returnAll
	 *
	 * @return array Returns an array of courses where each course is an instance of CoursePress_Course
	 */
	function get_instructed_courses( $published = true, $returnAll = true ) {
		$args = array(
			'post_status' => $published ? 'publish' : 'any',
			'meta_key' => 'instructor',
			'meta_value' => $this->__get( 'ID' ),
			'meta_compare' => 'IN',
		);

		if ( $returnAll ) {
			$args['posts_per_page'] = - 1;
		}

		$courses = coursepress_get_courses( $args );

		return $courses;
	}

	function get_instructor_profile_link() {
		if ( false == $this->is_instructor() ) {
			return null;
		}

		$slug = coursepress_get_setting( 'slugs/instructor_profile', 'instructor' );

		return site_url( '/' ) . trailingslashit( $slug ) . $this->__get( 'user_login' );
	}

	/******************************************
	 * USER AS FACILITATOR
	 *****************************************/

	function get_facilitated_courses( $published = true, $returnAll = true ) {
		$args = array(
			'post_status' => $published ? 'publish' : 'any',
			'meta_key' => 'facilitator',
			'meta_value' => $this->__get( 'ID' ),
		);

		if ( $returnAll ) {
			$args['posts_per_page'] = - 1;
		}

		$courses = coursepress_get_courses( $args );

		return $courses;
	}

	/**
	 * Get last activity of the user.
	 *
	 * @return string|bool
	 */
	function get_last_activity_time() {

		$id = $this->__get( 'ID' );

		// False if id not set.
		if ( empty( $id ) ) {
			return false;
		}

		// Get last activity time of the user.
		$last_seen = get_user_meta( $id, 'coursepress_latest_activity_time', true );
		if ( ! empty( $last_seen ) ) {
			return $last_seen;
		}

		return false;
	}

	/**
	 * clear student data after delete
	 */
	public function clear_student_data( $student_id ) {
		global $wpdb;
		$args = array( 'student_id' => $student_id );
		$wpdb->delete( $this->progress_table, $args );
		$wpdb->delete( $this->student_table, $args );
	}
}
