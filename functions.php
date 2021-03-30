<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

 
if ( !function_exists( 'wpestate_chld_thm_cfg_parent_css' ) ):
    function wpestate_chld_thm_cfg_parent_css() {
        $parent_style = 'wpestate_style'; 
        wp_enqueue_style('bootstrap.min',get_theme_file_uri('/css/bootstrap.min.css'), array(), '1.0', 'all');  
        wp_enqueue_style('bootstrap-theme.min',get_theme_file_uri('/css/bootstrap-theme.min.css'), array(), '1.0', 'all');  
        
        $use_mimify     =   wpresidence_get_option('wp_estate_use_mimify','');
        $mimify_prefix  =   '';
        if($use_mimify==='yes'){
            $mimify_prefix  =   '.min';    
        }
        
        if($mimify_prefix===''){
            wp_enqueue_style($parent_style,get_template_directory_uri().'/style.css', array('bootstrap.min','bootstrap-theme.min'), '1.0', 'all');  
        }else{
            wp_enqueue_style($parent_style,get_template_directory_uri().'/style.min.css', array('bootstrap.min','bootstrap-theme.min'), '1.0', 'all');  
        }
        
        if ( is_rtl() ) {
           wp_enqueue_style( 'chld_thm_cfg_parent-rtl',  trailingslashit( get_template_directory_uri() ). '/rtl.css' );
    }
        wp_enqueue_style( 'wpestate-child-style',
            get_stylesheet_directory_uri() . '/style.css',
                array( $parent_style ),
                wp_get_theme()->get('Version')
        );
        
    }
endif;

load_child_theme_textdomain('wpresidence', get_stylesheet_directory().'/languages');
add_action( 'wp_enqueue_scripts', 'wpestate_chld_thm_cfg_parent_css' );

function wp_custom_js() {
    wp_enqueue_script( 'custom', get_stylesheet_directory_uri() . '/js/custom.js', array( 'jquery' ) );

   wp_localize_script('plugin_prefix_scripts', 'js_object',array('ajax_url' => admin_url('admin-ajax.php')));

}
add_action( 'wp_enqueue_scripts', 'wp_custom_js' );

function add_ajax_url(){?>
<script type="text/javascript">
    var ajaxurl = "<?php echo admin_url( 'admin-ajax.php '); ?>";
</script>
<?php 
}
add_action('wp_head', 'add_ajax_url');




function custom_meta_box_markup($object)
{
    wp_nonce_field(basename(__FILE__), "meta-box-nonce");

   
    $increment = get_option( 'increment_value' );
    $checkbox_value = get_post_meta($object->ID, "reference_number", true);

    if($checkbox_value == "")
    {
        ?>
        <input name="reference_number" type="text"  value="<?php echo $increment; ?>" readonly>
        <?php
    }
    else
    {
       
        ?>  
            <input name="reference_number" type="text"  value="<?php echo get_post_meta($object->ID, "reference_number", true); ?>" readonly>
        <?php
    }
}

function add_custom_meta_box()
{
    add_meta_box("demo-meta-box", "Reference No.", "custom_meta_box_markup", "estate_property", "side", "high", null);
}

add_action("add_meta_boxes", "add_custom_meta_box");


function save_custom_meta_box($post_id, $post, $update)
{
       // print_r($_POST); die;

    if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
        return $post_id;

    if(!current_user_can("edit_post", $post_id))
        return $post_id;

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $slug = "estate_property";
    if($slug != $post->post_type)
        return $post_id;

    
    $meta_box_text_value = "";
    $meta_box_dropdown_value = "";
    $meta_box_checkbox_value = "";

    if(isset($_POST["reference_number"]))
    {
        $meta_box_text_value = $_POST["reference_number"];
    } 
    //echo $post->post_type; print_r($_POST);die;  
    $increment = get_option( 'increment_value' );
    if(isset($increment) && !empty($increment))
    {
        $increment = $increment+1;
        update_option('increment_value', $increment);
    }
    else
    {
        $increment = 1000;
        add_option('increment_value',$increment);
    }
    update_post_meta($post_id, "reference_number", $meta_box_text_value);
}

add_action("save_post", "save_custom_meta_box", 10, 3);


add_action('wp_ajax_cvf_upload_files', 'cvf_upload_files');
add_action('wp_ajax_nopriv_cvf_upload_files', 'cvf_upload_files');

function cvf_upload_files(){
    //print_r($_POST); die;
    $parent_post_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;  // The parent ID of our attachments
    $valid_formats = array("jpg", "png", "gif", "bmp", "jpeg"); // Supported file types
    $max_file_size = 4096 * 500; // in kb
    $max_image_upload = 4096 * 500; // Define how many images can be uploaded to the current post
    $wp_upload_dir = wp_upload_dir();
    $path = $wp_upload_dir['path'] . '/';
    $count = 0;
    $user = new WP_User(get_current_user_id());

   // $parent_post_id++;
    $post_id = get_option('custom_post_image');
    $attachments = get_posts( array(
        'post_type'         => 'attachment',
        'posts_per_page'    => -1,
        'post_parent'       => $post_id,
        'exclude'           => get_post_thumbnail_id() // Exclude post thumbnail to the attachment count
    ) );

    // Image upload handler
    if( $_SERVER['REQUEST_METHOD'] == "POST" ){
       
        // Check if user is trying to upload more than the allowed number of images for the current post
        if( ( count( $attachments ) + count( $_FILES['files']['name'] ) ) > $max_image_upload ) {
            $upload_message[] = "Sorry you can only upload " . $max_image_upload . " images for each Ad";
        } else {
           
            foreach ( $_FILES['files']['name'] as $f => $name ) {
                $extension = pathinfo( $name, PATHINFO_EXTENSION );
                // Generate a randon code for each file name
                $new_filename = cvf_td_generate_random_code( 20 )  . '.' . $extension;
               
                if ( $_FILES['files']['error'][$f] == 4 ) {
                    continue;
                }
               
                if ( $_FILES['files']['error'][$f] == 0 ) {
                    // Check if image size is larger than the allowed file size
                    if ( $_FILES['files']['size'][$f] > $max_file_size ) {
                        $upload_message[] = "$name is too large!.";
                        continue;
                   
                    // Check if the file being uploaded is in the allowed file types
                    } elseif( ! in_array( strtolower( $extension ), $valid_formats ) ){
                        $upload_message[] = "$name is not a valid format";
                        continue;
                   
                    } else{
                        // If no errors, upload the file...
                        if( move_uploaded_file( $_FILES["files"]["tmp_name"][$f], $path.$new_filename ) ) {
                           
                            $count++;

                            $filename = $path.$new_filename;
                            $post_id = get_option('custom_post_image');
                            $filetype = wp_check_filetype( basename( $filename ), null );
                            $wp_upload_dir = wp_upload_dir();
                            $attachment = array(
                                'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
                                'post_mime_type' => $filetype['type'],
                                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                                'post_content'   => '',
                                'post_status'    => 'inherit'
                            );
                            // Insert attachment to the database
                            $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );

                            $arr = array(
                                'attachment_id' => $attachment_id,
                                'url' => $image_full_attributes[0],
                                'thumb' => $image_thumb_attributes[0]
                            );

                            require_once( ABSPATH . 'wp-admin/includes/image.php' );
                           
                            // Generate meta data
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                            wp_update_attachment_metadata( $attach_id, $attach_data );
                           // update_post_meta($post_id, '_attach_custom_image', $image_full_attributes[0]);
                            delete_option('custom_post_image');
                        }
                    }
                }
            }
        }
    }
    // Loop through each error then output it to the screen
    if ( isset( $upload_message ) ) :
        foreach ( $upload_message as $msg ){       
            printf( __('<p class="bg-danger">%s</p>', 'wp-trade'), $msg );
        }
    endif;
   
    // If no error, show success message
    if( $count != 0 ){
        print esc_html__('%d files added successfully!',$count);
       // printf( __('<p class = "bg-success">%d files added successfully!</p>', 'wp-trade'), $count );  
    }
   
    exit();
}

// Random code generator used for file names.
function cvf_td_generate_random_code($length=10) {
 
   $string = '';
   $characters = "23456789ABCDEFHJKLMNPRTVWXYZabcdefghijklmnopqrstuvwxyz";
 
   for ($p = 0; $p < $length; $p++) {
       $string .= $characters[mt_rand(0, strlen($characters)-1)];
   }
 
   return $string;
 
}



add_action('wp_ajax_cvf_upload_certificate_files', 'cvf_upload_certificate_files');
add_action('wp_ajax_nopriv_cvf_upload_certificate_files', 'cvf_upload_certificate_files');

function cvf_upload_certificate_files(){
    
    $parent_post_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;  // The parent ID of our attachments
    $valid_formats = array("jpg", "png", "gif", "bmp", "jpeg"); // Supported file types
    $max_file_size = 4096 * 500; // in kb
    $max_image_upload = 4096 * 500; // Define how many images can be uploaded to the current post
    $wp_upload_dir = wp_upload_dir();
    $path = $wp_upload_dir['path'] . '/';
    $_count = 0;

   // $parent_post_id++;
    
    $attachments = get_posts( array(
        'post_type'         => 'attachment',
        'posts_per_page'    => -1,
        'post_parent'       => $parent_post_id,
        'exclude'           => get_post_thumbnail_id() // Exclude post thumbnail to the attachment count
    ) );

    // Image upload handler
    if( $_SERVER['REQUEST_METHOD'] == "POST" ){
       
        // Check if user is trying to upload more than the allowed number of images for the current post
        if( ( count( $attachments ) + count( $_FILES['files']['name'] ) ) > $max_image_upload ) {
            $upload_message[] = "Sorry you can only upload " . $max_image_upload . " images for each Ad";
        } else {
           
            foreach ( $_FILES['files']['name'] as $f => $name ) {
                $extension = pathinfo( $name, PATHINFO_EXTENSION );
                // Generate a randon code for each file name
                $new_filename = cvf_td_generate_random_code_2( 20 )  . '.' . $extension;
               
                if ( $_FILES['files']['error'][$f] == 4 ) {
                    continue;
                }
               
                if ( $_FILES['files']['error'][$f] == 0 ) {
                    // Check if image size is larger than the allowed file size
                    if ( $_FILES['files']['size'][$f] > $max_file_size ) {
                        $upload_message[] = "$name is too large!.";
                        continue;
                   
                    // Check if the file being uploaded is in the allowed file types
                    } elseif( ! in_array( strtolower( $extension ), $valid_formats ) ){
                        $upload_message[] = "$name is not a valid format";
                        continue;
                   
                    } else{
                        // If no errors, upload the file...
                        if( move_uploaded_file( $_FILES["files"]["tmp_name"][$f], $path.$new_filename ) ) {
                           
                            $_count++;

                            $filename = $path.$new_filename;
                           
                            $filetype = wp_check_filetype( basename( $filename ), null );
                            $wp_upload_dir = wp_upload_dir();
                            $attachment = array(
                                'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
                                'post_mime_type' => $filetype['type'],
                                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                                'post_content'   => '',
                                'post_status'    => 'inherit'
                            );
                            // Insert attachment to the database
                            $attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

                            $arr = array(
                                'attachment_id' => $attachment_id,
                                'url' => $image_full_attributes[0],
                                'thumb' => $image_thumb_attributes[0]
                            );

                            require_once( ABSPATH . 'wp-admin/includes/image.php' );
                           
                            // Generate meta data
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                            wp_update_attachment_metadata( $attach_id, $attach_data );
                           // update_post_meta($post_id, '_attach_custom_image', $image_full_attributes[0]);
                          
                        }
                    }
                }
            }
        }
    }
    // Loop through each error then output it to the screen
    if ( isset( $upload_message ) ) :
        foreach ( $upload_message as $msg ){       
            printf( __('<p class="bg-danger">%s</p>', 'wp-trade'), $msg );
        }
    endif;
   
    // If no error, show success message
    if( $_count != 0 ){
        print esc_html__('%d files added successfully!',$_count);
       // printf( __('<p class = "bg-success">%d files added successfully!</p>', 'wp-trade'), $count );  
    }
   
    exit();
}

// Random code generator used for file names.
function cvf_td_generate_random_code_2($length=10) {
 
   $string = '';
   $characters = "23456789ABCDEFHJKLMNPRTVWXYZabcdefghijklmnopqrstuvwxyz";
 
   for ($p = 0; $p < $length; $p++) {
       $string .= $characters[mt_rand(0, strlen($characters)-1)];
   }
 
   return $string;
 
}



/*add_action( 'add_meta_boxes', 'custom_featured_mb_add' );
function custom_featured_mb_add(){
  add_meta_box( 'custom-featured-image', 'License Image', 'custom_featured_image_mb', array('estate_agent','estate_agency', 'estate_developer'), 'side', 'low' );
}

function custom_featured_image_mb($post){
   

$attachments = get_posts( array(
'post_type' => 'attachment',
'posts_per_page' => 1,
'post_parent' => $post->ID, 
'exclude'     => get_post_thumbnail_id()
) );

echo $post->ID;
if(!empty($attachments))
{

?>
   <center> <img src="<?php echo $attachments[0]->guid; ?>" height="100" width="150"></center>
<?php 
}
}*/

//add_action( 'save_post', 'custom_featured_image_mb_save' );

/* Start Contact Form  Data Save in Database */
function contactform7_before_send_mail( $wpcf7 ) {
    //set your db details
   global $wpdb;

    $form_to_DB = WPCF7_Submission::get_instance();
    if ( $form_to_DB ) 
    {
        $formData = $form_to_DB->get_posted_data();
    }

    if($_POST['request-subtype-flat'] != 'Select Subtype')
    {
        $sub_type = stripcslashes($_POST['request-subtype-flat']);
    }
    if($_POST['request-subtype-home'] != 'Select Subtype')
    {
        $sub_type = stripcslashes($_POST['request-subtype-home']);
    }
    if($_POST['request-subtype-land'] != 'Select Subtype')
    {
        $sub_type = stripcslashes($_POST['request-subtype-land']);
    }
    if($_POST['request-subtype-commercial'] != 'Select Subtype')
    {
        $sub_type = stripcslashes($_POST['request-subtype-commercial']);
    }
    if($_POST['request-subtype-event'] != 'Select Subtype')
    {
        $sub_type = stripcslashes($_POST['request-subtype-event']);
    }
    //check sub type is empty or blank
    if(!empty($sub_type) && isset($sub_type))
    {
        $sub_type_new = $sub_type;
    }
    else
    {
        $sub_type_new = "";
    }
    // check bedroom is empty or blank
    if(isset($_POST['request-bedroom']) && !empty($_POST['request-bedroom']))
    {
        $new_bedroom = stripcslashes($_POST['request-bedroom']);
    }
    else
    {
        $new_bedroom = "";
    }

    $data = array(
        'category'      => stripcslashes($_POST['request-category']),
        'type'          => stripcslashes($_POST['request-type']),
        'sub_type'      => $sub_type_new,
        'state'         => $formData['request-state'],
        'city'          => $formData['request-city'],
        'area'          => $formData['request-area'],
        'bedroom'       => $new_bedroom,
        'budget'        => $formData['request-amount'],
        'name'          => $formData['request-name'],
        'comment'       => $formData['request-comment'],
        'person_type'   => stripcslashes($_POST['request-user-type']),
        'email'         => $formData['request-email'],
        'phone'         => $formData['request-tel']
    );

    //print_r($formData); die;
    $result = $wpdb->insert( 'wp_contacts', $data, array( '%s' ));

}

add_action( 'wpcf7_mail_sent', 'contactform7_before_send_mail' );
/* End Contact Form  Data Save in Database */


add_action( 'add_meta_boxes', 'listing_image_add_metabox' );
function listing_image_add_metabox () {
    add_meta_box( 'listingimagediv', __( 'Corporate Affairs Commission Certificate (CAC)', 'text-domain' ), 'listing_image_metabox', array('estate_agent','estate_agency', 'estate_developer'), 'side', 'low');
}

function listing_image_metabox ( $post ) {
    global $content_width, $_wp_additional_image_sizes;

    $image_id = get_post_meta( $post->ID, '_listing_image_id', true );

    $old_content_width = $content_width;
    $content_width = 254;

    if ( $image_id && get_post( $image_id ) ) {

        if ( ! isset( $_wp_additional_image_sizes['post-thumbnail'] ) ) {
            $thumbnail_html = wp_get_attachment_image( $image_id, array( $content_width, $content_width ) );
        } else {
            $thumbnail_html = wp_get_attachment_image( $image_id, 'post-thumbnail' );
        }
      
        
        if ( ! empty( $thumbnail_html ) ) {
            $content = $thumbnail_html;
            $content .= '<p class="hide-if-no-js"><a href="javascript:;" id="remove_listing_image_button" >' . esc_html__( 'Remove license image', 'text-domain' ) . '</a></p>';
            $content .= '<input type="hidden" id="upload_listing_image" name="_listing_cover_image" value="' . esc_attr( $image_id ) . '" />';
        }

        $content_width = $old_content_width;
    } else {

        $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_parent' => $post->ID, 
        'exclude'     => get_post_thumbnail_id()
        ) );
        
        if(!empty($attachments) && $image_id == '')
        {
            echo '<img src='.$attachments[0]->guid.' style="width:' . esc_attr( $content_width ) . 'px;;height:auto;border:0;">';
        }
        else
        {
            $content = '<img src="" style="width:' . esc_attr( $content_width ) . 'px;height:auto;border:0;display:none;" />';
            $content .= '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set license image', 'text-domain' ) . '" href="javascript:;" id="upload_listing_image_button" id="set-listing-image" data-uploader_title="' . esc_attr__( 'Choose an image', 'text-domain' ) . '" data-uploader_button_text="' . esc_attr__( 'Set license image', 'text-domain' ) . '">' . esc_html__( 'Set license image', 'text-domain' ) . '</a></p>';
            $content .= '<input type="hidden" id="upload_listing_image" name="_listing_cover_image" value="" />';
        }   
    }

    echo $content;
}

add_action( 'save_post', 'listing_image_save', 10, 1 );
function listing_image_save ( $post_id ) {
    if( isset( $_POST['_listing_cover_image'] ) ) {
        $image_id = (int) $_POST['_listing_cover_image'];
        update_post_meta( $post_id, '_listing_image_id', $image_id );
    }
}

function featured_image_handler_enqueue() {
  global $typenow;
  if( $typenow == 'estate_agent' || $typenow == 'estate_agency' || $typenow == 'estate_developer') {
    wp_enqueue_script( 'featured-image-handler-js', get_stylesheet_directory_uri() . '/js/featured-image-handler.js', array(), '', false );
  }
}
add_action( 'admin_enqueue_scripts', 'featured_image_handler_enqueue' );

/* Add column in post type estate_agent */
add_filter( 'manage_edit-estate_agent_columns', 'override_my_columns_agent' );

if( !function_exists('alter_my_columns_agent') ):
function override_my_columns_agent( $columns ) {
    $slice=array_slice($columns,2,2);
    unset( $columns['comments'] );
    unset( $slice['comments'] );
    $splice=array_splice($columns, 2);
    $columns['estate_ID']               = esc_html__('ID','wpresidence-core');
    $columns['estate_agent_thumb']      = esc_html__('Image','wpresidence-core');
    $columns['estate_agent_verified_thumb']      = esc_html__('Licence Image','wpresidence-core');
    $columns['estate_agent_city']       = esc_html__('City','wpresidence-core');
    $columns['estate_agent_action']     = esc_html__('Action','wpresidence-core');
    $columns['estate_agent_category']   = esc_html__( 'Category','wpresidence-core');
    $columns['estate_agent_email']      = esc_html__('Email','wpresidence-core');
    $columns['estate_agent_phone']      = esc_html__('Phone','wpresidence-core');
    $columns['date']                    = esc_html__('Date','wpresidence-core');
    $columns['agent_verified']                = esc_html__('Verify','wpresidence-core');
    

    return  array_merge($columns,array_reverse($slice));
}
endif; // end   wpestate_my_columns

add_action('manage_posts_custom_column', function($column_key, $post_id) {
    if ($column_key == 'agent_verified') {
        $verified = get_post_meta($post_id, 'agent_verified', true);
        $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_parent' => $post_id, 
        'exclude'     => get_post_thumbnail_id()
        ) );
     
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_image( $image_id, 'post-thumbnail' );
        if ($verified) {
            echo '<span style="color:green;">'; _e('Verified', 'textdomain'); echo '</span>';
        } else {

            if(!empty($attachments) || !empty($img_url))
            {
                echo '<span style="color:red;">'; _e('Unverified', 'textdomain'); echo '</span>';
            }
            else
            {
                echo '<span style="color:#0086bf;">'; _e('Certificate Not Uploaded', 'textdomain'); echo '</span>'; 
            }
        }
    }
    elseif($column_key == 'estate_agent_verified_thumb')
    {
        $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_parent' => $post_id, 
        'exclude'     => get_post_thumbnail_id()
        ) );
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_url( $image_id );
        if(!empty($attachments))
        {
          echo '<img src="'.$attachments[0]->guid.'" style="width:100%;height:auto;">';  
        }
        
        
    }
}, 10, 2);

add_filter('bulk_actions-edit-estate_agent', function($bulk_actions) {
    $bulk_actions['mark-as-verified'] = __('Mark as verified', 'txtdomain');
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-estate_agent', function($redirect_url, $action, $post_ids) {
    if ($action == 'mark-as-verified') {
        foreach ($post_ids as $post_id) {
       
        $attachments = get_posts( array(
            'post_type' => 'attachment',
            'posts_per_page' => 2,
            'post_parent' => $post_id, 
            'exclude'     => get_post_thumbnail_id()
        ) );

        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_image( $image_id, 'post-thumbnail' );

            if(!empty($attachments) || !empty($img_url))
            {

                update_post_meta($post_id, 'agent_verified', '1');
            }
        }
        $redirect_url = add_query_arg('mark-as-verified', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);
/* End add column in post type estate_agent 27-02-2021*/

/* Add column in posty type estate_agency 27-02-2021 */
add_filter( 'manage_edit-estate_agency_columns', 'override_my_columns_agency' );

if( !function_exists('alter_my_columns_agent') ):
function override_my_columns_agency( $columns ) {
    $slice=array_slice($columns,2,2);
    unset( $columns['comments'] );
    unset( $slice['comments'] );
    $splice=array_splice($columns, 2);
    $columns['estate_ID']               = esc_html__('ID','wpresidence-core');
    $columns['estate_agency_thumb']      = esc_html__('Image','wpresidence-core');
    $columns['estate_agency_verified_thumb']      = esc_html__('Licence Image','wpresidence-core');
    $columns['estate_agency_city']       = esc_html__('City','wpresidence-core');
    $columns['estate_agency_action']     = esc_html__('Action','wpresidence-core');
    $columns['estate_agency_category']   = esc_html__( 'Category','wpresidence-core');
    $columns['estate_agency_email']      = esc_html__('Email','wpresidence-core');
    $columns['estate_agency_phone']      = esc_html__('Phone','wpresidence-core');
    $columns['date']                    = esc_html__('Date','wpresidence-core');
    $columns['agency_verified']                = esc_html__('Verify','wpresidence-core');
    

    return  array_merge($columns,array_reverse($slice));
}
endif; // end   wpestate_my_columns

add_action('manage_posts_custom_column', function($column_key, $post_id) {
    if ($column_key == 'agency_verified') {
        $verified = get_post_meta($post_id, 'agency_verified', true);
        $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_parent' => $post_id, 
        'exclude'     => get_post_thumbnail_id()
        ) );
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_image( $image_id, 'post-thumbnail' );

        if ($verified) {
            echo '<span style="color:green;">'; _e('Verified', 'textdomain'); echo '</span>';
        } else {

            if(!empty($attachments) || !empty($img_url))
            {
                echo '<span style="color:red;">'; _e('Unverified', 'textdomain'); echo '</span>';
            }
            else
            {
                echo '<span style="color:#0086bf;">'; _e('Certificate Not Uploaded', 'textdomain'); echo '</span>'; 
            }
        }
    }
    elseif($column_key == 'estate_agency_verified_thumb')
    {
        $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_parent' => $post_id, 
        'exclude'     => get_post_thumbnail_id()
        ) );
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_url($image_id);
        if(!empty($attachments))
        {
          echo '<img src="'.$attachments[0]->guid.'" style="width:100%;height:auto;">';  
        }
       
        
    }
}, 10, 2);

add_filter('bulk_actions-edit-estate_agency', function($bulk_actions) {
    $bulk_actions['mark-as-verified'] = __('Mark as verified', 'txtdomain');
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-estate_agency', function($redirect_url, $action, $post_ids) {
    if ($action == 'mark-as-verified') {
        foreach ($post_ids as $post_id) {

        $attachments = get_posts( array(
            'post_type' => 'attachment',
            'posts_per_page' => 1,
            'post_parent' => $post_id, 
            'exclude'     => get_post_thumbnail_id()
        ) );
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_image( $image_id, 'post-thumbnail' );
            if(!empty($attachments) || !empty($img_url))
            {

                update_post_meta($post_id, 'agency_verified', '1');
            }
        }
        $redirect_url = add_query_arg('mark-as-verified', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);
/* End add column in posty type estate_agency 27-02-2021*/

/* Add column in posty type estate_developer 27-02-2021 */
add_filter( 'manage_edit-estate_developer_columns', 'override_my_columns_developer' );
if( !function_exists('alter_my_columns_developer') ):
function override_my_columns_developer( $columns ) {
    $slice=array_slice($columns,2,2);
    unset( $columns['comments'] );
    unset( $slice['comments'] );
    $splice=array_splice($columns, 2);
    $columns['estate_ID']                   = esc_html__('ID','wpresidence-core');
    $columns['estate_developer_thumb']      = esc_html__('Image','wpresidence-core');
    $columns['estate_developer_verified_thumb']      = esc_html__('Licence Image','wpresidence-core');
    $columns['estate_developer_city']       = esc_html__('City','wpresidence-core');
    $columns['estate_developer_action']     = esc_html__('Action','wpresidence-core');
    $columns['estate_developer_category']   = esc_html__( 'Category','wpresidence-core');
    $columns['estate_developer_email']      = esc_html__('Email','wpresidence-core');
    $columns['estate_developer_phone']      = esc_html__('Phone','wpresidence-core');
    $columns['date']                    = esc_html__('Date','wpresidence-core');
    $columns['developer_verified']                = esc_html__('Verify','wpresidence-core');
    

    return  array_merge($columns,array_reverse($slice));
}
endif; // end   wpestate_my_columns

add_action('manage_posts_custom_column', function($column_key, $post_id) {
    if ($column_key == 'developer_verified') {
        $verified = get_post_meta($post_id, 'developer_verified', true);
        $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_parent' => $post_id, 
        'exclude'     => get_post_thumbnail_id()
        ) );
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_image( $image_id, 'post-thumbnail' );
        if ($verified) {
            echo '<span style="color:green;">'; _e('Verified', 'textdomain'); echo '</span>';
        } else {

            if(!empty($attachments) || !empty($img_url))
            {
                echo '<span style="color:red;">'; _e('Unverified', 'textdomain'); echo '</span>';
            }
            else
            {
                echo '<span style="color:#0086bf;">'; _e('Certificate Not Uploaded', 'textdomain'); echo '</span>'; 
            }
        }
    }
    elseif($column_key == 'estate_developer_verified_thumb')
    {
        $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => 1,
        'post_parent' => $post_id, 
        'exclude'     => get_post_thumbnail_id()
        ) );
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_url( $image_id );
        if(!empty($attachments))
        {
          echo '<img src="'.$attachments[0]->guid.'" style="width:100%;height:auto;">';  
        }
        if(!empty($img_url))
        {
          echo '<img src='.$img_url.' style="width:100%;height:auto;">';  
        }
        
    }
}, 10, 2);

add_filter('bulk_actions-edit-estate_developer', function($bulk_actions) {
    $bulk_actions['mark-as-verified'] = __('Mark as verified', 'txtdomain');
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-estate_developer', function($redirect_url, $action, $post_ids) {

    if ($action == 'mark-as-verified') {
        foreach ($post_ids as $post_id) {

        $attachments = get_posts( array(
            'post_type' => 'attachment',
            'posts_per_page' => 1,
            'post_parent' => $post_id, 
            'exclude'     => get_post_thumbnail_id()
        ) );
        $image_id = get_post_meta( $post_id, '_listing_image_id', true );
        $img_url = wp_get_attachment_image( $image_id, 'post-thumbnail' );
            if(!empty($attachments) || !empty($img_url))
            {

                update_post_meta($post_id, 'developer_verified', '1');
            }
        }
        $redirect_url = add_query_arg('mark-as-verified', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);
/* End add column in posty type estate_developer 27-02-2021*/

/*Mark as unverified in post type estate_agent 17-03-2021*/
add_filter('bulk_actions-edit-estate_agent', function($bulk_actions) {
    $bulk_actions['mark-as-unverified'] = __('Mark as unverified', 'txtdomain');
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-estate_agent', function($redirect_url, $action, $post_ids) {

    if ($action == 'mark-as-unverified') {
        foreach ($post_ids as $post_id) {
            $already_verified = get_post_meta($post_id, 'agent_verified');
            if(!empty($already_verified) && $already_verified[0] == 1)
            {

            update_post_meta($post_id, 'agent_verified', '0');
            }
        }
        $redirect_url = add_query_arg('mark-as-unverified', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);
/*End Mark as unverified in post type estate_agent 17-03-2021*/


/*Mark as unverified in post type estate_agency 17-03-2021*/
add_filter('bulk_actions-edit-estate_agency', function($bulk_actions) {
    $bulk_actions['mark-as-unverified'] = __('Mark as unverified', 'txtdomain');
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-estate_agency', function($redirect_url, $action, $post_ids) {

    if ($action == 'mark-as-unverified') {
        foreach ($post_ids as $post_id) {
            $already_verified = get_post_meta($post_id, 'agency_verified');
            if(!empty($already_verified) && $already_verified[0] == 1)
            {

                update_post_meta($post_id, 'agency_verified', '0');
            }
        }
        $redirect_url = add_query_arg('mark-as-unverified', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);
/*End Mark as unverified in post type estate_agency 17-03-2021*/


/*Mark as unverified in post type estate_developer 17-03-2021*/
add_filter('bulk_actions-edit-estate_developer', function($bulk_actions) {
    $bulk_actions['mark-as-unverified'] = __('Mark as unverified', 'txtdomain');
    return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-estate_developer', function($redirect_url, $action, $post_ids) {
    if ($action == 'mark-as-unverified') {
        foreach ($post_ids as $post_id) {
            $already_verified = get_post_meta($post_id, 'developer_verified');
            if(!empty($already_verified) && $already_verified[0] == 1)
            {

                update_post_meta($post_id, 'developer_verified', '0');
            }
        }
        $redirect_url = add_query_arg('mark-as-unverified', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);
/*End Mark as unverified in post type estate_agency 17-03-2021*/

/*Override wpestate_get_pack_data_for_user_top function in user_membership_profile.php 19-03-2021*/
function wpestate_get_pack_data_for_user_top_override($userID,$user_pack,$user_registered,$user_package_activation){     
    print '<div class="pack_description">
                <div class="pack-unit">';
            $remaining_lists=wpestate_get_remain_listing_user($userID,$user_pack);
            if($remaining_lists==-1){
                $remaining_lists=esc_html__('unlimited','wpresidence-core');
            }
               
      
               
            if ($user_pack!=''){
                $title              = get_the_title($user_pack);
                $pack_time          = get_post_meta($user_pack, 'pack_time', true);
                $pack_list          = get_post_meta($user_pack, 'pack_listings', true);
                $pack_featured      = get_post_meta($user_pack, 'pack_featured_listings', true);
                $pack_price         = get_post_meta($user_pack, 'pack_price', true);
                $unlimited_lists    = get_post_meta($user_pack, 'mem_list_unl', true);
                $date               = strtotime ( get_user_meta($userID, 'package_activation',true) );
                $biling_period      = get_post_meta($user_pack, 'biling_period', true);
                $billing_freq       = get_post_meta($user_pack, 'billing_freq', true);  
            
                
                $seconds=0;
                switch ($biling_period){
                   case 'Day':
                       $seconds=60*60*24;
                       break;
                   case 'Week':
                       $seconds=60*60*24*7;
                       break;
                   case 'Month':
                       $seconds=60*60*24*30;
                       break;    
                   case 'Year':
                       $seconds=60*60*24*365;
                       break;    
                }
               
                $time_frame      =   $seconds*$billing_freq;
                $expired_date    =   $date+$time_frame;
                $expired_date    =   date('d-m-Y',$expired_date); 
                $pack_image_included  =   get_post_meta($user_pack, 'pack_image_included', true);
                if (intval($pack_image_included)==0){
                    $pack_image_included=esc_html__('Unlimited', 'wpresidence-core');
                }
               
                
                 
                print '<div class="pack_description_unit_head"><h4>'.esc_html__('Your Current Package :','wpresidence-core').'</h4> 
                       <span class="pack-name">'.$title.' </span><a id="renew_membership_old" data-id='.$userID.'>Renew Membership</a></div> ';
                
                if($unlimited_lists==1){
                    print '<div class="pack_description_unit pack_description_details">';
                    print esc_html__('  unlimited','wpresidence-core');
                    print '<p class="package_label">'.esc_html__('Listings Included','wpresidence-core').'</p></div>';
                    
                    print '<div class="pack_description_unit pack_description_details">';
                    print esc_html__('  unlimited','wpresidence-core');
                    print '<p class="package_label">'.esc_html__('Listings Remaining','wpresidence-core').'</p></div>';
                }else{
                    print '<div class="pack_description_unit pack_description_details">';
                    print ' '.$pack_list;
                    print '<p class="package_label">'.esc_html__('Listings Included','wpresidence-core').'</p></div>';
                    
                    print '<div class="pack_description_unit pack_description_details">';
                    print '<span id="normal_list_no"> '.$remaining_lists.'</span>';
                    print '<p class="package_label">'.esc_html__('Listings Remaining','wpresidence-core').'</p></div>';
                }
                
                print '<div class="pack_description_unit pack_description_details">';
                print '<span id="normal_list_no"> '.$pack_featured.'</span>';
                print '<p class="package_label">'.esc_html__('Featured Included','wpresidence-core').'</p></div>';
                
                print '<div class="pack_description_unit pack_description_details">';
                print '<span id="featured_list_no"> '.wpestate_get_remain_featured_listing_user($userID).'</span>';
                print '<p class="package_label">'.esc_html__('Featured Remaining','wpresidence-core').'</p></div>';
                
                print '<div class="pack_description_unit pack_description_details">';
                print ' '.$pack_image_included;
                print '<p class="package_label">'.esc_html__('Images / per listing','wpresidence-core').'</p></div>';
                
                print '<div class="pack_description_unit pack_description_details">';
                print ' '.$expired_date;
                print '<p class="package_label">'.esc_html__('Ends On','wpresidence-core').'</p></div>';
             
            }else{

                $free_mem_list      =   esc_html( wpresidence_get_option('wp_estate_free_mem_list','') );
                $free_feat_list     =   esc_html( wpresidence_get_option('wp_estate_free_feat_list','') );
                $free_mem_list_unl  =   wpresidence_get_option('wp_estate_free_mem_list_unl', '' );
                $free_pack_image_included  =  esc_html( wpresidence_get_option('wp_estate_free_pack_image_included ','') );
                print '<div class="pack_description_unit_head"><h4>'.esc_html__('Your Current Package:','wpresidence-core').'</h4>
                      <span class="pack-name">'.esc_html__('Free Membership','wpresidence-core').'</span></div>';
                
                print '<div class="pack_description_unit pack_description_details">';
                if($free_mem_list_unl==1){
                    print esc_html__('  unlimited','wpresidence-core');
                }else{
                    print ' '.$free_mem_list;
                }
                print '<p class="package_label">'.esc_html__('Listings Included','wpresidence-core').'</p></div>';
                 
                print '<div class="pack_description_unit pack_description_details">';
                print '<span id="normal_list_no"> '.$remaining_lists.'</span>';
                print '<p class="package_label">'.esc_html__('Listings Remaining','wpresidence-core').'</p></div>';
             
                print '<div class="pack_description_unit pack_description_details">';
                print '<span id="normal_list_no"> '.$free_feat_list.'</span>';
                print '<p class="package_label">'.esc_html__('Featured Included','wpresidence-core').'</p></div>';
                
                print '<div class="pack_description_unit pack_description_details">';
                print '<span id="featured_list_no"> '.wpestate_get_remain_featured_listing_user($userID).'</span>';
                print '<p class="package_label">'.esc_html__('Featured Remaining','wpresidence-core').'</p></div>';
                
                print '<div class="pack_description_unit pack_description_details">';
                print '<span id="free_pack_image_included"> '.$free_pack_image_included.'</span>';
                print '<p class="package_label">'.esc_html__('Images / listing','wpresidence-core').'</p></div>';
                
                print '<div class="pack_description_unit pack_description_details">';
                print '&nbsp;<p class="package_label">'.esc_html__('Ends On: -','wpresidence-core').'</p></div>';
                
            }
            print '</div></div>';
          
}
/*End override wpestate_get_pack_data_for_user_top function in user_membership_profile.php 19-03-2021*/

/*Renew membership on click #renew_membership_old 19-03-2021 */
add_action('wp_ajax_renew_membership_one_month', 'renew_membership_one_month');
add_action('wp_ajax_nopriv_renew_membership_one_month', 'renew_membership_one_month');

function renew_membership_one_month(){
    $userID = stripcslashes($_POST['id']);
    $activation_pack = get_user_meta($userID, 'package_activation',true);

    if(!empty($activation_pack))
    {
        $new_pack_activation_date = date('Y-m-d h:i:s');
        update_user_meta($userID, 'package_activation',$new_pack_activation_date);
        $response['success'] = 'true';
        $response['message'] = 'User package successfully updated';
    }
    echo json_encode($response);
}
/*End Renew membership on click #renew_membership_old 19-03-2021*/

/*Update Sold Property Status For Specific Post 25-03-2021*/
add_action( 'wp_ajax_sold_property_update', 'sold_property_update' );
function sold_property_update()
{
global $wpdb;

$catid              =   intval($_POST['cid']);
$post_id            =   intval($_POST['pid']);
$current_user       =   wp_get_current_user();
$taxonomy = 'property_status';
$obj_terms = wp_get_object_terms( $post_id, $taxonomy );
$user_type          =   get_user_meta($current_user->ID, 'user_estate_role', true);
/* Loop through each object term */
if($user_type == 2 || $user_type == 3)
    foreach ( $obj_terms as $term ) {
        
        /* See if the obj term_id is a key in $terms_map */
        if ( isset( $obj_terms ) ) {
            $from_term_tax_id = $term->term_taxonomy_id;
            $to_term_tax_id = "109";
            /* Update the '{prefix}_term_relationships' table */
            $update_res = $wpdb->update( 
                $wpdb->term_relationships, /* The table to update */
                array( 'term_taxonomy_id' => $to_term_tax_id ), /* Data to be updated */
                array( 'object_id' => $post_id, 'term_taxonomy_id' => $from_term_tax_id ), /* Where clause */
                array( '%d' ), /* Format of the data is int */
                array( '%d', '%d' ) /* Format of the where clause is int */
            );
            /* Finally, you may wish to update the term count for each term */
            $result = wp_update_term_count( array( $from_term_tax_id, $to_term_tax_id ), $taxonomy );
            if($result)
            {
                $response["status"] = "true";
                $response["message"] = "Property status successfully updated.";
            }
        }
    }
}    

/*End Update Sold Property Status For Specific Post 25-03-2021*/

/*Reference number field add in wp admin 30-03-2021*/
add_filter('manage_edit-estate_property_columns', 'bs_event_table_head');
function bs_event_table_head( $defaults ) {
    $defaults['reference']  = 'Reference';
    return $defaults;
}
add_action('manage_posts_custom_column', function($column_key, $post_id) {
    if ($column_key == 'reference') {
       $reference_num = get_post_meta($post_id, 'reference_number', true);
       echo $reference_num;
    }
   
}, 10, 2);
/*Reference number field add in wp admin 30-03-2021*/

/*Search Post Based on Post Id In Wp-admin 30-03-2021*/
if (!function_exists('extend_admin_search')) {
    add_action('admin_init', 'extend_admin_search');

    /**
     * hook the posts search if we're on the admin page for our type
     */
    function extend_admin_search() {
        global $typenow;

        if ($typenow === 'estate_property') {
            add_filter('posts_search', 'posts_search_custom_post_type', 10, 2);
        }
    }

    /**
     * add query condition for custom meta
     * @param string $search the search string so far
     * @param WP_Query $query
     * @return string
     */
    function posts_search_custom_post_type($search, $query) {
        global $wpdb;

        if ($query->is_main_query() && !empty($query->query['s'])) {
            $sql    = "
            or exists (
                select * from {$wpdb->postmeta} where post_id={$wpdb->posts}.ID
                and meta_key in ('reference_number')
                and meta_value like %s
            )
        ";
            $like   = '%' . $wpdb->esc_like($query->query['s']) . '%';
            $search = preg_replace("#\({$wpdb->posts}.post_title LIKE [^)]+\)\K#",
                $wpdb->prepare($sql, $like), $search);
        }

        return $search;
    }
}
/*Search Post Based on Post Id In Wp-admin 30-03-2021*/

