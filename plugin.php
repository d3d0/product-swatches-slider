<?php
/*
Plugin Name: Product Swatches Slider
Plugin URI: https://github.com/d3d0/product-swatches-slider
Description: This Plugin change the product image in relation the to selected color
Author: Lorenzo De Donato
Version: 1.0.0
Author URI: http://www.lorenzodedonato.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action('init','plugin_init');
function plugin_init(){
  if (class_exists("Woocommerce")) {

    // -----------------------------------------
    // -----------------------------------------

    // Disable unique sku
    add_filter( 'wc_product_has_unique_sku', '__return_false' );

    // -----------------------------------------
    // -----------------------------------------

    // Enqueue Slick scripts and styles
    add_action( 'wp_enqueue_scripts', 'slick_enqueue_scripts_styles' );
    function slick_enqueue_scripts_styles() {
    	wp_enqueue_script( 'slickjs', plugin_dir_url(__FILE__) . 'assets/js/slick.min.js', array( 'jquery' ), '1.6.0', true );
    	wp_enqueue_script( 'slickjs-init', plugin_dir_url(__FILE__) . 'assets/js/slick-init.js', array( 'slickjs' ), '1.6.0', true );
    	wp_enqueue_style( 'slickcss', plugin_dir_url(__FILE__) . 'assets/css/slick.css', '1.6.0', 'all');
    	wp_enqueue_style( 'customcsstheme', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    	// wp_enqueue_style( 'slickcsstheme', plugin_dir_url(__FILE__) . 'assets/css/slick-theme.css', '1.6.0', 'all');
    }

    // -----------------------------------------
    // -----------------------------------------

    // Add custom field > Polarized
    add_action( 'woocommerce_product_options_general_product_data', 'wc_add_custom_fields_product_general' );
    function wc_add_custom_fields_product_general() {
       global $post;
       $_product = wc_get_product( $post->ID );
       if( $_product->is_type( 'simple' ) ) {
         // do stuff for simple products
         woocommerce_wp_checkbox(array(
             'id'            => '_product_polarized',
             'label'         => __('Polarized', 'woocommerce' ),
             'description'   => __('Checkbox per occhiale polarizzato.', 'woocommerce' ),
             'value'         => get_post_meta( $post->ID, '_product_polarized', true ),
         ));
       } else {
         // do stuff for everything else
       }
    }
    // Save custom field > Polarized
    add_action( 'woocommerce_process_product_meta', 'wc_custom_save_custom_fields_product_general' );
    function wc_custom_save_custom_fields_product_general($post_id) {
      $_custom_text_option = isset( $_POST['_product_polarized'] ) ? 'yes' : 'no';
      update_post_meta( $post_id, '_product_polarized', $_custom_text_option );
    }

    // -----------------------------------------
    // -----------------------------------------

    // Add custom field > Alias Codice Modello
    add_action( 'woocommerce_product_options_inventory_product_data', 'wc_add_custom_fields_product' );
    function wc_add_custom_fields_product() {
        // Print a custom text field
        woocommerce_wp_text_input( array(
            'id' => '_sku_alias_modello',
            'label' => 'CODICE Alias Modello',
            'description' => 'Campo di testo per alias codice modello.',
            'desc_tip' => 'true',
            'placeholder' => ''
          )
        );
    }
    // Save custom field > Alias Codice Modello
    add_action( 'woocommerce_process_product_meta', 'wc_save_custom_fields_product' );
    function wc_save_custom_fields_product( $post_id ) {
        if ( ! empty( $_POST['_sku_alias_modello'] ) ) {
            update_post_meta( $post_id, '_sku_alias_modello', esc_attr( $_POST['_sku_alias_modello'] ) );
        }
    }

    // -----------------------------------------
    // -----------------------------------------

    // 1. Add custom field input @ Product Data > Variations > Single Variation
    add_action( 'woocommerce_variation_options_pricing', 'wc_add_custom_fields_variations', 10, 3 );
    function wc_add_custom_fields_variations( $loop, $variation_data, $variation ) {
    	woocommerce_wp_text_input( array(
    		'id' => '_sku_alias_colore[' . $variation->ID . ']',
    		'class' => 'short',
    		'label' => __('CODICE Alias Colore', 'woocommerce' ),
    		'description' => __('Campo di testo per alias codice colore.', 'woocommerce' ),
    		'desc_tip' => 'true',
    		'value' => get_post_meta( $variation->ID, '_sku_alias_colore', true )
    		)
    	);
    	woocommerce_wp_checkbox( array(
    		'id'            => '_checkbox_polarized[' . $variation->ID . ']',
    		'label'         => __('Polarized', 'woocommerce' ),
    		'value'         => get_post_meta( $variation->ID, '_checkbox_polarized', true ),
    		)
    	);
    }

    // 2. Save custom field on product variation save
    add_action( 'woocommerce_save_product_variation', 'wc_save_custom_fields_variations', 10, 2 );
    function wc_save_custom_fields_variations( $variation_id ) {
    	// Text field
    	$custom_field = $_POST['_sku_alias_colore'][$variation_id];
    	if ( isset( $custom_field ) ) update_post_meta( $variation_id, '_sku_alias_colore', esc_attr( $custom_field ) );

    	// Checkbox
    	$checkbox = isset( $_POST['_checkbox_polarized'][ $variation_id ] ) ? 'yes' : 'no';
    	update_post_meta( $variation_id, '_checkbox_polarized', $checkbox );
    }

    // 3. Store custom field value into variation data to display in variation.php
    add_filter( 'woocommerce_available_variation', 'wc_add_custom_fields_variations_data' );
    function wc_add_custom_fields_variations_data( $variations ) {

    	$variations['_sku_alias_colore'] = get_post_meta( $variations[ 'variation_id' ], '_sku_alias_colore', true );
    	$variations['_checkbox_polarized'] = get_post_meta( $variations[ 'variation_id' ], '_checkbox_polarized', true );

    	return $variations;
    }

    // -----------------------------------------
    // -----------------------------------------

    // Get path for templates used in loop ( like content-product.php )
    add_filter( 'wc_get_template_part', function( $template, $slug, $name )
    {
        // Look in plugin/woocommerce/slug-name.php or plugin/woocommerce/slug.php
        if ( $name ) {
            $path = plugin_dir_path( __FILE__ ) . WC()->template_path() . "{$slug}-{$name}.php";
        } else {
            $path = plugin_dir_path( __FILE__ ) . WC()->template_path() . "{$slug}.php";
        }
        return file_exists( $path ) ? $path : $template;

    }, 10, 3 );
  }
}
