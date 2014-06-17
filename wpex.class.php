<?php
/**
 * This is the main class for Title Experiemnts
 */

class WPEx {
	private $titles_tbl;

	function __construct($wpph) {
		global $wpdb;

		if (!session_id()) session_start();

		$_SESSION['wpex_viewed'] = array();
		$_SESSION['wpex_impressed'] = array();

		$this->titles_tbl = $wpdb->prefix . "wpex_titles";
		
		//Initialize
		add_action('add_meta_boxes',array($this,'add_meta_box'));
		
		//Save the blocks
		add_action('save_post',array($this,'save_blocks'));
		
		//Admin CSS
		add_action('admin_enqueue_scripts',array($this,'enqueue'));

		add_filter( 'the_title', array($this,'titles'), 10, 2 );
	}
	
	function get($what,$post_id) {
		$d = isset($_SESSION['wpex_data']) ? unserialize(base64_decode($_SESSION['wpex_data'])) : array();
		if(isset($d[$what.$post_id])) {
			return $d[$what.$post_id];
		} else {
			return NULL;
		}
	}

	function set($what,$post_id,$id) {
		$d = isset($_SESSION['wpex_data']) ? unserialize(base64_decode($_SESSION['wpex_data'])) : array();
		$d[$what.$post_id] = $id;
		$_SESSION["wpex_data"] = base64_encode(serialize($d));
	}

	function viewed($post_id,$title_id)
	{
		global $wpdb;

		if($this->is_bot()) return;
		
		if(in_array($post_id,$_SESSION['wpex_viewed'])) {
			return;
		}
		$sql = "SELECT stats FROM " . $this->titles_tbl . " WHERE id=".$title_id;
		
		$result = $wpdb->get_row($sql);
		if($result) {
			$time = strtotime("today");
			$data = unserialize($result->stats);
			if(!isset($data[$time])) {
				$data[$time] = 1;
			} else {
				$data[$time]++;
			}
			$data = serialize($data);
			$sql = "UPDATE " . $this->titles_tbl ." SET clicks=clicks+1,stats='$data' WHERE id=".$title_id;
			
			$wpdb->query($sql);
		}
		$_SESSION['wpex_viewed'][] = $post_id;
	}

	function titles($title,$id) {
		global $wpdb;

		if(is_admin()) return $title;

		$title_id = null;

		//Check if a specific post title is in our cookie 
		$result = $this->get("title",$id);
		$from_cookie = false;

		if($result) {
			$sql = "SELECT id,title FROM " . $this->titles_tbl . " WHERE enabled AND id=".$result;
			$result = $wpdb->get_row($sql, ARRAY_A);
			if($result) {
				$from_cookie = true;
				$result = array($result);
			}
		}

		if(!$result) {
			$sql = "SELECT id,title,impressions,clicks FROM " . $this->titles_tbl . " WHERE enabled AND post_id=".$id;
			$result = $wpdb->get_results($sql, ARRAY_A);
		}


		if(count($result) > 1) {
			//Use a beta distribution random number to determine which 
			//test to show. Based on:
			// http://www.quora.com/In-A-B-Testing-how-many-conversions-do-you-need-per-variation-for-the-results-to-be-significant
			require_once dirname(__FILE__).'/libs/PDL/BetaDistribution.php';
			mt_srand();
			$max = 0;
			foreach($result as $t) {
				// Seed with a 1/2 = 50% probability
				$i = $t['impressions'] <= 1 ? 2 : $t['impressions'];
				$c = $t['clicks'] <= 1 ? 1 : $t['clicks'];

				// Always ensure we $i & $c are > 0
				if($i-$c <= 0) {
					$i = $c + 1;
				}

				$bd= new BetaDistribution($c,$i-$c);
				$r = $bd->_getRNG();
				if($r > $max) {
					$test = $t;
					$max = $r;
				}
			}

			$result = $test;
		} elseif(count($result) == 1) {
			$result = $result[0];
		}
		
		if($result) {
			$title_id = $result['id'];
			$title = $result['title'] == "__WPEX_MAIN__" ? $title : $result['title'];

			// If this isn't the post/page and the user hasn't seen this title before, count
			// it as an impression
			if(!(is_single($id) || is_page($id)) && !in_array($title_id,$_SESSION['wpex_impressed'])) {
				$sql = "UPDATE " . $this->titles_tbl ." SET impressions=impressions+1 WHERE id=".$result['id'];
				$wpdb->query($sql);

				$_SESSION['wpex_impressed'][] = $title_id;
			}
			
			$this->set("title",$id,$result['id']);
		}

		// If this is the page/post and we found the title from 
		// the user's session, that means they saw the title elsewhere
		// and are now viewing the page - count it as a view
		if($from_cookie && (is_single($id) || is_page($id))) {
			$this->viewed($id,$title_id);	
		} 
		return stripslashes($title);
	}


	function enqueue() {
		wp_enqueue_style('wpexcss', plugins_url('css/wpex.css',__FILE__));
		wp_enqueue_script('wpexjs', plugins_url('js/wpex.js',__FILE__), array('jquery'), "0.0.1");
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
	function meta_box() {
		global $post;
		global $wpdb;

		$sql = "SELECT * FROM ".$this->titles_tbl." WHERE enabled AND post_id=".$post->ID;
		$results = $wpdb->get_results($sql,ARRAY_A);

		$so_title = str_replace("'", "\\'", $post->post_title);

		require_once dirname(__FILE__).'/libs/PDL/BetaDistribution.php';
			
		foreach($results as &$test) {
			$c = $test['clicks'] > 1 ? $test['clicks'] : 1;
			$i = $test['impressions'] > 1 ? $test['impressions'] : 2;

			if($i-$c <= 0) ($i = $c+1);

			$test['bd'] = new BetaDistribution($c,$i-$c);
		}
		
		$this->statTests = $results;
		foreach($results as $idx=>&$test) {
			$this->statChecking = $idx;
			$test['probability'] = round($this->simpsonsrule() * 100);
			$data = $this->get_sl_data($test['stats']);
			$test['stats_str'] = join(",",$data);
			$test['title'] = stripslashes($test['title']);
		}

		$rows = $results;
		
		$sql = "SELECT * FROM ".$this->titles_tbl." WHERE NOT enabled AND post_id=".$post->ID;
		$results = $wpdb->get_results($sql,ARRAY_A);
		$so_title = str_replace("'", "\\'", $post->post_title);
		foreach($results as $idx=>&$test) {
			$test['probability'] = 0;
			$test['stats_str'] = join(",",$data);
			$test['title'] = stripslashes($test['title']);
		}

		$rows = array_merge($rows, $results);

		echo "<script type='text/javascript'>";
		echo "_wpex_data = " . json_encode($rows);
		echo "</script>";
	}
	
	/**
	* Get the last seven days of sparkline data
	**/
	function get_sl_data($data) {
		$arr = array(0,0,0,0,0,0,0);
		$data = unserialize($data);
		if(!is_array($data)) {
			return $arr;
		}

		$today = strtotime("today");
		for($i=0;$i<7;$i++) {
			$d = $today-(24*60*60*$i);
			$arr[$i] = isset($data[$d]) ? $data[$d] : 0;
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

		if(!wp_is_post_revision($post_id) && !wp_is_post_autosave($post_id) && ((!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || @$_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest'))) :
			//Ensure the main title is in the DB
			$sql = "SELECT COUNT(*) FROM " . $this->titles_tbl . " WHERE post_id=".$post_id." AND title='__WPEX_MAIN__';";
			$count = $wpdb->get_col($sql);
			if($count[0] == 0) {
				$wpdb->insert($this->titles_tbl, array("title"=>"__WPEX_MAIN__","post_id"=>$post_id));
			}

			if(isset($_POST['wpex-titles'])) {
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
				foreach($_POST['wpex-removed'] as $val) {
					$wpdb->delete($this->titles_tbl, array("id"=>$val,"post_id"=>$post_id));
				}
			}
		endif;
	}

	function is_bot() {
		global $_ROBOT_USER_AGENTS;
		
		if(isset($_SESSION['wpexpro_is_bot'])) {
			return $_SESSION['wpexpro_is_bot'];
		}

		$ua = $_SERVER['HTTP_USER_AGENT'];
		foreach($_ROBOT_USER_AGENTS as $agent) {
			if(preg_match("/".$agent."/i", $ua)) {
				$_SESSION['wpexpro_is_bot'] = TRUE;
				return TRUE;
			}
		}

		$_SESSION['wpexpro_is_bot'] = FALSE;
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
?>