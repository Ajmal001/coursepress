<?php

class CoursePress_Template_User {

	public static function render_instructor_page() {
		return do_shortcode( '[instructor_page]' );
	}

	public static function render_facilitator_page() {
		return do_shortcode( '[facilitator_page]' );
	}

}