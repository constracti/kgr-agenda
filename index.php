<?php

/*
 * Plugin Name: KGR Agenda
 * Plugin URI: https://github.com/constracti/kgr-agenda
 * Description: List upcoming posts by a date meta value.
 * Version: 0.1
 * Requires at least: ?
 * Requires PHP: 8.0
 * Author: constracti
 * Author URI: https://github.com/constracti
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: kgr-agenda
 * Domain Path: /languages
 */

if ( !defined( 'ABSPATH' ) )
	exit;

final class KGR_Agenda {

	// constants

	public static function dir( string $dir ): string {
		return plugin_dir_path( __FILE__ ) . $dir;
	}

	public static function url( string $url ): string {
		return plugin_dir_url( __FILE__ ) . $url;
	}

	// plugin version

	public static function version(): string {
			$plugin_data = get_plugin_data( __FILE__ );
			return $plugin_data['Version'];
	}

	// return json string

	public static function success( string $html ): void {
		header( 'content-type: application/json' );
		exit( json_encode( [
			'html' => $html,
		] ) );
	}

	// build attribute list

	public static function attrs( array $attrs ): string {
		$return = '';
		foreach ( $attrs as $prop => $val ) {
			$return .= sprintf( ' %s="%s"', $prop, $val );
		}
		return $return;
	}

	// nonce

	private static function nonce_action( string $action, string ...$args ): string {
		foreach ( $args as $arg )
			$action .= '_' . $arg;
		return $action;
	}

	public static function nonce_create( string $action, string ...$args ): string {
		return wp_create_nonce( self::nonce_action( $action, ...$args ) );
	}

	public static function nonce_verify( string $action, string ...$args ): void {
		$nonce = KGRDTR::get_str( 'nonce' );
		if ( !wp_verify_nonce( $nonce, self::nonce_action( $action, ...$args ) ) )
			exit( 'nonce' );
	}

	// tags

	public static function get_tags(): array {
		return get_option( 'kgr_agenda_tags', [] );
	}

	public static function set_tags( array $tags ): void {
		if ( !empty( $tags ) )
			update_option( 'kgr_agenda_tags', $tags );
		else
			delete_option( 'kgr_agenda_tags' );
	}

	// post dates

	public static function post_date_sel( WP_Post $post ): array {
		return get_post_meta( $post->ID, 'kgr_agenda_dates' );
	}

	public static function post_date_add( WP_Post $post, string $date ): void {
		add_post_meta( $post->ID, 'kgr_agenda_dates', $date );
	}

	public static function post_date_del( WP_Post $post, string $date ): void {
		delete_post_meta( $post->ID, 'kgr_agenda_dates', $date );
	}
}

$files = glob( KGR_Agenda::dir( '*.php' ) );
foreach ( $files as $file ) {
        if ( $file !== __FILE__ )
                require_once( $file );
}

add_action( 'init', function(): void {
        load_plugin_textdomain( 'kgr-agenda', FALSE, basename( __DIR__ ) . '/languages' );
} );

add_filter( 'plugin_action_links', function( array $actions, string $plugin_file ): array {
	if ( $plugin_file !== basename( __DIR__ ) . '/' . basename( __FILE__ ) )
		return $actions;
	$actions['settings'] = sprintf( '<a href="%s">%s</a>',
		menu_page_url( 'kgr_agenda', FALSE ),
		esc_html__( 'Settings', 'kgr-agenda' )
	);
	return $actions;
}, 10, 2 );

add_action( 'pre_get_posts', function( WP_Query $query ): void {
	if ( is_admin() )
		return;
	if ( !$query->is_archive() )
		return;
	$tags = KGR_Agenda::get_tags();
	if ( empty( $tags ) )
		return;
	if ( !$query->is_tag( $tags ) )
		return;
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'order', 'ASC' );
	$query->set( 'meta_key', 'kgr_agenda_dates' );
	$query->set( 'meta_compare', '>' );
	$query->set( 'meta_value', current_time( 'Y-m-d' ) );
} );
