<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Audit;

use WP_Defender\Traits\User;

class Options_Audit extends Audit_Event {
	use User;

	const CONTEXT_SETTINGS = 'ct_setting';

	public function get_hooks() {
		return array(
			'update_option' => array(
				'args'        => array( 'option', 'old_value', 'value' ),
				'callback'    => array( self::class, 'process_options' ),
				'event_type'  => 'settings',
				'action_type' => self::ACTION_UPDATED,
			),
			/*'update_site_option' => array(
				'args'        => array( 'option', 'old_value', 'value' ),
				'callback'    => array( 'WD_Options_Audit', 'process_network_options' ),
				'level'       => self::LOG_LEVEL_ERROR,
				'event_type'  => 'settings',
				'action_type' => self::ACTION_UPDATED,
			)*/
		);
	}

	/**
	 * @return bool|string
	 */
	public function process_options() {
		$args              = func_get_args();
		$option            = $args[1]['option'];
		$old               = $args[1]['old_value'];
		$new               = $args[1]['value'];
		$option_human_read = self::key_to_human_name( $option );

		//to avoid the recursing compare if both are nested array, convert all to string
		$check1 = is_array( $old ) ? serialize( $old ) : $old;
		$check2 = is_array( $new ) ? serialize( $new ) : $new;

		if ( $check1 == $check2 ) {
			return false;
		}
		if ( $option_human_read !== false ) {
			//we will need special case for reader
			switch ( $option ) {
				case 'users_can_register':
					if ( $new == 0 ) {
						$text = sprintf( esc_html__( "%s disabled site registration", 'wpdef' ), $this->get_user_display( get_current_user_id() ) );
					} else {
						$text = sprintf( esc_html__( "%s opened site registration", 'wpdef' ), $this->get_user_display( get_current_user_id() ) );
					}
					break;
				case 'start_of_week':
					global $wp_locale;
					$old_day = $wp_locale->get_weekday( $old );
					$new_day = $wp_locale->get_weekday( $new );
					$text    = sprintf( esc_html__( "%s update option %s from %s to %s", 'wpdef' ),
						$this->get_user_display( get_current_user_id() ), $option_human_read, $old_day, $new_day );
					break;
				case 'WPLANG':
					//no old value here
					$text = sprintf( esc_html__( "%s update option %s to %s", 'wpdef' ),
						$this->get_user_display( get_current_user_id() ), $option_human_read, $old, $new );
					break;
				default:
					$text = sprintf( esc_html__( "%s update option %s from %s to %s", 'wpdef' ),
						$this->get_user_display( get_current_user_id() ), $option_human_read, $old, $new );
					break;
			}

			return array( $text, self::CONTEXT_SETTINGS );
		}

		return false;
	}

	private static function key_to_human_name( $key ) {
		$human_read = apply_filters( 'wd_audit_settings_keys', array(
			'blogname'                      => esc_html__( "Site Title", 'wpdef' ),
			'blogdescription'               => esc_html__( "Tagline", 'wpdef' ),
			'gmt_offset'                    => esc_html__( "Timezone", 'wpdef' ),
			'date_format'                   => esc_html__( "Date Format", 'wpdef' ),
			'time_format'                   => esc_html__( "Time Format", 'wpdef' ),
			'start_of_week'                 => esc_html__( "Week Starts On", 'wpdef' ),
			'timezone_string'               => esc_html__( "Timezone", 'wpdef' ),
			'WPLANG'                        => esc_html__( "Site Language", 'wpdef' ),
			'siteurl'                       => esc_html__( "WordPress Address (URL)", 'wpdef' ),
			'home'                          => esc_html__( "Site Address (URL)", 'wpdef' ),
			'admin_email'                   => esc_html__( "Email Address", 'wpdef' ),
			'users_can_register'            => esc_html__( "Membership", 'wpdef' ),
			'default_role'                  => esc_html__( "New User Default Role", 'wpdef' ),
			'default_pingback_flag'         => esc_html__( "Default article settings", 'wpdef' ),
			'default_ping_status'           => esc_html__( "Default article settings", 'wpdef' ),
			'default_comment_status'        => esc_html__( "Default article settings", 'wpdef' ),
			'comments_notify'               => esc_html__( "Email me whenever", 'wpdef' ),
			'moderation_notify'             => esc_html__( "Email me whenever", 'wpdef' ),
			'comment_moderation'            => esc_html__( "Before a comment appears", 'wpdef' ),
			'require_name_email'            => esc_html__( "Other comment settings", 'wpdef' ),
			'comment_whitelist'             => esc_html__( "Before a comment appears", 'wpdef' ),
			'comment_max_links'             => esc_html__( "Comment Moderation", 'wpdef' ),
			'moderation_keys'               => esc_html__( "Comment Moderation", 'wpdef' ),
			'blacklist_keys'                => esc_html__( "Comment Blocklist", 'wpdef' ),
			'show_avatars'                  => esc_html__( "Avatar Display", 'wpdef' ),
			'avatar_rating'                 => esc_html__( "Maximum Rating", 'wpdef' ),
			'avatar_default'                => esc_html__( "Default Avatar", 'wpdef' ),
			'close_comments_for_old_posts'  => esc_html__( "Other comment settings", 'wpdef' ),
			'close_comments_days_old'       => esc_html__( "Other comment settings", 'wpdef' ),
			'thread_comments'               => esc_html__( "Other comment settings", 'wpdef' ),
			'thread_comments_depth'         => esc_html__( "Other comment settings", 'wpdef' ),
			'page_comments'                 => esc_html__( "Other comment settings", 'wpdef' ),
			'comments_per_page'             => esc_html__( "Other comment settings", 'wpdef' ),
			'default_comments_page'         => esc_html__( "Other comment settings", 'wpdef' ),
			'comment_order'                 => esc_html__( "Other comment settings", 'wpdef' ),
			'comment_registration'          => esc_html__( "Other comment settings", 'wpdef' ),
			'thumbnail_size_w'              => esc_html__( "Thumbnail size", 'wpdef' ),
			'thumbnail_size_h'              => esc_html__( "Thumbnail size", 'wpdef' ),
			'thumbnail_crop'                => esc_html__( "Thumbnail size", 'wpdef' ),
			'medium_size_w'                 => esc_html__( "Medium size", 'wpdef' ),
			'medium_size_h'                 => esc_html__( "Medium size", 'wpdef' ),
			'medium_large_size_w'           => esc_html__( "Medium size", 'wpdef' ),
			'medium_large_size_h'           => esc_html__( "Medium size", 'wpdef' ),
			'large_size_w'                  => esc_html__( "Large size", 'wpdef' ),
			'large_size_h'                  => esc_html__( "Large size", 'wpdef' ),
			'image_default_size'            => esc_html__( "", 'wpdef' ),
			'image_default_align'           => esc_html__( "", 'wpdef' ),
			'image_default_link_type'       => esc_html__( "", 'wpdef' ),
			'uploads_use_yearmonth_folders' => esc_html__( "Uploading Files", 'wpdef' ),
			'posts_per_page'                => esc_html__( "Blog pages show at most", 'wpdef' ),
			'posts_per_rss'                 => esc_html__( "Syndication feeds show the most recent", 'wpdef' ),
			'rss_use_excerpt'               => esc_html__( "For each article in a feed, show", 'wpdef' ),
			'show_on_front'                 => esc_html__( "Front page displays", 'wpdef' ),
			'page_on_front'                 => esc_html__( "Front page", 'wpdef' ),
			'page_for_posts'                => esc_html__( "Posts page", 'wpdef' ),
			'blog_public'                   => esc_html__( "Search Engine Visibility", 'wpdef' ),
			'default_category'              => esc_html__( "Default Post Category", 'wpdef' ),
			'default_email_category'        => esc_html__( "Default Mail Category", 'wpdef' ),
			'default_link_category'         => esc_html__( "", 'wpdef' ),
			'default_post_format'           => esc_html__( "Default Post Format", 'wpdef' ),
			'mailserver_url'                => esc_html__( "Mail Server", 'wpdef' ),
			'mailserver_port'               => esc_html__( "Port", 'wpdef' ),
			'mailserver_login'              => esc_html__( "Login Name", 'wpdef' ),
			'mailserver_pass'               => esc_html__( "Password", 'wpdef' ),
			'ping_sites'                    => esc_html__( "", 'wpdef' ),
			'permalink_structure'           => esc_html__( "Permalink Setting", 'wpdef' ),
			'category_base'                 => esc_html__( "Category base", 'wpdef' ),
			'tag_base'                      => esc_html__( "Tag base", 'wpdef' ),
			'registrationnotification'      => esc_html__( "Registration notification", 'wpdef' ),
			'registration'                  => esc_html__( "Allow new registrations", 'wpdef' ),
			'add_new_users'                 => esc_html__( "Add New Users", 'wpdef' ),
			'menu_items'                    => esc_html__( "Enable administration menus", 'wpdef' ),
			'upload_space_check_disabled'   => esc_html__( "Site upload space", 'wpdef' ),
			'blog_upload_space'             => esc_html__( "Site upload space", 'wpdef' ),
			'upload_filetypes'              => esc_html__( "Upload file types", 'wpdef' ),
			'site_name'                     => esc_html__( "Network Title", 'wpdef' ),
			'first_post'                    => esc_html__( "First Post", 'wpdef' ),
			'first_page'                    => esc_html__( "First Page", 'wpdef' ),
			'first_comment'                 => esc_html__( "First Comment", 'wpdef' ),
			'first_comment_url'             => esc_html__( "First Comment URL", 'wpdef' ),
			'first_comment_author'          => esc_html__( "First Comment Author", 'wpdef' ),
			'welcome_email'                 => esc_html__( "Welcome Email", 'wpdef' ),
			'welcome_user_email'            => esc_html__( "Welcome User Email", 'wpdef' ),
			'fileupload_maxk'               => esc_html__( "Max upload file size", 'wpdef' ),
			//'global_terms_enabled'          => esc_html__( "", 'wpdef' ),
			'illegal_names'                 => esc_html__( "Banned Names", 'wpdef' ),
			'limited_email_domains'         => esc_html__( "Limited Email Registrations", 'wpdef' ),
			'banned_email_domains'          => esc_html__( "Banned Email Domains", 'wpdef' ),
		) );

		if ( isset( $human_read[ $key ] ) ) {
			if ( empty( $human_read[ $key ] ) ) {
				return $key;
			}

			return $human_read[ $key ];
		}

		return false;
	}

	public function dictionary() {
		return array(
			self::CONTEXT_SETTINGS => esc_html__( "Settings", 'wpdef' )
		);
	}
}