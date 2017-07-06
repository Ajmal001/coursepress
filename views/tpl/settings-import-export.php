<script type="text/template" id="coursepress-import-export-setting-tpl">
	<div class="cp-box-heading">
		<h2 class="box-heading-title"><?php _e( 'Import & Export', 'cp' ); ?></h2>
	</div>

    <?php
    $toggle_input = coursepress_create_html( 'span', array( 'class' => 'cp-toggle-btn' ) );
    $config = array();

    $config['import'] = array(
        'title' => __( 'Import', 'CoursePress' ),
        'description' => __( 'Upload your exported courses to import here.', 'CoursePress' ),
        'fields' => array(
            //'import' => array(
            //    'type' => 'file',
            //),
            'replace' => array(
                'type' => 'checkbox',
                'title' => $toggle_input . __( 'Replace course if exists', 'CoursePress' ),
                'desc' => __( 'Courses with the same title will be automatically replaced by the new one.', 'CoursePress' ),
            ),
            'with_students' => array(
                'type' => 'checkbox',
                'title' => $toggle_input . __( 'Include course students', 'CoursePress' ),
                'desc' => __( 'Students listing must also included in your export for this to work.', 'CoursePress' ),
            ),
            'with_comments' => array(
                'type' => 'checkbox',
                'title' => $toggle_input . __( 'Include course thread/comments', 'CoursePress' ),
                'desc' => __( 'Comments listing must also included in your export for this to work.', 'CoursePress' ),
                'disabled' => true,
            ),
            '' => array(
                'type' => 'submit',
                'value' => __( 'Upload file and import', 'CoursePress' ),
                'class' => 'cp-btn cp-btn-active',
            ),
        ),
    );
    /**
     * export
     */
    $config['export'] = array(
        'title' => __( 'Export', 'CoursePress' ),
        'description' => __( 'Select courses to export to another site.', 'CoursePress' ),
        'fields' => array(
            'coursepress[all]' => array(
                'type' => 'checkbox',
                'title' => $toggle_input . __( 'All Courses', 'CoursePress' ),
            ),
        ),
    );
    /**
     * Courses list
     */
    $list = coursepress_get_courses( array( 'posts_per_page' => -1 ) );

    foreach ( $list as $course ) {
        $course_id = $course->__get( 'ID' );
        $course_title = $course->__get( 'post_title' );
        $config['export']['fields'][ 'coursepress[courses]['.$course_id.']' ] = array(
            'type' => 'checkbox',
            'title' => $toggle_input . ( empty( $course_title )? __( '-[This course has no title]-', 'CoursePress' ):$course_title ),
        );
    }
    $config['export']['fields'] += array(
        'coursepress[export][students]' => array(
            'type' => 'checkbox',
            'title' => $toggle_input . __( 'Include course students', 'CoursePress' ),
            'desc' => __( 'Will include course students and their course submission progress.', 'CoursePress' ),
        ),
        'coursepress[export][comments]' => array(
            'type' => 'checkbox',
            'title' => $toggle_input . __( 'Include course thread/comments', 'CoursePress' ),
            'desc' => __( 'Will include course students and their course submission progress.', 'CoursePress' ),
            'disabled' => true,
        ),
        'coursepress[export][button]' => array(
            'type' => 'submit',
            'value' => __( 'Export Courses', 'CoursePress' ),
            'class' => 'cp-btn cp-btn-active',
        ),
    );

    /**
     * Fire to get all options.
     *
     * @since 3.0
     * @param array $extensions
     */
    $option_name = sprintf( 'coursepress_%s', basename( __FILE__, '.php' ) );
    $options = apply_filters( $option_name, $config );

    foreach ( $options as $option_key => $option ) {
    ?>
    <div class="cp-box-content">
        <div class="box-label-area">
            <h2 class="label"><?php echo $option['title']; ?></h2>
    <?php
    if ( isset( $option['description'] ) ) {
        printf( '<p class="description">%s</p>', $option['description'] );
    }
    ?>
        </div>
        <div class="box-inner-content">
            <form method="post" id="form-<?php echo $option_key; ?>" class="coursepress-form" enctype="multipart/form-data">
                <?php if ( 'import' == $option_key ) : ?>
                    <input type="file" name="import" />
                    <div class="cp-alert cp-alert-error"></div>
                <?php elseif ( 'export' == $option_key ) : ?>

                <?php endif; ?>
    <?php
    foreach ( $option['fields'] as $key => $data ) {
    ?>
        <div class="option option-<?php esc_attr_e( $key ); ?>">
    <?php
    if ( isset( $data['label'] ) ) {
        printf( '<h3>%s</h3>', $data['label'] );
    }
        $data['name'] = $key;
        lib3()->html->element( $data );
    ?>
        </div>
    <?php
    }
    ?>
            </form>
    </div>
    <?php
    }
    ?>
</script>
