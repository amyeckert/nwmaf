<?php
/**
 * View: Organizer meta content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/organizer/meta/content.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 5.0.0
 *
 * @var WP_Post $organizer The organizer post object.
 *
 */

$content = tribe_get_the_content( null, false, $organizer->ID );

if ( empty( $content ) ) {
	return;
}
?>

<div class="tribe-events-pro-organizer__meta-content tribe-common-g-col tribe-common-b1 instructor__bio">
	<?php echo $content; ?>
</div>
