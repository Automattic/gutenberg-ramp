<?php

class Ramp_For_Gutenberg {

	private static $instance;
	public $option_name = 'ramp_for_gutenberg_load_critera';
	public $active      = false;

	public static function get_instance() {
		if ( ! self::$instance ) {
			 self::$instance = new Ramp_For_Gutenberg();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function option_name() {
		return apply_filters( 'ramp_for_gutenberg_option_name', $this->option_name );
	}

	public function get_criteria() {
		return get_option( $this->option_name() );
	}

	public function save_criteria( $criteria ) {
		if ( $this->validate_criteria( $criteria ) ) {
			return update_option( $this->option_name(), $criteria );
		}
		return false;
	}

	public function validate_criteria( $criteria ) {

		if ( ! is_array( $criteria ) || ! $criteria ) {
			return false;
		}

		$criteria_whitelist = [ 'post_ids', 'post_types', 'terms', 'load' ];
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
				case 'terms':
					foreach ( $value as $term ) {
						if ( ! is_array( $term ) || count( $term ) !== 2 ) {
							return false;
						}
					}
					break;
				case 'load':
					if ( !in_array( $value, [ 0, 1 ], true ) ) {
						return false;
					}
					break;
				default:
					break;
			}
		}
		return true;
	}

	public function load_decision() {
		// we need to correct the situation when one of two conditions apply:
		// case 1: gutenberg should load according to our criteria but it will not currently do so
		// case 2:  gutenberg should not load according to our criteria, but it will currently do so
		if ( $this->gutenberg_should_load() && ! $this->gutenberg_will_load() ) {
			// this is case 1 ... force gutenberg to load if possible
			$this->gutenberg_load();
		} elseif ( ! $this->gutenberg_should_load() && $this->gutenberg_will_load() ) {
			// this is case 2 ... force gutenberg to bail if possible
			// @todo define this behavior -- will probably leverage the classic editor plugin or some version thereof
			$this->gutenberg_unload();
		}
	}

	// this happens very early -- on plugins_loaded.  We'll probably have to do some ghetto stuff here
	public function gutenberg_should_load() {

		// we only conditionally load Gutenberg on the edit screen.
		if ( ! $this->is_eligible_admin_url() ) {
			return false;
		}

		$criteria = $this->get_criteria();
		// if criteria is empty, we never load gutenberg
		if ( ! $criteria ) {
			return false;
		}

		// check if we should always or never load
		if ( array_key_exists( 'load', $criteria ) ) {
			if ( $criteria['load'] === 1 ) {
				return true;
			} elseif ( $criteria['load'] === 0 ) {
				return false;
			}
		}

		// CRITERIA
		// in order load Gutnberg because of other criteria, we will need to check that a few things are true:
		// 1. we are attempting to load post.php ... there's an available post_id
		// 2. there's an available post_id in the URL to check
		$ramp_for_gutenberg_post_id = $this->get_current_post_id();
		if ( ! $ramp_for_gutenberg_post_id ) {
			return false;
		}

		// grab the criteria
		$ramp_for_gutenberg_post_ids   = ( isset( $criteria['post_ids'] ) ) ? $criteria['post_ids'] : [];
		$ramp_for_gutenberg_post_types = ( isset( $criteria['post_types'] ) ) ? $criteria['post_types'] : [];
		$ramp_for_gutenberg_terms      = ( isset( $criteria['terms'] ) ) ? $criteria['terms'] : [];

		// check post_ids
		if ( in_array( $ramp_for_gutenberg_post_id, $ramp_for_gutenberg_post_ids, true ) ) {
			return true;
		}

		// check post_types
		$ramp_for_gutenberg_current_post_type = get_post_type( $ramp_for_gutenberg_post_id );
		if ( in_array( $ramp_for_gutenberg_current_post_type, $ramp_for_gutenberg_post_types, true ) ) {
			return true;
		}

		// check if the post has one of the terms
		// @todo
	}

	public function gutenberg_will_load() {
		// for WordPress version > 5, Gutenberg will load
		global $wp_version;
		$version_arr     = explode( '.', $wp_version );
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
	public function gutenberg_load() {
		// perform any actions required before loading gutenberg
		do_action( 'ramp_for_gutenberg_before_load_gutenberg' );
		$gutenberg_include = apply_filters( 'ramp_for_gutenberg_gutenberg_load_path', WP_PLUGIN_DIR . '/gutenberg/gutenberg.php' );
		if ( validate_file( $gutenberg_include ) !== 0 ) {
			return false;
		}
		if ( file_exists( $gutenberg_include ) ) {
			include_once $gutenberg_include;
		}
	}

	// @todo
	public function gutenberg_unload() {}

	// utility functions
	public function get_current_post_id() {
		if ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) && ( (int) $_GET['post'] > 0 ) ) {
			return (int) $_GET['post'];
		}
	}

	public function is_eligible_admin_url() {
		$path = sanitize_text_field( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
		return ( '/wp-admin/post.php' === trim( $path ) );
	}

	public function cleanup_option() {
		// if the criteria are already such that Gutenberg will never load, no change is needed
		if ( $this->get_criteria() === [ 'load' => 0 ] ) {
			return;
		}
		// if the theme did not call its function, then remove the option containing criteria, which will prevent all loading
		if ( ! $this->active ) {
			delete_option( $this->option_name() );
		}
	}
}
