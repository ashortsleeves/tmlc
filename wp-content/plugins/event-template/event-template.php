<?php
/**
 * Plugin Name: Event Template
 * Description: <strong>This plugin allows users to create an event with tickets from a template.</strong>.
 * Version:     1.0.0
 * Author:      Craft Iconic
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    EventTemplate
 * @since      1.0.0
 * @copyright  Copyright (c) 2020, Craft Iconic
 * @license    GPL-2.0+
 */


//  // Ensure we are in wp-admin before performing any additional actions
 if( is_admin() ) {
     add_action( 'admin_head', 'ci_event_init' );
 }

 function ci_event_template() {
   if(isset($_POST['new_event']) == '1') {

     $new_venue = array(
       'ID' => '',
       'post_type' => 'tribe_venue',
       'post_status' => 'publish',
       'post_title'  => $_POST['post_venue'],
       'meta_input' => array(

       ),
     );
     $venue_id = wp_insert_post($new_venue);

     $new_event = array(
         'ID' => '',
         'post_type'   => 'tribe_events', // Custom Post Type Slug
         'post_status' => 'draft',
         'post_title'  => $_POST['post_title'],
         'meta_input' => array(
             '_EventVenueID' => $venue_id,
             '_EventStartDate' => date('Y-m-d H:i:s', strtotime($_POST['start_date'])),
             '_EventEndDate' => date('Y-m-d H:i:s', strtotime($_POST['end_date'])),
         ),
       );

     $event_id = wp_insert_post($new_event);

      if(isset($_POST['small_locker'])) {
        $small_ticket = array(
          'ID' => '',
          'post_type' => 'tribe_tpp_tickets',
          'post_title'  => 'Small Locker',
          'post_status' => 'publish',
          'meta_input' => array(
              '_tribe_tpp_for_event' => $event_id,
              '_price'               => 10,
          ),
        );

       $small_id = wp_insert_post($small_ticket);
     } else {
       $small_id = null;
     };

      if(isset($_POST['medium_locker'])) {
       $medium_ticket = array(
         'ID' => '',
         'post_type' => 'tribe_tpp_tickets',
         'post_title'  => 'Medium Locker',
         'post_status' => 'publish',
         'meta_input' => array(
             '_tribe_tpp_for_event' => $event_id,
             '_price'               => 15,
         ),
       );

      $medium_id = wp_insert_post($medium_ticket);
    } else {
      $medium_id = null;
    };

      if(isset($_POST['large_locker'])) {
       $large_ticket = array(
         'ID' => '',
         'post_type' => 'tribe_tpp_tickets',
         'post_title'  => 'Large Locker',
         'post_status' => 'publish',
         'meta_input' => array(
             '_tribe_tpp_for_event' => $event_id,
             '_price'               => 20,
         ),
       );

      $large_id = wp_insert_post($large_ticket);
    } else {
      $large_id = null;
    };

     $post = get_posts($event_id, $small_id, $medium_id, $large_id);
   }
 }

 function ci_event_init($post) {
   // Ensure the TEC plugin exists
   if( class_exists('TribeEventsAPI')) {
     ci_event_template();

     echo '<style>
     form.ci-event-template input {
       margin: 5px;
     }
     </style>';
     ?>
     <script>
     // Adds button to the events list page
     jQuery(function(){
         jQuery("body.forms_page_gf_entries #post-body-content").append('<form class="ci-event-template" method="post" action=""><input name="post_title" type="text" value="" placeholder="Event Name" /><br /><input name="post_venue" type="text" value="" placeholder="Location" /><br /><input name="start_date" type="text" value="" placeholder="Start Date" /><input name="end_date" type="text" value="" placeholder="End Date" /><br /><input name="small_locker" type="checkbox" id="small_locker" value=""/><label for="small_locker">Small Locker</label><br /><input name="medium_locker" type="checkbox" id="medium_locker" value=""/><label for="medium_locker">Medium Locker</label><br /><input name="large_locker" type="checkbox" id="Large_locker" value=""/><label for="large_locker">large Locker</label><br /><input type="hidden" name="new_event" value="" /><input type="submit" name="submit" value="Create Event" /></form>');
     });
     </script>
     <?php
   }
 }
