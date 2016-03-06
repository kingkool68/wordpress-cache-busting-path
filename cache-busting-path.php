<?php
/*
Plugin Name: Cache Busting Path
Description: Function that returns a path with a cache-busting query string based on the last time the file was updated.
Version: 1.1
Author: kingkool68
Author URI: http://www.russellheimlich.com/blog
License: GPL2
*/
function cache_busting_path($path, $time_format = 'U') {
	if( $path[0] != '/' ) { // Checks for the first character in $path is a slash and adds it if it isn't.
		$path = '/' . $path;
	}
	return get_bloginfo('template_url') . $path . '?' . date($time_format, filemtime( get_theme_root() . '/' . get_template() . $path ) );
}

/*
Rewrites CSS and JavaScript urls that are enqueued via the proper WordPress functions. It adds a date of when the file was last modified before the file name for cacheing efficeny.
Requires the following in the .htaccess file:

<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.+)\.([0-9|\-|:_])+\.(bmp|css|cur|gif|ico|jpe?g|js|png|svgz?|webp|webmanifest|js)$ $1.$3 [L]
</IfModule>
*/
function cache_busting_file_src( $src, $handle ) {
	global $wp_scripts;
	// If $wp_scripts hasn't been initialized
	if( ( $wp_scripts instanceof WP_Scripts ) === false ) {
		$wp_scripts = new WP_Scripts();
	}
	$base_url = $wp_scripts->base_url;

	// Check if script lives on this domain. Can't rewrite external scripts, they won't work.
	if( !strstr( $src, $base_url ) ) {
		return $src;
	}

	// Remove the 'ver' query var: ?ver=0.1
	$src = remove_query_arg( 'ver', $src );

	$regex = '/' . preg_quote( $base_url, '/' ) . '/';
	$path = preg_replace( $regex, '', $src );

	// If the folder starts with wp- then we can figure out where it lives on the filesystem.
	if( strstr( $path, '/wp-' ) ) {
		$file = untrailingslashit(ABSPATH) . $path;
	}

	if( !file_exists( $file ) ) {
		return $src;
	}

	$time = 0; //Just in case something goes wrong...
	$time = date( 'Y-m-d_g:i', filemtime( $file ) );
	$src = preg_replace( '/\.(css|js)$/i', ".$time.$1", $src );

	return $src;
}
add_filter( 'script_loader_src', 'cache_busting_file_src', 10, 2 );
add_filter( 'style_loader_src', 'cache_busting_file_src', 10, 2 );