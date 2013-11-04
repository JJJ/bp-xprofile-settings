<?php

/**
 * Plugin Name: BuddyPress XProfile Settings
 * Plugin URI:  http://jjj.me
 * Description: XProfile Visibility Settings
 * Author:      John James Jacoby
 * Author URI:  http://jjj.me
 * Version:     1.0
 * Text Domain: buddypress
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * BuddyPress XProfile Settings Loader
 *
 * @package BuddyPress
 * @subpackage SettingsLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Setup BuddyBar navigation
 *
 * @since BuddyPress (1.9)
 */
function bp_xprofile_settings_setup_nav() {

	// Determine user to use
	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_domain = bp_loggedin_user_domain();
	} else {
		return;
	}

	// Add General Settings nav item
	bp_core_new_subnav_item( array(
		'name'            => __( 'Profile', 'buddypress' ),
		'slug'            => 'profile',
		'parent_url'      => trailingslashit( $user_domain . bp_get_settings_slug() ),
		'parent_slug'     => bp_get_settings_slug(),
		'screen_function' => 'bp_settings_screen_xprofile',
		'position'        => 25,
		'user_has_access' => bp_core_can_edit_settings()
	) );
}

/**
 * Set up the Toolbar
 *
 * @since BuddyPress (1.9)
 */
function bp_xprofile_settings_setup_admin_bar() {

	// Bail if this is an ajax request
	if ( defined( 'DOING_AJAX' ) ) {
		return;
	}

	// Menus for logged in user
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Do not proceed if BP_USE_WP_ADMIN_BAR constant is not set or is false
	if ( ! bp_use_wp_admin_bar() ) {
		return;
	}

	// Define the WordPress global
	global $wp_admin_bar;

	// Setup the logged in user variables
	$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() . '/' );

	// Add main Settings menu
	$wp_admin_bar->add_menu( array(
		'parent' => 'my-account-settings',
		'id'     => 'my-account-settings-xprofile',
		'title'  => __( 'Profile', 'buddypress' ),
		'href'   => trailingslashit( $settings_link . 'profile' )
	) );
}

/**
 * Get it all going
 *
 * @since BuddyPress (1.9)
 */
function bp_setup_xprofile_settings() {
	// Bail if no settings or no xprofile
	if ( ! bp_is_active( 'xprofile' ) || ! bp_is_active( 'settings' ) ) {
		return;
	}

	add_action( 'bp_setup_nav',       'bp_xprofile_settings_setup_nav',       11  );
	add_action( 'bp_setup_admin_bar', 'bp_xprofile_settings_setup_admin_bar', 105 );
	add_action( 'bp_actions',         'bp_settings_action_xprofile'               );
}
add_action( 'bp_setup_components', 'bp_setup_xprofile_settings', 10 );


/** Action ********************************************************************/

/**
 * Handles the saving of xprofile field visibilities
 *
 * @since BuddyPress (1.9)
 */
function bp_settings_action_xprofile() {

	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	// Bail if no submit action
	if ( ! isset( $_POST['xprofile-settings-submit'] ) )
		return;

	// Bail if not in settings
	if ( ! bp_is_settings_component() || ! bp_is_current_action( 'profile' ) ) {
		return false;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Nonce check
	check_admin_referer( 'bp_xprofile_settings' );

	do_action( 'bp_settings_xprofile_before_save' );

	/** Save ******************************************************************/

	// Only save if there are field ID's being posted
	if ( ! empty( $_POST['field_ids'] ) ) {

		// Get the POST'ed field ID's
		$posted_field_ids = explode( ',', $_POST['field_ids'] );

		// Save the visibility settings
		foreach ( $posted_field_ids as $field_id ) {
			$visibility_level = !empty( $_POST['field_' . $field_id . '_visibility'] ) ? $_POST['field_' . $field_id . '_visibility'] : 'public';
			xprofile_set_field_visibility_level( $field_id, bp_displayed_user_id(), $visibility_level );
		}
	}

	/** Other *****************************************************************/

	do_action( 'bp_settings_xprofile_after_save' );

	// Redirect to the root domain
	bp_core_redirect( bp_displayed_user_domain() . bp_get_settings_slug() . '/profile' );
}

/** Screen ********************************************************************/

/**
 * Show the xprofile settings template
 *
 * @since BuddyPress (1.9)
 */
function bp_settings_screen_xprofile() {

	if ( bp_action_variables() || ! bp_is_active( 'xprofile' ) ) {
		bp_do_404();
		return;
	}

	// Title and content
	add_action( 'bp_template_content', 'bp_xprofile_settings_content' );

	// Load the template
	bp_core_load_template( apply_filters( 'bp_settings_screen_xprofile', '/members/single/plugin' ) );
}

/**
 * Output the xprofile settings template part
 *
 * @since BuddyPress (1.9)
 */
function bp_xprofile_settings_content() {
	bp_buffer_template_part( 'members/single/settings/profile' );
}