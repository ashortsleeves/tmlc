<?php
/**
 * Plugin Name: Basics Functionality
 * Description: <strong>This plugin should always be activated</strong>.
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
 * @package    BasicsFunctionality
 * @since      1.0.0
 * @copyright  Copyright (c) 2020, Craft Iconic
 * @license    GPL-2.0+
 */

add_action( 'plugins_loaded', function() {

    /**
    * Remove the updates menu from admin
    */
    add_action( 'admin_init', function () {
       remove_submenu_page( 'index.php', 'update-core.php' );
    } );

    /**
    * Remove frontend the admin bar for logged in users
    */
    add_filter('show_admin_bar', '__return_false');

    /**
    * Add ACF Options Page
    */
    if( function_exists('acf_add_options_page') ) {
    	acf_add_options_page();
    }

    /**
    * ACF Relative URLS
    */
    add_filter('acf/update_value/type=link', function ( $value, $post_id, $field  ) {

       $url = $value['url'];
       $siteurl = get_site_url();

       if( strpos($url, $siteurl) !== false ) {
         $value['url'] = wp_make_link_relative($url);
       }

       return $value;

    }, 10, 3);


    /**
    * Add hero image if a page has a featured image
    * Note: Customize as needed
    */
    add_action( 'wp_head', function () {
       global $post;

       wp_reset_postdata();

       if(is_archive() || is_category() || is_home() && !is_front_page()) { //checks if this is an archive or post page
           $page_id = get_option( 'page_for_posts' );
       } elseif(is_404() || is_search()) {
           $page_id = get_option( 'page_on_front' );
       } else {
           $page_id = $post->ID;
       }
       if (has_post_thumbnail($page_id)) { // if a thumbnail has been set
           $thumb = get_the_post_thumbnail_url($post->ID, 'thumbnail');
           $medium = get_the_post_thumbnail_url($post->ID, 'medium');
           $large = get_the_post_thumbnail_url($post->ID, 'large');
           $full = get_the_post_thumbnail_url($post->ID, 'full');

           $custom_css = "
           .responsive-hero {
             background-image: url({$large});
           }
           @media screen and (min-device-width: 1200px) {
              .responsive-hero {
                background-image: url({$full});
              }
           }";
           echo '<style type="text/css">' . $custom_css . '</style>';
       }
    }, 100 );

     /**
     * Move Yoast to bottom
     */
    add_filter( 'wpseo_metabox_prio', function () {
       return 'low';
    } );

     /**
     * Close yoast metabox by default
     */
    add_action( 'admin_enqueue_scripts', function () { ?>
       <script type="text/javascript">
           window.onload = function() {
               var yoastmeta = document.getElementById('wpseo_meta');
               if(yoastmeta) {
                   yoastmeta.className += ' closed';
               }
           }
       </script>
    <?php } );

    /**
    * Add company logo to admin login page
    */
    add_action( 'login_enqueue_scripts', function () {
      if( get_field( 'site_logo', 'option' )) : ?>
        <style type="text/css">
            #login h1 a, .login h1 a {
                background-image: url(<?= get_field('site_logo', 'option'); ?>);
                height: 95px;
                width: 320px;
                background-size: contain;
                background-repeat: no-repeat;
                padding-bottom: 30px;
            }
        </style>
    <?php endif;
    } );

    /**
    * Makes image urls inserted into post will have relative url instead of full
    */
    add_filter('image_send_to_editor', function ($html, $id, $caption, $title, $align, $url, $size, $alt) {
        $imageurl = wp_get_attachment_image_src($id, $size);
        $relativeurl = wp_make_link_relative($imageurl[0]);
        $html = str_replace($imageurl[0],$relativeurl,$html);

        return $html;
    }, 10, 8);

     /**
      * Add merge tag for Postmark sender in Gravity Forms
      */
     if( class_exists( 'GFForms' ) && class_exists( 'Postmark_Mail' ) ) {
         /**
          * Create Postmark sender merge tag
          */

         add_action( 'gform_admin_pre_render', function ( $form ) {
             ?>
             <script type="text/javascript">
                 gform.addFilter('gform_merge_tags', 'add_merge_tags');
                 function add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option){
                     mergeTags["custom"].tags.push({ tag: '{postmark_sender}', label: 'Postmark Sender' });
                     return mergeTags;
                 }
             </script>
             <?php
             //return the form object from the php hook
             return $form;
         } );

         /**
          * Replace Postmark sender merge tag with postmark sender setting value
          */

         add_filter( 'gform_notification', function ( $notification, $form, $entry ) {
             if(($pm_settings = get_option('postmark_settings', false)) !== false) {
                 $pm_settings = json_decode($pm_settings);
                 $pm_settings->sender_address = strtolower(trim($pm_settings->sender_address));
                 if($pm_settings->enabled && !empty($pm_settings->sender_address)) {
                     if($notification['from'] == '{postmark_sender}') {
                         $notification['from'] = $pm_settings->sender_address;
                     }
                 }
             }
             return $notification;
         }, 10, 3 );
     }

     /**
     * Prevents Sage plugin Gravity Forms conflict
     */
     add_filter( 'gform_init_scripts_footer', '__return_true' );

     /**
     * Hide editor on the home page
     */
    add_action( 'admin_init', function () {
        // Get the Post ID.
        if( isset($_GET['post']) ) {
            $post_id = $_GET['post'];
        } elseif( isset($_POST['post_ID']) ) {
            $post_id = $_POST['post_ID'];
        } else {
            return;
        }

        // Hide the editor on the page titled 'Homepage'
        $homepgname = get_the_title($post_id);
        if($homepgname == 'Home'){
            remove_post_type_support('page', 'editor');
        }

    } );

    /**
     * Extend WordPress search to include custom fields
     *
     * https://adambalee.com
     */

    /**
     * Join posts and postmeta tables
     *
     * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
     */
    // add_filter('posts_join', function ( $join ) {
    //     global $wpdb;
    //
    //     if ( is_search() ) {
    //         $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
    //     }
    //
    //     return $join;
    // } );
    //
    // /**
    //  * Modify the search query with posts_where
    //  *
    //  * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
    //  */
    // add_filter( 'posts_where', function ( $where ) {
    //     global $pagenow, $wpdb;
    //
    //     if ( is_search() ) {
    //         $where = preg_replace(
    //             "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
    //             "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
    //     }
    //
    //     return $where;
    // } );
    //
    // /**
    //  * Prevent duplicates
    //  *
    //  * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
    //  */
    // add_filter( 'posts_distinct', function ( $where ) {
    //     global $wpdb;
    //
    //     if ( is_search() ) {
    //         return "DISTINCT";
    //     }
    //
    //     return $where;
    // } );

} ); // END PLUGINS_LOADED
