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
		 * Tell Gutenberg when not to load
		 *
		 * Gutenberg only calls this filter when checking the primary post
		 */
		// Gutenberg < 4.1
		add_filter( 'gutenberg_can_edit_post', [ $this, 'gutenberg_should_load' ], 20, 2 );

		// WordPress > 5.0
		add_filter( 'use_block_editor_for_post', [ $this, 'gutenberg_should_load' ], 20, 2 );
	}


	/**
	 * Figure out whether or not Gutenberg should be loaded
	 *
	 * Ramp has everything disabled by default, so the default answer  for `gutenberg_should_load` is false
	 * The conditions in the functions are attempts to change that to true
	 *
	 * @return bool
	 *
	 */
	public function gutenberg_should_load( $can_edit, $post ) {

		// Don't load the Gutenberg, if the Gutenberg doesn't want to be loaded
		if( false === $can_edit ) {
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

		if ( ! isset( $post->ID ) ) {
			return false;
		}

		if ( $this->is_allowed_post_type( $post->ID ) ) {
			return true;
		}


		$ramp_post_ids = ( isset( $criteria['post_ids'] ) ) ? $criteria['post_ids'] : [];
		if ( in_array( $post->ID, $ramp_post_ids, true ) ) {
			return true;
		}

		return false;
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
