<?php
/**
 * Frontend Uploader Settings
 */
class Arfaly_Settings {

	private $settings_api, $public_post_types = array();

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;

		add_action( 'current_screen', array( $this, 'action_current_screen' ) );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
	}

	/**
	 * Only run if current screen is plugin settings or options.php
	 * @return [type] [description]
	 */
	function action_current_screen() {
		$screen = get_current_screen();
		if ( in_array( $screen->base, array( 'settings_page_arfaly_settings', 'options' ) ) ) {
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );
			// Initialize settings
			$this->settings_api->admin_init();
		}
	}

	/**
	 * Get post types for checkbox option
	 * @return array of slug => label for registered post types
	 */
	static function get_post_types() {
		$arfaly_public_post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach( $arfaly_public_post_types as $slug => $post_object ) {
			if ( $slug == 'attachment' ) {
				unset( $arfaly_public_post_types[$slug] );
				continue;
			}
			$arfaly_public_post_types[$slug] = $post_object->labels->name;
		}
		return $arfaly_public_post_types;
	}

	function action_admin_menu() {
		add_options_page( __( 'Arfaly Settings', 'arfaly-uploader' ) , __( 'Arfaly Settings', 'arfaly-uploader' ), 'manage_options', 'arfaly_settings', array( $this, 'plugin_page' ) );
	}

	function get_settings_sections() {
		$sections = array(
			array(
				'id' => 'arfaly_settings',
				'title' => __( 'Arfaly Settings', 'arfaly-uploader' ),
			),
		);
		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	static function get_settings_fields() {;
		$default_post_type = array( 'post' => 'Posts', 'post' => 'post' );
		$settings_fields = array(
			'arfaly_settings' => array(
				array(
					'name' => 'notify_admin',
					'label' => __( 'Notify site admins', 'arfaly-uploader' ),
					'desc' => __( 'Yes', 'arfaly-uploader' ),
					'type' => 'checkbox',
					'default' => '',
				),
				array(
					'name' => 'admin_notification_text',
					'label' => __( 'Admin Notification', 'arfaly-uploader' ),
					'desc' => __( 'Message that admin will get on new file upload', 'arfaly-uploader' ),
					'type' => 'textarea',
					'default' => 'Someone uploaded a new Arfaly file, please moderate at: ' . admin_url( 'upload.php?page=arfaly_manage_list' ),
					'sanitize_callback' => 'wp_filter_post_kses'
				),
				array(
					'name' => 'notification_email',
					'label' => __( 'Notification email', 'arfaly-uploader' ),
					'desc' => __( 'Leave blank to use site admin email', 'arfaly-uploader' ),
					'type' => 'text',
					'default' => '',
					'sanitize_callback' => 'sanitize_email',
				),
				array(
					'name' => 'enabled_files',
					'label' => __( 'Also allow to upload these files (in addition to the ones that WP allows by default)', 'arfaly-uploader' ),
					'desc' => '',
                    'type' => 'multicheck',
                    'default' => array(),
                    'options' => itech_arfaly_get_exts_descs(),
				),
				array(
					'name' => 'auto_approve_user_files',
					'label' => __( 'Auto-approve registered users files', 'arfaly-uploader' ),
					'desc' => __( 'Yes', 'arfaly-uploader' ),
					'type' => 'checkbox',
					'default' => '',
				),
				array(
					'name' => 'auto_approve_any_files',
					'label' => __( 'Auto-approve any files', 'arfaly-uploader' ),
					'desc' => __( 'Yes', 'arfaly-uploader' ),
					'type' => 'checkbox',
					'default' => '',
				),
			),
		);
		return $settings_fields;
	}

	/**
	 * Render the UI
	 */
	function plugin_page() {
		echo '<div class="wrap">';
        echo '<a target="_blank" href="http://codecanyon.net/item/arfaly-press-premium-digital-information-uploader-and-manager-/11164660&ref=mindsquare"><img src="'.ITECHARFALYPLUGINURL.'/assets/images/logo.png" ></a>';
        echo '<h2>Get premium to enable these management features</h2>';
		$this->settings_api->show_navigation();
		$this->settings_api->show_forms();
		echo '</div>';
	}
}

// Instantiate
$arfaly_settings = new Arfaly_Settings;