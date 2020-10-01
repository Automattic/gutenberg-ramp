#!/usr/bin/env php
<?php

function json_parser_post_url(
	$github_url,
	$github_postfields,
	$github_token,
	$http_delete = false
) {
	global $argv;

	do {
		$ret_val = 0;

		$retry_req = false;

		$ch = curl_init();

		curl_setopt(
			$ch, CURLOPT_URL, $github_url
		);

		curl_setopt(
			$ch, CURLOPT_RETURNTRANSFER, 1
		);

		curl_setopt(
			$ch, CURLOPT_CONNECTTIMEOUT, 20
		);

		curl_setopt(
			$ch, CURLOPT_USERAGENT,	$argv[0]
		);

		if ( false === $http_delete ) {
			curl_setopt(
				$ch, CURLOPT_POST, 1
			);
		}

		else {
			curl_setopt(
				$ch, CURLOPT_CUSTOMREQUEST, 'DELETE'
			);
		}

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $github_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Authorization: token ' . $github_token,
				'Accept: application/vnd.github.v3+json'
			)
		);

		$resp_data = curl_exec( $ch );

		curl_close( $ch );

		$ret_val = $resp_data;

	} while ( $retry_req == true );

	return $ret_val;
}

global $argv;


if ( $argv[1] == "pr-create" ) {
	$json = json_parser_post_url(
		$argv[2],
		json_decode( $argv[4], true ),
		$argv[3]
	);


	$json = json_decode(
		$json,
		true
	);

	echo $json["html_url"] . PHP_EOL;
}

exit( 0 );
