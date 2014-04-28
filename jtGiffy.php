<?php
/*
Plugin Name: Giffy JSON endpoint
Plugin URI: http://jtsternberg.com/?gifs&json
Description: Give JSON access to your gifs. C'mon, share the love!
Author URI: http://jtsternberg.com
Author: Jtsternberg
Donate link: http://dsgnwrks.pro/give/
Version: 0.1.0
*/

class jtGiffy {

	public function __construct() {

		// Save your path via ?jtgiffy_url=http://jtsternberg.com/gifs
		if ( isset( $_GET['jtgiffy_url'] ) ) {
			update_option( 'jtgiffy_url', esc_url( $_GET['jtgiffy_url'] ) );
		}
		$this->gif_url = get_option( 'jtgiffy_url', site_url( '/gifs' ) );
		$this->gif_path = trailingslashit( str_ireplace( site_url(), untrailingslashit( ABSPATH ), $this->gif_url ) ) .'*.gif';

	}

	public function hooks() {
		add_action( 'template_redirect', array( $this, 'get_gifs' ), 9999 );
	}

	public function gif_paths() {
		$gifs = glob( $this->gif_path );
		return ! empty( $gifs ) ? $gifs : false;
	}

	public function get_gifs() {

		$gifs = $this->gif_urls( $this->gif_paths() );
		if ( ! $gifs )
			return false;

		// Halt here for json
		if ( isset( $_GET['json'] ) ) {
			if ( empty( $gifs ) ) {
				wp_send_json_error( 'No gifs found! Try a different search' );
			}
			wp_send_json_success( $gifs );
		}

		return $gifs;
	}

	public function gif_urls( $gif_paths ) {
		if ( ! $gif_paths )
			return false;

		$gifs = (object) array();
		foreach ( $gif_paths as $gif_path ) {

			$filename = explode( '/', $gif_path );
			$filename = array_pop( $filename );

			$thumb_src = $this->thumb_path_to_url( $gif_path, $filename );

			// Filter out if a term was searched & json
			if ( isset( $_GET['json'] ) && $_GET['gifs'] && false === stripos( $filename, $_GET['gifs'] ) ) {
				continue;
			}

			$nice_name = explode( '.', $filename );
			$nice_name = array_shift( $nice_name );
			$src = esc_url( str_ireplace( ABSPATH, site_url( '/' ), $gif_path ) );

			$gifs->$filename = (object) array(
				'name'      => str_ireplace( '-', ' ', $nice_name ),
				'src'       => $src,
				'thumb_src' => $thumb_src,
			);
		}

		return $gifs;
	}

	public function thumb_path_to_url( $gif_path, $filename ) {
		// Get thumb path
		$thumb_path = str_ireplace( $filename, 'thumbs/'. $filename, $gif_path );

		// Thumb exists?
		if ( ! file_exists( $thumb_path ) ) {
			$thumb_path = $this->thumb_it( $gif_path, $thumb_path );
		}

		return $thumb_path
			? esc_url( str_ireplace( ABSPATH, site_url( '/' ), $thumb_path ) )
			: '';
	}

	public function thumb_it( $file, $new_file ) {
		$image = wp_get_image_editor( $file );
		if ( ! is_wp_error( $image ) ) {
			$image->resize( 30, 30, true );
			$image->set_quality( 25 );
			$image->save( $new_file, 'image/gif' );
			return $new_file;
		}
		return '';
	}

}

$jtGiffy = new jtGiffy();

if ( isset( $_GET['gifs'], $_GET['json'] ) ) {
	$jtGiffy->hooks();
}
