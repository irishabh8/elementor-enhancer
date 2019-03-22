<?php
/**
 * Elementor Enhancer Loader.
 *
 * @package Elementor Enhancer
 */

if ( ! class_exists( 'EE_Loader' ) ) {

	/**
	 * Class EE_Loader.
	 */
	final class EE_Loader {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 *  Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Action link.
			$plugin = plugin_basename( ELEMENTOR_ENHANCER_FILE );
			add_filter( "plugin_action_links_$plugin", array($this, 'ee_plugin_add_settings_link') );

			// Activation hook.
			register_activation_hook( ELEMENTOR_ENHANCER_FILE, array( $this, 'activation_reset' ) );

			// deActivation hook.
			register_deactivation_hook( ELEMENTOR_ENHANCER_FILE, array( $this, 'deactivation_reset' ) );

			$this->define_constants();

			add_action( 'plugins_loaded', array( $this, 'load_ee_plugin' ) );
		}

		/**
		 * Defines all constants
		 *
		 * @since 1.0.0
		 */
		public function define_constants() {
			define( 'ELEMENTOR_ENHANCER_BASE', plugin_basename( ELEMENTOR_ENHANCER_FILE ) );
			define( 'ELEMENTOR_ENHANCER_DIR', plugin_dir_path( ELEMENTOR_ENHANCER_FILE ) );
			define( 'ELEMENTOR_ENHANCER_URL', plugins_url( '/', ELEMENTOR_ENHANCER_FILE ) );
			define( 'ELEMENTOR_ENHANCER_VER', '1.0.0' );
			define( 'ELEMENTOR_ENHANCER_MODULES_DIR', ELEMENTOR_ENHANCER_DIR . 'modules/' );
			define( 'ELEMENTOR_ENHANCER_MODULES_URL', ELEMENTOR_ENHANCER_URL . 'modules/' );
			define( 'ELEMENTOR_ENHANCER_SLUG', 'elementor-enhancer' );
			define( 'ELEMENTOR_ENHANCER_CATEGORY', 'Enhancing Elementor' );
		}

		/**
		 * Creates an Action Menu
		 *
		 * @since 1.0.0
		 */
		public function ee_plugin_add_settings_link( $links ) {
			$settings_link = sprintf( '<a href="admin.php?page=elementor-enhancer-settings">' . __( 'Manage Elements', 'elementor-enhancer' ) . '</a>' );
			array_push( $links, $settings_link );
			return $links;
		}

		/**
		 * Loads plugin files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		function load_ee_plugin() {

			if ( ! did_action( 'elementor/loaded' ) ) {
				/* TO DO */
				add_action( 'admin_notices', array( $this, 'ee_plugin_fails_to_load' ) );
				return;
			}

			$this->load_textdomain();

			require_once ELEMENTOR_ENHANCER_DIR . 'classes/class-elementor-enhancer-core-plugin.php';
		}

		/**
		 * Load Elementor Enhancer Text Domain.
		 * This will load the translation textdomain depending on the file priorities.
		 *      1. Global Languages /wp-content/languages/elementor-enhancer/ folder
		 *      2. Local dorectory /wp-content/plugins/elementor-enhancer/languages/ folder
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function load_textdomain() {
			// Default languages directory for "elementor-enhancer".
			$lang_dir = ELEMENTOR_ENHANCER_DIR . 'languages/';

			/**
			 * Filters the languages directory path to use for AffiliateWP.
			 *
			 * @param string $lang_dir The languages directory path.
			 */
			$lang_dir = apply_filters( 'elementor_enhancer_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter.
			global $wp_version;

			$get_locale = get_locale();

			if ( $wp_version >= 4.7 ) {
				$get_locale = get_user_locale();
			}

			/**
			 * Language Locale for Elementor Enhancer
			 *
			 * @var $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
			 *                  otherwise uses `get_locale()`.
			 */
			$locale = apply_filters( 'plugin_locale', $get_locale, 'elementor-enhancer' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'elementor-enhancer', $locale );

			// Setup paths to current locale file.
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/elementor-enhancer/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/elementor-enhancer/ folder.
				load_textdomain( 'elementor-enhancer', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/elementor-enhancer/languages/ folder.
				load_textdomain( 'elementor-enhancer', $mofile_local );
			} else {
				// Load the default language files.
				load_plugin_textdomain( 'elementor-enhancer', false, $lang_dir );
			}
		}
		/**
		 * Fires admin notice when Elementor is not installed and activated.
		 *
		 * @since 0.0.1
		 *
		 * @return void
		 */
		public function ee_plugin_fails_to_load() {
			$class = 'notice notice-error';
			/* translators: %s: html tags */
			$message = sprintf( __( 'The %1$sElementor Enhancer%2$s plugin requires %1$sElementor%2$s plugin installed & activated.', 'elementor-enhancer' ), '<strong>', '</strong>' );

			$plugin = 'elementor/elementor.php';

			if ( _is_elementor_installed() ) {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}

				$action_url   = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
				$button_label = __( 'Activate Elementor', 'elementor-enhancer' );

			} else {
				if ( ! current_user_can( 'install_plugins' ) ) {
					return;
				}

				$action_url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ), 'install-plugin_elementor' );
				$button_label = __( 'Install Elementor', 'elementor-enhancer' );
			}

			$button = '<p><a href="' . $action_url . '" class="button-primary">' . $button_label . '</a></p><p></p>';

			printf( '<div class="%1$s"><p>%2$s</p>%3$s</div>', esc_attr( $class ), $message, $button );
		}

		/**
		 * Activation Reset
		 */
		function activation_reset() {

		}

		/**
		 * Deactivation Reset
		 */
		function deactivation_reset() {

		}
	}

	/**
	 *  Prepare if class 'EE_Loader' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	EE_Loader::get_instance();
}

/**
 * Is elementor plugin installed.
 */
if ( ! function_exists( '_is_elementor_installed' ) ) {

	/**
	 * Check if Elementor Pro is installed
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	function _is_elementor_installed() {
		$path    = 'elementor/elementor.php';
		$plugins = get_plugins();

		return isset( $plugins[ $path ] );
	}
}
