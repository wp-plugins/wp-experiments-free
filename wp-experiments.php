<?php
/*
	Plugin Name: WP Experiments Free
	Plugin URI: http://wpexperiments.com
	Description: A/B test the titles of your pages and posts to get the most page views. More info: http://wpexperiments.com
	Author: Jason Funk
	Author URI: http://jasonfunk.net
	Version: 1.2
	License: GPLv3
*/

global $wpex_db_version;
$wpex_db_version = "0.0.1";

include('wpex.class.php');

register_activation_hook( __FILE__, 'wpex_install' );

function wpex_install() {
	global $wpdb;
	global $wpex_db_version;

	$table_name = $wpdb->prefix . "wpex_titles";
	     
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id int NOT NULL,
		title text NOT NULL,
		enabled tinyint NOT NULL default 1,
		impressions  int unsigned default 0,
		clicks  int unsigned default 0,
		stats text NOT NULL,
		UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	
	add_option( "wpex_db_version", $wpex_db_version );
}

?>
