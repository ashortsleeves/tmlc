<?php
/**
 * Component: iCal Link
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/components/ical-link.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1aiy
 *
 * @version 5.0.1
 *
 * @var object $ical Object containing iCal data
 */

if ( empty( $ical->display_link ) ) {
	return;
}

?>
