<?php
	/*
	Plugin Name: Let The People Move You - ScrapBook
	Plugin URI: http://www.momensity.com
	Description: Based on VeriteCo Timeline  
	Author: Jermon D. Green/Young J. Yoon
	Version: 1.2
	Author URI: http://www.momensity.com
	*/
?>
<?php
	/* TIMELINE ENTRY CLASS */
	class wpvtEntry 
	{
		private $post_id;
		
		public $startDate;
		public $endDate;
		public $headline;
		public $text;
		public $asset;
		
		public function __construct( $post ) {
			$this->post_id = $post->ID;
			$meta = get_post_meta( $this->post_id );
			
			$this->startDate = $meta['wpvt_start_date'][0];
			$this->endDate = $meta['wpvt_end_date'][0];
			$this->headline = get_the_title( $this->post_id );
			
			$text = apply_filters('the_content', $post->post_content);
			$text = preg_replace('/\v+|\\\[rn]/','',$text);
			$text = $this->undoTexturize($text);
			
			$this->text = $text;
			
			$thumbnail_id = get_post_thumbnail_id( $this->post_id );
			
			if( $thumbnail_id ) {
				// if there is featured image
				$img = wp_get_attachment_image_src( $thumbnail_id, 'full' );
				$thumbnail_image = get_post( $thumbnail_id, 'OBJECT' );
				if ($thumbnail_image && isset($thumbnail_image)) {
					$this->asset->media = $img[0];
					$this->asset->caption = $thumbnail_image->post_excerpt;
				}
			} else if( $meta['wpvt_video'][0] ) {
				// otherwise, look for youtube link
				$this->asset->media = $meta['wpvt_video'][0];
				$this->asset->caption = $meta['wpvt_video_caption'][0];
			}
		}
		
		public function toJSON() {
			return json_encode($this);
		}
		
		public function undoTexturize($content, $deprecated = '') {
			if ( !empty( $deprecated ) )
				_deprecated_argument( __FUNCTION__, '0.71' );

			// Translation of invalid Unicode references range to valid range
			$wp_htmltranswinuni = array(
				'&#8211;' => '-',
				'&#8212;' => '�',
				'&#8217;' => '\'',
				'&#8218;' => ',',
				'&#8220;' => '\"',
				'&#8221;' => '\"'
			);
			// Fix Word pasting
			$content = strtr($content, $wp_htmltranswinuni);
			return $content;
		}
	}


	/* Initailaize Back-end */	
	function wpvt_admin_init() {
		wp_register_script( 'veriteco', plugins_url('js/timeline-min.js', __FILE__) );

		wp_register_script( 'wpvt_custom', plugins_url('js/wpvt_custom.js', __FILE__) );
		wp_register_style( 'wpvt_css', plugins_url('css/wpvt.css', __FILE__) );
		
		$page_title = "Momensity ScrapBook Configuration";
		$menu_title = "Momensity ScrapBook";
		$capability = "publish_posts";
		$menu_slug = "wpvt_config";
		$function = "wpvt_config_page";
		$icon_url = "";
		$position = "";
		
		add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function );
		
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('veriteco');
		wp_enqueue_script('wpvt_custom');
		
		wp_enqueue_style('wpvt_css');
	}
	add_action('admin_menu', 'wpvt_admin_init');
	
	
	/* Load Default Settings */
	function wpvt_default_settings() {
		$tmp = get_option('wpvt_options');
		if(!is_array($tmp)) {
			$arr = array(
				'width' => '900',
				'height' => '600',
				'maptype' => 'toner',
				'font' => 'Bevan-PotanoSans'
			);
			update_option('wpvt_options', $arr);
		}
	}
	register_activation_hook(__FILE__, 'wpvt_default_settings');
	
	
	/* Settings */
	function wpvt_settings_init() {
		$maptypes = array(
			'toner' => 'Stamen Maps: Toner',
			'toner-lines' => 'Stamen Maps: Toner Lines',
			'toner-labels' => 'Stamen Maps: Toner Labels',
			'watercolor' => 'Stamen Maps: Watercolor',
			'sterrain' => 'Stamen Maps: Terrain',
			'ROADMAP' => 'Google Maps: Roadmap',
			'TERRAIN' => 'Google Maps: Terrain',
			'HYBRID' => 'Google Maps: Hybrid',
			'SATELLITE' => 'Google Maps: Satellite'
		);
		
		$fonts = array(
			'Bevan-PotanoSans' => 'Bevan &amp; Potano Sans',
			'Merriweather-NewsCycle' => 'Merriweather &amp; News Cycle',
			'PoiretOne-Molengo' => 'Poiret One &amp; Molengo',
			'Arvo-PTSans' => 'Arvo &amp; PTSans',
			'PTSerif-PTSans' => 'PTSerif &amp; PTSans',
			'DroidSerif-DroidSans' => 'Droid Serif &amp; Droid Sans',
			'Lekton-Molengo' => 'Lekton &amp; Molengo',
			'NixieOne-Ledger' => 'NixieOne &amp; Ledger',
			'AbrilFatface-Average' => 'Abril Fatface &amp; Average',
			'PlayfairDisplay-Muli' => 'Playfair Display &amp; Muli',
			'Rancho-Gudea' => 'Rancho &amp; Gudea',
			'BreeSerif-OpenSans' => 'Bree Serif &amp; Open Sans',
			'SansitaOne-Kameron' => 'Sansita One &amp; Kameron',
			'Pacifico-Arimo' => 'Pacifico &amp; Arimo',
			'PT' => 'PT Sans &amp; PT Narrow &amp; PT Serif'
		);
		
		$types = array(
			'default' => 'Default'
		);
		
		add_settings_section('wpvt_id', '', 'wpvt_callback', 'wpvt_page');
		
		register_setting( 'wpvt_optiongroup', 'wpvt_options' ); // General Settings
		
		/* Add fields to cover page settings */
		add_settings_field('headline', 'Cover Headline', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'headline', 'type' => 'text') );
		add_settings_field('text', 'Cover Text', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'text', 'type' => 'text') );
		add_settings_field('type', 'Timeline Type', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'type', 'type' => 'select', 'options' => $types ) );
		
		/* Add fields */		
		add_settings_field('width', 'Width', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'width', 'type' => 'text') );
		add_settings_field('height', 'Height', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'height', 'type' => 'text') );
		add_settings_field('maptype', 'Map Type', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'map', 'type' => 'select', 'options' => $maptypes ) );
		add_settings_field('font', 'Fonts', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'fonts', 'type' =>'select', 'options' => $fonts ) );
		add_settings_field('start_at_end', 'Start at the end?', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'start_at_end', 'type' => 'checkbox', 'label' => 'Yes') );
		add_settings_field('hash_bookmark', 'Hash Bookmarks?', 'wpvt_setting_string', 'wpvt_page', 'wpvt_id', array('id' => 'hash_bookmark', 'type' => 'checkbox', 'label' => 'Yes') );		
	}
	add_action('admin_init', 'wpvt_settings_init');
	
		function wpvt_callback() { echo '<p>Adjust settings for the Scrapbook here.</p>'; }

		function wpvt_setting_string( $args ) {
			$options = get_option('wpvt_options');
			$id = $args['id'];
			$type = $args['type'];
			
			switch($type) {
				case 'text':
					$class = ($args['class']) ? ' class="'.$args['class'].'"' : '';
					echo "<input id='wpvt_".$id."' name='wpvt_options[".$id."]' type='text'". $class ." value='".$options[$id]."' />";
					break;
				case 'select':
					$choices = $args['options'];
					echo '<select id="wpvt_'.$id.'" name="wpvt_options['.$id.']">';
					foreach($choices as $value => $label) {
						$selected = ($options[$id] == $value) ? ' selected' : '';
						echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
					}
					echo '</select>';
					break;
				case 'checkbox':
					$checked = ($options[$id] == '1') ? ' checked' : '';
					echo '<input id="wpvt_'.$id.'" name="wpvt_options['.$id.']" type="checkbox" value="1" class="code" ' . $checked . ' /> '.$args['label'];
					break;
				default:
					break;
			}			
		}
	
	/* Back-end Interface */	
	function wpvt_config_page() { ?>
		<div class="wrap">
			<div id="poststuff">
				<div id="wpvt-icon"><br /></div>
				<?php echo '<h1 class="wpvt-title">' . __( 'Momensity ScrapBook Configuration', 'wpvt-config' ) . '</h1>'; ?>
				<div class="clear"></div>
				
				<div class="postbox timeline-postbox">
					<h3>ScrapBook Settings</h3>
					
					<div class="inside">
						<form method="post" action="options.php">
							<?php settings_fields( 'wpvt_optiongroup' ); ?>
							<?php do_settings_sections( 'wpvt_page' ); ?>
							<?php submit_button(); ?>
						</form>
					</div>
				</div><!-- #postbox -->
			</div><!-- #poststuff -->
		</div>
	<?php }
	
	/* Register custom post type */
	function wpvt_post_type_init() {
		$labels = array(
			'name' => _x('ScrapBook Entries', 'post type general name'),
			'singular_name' => _x('ScrapBook Entry', 'post type singular name'),
			'add_new' => _x('Add New', 'ScrapBook'),
			'add_new_item' => __('Add New ScrapBook Entry'),
			'edit_item' => __('Edit ScrapBook Entry'),
			'new_item' => __('New ScrapBook Entry'),
			'all_items' => __('All ScrapBook Entries'),
			'view_item' => __('View ScrapBook Entry'),
			'search_items' => __('Search ScrapBook Entries'),
			'not_found' =>  __('No ScrapBook Entries found'),
			'not_found_in_trash' => __('No ScrapBook Entries found in Trash'), 
			'parent_item_colon' => '',
			'menu_name' => __('ScrapBook')
		);
		$capabilities = array(
			'publish_posts' => 'publish_scrapbook',
			'edit_posts' => 'edit_scrapbook',
			'edit_others_posts' => 'edit_others_scrapbook',
			'delete_posts' => 'delete_scrapbook',
			'delete_others_posts' => 'delete_others_scrapbook',
			'read_private_posts' => 'read_private_scrapbook',
			'edit_post' => 'edit_scrapbook',
			'delete_post' => 'delete_scrapbook',
			'read_post' => 'read_scrapbook'
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'capabilities' => $capabilities,
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'thumbnail', 'author' ),
			'register_meta_box_cb' => 'wpvt_meta_boxes'
		); 
		register_post_type( 'scrapbook' , $args );
		
		wp_register_style( 'veriteco_css', plugins_url('css/timeline.css', __FILE__) );
		wp_enqueue_style('veriteco_css');
	}
	add_action( 'init', 'wpvt_post_type_init' );
	
	/* Metaboxes for Scrapbook Post Type */
	function wpvt_meta_boxes() {
		add_meta_box( 'scrapbook-meta', 'ScrapBook Meta Data', 'wpvt_meta_boxes_inner', 'scrapbook' );
	}
	add_action( 'add_meta_boxes', 'wpvt_meta_boxes' );

        /* Metaboxes for Fanbook Post Type */
	function fb_wpvt_meta_boxes() {
		add_meta_box( 'fanbook-meta', 'FanBook Meta Data', 'wpvt_meta_boxes_inner', 'm_fanbook' );
	}
	add_action( 'add_meta_boxes', 'fb_wpvt_meta_boxes' );

        /* Metaboxes for Groupbook Post Type */
	function gb_wpvt_meta_boxes() {
		add_meta_box( 'groupbook-meta', 'GroupBook Meta Data', 'wpvt_meta_boxes_inner', 'm_groupbook' );
	}
	add_action( 'add_meta_boxes', 'gb_wpvt_meta_boxes' );

	/* Prints the box content */
	function wpvt_meta_boxes_inner() {
		global $post;
		wp_nonce_field( plugin_basename( __FILE__ ), 'wpvt_noncename' ); // Use nonce for verification
		
		$meta = get_post_meta($post->ID);
		?>
		<div class="wpvt-metabox">
			<div class="wpvt-metabox-item">
				<label for="wpvt_start_date">Start Date:</label>
				<input type="text" id="wpvt_start_date" name="wpvtmeta[wpvt_start_date]" class="datepicker" value="<?php echo $meta['wpvt_start_date'][0]; ?>" />
			</div>
			<div class="wpvt-metabox-item">
				<label for="wpvt_end_date">End Date:</label>
				<input type="text" id="wpvt_end_date" name="wpvtmeta[wpvt_end_date]" class="datepicker" value="<?php echo $meta['wpvt_end_date'][0]; ?>" />
			</div>
			<div class="wpvt-metabox-item">
				<label for="wpvt_video">Video Embed:</label>
				<input type="text" id="wpvt_video" class="longinput" name="wpvtmeta[wpvt_video]" value="<?php echo $meta['wpvt_video'][0]; ?>" />
			</div>
			<div class="wpvt-metabox-item">
				<label for="wpvt_video_caption">Video Caption:</label>
				<input type="text" id="wpvt_video_caption" class="longinput" name="wpvtmeta[wpvt_video_caption]" value="<?php echo $meta['wpvt_video_caption'][0]; ?>" />
			</div>
			
			<input type="submit" class="button" name="wpvt_meta_submit" value="Save ScrapBook Data" />
		</div>
		<?php
	}
	
	/* Save Meta Data */
	function wpvt_save_wpvt_meta($post_id, $post) {
		// verify this came from the our screen and with proper authorization,
			// because save_post can be triggered at other times 
			if ( !wp_verify_nonce( $_POST['wpvt_noncename'], plugin_basename(__FILE__) )) {
				return $post->ID;
			}
			// Is the user allowed to edit the post or page?
			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;			
		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.
		// Serialize and save.
		$wpvt_meta = $_POST['wpvtmeta'];
		
		// Add values of $events_meta as custom fields
		foreach ($wpvt_meta as $key => $value) { // Cycle through the $events_meta array!
			if( $post->post_type == 'revision' ) return; // Don't store custom data twice
			if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
				update_post_meta($post->ID, $key, $value);
			} else { // If the custom field doesn't have a value
				add_post_meta($post->ID, $key, $value);
			}
			if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
		}
	}
	
	/*Insert into Database*/
	function insertarecord($user_ID,$string,$type,$book_id=false,$group_id=false)
	{
		global $wpdb;
		global $bp;
		if($type=='scrapbook')
		{
			//test if new or update
			$previous_sb = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ltpmy_scrapbooks  WHERE uid = '".$user_ID."'"));
			if(!empty($previous_sb))
			{
				//update
				$wpdb->get_results("UPDATE {$wpdb->prefix}ltpmy_scrapbooks SET json_string='$string' WHERE uid='".$user_ID."'");
			} 
		} elseif($type=='fanbook'){
			//test if new or update
			$previous_fb = $wpdb->get_var($wpdb->prepare("SELECT managing_post_id FROM {$wpdb->prefix}ltpmy_fanbooks WHERE id = '".$book_id."'"));
			if($previous_fb)
			{
				//update
				$wpdb->get_results("UPDATE {$wpdb->prefix}ltpmy_fanbooks SET json_string='$string' WHERE id='".$book_id."'");
			} 
		} elseif($type=='groupbook'){
			//test if new or update
			$previous_gb = $wpdb->get_var($wpdb->prepare("SELECT managing_post_id FROM {$wpdb->prefix}ltpmy_groupbooks  WHERE id = '".$book_id."'"));
			if($previous_gb)
			{
				//update
				$wpdb->get_results("UPDATE {$wpdb->prefix}ltpmy_groupbooks SET json_string='$string' WHERE id='".$book_id."'");
			}
		}
	}
	/* Save JSON file */
	function wpvt_update_json($author_id, $type,$book_id=false) 
	{
		global $post;
		global $wpdb;		
			
		//Make sure cover is up to date
		$cover_post_id =  managing_PostID($author_id,'scrapbook');//managing post ID
		if($cover_post_id)
		{
			$cover_post = get_post($cover_post_id); 
			$book_title = $cover_post->post_title;
			$book_desc = $cover_post->post_content ;
			$image = wp_get_attachment_image_src( get_post_thumbnail_id($cover_post_id));
		} else {
			//default
			$book_title = 'Create a Cover Title';
			$book_desc = "And don't forget a Cover Image and brief description" ;
		}
		$string = '
		 {
				"timeline":
				{
					"headline":"'.$book_title.'",
					"type":"default",
					"text":"<p>'.$book_desc.'</p>",
					"asset": {
						"media":"'.$image[0].'"
					},
					"date": [
		';
		
		// TODO: APPEND DATE ENTRIES
		
		$args = array( 'post_status' => array( 'publish', 'future' ) ,'orderby' => 'date' ,'order' => 'ASC', 'post_type' =>'scrapbook' ,'author' => $author_id ,'posts_per_page'=> -1 );
		$loop = new WP_Query( $args );
		$last = ($loop->post_count <= get_option('posts_per_page')) ? $loop->post_count : get_option('posts_per_page');
		
		while ( $loop->have_posts() ) :
			$loop->the_post();
			$entry = new wpvtEntry( $post );
			$string .= stripslashes($entry->toJSON());
				
			if($loop->current_post < $loop->post_count - 1) {
				$string .= ',';
			}
			wp_reset_postdata();	
		endwhile;
		
		$string .= '
				]
				}
			}
		';			
		//New way - send to database
		$json_string = mysql_real_escape_string($string);
		insertarecord($author_id,$json_string,$type,$book_id,$group_id);
		return $json_string;
	}
	function wpvt_update_FBjson($author_id, $type,$book_id) 
	{
		global $post;
		global $wpdb;		
			
		//Make sure cover is up to date
		$cover_post_id = managing_PostID($author_id,'fanbook',$book_id);//managing post ID
		if($cover_post_id)
		{
			$cover_post = get_post($cover_post_id); 
			$book_title = $cover_post->post_title;
			if(empty($book_title))
			{
				//default
				$book_title = 'Create a Cover Title';
			}
			$image_id = get_post_thumbnail_id($cover_post_id);
			$book_desc = $cover_post->post_content ;
			if(empty($book_descr) && empty($image_id))
			{
				//default
				$book_desc = "You can update your cover image by hovering your mouse cursor over Edit Entries above, and clicking Update Cover" ;
			}
			if(empty($image_id))
			{
				//default
				mom_defaultcovers($cover_post_id);
				$image = wp_get_attachment_image_src( get_post_thumbnail_id($cover_post_id));
			} else {
				$image = wp_get_attachment_image_src( get_post_thumbnail_id($cover_post_id));
			}
		}
		$string = '
		 {
				"timeline":
				{
					"headline":"'.$book_title.'",
					"type":"default",
					"text":"<p>'.$book_desc.'</p>",
					"asset": {
						"media":"'.$image[0].'"
					},
					"date": [
		';
		
		// TODO: APPEND DATE ENTRIES
		$parent_post_id = $wpdb->get_var($wpdb->prepare("SELECT managing_post_id FROM {$wpdb->prefix}ltpmy_fanbooks  WHERE id = '".$book_id."'"));
		$args = array( 'post_status' => array( 'publish', 'future' ) ,'orderby' => 'date' ,'order' => 'ASC', 'post_type' => 'm_fanbook' ,'author' => $author_id ,'posts_per_page'=> -1 );
		$loop = new WP_Query( $args );
		$last = ($loop->post_count <= get_option('posts_per_page')) ? $loop->post_count : get_option('posts_per_page');
		
		while ( $loop->have_posts() ) :
			$loop->the_post();
			if ($post->post_parent == $parent_post_id) 
			{
				$entry = new wpvtEntry( $post );		
			
				$string .= stripslashes($entry->toJSON());
					
				if($loop->current_post < $loop->post_count - 1) {
					$string .= ',';
				}
				wp_reset_postdata();
			}				
		endwhile;
		
		$string .= '
				]
				}
			}
		';			
		//New way - send to database
		$json_string = mysql_real_escape_string($string);
		insertarecord($author_id,$json_string,$type,$book_id,$group_id);
	}
	
	function wpvt_update_allFB() 
	{
		global $post;
		global $wpdb;
		
		$people = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}users");
		$authors = objectToArray($people);
		$i = 0;
		foreach($authors as $author_id)
		{
			$library = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}ltpmy_fanbooks WHERE uid = '".$author_id[ID]."'");
			if(!empty($library))
			{
				//create useable array
				$books = objectToArray($library);
				foreach($books as $book_id)
				{
					wpvt_update_FBjson($author_id[ID], "fanbook",$book_id[id]);
				}
			}
		}
		return $array;
	}
	
    function wpvt_update_GBjson($author_id, $type,$book_id) 
	{
		global $post;
		global $wpdb;		
			
		//Make sure cover is up to date
		$parent_post_id = managing_PostID($author_id,$type,$book_id);//managing post ID
		if($parent_post_id)
		{
			$cover_post = get_post($parent_post_id); 
			$book_title = $cover_post->post_title;
			$book_desc = $cover_post->post_content ;
			$image = wp_get_attachment_image_src( get_post_thumbnail_id($parent_post_id));
		} else {
			//default
			$book_title = 'Create a Cover Title';
			$book_desc = "And don't forget a Cover Image and brief description" ;
		}
		$string = '
		 {
				"timeline":
				{
					"headline":"'.$book_title.'",
					"type":"default",
					"text":"<p>'.$book_desc.'</p>",
					"asset": {
						"media":"'.$image[0].'"
					},
					"date": [
		';
		
		// TODO: APPEND DATE ENTRIES                
		$args = array( 'post_status' => array( 'publish', 'future' ) ,'orderby' => 'date' ,'order' => 'ASC', 'post_type' => 'm_groupbook' ,'parent_post' => $parent_post_id ,'posts_per_page'=> -1 );
		$loop = new WP_Query( $args );
		$last = ($loop->post_count <= get_option('posts_per_page')) ? $loop->post_count : get_option('posts_per_page');
		while ( $loop->have_posts() ) :
			$loop->the_post();
			if ($post->post_parent == $parent_post_id) 
			{
				$entry = new wpvtEntry( $post );		
			
				$string .= stripslashes($entry->toJSON());
					
				if($loop->current_post < $loop->post_count - 1) {
					$string .= ',';
				}
				wp_reset_postdata();
			}				
		endwhile;	
		
		$string .= '
				]
				}
			}
		';			
		//New way - send to database
		$json_string = mysql_real_escape_string($string);
		insertarecord($author_id,$json_string,$type,$book_id);
		return $parent_post_id;
	}
	/* Shortcodes
	function wpvt_sc_func($atts)
	{
		global $post;	
		global $wpdb;
		extract(shortcode_atts(array("author_id" => 0,"type" => 0,"book_id" => 0), $atts));
		if($author_id != 0)
		{			
			//now work with $author_id;
			$author_info = get_userdata($author_id);	
			
			//work with database
			if($type=='scrapbook')
			{
				//test if new or update
				$users_file = $wpdb->get_var($wpdb->prepare("SELECT json_string FROM {$wpdb->prefix}ltpmy_scrapbooks  WHERE uid = '".$author_id."'"));				
			} elseif($type=='fanbook'){
				//test if new or update
				$users_file = $wpdb->get_var($wpdb->prepare("SELECT json_string FROM {$wpdb->prefix}ltpmy_fanbooks  WHERE uid = '".$author_id."' AND id = '".$book_id."'"));
			} elseif($type=='groupbook'){
				//test if new or update
				$users_file = $wpdb->get_var($wpdb->prepare("SELECT json_string FROM {$wpdb->prefix}ltpmy_groupbooks  WHERE group_id = '".$author_id."' AND id = '".$book_id."'"));
			}
			
			if ($users_file) 
			{		
				$options = get_option('wpvt_options');
				$start_at_end = ($options['start_at_end'] == 1) ? 'true' : 'false';
				$hash_bookmark = ($options['hash_bookmark'] == 1) ? 'true' : 'false';
				$managing_post_id = managing_PostID($author_id,$type,$book_id);
				$s = $wpdb->get_results("SELECT start,font,language,maptype FROM {$wpdb->prefix}ltpmy_sb_settings WHERE managing_post_id='".$managing_post_id."' ");
				if(empty($s))
				{
					$font = 'Georgia-Helvetica';
					$lang= 'en';
					$start_at_end = 'false';
					$maptype = 'watercolor';
				} else {					
					$font = $s[0]->font;
					$lang=$s[0]->language;
					$maptype=$s[0]->maptype;
					$start_at_end = $s[0]->start;
				}
				
				// NOW I JUST NEED TO FETCH ALL THE POSTS, ARRANGE THE INFO INTO JSON THEN PRINT THE JAVASCRIPT CALL.
				
				echo '
					<div id="timeline-embed"></div>
					<script type="text/javascript">
					var timeline_config = {
						width: "'.$options['width'].'",
						height: "'.$options['height'].'",
						source: '.$users_file.',
						start_at_end: '.$start_at_end.',
						hash_bookmark: '.$hash_bookmark.',
						font:"'.$font.'",
						lang:"'.$lang.'",
						maptype:"'.$maptype.'",
						css: "'.plugins_url( 'css/timeline.css', __FILE__ ).'",
						js: "'.plugins_url( 'js/timeline-min.js', __FILE__ ).'"
					}
					</script>
					<script type="text/javascript" src="' . plugins_url( 'js/storyjs-embed.js', __FILE__ ).'"></script>
				';
			} else {
				return 1;
			} 
		} else {			
			return 2;		
		}
	}
	add_shortcode('WPVT', 'wpvt_sc_func');
	*/
	function getSBData($author_id,$type,$book_id)
    {
		global $wpdb;	
			
		//work with database
		if($type == 'scrapbook')
		{
			//test if new or update
			$users_file = $wpdb->get_var($wpdb->prepare("SELECT json_string FROM {$wpdb->prefix}ltpmy_scrapbooks  WHERE uid = '".$author_id."'"));				
		} elseif($type == 'fanbook'){
			//test if new or update
			$users_file = $wpdb->get_var($wpdb->prepare("SELECT json_string FROM {$wpdb->prefix}ltpmy_fanbooks  WHERE uid = '".$author_id."' AND id = '".$book_id."'"));
		} elseif($type == 'groupbook'){
			//test if new or update
			$users_file = $wpdb->get_var($wpdb->prepare("SELECT json_string FROM {$wpdb->prefix}ltpmy_groupbooks WHERE id = '".$book_id."'"));
		}
		if ($users_file) 
		{	                                
			$options = get_option('wpvt_options');
			$start_at_end = ($options['start_at_end'] == 1) ? 'true' : 'false';
			$hash_bookmark = ($options['hash_bookmark'] == 1) ? 'true' : 'false';
			$s = returnSbSettings($author_id,$type,$book_id);
			if(empty($s))
			{
				$font = 'Georgia-Helvetica';
				$lang= 'en';
				$start_at_end = 'false';
				$maptype = 'toner';
			} else {					
				$font = $s[0]->font;
				$lang=$s[0]->language;
				$maptype=$s[0]->maptype;
				$start_at_end = $s[0]->start;
			}
				
			// NOW I JUST NEED TO FETCH ALL THE POSTS, ARRANGE THE INFO INTO JSON THEN PRINT THE JAVASCRIPT CALL.
            $array = array( 'width'=> $options['width'], 'height'=>$options['height'], 'jsonString'=>$users_file, 'start'=>$start_at_end, 'hash'=>$hash_bookmark, 'font'=>$font, 'lang'=>$lang, 'maptype'=>$maptype);
        } else {
            $array = array('error' => 1);
        }
            return $array;
    }
	
?>