<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'admin_menu', function(): void {
	$page_title = esc_html__( 'KGR Agenda', 'kgr-agenda' );
	$menu_title = esc_html__( 'KGR Agenda', 'kgr-agenda' );
	$capability = 'manage_options';
	$menu_slug = 'kgr_agenda';
	$callback = [ 'KGR_Agenda_Settings', 'home_echo' ];
	add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback );
} );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'settings_page_kgr_agenda' )
		return;
	wp_enqueue_style( 'kgr_agenda_flex', KGR_Agenda::url( 'flex.css' ), [], KGR_Agenda::version() );
	wp_enqueue_script( 'kgr_agenda_script', KGR_Agenda::url( 'script.js' ), [ 'jquery' ], KGR_Agenda::version() );
} );

final class KGR_Agenda_Settings {

	public static function home(): string {
		$agenda_tags = KGR_Agenda::get_tags();
		$tags = get_terms( [
			'taxonomy' => 'post_tag',
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => FALSE,
		] );
		$html = '<div class="kgr-agenda-home kgr-agenda-flex-col kgr-agenda-root" style="margin: 0 -16px;">' . "\n";
		$html .= '<div class="kgr-agenda-flex-row kgr-agenda-flex-justify-between kgr-agenda-flex-align-center">' . "\n";
		$html .= self::refresh_button();
		$html .= '<span class="kgr-agenda-spinner kgr-agenda-leaf spinner" data-kgr-agenda-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<hr class="kgr-agenda-leaf" />' . "\n";
		$html .= '<div class="kgr-agenda-flex-row kgr-agenda-flex-justify-between kgr-agenda-flex-align-center">' . "\n";
		$html .= sprintf( '<h2 class="kgr-agenda-leaf">%s</h2>', esc_html__( 'Tags', 'kgr-agenda' ) ) . "\n";
		$html .= '<div class="kgr-agenda-flex-row">' . "\n";
		$html .= self::insert_button();
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= self::table( $tags, $agenda_tags );
		$html .= self::form( $tags, $agenda_tags );
		$html .= '<hr class="kgr-agenda-leaf" />' . "\n";
		$html .= sprintf( '<h2 class="kgr-agenda-leaf">%s</h2>', esc_html__( 'Danger Zone', 'kgr-agenda' ) ) . "\n";
		$html .= '<div class="kgr-agenda-flex-row">' . "\n";
		$html .= self::clear_button();
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	public static function home_echo(): void {
		echo '<div class="wrap">' . "\n";
		echo sprintf( '<h1>%s</h1>', esc_html__( 'KGR Agenda', 'kgr-agenda' ) ) . "\n";
		echo self::home();
		echo '</div>' . "\n";
	}

	private static function refresh_button(): string {
		return sprintf( '<a%s>%s</a>', KGR_Agenda::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_agenda_settings_refresh',
				'nonce' => KGR_Agenda::nonce_create( 'kgr_agenda_settings_refresh' ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-agenda-link kgr-agenda-leaf button',
		] ), esc_html__( 'Refresh', 'kgr-agenda' ) ) . "\n";
	}

	private static function insert_button(): string {
		return sprintf( '<a%s>%s</a>', KGR_Agenda::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_agenda_settings_insert',
				'nonce' => KGR_Agenda::nonce_create( 'kgr_agenda_settings_insert' ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-agenda-insert kgr-agenda-leaf button',
			'data-kgr-agenda-form' => '.kgr-agenda-form-tag',
		] ), esc_html__( 'Include', 'kgr-agenda' ) ) . "\n";
	}

	private static function table( array $tags, array $include_tags ): string {
		$html = '<div class="kgr-agenda-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= self::table_head_row();
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( $tags as $tag ) {
			if ( array_search( $tag->term_id, $include_tags, TRUE ) === FALSE )
				continue;
			$html .= self::table_body_row( $tag );
		}
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function table_head_row(): string {
		$html = '<tr>' . "\n";
		$html .= sprintf( '<th class="column-primary has-row-actions">%s</th>', esc_html__( 'Tag', 'kgr-agenda' ) ) . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function table_body_row( WP_Term $tag ): string {
		$actions = [
			sprintf( '<a href="%s">%s</a>', get_term_link( $tag ), esc_html__( 'View', 'kgr-agenda' ) ),
			sprintf( '<a href="%s">%s</a>', get_edit_term_link( $tag->term_id ), esc_html__( 'Edit', 'kgr-agenda' ) ),
			sprintf( '<span class="delete"><a%s>%s</a></span>', KGR_Agenda::attrs( [
				'href' => add_query_arg( [
					'action' => 'kgr_agenda_settings_delete',
					'tag' => $tag->term_id,
					'nonce' => KGR_Agenda::nonce_create( 'kgr_agenda_settings_delete', $tag->term_id ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'kgr-agenda-link',
				'data-kgr-agenda-confirm' => esc_attr( sprintf( __( 'Exclude %s?', 'kgr-agenda' ), $tag->name ) ),
			] ), esc_html__( 'Exclude', 'kgr-agenda' ) ),
		];
		$html = '<tr>' . "\n";
		$html .= '<td class="column-primary has-row-actions">' . "\n";
		$html .= sprintf( '<strong>%s</strong>', esc_html( $tag->name ) ) . "\n";
		$html .= sprintf( '<div class="row-actions">%s</div>', implode( ' | ', $actions ) ) . "\n";
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function form( array $tags, array $exclude_tags ): string {
		$html = '<div class="kgr-agenda-form kgr-agenda-form-tag kgr-agenda-leaf kgr-agenda-root kgr-agenda-root-border kgr-agenda-flex-col" style="display: none;">' . "\n";
		$html .= '<div class="kgr-agenda-leaf">' . "\n";
		$html .= '<table class="form-table">' . "\n";
		$html .= '<tbody>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th><label for="%s">%s</label></th>', esc_attr( 'kgr-agenda-form-tag' ), esc_html__( 'Tag', 'kgr-agenda' ) ) . "\n";
		$html .= '<td>' . "\n";
		$html .= '<select class="kgr-agenda-field" data-kgr-agenda-name="tag" id="kgr-agenda-form-tag">' . "\n";
		$html .= '<option value=""></option>' . "\n";
		foreach ( $tags as $tag ) {
			if ( array_search( $tag->term_id, $exclude_tags, TRUE ) !== FALSE )
				continue;
			$html .= sprintf( '<option value="%d">%s</option>', $tag->term_id, esc_html( $tag->name ) ) . "\n";
		}
		$html .= '</select>' . "\n";
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="kgr-agenda-flex-row kgr-agenda-flex-justify-between kgr-agenda-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="kgr-agenda-link kgr-agenda-submit kgr-agenda-leaf button button-primary">%s</a>', esc_html__( 'Submit', 'kgr-agenda' ) ) . "\n";
		$html .= sprintf( '<a href="" class="kgr-agenda-cancel kgr-agenda-leaf button">%s</a>', esc_html__( 'Cancel', 'kgr-agenda' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function clear_button(): string {
		return sprintf( '<a%s>%s</a>', KGR_Agenda::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_agenda_settings_clear',
				'nonce' => KGR_Agenda::nonce_create( 'kgr_agenda_settings_clear' ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-agenda-link kgr-agenda-leaf button',
			'data-kgr-agenda-confirm' => esc_attr__( 'Clear?', 'kgr-agenda' ),
		] ), esc_html__( 'Clear', 'kgr-agenda' ) ) . "\n";
	}
}

add_action( 'wp_ajax_' . 'kgr_agenda_settings_refresh', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	KGR_Agenda::nonce_verify( 'kgr_agenda_settings_refresh' );
	KGR_Agenda::success( KGR_Agenda_Settings::home() );
} );

add_action( 'wp_ajax_' . 'kgr_agenda_settings_insert', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$tags = KGR_Agenda::get_tags();
	KGR_Agenda::nonce_verify( 'kgr_agenda_settings_insert' );
	$tag = KGR_Agenda_Request::post_int( 'tag' );
	$tag = get_term( $tag, 'post_tag' );
	if ( is_null( $tag ) )
		exit( 'tag' );
	$key = array_search( $tag->term_id, $tags, TRUE );
	if ( $key !== FALSE )
		exit( 'tag' );
	$tags[] = $tag->term_id;
	KGR_Agenda::set_tags( $tags );
	KGR_Agenda::success( KGR_Agenda_Settings::home() );
} );

add_action( 'wp_ajax_' . 'kgr_agenda_settings_delete', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$tags = KGR_Agenda::get_tags();
	$tag = KGR_Agenda_Request::get_int( 'tag' );
	$tag = get_term( $tag, 'post_tag' );
	if ( is_null( $tag ) )
		exit( 'tag' );
	$key = array_search( $tag->term_id, $tags, TRUE );
	if ( $key === FALSE )
		exit( 'tag' );
	KGR_Agenda::nonce_verify( 'kgr_agenda_settings_delete', $tag->term_id );
	unset( $tags[$key] );
	KGR_Agenda::set_tags( $tags );
	KGR_Agenda::success( KGR_Agenda_Settings::home() );
} );

add_action( 'wp_ajax_' . 'kgr_agenda_settings_clear', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	KGR_Agenda::nonce_verify( 'kgr_agenda_settings_clear' );
	KGR_Agenda::set_tags( [] );
	$posts = get_posts( [
		'meta_key' => 'kgr_agenda_dates',
		'meta_compare' => 'EXISTS',
	] );
	foreach ( $posts as $post )
		KGR_Agenda::post_date_del( $post, '' );
	KGR_Agenda::success( KGR_Agenda_Settings::home() );
} );
