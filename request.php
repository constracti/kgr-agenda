<?php

if ( !defined( 'ABSPATH' ) )
	exit;

final class KGR_Agenda_Request {

	private static function request_var( string $method, string $key ) {
		switch ( $method ) {
			case 'GET':
				if ( !array_key_exists( $key, $_GET ) )
					return NULL;
				return $_GET[$key];
			case 'POST':
				if ( !array_key_exists( $key, $_POST ) )
					return NULL;
				return $_POST[$key];
			default:
				exit( 'method' );
		}
	}

	private static function request_str( string $method, string $key ): string|null {
		$var = self::request_var( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		if ( !is_string( $var ) )
			exit( $key );
		if ( $var === '' )
			return NULL;
		return $var;
	}

	private static function request_int( string $method, string $key ): int|null {
		$var = self::request_str( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		$var = filter_var( $var, FILTER_VALIDATE_INT );
		if ( $var === FALSE )
			exit( $key );
		return $var;
	}

	// GET

	public static function get_str( string $key, bool $nullable = FALSE ): string|null {
		$var = self::request_str( 'GET', $key );
		if ( !is_null( $var ) || $nullable )
			return $var;
		exit( $key );
	}

	public static function get_int( string $key, bool $nullable = FALSE ): int|null {
		$var = self::request_int( 'GET', $key );
		if ( !is_null( $var ) || $nullable )
			return $var;
		exit( $key );
	}

	// POST

	public static function post_str( string $key, bool $nullable = FALSE ): string|null {
		$var = self::request_str( 'POST', $key );
		if ( !is_null( $var ) || $nullable )
			return $var;
		exit( $key );
	}

	public static function post_int( string $key, bool $nullable = FALSE ): int|null {
		$var = self::request_int( 'POST', $key );
		if ( !is_null( $var ) || $nullable )
			return $var;
		exit( $key );
	}
}
