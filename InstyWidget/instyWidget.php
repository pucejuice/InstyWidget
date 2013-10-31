<?php

/*

Plugin Name: InstyWidget

Plugin URI: https://github.com/danke/InstyWidget

Description: Instagram Slideshow Widget on your wordpress sidebar

Author: Daniel Kenna

Version: 1

Author URI: https://github.com/danke/InstyWidget

*/

function instyWidget_install() {
		global $wpdb;
	
		//create the instagram image and link store table
		$query = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix.'insty_images'."` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`url` VARCHAR(200) NOT NULL,
			`link` VARCHAR(200) NOT NULL,
			`date` INT(11),
			UNIQUE KEY id (id)
			);";
		$wpdb->query($query);
		//option to be used by cron, can't get instance info outside of widget class
		add_option('instyWidget_username', '');		
}

register_activation_hook(__FILE__, 'instyWidget_install');

function instyWidget_uninstall() {
		global $wpdb;
		//remove the email table
		$query = "DROP TABLE `".$wpdb->prefix.'insty_images'."`";
		$wpdb->query($query);
		//remove the next scheduled cron
		wp_clear_scheduled_hook('instyWidget_update');
}

register_deactivation_hook(__FILE__, 'instyWidget_uninstall');

function instyWidget_update(){
		global $wpdb;
		
		$username = get_option('instyWidget_username');
		
		$imgList = getInstyImages($username);
			
		//select the current images in the insty_images table
		$query = "SELECT * FROM `".$wpdb->prefix.'insty_images'."`";
		$images = $wpdb->get_results($query, ARRAY_A);
		$counter = 0;
		
		foreach ($imgList['imgs'] as $i){
			$checker = $wpdb->get_results("SELECT COUNT(*) FROM `".$wpdb->prefix.'insty_images'."` WHERE `url`='".$i."'");
			if($checker == 0){
				$query = "INSERT INTO `".$wpdb->prefix.'insty_images'."` 
						(`id`,`url`,`link`,`date`)
						VALUES (NULL,'".$imgList['imgs'][$counter]."', '".$imgList['links'][$counter]."', '".time()."')";
				$wpdb->query($query);
			}
			$counter ++;
		}
		
}
	
add_action('instyWidget_update', 'instyWidget_update');

function displayImages(){
	global $wpdb;

	$query = "SELECT * FROM `".$wpdb->prefix.'insty_images'."` ORDER BY RAND() LIMIT 20";
	$list = $wpdb->get_results($query, ARRAY_A);
	$imgs = array();
	$links = array();
	
	foreach ($list as $l){
		$imgs[] = $l['url'];
		$links[] = $l['link'];
	}
	
	$combined['imgs'] = $imgs;
	$combined['links'] = $links;
	return $combined;
}

function getInstyImages($username){
		$url = 'http://www.instagram.com/'.$username;
		$pageContent = get_web_page( $url );
		$imgList = getImages ( $pageContent['content'] );
		return $imgList;
}

function get_web_page( $url ){
		$options = array(
			CURLOPT_RETURNTRANSFER => true,     // return web page
			CURLOPT_HEADER         => false,    // don't return headers
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects
			CURLOPT_ENCODING       => "",       // handle all encodings
			CURLOPT_USERAGENT      => "spider", // who am i
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
			CURLOPT_TIMEOUT        => 120,      // timeout on response
			CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
		);

		$ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
		$content = curl_exec( $ch );
		$err     = curl_errno( $ch );
		$errmsg  = curl_error( $ch );
		$header  = curl_getinfo( $ch );
		curl_close( $ch );

		$header['errno']   = $err;
		$header['errmsg']  = $errmsg;
		$header['content'] = $content;
		return $header;
}

function getImages ($html){
		$pageArray = explode('"',$html);
		$imgs = array();
		$links = array();
			//filter through for images and links
			foreach ($pageArray as $p){
				//get the user uploaded images, smaller images only(ending in _5)
				if (strpos($p, '_6.jpg') !== false && strpos($p, 'profile') == false){
					$imgs[] = str_replace('\/','/',$p);
				} else if (strpos($p,'http:\/\/instagram.com\/p\/') !== false && strpos($p, 'profile') == false){
					$links[] = str_replace('\/','/',$p);
				}
			}
		$combined['imgs'] = $imgs;
		$combined['links'] = $links;
		return $combined;
}

class InstyWidget extends WP_Widget

{
	
  //basic details for the widget, description etc

  function InstyWidget()

  {
    $widget_ops = array('classname' => 'InstyWidget', 'description' => 'Displays an Instagram Slideshow Widget on your sidebar.' );
    $this->WP_Widget('InstyWidget', 'Insty Slideshow', $widget_ops);
  }

  

  //the display for changing the widget title in the admin section

  function form($instance)

  {

    $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'insty_username' => '', 'thumb_size' => '250', 'timing' => '3000') );

    $title = $instance['title'];
	$insty_username = $instance['insty_username'];
	$thumb_size = $instance['thumb_size'];
	$timing = $instance['timing'];
	
	$timestamp = date('d M y H:i:s',wp_next_scheduled( 'instyWidget_update' ) + 36000);
		?>

		  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: 

				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label>

		  </p>

		  <p><label for="<?php echo $this->get_field_id('insty_username'); ?>">Instagram Username: 

				<input class="widefat" id="<?php echo $this->get_field_id('insty_username'); ?>" name="<?php echo $this->get_field_name('insty_username'); ?>" type="text" value="<?php echo attribute_escape($insty_username); ?>" /></label>

		  </p>
		   
		  <p><label for="<?php echo $this->get_field_id('thumb_size'); ?>">Thumbnail Size: 

				<input class="widefat" id="<?php echo $this->get_field_id('thumb_size'); ?>" name="<?php echo $this->get_field_name('thumb_size'); ?>" type="text" value="<?php echo attribute_escape($thumb_size); ?>" /></label>

		  </p>
		  
		  <p><label for="<?php echo $this->get_field_id('timing'); ?>">Slideshow Delay (ms): 

				<input class="widefat" id="<?php echo $this->get_field_id('timing'); ?>" name="<?php echo $this->get_field_name('timing'); ?>" type="text" value="<?php echo attribute_escape($timing); ?>" /></label>

		  </p>
	  
		  <p><label for="nextUpdate">Next Update: <?php echo $timestamp; ?></p>
		<?php

  }

 

  function update($new_instance, $old_instance)

  {

    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
	$instance['insty_username'] = $new_instance['insty_username'];
	$instance['thumb_size'] = $new_instance['thumb_size'];
	$instance['timing'] = $new_instance['timing'];
	
	update_option('instyWidget_username', $instance['insty_username']);
	//update images
	instyWidget_update();
	//clear the current cron
	wp_clear_scheduled_hook('instyWidget_update');
	//schedule a new daily cron to update images
	wp_schedule_event(time(), 'daily', 'instyWidget_update');
	
    return $instance;

  }

  function widget($args, $instance)

  {

    extract($args, EXTR_SKIP);

 

    echo $before_widget;

    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	$insty_username = empty($instance['insty_username']) ? '' : $instance['insty_username'];
	$thumb_size = empty($instance['thumb_size']) ? '' : $instance['thumb_size'];
	$timing = empty($instance['timing']) ? '3000' : $instance['timing'];

	$images = displayImages();
	$counter = 0;
	
	if (!empty($title))

      echo $before_title . $title . $after_title;;

    // WIDGET CODE GOES HERE

	echo '<script src="http://malsup.github.com/jquery.cycle.all.js" type="text/javascript"></script>
			<script type="text/javascript">
				$(document).ready(function() {
					$(\'.slideshow\').cycle({
						fx: \'fade\', // choose your transition type, ex: fade, scrollUp, shuffle, etc...
						timeout: '.$instance['timing'].',												fit: true,												width: '.$instance['thumb_size'].',												height: '.$instance['thumb_size'].'
					});
					$(\'.slideshow\').attr(\'style\',\'position: relative;\');
				});
			</script>';	$divHeight = $instance['thumb_size'] + 10;	echo '<div style="height:'.$divHeight.'px; display:block;">';
	echo '<div class="slideshow" style="display:none;">';
	foreach ($images['imgs'] as $i){
		echo '<a href="'.$images['links'][$counter].'" target="_blank"><img src="'.$images['imgs'][$counter].'" style="width:'.$instance['thumb_size'].';height:'.$instance['thumb_size'].';"/></a>';
		$counter ++;
	}
	echo '</div>			</div>';
	
    echo $after_widget;

  }
	
}

add_action( 'widgets_init', create_function('', 'return register_widget("InstyWidget");') );?>
