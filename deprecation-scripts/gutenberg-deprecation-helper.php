#!/usr/bin/env php
<?php

if ( ! isset( $argv[2] ) ) {
	$argv[2] = "php://stdin";
}

$file = file_get_contents(
	$argv[2]
);

if ( $argv[1] == "other" ) {
	$file = str_replace(
		"ramp_for_gutenberg_load_gutenberg",
		"gutenberg_ramp_load_gutenberg",
		$file
	);

	$file = str_replace(
		"wpcom_vip_load_gutenberg",
		"gutenberg_ramp_load_gutenberg",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\((\s*)(false|0)(\s*)\);/im",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\((\s*)\[(\s*)'load'(\s*)=>(\s*)(0|false),{0,1}(\s*)\](\s*)\);/im",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\((\s*)array\((\s*)'load'(\s*)=>(\s*)(0|false),{0,1}(\s*)\)(\s*)\);/im",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);


	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\((\s*)\);/im",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\((\s*)(true|1)(\s*)\);/im",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\((\s*)\[(\s*)'load'(\s*)=>(\s*)(1|true),{0,1}(\s*)\](\s*)\);/im",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\((\s*)array\((\s*)'load'(\s*)=>(\s*)(1|true),{0,1}(\s*)\)(\s*)\);/im",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);
}

else if ( $argv[1] == "function_exists" ) {
}

else {
	die("Invalid usage");
}

echo $file;
