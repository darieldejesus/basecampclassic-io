<?php

class AuthHandler {
	const SESSION_NAME = 'BasecampIO';
	const USER_KEY = 'bc_user';

	public function startSession() {
		if ( session_status() == PHP_SESSION_ACTIVE ) {
			return;
		}
		session_name( self::SESSION_NAME );
		session_cache_limiter( 'public' );
		session_start();
	}

	public function authorize( $user ) {
		$_SESSION[ self::USER_KEY ] = $user;
	}

	public function isAuthorized() {
		if ( !array_key_exists( self::USER_KEY, $_SESSION ) || empty( $_SESSION[ self::USER_KEY ] ) ) {
			return FALSE;
		}
		return $_SESSION[ self::USER_KEY ];
	}

	public function regenerateSessionId() {
		session_regenerate_id();
	}

	public function logout() {
		$_SESSION = [];
		if ( ini_get( 'session.use_cookies' ) ) {
			setcookie( self::SESSION_NAME, '', time() - 42000 );
		}
		if ( session_status() == PHP_SESSION_ACTIVE ) {
			session_destroy();
		}
	}
}