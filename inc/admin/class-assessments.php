<?php
/**
 * Class CoursePress_Admin_Assesments
 *
 * @since 2.0
 * @package CoursePress
 */
class CoursePress_Admin_Assessments extends CoursePress_Admin_Page {

	/**
	 * Assesments page slug.
	 *
	 * @var string
	 */
	protected $slug = 'coursepress_assessments';

	/**
	 * Get students listing page content and set pagination.
	 *
	 * @uses get_current_screen().
	 * @uses get_hidden_columns().
	 * @uses get_column_headers().
	 * @uses coursepress_render().
	 */
	function get_page() {

		$count = 0;
		$screen = get_current_screen();

		// Set query parameters back.
		$search = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$course_id = empty( $_GET['course_id'] ) ? 0 : $_GET['course_id'];
		$unit_id = empty( $_GET['unit_id'] ) ? 0 : $_GET['unit_id'];
		$graded = empty( $_GET['graded_ungraded'] ) ? 'all' : $_GET['graded_ungraded'];
		$graded = in_array( $graded, array( 'graded', 'ungraded' ) ) ? $graded : 'all';

		// Data for template.
		$args = array(
			'columns' => get_column_headers( $screen ),
			'assessments' => $this->get_assesments( $course_id, $unit_id, $graded, $count ),
			'courses' => coursepress_get_accessible_courses(),
			'units' => coursepress_get_course_units( $course_id ),
			'list_table' => $this->set_pagination( $count, 'coursepress_assesments_per_page' ),
			'hidden_columns' => get_hidden_columns( $screen ),
			'page' => $this->slug,
			'course_id' => absint( $course_id ),
			'unit_id' => absint( $unit_id ),
			'graded' => $graded,
			'search' => $search,
		);

		// Render templates.
		coursepress_render( 'views/admin/assessments', $args );
		coursepress_render( 'views/admin/footer-text' );
	}

	/**
	 * Get assessments data.
	 *
	 * @param int $course_id Course ID.
	 * @param int $unit_id Unit id.
	 * @param int $graded Graded or ungraded.
	 * @param int $count Total count of the students (pass by ref.).
	 *
	 * @return array
	 */
	function get_assesments( $course_id, $unit_id, $graded = 'all', &$count = 0 ) {

		// We need course id.
		if ( empty( $course_id ) ) {
			return array();
		}

		$assessments = new CoursePress_Data_Assessments( $course_id );

		return $assessments->get_assessments( $unit_id, $graded, $count );
	}

	/**
	 * Custom screen options for assesments listing page.
	 *
	 * @uses get_current_screen().
	 */
	function screen_options() {

		$screen_id = get_current_screen()->id;

		// Setup columns.
		add_filter( 'default_hidden_columns', array( $this, 'hidden_columns' ) );
		add_filter( 'manage_' . $screen_id . '_columns', array( $this, 'get_columns' ) );

		// Assesments per page.
		add_screen_option( 'per_page', array( 'default' => 20, 'option' => 'coursepress_assesments_per_page' ) );
	}

	/**
	 * Get column for the listing page.
	 *
	 * @return array
	 */
	function get_columns() {

		$columns = array(
			'student' => __( 'Student', 'cp' ),
			'last_active' => __( 'Last active', 'cp' ),
			'grade' => __( 'Grade', 'cp' ),
			'modules_progress' => __( 'Modules progress', 'cp' ),
		);

		/**
		 * Trigger to allow custom column values.
		 *
		 * @since 3.0
		 * @param array $columns
		 */
		$columns = apply_filters( 'coursepress_assesments_columns', $columns );

		return $columns;
	}

	/**
	 * Default columns to be hidden on listing page.
	 *
	 * @return array
	 */
	function hidden_columns() {

		/**
		 * Trigger to modify hidden columns.
		 *
		 * @since 3.0
		 * @param array $hidden_columns.
		 */
		return apply_filters( 'coursepress_assesments_hidden_columns', array() );
	}
}
