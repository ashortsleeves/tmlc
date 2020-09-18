<?php

namespace App;

/**
 * Theme customizer
 */
add_action('customize_register', function (\WP_Customize_Manager $wp_customize) {
    // Add postMessage support
    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->selective_refresh->add_partial('blogname', [
        'selector' => '.brand',
        'render_callback' => function () {
            bloginfo('name');
        }
    ]);
});

/**
 * Customizer JS
 */
add_action('customize_preview_init', function () {
    wp_enqueue_script('sage/customizer.js', asset_path('scripts/customizer.js'), ['customize-preview'], null, true);
});

  // 
  // /**
  //  * This will create a WordPress page everytime this hook runs. (i.e. When a user is added via WP Zapier plugin)
  //  * Add the below code to a custom plugin / child theme functions.php
  //  */
  //
  // add_action( 'wp_zapier_after_create_user', function( $user_id ) {
  //
  //       $post_data = array(
  //           'post_title' => 'The post title via wp_insert_post',
  //           'post_content' => 'Place all your body content for the post in this line.',
  //           'post_status' => 'publish', // Automatically publish the post.
  //           'post_author' => $user_id,
  //           'post_category' => array( 1, 3 ), // Add it two categories.
  //           'post_type' => 'page' // defaults to "post". Can be set to CPTs.
  //       );
  //
  //       // Lets insert the post now.
  //       wp_insert_post( $post_data );
  //
  //   }, 10, 1 );
