<?php
/**
 * Class CoursePress_Data_Core
 *
 * @since 3.0
 * @package CoursePress
 */
final class CoursePress_Core extends CoursePress_Utility {
	var $courses = array();
	var $users = array();
	var $students = array();
	var $instructors = array();

	protected $course_post_type = 'course';
	protected $unit_post_type = 'unit';
	protected $step_post_type = 'module';
	protected $category_type = 'course_category';
	protected $notification_post_type = 'cp_notification';

	public function __construct() {
		// Register CP post types
		add_action( 'init', array( $this, 'register_post_types' ) );

		// Initialize unsubscribe
		add_action( 'init', array( $this, 'init_unsubscribe' ) );

		// Register CP query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		// Add CP rewrite rules
		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );
		// Listen to comment submission
		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2 );
	}

	public function register_post_types() {
		// Course
		$course_slug = coursepress_get_setting( 'slugs/course', 'courses' );
		register_post_type(
			$this->course_post_type,
			array(
				'public' => true,
				'label' => __( 'All Courses', 'cp' ),
				'show_ui' => false,
				'show_in_nav_menu' => false,
				'has_archive' => true,
				'can_export' => false, // CP have it's own export mechanism
				'delete_with_user' => false,
				'rewrite' => array(
					'slug' => 'courses',
					'with_front' => false,
				),
				'support' => array( 'comments' ),
				'labels' => array(
					'add_new_item' => __( 'New Course' ),
					'edit_item' => __( 'Edit Course' ),
					'new_item' => __( 'New Course' ),
					'view_item' => __( 'View Course' ),
					'view_items' => __( 'View Courses' ),
					'search_items' => __( 'Search Courses' ),
					'not_found' => __( 'No courses found.' ),
					'not_found_in_trash' => __( 'No courses found in Trash.' ),
					'attributes' => __( 'Course Attributes' ),
					'insert_into_item' => __( 'Insert into course' ),
					'uploaded_to_this_item' => __( 'Uploaded to this course' ),
					'filter_items_list' => __( 'Filter courses list' ),
					'items_list_navigation' => __( 'Courses list navigation' ),
					'items_list' => __( 'Courses list' ),
				),
			)
		);

		$category_slug = coursepress_get_setting( 'slugs/category', 'course_category' );

		register_taxonomy( $this->category_type,
			array( $this->course_post_type ),
			array(
				'public' => true,
				'rewrite' => array(
					'slug' => $category_slug,
					'with_front' => false,
				),
			)
		);

		// Unit
		register_post_type( $this->unit_post_type, array(
			'public' => false,
			'can_export' => false,
			'label' => 'Units', // debugging only,
			'query_var' => false,
			'publicly_queryable' => false,
			'supports' => array( 'thumbnail', 'comments' ),
			//'show_ui' => true,
			'hierarchical' => true,
		) );

		// Module
		register_post_type( $this->step_post_type, array(
			'public' => false,
			'show_ui' => false,
			'can_export' => false,
			'label' => 'Modules', // dbugging only
			'query_var' => false,
			'publicly_queryable' => false,
			'support' => array( 'comments' ),
			'hierarchical' => true,
		) );

		// Certificate
		register_post_type( 'cp_certificate', array(
			'public' => false,
			'show_ui' => false,
			'can_export' => false,
		) );

		// Notifications.
		register_post_type( $this->notification_post_type, array(
			'public' => true,
			'show_ui' => false,
			'can_export' => false,
			'show_in_nav_menu' => false,
			'has_archive' => true,
			'query_var' => false,
			'publicly_queryable' => false,
		) );
	}

	/**
	 * Initialize unsubscribe action.
	 *
	 * @return void
	 */
	function init_unsubscribe() {

		( new CoursePress_Data_Unsubscribe() )->init();
	}

	function add_query_vars( $vars ) {
		$vars[] = 'coursepress';
		$vars[] = 'unit';
		$vars[] = 'coursename';
		$vars[] = 'module';
		$vars[] = 'step';
		$vars[] = 'instructor';
		$vars[] = 'topic';
		$vars[] = 'notification';

		return $vars;
	}

	function add_rewrite_rules( $rules ) {
		$course_slug = coursepress_get_setting( 'slugs/course', 'courses' );
		$unit_slug = coursepress_get_setting( 'slugs/units', 'units' );
		$workbook_slug = coursepress_get_setting( 'slugs/workbook', 'workbook' );
		$notification_slug = coursepress_get_setting( 'slugs/notifications', 'notifications' );
		$discussion_slug = coursepress_get_setting( 'slugs/discussions', 'discussions' );
		$new_discussion_slug = coursepress_get_setting( 'slugs/discussions_new', 'new-discussion' );
		$grade_slug = coursepress_get_setting( 'slugs/grades', 'grades' );
		$instructor_slug = coursepress_get_setting( 'slugs/instructor_profile', 'instructor' );
		$student_dashboard = coursepress_get_setting( 'slugs/student_dashboard', 'courses-dashboard' );
		$student_settings = coursepress_get_setting( 'slugs/student_settings', 'student-settings' );
		$student_login = coursepress_get_setting( 'slugs/login', 'student-login' );
		$base = '^' . $course_slug . '/([^/]*)/';

		$new_rules = array(
			// Course completion
			$base . 'completion/almost-there/?' => 'index.php?coursename=$matches[1]&coursepress=completion-status',
			$base . 'completion/success/?' => 'index.php?coursename=$matches[1]&coursepress=completion-status',
			$base . 'completion/failed/?' => 'index.php?coursename=$matches[1]&coursepress=completion-status',
			$base . 'completion/validate/?' => 'index.php?coursename=$matches[1]&coursepress=completion',
			// Unit
			$base . $unit_slug . '/([^/]*)/?$' => 'index.php?coursename=$matches[1]&unit=$matches[2]&coursepress=unit',
			$base . $unit_slug . '/([^/]*)/([^/]*)/?$' => 'index.php?coursename=$matches[1]&unit=$matches[2]&module=$matches[3]&coursepress=module',
			$base . $unit_slug . '/([^/]*)/([^/]*)/([^/]*)/?$' => 'index.php?coursename=$matches[1]&unit=$matches[2]&module=$matches[3]&step=$matches[4]&coursepress=step',
			// Units archive
			$base . $unit_slug . '/?' => 'index.php?coursename=$matches[1]&coursepress=unit-archive',
			// Workbook
			$base . $workbook_slug . '/?' => 'index.php?coursename=$matches[1]&coursepress=workbook',
			// Notifications
			$base . $notification_slug . '/?' => 'index.php?coursename=$matches[1]&coursepress=notifications',

			/**
			 * Forum | Discussions
			 */
			$base . $discussion_slug . '/?$' => 'index.php?coursename=$matches[1]&coursepress=forum',
			$base . $discussion_slug . '/' . $new_discussion_slug . '/?' => 'index.php?coursename=$matches[1]&coursepress=forum&topic=new',
			$base . $discussion_slug . '/([^/]*)/?' => 'index.php?coursename=$matches[1]&coursepress=forum&topic=$matches[2]',

			// Grades
			$base . $grade_slug . '/?' => 'index.php?coursename=$matches[1]&coursepress=grades',
			// Course Instructor Profile
			'^' . $instructor_slug . '/([^/]*)/?' => 'index.php?coursepress=instructor&instructor=$matches[1]',
			// Student Dashboard
			'^' . $student_dashboard . '/?' => 'index.php?coursepress=student-dashboard',
			// Student Settings
			'^' . $student_settings . '/?' => 'index.php?coursepress=student-settings',
			// Student Login
			'^' . $student_login . '/?' => 'index.php?coursepress=student-login',
		);

		return array_merge( $new_rules, $rules );
	}

	function comments_open( $is_open, $object_id ) {
		$post_type = get_post_type( $object_id );
		$post_types = array( 'course', 'unit', 'module' );

		if ( in_array( $post_type, $post_types ) ) {
			return true;
		}

		return $is_open;
	}
}
