<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reserved for backwards compatibility with earlier plugin scaffolding.
 *
 * The plugin bootstrap now lives in TLCWT_Plugin. Keeping this distinct class
 * prevents a fatal "class already declared" error if this legacy file is
 * loaded by a host or an integration.
 */
class TLCWT_Loader {}
