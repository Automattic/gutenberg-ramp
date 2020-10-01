#!/usr/bin/env php
<?php

if ( ! isset( $argv[2] ) ) {
	$argv[2] = "php://stdin";
}

$file = file_get_contents(
	$argv[2]
);

if ( $argv[1] == "other" ) {
	$file = preg_replace(
		"/ramp_for_gutenberg_load_gutenberg/",
		"gutenberg_ramp_load_gutenberg",
		$file
	);

	$file = preg_replace(
		"/wpcom_vip_load_gutenberg/",
		"gutenberg_ramp_load_gutenberg",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\( true \);/",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\( false \);/",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);

	$file = preg_replace(
		"/gutenberg_ramp_load_gutenberg\(\);/",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => 1, ] );",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => 1 ] );",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => true, ] );",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => true ] );",
		"add_filter( 'use_block_editor_for_post', '__return_true' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => 0, ] );",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => 0 ] );",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => false, ] );",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);

	$file = str_replace(
		"gutenberg_ramp_load_gutenberg( [ 'load' => false ] );",
		"add_filter( 'use_block_editor_for_post', '__return_false' );",
		$file
	);
}

else if ( $argv[1] == "function_exists" ) {
	
/*
	$file = preg_replace(
		"/if.\(.function_exists\(.'gutenberg_ramp_load_gutenberg'.\).\).{((.|\\r|\\n|\\t|\s+|\S+).*)}/",
		"$1",
		$file
	);


	$file = preg_replace(
		"/if.\(.function_exists\(.'gutenberg_ramp_load_gutenberg'.\).\).{((.|\\r|\\n|\\t|\s+|\S+).*)}/m",
		"$1",
		$file,
		1
	);
*/
	$file = str_replace("\n", " ___LINEBRK___ ", $file);
/*
	$file = preg_replace(
		"/if.\(.function_exists\(.'gutenberg_ramp_load_gutenberg'.\).\).{((.|\\r|\\n|\\t|\s+|\S+).*)}/",
		"$1",
		$file,
		1
	);
*/

	$file = preg_replace(
		"/if.\(.function_exists\(.'gutenberg_ramp_load_gutenberg'.\).\).\{((\s*\S+\s*).*)\}/",
		"$1",
		$file,
		1
	);

	$file = str_replace(" ___LINEBRK___ ", "\n", $file);
}

else {
	die("Invalid usage");
}

echo $file;
