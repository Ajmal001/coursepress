<?php
/**
 * Class CoursePress_Course
 *
 * @since 3.0
 * @package CoursePress
 */
class CoursePress_Course extends CoursePress_Utility {
	protected $progress_table;
	protected $student_table;

	/**
	 * Course Number
	 */
	protected $count_title_name = 'course_number_by_title';

	/**
	 * CoursePress_Course constructor.
	 *
	 * @param int|WP_Post $course
	 */
	public function __construct( $course ) {
		global $wpdb;

		if ( ! $course instanceof WP_Post ) {
			$course = get_post( (int) $course );
		}

		if ( ! $course instanceof WP_Post
		     || $course->post_type != 'course' ) {
			return $this->wp_error();
		}

		$this->progress_table = $wpdb->prefix . 'coursepress_student_progress';
		$this->student_table = $wpdb->prefix . 'coursepress_students';

		$this->setUp( array(
			'ID' => $course->ID,
			'post_title' => $course->post_title,
			'post_excerpt' => $course->post_excerpt,
			'post_content' => $course->post_content,
			'post_status' => $course->post_status,
			'post_name' => $course->post_name,
			'post_author' => $course->post_author,
		) );

		// Set course meta
		$this->setUpCourseMetas();
	}

	function wp_error() {
		return new WP_Error( 'wrong_param', __( 'Invalid course ID!', 'cp' ) );
	}

	function setUpCourseMetas() {
		$course_id = $this->__get( 'ID' );
		$settings = $this->get_settings();
		$date_format = coursepress_get_option( 'date_format' );
		$time_now = current_time( 'timestamp' );
		$date_keys = array( 'course_start_date', 'course_end_date', 'enrollment_start_date', 'enrollment_end_date' );

		foreach ( $settings as $key => $value ) {
			if ( in_array( $key, $date_keys ) ) {
				$timestamp = strtotime( $value, $time_now );
				$value = date_i18n( $date_format, $timestamp );

				// Add timestamp info
				$this->__set( $key . '_timestamp', $timestamp );
			}

			// Legacy fixes
			if ( 'enrollment_type' === $key && 'anyone' === $value ) {
				$value = 'registered';
			}
			if ( 'on' === $value || 'yes' === $value ) {
				$value = true;
			}

			if ( 'off' === $value ) {
				$value = false;
			}

			$this->__set( $key, $value );
			$this->__set( 'meta_' . $key, $value );
		}

		$this->__set( 'course_view', 'focus' );

		// Legacy: fix course_type meta
		$cpv = get_post_meta( $course_id, 'cp_cpv', true );

		if ( ! $cpv ) {
			$this->__set( 'with_modules', true );
			$this->__set( 'course_type', 'auto-moderated' );
		}
	}

	function get_settings() {
		global $CoursePress;

		$pre_completion_content = sprintf( '<h3>%s</h3>', __( 'Congratulations! You have completed COURSE_NAME!', 'cp' ) );
		$pre_completion_content .= sprintf( '<p>%s</p>', __( 'Your course instructor will now review your work and get back to you with your final grade before issuing you a certificate of completion.', 'cp' ) );
		$completion_content = sprintf( '<h3>%s</h3><p>%s</p><p>DOWNLOAD_CERTIFICATE_BUTTON</p>',
			__( 'Congratulations! You have successfully completed and passed COURSE_NAME!', 'CP_TD' ),
			__( 'You can download your certificate here.', 'CP_TD' )
		);
		$failed_content = sprintf( '<p>%s</p><p>%s</p>',
			__( 'Unfortunately, you didn\'t pass COURSE_NAME.', 'CP_TD' ),
			__( 'Better luck next time!', 'CP_TD' )
		);
		$id = $this->__get( 'ID' );
		$course_meta = array(
			'course_type' => 'auto-moderated',
			'course_language' => __( 'English', 'cp' ),
			'allow_discussion' => false,
			'allow_workbook' => false,
			'payment_paid_course' => false,
			'listing_image' => '',
			'listing_image_thumbnail_id' => 0,
			'featured_video' => '',
			'enrollment_type' => 'registered',
			'enrollment_passcode' => '',
			'enrollment_prerequisite' => array(),

			'course_view' => 'focus',
			'structure_level' => 'unit',
			'course_open_ended' => true,
			'course_start_date' => 0,
			'course_end_date' => '',
			'enrollment_open_ended' => false,
			'enrollment_start_date' => '',
			'enrollment_end_date' => '',
			'class_limited' => '',
			'class_size' => '',

			'pre_completion_title' => __( 'Almost there!', 'CP_TD' ),
			'pre_completion_content' => $pre_completion_content,
			'minimum_grade_required' => 100,
			'course_completion_title' => __( 'Congratulations, You Passed!', 'CP_TD' ),
			'course_completion_content' => $completion_content,
			'course_failed_title' => __( 'Sorry, you did not pass this course!', 'CP_TD' ),
			'course_failed_content' => $failed_content,
			'basic_certificate_layout' => '',
			'basic_certificate' => false,
			'certificate_background' => '',
			'cert_margin' => array(
				'top' => 0,
				'left' => 0,
				'right' => 0,
			),
			'page_orientation' => 'L',
			'cert_text_color' => '#5a5a5a',
			'with_modules' => true,
			/**
			 * paid course defaults
			 */
			'mp_auto_sku' => true,
			'mp_product_price' => '',
			'mp_product_sale_price' => '',
			'mp_sale_price_enabled' => false,
			'mp_sku_placeholder' => sprintf( __( 'e.g. %s-%06d', 'cp' ), 'CP', $id ),
			'mp_sku' => '',
			'cpv' => 3,
		);

		$settings = get_post_meta( $id, 'course_settings', true );
		$settings = wp_parse_args( $settings, $course_meta );

		/**
		 * MarketPress plugin status
		 */
		$MarketPress = $CoursePress->get_class( 'CoursePress_Extension_MarketPress' );
		$settings['mp_is_instaled'] = $MarketPress->installed();
		$settings['mp_is_activated'] = $MarketPress->activated();

		return $settings;
	}

	function update_setting( $key, $value = array() ) {
		global $CoursePress_Core;

		$course_id = $this->__get( 'ID' );
		$settings = $this->get_settings();

		if ( true === $key ) {
			$settings = $value;

			foreach ( $settings as $key => $value ) {
				update_post_meta( $course_id, 'cp_' . $key, $value );
			}
		} else {
			$settings[ $key ] = $value;

			update_post_meta( $course_id, 'cp_' . $key, $value );
		}

		update_post_meta( $course_id, 'course_settings', $settings );

		// We need date types in most queries, store them as seperate meta key

		if ( true === $key ) {
			foreach ( $settings as $key => $value ) {
				update_post_meta( $course_id, $key, $value );
			}
		} else {
			update_post_meta( $course_id, $key, $value );
		}

		// Set post thumbnail ID if not empty
		if ( ! empty( $settings['listing_image_thumbnail_id'] ) ) {
			set_post_thumbnail( $course_id, $settings['listing_image_thumbnail_id'] );
		}

		if ( ! empty( $settings['course_category'] ) ) {
			$categories = $settings['course_category'];
			$category_type = $CoursePress_Core->__get( 'category_type' );
			wp_set_object_terms( $course_id, $categories, $category_type );
		}

		/**
		 * Fire whenever a course is created or updated.
		 *
		 * @param int $course_id
		 * @param array $course_meta
		 */
		do_action( 'coursepress_course_updated', $course_id, $settings );

		return true;
	}

	/**
	 * Returns course title.
	 *
	 * @return string
	 */
	function get_the_title() {
		return $this->__get( 'post_title' );
	}

	/**
	 * Returns course summary.
	 *
	 * @param int $length
	 *
	 * @return bool|null|string
	 */
	function get_summary( $length = 140 ) {
		$summary = $this->__get( 'post_excerpt' );
		$length++;

		if ( mb_strlen( $summary ) > $length ) {
			$summary = wp_strip_all_tags( $summary );
			$sub = mb_substr( $summary, 0, $length - 5 );
			$words = explode( ' ', $sub );
			$cut = ( mb_strlen( $words[ count( $words ) - 1 ] ) );

			if ( $cut < 0 ) {
				return mb_substr( $sub, 0, $cut ); } else { 				return $sub; }
		}

		return $summary;
	}

	function get_feature_image_url() {
		return $this->__get( 'listing_image' );
	}

	/**
	 * Get the course feature image.
	 *
	 * @param int $width
	 * @param int $height
	 *
	 * @return null|string
	 */
	function get_feature_image( $width = 235, $height = 235 ) {
		$id = $this->__get( 'ID' );

		if ( ! $width ) {
			$width = coursepress_get_setting( 'course/image_width', 235 ); }
		if ( ! $height ) {
			$height = coursepress_get_setting( 'course/image_height', 235 ); }

		$listing_image = $this->get_feature_image_url();

		// Try post-thumbnail
		if ( ! $listing_image ) {
			if ( has_post_thumbnail( $id ) ) {
				$listing_image = get_the_post_thumbnail( $id, array( $width, $height ), array( 'class' => 'course-feature-image' ) ); }
		} else {
			$listing_image = $this->create_html(
				'img',
				array(
					'src' => esc_url( $listing_image ),
					'class' => 'course-listing-image',
					'width' => $width,
					'height' => $height,
				)
			);
		}

		return $listing_image;
	}

	function get_feature_video_url() {
		return $this->__get( 'featured_video' );
	}

	function get_feature_video( $width = 235, $height = 235 ) {
		$feature_video = $this->get_feature_video_url();

		if ( ! $width ) {
			$width = coursepress_get_setting( 'course/image_width', 235 );
		}
		if ( ! $height ) {
			$height = coursepress_get_setting( 'course/image_height', 235 );
		}

		if ( ! empty( $feature_video ) ) {
			$attr = array(
				'src' => esc_url_raw( $feature_video ),
				'class' => 'video-js vjs-default-skin vjs-big-play-centered course-feature-video',
				'width' => $width,
				'height' => $height,
				'controls' => true,
				'data-setup' => $this->create_video_js_setup_data( $feature_video ),
			);

			return $this->create_html( 'video', $attr );
		}

		return null;
	}

	function get_media( $width = 235, $height = 235 ) {
		$media_type = coursepress_get_setting( 'course/details_media_type', 'image' );
		$image = $this->get_feature_image( $width, $height );

		if ( ( 'image' == $media_type || 'default' == $media_type ) && ! empty( $image ) ) {
			return $image;
		}
		$video = $this->get_feature_video( $width, $height );
		return empty( $video )? $image : $video;
	}

	function get_description() {
		$description = $this->__get( 'post_content' );

		// @todo: Fix HTML formatting issue here

		return $description;
	}

	function get_course_start_date() {
		return $this->__get( 'course_start_date' );
	}

	function get_course_end_date() {
		return $this->__get( 'course_end_date' );
	}

	function get_course_dates( $separator = ' - ' ) {
		$course_type = $this->__get( 'course_type' );
		$open = 'auto-moderated' == $course_type;
		if ( ! $open ) {
			$open = $this->__get( 'course_open_ended' );
		}
		if ( $open ) {
			return __( 'Open Ended', 'cp' );
		}
		return implode( $separator, array( $this->get_course_start_date(), $this->get_course_start_date() ) );
	}

	function get_enrollment_start_date() {
		$open_ended = $this->__get( 'enrollment_open_ended' );
		if ( $open_ended ) {
			return __( 'Anytime', 'cp' );
		}
		return $this->__get( 'enrollment_start_date' );
	}

	function get_enrollment_end_date() {
		return $this->__get( 'enrollment_end_date' );
	}

	function get_enrollment_dates( $separator = ' - ' ) {
		$open_ended = $this->__get( 'enrollment_open_ended' );

		if ( $open_ended ) {
			return __( 'Anytime', 'cp' );
		}

		return implode( $separator, array( $this->get_enrollment_start_date(), $this->get_enrollment_end_date() ) );
	}

	function get_course_language() {
		return $this->__get( 'course_language' );
	}

	function get_course_cost() {
		$price_html = __( 'FREE', 'cp' );

		if ( $this->__get( 'payment_paid_course' ) ) {
			$price = $this->__get( 'mp_product_price' );

			/**
			 * Trigger to allow changes on course cost
			 */
			$price_html = apply_filters( 'coursepress_course_cost', $price_html, $price, $this );
		}

		return $price_html;
	}

	function get_view_mode() {
		return $this->__get( 'course_view' );
	}

	function get_product_id() {
		return $this->__get( 'mp_product_id' );
	}

	function is_with_modules() {
		return $this->__get( 'with_modules' );
	}

	function is_paid_course() {
		return $this->__get( 'payment_paid_course' );
	}

	/**
	 * Check if the course has already started.
	 *
	 * @return bool
	 */
	function is_course_started() {
		$time_now = $this->date_time_now();
		$openEnded = $this->__get( 'course_open_ended' );
		$start_date = $this->__get( 'course_start_date_timestamp' );

		if ( empty( $openEnded )
		     && $start_date > 0
		     && $start_date > $time_now ) {
			return false; }

		return true;
	}

	/**
	 * Check if the course is no longer open.
	 *
	 * @return bool
	 */
	function has_course_ended() {
		$time_now = $this->date_time_now();
		$openEnded = $this->__get( 'course_open_ended' );
		$end_date = $this->__get( 'course_end_date_timestamp' );

		if ( empty( $openEnded )
		     && $end_date > 0
		     && $end_date < $time_now ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the course is available
	 *
	 * @return bool
	 */
	function is_available() {
		$course_type = $this->__get( 'course_type' );

		if ( 'auto-moderated' == $course_type ) {
			// Auto-moderated courses are always available
			return true;
		}

		$is_available = $this->is_course_started();

		if ( $is_available ) {
			// Check if the course hasn't ended yet
			if ( $this->has_course_ended() ) {
				$is_available = false;
			}
		}

		return $is_available;
	}

	/**
	 * Check if enrollment is open.
	 *
	 * @return bool
	 */
	function is_enrollment_started() {
		$time_now = $this->date_time_now();
		$enrollment_open = $this->__get( 'enrollment_open_ended' );
		$start_date = $this->__get( 'enrollment_start_date_timestamp' );

		if ( empty( $enrollment_open )
		     && $start_date > 0
		     && $start_date > $time_now ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if enrollment has closed.
	 *
	 * @return bool
	 */
	function has_enrollment_ended() {
		$time_now = $this->date_time_now();
		$enrollment_open = $this->__get( 'enrollment_open_ended' );
		$end_date = $this->__get( 'enrollment_end_date_timestamp' );

		if ( empty( $enrollment_open )
		     && $end_date > 0
		     && $end_date < $time_now ) {
			return true; }

		return false;
	}

	/**
	 * Check if user can enroll to the course.
	 *
	 * @return bool
	 */
	function user_can_enroll() {
		$available = $this->is_available();

		if ( $available ) {
			// Check if enrollment has started
			$available = $this->is_enrollment_started();

			// Check if enrollment already ended
			if ( $available && $this->has_course_ended() ) {
				$available = false; }
		}

		return $available;
	}

	private function _get_instructors() {
		$id = $this->__get( 'ID' );
		$instructor_ids = get_post_meta( $id, 'instructor' );

		if ( is_array( $instructor_ids ) ) {
			$instructor_ids = array_filter( $instructor_ids );
		}

		if ( ! empty( $instructor_ids ) ) {
			return $instructor_ids;
		}

		// Legacy call
		// @todo: Delete this meta
		$instructor_ids = get_post_meta( $id, 'instructors', true );

		if ( ! empty( $instructor_ids ) ) {
			foreach ( $instructor_ids as $instructor_id ) {
				coursepress_add_course_instructor( $instructor_id, $id );
			}
		}

		return $instructor_ids;
	}

	/**
	 * Count total number of course instructors.
	 *
	 * @return int
	 */
	function count_instructors() {
		return count( $this->_get_instructors() );
	}

	/**
	 * Get course instructors.
	 *
	 * @return array An array of WP_User object on success.
	 */
	function get_instructors() {
		$instructors = array();
		$instructor_ids = $this->_get_instructors();

		if ( ! empty( $instructor_ids ) ) {
			foreach ( $instructor_ids as $instructor_id ) {
				$instructors[ $instructor_id ] = coursepress_get_user( $instructor_id );
			}
		}

		return $instructors;
	}

	function get_instructors_link() {
		$instructors = $this->get_instructors();
		$links = array();

		if ( ! empty( $instructors ) ) {
			foreach ( $instructors as $instructor ) {
				$links[] = $this->create_html(
					'a',
					array(
						'href' => esc_url( $instructor->get_instructor_profile_link() ),
					),
					$instructor->get_name()
				);
			}
		}

		return $links;
	}

	private function _get_facilitators() {
		$id = $this->__get( 'ID' );
		$facilitator_ids = get_post_meta( $id, 'facilitator' );

		if ( is_array( $facilitator_ids ) && ! empty( $facilitator_ids ) ) {
			return array_unique( array_filter( $facilitator_ids ) ); }

		return array();
	}

	/**
	 * Count the total number of course facilitators.
	 *
	 * @return int
	 */
	function count_facilitators() {
		return count( $this->_get_facilitators() );
	}

	/**
	 * Get course facilitators.
	 *
	 * @return array of WP_User object
	 */
	function get_facilitators() {
		$facilitator_ids = $this->_get_facilitators();

		return array_map( 'get_userdata', $facilitator_ids );
	}

	private function _get_students( $all = true, $paged = 1 ) {
		global $wpdb;

		$id = $this->__get( 'ID' );
		$offset = ( $paged - 1 ) * 20;
		$limit = 20;

		$sql = "SELECT S.`student_id` FROM `$this->student_table` AS S ";
		$sql .= "LEFT JOIN `$wpdb->users` AS U on S.`student_id` = U.`ID` ";
		$sql .= 'WHERE S.`course_id` = %d ';
		$sql .= 'ORDER BY U.`user_login` ';

		if ( ! $all ) {
			$sql .= ' LIMIT %d, %d';
		}
		$sql = $wpdb->prepare( $sql, $id, $offset, $limit );

		$results = $wpdb->get_results( $sql, OBJECT );
		$student_ids = array();

		if ( $results ) {
			foreach ( $results as $result ) {
				$student_ids[] = $result->student_id;
			}
		}

		return $student_ids;
	}

	/**
	 * Count total number of students in a course.
	 *
	 * @return int
	 */
	function count_students() {
		return count( $this->_get_students() );
	}

	/**
	 * Get course students
	 *
	 * @return array of CoursePress_User object
	 */
	function get_students( $all = true, $paged = 1 ) {
		$students = array();
		$student_ids = $this->_get_students( $all, $paged );

		if ( ! empty( $student_ids ) ) {
			foreach ( $student_ids as $student_id ) {
				$students[ $student_id ] = new CoursePress_User( $student_id );
			}
		}

		return $students;
	}

	function get_invited_students() {
		$invitee = $this->__get( 'invited_students' );

		if ( ! empty( $invitee ) ) {
			foreach ( $invitee as $pos => $invite ) {
				if ( empty( $invite->date ) ) {
					// Legacy:: Previous invitation has no date
					$invite->date = '-';
				} else {
					$invite->date = $this->date( $invite->date );
				}
				$invitee->{$pos} = $invite;
			}
		}

		return $invitee;
	}

	function count_certified_students() {
		// @todo: count certified students here
		return 0;
	}

	/**
	 * Get an array of categories of the course.
	 *
	 * @return array
	 */
	function get_category() {
		$id = $this->__get( 'ID' );
		$course_category = wp_get_object_terms( $id, 'course_category' );
		$cats = array();

		if ( ! empty( $course_category ) ) {
			foreach ( $course_category as $term ) {
				$cats[ $term->term_id ] = $term->name; }
		}

		return $cats;
	}

	function get_permalink() {
		$course_name = $this->__get( 'post_name' );

		return coursepress_get_main_courses_url() . trailingslashit( $course_name );
	}

	function get_units_url() {
		$course_url = $this->get_permalink();
		$slug = coursepress_get_setting( 'slugs/units', 'units' );

		return $course_url . trailingslashit( $slug );
	}

	function get_discussion_url() {
		$course_url = $this->get_permalink();
		$discussion_slug = coursepress_get_setting( 'slugs/discussions', 'discussions' );

		return $course_url . trailingslashit( $discussion_slug );
	}

	function get_discussion_new_url() {
		$url = $this->get_discussion_url();
        $slug = coursepress_get_setting( 'slugs/discussions_new', 'add_new_discussion' );
		return $url . trailingslashit( $slug );
	}

	function get_grades_url() {
		$course_url = $this->get_permalink();
		$grades_slug = coursepress_get_setting( 'slugs/grades', 'grades' );

		return $course_url . trailingslashit( $grades_slug );
	}

	function get_unenroll_url( $redirect = '' ) {
		$url = array(
			'course_id' => $this->__get( 'ID' ),
			'action' => 'coursepress_unenroll',
			'_wpnonce' => wp_create_nonce( 'coursepress_nonce' ),
		);

		if ( ! empty( $redirect ) ) {
			$url['redirect'] = $redirect;
		}

		$url = add_query_arg( $url, admin_url( 'admin-ajax.php' ) );

		return $url;
	}

	function get_workbook_url() {
		$course_url = $this->get_permalink();
		$workbook_slug = coursepress_get_setting( 'slugs/workbook', 'workbook' );

		return $course_url . trailingslashit( $workbook_slug );
	}

	function get_edit_url() {
		$url = add_query_arg( array(
			'page' => 'coursepress_course',
			'cid' => $this->__get( 'ID' ),
		), admin_url( 'admin.php' ) );

		return $url;
	}

	private function _get_units( $published = true, $ids = true ) {
		$args = array(
			'post_type'      => 'unit',
			'post_status'    => $published ? 'publish' : 'any',
			'post_parent'    => $this->__get( 'ID' ),
			'posts_per_page' => -1, // Units are often retrieve all at once
			'suppress_filters' => true,
			'orderby' => 'menu_order',
			'order' => 'ASC',
		);

		if ( $ids ) {
			$args['fields'] = 'ids';
		}

		$units = get_posts( $args );

		return $units;
	}

	function count_units( $published = true ) {
		$units = $this->_get_units( $published );

		return count( $units );
	}

	function get_units( $published = true ) {
		if ( $this->__get( 'current_units' ) ) {
			return $this->__get( 'current_units' );
		}

		$units = array();
		$results = $this->_get_units( $published, false );

		if ( ! empty( $results ) ) {
			foreach ( $results as $unit ) {
				$unitClass = new CoursePress_Unit( $unit, $this );
				$units[] = $unitClass;
			}
		}

		$this->__set( 'current_units', $units );

		return $units;
	}

	function get_course_structure( $show_details = false ) {
		/**
		 * @var $user CoursePress_Student
		 */

		$course_id = $this->__get( 'ID' );
		$user = coursepress_get_user();
		$has_access = $user->has_access_at( $course_id );
		$structure = '';
		$units = $this->get_units( ! $has_access );

		if ( $units ) {
			foreach ( $units as $unit ) {
				$unit_structure = $unit->get_unit_structure( false, $show_details );
				$structure .= $this->create_html( 'li', false, $unit_structure );
			}
			$structure = $this->create_html( 'ul', array( 'class' => 'tree unit-tree' ), $structure );
		}

		return $structure;
	}


	/**
	 * Duplicate current course.
	 *
	 * This class object is created based on a WP_Post object. So using the current
	 * course post data, create new post of type "course". If success, then copy the
	 * course metadata to newly created course post.
	 * If there are units set, duplicate those units also.
	 *
	 * @return bool Success?
	 */
	function duplicate_course() {

		// Course ID is set when this class is instantiated.
		$course_id = $this->__get( 'ID' );

		// If in case course post object is not and ID not found, bail.
		if ( empty( $course_id ) ) {

			/**
			 * Perform actions if the duplication was failed.
			 *
			 * Note: We don't have course ID here.
			 *
			 * @since 3.0
			 */
			do_action( 'coursepress_course_duplicate_failed', false );

			return false;
		}

		/**
		 * Allow course duplication to be cancelled when filter returns true.
		 *
		 * @since 1.2.1
		 */
		if ( apply_filters( 'coursepress_course_cancel_duplicate', false, $course_id ) ) {

			/**
			 * Perform actions if the duplication was cancelled.
			 *
			 * @since 1.2.1
			 */
			do_action( 'coursepress_course_duplicate_cancelled', $course_id );

			return false;
		}

		// Copy of current course object.
		$new_course = $this;

		// Unset old ID, otherwise it will update the existing course.
		unset( $new_course->ID );

		// Set basic details.
		$new_course->post_author = get_current_user_id();
		$new_course->post_status = 'private';
		$new_course->post_type = 'course';
		$new_course->post_name = $new_course->post_name . '-copy';
		$new_course->post_title	= $new_course->post_title . ' (copy)';

		// Attempt to create new post of type "course".
		$new_course_id = wp_insert_post( $new_course );

		// If duplicate course was created.
		if ( ! empty( $new_course_id ) ) {

			// Copy the old course metadata to duplicated course.
			$course_metas = get_post_meta( $course_id );
			if ( ! empty( $course_metas ) ) {
				foreach ( $course_metas as $key => $value ) {
					$value = array_pop( $value );
					$value = maybe_unserialize( $value );
					update_post_meta( $new_course_id, $key, $value );
				}
			}

			// If units are available for course, duplicate them.
			$units = coursepress_get_units( $course_id );
			if ( ! empty( $units ) ) {
				foreach ( $units as $unit ) {
					$unit = new CoursePress_Unit( $unit );
					$unit->duplicate_unit( $new_course_id );
				}
			}

			/**
			 * Perform actions if the duplication was successful.
			 *
			 * @since 3.0
			 */
			do_action( 'coursepress_course_duplicated', $new_course_id );

			return true;
		}

		// This action is documented above.
		do_action( 'coursepress_course_duplicate_failed', $course_id );

		return false;
	}

	/**
	 * Delete current course.
	 *
	 * @return bool Success?
	 */
	function delete_course() {

		// Course ID is set when this class is instantiated.
		$course_id = $this->__get( 'ID' );

		// If in case course post object is not and ID not found, bail.
		if ( empty( $course_id ) ) {

			/**
			 * Perform actions if the deletion was failed.
			 *
			 * Note: We don't have course ID here.
			 *
			 * @since 3.0
			 */
			do_action( 'coursepress_course_delete_failed', false );

			return false;
		}

		// If units are available for course, delete them.
		$units = $this->get_units();
		if ( ! empty( $units ) ) {
			foreach ( $units as $unit ) {
				$unit = new CoursePress_Unit( $unit->ID );
				$unit->delete_unit();
			}
		}

		// Delete the course post.
		wp_delete_post( $course_id, true );
	}

	function get_status() {
		$status = $this->is_available() ? 'active' : '';

		if ( $this->has_course_ended() ) {
			$status = 'ended';
		} elseif ( ! $this->is_course_started() ) {
			$status = 'future';
		}

		return $status;
	}

	/**
	 * Get couse author user object.
	 *
	 * @return mixed CoursePress_User object or false.
	 */
	function get_author() {

		$author = false;

		// Get current course author id.
		$author_id = $this->__get( 'post_author' );
		if ( $author_id ) {
			// Get the coursepress user object.
			$author = coursepress_get_user( $author_id );
			// If not a valid user.
			if ( is_wp_error( $author ) ) {
				return false;
			}
		}

		return $author;
	}

	/**
	 * Add custom filed with counter for posts with indetical title
	 *
	 * @since 2.0.0
	 *
	 * @param integer $post_id Post ID
	 * @param string $post_title Post title.
	 * @param array $excludes Array of excluded Post IDs
	 */
	public function save_course_number( $post_id, $post_title, $excludes = array() ) {
		global $CoursePress_Core;
		if ( ! coursepress_is_course( $post_id ) ) {
			return;
		}
		global $wpdb;
		$course_post_type = $CoursePress_Core->course_post_type;
		$sql = $wpdb->prepare(
			"select ID from {$wpdb->posts} where post_title = ( select a.post_title from {$wpdb->posts} a where id = %d ) and post_type = %s and post_status in ( 'publish', 'draft', 'pending', 'future' ) order by id asc",
			$post_id,
			$course_post_type
		);
		$posts = $wpdb->get_results( $sql );
		$limit = 2 + count( $excludes );
		if ( count( $posts ) < $limit ) {
			delete_post_meta( $post_id, $this->count_title_name );
			return;
		}
		$count = 1;
		foreach ( $posts as $post ) {
			if ( ! empty( $excludes ) && in_array( $post->ID, $excludes ) ) {
				continue;
			}
			/**
			 * we need it only once
			 */
			if ( ! add_post_meta( $post->ID, $this->count_title_name, $count, true ) ) {
				update_post_meta( $post->ID, $this->count_title_name, $count );
			}
			$count++;
		}
	}

	/**
	 * Function called by filter "the_title" to add number.
	 *
	 * @since 2.0.0
	 *
	 * @param string $post_title Post title.
	 * @param integer $post_id Post ID.
	 * @return string Post title.
	 */
	public function get_numeric_identifier_to_course_name( $post_id = 0, $before = ' (', $after = ')' ) {
		if ( ! empty( $post_id ) ) {
			$number = get_post_meta( $post_id, $this->count_title_name, true );
			if ( ! empty( $number ) ) {
				return $before.$number.$after;
			}
		}
		return '';
	}
}
