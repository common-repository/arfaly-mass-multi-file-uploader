<?php
/**
 * Include and setup custom metaboxes and fields.
 *
 * @category Arfaly
 * @package  Metaboxes
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/webdevstudios/Custom-Metaboxes-and-Fields-for-WordPress
 */
        
// Number sanitization function
function arfaly_number_sanitization($number)
{
  $number = preg_replace( "/[^0-9]/", "", $number );

    return $number;
}

add_filter( 'cmb_meta_boxes', 'arfaly_sample_metaboxes' );
/**
 * Define the metabox and field configurations.
 *
 * @param  array $meta_boxes
 * @return array
 */
function arfaly_sample_metaboxes( array $meta_boxes ) {
    
    $prefix = '_arfaly_';
    $meta_boxes['new_arfaly_metabox'] = array(
        'id'         => 'arfaly_metabox',
		'title'      => __( 'Arfaly multi-file uploader options', 'cmb' ),
		'pages'      => array( 'arfaly' ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true,
		'fields'     => array(
            array(
				'name' => __( 'Allow guests', 'cmb' ),
				'desc' => __( 'Allow guests to upload files', 'cmb' ),
				'id'   => $prefix . 'allow_guests',
				'type' => 'checkbox',
			),
            array(
				'name' => __( 'Disable file preview', 'cmb' ),
				'desc' => __( 'Disable file preview feature', 'cmb' ),
				'id'   => $prefix . 'image_preview',
				'type' => 'checkbox',
			),
            array(
              'name'       => __( 'Max file size (MB)', 'cmb' ),
              'desc'       => __( '(MB) Max upload file size allowed.<br><b>Premium</b>', 'cmb' ),
              'id'         => $prefix . 'arfaly_premium1',
              'type'       => 'text_small',
              'default'    => '5',
              'sanitization_cb' => 'arfaly_number_sanitization', // custom sanitization callback parameter
                          // 'escape_cb'       => 'number_escaping',  // custom escaping callback parameter
              'attributes'  => array(
                  'placeholder' => 10,
                  'disabled' => ''
              ),
            ),
            array(
              'name'       => __( 'Max files limit)', 'cmb' ),
              'desc'       => __( 'Max upload files limit.<br><b>Premium</b>', 'cmb' ),
              'id'         => $prefix . 'arfaly_premium2',
              'type'       => 'text_small',
              'default'    => '2',
              'sanitization_cb' => 'arfaly_number_sanitization', // custom sanitization callback parameter
              'attributes'  => array(
                  'placeholder' => 10,
                  'disabled' => ''
              ),
            ),
            array(
				'name'    => __( 'Strictly allow extensions', 'cmb' ),
				'desc'    => __( 'Allow only these extensions (Use comma seperator). Leave it empty to allow WP default mime list. <br><a href="'.admin_url( 'options-general.php?page=arfaly_settings' ).'">To customize mime</a>', 'cmb' ),
				'id'      => $prefix . 'strict_extensions',
				'type'    => 'text',
                'attributes'  => array(
                  'placeholder' => 'avi, wmv, png, jpg',
                ),
			),
            array(
              'name' => __( 'Debug', 'cmb' ),
              'desc' => __( 'Print out debug messages', 'cmb' ),
              'id'   => $prefix . 'debug',
              'type' => 'checkbox',
			),
            array(
				'name'       => __( 'Debugging info target', 'cmb' ),
				'desc'       => __( 'For class name add "." letter prefix. For ID targeting put "#" letter', 'cmb' ),
				'id'         => $prefix . 'target_debug',
				'type'       => 'text',
                'attributes'  => array(
                    'placeholder' => '#output-name',
                ),
			),
            array(
				'name'       => __( 'Label', 'cmb' ),
				'desc'       => __( 'Label that will be displayed in the bottom of the uploader box', 'cmb' ),
				'id'         => $prefix . 'label',
				'type'       => 'text',
                'attributes'  => array(
                    'placeholder' => 'Allowed file types are psd, ai, bmp, svg, tiff, gif, jpg, and png.',
                ),
			),
            array(
              'name' => __( 'Theme options', 'cmb' ),
              'desc' => __( 'Options related to plugin theme.', 'cmb' ),
              'id'   => $prefix . 'test_title',
              'type' => 'title',
              ),
            array(
              'name'    => __( 'Logo Color', 'cmb' ),
              'desc'    => __( 'Logo color', 'cmb' ),
              'id'      => $prefix . 'logo_color',
              'type'    => 'colorpicker',
              'default' => '#639AFF'
              ),
            array(
              'name'    => __( 'Text color', 'cmb' ),
              'desc'    => __( 'Text color', 'cmb' ),
              'id'      => $prefix . 'text_color',
              'type'    => 'colorpicker',
              'default' => '#818080'
              ),
            array(
              'name'    => __( 'Upload border color', 'cmb' ),
              'desc'    => __( 'Upload border color', 'cmb' ),
              'id'      => $prefix . 'border_color',
              'type'    => 'colorpicker',
              'default' => '#cecece'
              ),
            array(
              'name'    => __( 'Label Color', 'cmb' ),
              'desc'    => __( 'Upload label text color', 'cmb' ),
              'id'      => $prefix . 'label_color',
              'type'    => 'colorpicker',
              'default' => '#818080'
              ),
            )
        );
	// Add other metaboxes as needed

	return $meta_boxes;
}

add_action( 'init', 'arfaly_initialize_cmb_meta_boxes', 9999 );
/**
 * Initialize the metabox class.
 */
function arfaly_initialize_cmb_meta_boxes() {

	if ( ! class_exists( 'cmb_Meta_Box' ) )
		require_once 'init.php';

}
