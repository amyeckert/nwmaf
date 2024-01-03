<?php
/**
 * View: List Event
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/list/event.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://evnt.is/1aiy
 *
 * @version 5.0.0
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

$container_classes = [ 'tribe-common-g-row', 'tribe-events-calendar-list__event-row' ];
$container_classes['tribe-events-calendar-list__event-row--featured'] = $event->featured;
$organizer_id = get_the_ID();
$organizer = tribe_get_organizer();
$organizer_ids = tribe_get_organizer_ids();
$multiple = count( $organizer_ids ) > 1;

$event_classes = tribe_get_post_class( [ 'tribe-events-calendar-list__event', 'tribe-common-g-row', 'tribe-common-g-row--gutters' ], $event->ID );
?>
<div <?php tribe_classes( $container_classes ); ?>>

	<?php $this->template( 'list/event/date-tag', [ 'event' => $event ] ); ?>

	<div class="tribe-events-calendar-list__event-wrapper tribe-common-g-col">
		<article <?php tribe_classes( $event_classes ) ?>>
			<?php $this->template( 'list/event/featured-image', [ 'event' => $event ] ); ?>

			<div class="tribe-events-calendar-list__event-details tribe-common-g-col">

				<header class="tribe-events-calendar-list__event-header">
					<?php $this->template( 'list/event/date', [ 'event' => $event ] ); ?>
					<?php $this->template( 'list/event/title', [ 'event' => $event ] ); ?>
					<div class="instructor mb-3">
						<span class="tribe-events-calendar-day__event-venue-title"><strong>Instructor(s): </strong></span>
						<?php
							do_action( 'tribe_events_single_meta_organizer_section_start' );

							foreach ( $organizer_ids as $organizer ) {
								if ( ! $organizer ) {
									continue;
								}

								?>
								<?php echo tribe_get_organizer_link( $organizer )?>
								<?php
							}

							if ( ! $multiple ) { // only show organizer details if there is one

							}//end if

							do_action( 'tribe_events_single_meta_organizer_section_end' );
						?>
					</div>
                    <div class="tribe-events-calendar-day__event-venue-label">
                        <span><strong>Location:&nbsp;</strong></span>
                        <?php $this->template( 'day/event/venue', [ 'event' => $event ] ); ?>
                    </div>
				</header>

				<?php $this->template( 'list/event/description', [ 'event' => $event ] ); ?>
				<?php $this->template( 'list/event/cost', [ 'event' => $event ] ); ?>

			</div>
		</article>
	</div>

</div>
