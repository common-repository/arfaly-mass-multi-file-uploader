<?php


function Arfaly_Report_Error($error)
{
	$json = array(
		"msg" => 'false',
		"error" => $error
	);

	echo json_encode($json);
	die();
}

// Process multi-images
function itech_submit_arfaly()
{
    // Sanitize the whole input
    $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    
    $prefix = '_multi_';
    global $itech_globals;
    $nonceValidation = false;
    $post_id = "";
    $allowGuests = false;
    $allowedExts = array();
    
    if(isset($_POST['closify-id']))
    {
      $post_id = $_POST['closify-id'];
      // Get closify meta information
      $meta = get_post_meta( $post_id );

      if(isset($meta[$prefix.'allow_guests']))
      {
        $allowGuests = true;
      }
    }else{
      $allowGuests = true;
    }
    
    if(!is_user_logged_in() && !$allowGuests)
    {
      Arfaly_Report_Error("You do not have permission!");
    }
    
    global $current_user;
    wp_get_current_user();
    
    // Nonce security validation
    if(isset($_POST['nonce']))
    {
      $nonceValidation = wp_verify_nonce( $_POST['nonce'], $itech_globals['nonce_action'] );
      if(!$nonceValidation)
      {
        Arfaly_Report_Error("You violated a security check!");
      }
    }else{
      Arfaly_Report_Error("Security parameter is missing!");
    }
    
    if(isset($meta[$prefix.'image_formats']))
    {
      $allowedExts = unserialize($meta[$prefix.'image_formats'][0]);
    }else{
      $allowedExts = array();
    }
    
    // Default max file size
    $maxFileSize = 1024 * 1024 * 1; // Max 10MB
    
    if(isset($meta[$prefix.'max_file_size']))
    {
      $maxFileSize = intval($meta[$prefix.'max_file_size'][0]);
      $maxFileSize = $maxFileSize * 1048576;
    }
    
    $localFileDestination     = ITECHPLGUINURI.$itech_globals['destination_folder'].DIRECTORY_SEPARATOR; // From server side, define the uploads folder url 
    
    ########################################

    // Check if it is a delete command
    if(isset($_POST['command']) && $_POST['command']=='delete')
    {
        if(!isset($_POST['raqmkh']))
        {
            $json = array();
            $json['data'] = "Oops. Something wrong with deletion!";
            $json['status'] = 'false';

            Arfaly_Report_Error($json['data']);
        }
        
        $att_del_id = base64_decode($_POST['raqmkh']);
        
        // Handle file deletion here
        $result = wp_delete_post( $att_del_id, true );

        if($result == "false"){
          $json['data'] = "The object couldn't be deleted!";
          $json['status'] = 'false';

          Arfaly_Report_Error($json['data']);
        }else
        {
          echo base64_decode($_POST['arfalyfn']).' Has been deleted!';
          die();
        }
    }

    // Create uploads folder if doesn't exist
    if (!file_exists($localFileDestination)) {
        mkdir($localFileDestination, 0766, true);
    }

    // Enforce extensions to be converted into lower case
    $allowedExts = array_map('strtolower', $allowedExts);

    // Business Logic
    $allowedM = false;
    $allowedS = false;
    $json = array(
        "status" => 'false',
    );

    if(count($allowedExts)>0){
      // Check mime and apply to-lower case comparison to avoid case sensitive comparing
      foreach($allowedExts as $value)
      {
        if(array_key_exists($value,$extReference))
        {
          foreach($extReference[$value] as $mime)
          {
            if(strtolower($_FILES["SelectedFile"]["type"]) == strtolower($mime))
            {
                $allowedM = true;
            }
          }
        }
      }

      if(!$allowedM) Arfaly_Report_Error("Unsupported file type!");
    }else{
      $allowedM = true;
    }
    
    if($_FILES["SelectedFile"]["size"] < $maxFileSize)
    {
        $allowedS = true;
    }else{
        $json['data'] = "File size has exceeded the limit (".$maxFileSize.")!";
        Arfaly_Report_Error($json['data']);
    }

    if( $allowedM && $allowedS)
    {
      if ($_FILES["SelectedFile"]["error"] > 0) {
        Arfaly_Report_Error("Return Code: " . $_FILES["SelectedFile"]["error"]);
      } else {
        // add the function above to catch the attachments creation
        add_action('add_attachment','arfaly_new_multi_file_attachment');

        // Save image to library and attach it to the post
        // OLD Method::media_sideload_image($targetImgURLPath, $post_id, 'Closify ['.$title.'] Uploaded by: '.$current_user->display_name );

        $att_id = media_handle_upload( "SelectedFile", $post_id, array(), array('test_upload'=>false,'test_form'=>false) );

        if ( is_wp_error( $att_id ) ) {
          remove_action('add_attachment','arfaly_new_multi_file_attachment');
          Arfaly_Report_Error($att_id->get_error_message());
        }

        $image_attributes = wp_get_attachment_url( $att_id ); // returns an array
        if( $image_attributes ) {
          $targetImgURLPath = $image_attributes;
        }else
        {
          remove_action('add_attachment','arfaly_new_multi_file_attachment');
          Arfaly_Report_Error('Error fetching image url!');
        }

        // we have the Image now, and the function above will have fired too setting the thumbnail ID in the process, so lets remove the hook so we don't cause any more trouble 
        remove_action('add_attachment','arfaly_new_multi_file_attachment');

        $json = array(
            "status" => 'true',
            "data" => $_FILES["SelectedFile"]["name"].' Has been successfully uploaded!',
            "attid" => $att_id,
            "newFileName" => $_FILES["SelectedFile"]["name"],
            "fullPath" => $targetImgURLPath
        );
      }
    }
    
    // Print out results
    echo json_encode($json);

    die();
}

function arfaly_new_multi_file_attachment($att_id){
  
  arfaly_save_images_for_user(true, $att_id);
  
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
      if(!is_array($is_meta[$prefix][$prefix.'_'.$p->post_parent]))
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
      Arfaly_Report_Error("Problem while attaching image to the user!");
    }
  }
  
  // the post this was sideloaded into is the attachments parent!
  return;
 
}
?>