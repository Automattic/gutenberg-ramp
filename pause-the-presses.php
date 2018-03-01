<?php
/**
 * Pause the Presses
 *
 * Plugin Name: Pause the Presses
 * Description: Allows theme authors to control the circumstances under which the Gutenberg editor loads. Options include "all" (load all the time) "post_ids" (load for particular posts) "post_types" (load for particular posts types) "terms" (load for posts with particular terms.)
 * Version:     0.1
 * Author:      Automattic, Inc.
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

 /*
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */


function pause_the_presses_load_gutenberg( $criteria = false ) {
	$criteria = ( !$criteria ) ? [ 'all' => 1 ] : $criteria;
	$stored_criteria = pause_the_preses_get_criteria();
	if ( $criteria !== $stored_criteria ) {
		$criteria = pause_the_presses_save_criteria( $cirteria );
	}
}

function pause_the_presses_option_name() {
	return apply_filters( 'pause_the_presses_option_name', 'pause_the_presses_load_critera' );
}

function pause_the_preses_get_criteria() {
	return get_option( pause_the_presses_option_name() );
}

function pause_the_presses_save_criteria( $criteria ) {
	if ( pause_the_presses_validate_criteria( $criteria ) ) {
		return update_option( pause_the_presses_option_name(), $criteria );
	}
	return false;
}

function pause_the_presses_validate_criteria( $criteria ) {
	if ( !is_array( $criteria ) ) {
		return false;
	}
	$criteria_whitelist = [ 'post_ids', 'post_types', 'terms', 'all' ];
	foreach ( $criteria as $key => $value ) {
		if ( !in_array( $key, $criteria_whitelist, true ) ) {
			return false;	
		}
		if ( !is_array( $value ) ) {
			return false;
		}
		switch ( $key ) {
			case 'post_ids':
				foreach ( $value as $id ) {
					if ( ! ( is_numeric( $id ) && $id > 0 ) ) {
						return false;
					}
				}
				break;
			case 'post_types':
				foreach ( $val as $post_type ) {
					if ( sanitize_title( $post_type ) !== $post_type ) {
						return false;
					}
				}
				break;
			case 'terms':
				foreach ( $val as $term ) {
					if ( !is_array( $term ) || count( $term ) !== 2 ) {
						return false;
					}
				}
				break;
			case 'all':
				if ( !$val ) {
					return false;
				}
				break;
			default:
				break;
		}
	}
	return true;
}

function pause_the_presses_load_decision() {
	// we need to correct the situation when one of two conditions apply:
	// case 1: gutenberg should load according to our criteria but it will not currently do so
	// case 2:  gutenberg should not load according to our criteria, but it will currently do so
	if ( pause_the_presses_gutenberg_should_load() && !pause_the_presses_gutenberg_will_load() ) {
		// this is case 1 ... force gutenberg to load if possible
		pause_the_presses_gutenberg_load();
	}elseif( pause_the_presses_gutenberg_will_load() ) {
		// this is case 2 ... force gutenberg to bail if possible
		// @todo define this behavior -- will probably leverage the classic editor plugin or some version thereof
		pause_the_presses_gutenberg_unload();
	}
}

// this happens very early -- on plugins_loaded.  We'll probably have to do some ghetto stuff here
function pause_the_presses_gutenberg_should_load() {
	$criteria = pause_the_preses_get_criteria();
	// if criteria is empty, we never load gutenberg
	if ( !$criteria ) {
		return false;
	}
	// check if we should always load
	if ( array_key_exists( 'all', $criteria ) ) {
		return true;
	}
	
	// CRITERIA
	// in order load Gutnberg because of other criteria, we will need to check that a few things are true:
	// 1. we are attempting to load post.php ... there's an available post_id
	// 2. there's an available post_id in the URL to check
	
	$pause_the_presses_post_id = pause_the_presses_get_current_post_id();
	if ( !pause_the_presses_is_eligible_admin_url() || !$pause_the_presses_post_id ) {
		return false;
	}
		
	// grab the criteria
	$pause_the_presses_post_ids = ( isset( $criteria[ 'post_ids' ] ) ) ? $criteria[ 'post_ids' ] : [];
	$pause_the_presses_post_types = ( isset( $criteria[ 'post_types' ] ) ) ? $criteria[ 'post_types' ] : [];
	$pause_the_presses_terms = ( isset( $criteria[ 'terms' ] ) ) ? $criteria[ 'terms' ] : [];
	
	// check post_ids
	if ( in_array( $pause_the_presses_post_id, $pause_the_presses_post_ids ) ) {
		return true;
	}
	
	// check post_types
	$pause_the_presses_current_post_type = get_post_type( $pause_the_presses_post_id );
	if ( in_array( $pause_the_presses_current_post_type, $pause_the_presses_post_types ) ) {
		return true;
	}
	
	// check if the post has one of the terms
	// @todo
	
}

function pause_the_presses_gutenberg_will_load() {
	// for WordPress version > 5, Gutenberg will load
	global $wp_version;
	$version_arr = explode( '.', $wp_version );
	$wp_version_main = (int) $version_arr[0];
	if ( $wp_version_main >= 5 ) {
		return true;
	}
	// also, the gutenberg plugin might be the source of an attempted load
	if ( ( has_filter( 'replace_editor', 'gutenberg_init' ) || has_filter( 'load-post.php', 'gutenberg_intercept_edit_post' ) ) ) {
		return true;
	}
	return false;
}

// load gutenberg from the plugin
function pause_the_presses_gutenberg_load() {
	// perform any actions required before loading gutenberg
	do_action( 'pause_the_presses_before_load_gutenberg' );
	//@todo hmm maybe there's a better way to do this
	$gutenberg_include = apply_filters( 'pause_the_presses_gutenberg_load_path', plugin_dir_path( __FILE__ ) . '../gutenberg/gutenberg.php' );
	if ( file_exists( $gutenberg_include ) ) {
		include_once( $gutenberg_include );
	}
}

//@todo
function pause_the_presses_gutenberg_unload() {}

//utility functions

function pause_the_presses_get_current_post_id() {
	if ( isset( $_GET[ 'post' ] ) && is_numeric( $_GET[ 'post' ] ) && ( (int) $_GET[ 'post' ] > 0 ) ) {
		return (int) $_GET[ 'post' ];
	}
}

function pause_the_presses_is_eligible_admin_url() {
	$path = sanitize_text_field( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
	//@todo verify that this is narrow enough -- do we also need to verify that the action is edit?
	return ( '/admin/post.php' === trim( $path ) );
}

/** off to the races **/
add_action( 'plugins_loaded', 'pause_the_presses_load_decision', 20, 0 );