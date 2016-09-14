<?php

if ( ! CoursePress_Data_Capabilities::can_add_notifications() ) {
	wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
}

wp_reset_vars( array( 'action' ) );
$post_type = CoursePress_Data_Notification::get_post_type_name();
$the_id = ! empty( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;
$notification_type_object = get_post_type_object( $post_type );
$labels = get_post_type_labels( $notification_type_object );

$title = $labels->add_new_item;
$post = null;
$post_title = '';
$post_content = '';
$post_status = 'publish';

if ( 0 < $the_id ) {
	$title = $labels->edit_item;
	$post = get_post( $the_id );
	$post_title = $post->post_title;
	$post_content = $post->post_content;
}

do_action( 'add_meta_boxes', $post_type, $post );

do_action( 'do_meta_boxes', $post_type, 'side', $post );

if ( wp_is_mobile() ) {
	wp_enqueue_script( 'jquery-touch-punch' );
}

include_once ABSPATH.'/wp-admin/includes/meta-boxes.php';

wp_enqueue_script( 'post' );

// Add meta boxes
add_meta_box(
	'submitdiv',
	__( 'Save', 'cp' ),
	array( $this, 'box_submitdiv' ),
	$post_type,
	'side',
	'high'
);

/**
 * Notification box
 * /
$add_box_notify_students = apply_filters( 'coursepress_notifications_send_notify_to_students', true );
if ( $add_box_notify_students ) {
	add_meta_box(
		'notify-students',
		__( 'Notify Students', 'cp' ),
		array( $this, 'box_notify_students' ),
		$post_type,
		'side'
	);
}
 */

?>
<div class="wrap coursepress_wrapper course-edit-notification">
	<h2><?php echo $title; ?></h2>
	<hr />

	<form method="post" class="edit">
		<?php
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false, false );
		wp_nonce_field( 'edit_notification' ); ?>
		<input type="hidden" name="post_status" value="<?Php echo esc_attr( $post_status ); ?>" />
		<input type="hidden" id="post_ID" name="post_ID" value="<?php echo esc_attr( $the_id ); ?>" />
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
			<div id="post-body-content">
				<?php echo CoursePress_Helper_UI::get_admin_edit_title_field( $post_title, __( 'Notification Title', 'cp' ) ); ?>
				<br />
				<div id="postdivrich" class="postarea wp-editor-expand">
					<?php
					$editor_name = 'post_content';
					$editor_id = 'postContent';
					$args = array(
						'textarea_name' => $editor_name,
						'editor_class' => 'cp-editor',
						'textarea_rows' => 10,
						'tinymce' => array(
							'height' => 350,
						),
					);
					// Filter $args
					$args = apply_filters( 'coursepress_element_editor_args', $args, $editor_name, $editor_id );

					wp_editor( $post_content, $editor_id, $args );
					?>
				</div>
			</div>
			<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes( $post_type, 'side', $post ); ?>
			</div>
		</div>
	</form>
</div>
