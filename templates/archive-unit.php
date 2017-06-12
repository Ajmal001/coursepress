<?php
/**
 * The template use for course's units archive.
 *
 * @since 3.0
 * @package CoursePress
 */
$course = coursepress_get_course();
get_header(); ?>

    <div class="coursepress-wrap">
        <div class="container">
            <div class="content-area">
                <header class="page-header">
                    <h1 class="page-title"><?php echo coursepress_get_course_title(); ?></h1>
                </header>

                <?php
                /**
                 * To override course submenu template to your theme or a child-theme,
                 * create a template `course-submenu.php` and it will be loaded instead.
                 *
                 * @since 3.0
                 */
                 coursepress_get_template( 'course', 'submenu' );
                ?>

                <div class="course-structure">
                    <?php echo coursepress_get_course_structure( false, true ); ?>
                </div>
            </div>
        </div>
    </div>
<?php get_footer();
