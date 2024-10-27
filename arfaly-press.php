<?php
/**
 * Arfaly - Frontend Mass Uploader
 *
 * @package   arfaly_mass_uploader
 * @author    Abdulrhman Elbuni
 * @link      http://www.itechflare.com/
 * @copyright 2014-2015 iTechFlare
 *
 * @wordpress-plugin
 *
 * Plugin Name: Arfaly Mass - Free Multi File Uploader
 * Plugin URI:  http://www.itechflare.com/
 * Description: Free professional & elegant front end multi file uploader. Arfaly allows you to upload, delete, preview and manage digital files with ease.
 * Version:     1.1.1
 * Author:      Abdulrhman Elbuni
 * Author URI:  http://www.itechflare.com/
 * Text Domain: arfaly-uploader
 * Domain Path: /languages
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// This line is to configure PHP to allow using memeory for image processing operation
ini_set('memory_limit', '-1');
// Globals
require_once( plugin_dir_path( __FILE__ ).'includes/itechflare_globals.php' );
define('ITECHARFALYPLGUINURI',plugin_dir_path( __FILE__ ));
define('ITECHARFALYPLUGINURL',plugins_url( '',__FILE__));
define('ARFALY_NONCE','itech_arfaly_plugin');

// Invoke global plugin option array
global $itech_arfaly_globals;

// Advance meta boxes
require ITECHARFALYPLGUINURI . 'includes/cmb/arfaly-meta.php';
require ITECHARFALYPLGUINURI . 'includes/arfaly-lib/arfaly-upload-list-table.php';
require ITECHARFALYPLGUINURI . 'includes/arfaly-lib/settings-api/class.settings-api.php';
require ITECHARFALYPLGUINURI . 'includes/arfaly-lib/functions.php';
require ITECHARFALYPLGUINURI . 'includes/arfaly-lib/arfaly-settings.php';

// Required files for registering the post type and taxonomies.
require ITECHARFALYPLGUINURI . 'includes/arfaly-type.php';
require ITECHARFALYPLGUINURI . 'includes/arfaly-post-type-registrations.php';

// Instantiate registration class, so we can add it as a dependency to main plugin class.
$arfaly_post_type_registrations = new Arfaly_Post_Type_Registrations;

// Instantiate main plugin file, so activation callback does not need to be static.
$arfaly = new Arfaly_Type( $arfaly_post_type_registrations );
// Register callback that is fired when the plugin is activated.
register_activation_hook( __FILE__, array( $arfaly, 'activate' ) );

// Initialise registrations for post-activation requests.
$arfaly_post_type_registrations->init();


class Arfaly_Uploader
{
  private $allowed_mimes;
  public $settings;
  public $settings_slug = 'arfaly_settings';
  private $manage_permissions;
  private $post_type = 'arfaly';
  private $meta_prefix = '_arfaly_';
  
  public function __construct()
  {
    // Init
    add_action( 'init', array( $this, 'arfaly_init' ) );
  }
  
  function arfaly_init()
  {
    global $itech_arfaly_globals;
    
    // Initiate database options
    if(get_option( $itech_arfaly_globals['option_name'] ) == false)
    {
      add_option( $itech_arfaly_globals['option_name'],'none' , '', 'no' );
    }

    load_plugin_textdomain( 'arfaly-uploader', false, ITECHARFALYPLGUINURI . '/languages/' );

    // Add arfaly upload manage menu
    add_action('admin_menu', array($this, 'arfaly_add_menu_items'));
    
    // Static assets
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        
    // Hooking to wp_ajax
    add_theme_support( 'post-thumbnails' );
    add_action('wp_ajax_'.ARFALY_NONCE,array( $this, 'itech_submit_arfaly'));
    add_action('wp_ajax_nopriv_'.ARFALY_NONCE,array( $this, 'itech_submit_arfaly'));

    add_action( 'wp_ajax_approve_arfaly', array( $this, 'approve_media' ) );
    add_action( 'wp_ajax_delete_arfaly', array( $this, 'delete_post' ) );

    // Add shortcode
    add_shortcode( 'arfaly', array( $this, 'arfaly_func'));

    // Add client side scripts
    add_action( 'wp_enqueue_scripts', array( $this, 'load_arfaly_libraries') );
    add_action( 'new_files_uploaded', array( $this, 'new_files_uploaded') );
    
    // Customize mimes
    add_filter('upload_mimes', array( $this, 'custom_arfaly_upload_mimes') );
    
    add_filter( 'manage_edit-'.$this->post_type.'_columns', array( $this, 'set_custom_edit_arfaly_columns') );
    add_action( 'manage_'.$this->post_type.'_posts_custom_column' , array( $this, 'custom_arfaly_column'), 10, 2 );

    add_filter( 'posts_where', array( $this, 'filter_posts_where' ) );
    
    // Since 4.01 we need to explicitly disable texturizing of shortcode's inner content
    add_filter( 'no_texturize_shortcodes', array( $this, 'filter_no_texturize_shortcodes' ) );

    $this->manage_permissions = apply_filters( 'arfaly_manage_permissions', 'edit_posts');
    
// Debug mode filter
    $this->is_debug = (bool) apply_filters( 'arfaly_is_debug', defined( 'WP_DEBUG' ) && WP_DEBUG );

    $this->settings = array_merge( $this->settings_defaults(), (array) get_option( $this->settings_slug, $this->settings_defaults() ) );
	register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
    
  }

  function option_updated()
  {
    // When option gets updated. Do something
  }
  
  function filter_no_texturize_shortcodes( $shortcodes ) {
      $shortcodes[] = 'arfaly';
      return $shortcodes;
  }
  
  function custom_arfaly_upload_mimes ( ) {

    // Use wp_get_mime_types if available, fallback to get_allowed_mime_types()
    $mime_types = function_exists( 'wp_get_mime_types' ) ? wp_get_mime_types() : get_allowed_mime_types() ;
    $arfaly_mime_types = itech_arfaly_get_mime_types();
    // Workaround for IE
    $mime_types['jpg|jpe|jpeg|pjpg'] = 'image/pjpeg';
    $mime_types['png|xpng'] = 'image/x-png';
    // Iterate through default extensions
    foreach ( $arfaly_mime_types as $extension => $details ) {
        // Skip if it's not in the settings
        if ( !in_array( $extension, $this->settings['enabled_files'] ) )
            continue;

        // Iterate through mime-types for this extension
        foreach ( $details['mimes'] as $ext_mime ) {
            $mime_types[ $extension . '|' . $extension . sanitize_title_with_dashes( $ext_mime ) ] = $ext_mime;
        }
    }
    // Configuration filter: fu_allowed_mime_types should return array of allowed mime types (see readme)
    $mime_types = apply_filters( 'arfaly_allowed_mime_types', $mime_types );

    foreach ( $mime_types as $ext_key => $mime ) {
        // Check for php just in case
        if ( false !== strpos( $mime, 'php' ) )
            unset( $mime_types[$ext_key] );
    }
    
    return $mime_types;
  }
  
  // Add the main shortcode for Arfaly [closify id="<id>"]
  function arfaly_func( $atts ) {
    extract( shortcode_atts( array(
            'id' => 0
            ),
            $atts ) );

    return $this->building_arfaly_container($id);
  }

  // Enqueue scripts
  function load_arfaly_libraries() {
    // Global plugin option variable
    global $itech_arfaly_globals;

    wp_enqueue_script(
            'closify-multi-script',
            plugins_url( 'assets/js/closify-multi-min.js' , __FILE__ ), array('jquery'), $itech_arfaly_globals['version'], true
        );
    wp_enqueue_style( 'closify-default',
            plugins_url( 'assets/css/style.css' , __FILE__ ));
  }

  function building_arfaly_container($id)
  {
    // Get closify meta information
    $meta = get_post_meta( $id );
    $allowGuests = false;

    if(isset($meta[$this->meta_prefix.'allow_guests']))
    {
      $allowGuests = true;
    }

    if(!is_user_logged_in() && !$allowGuests)
    {
      return;
    }

    global $current_user;
    wp_get_current_user();

    static $count = 0;
    static $previous_post_id = 0;

    // This will fix blog page counter reset issue
    if($previous_post_id != $id)
    {
      $count = 0; 
    }

    $closify_info = array();

    // Check user's meta for any pre-stored info
    $existingImg = get_user_meta( $current_user->ID, 'closify_img_'.$id, true ); 

    if(isset($existingImg) && !empty($existingImg) && isset($existingImg["closify-".$id."-".$count]))
    {
      $img = wp_get_attachment_url($existingImg["closify-".$id."-".$count]);

      if($img == "")
      {
        delete_user_meta($current_user->ID, 'closify_img_'.$id );
      }

      $closify_info['startWithThisImg'] = $img;
    }

    if(isset($meta[$this->meta_prefix.'debug']))
      $closify_info['debug'] = ($meta[$this->meta_prefix.'debug'][0]=='on')?'true':'false';

    if(isset($meta[$this->meta_prefix.'logo_color']))
      $closify_info['logoColor'] = $meta[$this->meta_prefix.'logo_color'][0];

    if(isset($meta[$this->meta_prefix.'border_color']))
      $closify_info['borderColor'] = $meta[$this->meta_prefix.'border_color'][0];

    if(isset($meta[$this->meta_prefix.'label_color']))
      $closify_info['labelColor'] = $meta[$this->meta_prefix.'label_color'][0];

    if(isset($meta[$this->meta_prefix.'text_color']))
      $closify_info['textColor'] = $meta[$this->meta_prefix.'text_color'][0];

    if(isset($meta[$this->meta_prefix.'image_preview']))
      $closify_info['disablePreview'] = ($meta[$this->meta_prefix.'image_preview'][0]=='on')?'true':'false';
    
    if(isset($meta[$this->meta_prefix.'target_debug']))
    {
      if($meta[$this->meta_prefix.'target_debug']!="")
        $closify_info['targetOutput'] = $meta[$this->meta_prefix.'target_debug'][0];
    }

    if(isset($meta[$this->meta_prefix.'label']))
    {
        $closify_info['label'] = $meta[$this->meta_prefix.'label'][0];
    }

    $closify_info['url'] = admin_url( 'admin-ajax.php' );
    $closify_info['nonce'] = wp_create_nonce( ARFALY_NONCE );
    $closify_info['action'] = ARFALY_NONCE;
    $closify_options = json_encode($closify_info);

    // Removing double quotation from the keys
    $closify_options = preg_replace('/"([a-zA-Z]+[a-zA-Z0-9_]*)":/','$1:',$closify_options);

    // *** Pass loading gif and background photo dynamically here

    $closifyId = 'multi-'.$id.'-'.$count;
    // to pass it into the custom javascript script
    $output = '<div id="'.$closifyId.'" closify-idx="'.$count.'" closify-id="'.$id.'"></div>';
    $output = $output . '<script type="text/javascript">
          jQuery(document).ready(function(){
            jQuery("#'.$closifyId.'").arfaly('.$closify_options.');
          });
        </script>';

    $count++;
    $previous_post_id = $id;

    return $output;
  }

  function set_custom_edit_arfaly_columns($columns) {
      global $itech_arfaly_globals;

      unset( 
        $columns['taxonomy-arfaly_category'],
        $columns['taxonomy-arfaly_tag'],
        $columns['comments']
      );

      $columns['author'] = __( 'Author', $itech_arfaly_globals['domain'] );
      $columns['shortcode'] = __( 'Shortcode', $itech_arfaly_globals['domain'] );

      return $columns;
  }
  
  function custom_arfaly_column( $column, $post_id ) {
    global $itech_arfaly_globals;

    $post = get_post($post_id);

      switch ( $column ) {
          case 'quality' :
              $quality = get_post_meta( $post_id, '_closify_quality', true);
              if ( is_string( $quality ) )
                  echo $quality;
              else
                  _e( 'Unable to get quality', $itech_arfaly_globals['domain'] );
              break;
          case 'shortcode' :
                echo '<strong>['.$this->post_type.' id="'.$post->ID.'"]</strong>';
              break;

      }
  }
  
  /* Event section */
  function new_files_uploaded()
  {
    // Notify the admin via email
	$this->_notify_admin();
    // Do something
  }
  
  function arfaly_report_error($error)
  {
      $json = array(
          "msg" => 'false',
          "error" => $error
      );

      echo json_encode($json);
      die();
  }

  /**
    * Notify site administrator by email
    */
  function _notify_admin( ) {
      // Email notifications are disabled, or upload has failed, bailing
      if ( ! ( 'on' == $this->settings['notify_admin'] ) )
          return;

      // TODO: It'd be nice to add the list of upload files
      $to = !empty( $this->settings['notification_email'] ) && filter_var( $this->settings['notification_email'], FILTER_VALIDATE_EMAIL ) ? $this->settings['notification_email'] : get_option( 'admin_email' );
      $subj = __( 'New Arfaly content was uploaded on your site', 'arfaly-uploader' );
      wp_mail( $to, $subj, $this->settings['admin_notification_text'] );
  }
    
  // Process multi-images
  function itech_submit_arfaly()
  {
      // Sanitize the whole input
      $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

      $nonceValidation = false;
      $post_id = "";
      $allowGuests = false;
      
      if(isset($_POST['closify-id']))
      {
        $post_id = $_POST['closify-id'];
        // Get closify meta information
        $meta = get_post_meta( $post_id );

        if(isset($meta[$this->meta_prefix.'allow_guests']))
        {
          $allowGuests = true;
        }
      }else{
        $allowGuests = true;
      }

      if(!is_user_logged_in() && !$allowGuests)
      {
        $this->arfaly_report_error("You do not have permission!");
      }

      wp_get_current_user();

      // Nonce security validation
      if(isset($_POST['nonce']))
      {
        $nonceValidation = wp_verify_nonce( $_POST['nonce'], ARFALY_NONCE );
        if(!$nonceValidation)
        {
          $this->arfaly_report_error("You violated a security check!");
        }
      }else{
        $this->arfaly_report_error("Are you trying to hack me ?");
      }
      
      // Check if it is a delete command
      if(isset($_POST['command']) && $_POST['command']=='delete')
      {
          if(!isset($_POST['raqmkh']))
          {
              $json = array();
              $json['data'] = "Oops. Something wrong with deletion!";
              $json['status'] = 'false';

              $this->arfaly_report_error($json['data']);
          }

          $att_del_id = base64_decode($_POST['raqmkh']);

          // Handle file deletion here
          $result = wp_delete_post( $att_del_id, true );

          if($result == "false"){
            $json['data'] = "The object couldn't be deleted!";
            $json['status'] = 'false';

            $this->arfaly_report_error($json['data']);
          }else
          {
            echo base64_decode($_POST['arfalyfn']).' Has been deleted!';
            die();
          }
      }

      // Default max file size
      $maxFileSize = 1024 * 1024 * 1; // Max 10MB

      $temp = explode(".", $_FILES["SelectedFile"]["name"]);
      
      // Business Logic
      $extension = strtolower(end($temp));
      $pass_extension_test = false;
      $strict_ext_array = array();
      
      if(isset($meta[$this->meta_prefix.'max_file_size']))
      {
        $maxFileSize = intval($meta[$this->meta_prefix.'max_file_size'][0]);
        $maxFileSize = $maxFileSize * 1048576;
      }

      if(isset($meta[$this->meta_prefix.'strict_extensions'])){
        $strict_extensions = $meta[$this->meta_prefix.'strict_extensions'][0];
        $strict_extensions = str_replace(' ', '', $strict_extensions);
        $strict_ext_array = explode(',', $strict_extensions);
        
        foreach($strict_ext_array as $ext)
        {
          if($ext == $extension)
          {
            $pass_extension_test = true;
            break;
          }
        }
      }else
      {
        // If there is no strict extensions, take default wp mimes
        $pass_extension_test = true;
      }
      
      if(!$pass_extension_test) $this->arfaly_report_error("Unsupported file type!");

      ########################################

      if($_FILES["SelectedFile"]["size"] > $maxFileSize)
      {
          $json['data'] = "File size has exceeded the limit (".$maxFileSize.")!";
          $this->arfaly_report_error($json['data']);
      }


      if ($_FILES["SelectedFile"]["error"] > 0) {
        $this->arfaly_report_error("Return Code: " . $_FILES["SelectedFile"]["error"]);
      } 
      else
      {
        // add the function above to catch the attachments creation
        add_action('add_attachment',array($this, 'arfaly_new_multi_file_attachment') );

        // Save image to library and attach it to the post
        // OLD Method::media_sideload_image($targetImgURLPath, $post_id, 'Arfaly ['.$title.'] Uploaded by: '.$current_user->display_name );
        $post_data = array();
        
        // Check if auto approve is been checked and generate post_data 

        $post_data = array();

        /*==================================*/

        $att_id = media_handle_upload( "SelectedFile", $post_id, $post_data, array('test_upload'=>false,'test_form'=>false) );

        if ( is_wp_error( $att_id ) ) {
          remove_action('add_attachment',array($this, 'arfaly_new_multi_file_attachment') );
          $this->arfaly_report_error($att_id->get_error_message());
        }

        $image_attributes = wp_get_attachment_url( $att_id ); // returns an array
        if( $image_attributes ) {
          $targetImgURLPath = $image_attributes;
        }else
        {
          remove_action('add_attachment',array($this, 'arfaly_new_multi_file_attachment') );
          $this->arfaly_report_error('Error fetching image url!');
        }

        // we have the Image now, and the function above will have fired too setting the thumbnail ID in the process, so lets remove the hook so we don't cause any more trouble 
        remove_action('add_attachment',array($this, 'arfaly_new_multi_file_attachment') );

        if(isset($_POST['fileIndx']) && $_POST['fileIndx']=='0')
        {
          do_action('new_files_uploaded');
        }

        $json = array(
            "status" => 'true',
            "data" => $_FILES["SelectedFile"]["name"].' Has been successfully uploaded!',
            "attid" => $att_id,
            "newFileName" => $_FILES["SelectedFile"]["name"],
            "fullPath" => $targetImgURLPath
        );
      }

      // Print out results
      echo json_encode($json);

      die();
  }

  function arfaly_new_multi_file_attachment($att_id){

    $this->arfaly_save_images_for_user(true, $att_id);

    return;
  }

  // Load images for a specific user
  function arfaly_get_closify_images_for_user($is_multi, $closify_id, $id_indx = 0, $user_id = -1)
  {
    // Closify return image object structure
    // $images = new array(
    //    'closify-single' => array(
    //        array('closify_single_{postid1}_indx' => array(
    //          'attachment_id' => 'xxx',
    //          'index' => 'xx',
    //          'post_id' => 'xxx'
    //        )),
    //        array('closify_single_{postid2}_indx' => array(
    //          'attachment_id' => 'xxx',
    //          'index' => 'xx',
    //          'post_id' => 'xxx'
    //        )),
    //    'closify-multi' => array(
    //        array('closify_multi_{postid1}' => array(
    //          array('attachment_id' => 'xxx','post_id' => 'xxx', 'index' => 'xx'), 
    //          array('attachment_id' => 'xxx','post_id' => 'xxx', 'index' => 'xx'),
    //          ....
    //        )),
    //        array('closify_multi_{postid2}' => array('image-path1', 'image-path2', ....))
    //    )
    // ); 
    global $current_user;
    $meta_prefix = "";

    if($is_multi){
      $meta_prefix = "closify_multi";
    }else{
      $meta_prefix = "closify_single";
    }

    wp_get_current_user();

    if(!$is_multi){
      // Fetch user specific image
      if($user_id != -1){
        $existingImg = get_user_meta( $user_id, $meta_prefix, true ); 
      }
      else{
        $existingImg = get_user_meta( $current_user->ID, $meta_prefix, true ); 
      }
      $attid = "";
      if(isset($existingImg) && !empty($existingImg) && isset($existingImg[$meta_prefix][$meta_prefix.'_'.$closify_id.'_'.$id_indx]['attachment_id']))
      {
        $imageDetails = $existingImg[$meta_prefix][$meta_prefix.'_'.$closify_id.'_'.$id_indx];

        return $imageDetails;
      }
    }else{
      // Fetch user specific multi images
      $existingImg = get_user_meta( $current_user->ID, $meta_prefix, true ); 
      $attid = "";
      if(isset($existingImg) && !empty($existingImg) && isset($existingImg[$meta_prefix][$meta_prefix.'_'.$closify_id]))
      {
        $imageDetails = $existingImg[$meta_prefix][$meta_prefix.'_'.$closify_id];

        return $imageDetails;
      }
    }

    return false;

  }
  
  	/**
	 * Enqueue scripts for admin
	 */
	function admin_enqueue_scripts() {
		$screen = get_current_screen();
		/**
		 * Don't try to include media script anywhere except "Manage UGC" screen
		 * Otherwise it produces JS errors, potentially breaking some post edit screen features
		 */

		if ( $screen && 'media_page_arfaly_manage_list' == $screen->base )
			wp_enqueue_script( 'media', array( 'jquery' ) );
	}

  // Save images for a specific user
  function arfaly_save_images_for_user($is_multi, $att_id)
  {
    global $current_user;
    $prefix = "";

    if($is_multi){
      $prefix = "closify_multi";
      $file_index = $_POST["fileIndx"];
    }else{
      $prefix = "closify_single";
    }

    wp_get_current_user();
    $p = get_post($att_id);

    $img_index = $_POST["closify-idx"];
    $is_meta = get_user_meta($current_user->ID, $prefix, true ); 

    // Tmp variable
    $imageDetails = array();

    $imageDetails['attachment_id'] = $att_id;
    $imageDetails['post_id'] = $p->post_parent;

    // If isMeta is an array
    if(is_array($is_meta) && count($is_meta) > 0)
    {
      if(!$is_multi){
        $imageDetails['index'] = $img_index;
        $is_meta[$prefix][$prefix.'_'.$p->post_parent."_".$img_index] = $imageDetails;
      }else{
        $imageDetails['index'] = $file_index;
        if(!is_array($is_meta[$prefix]))
        {
          $is_meta[$prefix] = array();
        }
        if(!isset($is_meta[$prefix][$prefix.'_'.$p->post_parent]) || !is_array($is_meta[$prefix][$prefix.'_'.$p->post_parent]))
        {
          $is_meta[$prefix][$prefix.'_'.$p->post_parent] = array();
        }
        array_push($is_meta[$prefix][$prefix.'_'.$p->post_parent], $imageDetails);
      }
      update_user_meta( $current_user->ID, $prefix, $is_meta );
    }else if(!is_array($is_meta)) {
      $is_meta = array();
      $meta[$prefix] = array();

      update_user_meta( $current_user->ID, $prefix, $is_meta );
      if(!$is_multi){
        $imageDetails['index'] = $img_index;
        $is_meta[$prefix][$prefix.'_'.$p->post_parent."_".$img_index] = $imageDetails;
      }else{
        if(!is_array(@$is_meta[$prefix]))
        {
          $is_meta[$prefix] = array();
        }
        $is_meta[$prefix][$prefix.'_'.$p->post_parent] = array();
        $imageDetails['index'] = $file_index;
        array_push($is_meta[$prefix][$prefix.'_'.$p->post_parent], $imageDetails);
      }
      update_user_meta( $current_user->ID, $prefix, $is_meta );
    }else {
      // First time adding an image array
      $meta = array();
      $meta[$prefix] = array();

      if(!$is_multi)
      {
        $imageDetails['index'] = $img_index;
        $meta[$prefix][$prefix.'_'.$p->post_parent.'_'.$img_index] = $imageDetails;
      }else{
        $imageDetails['index'] = $file_index;
        array_push($meta[$prefix][$prefix.'_'.$p->post_parent], $imageDetails);
      }

      if(!add_user_meta( $current_user->ID, $prefix, $meta ))
      {
        $this->arfaly_report_error("Problem while attaching image to the user!");
      }
    }

    // the post this was sideloaded into is the attachments parent!
    return;
  }
  
  /**
    * Activation hook:
    *
    * Bail if version is less than 3.3, set default settings
    */
  function activate_plugin() {
      global $wp_version;
      if ( version_compare( $wp_version, '3.3', '<' ) ) {
          wp_die( __( 'Frontend Uploader requires WordPress 3.3 or newer. Please upgrade.', 'frontend-uploader' ) );
      }

      $defaults = $this->settings_defaults();
      $existing_settings = (array) get_option( $this->settings_slug, $this->settings_defaults() );
      update_option( $this->settings_slug, array_merge( $defaults, (array) $existing_settings ) );
  }
    
  function settings_defaults() {
      $defaults = array();
      $settings = Arfaly_Settings::get_settings_fields();
      foreach ( $settings[$this->settings_slug] as $setting ) {
          $defaults[ $setting['name'] ] = $setting['default'];
      }
      return $defaults;
  }
  
  function arfaly_add_menu_items(){
     add_submenu_page('upload.php', 'Manage Uploads', ucfirst($this->post_type).' Manage Uploads', $this->manage_permissions,'arfaly_manage_list', array($this, 'arfaly_render_menu'));
  }
  
  function arfaly_render_menu(){
        
    ?><br>
    <a target="_blank" href="http://codecanyon.net/item/arfaly-press-premium-digital-information-uploader-and-manager-/11164660&ref=mindsquare"><img src="<?php echo ITECHARFALYPLUGINURL.'/assets/images/logo.png';?>"></a>
    <h3>Get the premium version if you want to extend your plugin, and review & control user uploaded files before it goes into your media library</h3>
    <h1>Arfaly Upload Management:</h1>
    <img src="<?php echo ITECHARFALYPLUGINURL.'/assets/images/media-control.png';?>" >
    <?php
    
  }
  
  	/**
	 * Since WP 3.5-beta-1 WP Media interface shows private attachments as well
	 * We don't want that, so we force WHERE statement to post_status = 'inherit'
	 *
	 * @since 0.3
	 *
	 * @param string $where WHERE statement
	 * @return string WHERE statement
	 */
	function filter_posts_where( $where ) {
		if ( !is_admin() || !function_exists( 'get_current_screen' ) )
			return $where;

		$screen = get_current_screen();
		if ( ! defined( 'DOING_AJAX' ) && $screen && isset( $screen->base ) && $screen->base == 'upload' && ( !isset( $_GET['page'] ) || $_GET['page'] != 'arfaly_manage_list' ) ) {
			$where = str_replace( "post_status = 'arfaly-mass'", "post_status = 'inherit'", $where );
		}
		return $where;
	}
    
    /**
	 * Approve a media file
	 *
	 * TODO: refactor in 0.6
	 *
	 * @return [type] [description]
	 */
	function approve_media() {
		// Check permissions, attachment ID, and nonce

		if ( false === $this->_check_perms_and_nonce() || 0 === (int) $_GET['id'] ) {
			wp_safe_redirect( get_admin_url( null, 'upload.php?page=arfaly_manage_list&error=id_or_perm' ) );
		}

		$post = get_post( $_GET['id'] );

		if ( is_object( $post ) && $post->post_status == 'arfaly-mass' ) {
			$post->post_status = 'inherit';
			wp_update_post( $post );

			do_action( 'arfaly_media_approved', $post );
			wp_safe_redirect( get_admin_url( null, 'upload.php?page=arfaly_manage_list&approved=1' ) );
		}
        
        die();
	}

	/**
	 * Delete post and redirect to referrer
	 *
	 * @return [type] [description]
	 */
	function delete_post() {
		if ( $this->_check_perms_and_nonce() && 0 !== (int) $_GET['id'] ) {
			if ( wp_delete_post( (int) $_GET['id'], true ) )
				$args['deleted'] = 1;
		}

		wp_safe_redirect( add_query_arg( $args, wp_get_referer() ) );
		exit;
	}

	/**
	 * Handles security checks
	 *
	 * @return bool
	 */
	function _check_perms_and_nonce() {
		return current_user_can( $this->manage_permissions ) && wp_verify_nonce( $_REQUEST['arfaly_nonce'], ARFALY_NONCE );
	}
    
    public function process_bulk_action($wp_media_list_table) {

        // security check!
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];

            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );

        }

        $action = $wp_media_list_table->current_action();

        switch ( $action ) {

          case 'delete':
            foreach ( (array) $_REQUEST['media'] as $post_id_delete ) {
                if ( !current_user_can( 'edit_post', $post_id_delete ) )
                    wp_die( __( 'You are not allowed to approve this file upload.' ) );

                $post = get_post( $post_id_delete );

                if ( is_object( $post ) ) {
                    wp_delete_post( $post_id_delete, true );

                    do_action( 'arfaly_media_deleted', $post );
                }else{
                  return 'No file object found';
                }
            }
            return 'Selected files has been deleted';
            break;
          case 'approve':
              
            foreach ( (array) $_REQUEST['media'] as $post_id_approve ) {
                if ( !current_user_can( 'edit_post', $post_id_approve ) )
                    wp_die( __( 'You are not allowed to approve this file upload.' ) );

                $post = get_post( $post_id_approve );

                if ( is_object( $post ) && $post->post_status == 'arfaly-mass' ) {
                    
                  $post->post_status = 'inherit';
                    
                  wp_update_post( $post );

                  do_action( 'arfaly_media_approved', $post );
                }else{
                  return 'No file object found';
                }
            }
            return 'Selected files has been approved';
            break;

          default:
              // do nothing or something else
              return;
              break;
        }

        return;
    }
}

        
// Main plugin entry
$arfaly = new Arfaly_Uploader;