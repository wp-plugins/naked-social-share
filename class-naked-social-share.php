<?php

/**
 * The main class that powers the plugin.
 *
 * @package   naked-social-share
 * @copyright Copyright (c) 2015, Ashley Evans
 * @license   GPL2+
 */
class Naked_Social_Share {

	/**
	 * The single instance of the plugin.
	 * @var Naked_Social_Share
	 * @since 1.0.0
	 */
	private static $_instance = null;

	/**
	 * The array of plugin settings.
	 * @var array
	 */
	public $settings;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function
	 *
	 * Sets up all the variables, handles localisation, and adds
	 * the content filter.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		// Load plugin environment variables.
		$this->_version = $version;
		$this->_token   = 'naked_social_share';

		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( 'assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register the activation hook
		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Upgrade routine.
		add_action( 'admin_init', array( $this, 'check_upgrades' ) );

		// Include necessary files.
		$this->includes();
		// Load settings into the options panel.
		$this->register_settings();

		// Add a link to the settings panel on the plugin listing.
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'settings_link' ) );

		// Load front end JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_styles' ) );

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Filter the content.
		add_filter( 'the_content', array(
			$this,
			'auto_add_buttons'
		), apply_filters( 'naked_social_share_priority', 125 ) );
	}

	/**
	 * Sets up the main Naked_Social_Share instance
	 *
	 * @access public
	 * @since  1.0.0
	 * @return Naked_Social_Share
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is not allowed
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'naked-social-share' ), $this->_version );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'naked-social-share' ), $this->_version );
	}

	/**
	 * Includes the required files.
	 *
	 * @access private
	 * @since  1.0.0
	 * @return void
	 */
	private function includes() {
		if ( ! class_exists( 'Nose_Graze_Settings' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'admin/ng-settings.class.php';
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-naked-social-share-buttons.php';
	}

	/**
	 * Load the plugin language files.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$domain = 'naked-social-share';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'naked-social-share', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();
	}

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		update_option( $this->_token . '_version', $this->_version );
	}

	/**
	 * Gets the plugin version saved in the database.
	 *
	 * @access public
	 * @since  1.2.0
	 * @return int
	 */
	public function get_db_version() {
		$option = get_option( $this->_token . '_version' );

		return ! empty( $option ) ? $option : 0;
	}

	/**
	 * Checks for any upgrades that haven't been done.
	 *
	 * If the DB version doesn't match the current plugin version
	 * then upgrades are performed and the new version number is
	 * logged in the DB.
	 *
	 * @access public
	 * @since  1.2.0
	 * @return void
	 */
	public function check_upgrades() {
		// If the versions match - bail.
		if ( $this->_version == $this->get_db_version() ) {
			return;
		}

		// Otherwise, run the upgrade routine.
		$this->upgrade( $this->get_db_version() );
		$this->_log_version_number();
	}

	/**
	 * Performs upgrade routines.
	 *
	 * @param int $db_version
	 *
	 * @access private
	 * @since  1.2.0
	 * @return void
	 */
	private function upgrade( $db_version ) {
		/*
		 * Upgrade to 1.2.0 to add new social sites.
		 */
		if ( version_compare( $db_version, '1.2.0', '<' ) ) {
			$social_sites = $this->settings['social_sites'];

			// Add LinkedIn.
			if ( ! array_key_exists( 'linkedin', $social_sites['enabled'] ) && ! array_key_exists( 'linkedin', $social_sites['disabled'] ) ) {
				$social_sites['disabled']['linkedin'] = array(
					'name' => __( 'LinkedIn', 'naked-social-share' )
				);

				$this->settings['social_sites'] = $social_sites;

				update_option( 'naked_ss_settings', $this->settings );
			}
		}
	}

	/**
	 * Adds a link to the plugin's settings page on the listing.
	 *
	 * @param $links
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array
	 */
	public function settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s">' . __( 'Settings', 'naked-social-share' ) . '</a>', admin_url( 'options-general.php?page=naked-social-share' ) );
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Registers the settings with the options panel.
	 *
	 * Updates the environment variable with the settings array.
	 *
	 * @uses  Nose_Graze_Settings
	 * @acess public
	 * @since 1.0.0
	 * @return void
	 */
	private function register_settings() {
		$fields = apply_filters( 'naked_social_share_settings_fields', array(
			/* General */
			'general' => array(
				'title'  => __( 'General Settings', 'naked-social-share' ),
				'desc'   => __( 'Just a few settings for this plugin.', 'naked-social-share' ),
				'fields' => array(
					'load_styles'      => array(
						'id'   => 'load_styles',
						'name' => __( 'Load Default Styles', 'naked-social-share' ),
						'desc' => __( 'If checked, a stylesheet will be loaded to give the buttons a few basic styles.', 'naked-social-shre' ),
						'type' => 'checkbox',
						'std'  => false
					),
					'load_fa'          => array(
						'id'   => 'load_fa',
						'name' => __( 'Load Font Awesome', 'naked-social-share' ),
						'desc' => __( 'Font Awesome is used for the brand icons.', 'naked-social-shre' ),
						'type' => 'checkbox',
						'std'  => true
					),
					'disable_js'       => array(
						'id'   => 'disable_js',
						'name' => __( 'Disable JavaScript', 'naked-social-share' ),
						'desc' => __( 'Some simple JavaScript is used to make the share links open in small popup windows. Disabling the JavaScript will lose that behaviour.', 'naked-social-share' ),
						'type' => 'checkbox',
						'std'  => false
					),
					'auto_add'         => array(
						'id'      => 'auto_add',
						'name'    => __( 'Automatically Add Buttons', 'naked-social-share' ),
						'desc'    => sprintf( __( 'Choose where you want the buttons to appear automatically. Alternatively, you can add the icons to your theme manually using this function: %s', 'naked-social-shre' ), '<code>naked_social_share_buttons();</code>' ),
						'type'    => 'multicheck',
						'std'     => array(),
						'options' => array(
							'blog_archive' => __( 'Blog Archives', 'naked-social-share' ),
							'blog_single'  => __( 'Single Posts', 'naked-social-share' ),
							'pages'        => __( 'Pages', 'naked-social-share' )
						)
					),
					'disable_counters' => array(
						'id'   => 'disable_counters',
						'name' => __( 'Disable Share Counters', 'naked-social-share' ),
						'desc' => __( 'If checked, the number of shares for each post/site will not be displayed.', 'naked-social-share' ),
						'type' => 'checkbox',
						'std'  => false
					),
					'twitter_handle'   => array(
						'id'          => 'twitter_handle',
						'name'        => __( 'Twitter  Handle', 'naked-social-share' ),
						'desc'        => __( 'Enter your Twitter handle (WITHOUT the @ sign)', 'naked-social-shre' ),
						'type'        => 'text',
						'placeholder' => 'NoseGraze',
					),
					'social_sites'     => array(
						'id'   => 'social_sites',
						'name' => __( 'Social Media Sites', 'naked-social-share' ),
						'desc' => __( 'Drag the sites you want to display buttons for into the "Enabled" column.', 'naked-social-share' ),
						'type' => 'sorter',
						'std'  => array(
							'enabled'  => array(
								'twitter'     => array(
									'name' => __( 'Twitter', 'naked-social-share' ),
								),
								'facebook'    => array(
									'name' => __( 'Facebook', 'naked-social-share' ),
								),
								'pinterest'   => array(
									'name' => __( 'Pinterest', 'naked-social-share' ),
								),
								'stumbleupon' => array(
									'name' => __( 'StumbleUpon', 'naked-social-share' )
								),
							),
							'disabled' => array(
								'google'   => array(
									'name' => __( 'Google+', 'naked-social-share' )
								),
								'linkedin' => array(
									'name' => __( 'LinkedIn', 'naked-social-share' )
								)
							)
						)
					)
				)
			),
		) );

		// Create a new settings object.
		$settings = new Nose_Graze_Settings(
			array(
				'page_title'   => __( 'Naked Social Share', 'naked-social-share' ),
				'menu_slug'    => 'naked-social-share',
				'options_slug' => 'naked_ss_',
			)
		);
		$settings->set_fields( $fields );

		// Update the environment variable with our settings array.
		$this->settings = $settings->get_options();
	}

	/**
	 * Adds the CSS and JavaScript for the plugin.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function add_scripts_styles() {
		// Load Font Awesome if it's enabled.
		if ( isset( $this->settings['load_fa'] ) && $this->settings['load_fa'] == true ) {
			wp_register_style( 'font-awesome', esc_url( $this->assets_url ) . 'css/font-awesome.min.css', array(), '4.3.0' );
			wp_enqueue_style( 'font-awesome' );
		}

		// Load the default styles if they're enabled.
		if ( isset( $this->settings['load_styles'] ) && $this->settings['load_styles'] == true ) {
			wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/naked-social-share.css', array(), $this->_version );
			wp_enqueue_style( $this->_token . '-frontend' );
		}

		if ( ! isset( $this->settings['disable_js'] ) || $this->settings['disable_js'] === false ) {
			wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/naked-social-share' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
			wp_enqueue_script( $this->_token . '-frontend' );
		}
	}

	/**
	 * Filters the_content
	 *
	 * Adds the social share buttons below blog posts
	 * if we've opted to display them automatically.
	 *
	 * @param string $content Unfiltered post content
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string Content with buttons after it
	 */
	public function auto_add_buttons( $content ) {
		// We do not want to automatically add buttons -- bail.
		if ( ! isset( $this->settings['auto_add'] ) || empty( $this->settings['auto_add'] ) ) {
			return $content;
		}

		// Proceed with post type checks.
		global $post;
		$post_type = get_post_type( $post );

		// This isn't a post or a page -- bail.
		if ( $post_type != 'page' && $post_type != 'post' ) {
			return $content;
		}

		// This is a page and we haven't specified to add pages -- bail.
		if ( $post_type == 'page' && ! array_key_exists( 'pages', $this->settings['auto_add'] ) ) {
			return $content;
		}

		// This is a post in the archive and we haven't specified to
		// add the buttons there -- bail.
		if ( ! is_single() && $post_type == 'post' && ! array_key_exists( 'blog_archive', $this->settings['auto_add'] ) ) {
			return $content;
		}

		// This is a single post page and we haven't specified to
		// add the buttons there -- bail.
		if ( is_single() && $post_type == 'post' && ! array_key_exists( 'blog_single', $this->settings['auto_add'] ) ) {
			return $content;
		}

		// Add the social share buttons after the post content.
		ob_start();
		naked_social_share_buttons();

		return $content . ob_get_clean();
	}

}