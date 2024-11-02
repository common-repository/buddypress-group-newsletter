<?php
/*
Plugin Name: BuddyPress Group Newsletter
Plugin URI: http://wordpress.org/extend/plugins/buddypress-group-newsletter
Description: This plugin allows the admin or moderator to send an email to all group members. As a member of a group you can view all newsletter
Author: Sven Gak	
Version: 1.1.0.0
Author URI: http://www.sven-gak.de
Site Wide Only: false
*/

define ( 'BP_GROUP_NEWSLETTER_PLUGIN_NAME', 'buddypress-group-newsletter' );
define ( 'BP_GROUP_NEWSLETTER_PLUGIN_SLUG', 'group-newsletter' );
define ( 'BP_GROUP_NEWSLETTER_PLUGIN_SHOW', 10 );

/* Define the slug for the component */
function buddypress_group_newsletter_init() {
	require_once ( dirname( __FILE__ ) . '/includes/buddypress-group-newsletter.php' );
}
add_action( 'bp_init', 'buddypress_group_newsletter_init' );

function buddypress_group_newsletter_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->buddypress_group_newsletter->slug = $bp->buddypress_group_newsletter->id = BP_GROUP_NEWSLETTER_PLUGIN_SLUG;
	//$bp->buddypress_group_newsletter->format_notification_function = 'bp_introduce_format_notifications';
	
	do_action( 'buddypress_group_newsletter_setup_globals' );
}
add_action( 'wp', 'buddypress_group_newsletter_setup_globals' );
add_action( 'admin_menu', 'buddypress_group_newsletter_setup_globals' );
add_action( 'bp_setup_globals', 'buddypress_group_newsletter_setup_globals' );

?>