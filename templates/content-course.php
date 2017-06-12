<?php
/**
 * The template use for courses list.
 *
 * @since 3.0
 * @package CoursePress
 *
 * @var $post CoursePress_Course
 */
global $post;

$course = coursepress_get_course( $post );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'course-item-box' ); ?>>
	<div class="course-feature-image-container">
		<?php echo $course->get_feature_image( 280 ); ?>
	</div>
	<div class="course-info">
		<header class="entry-header course-entry-header">
			<?php the_title( '<h3 class="entry-title course-title"><a href="' . esc_url( $course->get_permalink() ) . '" rel="bookmark">', '</a></h3>' ); ?>
		</header>
		<div class="entry-content course-description">
			<?php echo $course->get_summary( 140 ); ?>
		</div>

		<?php if ( ( $instructors = $course->get_instructors_link() ) ) : ?>
			<div class="course-instructors">
				<strong><?php echo _n( 'Instructor', 'Instructors', count( $instructors ), 'cp' ); ?>: </strong>
				<?php echo implode( ', ', $instructors ); ?>
			</div>
		<?php endif; ?>

		<div class="course-metas">
			<span class="course-meta course-meta-start-date"><?php echo $course->get_course_start_date(); ?></span>
			<span class="course-meta course-meta-language"><?php echo $course->get_course_language(); ?></span>
			<span class="course-meta course-meta-cost"><?php echo $course->get_course_cost(); ?></span>
		</div>
	</div>
</article>