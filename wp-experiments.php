<?php
/*
	Plugin Name: Title Experiments Free
	Plugin URI: http://wpexperiments.com
	Description: A/B test the titles of your pages and posts to get the most page views. More info: http://wpexperiments.com
	Author: Jason Funk
	Author URI: http://jasonfunk.net
	Version: 2.0
	License: GPLv3
*/

global $wpex_db_version;
$wpex_db_version = "0.0.1";

include('libs/wpph.php');
$wpph = new WPPH(
	plugin_basename(__FILE__),
	"77107452-a1a9-4940-8dc3-ba79907527d0",
	array(
		'enable_license' => TRUE,
		'enable_license_purchase' => TRUE,
		'license_menu_slug' => 'plugins.php',
		'license_menu_title' => 'Title Experiments License',
		'enable_support' => TRUE,
		'support_menu_slug' => 'plugins.php',
		'support_menu_title' => 'Title Experiments Support',
	)
);

include('user-agents.php');
include('wpex.class.php');
new WPEx($wpph);
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
