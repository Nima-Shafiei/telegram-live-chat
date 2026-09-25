<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLCWT_Activator {

	public static function activate() {

		require_once TLCWT_PATH . 'includes/class-tlc-database.php';

		TLCWT_Database::install();
	}
}