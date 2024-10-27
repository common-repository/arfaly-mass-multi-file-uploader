<?php
/**
 * Portfolio Post Type
 *
 * @package   Arfaly_Post_Type
 * @author    Abdulrhman Elbuni
 * @license   GPL-2.0+
 * @copyright 2013-2014
 */

/**
 * Register arfaly types and taxonomies.
 *
 * @package Arfaly_Post_Type
 */
class Arfaly_Post_Type_Registrations {

	public $post_type = 'arfaly';

	public $taxonomies = array( 'arfaly_category', 'arfaly_tag' );

	public function init() {
		// Add the portfolio post type and taxonomies
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Initiate registrations of post type and taxonomies.
	 *
	 * @uses Portfolio_Post_Type_Registrations::register_post_type()
	 * @uses Portfolio_Post_Type_Registrations::register_taxonomy_tag()
	 * @uses Portfolio_Post_Type_Registrations::register_taxonomy_category()
	 */
	public function register() {
		$this->register_post_type();
		$this->register_taxonomy_category();
		$this->register_taxonomy_tag();
	}

	/**
	 * Register the custom post type.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 */
	protected function register_post_type() {
		$labels = array(
			'name'               => __( 'Arfaly', 'arfaly-post-type' ),
			'singular_name'      => __( 'Arfaly Item', 'arfaly-post-type' ),
			'add_new'            => __( 'Add New Item', 'arfaly-post-type' ),
			'add_new_item'       => __( 'Add New Arfaly Item', 'arfaly-post-type' ),
			'edit_item'          => __( 'Edit Arfaly Item', 'arfaly-post-type' ),
			'new_item'           => __( 'Add New Arfaly Item', 'arfaly-post-type' ),
			'view_item'          => __( 'View Item', 'arfaly-post-type' ),
			'search_items'       => __( 'Search Arfaly', 'arfaly-post-type' ),
			'not_found'          => __( 'No Arfaly items found', 'arfaly-post-type' ),
			'not_found_in_trash' => __( 'No Arfaly items found in trash', 'arfaly-post-type' ),
		);

		$supports = array(
			'title',
//	'editor',
//	'excerpt',
//			'thumbnail',
//			'comments',
			'author',
//		'custom-fields',
//			'revisions',
		);

		$args = array(
			'labels'          => $labels,
			'supports'        => $supports,
			'public'          => true,
			'capability_type' => 'post',
			'rewrite'         => array( 'slug' => 'arfaly', ), // Permalinks format
			'menu_position'   => 5,
			'menu_icon'       => ( version_compare( $GLOBALS['wp_version'], '3.8', '>=' ) ) ? 'dashicons-cloud' : '',
			'has_archive'     => true,
		);

		$args = apply_filters( 'arfalyposttype_args', $args );

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Register a taxonomy for Arfaly Categories.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/register_taxonomy
	 */
	protected function register_taxonomy_category() {
		$labels = array(
			'name'                       => __( 'Arfaly Categories', 'arfaly-post-type' ),
			'singular_name'              => __( 'Arfaly Category', 'arfaly-post-type' ),
			'menu_name'                  => __( 'Arfaly Categories', 'arfaly-post-type' ),
			'edit_item'                  => __( 'Edit Arfaly Category', 'arfaly-post-type' ),
			'update_item'                => __( 'Update Arfaly Category', 'arfaly-post-type' ),
			'add_new_item'               => __( 'Add New Arfaly Category', 'arfaly-post-type' ),
			'new_item_name'              => __( 'New Arfaly Category Name', 'arfaly-post-type' ),
			'parent_item'                => __( 'Parent Arfaly Category', 'arfaly-post-type' ),
			'parent_item_colon'          => __( 'Parent Arfaly Category:', 'arfaly-post-type' ),
			'all_items'                  => __( 'All Arfaly Categories', 'arfaly-post-type' ),
			'search_items'               => __( 'Search Arfaly Categories', 'arfaly-post-type' ),
			'popular_items'              => __( 'Popular Arfaly Categories', 'arfaly-post-type' ),
			'separate_items_with_commas' => __( 'Separate arfaly categories with commas', 'arfaly-post-type' ),
			'add_or_remove_items'        => __( 'Add or remove arfaly categories', 'arfaly-post-type' ),
			'choose_from_most_used'      => __( 'Choose from the most used arfaly categories', 'arfaly-post-type' ),
			'not_found'                  => __( 'No arfaly categories found.', 'arfaly-post-type' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_ui'           => true,
			'show_tagcloud'     => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'arfaly_category' ),
			'show_admin_column' => true,
			'query_var'         => true,
		);

		$args = apply_filters( 'arfalyposttype_category_args', $args );

		register_taxonomy( $this->taxonomies[0], $this->post_type, $args );
	}

	/**
	 * Register a taxonomy for Arfaly Tags.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/register_taxonomy
	 */
	protected function register_taxonomy_tag() {
		$labels = array(
			'name'                       => __( 'Arfaly Tags', 'arfaly-post-type' ),
			'singular_name'              => __( 'Arfaly Tag', 'arfaly-post-type' ),
			'menu_name'                  => __( 'Arfaly Tags', 'arfaly-post-type' ),
			'edit_item'                  => __( 'Edit Arfaly Tag', 'arfaly-post-type' ),
			'update_item'                => __( 'Update Arfaly Tag', 'arfaly-post-type' ),
			'add_new_item'               => __( 'Add New Arfaly Tag', 'arfaly-post-type' ),
			'new_item_name'              => __( 'New Arfaly Tag Name', 'arfaly-post-type' ),
			'parent_item'                => __( 'Parent Arfaly Tag', 'arfaly-post-type' ),
			'parent_item_colon'          => __( 'Parent Arfaly Tag:', 'arfaly-post-type' ),
			'all_items'                  => __( 'All Arfaly Tags', 'arfaly-post-type' ),
			'search_items'               => __( 'Search Arfaly Tags', 'arfaly-post-type' ),
			'popular_items'              => __( 'Popular Arfaly Tags', 'arfaly-post-type' ),
			'separate_items_with_commas' => __( 'Separate arfaly tags with commas', 'arfaly-post-type' ),
			'add_or_remove_items'        => __( 'Add or remove arfaly tags', 'arfaly-post-type' ),
			'choose_from_most_used'      => __( 'Choose from the most used arfaly tags', 'arfaly-post-type' ),
			'not_found'                  => __( 'No arfaly tags found.', 'arfaly-post-type' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_ui'           => true,
			'show_tagcloud'     => true,
			'hierarchical'      => false,
			'rewrite'           => array( 'slug' => 'arfaly_tag' ),
			'show_admin_column' => true,
			'query_var'         => true,
		);

		$args = apply_filters( 'arfalyposttype_tag_args', $args );

		register_taxonomy( $this->taxonomies[1], $this->post_type, $args );

	}
}
