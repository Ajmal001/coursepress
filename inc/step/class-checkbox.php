<?php
/**
 * Class CoursePress_Step_Checkbox
 *
 * Note*: Legacy class to handle `input-checkbox` module type.
 *
 * @since 3.0
 * @package CoursePress
 */
class CoursePress_Step_Checkbox extends CoursePress_Step_Quiz {
	protected function get_questions_data() {
		$answers = $this->__get( 'answers' );
		$selected_answers = $this->__get( 'answers_selected' );
		$checked = array();

		foreach ( $answers as $pos => $answer ) {
			$checked[ $pos ] = in_array( $pos, $selected_answers );
		}

		return array(
			array(
				'type' => 'multiple',
				'question' => '',
				'options' => array(
					'answers' => $answers,
					'checked' => $checked,
				)
			)
		);
	}
}