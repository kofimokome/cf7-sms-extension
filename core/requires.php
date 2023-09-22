<?php

/**
 * Add libraries to be included
 */


add_filter( 'kmcf7se_requires_filter', function ( $includes ) {
	$plugin_path = plugin_dir_path( __FILE__ );

	$files = [
		$plugin_path . 'CF7SmsExtension.php', //
	];

	return array_merge( $includes, $files );
} );