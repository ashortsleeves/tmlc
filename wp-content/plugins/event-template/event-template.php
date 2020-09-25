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

     $new_event = array(
         'ID' => '',
         'post_type'   => 'tribe_events', // Custom Post Type Slug
         'post_status' => 'draft',
         'post_title'  => $_POST['post_title'],
         'meta_input' => array(
             '_EventURL' => 'www.google.com',
         ),
       );

     $event_id = wp_insert_post($new_event);

     $new_ticket = array(
       'ID' => '',
       'post_type' => 'tribe_tpp_tickets',
       'post_title'  => 'Small Locker',
       'post_status' => 'publish',
       'meta_input' => array(
           '_tribe_tpp_for_event' => $event_id,
           '_price'               => 10,
       ),
     );


     $ticket_id = wp_insert_post($new_ticket);
     $post = get_posts($event_id, $ticket_id);
   }
 }

 function ci_event_init($post) {
   // Ensure the TEC plugin exists
   if( class_exists('TribeEventsAPI')) {
     ci_event_template();
     ?>
     <script>
     // Adds button to the events list page
     jQuery(function(){
         jQuery("body.post-type-tribe_events .wrap").prepend('<form method="post" action=""><input name="post_title" type="text" /><input type="hidden" name="new_event" value="1" /><input type="submit" name="submit" value="Create Post With Tickets" /></form>');
     });
     </script>
     <?php
   }
 }
