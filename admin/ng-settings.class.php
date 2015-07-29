<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates a settings page with specified fields.
 *
 * @package ubb
 */
class Nose_Graze_Settings {

	/**
	 * The text to be displayed in the title tags, and
	 * the text used for the menu page.
	 *
	 * @var string
	 * @access public
	 * @since  1.0.0
	 */
	public $page_title;

	/**
	 * The slug name to refer to this menu by.
	 *
	 * @var string
	 * @access public
	 * @since  1.0.0
	 */
	public $menu_slug;

	/**
	 * The slug to prefix all options.
	 *
	 * @var string
	 * @access public
	 * @since  1.0.0
	 */
	public $options_slug;

	/**
	 * The capability required for this menu to be
	 * displayed to the user.
	 *
	 * @var string
	 * @access public
	 * @since  1.0.0
	 */
	public $capability;

	/**
	 * The type of settings page to use. Can be
	 * empty (nested under "Settings") or an array
	 * to use for a top-level menu page.
	 *
	 * @var null|array
	 * @access public
	 * @since  1.0.0
	 */
	public $page_type;

	/**
	 * An array of settings fields.
	 *
	 * @var array
	 * @access public
	 * @since  1.0.0
	 */
	public $fields = array();

	/**
	 * The constructor function.
	 *
	 * @param array $args
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct( $args = array() ) {
		// Assign the values to variables.
		$this->page_title   = isset( $args['page_title'] ) ? $args['page_title'] : __( 'Nose Graze Settings', $this->options_slug );
		$this->menu_slug    = isset( $args['menu_slug'] ) ? $args['menu_slug'] : 'nose-graze';
		$this->options_slug = isset( $args['options_slug'] ) ? $args['options_slug'] : 'ng';
		$this->capability   = isset( $args['capability'] ) ? $args['capability'] : 'activate_plugins';
		$this->page_type    = isset( $args['page_type'] ) ? $args['page_type'] : null;

		// Include dependencies.
		//$this->load_files();

		// Add the settings page.
		add_action( 'admin_menu', array( $this, 'add_menu' ) );

		// Register all the settings sections and fields.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add sanitization filters.
		add_filter( $this->options_slug . '_settings_sanitize_text', array( $this, 'sanitize_text_field' ), 10, 2 );
		add_filter( $this->options_slug . '_settings_sanitize_color', array( $this, 'sanitize_color_field' ), 10, 2 );
		add_filter( $this->options_slug . '_settings_sanitize_ubb_sorter', array(
			$this,
			'sanitize_ubb_sorter_field'
		), 10, 2 );

		// Enqueue scripts & styles
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
	}

	/**
	 * Sets fields to be registered.
	 *
	 * @param array $fields The array of fields to be set and registered.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function set_fields( $fields = array() ) {
		$this->fields = apply_filters( $this->options_slug . '_fields', $fields );
	}

	/**
	 * Include any required files.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function load_files() {
		include_once plugin_dir_path( __FILE__ ) . 'settings-functions.php';
	}

	/**
	 * Adds the menu page.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function add_menu() {
		// This menu is nested under "Settings"
		if ( ! is_array( $this->page_type ) ) {
			add_options_page( $this->page_title, $this->page_title, $this->capability, $this->menu_slug, array(
				$this,
				'settings_page'
			) );

			return;
		}

		// Add a top-level menu item
		$icon     = isset( $this->page_type['icon'] ) ? $this->page_type['icon'] : '';
		$position = isset( $this->page_type['position'] ) ? $this->page_type['position'] : 99;
		add_menu_page( $this->page_title, $this->page_title, $this->capability, $this->menu_slug, array(
			$this,
			'settings_page'
		), $icon, $position );

		// Add submenus for each tab if 'submenus' is not set to false.
		if ( ! isset( $this->page_type['submenus'] ) ) {
			foreach ( $this->get_tabs() as $tab => $details ) {
				add_submenu_page( $this->menu_slug, $details['title'], $details['title'], $this->capability, 'admin.php?page=' . $this->menu_slug . '&tab=' . $tab );
			}
		}
	}

	/**
	 * Adds our JavaScripts and CSS on the settings page.
	 *
	 * @param string $hook The string ID of the page we're currently on
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function add_scripts( $hook ) {
		// Bail if we're not on our settings page.
		if ( $hook != 'settings_page_' . $this->menu_slug && $hook != 'toplevel_page_' . $this->menu_slug ) {
			return;
		}

		// Add our stylesheet
		wp_enqueue_style( $this->options_slug . '-style', plugins_url( 'assets/css/style.css', __FILE__ ), false, false );
		// Test jQuery UI stylesheet
		//wp_enqueue_style('jquery-ui-test', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');

		// Add the color picker css file
		wp_enqueue_style( 'wp-color-picker' );
		// Add jQuery UI sortable
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		// Include our custom JavaScript
		wp_enqueue_script( $this->options_slug . '-scripts', plugins_url( 'assets/js/scripts.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
	}

	/**
	 * The HTML and visual output for the settings page.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function settings_page() {
		$options = $this->get_options();

		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->get_tabs() ) ? $_GET['tab'] : 'general';

		$all_tabs        = $this->get_tabs();
		$active_tab_name = $all_tabs[ $active_tab ]['title'];
		$active_tab_desc = $all_tabs[ $active_tab ]['desc'];
		ob_start();
		?>
		<div class="wrap">
			<h1><?php echo $this->page_title; ?></h1>

			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $all_tabs as $tab_id => $tab_details ) {
					$tab_url = add_query_arg( array(
						'settings-updated' => false,
						'tab'              => $tab_id
					) );
					$active  = $active_tab == $tab_id ? ' nav-tab-active' : '';
					echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_details['title'] ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_details['title'] );
					echo '</a>';
				}
				?>
			</h2>

			<div id="tab_container">
				<?php echo ( ! empty( $active_tab_name ) ) ? '<h3>' . $active_tab_name . '</h3>' : ''; ?>
				<?php echo ( ! empty( $active_tab_desc ) ) ? '<div class="tab-desc">' . $active_tab_desc . '</div>' : ''; ?>
				<form id="ng-settings-panel" method="post" action="options.php">
					<table class="form-table">
						<?php
						settings_fields( $this->options_slug . '_settings' );
						$this->do_settings_fields( $this->options_slug . '_settings_' . $active_tab, $this->options_slug . '_settings_' . $active_tab );
						?>
					</table>

					<div class="ng-settings-buttons">
						<?php submit_button(); ?>
						<p id="reset-tab">
							<input type="submit" name="ng-reset-defaults" class="button-secondary" value="<?php esc_attr_e( 'Reset Tab', $this->options_slug ); ?>">
						</p>
					</div>
				</form>
			</div>
			<!-- #tab_container-->
		</div><!-- .wrap -->
		<?php
		echo ob_get_clean();
	}

	/**
	 * Displays a table row of settings.
	 *
	 * @param $page
	 * @param $section
	 */
	public function do_settings_fields( $page, $section ) {
		global $wp_settings_fields;
		$options = $this->get_options();
		//var_dump( $options );

		if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
			$class     = 'ng-field-' . $field['args']['type'];
			$data_attr = '';
			// Check to see if another field is required.
			if ( isset( $field['args']['required'] ) && ! empty( $field['args']['required'] ) && is_array( $field['args']['required'] ) ) {

				foreach ( $field['args']['required'] as $id => $value ) {
					// Add the required field ID as a class name.
					$class .= ' requires-' . $id;
					// Add the required value as a data attribute.
					$data_attr .= ' data-requiredvalue="' . $value . '"';
					// Hide this if the requirements are not met.
					$class .= ( ! isset( $options[ $id ] ) || $options[ $id ] != $value ) ? ' ng-hide' : '';
				}
			}

			// Display table
			echo '<tr data-group="' . $field['id'] . '" class="' . $class . '"' . $data_attr . '>';
			if ( ! empty( $field['args']['label_for'] ) ) {
				echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></th>';
			} else {
				$description = ( isset( $field['args']['desc'] ) && ! empty( $field['args']['desc'] ) ) ? '<span class="desc">' . $field['args']['desc'] . '</span>' : '';
				echo '<th scope="row"><label for="' . $this->options_slug . '_settings[' . $field['args']['id'] . ']">' . $field['title'] . '</label>' . $description . '</th>';
			}
			echo '<td>';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
			echo '</tr>';
		}
	}

	/**
	 * Registers all the settings sections and fields.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function register_settings() {

		// If the settings field doesn't exist in the database, add it.
		if ( false == get_option( $this->options_slug . '_settings' ) ) {
			add_option( $this->options_slug . '_settings' );
		}

		// Loop through each settings option to register the tabs.
		foreach ( $this->fields as $tab => $settings ) {

			// Add a section for this tab.
			add_settings_section(
				$this->options_slug . '_settings_' . $tab,
				__return_null(),
				'__return_false',
				$this->options_slug . '_settings_' . $tab
			);

			if ( ! isset( $settings['fields'] ) ) {
				continue;
			}

			// Loop through each setting in this tab.
			foreach ( $settings['fields'] as $key => $option ) {

				// There's no type set, bail
				if ( ! isset( $option['type'] ) ) {
					continue;
				}

				$name = isset( $option['name'] ) ? $option['name'] : '';

				// Add this settings field.
				add_settings_field(
					$this->options_slug . '[' . $option['id'] . ']',
					$name,
					method_exists( $this, $option['type'] . '_callback' ) ? array(
						$this,
						$option['type'] . '_callback'
					) : array( $this, 'missing_callback' ),
					$this->options_slug . '_settings_' . $tab,
					$this->options_slug . '_settings_' . $tab,
					array(
						'section'     => $tab,
						'id'          => isset( $option['id'] ) ? $option['id'] : null,
						'desc'        => isset( $option['desc'] ) ? $option['desc'] : '',
						'type'        => isset( $option['type'] ) ? $option['type'] : '',
						'name'        => isset( $option['name'] ) ? $option['name'] : null,
						'size'        => isset( $option['size'] ) ? $option['size'] : null,
						'options'     => isset( $option['options'] ) ? $option['options'] : '',
						'on'          => isset( $option['on'] ) ? $option['on'] : __( 'On', $this->options_slug ),
						'off'         => isset( $option['off'] ) ? $option['off'] : __( 'Off', $this->options_slug ),
						'std'         => isset( $option['std'] ) ? $option['std'] : '',
						'min'         => isset( $option['min'] ) ? $option['min'] : null,
						'max'         => isset( $option['max'] ) ? $option['max'] : null,
						'step'        => isset( $option['step'] ) ? $option['step'] : null,
						'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
						'required'    => isset( $option['required'] ) ? $option['required'] : ''
					)
				);

			}

		}

		// Register the setting in the options table.
		register_setting( $this->options_slug . '_settings', $this->options_slug . '_settings', array(
			$this,
			'sanitize_settings'
		) );

	}

	/**
	 * Retrives the saved options from the database.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array|bool
	 */
	public function get_options() {
		$options = get_option( $this->options_slug . '_settings' );
		if ( empty( $options ) || ! is_array( $options ) ) {
			$options = array();
		}

		$defaults = $this->get_defaults();

		return array_merge( $defaults, $options );
	}

	/**
	 * Retrieves all the available tabs.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array The array of tabs
	 */
	public function get_tabs() {
		$tabs = array();

		foreach ( $this->fields as $tab => $setting ) {
			//$tabs[ $tab ] = isset( $setting['title'] ) ? $setting['title'] : $tab;
			$tabs[ $tab ] = array(
				'title' => isset( $setting['title'] ) ? $setting['title'] : $tab,
				'desc'  => isset( $setting['desc'] ) ? $setting['desc'] : '',
			);
		}

		return apply_filters( $this->options_slug . '_tabs', $tabs );
	}

	/**
	 * Retrieves every single default option available.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array The array of default settings
	 */
	public function get_defaults( $target_tab = null ) {
		$defaults = array();

		// Loop through each tab
		foreach ( $this->fields as $key => $tab ) {
			if ( $target_tab != null && $target_tab != $key ) {
				continue;
			}

			// Now loop through each section in this tab
			foreach ( $tab as $tab_key => $tab_field ) {
				if ( $tab_key !== 'fields' ) {
					continue;
				}

				// Loop through each field
				foreach ( $tab_field as $field_key => $field_value ) {
					if ( isset( $field_value['std'] ) ) {
						$defaults[ $field_key ] = $field_value['std'];
					}
				}
			}
		}

		return $defaults;
	}

	/**
	 * Settings Sanitization
	 *
	 * @param array $input
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array|string
	 */
	public function sanitize_settings( $input = array() ) {

		// There's no referrer - bail.
		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		// Get the current options.
		$options = $this->get_options();
		if ( empty( $options ) || $options == false ) {
			$options = array();
		}

		// Used to get the tab we're on.
		parse_str( $_POST['_wp_http_referer'], $referrer );

		// If we wanted to reset to the defaults, replace the values with them.
		if ( isset( $_POST['ng-reset-defaults'] ) ) {
			$input = $this->get_defaults( $referrer['tab'] );
		}

		// The field structure, passed into the class.
		$settings = $this->fields;
		$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';

		$input = $input ? $input : array();
		$input = apply_filters( $this->options_slug . '_settings_' . $tab . '_sanitize', $input );

		// Loop through each setting being saved and pass it through a sanitization filter
		foreach ( $input as $key => $value ) {
			// Get the setting type (checkbox, select, etc)
			$type = isset( $settings[ $tab ]['fields'][ $key ]['type'] ) ? $settings[ $tab ]['fields'][ $key ]['type'] : false;
			if ( $type ) {
				// Field type specific filter
				$input[ $key ] = apply_filters( $this->options_slug . '_settings_sanitize_' . $type, $value, $key );
			}
			// General filter
			$input[ $key ] = apply_filters( $this->options_slug . '_settings_sanitize', $input[ $key ], $key );
		}

		// Loop through the whitelist and unset any that are empty for the tab being saved.
		// This ensures things like multicheck boxes work when they're all empty.
		if ( ! empty( $settings[ $tab ]['fields'] ) ) {
			foreach ( $settings[ $tab ]['fields'] as $key => $value ) {
				if ( empty( $input[ $key ] ) || ! isset( $input[ $key ] ) ) {
					$input[$key] = false;
				}
			}
		}

		// Merge our new settings with the existing
		$output = array_merge( $options, $input );
		add_settings_error( $this->options_slug . '_settings', '', __( 'Settings updated.', $this->options_slug ), 'updated' );

		return $output;

	}

	/**
	 * Sanitizes text fields.
	 *
	 * @param string $input The field value.
	 * @param string $key
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string The sanitized value.
	 */
	public function sanitize_text_field( $input, $key ) {
		return $input;
	}

	/**
	 * Sanitizes color fields.
	 * Makes sure each color is a valid hex.
	 *
	 * @param string $input The field value
	 * @param string $key
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string The sanitized value.
	 */
	public function sanitize_color_field( $input, $key ) {
		// Validate
		$input = strip_tags( trim( $input ) );

		// It's empty, whatever.
		if ( empty( $input ) ) {
			return $input;
		}

		// This is not a valid hex colour
		if ( ! preg_match( '/^#[a-f0-9]{6}$/i', $input ) ) {
			add_settings_error( $this->options_slug . '_settings', '', __( 'Error: Color field not updated. You must insert a valid color into the color picker.', $this->options_slug ), 'error' );
			$options = $this->get_options();

			return isset( $options[ $key ] ) ? $options[ $key ] : '';
		}

		return $input;
	}

	/**
	 * Text Callback
	 *
	 * Renders the textbox fields.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function text_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		$size        = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$placeholder = ( isset( $args['placeholder'] ) ) ? 'placeholder="' . $args['placeholder'] . '"' : '';
		$html        = '<input type="text" class="' . $size . '-text" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"' . $placeholder . '>';
		echo $html;
	}

	/**
	 * Number Callback
	 *
	 * Renders the number fields.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function number_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		$max  = isset( $args['max'] ) ? $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;
		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '">';
		echo $html;
	}

	/**
	 * Textarea Callback
	 *
	 * Renders the textarea fields.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function textarea_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		$html = '<textarea class="large-text" cols="50" rows="5" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		echo $html;
	}

	/**
	 * Password Callback
	 *
	 * Renders the password fields.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function password_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="password" class="' . $size . '-text" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '">';
		echo $html;
	}

	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function checkbox_callback( $args ) {
		$options = $this->get_options();

		// Get the value of the checkbox.
		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = ( isset( $args['std'] ) && $args['std'] == true ) ? 1 : '';
		}

		$checked = ( intval( $value ) == 1 ) ? checked( 1, $value, false ) : '';
		$html    = '<input type="checkbox" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="1" ' . $checked . '>';
		echo $html;
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function multicheck_callback( $args ) {
		$options = $this->get_options();

		// No options are filled out - bail early.
		if ( empty( $args['options'] ) ) {
			return;
		}

		// Loop through each option in the setting.
		foreach ( $args['options'] as $key => $option ) {
			if ( isset( $options[ $args['id'] ][ $key ] ) ) {
				$enabled = $option;
			} else {
				$enabled = null;
			}
			echo '<input name="' . $this->options_slug . '_settings[' . $args['id'] . '][' . $key . ']" id="' . $this->options_slug . '_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked( $option, $enabled, false ) . '>&nbsp;';
			echo '<label for="' . $this->options_slug . '_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br>';
		}
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function radio_callback( $args ) {
		$options = $this->get_options();

		// No options are filled out - bail early.
		if ( empty( $args['options'] ) ) {
			return;
		}

		foreach ( $args['options'] as $key => $option ) {
			$checked = false;
			if ( isset( $options[ $args['id'] ] ) && $options[ $args['id'] ] == $key ) {
				$checked = true;
			} elseif ( isset( $args['std'] ) && $args['std'] == $key && ! isset( $options[ $args['id'] ] ) ) {
				$checked = true;
			}
			echo '<input name="' . $this->options_slug . '_settings[' . $args['id'] . ']" id="' . $this->options_slug . '_settings[' . $args['id'] . '][' . $key . ']" data-id="' . $args['id'] . '" type="radio" value="' . $key . '" ' . checked( true, $checked, false ) . '>&nbsp;';
			echo '<label for="' . $this->options_slug . '_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br>';
		}
	}

	/**
	 * Image Select Callback
	 *
	 * Renders radio boxes with images instead of radios.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function image_select_callback( $args ) {
		$options = $this->get_options();

		// No options are filled out - bail early.
		if ( empty( $args['options'] ) ) {
			return;
		}

		foreach ( $args['options'] as $key => $option ) {
			$checked = false;
			if ( isset( $options[ $args['id'] ] ) && $options[ $args['id'] ] == $key ) {
				$checked = true;
			} elseif ( isset( $args['std'] ) && $args['std'] == $key && ! isset( $options[ $args['id'] ] ) ) {
				$checked = true;
			}
			?>
			<label for="<?php echo $this->options_slug . '_settings[' . $args['id'] . '][' . $key . ']'; ?>" class="ng-image-select">
				<input name="<?php echo $this->options_slug . '_settings[' . $args['id'] . ']'; ?>" id="<?php echo $this->options_slug . '_settings[' . $args['id'] . '][' . $key . ']'; ?>" data-id="<?php echo $args['id']; ?>" type="radio" value="<?php echo $key; ?>" <?php echo checked( true, $checked, false ); ?>>
				<br>
				<img src="<?php echo $option; ?>" alt="<?php echo $key; ?>">
			</label>
		<?php
		}
	}

	/**
	 * Select Callback
	 *
	 * Renders select boxes.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function select_callback( $args ) {
		$options = $this->get_options();

		// No options are filled out - bail early.
		if ( empty( $args['options'] ) ) {
			return;
		}

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		if ( isset( $args['placeholder'] ) ) {
			$placeholder = $args['placeholder'];
		} else {
			$placeholder = '';
		}

		$html = '<select id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" ' . ( isset( $args['select2'] ) ? 'class="ng-select2"' : '' ) . 'data-placeholder="' . $placeholder . '">';
		foreach ( $args['options'] as $option => $name ) {
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
		}
		$html .= '</select>';
		echo $html;
	}

	/**
	 * Categories Callback
	 *
	 * Renders select boxes, populated with category names.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function categories_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$dropdown_args = array(
			'show_option_none' => __( 'Select a Category', $this->options_slug ),
			'echo'             => false,
			'name'             => $this->options_slug . '_settings[' . $args['id'] . ']',
			'id'               => $this->options_slug . '_settings[' . $args['id'] . ']',
			'selected'         => $value,
		);

		$html = wp_dropdown_categories( $dropdown_args );
		echo $html;
	}

	/**
	 * Switch Callback
	 *
	 * Renders the switch, which has a hidden checkbox.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function switch_callback( $args ) {
		$options = $this->get_options();

		// Value of the switch
		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$enabled = $disabled = '';

		// Check to see if this is enabled or disabled.
		if ( $value == 1 ) {
			$enabled = ' selected';
		} else {
			$disabled = ' selected';
		}


		$html = '<label for="' . $this->options_slug . '_settings[' . $args['id'] . ']" class="ng-enable' . $enabled . '" data-id="' . $args['id'] . '"><span>' . $args['on'] . '</span></label>';
		$html .= '<label for="' . $this->options_slug . '_settings[' . $args['id'] . ']" class="ng-disable' . $disabled . '" data-id="' . $args['id'] . '"><span>' . $args['off'] . '</span></label>';
		$html .= '<input type="hidden" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="0">';
		$html .= '<input type="checkbox" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" class="ng-hide" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="1" ' . checked( $value, 1, false ) . '>';

		echo $html;
	}

	/**
	 * Color Callback
	 *
	 * Renders color pickers.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function color_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		$html = '<input type="text" class="ng-color-field" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '">';
		echo $html;
	}

	/**
	 * Border Callback
	 *
	 * Renders a border field with size, style, and colour.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function border_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
		?>
		<div class="ng-border-field-wrap">
			<?php if ( array_key_exists( 'size', $value ) ) : ?>
				<div class="ng-border-width-wrap">
					<label for="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][size]"><?php _e( 'Width (px)', $this->options_slug ); ?></label>
					<input type="number" id="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][size]" name="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][size]" value="<?php esc_attr_e( stripslashes( $value['size'] ) ); ?>">
				</div>
			<?php endif; ?>

			<?php if ( array_key_exists( 'style', $value ) ) : ?>
				<div class="ng-border-style-wrap">
					<label for="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][style]"><?php _e( 'Style', $this->options_slug ); ?></label>
					<select id="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][style]" name="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][style]">
						<option value="none" <?php selected( $value['style'], 'none' ); ?>><?php _e( 'None', $this->options_slug ); ?></option>
						<option value="solid" <?php selected( $value['style'], 'solid' ); ?>><?php _e( 'Solid', $this->options_slug ); ?></option>
						<option value="dashed" <?php selected( $value['style'], 'dashed' ); ?>><?php _e( 'Dashed', $this->options_slug ); ?></option>
						<option value="double" <?php selected( $value['style'], 'double' ); ?>><?php _e( 'Double', $this->options_slug ); ?></option>
						<option value="dotted" <?php selected( $value['style'], 'dotted' ); ?>><?php _e( 'Dotted', $this->options_slug ); ?></option>
					</select>
				</div>
			<?php endif; ?>

			<?php if ( array_key_exists( 'color', $value ) ) : ?>
				<div class="ng-border-width-wrap">
					<label for="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][color]"><?php _e( 'Colour', $this->options_slug ); ?></label>
					<input type="text" id="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][color]" name="<?php echo $this->options_slug; ?>_settings[<?php echo $args['id']; ?>][color]" class="ng-color-field" value="<?php esc_attr_e( stripslashes( $value['color'] ) ); ?>">
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Sorter Callback
	 *
	 * Renders a normal sorter.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function sorter_callback( $args ) {
		$options = $this->get_options();

		// Get the configuration
		if ( isset( $options[ $args['id'] ] ) ) {
			$config = $options[ $args['id'] ];
		} else {
			$config = isset( $args['std'] ) ? $args['std'] : array();
		}

		$html = '<div id="' . $args['id'] . '" class="sorter">';
		$html .= '<input type="hidden" class="ng-settings-key" value="' . $this->options_slug . '_settings">';

		// Loop through each column
		foreach ( $config as $column => $section ) {

			$html .= '<ul id="' . $args['id'] . '_' . $column . '" class="sortlist_' . $args['id'] . '"><h3>' . $column . '</h3>';

			$html .= '<input class="sorter-placebo" type="hidden" name="' . $this->options_slug . '_settings[' . $args['id'] . '][' . $column . '][placebo]" value="placebo">';

			foreach ( $section as $key => $item ) {
				// Don't add a list item for the placebo.
				if ( $key == 'placebo' ) {
					continue;
				}
				$html .= '<li id="' . $key . '">';
				$html .= '<input type="hidden" name="' . $this->options_slug . '_settings[' . $args['id'] . '][' . $column . '][' . $key . '][name]" value="' . esc_attr( $item['name'] ) . '" data-key="name" class="sorter-input sorter-input-name">';
				$html .= $item['name'];
				$html .= '</li>';
			}

			$html .= '</ul>';

		}

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Slider Callback
	 *
	 * Renders the jQuery UI slider.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function slider_callback( $args ) {
		$options = $this->get_options();

		if ( isset( $options[ $args['id'] ] ) ) {
			$value = $options[ $args['id'] ];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		// Get args
		$min  = 0;
		$max  = 10000;
		$step = 1;

		if ( isset( $args['options'] ) ) {
			$min  = ( isset( $args['options']['min'] ) && is_numeric( $args['options']['min'] ) ) ? $args['options']['min'] : $min;
			$max  = ( isset( $args['options']['max'] ) && is_numeric( $args['options']['max'] ) ) ? $args['options']['max'] : $max;
			$step = ( isset( $args['options']['step'] ) && is_numeric( $args['options']['step'] ) ) ? $args['options']['step'] : $step;
		}

		$html = '<input type="number" class="ng-slider-field" id="' . $this->options_slug . '_settings[' . $args['id'] . ']" name="' . $this->options_slug . '_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '">';
		$html .= '<div id="' . $this->options_slug . '_settings[' . $args['id'] . ']-slider" class="ng-options-slider" data-id="' . $this->options_slug . '_settings[' . $args['id'] . ']" data-val="' . esc_attr( stripslashes( $value ) ) . '" data-min="' . $min . '" data-max="' . $max . '" data-step="' . $step . '"></div>';

		echo $html;
	}

	//@todo more callbacks here

	/**
	 * Missing Callback
	 *
	 * If an option is using a callback that doesn't exist, this will
	 * alert the developer.
	 *
	 * @param array $args The arguments passed by the setting
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function missing_callback( $args ) {
		printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', $this->options_slug ), $args['id'] );
	}

}