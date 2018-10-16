<?php

class Gutenberg_Ramp {

	private static $instance;
	public    $active         = false;

	/**
	 * @var Gutenberg_Ramp_Criteria
	 */
	public $criteria;

	/**
	 * Get the Gutenberg Ramp singleton instance
	 *
	 * @return Gutenberg_Ramp
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new Gutenberg_Ramp();
		}

		return self::$instance;
	}

	/**
	 * Gutenberg_Ramp constructor.
	 */
	private function __construct() {

		$this->criteria = new Gutenberg_Ramp_Criteria();

		/**
		 * If gutenberg_ramp_load_gutenberg() has not been called, perform cleanup
		 * unfortunately this must be done on every admin pageload to detect the case where
		 * criteria were previously being set in a theme, but now are not (due to a code change)
		 */
		add_action( 'admin_init', [ $this, 'cleanup_option' ], 10, 0 );


		/**
		 * Tell Gutenberg when not to load
		 *
		 * Gutenberg only calls this filter when checking the primary post
		 * @TODO duplicate this for WP5.0 core with the new filter name, it's expected to change
		 */
		add_filter( 'gutenberg_can_edit_post', [ $this, 'gutenberg_should_load' ], 20, 2 );
	}


	/**
	 * Figure out whether or not Gutenberg should be loaded
	 * This method is run during `plugins_loaded` so not
	 *
	 * @return bool
	 */
	public function gutenberg_should_load() {

		// Always load Gutenberg on the front-end -- this allows blocks to render correctly, etc.
		if ( ! is_admin() ) {
			return true;
		}

		// Only load Ramp in edit screens
		if ( ! $this->is_eligible_admin_url() ) {
			return false;
		}

		$criteria = $this->criteria->get();

		/**
		 * Return false early -
		 * If criteria is empty and there are no post types enabled from the Ramp UI
		 */
		if ( ! $criteria && empty( $this->criteria->get_enabled_post_types() ) ) {
			return false;
		}

		// check if we should always or never load
		if ( false !== $criteria && array_key_exists( 'load', $criteria ) ) {
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
		$gutenberg_ramp_post_id = $this->get_current_post_id();

		// check post_types
		if ( $this->is_allowed_post_type( $gutenberg_ramp_post_id ) ) {
			return true;
		}

		if ( ! $gutenberg_ramp_post_id ) {
			return false;
		}

		// grab the criteria
		$gutenberg_ramp_post_ids = ( isset( $criteria['post_ids'] ) ) ? $criteria['post_ids'] : [];

		// check post_ids
		if ( in_array( $gutenberg_ramp_post_id, $gutenberg_ramp_post_ids, true ) ) {
			return true;
		}
	}



	/**
	 * Check whether current post type is defined as gutenberg-friendly
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function is_allowed_post_type( $post_id ) {

		$allowed_post_types = $this->criteria->get_enabled_post_types();

		// Exit early, if no allowed post types are found
		if ( false === $allowed_post_types || ! is_array( $allowed_post_types ) ) {
			return false;
		}

		// Find the current post type
		$current_post_type = false;
		if ( 0 === (int) $post_id ) {

			if ( isset( $_GET['post_type'] ) ) {
				$current_post_type = sanitize_title( $_GET['post_type'] );
			} // Regular posts are plain `post-new.php` with no `post_type` parameter defined.
			elseif ( $this->is_eligible_admin_url( [ 'post-new.php' ] ) ) {
				$current_post_type = 'post';
			}

		} else {
			$current_post_type = get_post_type( $post_id );
		}

		// Exit if no current post type found
		if ( false === $current_post_type ) {
			return false;
		}

		return in_array( $current_post_type, $allowed_post_types, true );

	}

	//
	//
	// ----- Utility functions -----
	//
	//

	/**
	 * A way to get the current post_id during the `plugins_loaded` action because the query may not exist yet
	 *
	 * @return int
	 */
	public function get_current_post_id() {

		if ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) && ( (int) $_GET['post'] > 0 ) ) {
			return absint( $_GET['post'] );
		}

		return 0;
	}

	/**
	 * Check if the current URL is elegible for Gutenberg
	 *
	 * @param array $supported_filenames - which /wp-admin/ pages to check for. Defaults to `post.php` and `post-new.php`
	 * @return bool
	 */
	public function is_eligible_admin_url( $supported_filenames = [ 'post.php', 'post-new.php' ] ) {

		$path          = sanitize_text_field( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
		$path          = trim( $path );
		$wp_admin_slug = trim( wp_parse_url( get_admin_url(), PHP_URL_PATH ), '/' );

		foreach ( $supported_filenames as $filename ) {
			// Require $filename not to be empty to avoid accidents like matching against a plain `/wp-admin/`
			if ( ! empty( $filename ) && "/{$wp_admin_slug}/{$filename}" === $path ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove the stored Gutenberg Ramp settings if `gutenberg_ramp()` isn't used
	 */
	public function cleanup_option() {

		// if the criteria are already such that Gutenberg will never load, no change is needed
		if ( $this->criteria->get() === [ 'load' => 0 ] ) {
			return;
		}
		// if the theme did not call its function, then remove the option containing criteria, which will prevent all loading
		if ( ! $this->active ) {
			$this->criteria->delete();
		}
	}

	/**
	 * Get post types that can be supported by Gutenberg.
	 *
	 * This will get all registered post types and remove post types:
	 *        * that aren't shown in the admin menu
	 *        * like attachment, revision, etc.
	 *        * that don't support native editor UI
	 *
	 *
	 * Also removes post types that don't support `show_in_rest`:
	 * @link https://github.com/WordPress/gutenberg/issues/3066
	 *
	 * @return array of formatted post types as [ 'slug' => 'label' ]
	 */
	public function get_supported_post_types() {

		if ( 0 === did_action( 'init' ) && ! doing_action( 'init' ) ) {
			_doing_it_wrong( 'Gutenberg_Ramp::get_supported_post_types', "get_supported_post_types() was called before the init hook. Some post types might not be registered yet.", '1.0.0' );
		}

		$post_types = get_post_types(
			[
				'show_ui'      => true,
				'show_in_rest' => true,
			],
			'object'
		);

		$available_post_types = [];

		// Remove post types that don't want an editor
		foreach ( $post_types as $name => $post_type_object ) {
			if ( post_type_supports( $name, 'editor' ) && ! empty( $post_type_object->label ) ) {
				$available_post_types[ $name ] = $post_type_object->label;
			}
		}

		return $available_post_types;
	}


	/**
	 * Get a list of unsupported post types post types
	 * @return array
	 */
	public function get_unsupported_post_types() {

		if ( 0 === did_action( 'init' ) && ! doing_action( 'init' ) ) {
			_doing_it_wrong( 'Gutenberg_Ramp::get_unsupported_post_types', "get_unsupported_post_types() was called before the init hook. Some post types might not be registered yet.", '1.1.0' );
		}

		$post_types       = array_keys( get_post_types( [
				'public'   => true,  // Remove any internal/hidden post types
				'_builtin' => false, // Remove builtin post types like attachment, revision, etc.
			]
		) );

		$supported_post_types = array_keys( $this->get_supported_post_types() );

		return array_diff( $post_types, $supported_post_types );
	}


}
