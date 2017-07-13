<?php
/**
 * @var $columns
 * @var $hidden_columns
 * @var $courses
 * @var $edit_link
 * @var $course CoursePress_Course
 */
?>
<div class="wrap coursepress-wrap coursepress-forums" id="coursepress-forums">
    <h1 class="wp-heading-inline">
        <?php _e( 'Forums', 'cp' ); ?>
        <a href="<?php echo $edit_link; ?>" class="cp-btn cp-bordered-btn"><?php _e( 'Create New', 'cp' ); ?></a>
    </h1>
    <div class="coursepress-page">
        <form method="get" class="cp-search-form" id="cp-search-form">
            <div class="cp-flex">
                <div class="cp-div">
                    <label class="label"><?php _e( 'Filter by course', 'cp' ); ?></label>
                    <select name="course_id" id="select_course_id">
                        <option value=""><?php _e( 'Any course', 'cp' ); ?></option>
<?php
$current = isset( $_REQUEST['course_id'] )? $_REQUEST['course_id']:0;
foreach ( $courses as $course_id => $course ) {
	printf(
		'<option value="%d" %s>%s</option>',
		esc_attr( $course_id ),
		selected( $current, $course_id ),
		esc_html( $course->post_title )
	);
}
	?>
                    </select>
                </div>
            </div>
            <input type="hidden" name="page" value="<?php esc_attr_e( $page ); ?>" />
        </form>

        <table class="coursepress-table" cellspacing="0">
            <thead>
                <tr>
                    <?php foreach ( $columns as $column_id => $column_label ) { ?>
                        <th class="manage-column column-<?php echo $column_id; echo in_array( $column_id, $hidden_columns ) ? ' hidden': ''; ?>" id="<?php echo $column_id; ?>">
<?php
if ( 'comments' == $column_id ) {
	echo '<i class="fa fa-comments" aria-hidden="true"></i>';
} else {
	echo $column_label;
}
?>
                        </th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
<?php
$i = 0;
if ( ! empty( $forums ) ) {
	$date_format = get_option( 'date_format' );
	foreach ( $forums as $forum ) {
			$i++;
?>
                        <tr class="<?php echo $i % 2? 'odd' : 'even'; ?>">

                            <?php foreach ( array_keys( $columns ) as $column_id ) { ?>
                                <td class="column-<?php echo $column_id; echo in_array( $column_id, $hidden_columns ) ? ' hidden': ''; ?>">
<?php
switch ( $column_id ) {
	case 'topic' :
		echo $forum->post_title;
	break;
	case 'course' :
		if ( isset( $courses[ $forum->course_id ] ) ) {
			echo $courses[ $forum->course_id ]->post_title;
		}
	break;
	case 'comments':
		echo $forum->comments_number;
	break;
	case 'status':
		echo '<label>';
		$active = isset( $forum->post_status ) && 'publish' === $forum->post_status;
		printf(
			'<input type="checkbox" class="cp-toggle-input cp-toggle-forum-status" value="%d" %s /> <span class="cp-toggle-btn"></span>',
			esc_attr( $forum->ID ),
			checked( $active, true, false )
		);
	break;

	default :
		echo $column_id;
		/**
				 * Trigger to allow custom column value
				 *
				 * @since 3.0
				 * @param string $column_id
				 * @param CoursePress_Course object $forum
				 */
		do_action( 'coursepress_forums_column', $column_id, $forum );
	break;
}
?>
                                </td>
                            <?php } ?>
                        </tr>
<?php
	}
} else {
?>
                    <tr class="odd">
                        <td>
                            <?php _e( 'No forums found.', 'cp' ); ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php if ( ! empty( $pagination ) ) : ?>
            <div class="tablenav cp-admin-pagination">
                <?php $pagination->pagination( 'bottom' ); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php

