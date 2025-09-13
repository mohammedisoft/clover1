<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dq_banner_enqueue_admin_assets( $hook ) {
	global $post;
	// If the global $post is not set, try to get it from the URL.
	if ( ! isset( $post ) && isset( $_GET['post'] ) ) {
		$post = get_post( $_GET['post'] );
	}
	if ( isset( $post ) && $post->post_type === 'dq_banner' ) {
		// Enqueue WordPress media uploader.
		wp_enqueue_media();

		// Enqueue banner admin CSS.
		$css_file = plugin_dir_path( __FILE__ ) . '../assets/banner-admin.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'dq-banner-admin-style',
				plugin_dir_url( __FILE__ ) . '../assets/banner-admin.css',
				array(),
				filemtime( $css_file )
			);
		}

		// Enqueue banner admin JS.
		$js_file = plugin_dir_path( __FILE__ ) . '../assets/banner-admin.js';
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'dq-banner-admin-script',
				plugin_dir_url( __FILE__ ) . '../assets/banner-admin.js',
				array( 'jquery' ),
				filemtime( $js_file ),
				true
			);
		}

		// Prepare products array for the localized script.
		$products = array();
		if ( class_exists( 'WooCommerce' ) ) {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => 'publish'
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$products[] = array(
						'id'    => get_the_ID(),
						'title' => get_the_title()
					);
				}
				wp_reset_postdata();
			}
		}

		wp_localize_script( 'dq-banner-admin-script', 'dqBannerData', array(
			'recommendedWidth'  => 700,
			'recommendedHeight' => 450,
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'products'          => $products,
		) );
	}
}
add_action( 'admin_enqueue_scripts', 'dq_banner_enqueue_admin_assets' );
