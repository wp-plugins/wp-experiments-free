<?php
/*
	Plugin Name: Title Experiments Free
	Plugin URI: http://wpexperiments.com
	Description: A/B test the titles of your pages and posts to get the most page views. More info: http://wpexperiments.com
	Author: Jason Funk
	Author URI: http://jasonfunk.net
	Version: 2.7
	License: GPLv3
*/

global $wpex_db_version;
$wpex_db_version = "0.0.1";

include('user-agents.php');
include('wpex.class.php');
new WPEx();
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
	
	$stats_table_name = $wpdb->prefix . "wpex_stats";
	$sql = "CREATE TABLE $stats_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		ts int NOT NULL,
		post_id int NOT NULL,
		title_id int NOT NULL,
		impressions  int unsigned default 0,
		clicks  int unsigned default 0,
		UNIQUE KEY id (id)
	);";
	dbDelta( $sql );

	add_option( "wpex_db_version", $wpex_db_version );
}

?>
