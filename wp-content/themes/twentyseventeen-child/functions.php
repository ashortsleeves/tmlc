<?php

// file functions.php

add_action( 'wp_enqueue_scripts', 'twentyseventeen_parent_theme_enqueue_styles' );

function twentyseventeen_parent_theme_enqueue_styles() {
    wp_enqueue_style( 'twentyseventeen-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'rest-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'twentyseventeen-style' )
    );

    // enqueue the theme script...
    wp_enqueue_script( 'rest-theme-js', get_stylesheet_directory_uri() . '/js/rest-theme.js', array( 'jquery' ) );

    // ...and localize The Events Calendar REST API information and nonce
    wp_localize_script( 'rest-theme-js', 'restTheme',
        array( 'root' => esc_url_raw( tribe_events_rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
}
