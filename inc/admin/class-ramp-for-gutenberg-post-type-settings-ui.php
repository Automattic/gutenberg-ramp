<?php

class Ramp_For_Gutenberg_Post_Type_Settings_UI {

	/**
	 * Ramp_For_Gutenberg_Post_Type_Settings_UI constructor.
	 */
	public function __construct() {

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		add_settings_section(
			'ramp_for_gutenberg_post_types',
			esc_html__( 'Ramp for Gutenberg', 'ramp-for-gutenberg' ),
			[ $this, 'render_settings_section' ],
			'writing'
		);
	}

	function render_settings_section( $args ) {

		$post_types = $this->get_supported_post_types();
		?>
		<div class="ramp-for-gutenberg-description">
			<p>
				<?php esc_html_e( 'Use these settings to enable Gutenberg for specific post types.', 'ramp-for-gutenberg' ); ?>
			</p>
		</div>

		<table class="form-table">
			<tbody>
			<tr class="ramp-for-gutenberg-post-types">
				<th scope="row"><?php esc_html_e( 'Enable Gutenberg on', 'ramp-for-gutenberg' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable Gutenberg on', 'ramp-for-gutenberg' ); ?> </span></legend>

						<?php foreach ( $post_types as $slug => $label ) : ?>

							<label for="<?php echo esc_attr( $slug ) ?>">
								<input name="rfg_post_types[<?php echo esc_attr( $slug ) ?>]"
									   type="checkbox"
									   id="rfg-post-type-<?php echo esc_attr( $slug ) ?>"
									   value="1"
									<?php checked( 0, 1 ); ?>
								>
								<span><?php echo esc_html( $label ) ?></span>
							</label>
							<br>

						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			</tbody>
		</table>

		<div class="ramp-for-gutenberg-description">
			<p>
				<?php printf(
					esc_html__( 'For more granular control you can use the %s function.', 'ramp-for-gutenberg' ),
					'<code>ramp_for_gutenberg_load_gutenberg()</code>'
				); ?>

				<a href="#" target="_blank"><?php esc_html_e( 'Learn more', 'ramp-for-gutenberg' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function get_supported_post_types() {

		$post_types = get_post_types(
			[
				'show_ui' => true,
				'show_in_rest' => true,
			],
			'object'
		);

		$available_post_types = array();

		// Remove post types that don't want an editor
		foreach ( $post_types as $name => $post_type_object ) {
			if ( post_type_supports( $name, 'editor' ) && ! empty( $post_type_object->label ) ) {
				$available_post_types[ $name ] = $post_type_object->label;
			}
		}

		return $available_post_types;
	}

}