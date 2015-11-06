<?php

/**
 * Helper class for working with CoursePress capabilities.
 *
 * Previously CoursePress_Capabilities
 *
 * @since 1.0.0
 *
 * @return object
 */
class CoursePress_Model_Capabilities {

	public static $capabilities = array(
		'instructor' => array(
			/* General */
			'coursepress_dashboard_cap'								 => 1,
			'coursepress_courses_cap'								 => 1,
			'coursepress_instructors_cap'							 => 1,
			'coursepress_students_cap'								 => 1,
			'coursepress_assessment_cap'							 => 1,
			'coursepress_reports_cap'								 => 1,
			'coursepress_notifications_cap'							 => 1,
			'coursepress_discussions_cap'							 => 1,
			'coursepress_settings_cap'								 => 1,
			/* Courses */
			'coursepress_create_course_cap'							 => 1,
			'coursepress_update_course_cap'							 => 1,
			'coursepress_update_my_course_cap'						 => 1,
			'coursepress_update_all_courses_cap'					 => 0, // NOT IMPLEMENTED YET
			'coursepress_delete_course_cap'							 => 0,
			'coursepress_delete_my_course_cap'						 => 1,
			'coursepress_delete_all_courses_cap'					 => 0, // NOT IMPLEMENTED YET
			'coursepress_change_course_status_cap'					 => 0,
			'coursepress_change_my_course_status_cap'				 => 1,
			'coursepress_change_all_courses_status_cap'				 => 0, // NOT IMPLEMENTED YET
			/* Units */
			'coursepress_create_course_unit_cap'					 => 1,
			'coursepress_view_all_units_cap'						 => 0,
			'coursepress_update_course_unit_cap'					 => 1,
			'coursepress_update_my_course_unit_cap'					 => 1,
			'coursepress_update_all_courses_unit_cap'				 => 0, // NOT IMPLEMENTED YET
			'coursepress_delete_course_units_cap'					 => 1,
			'coursepress_delete_my_course_units_cap'				 => 1,
			'coursepress_delete_all_courses_units_cap'				 => 0, // NOT IMPLEMENTED YET
			'coursepress_change_course_unit_status_cap'				 => 1,
			'coursepress_change_my_course_unit_status_cap'			 => 1,
			'coursepress_change_all_courses_unit_status_cap'		 => 0, // NOT IMPLEMENTED YET
			/* Instructors */
			'coursepress_assign_and_assign_instructor_course_cap'	 => 0,
			'coursepress_assign_and_assign_instructor_my_course_cap' => 1,
			/* Classes */
			'coursepress_add_new_classes_cap'						 => 0,
			'coursepress_add_new_my_classes_cap'					 => 0,
			'coursepress_delete_classes_cap'						 => 0,
			'coursepress_delete_my_classes_cap'						 => 0,
			/* Students */
			'coursepress_invite_students_cap'						 => 0,
			'coursepress_invite_my_students_cap'					 => 1,
			'coursepress_withdraw_students_cap'						 => 0,
			'coursepress_withdraw_my_students_cap'					 => 1,
			'coursepress_add_move_students_cap'						 => 0,
			'coursepress_add_move_my_students_cap'					 => 1,
			'coursepress_add_move_my_assigned_students_cap'			 => 1,
			//'coursepress_change_students_group_class_cap' => 0,
			//'coursepress_change_my_students_group_class_cap' => 0,
			'coursepress_add_new_students_cap'						 => 1,
			'coursepress_send_bulk_my_students_email_cap'			 => 0,
			'coursepress_send_bulk_students_email_cap'				 => 1,
			'coursepress_delete_students_cap'						 => 0,
			/* Groups */
			'coursepress_settings_groups_page_cap'					 => 0,
			//'coursepress_settings_shortcode_page_cap' => 0,
			/* Notifications */
			'coursepress_create_notification_cap'					 => 1,
			'coursepress_create_my_assigned_notification_cap'		 => 1,
			'coursepress_create_my_notification_cap'				 => 1,
			'coursepress_update_notification_cap'					 => 0,
			'coursepress_update_my_notification_cap'				 => 1,
			'coursepress_delete_notification_cap'					 => 0,
			'coursepress_delete_my_notification_cap'				 => 1,
			'coursepress_change_notification_status_cap'			 => 0,
			'coursepress_change_my_notification_status_cap'			 => 1,
			/* Discussions */
			'coursepress_create_discussion_cap'						 => 1,
			'coursepress_create_my_assigned_discussion_cap'			 => 1,
			'coursepress_create_my_discussion_cap'					 => 1,
			'coursepress_update_discussion_cap'						 => 0,
			'coursepress_update_my_discussion_cap'					 => 1,
			'coursepress_delete_discussion_cap'						 => 0,
			'coursepress_delete_my_discussion_cap'					 => 1,
			/* Certificates */
			'coursepress_certificates_cap'							 => 0,
			'coursepress_create_certificates_cap'					 => 0,
			'coursepress_update_certificates_cap'					 => 0,
			'coursepress_delete_certificates_cap'					 => 0,
			/* Course Categories */
			'coursepress_course_categories_manage_terms_cap'		 => 1,
			'coursepress_course_categories_edit_terms_cap'			 => 1,
			'coursepress_course_categories_delete_terms_cap'		 => 0,
			/* Posts and Pages */
			'edit_pages'											 => 0,
			'edit_published_pages'									 => 0,
			'edit_posts'											 => 0,
			'publish_pages'											 => 0,
			'publish_posts'											 => 0
		),
	);

	public static function init() {
		add_action( 'set_user_role', array( __CLASS__, 'assign_role_capabilities' ), 10, 3 );
		add_action( 'wp_login', array( __CLASS__, 'restore_capabilities' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'fix_admin_capabilities' ) );
	}

	/**
	 * Assign appropriate CoursePress capabilities for roles
	 *
	 * @since 1.2.3.3.
	 *
	 */
	public static function assign_role_capabilities( $user_id, $role, $old_role ) {

		$capability_types = self::$capabilities[ 'instructor' ];

		if ( 'administrator' == $role ) {

			self::assign_admin_capabilities( $user_id );
		} else {

			$user				 = new WP_User( $user_id );
			$instructor_courses	 = CoursePress_Model_Instructor::get_assigned_courses_ids( $user_id );

			// Remove all CoursePress capabilities
			foreach ( $capability_types as $key => $value ) {
				$user->remove_cap( $key );
			}

			// If they are an instructor, give them their appropriate capabilities back
			if ( !empty( $instructor_courses ) ) {
				self::assign_instructor_capabilities( $user_id );
			}
		}
	}

	/**
	 * Make sure the admin has required capabilities
	 *
	 * @since 1.2.3.3.
	 *
	 */
	public static function restore_capabilities( $user_login = false, $user ) {
		if ( user_can( $user, 'manage_options' ) && !user_can( $user, 'coursepress_dashboard_cap' ) ) {
			self::assign_admin_capabilities( $user->ID );
		}
	}


	public static function fix_admin_capabilities () {
		$user_id = get_current_user_id();
		if ( user_can( $user_id, 'manage_options' ) && ! user_can( $user_id, 'coursepress_dashboard_cap' ) ) {
			self::assign_admin_capabilities( $user_id );
		}
	}

	public static function assign_admin_capabilities( $user ) {

		$user_id = CoursePress_Helper_Utility::get_id( $user );
		$user				 = new WP_User( $user_id );
		$capability_types	 = self::$capabilities[ 'instructor' ];
		$user->add_cap('manage_options');
		foreach ( $capability_types as $key => $value ) {
			$user->add_cap( $key );
		}
	}

	/**
	 * Can the user create a course?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_create_course( $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return ( user_can( $user_id, 'coursepress_create_course_cap' ) ) || user_can( $user_id, 'manage_options' );
	}

	/**
	 * Can the user update this course?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_update_course( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$course_creator	 = self::is_course_creator( $course_id, $user_id );
		$my_course		 = self::is_course_instructor( $course_id, $user_id );

		// For new courses
		if ( ( empty( $course_id ) || 0 == $course_id ) && ( user_can( $user_id, 'coursepress_update_my_course_cap' ) || user_can( $user_id, 'coursepress_update_course_cap' ) || user_can( $user_id, 'coursepress_update_all_courses_cap' ) || user_can( $user_id, 'manage_options' ) ) ) {
			return true;
		}

		// return ($my_course && user_can( $user_id, 'coursepress_update_my_course_cap' ) ) || user_can( $user_id, 'coursepress_update_course_cap' ) ? true : false;
		return ( $my_course && ( ( $course_creator && user_can( $user_id, 'coursepress_update_my_course_cap' ) ) || user_can( $user_id, 'coursepress_update_course_cap' ) ) ) || user_can( $user_id, 'coursepress_update_all_courses_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user delete this course?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_delete_course( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$course_creator	 = self::is_course_creator( $course_id, $user_id );
		$my_course		 = self::is_course_instructor( $course_id, $user_id );

		// return ($my_course && user_can( $user_id, 'coursepress_delete_my_course_cap' ) ) || user_can( $user_id, 'coursepress_delete_course_cap' ) ? true : false;
		return ( $my_course && ( ( $course_creator && user_can( $user_id, 'coursepress_delete_my_course_cap' ) ) || user_can( $user_id, 'coursepress_delete_course_cap' ) ) ) || user_can( $user_id, 'coursepress_delete_all_courses_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user change the course status?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_change_course_status( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// For new courses
		if ( ( empty( $course_id ) || 0 == $course_id ) && ( user_can( $user_id, 'coursepress_change_my_course_status_cap' ) || user_can( $user_id, 'coursepress_change_course_status_cap' ) || user_can( $user_id, 'coursepress_change_all_courses_status_cap' ) || user_can( $user_id, 'manage_options' ) ) ) {
			return true;
		}

		$course_creator	 = self::is_course_creator( $course_id, $user_id );
		$my_course		 = self::is_course_instructor( $course_id, $user_id );

		return ( $my_course && ( ( $course_creator && user_can( $user_id, 'coursepress_change_my_course_status_cap' ) ) || user_can( $user_id, 'coursepress_change_course_status_cap' ) ) ) || user_can( $user_id, 'coursepress_change_all_courses_status_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user create units?
	 *
	 * @since 1.0.0
	 *
	 *
	 * @return bool
	 */
	public static function can_create_unit( $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return user_can( $user_id, 'coursepress_create_course_unit_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user create units in this course?
	 *
	 * @since 1.0.0
	 *
	 *
	 * @return bool
	 */
	public static function can_create_course_unit( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$can_update_course	 = self::can_update_course( $course_id, $user_id );
		$can_create_units	 = self::can_create_unit( $user_id );

		return ( $can_update_course && $can_create_units ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user view units?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_view_course_units( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$my_course = self::is_course_instructor( $course_id, $user_id );

		return ( $my_course || user_can( $user_id, 'coursepress_view_all_units_cap' ) ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user update the units?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_update_course_unit( $course_id, $unit_id = '', $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$my_unit	 = self::is_unit_creator( $unit_id, $user_id );
		$my_course	 = self::is_course_instructor( $course_id, $user_id );

		// For new unit
		if ( ( empty( $unit_id ) || 0 == $unit_id ) && ( user_can( $user_id, 'coursepress_update_my_course_unit_cap' ) || user_can( $user_id, 'coursepress_update_course_unit_cap' ) || user_can( $user_id, 'coursepress_update_all_courses_unit_cap' ) || user_can( $user_id, 'manage_options' ) ) ) {
			if ( $my_course ) {
				return true;
			}
		}

		return ( $my_course && ( ( $my_unit && user_can( $user_id, 'coursepress_update_my_course_unit_cap' ) ) || user_can( $user_id, 'coursepress_update_course_unit_cap' ) ) ) || user_can( $user_id, 'coursepress_update_all_courses_unit_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user delete the units?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_delete_course_unit( $course_id, $unit_id = '', $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$my_unit	 = self::is_unit_creator( $unit_id, $user_id );
		$my_course	 = self::is_course_instructor( $course_id, $user_id );

		return ( $my_course && ( ( $my_unit && user_can( $user_id, 'coursepress_delete_my_course_units_cap' ) ) || user_can( $user_id, 'coursepress_delete_course_units_cap' ) ) ) || user_can( $user_id, 'coursepress_delete_all_courses_units_cap' ) ? true : false;
	}

	/**
	 * Can the user change the unit state?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_change_course_unit_status( $course_id, $unit_id = '', $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$my_unit	 = self::is_unit_creator( $unit_id, $user_id );
		$my_course	 = self::is_course_instructor( $course_id, $user_id );

		// For new unit
		if ( ( empty( $unit_id ) || 0 == $unit_id ) && ( user_can( $user_id, 'coursepress_change_my_course_unit_status_cap' ) || user_can( $user_id, 'coursepress_change_course_unit_status_cap' ) || user_can( $user_id, 'coursepress_change_all_courses_unit_status_cap' ) || user_can( $user_id, 'manage_options' ) ) ) {
			if ( $my_course ) {
				return true;
			}
		}

		return ( $my_course && ( ( $my_unit && user_can( $user_id, 'coursepress_change_my_course_unit_status_cap' ) ) || user_can( $user_id, 'coursepress_change_course_unit_status_cap' ) ) ) || user_can( $user_id, 'coursepress_change_all_courses_unit_status_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user assign a course instructor?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_assign_course_instructor( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// For new courses
		if ( ( empty( $course_id ) || 0 == $course_id ) && ( user_can( $user_id, 'coursepress_assign_and_assign_instructor_my_course_cap' ) || user_can( $user_id, 'coursepress_assign_and_assign_instructor_course_cap' ) || user_can( $user_id, 'manage_options' ) ) ) {
			return true;
		}

		$my_course = self::is_course_instructor( $course_id, $user_id );

		return ( $my_course && user_can( $user_id, 'coursepress_assign_and_assign_instructor_my_course_cap' ) ) || user_can( $user_id, 'coursepress_assign_and_assign_instructor_course_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Can the user invite students?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function can_assign_course_student( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$my_course = self::is_course_instructor( $course_id, $user_id );

		return ( $my_course && user_can( $user_id, 'coursepress_invite_my_students_cap' ) ) || user_can( $user_id, 'coursepress_invite_students_cap' ) || user_can( $user_id, 'manage_options' ) ? true : false;
	}

	/**
	 * Is the user an instructor of this course?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_course_instructor( $course_id, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$instructor_courses	 = CoursePress_Model_Instructor::get_assigned_courses_ids( $user_id );

		return in_array( $course_id, $instructor_courses );
	}

	/**
	 * Is the user the unit author?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_unit_creator( $unit_id = '', $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $unit_id ) ) {
			return false;
		} else {
			return $user_id == get_post_field( 'post_author', $unit_id ) ? true : false;
		}
	}

	/**
	 * Is the user the course author?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_course_creator( $course_id = '', $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $course_id ) ) {
			return false;
		} else {
			return $user_id == get_post_field( 'post_author', $course_id );
		}
	}

	public static function grant_private_caps( $user_id ) {
		$user				 = new WP_User( $user_id );
		$capability_types	 = array( 'course', 'unit', 'module', 'module_response', 'notification', 'discussion' );

		foreach ( $capability_types as $capability_type ) {
			$user->add_cap( "read_private_{$capability_type}s" );
		}
	}

	public static function drop_private_caps( $user_id = '', $role = '' ) {

		if ( empty( $user_id ) && empty( $role ) ) {
			return;
		}

		$user = false;
		if ( !empty( $user_id ) ) {
			$user = new WP_User( $user_id );
		}

		$capability_types = array( 'course', 'unit', 'module', 'module_response', 'notification', 'discussion' );

		foreach ( $capability_types as $capability_type ) {
			if ( !empty( $user ) ) {
				$user->remove_cap( "read_private_{$capability_type}s" );
			}
			if ( !empty( $role ) ) {
				$role->remove_cap( "read_private_{$capability_type}s" );
			}
		}
	}

	public static function assign_instructor_capabilities( $user ) {

		$user_id = CoursePress_Helper_Utility::get_id( $user );

		// The default capabilities for an instructor
		$instructor_capabilities = self::get_instructor_capabilities();

		$role = new WP_User( $user_id );

		$global_option = !is_multisite();
		update_user_option( $user_id, 'role_ins', 'instructor', $global_option );

		$role->add_cap( 'can_edit_posts' );
		$role->add_cap( 'read' );
		$role->add_cap( 'upload_files' );

		foreach ( $instructor_capabilities as $cap ) {
			$role->add_cap( $cap );
		}
	}

	public static function drop_instructor_capabilities( $user ) {

		$user_id = CoursePress_Helper_Utility::get_id( $user );

		if ( user_can( $user_id, 'manage_options' ) ) {
			return;
		}

		$role = new WP_User( $user_id );

		$global_option = !is_multisite();
		delete_user_option( $user_id, 'role_ins', $global_option );
		// Legacy
		delete_user_meta( $user_id, 'role_ins', 'instructor' );

		$role->remove_cap( 'can_edit_posts' );
		$role->remove_cap( 'read' );
		$role->remove_cap( 'upload_files' );

		$capabilities = array_keys( self::$capabilities[ 'instructor' ] );
		foreach ( $capabilities as $cap ) {
			$role->remove_cap( $cap );
		}

		self::grant_private_caps( $user_id );
	}

	//Add new roles and user capabilities
	public static function add_user_roles_and_caps() {
		/* ---------------------- Add initial capabilities for the admins */
		$role = get_role( 'administrator' );
		$role->add_cap( 'read' );

		// Add ALL instructor capabilities
		$admin_capabilities = array_keys(self::$capabilities[ 'instructor' ] );
		foreach ( $admin_capabilities as $cap ) {
			$role->add_cap( $cap );
		}

		self::drop_private_caps( '', $role );
	}


	public static function get_instructor_capabilities() {
		$default_capabilities    = array_keys( CoursePress_Model_Capabilities::$capabilities['instructor'], 1 );
		$instructor_capabilities = CoursePress_Core::get_setting( 'instructor/capabilities' );

		if( empty( $instructor_capabilities ) ) {
			$instructor_capabilities = array();
			foreach ( $default_capabilities as $cap ) {
				$instructor_capabilities[ $cap ] = true;
			}
		}

		return $instructor_capabilities;
	}

	/**
	 * Is this CoursePress Pro?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return true;
	}

	/**
	 * Is this runnning on CampusPress or Edublogs?
	 *
	 * @since 1.2.1
	 *
	 * @return bool
	 */
	public static function is_campus() {
		$campus_conditions	 = array( 'is_campus', 'is_edublogs' );
		$is_campus			 = false;

		foreach ( $campus_conditions as $condition ) {
			$is_campus |= function_exists( $condition ) && call_user_func( $condition );
		}

		return $is_campus;
	}

}