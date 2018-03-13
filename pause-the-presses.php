	<?php
	/**
	 * Pause the Presses
	 *
	 * Plugin Name: Pause the Presses
	 * Description: Allows theme authors to control the circumstances under which the Gutenberg editor loads. Options include "load" (1 loads all the time, 0 loads never) "post_ids" (load for particular posts) "post_types" (load for particular posts types) "terms" (load for posts with particular terms.)
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

	include(  __DIR__ . '/inc/class-pause-the-presses.php' );
	
	/**
	*
	* This functions allows themes to specify Gutenberg loadiung critera.
	* In and of itself it doesn't cause any change to Gutenberg's loading behavior.
	* However, it governs the option which stores the criteria under which Gutenberg will load 
	*
	*/
	//@todo when criteria change the bahavior changes on the second (not first) reload
	function pause_the_presses_load_gutenberg( $criteria = false ) {
		$PTP = Pause_The_Presses::get_instance();
		$criteria = ( !$criteria ) ? [ 'load' => 1 ] : $criteria;
		$stored_criteria = $PTP->get_criteria();
		if ( $criteria !== $stored_criteria ) {
			$criteria = $PTP->save_criteria( $criteria );
		}
		$PTP->active = true;
	}
	
	/** grab the plugin **/
	$PTP = Pause_The_Presses::get_instance();
	
	/** off to the races **/
	add_action( 'plugins_loaded', [ $PTP, 'load_decision' ], 20, 0 );
	/** if pause_the_presses_load_gutenberg() has not been called, perform cleanup **/
	add_action( 'shutdown' , [ $PTP, 'cleanup_option' ], 10, 0 );