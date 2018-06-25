<?php

class Gutenberg_Ramp_Criteria {

	/**
	 * Criteria is temporarily stored on class instance before it can be validated and updated
	 * Do not trust raw data stored in $criteria!
	 * @var mixed null|array
	 */
	private static $criteria = null;

	/**
	 * Where the Gutenberg Ramp criteria is stored in opitons
	 * @var string
	 */
	private $option_name    = 'gutenberg_ramp_load_critera';

	/**
	 * Gutenberg_Ramp_Criteria constructor.
	 */
	public function __construct() {
		$this->option_name = apply_filters( 'gutenberg_ramp_option_name', $this->option_name );
	}

	/**
	 * Get the option name
	 *
	 * @return string
	 */
	public function get_option_name() {

		return $this->option_name;
	}

	/**
	 * Get the desired criteria
	 *
	 * @param string $criteria_name - post_types, post_ids, load
	 *
	 * @return mixed
	 */
	public function get( $criteria_name = '' ) {

		$options = get_option( $this->get_option_name() );

		if ( '' === $criteria_name ) {
			return $options;
		}

		if ( empty( $options[ $criteria_name ] ) ) {
			return false;
		}

		return $options[ $criteria_name ];

	}

	/**
	 * Set the private class variable $criteria
	 * self::$criteria going to be used to update the option when `$this->save_criteria()` is run
	 *
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function set( $criteria ) {

		if ( $this->is_sanitized( $criteria ) ) {
			$existing_criteria = self::$criteria;

			if ( ! is_null( $existing_criteria ) ) {
				$criteria = $this->merge( $criteria, $existing_criteria );
			}

			self::$criteria = $criteria;

			return true;
		}

		return false;
	}

	/**
	 * Merge all criteria from all calls to ramp_for_gutenberg_load_gutenberg()
	 *
	 * @param array $criteria The new criteria to be merged with existing criteria.
	 * @param array $existing_criteria Any existing criteria to be merged.
	 * @return array The merged criteria.
	 */
	public function merge( $criteria = [], $existing_criteria = [] ) {

		// If no existing criteria to merge, return the new criteria
		if ( ! is_array( $existing_criteria ) || empty( $existing_criteria ) ) {
			return $criteria;
		}

		// If no new criteria to merge, return the existing criteria
		if ( ! is_array( $criteria ) || empty( $criteria ) ) {
			return $existing_criteria;
		}

		$merged_criteria = [];

		// Remove any previous value for `load`. It should only ever be an integer, and does not need to be merged with existing values for `load`.
		if( isset( $criteria['load'] && isset( $existing_criteria['load'] ) ) ) {
			unset( $existing_criteria['load'] );
		}

		// Merge the new criteria with the existing criteria.
		$merged_criteria = array_merge_recursive( $criteria, $existing_criteria );

		// Clear out duplicate values.
		foreach ( $merged_criteria as $key => $value ) {
			if ( is_array( $value ) ) {
				$merged_criteria[ $key ] = array_unique( $value );
			}
		}

		return $merged_criteria;
	}

	/**
	 * Save criteria in WordPres options if it's valid
	 */
	public function save() {

		if ( null !== self::$criteria && $this->is_valid( self::$criteria ) ) {
			update_option( $this->get_option_name(), self::$criteria );
		}

	}

	/**
	 * Make sure that the passed $post_types exist and can support Gutenberg
	 *
	 * @param array $post_types
	 *
	 * @return bool
	 */
	public function validate_post_types( $post_types ) {

		$ramp = Gutenberg_Ramp::get_instance();
		$supported_post_types = array_keys( $ramp->get_supported_post_types() );

		foreach ( (array) $post_types as $post_type ) {
			if ( ! in_array( $post_type, $supported_post_types, true ) ) {
				_doing_it_wrong( 'gutenberg_ramp_load_gutenberg', "Cannot enable Gutenberg support for post type \"{$post_type}\"", null );

				return false;
			}
		}

		return true;
	}

	/**
	 * This will make sure that the passed $criteria can actually support Gutenberg
	 *
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function is_valid( $criteria ) {

		if ( ! empty( $criteria['post_types'] ) && ! $this->validate_post_types( $criteria['post_types'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check whether $criteria is sanitized or not
	 *
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function is_sanitized( $criteria ) {

		if ( ! is_array( $criteria ) || ! $criteria ) {
			return false;
		}

		$criteria_whitelist = [ 'post_ids', 'post_types', 'load' ];
		foreach ( $criteria as $key => $value ) {
			if ( ! in_array( $key, $criteria_whitelist, true ) ) {
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
					foreach ( $value as $post_type ) {
						if ( sanitize_title( $post_type ) !== $post_type ) {
							return false;
						}
					}
					break;
				case 'load':
					if ( ! in_array( $value, [ 0, 1 ], true ) ) {
						return false;
					}
					break;
				default:
					break;
			}
		}

		return true;
	}

	/**
	 * Delete the criteria data from options
	 */
	public function delete() {
		delete_option( $this->get_option_name() );
	}

	/**
	 * Get all post types with Gutenberg enabled
	 *
	 * @return array
	 */
	public function get_enabled_post_types() {

		$ui_enabled_post_types     = (array) get_option( 'gutenberg_ramp_post_types', [] );
		$helper_enabled_post_types = (array) $this->get( 'post_types' );

		return array_unique( array_merge( $ui_enabled_post_types, $helper_enabled_post_types ) );

	}
}

