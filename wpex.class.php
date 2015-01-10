<?php
/**
 * This is the main class for Title Experiemnts
 */

class WPEx {
	public $titles_tbl;
	public $stats_tbl;
	private $table_slug;
	private $now;
	
	function __construct($slug = "wpex") {
		global $wpdb;
		$this->table_slug = $slug;

		$this->titles_tbl = $wpdb->prefix . $this->table_slug . "_titles";
		$this->stats_tbl = $wpdb->prefix . $this->table_slug . "_stats";

		//Initialize
		add_action('init', array($this, 'start_session'), 1);
		add_action('add_meta_boxes',array($this,'add_meta_box'));
		if($this->get_option("wpex_use_js", false)) {
			add_action('wp_enqueue_scripts',array($this,'enqueue'));
		}
		add_action('admin_enqueue_scripts',array($this,'admin_enqueue'));
		
		//Save the blocks
		add_action('save_post',array($this,'save_blocks'));
		
		//Admin CSS
		add_action('admin_enqueue_scripts',array($this,'enqueue'));
		add_filter('manage_edit-post_columns', array($this, 'edit_post_columns'));
		add_filter('manage_edit-page_columns', array($this, 'edit_post_columns'));
		add_action('manage_posts_custom_column', array($this, 'edit_post_custom_column'), 10, 2);
		add_action('manage_pages_custom_column', array($this, 'edit_post_custom_column'), 10, 2);

		add_filter( 'the_title', array($this,'titles'), 10, 2 );
		add_action( 'wp_ajax_wpex_hide_nag', array($this,'hide_sale_nag'));
		add_action( 'wp_ajax_wpex_stat_reset', array($this,'reset_stats'));
		add_action( 'wp_ajax_wpex_titles', array($this,'ajax_titles'));
		add_action( 'wp_ajax_nopriv_wpex_titles', array($this,'ajax_titles'));
		
		add_action( 'wp_ajax_nopriv_wpex_setcookies', array($this,'set_cookies'));
		
		add_action( 'admin_menu', array($this,'settings_menu'));
		$this->now = current_time("timestamp");
		if($this->get_option("wpex_installed", FALSE) === FALSE) {
			$this->update_option("wpex_installed", $this->now);
		}

		add_action('wp_dashboard_setup', array($this, 'add_nag_widget'));
		// add_action('admin_notices', array($this, 'add_sale_nag'));
	}
	function start_session() {
	    if(!session_id()) {
	        session_start();
	    }
	}

	function update_option($key, $value) {
		if(function_exists("apc_store")) {
			apc_store($key, $value);
		}
		update_option($key, $value);
	}

	function get_option($key, $default = NULL) {
		if(function_exists("apcs_exists")) {
			//if apc is around, use that to cache our get_option calls
			if(apc_exists($key)) {
				$return = apc_fetch($key);
				return $return === FALSE ? $default : $return;
			} else {
				$option = get_option($key, $default);
				apc_store($key, $option, 7200); //keep it around for 2h (probably could be persistant - but oh well)
				return $option;
			}			
		} else {
			return get_option($key, $default);
		}
	}

	// Function that outputs the contents of the dashboard widget
	function dashboard_nag_widget($post, $callback_args) {
		echo "<div style='text-align:center;font-size: 1.3em;'>Are you enjoying your title experiments but wished you had more detail?<br/><br/>Now you can with Title Experiments Pro!<br/>";
		echo "<br/><b>Detailed Statistics, Priority Support, And More!</b><br/><br/>";
		echo "<img src='https://wpexperiments.com/wp-content/uploads/2014/07/titlexpro.gif' style='max-width:90%;margin:5px auto;'/>";
		echo "<a class='button button-primary' href='https://wpexperiments.com/title-experiments-pro/' target='_blank'>Upgrade Today!</a></div>";
	}

	function add_nag_widget() {
		if(!class_exists("TitleEx")) {
			$installed = $this->get_option("wpex_installed");
			wp_add_dashboard_widget('titlex_nag_widget', 'Title Experiments Pro', array($this, 'dashboard_nag_widget'));
			// Took this code from the SEO Ultimate Plugin. Thanks! :)
			// Globalize the metaboxes array, this holds all the widgets for wp-admin
			global $wp_meta_boxes;

			// Get the regular dashboard widgets array 
			// (which has our new widget already but at the end)
			$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

			// Backup and delete our new dashboard widget from the end of the array
			$widget_backup = array( 'titlex_nag_widget' => $normal_dashboard['titlex_nag_widget'] );
			unset( $normal_dashboard['titlex_nag_widget'] );

			// Merge the two arrays together so our widget is at the beginning
			$sorted_dashboard = array_merge($widget_backup, $normal_dashboard );

			// Save the sorted array back into the original metaboxes 
			$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;


		}
	}

	function add_sale_nag() {
		$now = time();
		if($this->get_option("_wpex_show_sale_nag_1420070400", true) && $now < 1420070400) {//2015-01-01
		    ?>
			<div class="update-nag" style="border-left: 4px solid lightgreen"><b>Awesome!</b> Between now and the end of the year, upgrade to <em>Title Experiments Pro</em> for only $14.99/yr! <b>That's 50% off!</b><br/><a target="_blank" href="https://wpexperiments.com/title-experiments-pro/">Click here</a> to upgrade now. <small><a style='float: right;' href="#" data-nag-id="1420070400">[hide]</a></small></div>
		    <?php
		}
	}
	
	function hide_sale_nag() {
		$id = $_POST['id'];
		$this->update_option("_wpex_show_sale_nag_".$id, 0);
	}

	function settings_menu() {
		add_submenu_page('options-general.php', 'Title Exp Settings', 'Title Exp Settings', 'edit_posts', "wpex-settings", array($this,"general_settings") );
	}

	// The general settings page
	function general_settings() {
		global $titleEx;
		if(isset($_REQUEST['save'])) {
			$this->update_option("wpex_use_js", $_REQUEST['use_js']);
			$this->update_option("wpex_best_feed", $_REQUEST['best_feed']);
			$this->update_option("wpex_search_engines", $_REQUEST['search_engines']);
			$this->update_option("wpex_adjust_every", $_REQUEST['adjust_every']);
			$this->update_option("wpex_skip_pages", $_REQUEST['skip_pages']);
			$this->update_option("wpex_ignore_users", $_REQUEST['ignore_users']);
			if($titleEx) {
				$titleEx->save_settings($_REQUEST);
			}
		}
		
		$use_js = $this->get_option("wpex_use_js", FALSE);
		$best_feed = $this->get_option("wpex_best_feed", FALSE);
		$search_engines = $this->get_option("wpex_search_engines", "first");
		$adjust_every = $this->get_option("wpex_adjust_every", 300);
		$skip_pages = $this->get_option("wpex_skip_pages", 300);
		$ignore_users = $this->get_option("wpex_ignore_users", FALSE);
		include 'wpex-general-settings.php';
	}

	function reset_stats($data) {
		global $wpdb;
		$post_id = $_POST['id'];
		$sql = "UPDATE " . $this->titles_tbl ." SET clicks=0,impressions=0,stats='' WHERE post_id=".$post_id;
		$wpdb->query($sql);
		$sql = "DELETE FROM " . $this->stats_tbl ." WHERE post_id=".$post_id;
		$wpdb->query($sql);
	}

	function get($what,$post_id) {
		$d = isset($_SESSION['wpex_data']) ? $_SESSION['wpex_data'] : array();
		if(isset($d[$what.$post_id])) {
			return $d[$what.$post_id];
		} else {
			return NULL;
		}
	}

	function set($what,$post_id,$id) {
		$d = isset($_SESSION['wpex_data']) ? $_SESSION['wpex_data'] : array();
		$d[$what.$post_id] = $id;
		$_SESSION['wpex_data'] = $d;
	}

	function viewed($post_id,$title_id)
	{
		global $wpdb;
		if($this->is_bot()) return;
		
		$viewed = $this->get('wpex_viewed', 'WPEX');
		if(!is_array($viewed)) $viewed = array();
		if(in_array($post_id, $viewed)) {
			return;
		}
		$sql = "SELECT stats FROM " . $this->titles_tbl . " WHERE id=".$title_id;
		
		$result = $wpdb->get_row($sql);
		if($result) {
			mt_srand();
			// fake the time right now
			$time = strtotime("midnight");
			$this->delta_stats($title_id, $post_id, $time, 0, 1);
			$sql = "UPDATE " . $this->titles_tbl ." SET clicks=clicks+1 WHERE id=".$title_id;
			$wpdb->query($sql);
		}
		
		$viewed[] = $post_id;
		$this->set('wpex_viewed', 'WPEX', $viewed);
	}

	function delta_stats($title_id, $post_id, $time, $impressions, $clicks) {
		global $wpdb;
		if(preg_match("/^\d+$/", $title_id) && preg_match("/^\d+$/", $time) && preg_match("/^\d+$/", $impressions) && preg_match("/^\d+$/", $clicks)) {
			$sql = "SELECT * FROM " . $this->stats_tbl ." WHERE ts=$time AND title_id=".$title_id;
			$row = $wpdb->get_row($sql, ARRAY_A);
			if($row) {
				$sql = "UPDATE " . $this->stats_tbl ." SET impressions=impressions+$impressions, clicks=clicks+$clicks WHERE ts=$time AND title_id=".$title_id;
			} else {
				$sql = "INSERT INTO " . $this->stats_tbl ."(ts, post_id, title_id, impressions, clicks) VALUES($time, $post_id, $title_id, $impressions, $clicks);";
			}
			$wpdb->query($sql);	
		}
	}

	function ajax_titles() {
		$titles = array();
		if(isset($_POST['id'])) {
			$cur_page = isset($_POST['cur_id']) ? $_POST['cur_id'] : NULL;
			foreach ($_POST['id'] as $id) {
				$titles[$id] = $this->titles("", $id, true, $id == $cur_page);
			}
			echo json_encode($titles); die();	
		}
	}

	function edit_post_custom_column($column, $post_id = NULL) {
		global $wpdb;
		if($post_id) {
			switch ($column) {
				case 'wpex_titles':
					//never trust an elf
					$post_id = intval($post_id); 
					
					$sql = "SELECT COUNT(*) as c FROM ".$this->titles_tbl." WHERE post_id=".$post_id;
					$row = $wpdb->get_row($sql, ARRAY_A);
					echo $row['c'] > 0 ? $row['c'] : '';
					break;
			}
		}
	}

	function edit_post_columns($columns) {
		return array_slice($columns, 0, 1, true) 
			+ array("wpex_titles" => "<span class='dashicons-before dashicons-editor-ul' title='Shows how many alternate titles this post has'></span>")
			+ array_slice($columns, 1, count($columns)-1, true);
	}

	function titles($title, $id=NULL, $ajax = false, $viewed = false) {
		global $wpdb;
		if($id == NULL) return $title;
		if(!$ajax && is_admin()) return $title;
		$skip_pages = $this->get_option("wpex_skip_pages", 300);
		$pages = explode("\n", $skip_pages);

		if(in_array($_SERVER['REQUEST_URI'], $pages)) {
			return $title;
		}

		// Check if we are supposed to ignore logged in users
		$ignore_users = $this->get_option("wpex_ignore_users", FALSE);
		if($ignore_users && is_user_logged_in() && current_user_can('edit_post', $id)) {
			return $title;
		}

		// ensure consistant ordering
		$sql = "SELECT id,title,impressions,clicks,probability,last_updated FROM " . $this->titles_tbl . " WHERE enabled=1 AND post_id=".$id." ORDER BY id";
		$titles_result = $wpdb->get_results($sql, ARRAY_A);
		
		if(count($titles_result) === 0) {
			//No titles are here
			return $title;
		}

		$search_engines = $this->get_option("wpex_search_engines", "first");

		// search engines should see the first title
		if($this->is_bot() && $search_engines == "first") {
			return $title;
		}

		//If this is a feed - no funny business
		// or if search engines should see the best title
		if(is_feed() || ($this->is_bot() && $search_engines == "best")) {
			//use the best title based on click percent
			if($this->get_option("wpex_best_feed", false)) {
				$max = array(-1, NULL);
				foreach($titles_result as $t) {
					$_max = $t['clicks'] / ($t['impressions'] == 0 ? 1 : $t['impressions']);
					if($_max > $max[0]) {
						$max = array($_max, $t['title']);
					}
				}
				if($max === NULL) return $title; //give up
				if($max[1] == "__WPEX_MAIN__") {
					return $title;
				}else {
					return stripslashes($max[1]);
				}
			} else {
				//use the standard title
				return $title;
			}
		}

		$title_id = null;
		if(!$ajax && $this->get_option("wpex_use_js", false)) {
			return "<span style='min-height: 1em; display: inline-block;' data-wpex-title-id='$id' data-original='".base64_encode($title)."'></span>";
		}

		//Check if a specific post title is in our cookie 
		$result = $this->get("title",$id);
		$from_cookie = false;
		if($result) {
			foreach($titles_result as $t) {
				if($t['id'] == $result) {
					$from_cookie = true;
					$result = array($t);
					break;
				}
			}
		}

		if(!$result) {
			$result = $titles_result;
		}

		$startTime = microtime(true);
		if(count($result) > 1) {
			//check if we need to regen the probabilities
			$adjust_every = $this->get_option("wpex_adjust_every", 300);

			if($result[0]['last_updated'] + $adjust_every < $this->now) {
				//Use a beta distribution random number to determine which 
				//test to show. Based on:
				// http://camdp.com/blogs/multi-armed-bandits
				require_once dirname(__FILE__).'/libs/PDL/BetaDistribution.php';
				mt_srand();
				$max = 0;
				foreach($result as &$t) {
					$i = (0.5) * $t['impressions'];
					$c = (0.5) * $t['clicks'];
					$t['bd']= new BetaDistribution(1+$c,1+max(0, $i-$c));
				}

				$this->statTests = $result;
				foreach($result as $idx=>&$test) {
					$this->statChecking = $idx;
					$test['probability'] = round($this->simpsonsrule() * 100);
					$sql = "UPDATE " . $this->titles_tbl ." SET probability=".$test['probability'].", last_updated=".$this->now." WHERE id=".$test['id'];
					$wpdb->query($sql);
				}
			}

			// We pick a random number and then loop
			// through our tests and check if the number is
			// less than the sum of all the previous probabilties
			// that we've checked so far. It works - test it. :)
			
			// mt_srand(); // no need to do this http://php.net/manual/en/function.mt-srand.php
			$rand = mt_rand(0,100);
			$total = 0;
			foreach($result as $t) {
				if($rand < ($total + $t['probability'])) {
					break;
				}
				$total += $t['probability'];
			}
			$result = $t;
		} elseif(count($result) == 1) {
			$result = $result[0];
		}

		if($result) {
			$title_id = $result['id'];
			$title = $result['title'] == "__WPEX_MAIN__" ? $title : $result['title'];

			// If this isn't the post/page and the user hasn't seen this title before, count
			// it as an impression
			$impressions_arr = $this->get('wpex_impressed', 'WPEX');
			if(!is_array($impressions_arr)) $impressions_arr = array();
			if(!($viewed || is_single($id) || is_page($id)) && !in_array($title_id, $impressions_arr)) {
				$time = strtotime("midnight");
				$this->delta_stats($result['id'], $id, $time, 1, 0);
				$sql = "UPDATE " . $this->titles_tbl ." SET impressions=impressions+1 WHERE id=".$result['id'];
				$wpdb->query($sql);

				$impressions_arr[] = $title_id;
				$this->set('wpex_impressed', "WPEX", $impressions_arr);
			}
			$this->set("title",$id,$result['id']);

			if(in_array($title_id, $impressions_arr)) {
				// If this is "the page/post and we found the title from 
				// the user's session, that means they saw the title elsewhere
				// and are now viewing the page - count it as a view
				if($from_cookie && ($viewed || is_single($id) || is_page($id))) {
					$this->viewed($id,$title_id);	
				} 
			} 
		}

		return stripslashes($title);
	}

	function enqueue() {
		// Register the script first.
		wp_register_script( 'wpextitles', plugins_url('/js/titles.js',__FILE__), array("jquery"), "5.6");

		// Now we can localize the script with our data.
		$data = array('ajaxurl' => admin_url( 'admin-ajax.php' ));
		wp_localize_script( 'wpextitles', 'wpex', $data );
		// The script can be enqueued now or later.
		wp_enqueue_script( 'wpextitles');
	}

	function admin_enqueue() {
		wp_enqueue_style('wpexcss', plugins_url('css/wpex.css',__FILE__), array(), "5.6");
		wp_enqueue_script('wpexjs', plugins_url('js/wpex.js',__FILE__), array('jquery'), "5.6");
		wp_enqueue_script('jquery.sparkline.min.js', plugins_url('js/jquery.sparkline.min.js',__FILE__), array('jquery'), "0.0.1");
		wp_enqueue_script('jquery.qtip.min.js', plugins_url('js/jquery.qtip.min.js',__FILE__), array('jquery'), "0.0.1");
		wp_enqueue_style('jquery.qtip.min.css', plugins_url('css/jquery.qtip.min.css',__FILE__));
	}
	
	/**
	 * Add meta box when the post has a block 
	 */
	function add_meta_box() {
		global $post;
		add_meta_box('wpex-meta-box',__('Title Experiments','wpex'),array($this,'meta_box'), $post->post_type, 'normal', 'high');
	}
	
	/**
	 * Show meta box
	 */
	function meta_box($post, $box, $reload = FALSE) {
		global $wpdb;

		$sql = "SELECT * FROM ".$this->titles_tbl." WHERE enabled=1 AND post_id=".$post->ID;
		$results = $wpdb->get_results($sql,ARRAY_A);

		$so_title = str_replace("'", "\\'", $post->post_title);

		$adjust_every = $this->get_option("wpex_adjust_every", 300);

		if(!$reload && (($results[0]['last_updated'] + $adjust_every) < $this->now)) {
			//we need to fetch the titles
			$this->titles($post->post_title, $post->ID, true);
			return $this->meta_box($post, $box, true);
		}
		foreach($results as $idx=>&$test) {
			$sql = "SELECT * FROM ".$this->stats_tbl." WHERE title_id=".$test['id'];
			$stat_results = $wpdb->get_results($sql, ARRAY_A);
			$stats = array();
			foreach($stat_results as $s) {
				$stats[$s['ts']] = array(
					'clicks' => $s['clicks'],
					'impressions' => $s['impressions']
				);
			}
			$data = $this->get_sl_data($stats);
			$test['stats_str'] = join(",",$data);
			$test['title'] = stripslashes($test['title']);
		}

		$rows = $results;
		
		$sql = "SELECT * FROM ".$this->titles_tbl." WHERE NOT enabled=1 AND post_id=".$post->ID;
		$results = $wpdb->get_results($sql,ARRAY_A);
		$so_title = str_replace("'", "\\'", $post->post_title);
		foreach($results as $idx=>&$test) {
			$test['probability'] = 0;
			$test['stats_str'] = join(",",$data);
			$test['title'] = stripslashes($test['title']);
		}

		$pro_nag = !class_exists("TitleEx") && ($this->get_option("_titlex_last_nag", 0) < (current_time("timestamp") - (12*60*60)));
		if($pro_nag) {
			$this->update_option("_titlex_last_nag", current_time("timestamp"));
		}
		$rows = array_merge($rows, $results);

		echo "<script type='text/javascript'>";
		echo "_wpex_data = " . json_encode($rows)."\n";
		echo "_wpex_pro_nag = ".($pro_nag?"true":"false").";";
		echo "</script>";

		// Add an nonce field so we can check for it later.
		wp_nonce_field('titlexp_meta_box', 'titlexp_meta_box_nonce');
	}
	
	/**
	* Get the last seven days of sparkline data
	**/
	function get_sl_data($data) {
		$arr = array(0,0,0,0,0,0,0);
		if(!is_array($data)) {
			return $arr;
		}

		$today = strtotime("today");
		for($i=0;$i<7;$i++) {
			$d = $today-(24*60*60*$i);
			$arr[$i] = isset($data[$d]) ? $data[$d]['clicks'] : 0;
		}
		return array_reverse($arr);
	}

	/**
	 * Save the blocks
	 *
	 * @param int $post_id
	 */
	function save_blocks($post_id) {
		global $wpdb;

		// Check if our nonce is set.
		if ( ! isset( $_POST['titlexp_meta_box_nonce'] ) ) {
			return;
		}
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['titlexp_meta_box_nonce'], 'titlexp_meta_box') ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if(isset($_POST['wpex-titles'])) {
			//Ensure the main title is in the DB
			$sql = "SELECT COUNT(*) FROM " . $this->titles_tbl . " WHERE post_id=".$post_id." AND title='__WPEX_MAIN__';";
			$count = $wpdb->get_col($sql);
			if($count[0] == 0) {
				$wpdb->insert($this->titles_tbl, array("title"=>"__WPEX_MAIN__","post_id"=>$post_id));
			}

			foreach($_POST['wpex-titles'] as $key=>$val) {
				$enabled = isset($_POST['wpex-enabled']) && isset($_POST['wpex-enabled'][$key]) ? true : false; 
				if($key[0] == "_") {
						//Update
					$wpdb->update($this->titles_tbl, array("title"=>$val,"post_id"=>$post_id,"enabled"=>$enabled), array("id"=>substr($key,1)));
				} else {
						//Insert
					$wpdb->insert($this->titles_tbl, array("title"=>$val,"post_id"=>$post_id,"enabled"=>$enabled));
				}
			}
		}
		if(isset($_POST['wpex-removed'])) {
			if(empty($_POST['wpex-titles'])) {
				// they deleted all the titles, just delete them all
				$wpdb->delete($this->titles_tbl, array("post_id"=>$post_id));
				$wpdb->delete($this->stats_tbl, array("post_id"=>$post_id));
			} else {
				foreach($_POST['wpex-removed'] as $val) {
					$wpdb->delete($this->titles_tbl, array("id"=>$val,"post_id"=>$post_id));
					$wpdb->delete($this->stats_tbl, array("title_id"=>$val,"post_id"=>$post_id));
				}	
			}
		}
	}

	function is_bot() {
		global $_ROBOT_USER_AGENTS;

		$is_bot = $this->get('wpex_is_bot', 'WPEX');
		if($is_bot !== NULL) {
			return $is_bot;
		}

		$ua = $_SERVER['HTTP_USER_AGENT'];
		foreach($_ROBOT_USER_AGENTS as $agent) {
			if(preg_match("/".$agent."/i", $ua)) {
				$this->set('wpex_is_bot', "WPEX", TRUE);
				return TRUE;
			}
		}

		$this->set('wpex_is_bot', "WPEX", FALSE);
		return FALSE;
	}
	
	function get_winner($id) {
		global $wpdb;

		$sql = "SELECT clicks,impressions,id FROM " . $this->titles_tbl . " WHERE impressions > 1 AND post_id=".$id;
		$results = $wpdb->get_results($sql,ARRAY_N);
		
		// Sort them by the probality(zscore)
		usort($results,array($this,'_prob_sort'));

		// Compare the first one against the rest down the line to
		// find out where our statistical difference is
		for($i = 1; $i < count($results); $i++) {
			$rA = $results[0];
			$rB = $results[$i];
			if($this->_prob_sort($rA,$rB) != 0) {
				break;
			}
		}

		//Get the winner's id
		$winners = array();
		for($x=0;$x<$i;$x++) {
			$winners[] = $results[$x][2];
		}

		return $winners;
	}

	// Find the winner of between two tests	
	function _winner($cA,$iA,$cB,$iB,$raw = false) {
		$res = $this->cumnormdist($this->zscore(array($iA,$cA), array($iB,$cB))); 

		if($raw) return $res;

		if($res > 0.95) {
			return 1;
		} elseif ($res < 0.05) {
			return -1;
		} else {
			return 0;
		}
	}

	// Use the winner function to sort an array of array(click,impressions) members
	function _prob_sort($a,$b) {
		return $this->_winner($a[1],$a[0],$b[1],$b[0]);
	}

	// Based on code from https://developer.amazon.com/sdk/ab-testing/reference/ab-math.html
	function conf_int($c,$i) {
		if($c == 0 || $i == 0) return 100;
		$sample = $i;
		$probabilty = $c/$i;
		$standard_error = $this->standard_error($probabilty, $sample);
		return round($standard_error*1.65*100,2);
	}
	function standard_error($prob,$sample) {
		if($sample == 0) return 0;
		return sqrt(($prob*(1-$prob)) / $sample);
	}

	// (((((((((((((((((((((((((((((())))))))))))))))))))))))))))))
	// From http://abtester.com/calculator/
	// (((((((((((((((((((((((((((((())))))))))))))))))))))))))))))
	// Calculation of the conversion rate
	function cr($t) 
	{ 
		if($t[0] == 0) return 0;
	    return $t[1]/$t[0]; 
	}

	//Calculation of the z-score
	function zscore($c, $t) 
	{
	    $z = $this->cr($t)-$this->cr($c);
	    $s = ($this->cr($t)*(1-$this->cr($t)))/$t[0] + ($this->cr($c)*(1-$this->cr($c)))/$c[0];
	    return $z/sqrt($s);
	}

	//Calculation of the cumulative normal distribution.
	function cumnormdist($x)
	{
	  $b1 =  0.319381530;
	  $b2 = -0.356563782;
	  $b3 =  1.781477937;
	  $b4 = -1.821255978;
	  $b5 =  1.330274429;
	  $p  =  0.2316419;
	  $c  =  0.39894228;

	  if($x >= 0.0) {
	      $t = 1.0 / ( 1.0 + $p * $x );
	      return (1.0 - $c * exp( -$x * $x / 2.0 ) * $t *
	      ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ));
	  }
	  else {
	      $t = 1.0 / ( 1.0 - $p * $x );
	      return ( $c * exp( -$x * $x / 2.0 ) * $t *
	      ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ));
	    }
	}


	// Out simpson rule integral approximation f(x)
	function simpsonf($x){
		$prod = 1;
		foreach($this->statTests as $id=>$test) {
			if($id == $this->statChecking) {
				$prod *= $test['bd']->_getPDF($x);
			} else {
				$prod *= $test['bd']->_getCDF($x);
			}
		}
		// returns f(x) for integral approximation with composite Simpson's rule
		 return $prod;
	}

	// Implementation of Simpsons Rule for integral approximations
	// From: http://www.php.net/manual/en/ref.math.php#61377
	function simpsonsrule(){
		$a = 0; $b = 1; $n = 1000;
		// approximates integral_a_b f(x) dx with composite Simpson's rule with $n intervals
		// $n has to be an even number
		// f(x) is defined in "function simpsonf($x)"
		 if($n%2==0){
				$h=($b-$a)/$n;
				$S=$this->simpsonf($a)+$this->simpsonf($b);
				$i=1;
				while($i <= ($n-1)){
					 $xi=$a+$h*$i;
					 if($i%2==0){
							$S=$S+2*$this->simpsonf($xi);
					 }
					 else{
							$S=$S+4*$this->simpsonf($xi);
					 }
					 $i++;
				}
				return($h/3*$S);
				}
		 else{
				return('$n has to be an even number');
		 }
	}
}