<?php
/**
 * Class CoursePress_Admin_Instructors
 *
 * @since 2.0
 * @package CoursePress
 */
class CoursePress_Admin_Instructors extends CoursePress_Admin_Page {
	private $items;
	private $count = 0;
	private $pagination;
	private $parent_slug;

	public function __construct() {
		parent::__construct();
		$this->parent_slug = $this->slug;
		$this->slug = 'coursepress_instructors';
	}

	function columns() {
		$columns = array(
			'id' => __( 'ID', 'cp' ),
			'instructor' => __( 'Instructor', 'cp' ),
			'registered' => __( 'Registered', 'cp' ),
			'courses' => __( 'Number of courses', 'cp' ),
		);
		return $columns;
	}

	public function get_page() {
		$search = isset( $_GET['instructor_search'] ) ? $_GET['instructor_search'] : '';
		$instructors = $this->get_list();
		$args = array(
			'columns' => $this->columns(),
			'courses' => coursepress_get_accessible_courses( false ),
			'hidden_columns' => array(),
			'instructors' => $instructors,
			'page' => $this->slug,
			'search' => $search,
			'instructor_edit_link' => '',
			'pagination' => $this->pagination,
		);
		coursepress_render( 'views/admin/instructors', $args );
		coursepress_render( 'views/admin/footer-text' );
	}

	public function get_list() {
		/**
		 * Search
		 */
		$usersearch = isset( $_REQUEST['instructor_search'] ) ? wp_unslash( trim( $_REQUEST['instructor_search'] ) ) : '';
		/**
		 * Per Page
		 */
		$per_page = $this->get_per_page();
		$per_page = $this->get_items_per_page( 'coursepress_instructors_per_page', $per_page );

		/**
		 * pagination
		 */
		$current_page = $this->get_pagenum();

		$offset = ( $current_page - 1 ) * $per_page;
		/**
		 * Query args
		 */
		$args = array(
			'number' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
			'role' => 'coursepress_instructor',
			'fields' => 'all_with_meta',
			'search' => $usersearch,
		);

		if ( ! empty( $_GET['course_id'] ) ) {
			// Show only students of current course
			$course_id = (int) $_GET['course_id'];
			$args['meta_key'] = 'instructor_' . $course_id;
			$args['meta_value'] = '1';
		}

		if ( '' !== $args['search'] ) {
			$args['search'] = '*' . $args['search'] . '*';
		}

		if ( $this->is_site_users ) {
			$args['blog_id'] = $this->site_id;
		}

		/**
		 * Fix multisite meta_key name
		 */
		if ( is_multisite() ) {
			global $wpdb;
			$args['blog_id'] = get_current_blog_id();
			$args['meta_key'] = sprintf( '%s%s', $wpdb->prefix, $args['meta_key'] );
		}

		// Query the user IDs for this page
		$wp_user_search = new WP_User_Query( $args );
		$this->count = $wp_user_search->total_users;
		$this->items = $wp_user_search->get_results();

		/**
		* pagination
		*/
		$listing = new WP_List_Table();
		$args = array(
			'total_items' => $wp_user_search->total_users,
			'per_page' => $per_page,
		);
		$listing->set_pagination_args( $args );
		$this->pagination = $listing;

		$url = add_query_arg(
			array(
				'page' => $this->parent_slug,
			),
			admin_url( 'admin.php' )
		);

		foreach ( array_keys( $this->items ) as $instructor_id ) {
			$this->items[ $instructor_id ]->count_courses = $this->count_courses( $instructor_id, true );
			$this->items[ $instructor_id ]->courses_link = add_query_arg( 'instructor_id', $instructor_id, $url );
		}

		return $this->items;
	}

	public function count_courses( $instructor_id, $refresh = false ) {
		$count = get_user_meta( $instructor_id, 'cp_instructor_course_count', true );
		if ( ! $count || $refresh ) {
			global $wpdb;

			$meta_keys = $wpdb->get_results(
				$wpdb->prepare( "
					SELECT `meta_key`
					FROM $wpdb->usermeta
					WHERE `meta_key` LIKE 'course_%%' AND `user_id`=%d",
					$instructor_id
				),
				ARRAY_A
			);

			if ( $meta_keys ) {
				$meta_keys = array_map(
					array( $this, 'meta_key' ),
					$meta_keys
				);

				$course_ids = array_map(
					array( $this, 'filter_course_meta_array' ),
					$meta_keys
				);
				$course_ids = array_filter( $course_ids );
				$count = count( $course_ids );

				// Save counted courses.
				update_user_meta( $instructor_id, 'cp_instructor_course_count', $count );
			}
		}

		return $count;
	}

	/**
	 * Callback for array_filter() that will return the meta-key if it
	 * indicates an instructor-course-link.
	 *
	 * So this function only returns values if the associated user is an
	 * instructor.
	 *
	 * @since  2.0.0
	 */
	public function filter_course_meta_array( $meta_key ) {
		global $wpdb;
		$regex = array();
		$regex[] = 'course_\d+';
		$regex[] = $wpdb->prefix . 'course_\d+';
		if ( is_multisite() && defined( 'BLOG_ID_CURRENT_SITE' ) && BLOG_ID_CURRENT_SITE == get_current_blog_id() ) {
			$regex[] = $wpdb->base_prefix . 'course_\d+';
		}
		$pattern = sprintf( '/^(%s)$/', implode( '|', $regex ) );
		if ( preg_match( $pattern, $meta_key ) ) {
			return $meta_key;
		}
		return false;
	}

	/**
	 * Get instructors by course ID
	 */
	public function get_instructors_by_course_id( $course_id ) {
		$args = array(
			'meta_key' => 'course_'.$course_id,
			'fields' => 'ID',
		);
		$user_query = new WP_User_Query( $args );
		return $user_query->results;
	}
}
