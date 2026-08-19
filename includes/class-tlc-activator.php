<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLC_Activator {

	public static function activate() {

		require_once TLC_PATH . 'includes/class-tlc-database.php';

		TLC_Database::install();
	}
}