<?php
/**
 * Single Event Template
 * A single event. This displays the event title, description, meta, and
 * optionally, the Google map for the event.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/single-event.php
 *
 * @package TribeEventsCalendar
 * @version 4.6.19
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$events_label_singular = tribe_get_event_label_singular();
$events_label_plural   = tribe_get_event_label_plural();

$organizer_id = get_the_ID();
$organizer = tribe_get_organizer();
$organizer_ids = tribe_get_organizer_ids();
$multiple = count( $organizer_ids ) > 1;

$event_id = get_the_ID();

$additional_fields = tribe_get_custom_fields();
$posttags = get_the_tags();
$event_location = tribe_get_venue();

$website = tribe_get_venue_website_link();

if ( ! tribe_get_venue_id() ) {
	return;
}

?>

<div id="tribe-events-content" class="tribe-events-single">
	<p class="tribe-events-back">
		<a href="/conference-schedule/">&larr; Conference Schedule</a>

		<a href="/nwmaf-events/">All Events &rarr;</a>
	</p>

	<!-- Notices -->
	<?php tribe_the_notices() ?>

	<?php the_title( '<h1 class="tribe-events-single-event-title">', '</h1>' ); ?>

	<div class="tribe-events-schedule tribe-clearfix">
		<?php echo tribe_events_event_schedule_details( $event_id, '<h2>', '</h2>' ); ?>
		<?php if ( tribe_get_cost() ) : ?>
			<span class="tribe-events-cost"><?php echo tribe_get_cost( null, true ) ?></span>
		<?php endif; ?>
	</div>

	<!-- Event header -->
	<div id="tribe-events-header" <?php tribe_events_the_header_attributes() ?>>
		<!-- Navigation -->
		<nav class="tribe-events-nav-pagination" aria-label="<?php printf( esc_html__( '%s Navigation', 'the-events-calendar' ), $events_label_singular ); ?>">
			<ul class="tribe-events-sub-nav">
				<li class="tribe-events-nav-previous"><?php tribe_the_prev_event_link( '<span>&laquo;</span> %title%' ) ?></li>
				<li class="tribe-events-nav-next"><?php tribe_the_next_event_link( '%title% <span>&raquo;</span>' ) ?></li>
			</ul>
			<!-- .tribe-events-sub-nav -->
		</nav>
	</div>
	<!-- #tribe-events-header -->

	<?php while ( have_posts() ) :  the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<!-- Event featured image, but exclude link -->
			<?php echo tribe_event_featured_image( $event_id, 'full', false ); ?>

			<!-- Event content -->
			<?php do_action( 'tribe_events_single_event_before_the_content' ) ?>
			<div class="tribe-events-single-event-description tribe-events-content">
				<div class="class-content-container">
				<?php the_content(); ?>
			</div>
			<section class="event-details">
				<dl class="class-instructor">
					<dt>Instructor(s):</dt>
					<!-- for each instructor, get name and link -->
					<?php
						do_action( 'tribe_events_single_meta_organizer_section_start' );

						foreach ( $organizer_ids as $organizer ) {
							if ( ! $organizer ) {
								continue;
							}

							?>
							<dd>
                                <ul class="instructor-list">
                                    <li class="instructor"><?php echo tribe_get_organizer_link( $organizer )?></li>
                                </ul>
                            </dd>
							<?php
						}

					if ( ! $multiple ) { // only show organizer details if there is one

					}//end if

					do_action( 'tribe_events_single_meta_organizer_section_end' );
					?>
				</dl>

				<?php if ($additional_fields) { ?>
				<dl class="class-categories">
					<dt>Appropriate For:</dt><dd>
                        <ul class="categories-list">
                            <li class="category"><?php echo $additional_fields['Appropriate For (select all that apply)']; ?></li>
                            <li class="category"><?php echo $additional_fields['Recommended Experience Level (select all that apply)']; ?></li>
                            <li class="category"><?php echo $additional_fields['Exertion Level']; ?></li>
						</ul>
					</dd>
				</dl>
				<?php } ?>

				<?php if ($posttags) { ?>
				<dl class="class-topics">
					<dt>Areas of Focus:</dt>
					<dd>
						<ul class="topics-list">
						<?php	foreach($posttags as $tag) { ?>
							<li class="topic"><?php echo $tag->name; ?></li>
						<?php	}
						} ?>
						</ul>
					</dd>
				</dl>

				<?php if ( $event_location ) : ?>
				<dl class="class-location">
					<dt>Location: </dt>

					<dd><?php echo $event_location ?><?php echo $website ?></dd>
				</dl>
				<?php endif; ?>
			</section>
			<!-- .tribe-events-single-event-description -->
			<?php do_action( 'tribe_events_single_event_after_the_content' ) ?>

			<!-- Event meta -->
			<?php do_action( 'tribe_events_single_event_before_the_meta' ) ?>
			<?php tribe_get_template_part( 'modules/meta' ); ?>
			<?php do_action( 'tribe_events_single_event_after_the_meta' ) ?>
		</div> <!-- #post-x -->
		<?php if ( get_post_type() == Tribe__Events__Main::POSTTYPE && tribe_get_option( 'showComments', false ) ) comments_template() ?>
	<?php endwhile; ?>

	<!-- Event footer -->
	<div id="tribe-events-footer">
		<!-- Navigation -->
		<nav class="tribe-events-nav-pagination" aria-label="<?php printf( esc_html__( '%s Navigation', 'the-events-calendar' ), $events_label_singular ); ?>">
			<ul class="tribe-events-sub-nav">
				<li class="tribe-events-nav-previous"><?php tribe_the_prev_event_link( '<span>&laquo;</span> %title%' ) ?></li>
				<li class="tribe-events-nav-next"><?php tribe_the_next_event_link( '%title% <span>&raquo;</span>' ) ?></li>
			</ul>
			<!-- .tribe-events-sub-nav -->
		</nav>
	</div>
	<!-- #tribe-events-footer -->

</div><!-- #tribe-events-content -->
