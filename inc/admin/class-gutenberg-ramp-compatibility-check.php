<?php

class Gutenberg_Ramp_Compatibility_Check {

	/**
	 * Gutenberg Compatibility should only be checked in WordPress < 5.0,
	 * because Core 5.0 and Gutenberg Plugin < 4.1.0 are not compatible anyway,
	 * and Gutenberg_Ramp_Compatibility_Check is only needed to check for Gutenberg Plugin < 3.5.0
	 */
	public static function should_check_compatibility() {

		return apply_filters( 'gutenberg_ramp_should_check_compatibility', version_compare( $GLOBALS['wp_version'], '5.0', '<' ) );
	}

	public static function should_display_message() {

		$gutenberg_version = static::get_gutenberg_version();

		/**
		 * If no Gutenberg version is found, don't display a notice
		 * Because this class only cares about a specific condition ( GB < 3.5 )
		 */
		if ( false === $gutenberg_version ) {
			return false;
		}

		return version_compare( $gutenberg_version, '3.5', '<' );
	}

	public static function get_gutenberg_version() {

		$gutenberg_plugin_path = gutenberg_ramp_validated_gutenberg_load_path();
		if ( false === $gutenberg_plugin_path ) {
			return false;
		}

		$gutenberg_data = get_plugin_data( $gutenberg_plugin_path, false, false );

		if ( empty( $gutenberg_data['Version'] ) ) {
			return false;
		}

		return $gutenberg_data['Version'];
	}


	// The backup sanity check, in case the plugin is activated in a weird way,
	// or the versions change after activation.
	public function maybe_display_notice() {

		if ( self::should_display_message() ) {
			add_action( 'admin_notices', [ $this, 'display_notice' ] );
		}
	}

	public function display_notice() {

		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong> <?php esc_html_e( 'Gutenberg Ramp functionality is disabled' ); ?></strong> <br/>
				<?php if ( current_time( 'timestamp' ) > 1542672000 ): // November 20th, 2018 ?>
					<?php esc_html_e( 'Gutenberg Ramp is no longer controlling where Gutenberg is loaded. Please update Gutenberg to at least version 3.5 or update to WordPress 5.0 to restore Gutenberg Ramp functionality.', 'gutenberg-ramp' ) ?>
				<?php else: ?>
					<?php esc_html_e( 'Gutenberg Ramp is no longer controlling where Gutenberg is loaded. Please update Gutenberg to at least version 3.5 to restore Gutenberg Ramp functionality.', 'gutenberg-ramp' ) ?>
				<?php endif; ?>
			</p>
		</div>
		<?php

	}
}

