<?php

class Modula_PRO_Addon {

	private $site_host;
	private $addons = array();
	public $wpchill_upsells = false;

	function __construct() {

		add_filter( 'modula_addon_button_action', array( $this, 'output_download_link' ), 10, 2 );
		add_filter( 'modula_addon_server_url', array( $this, 'add_license_to_url' ), 10, 2 );

		// Add script for installing addons
		add_action( 'admin_enqueue_scripts', array( $this, 'addons_scripts' ) );

		// Add ajax action in order to install our addons
		add_action( 'wp_ajax_modula-install-addons', array( $this, 'install_addons' ), 20 );

		add_action( 'wp_ajax_modula-activate-addon', array( $this, 'activate_addon' ), 30 );
		add_action( 'wp_ajax_modula-deactivate-addon', array( $this, 'deactivate_addon' ), 30 );

		add_filter( 'modula_package_sortage', array( $this, 'sort_addons' ), 30 );

		// Add extra action buttons to extensions page
		add_action( 'modula_extensions_tabs_extra_actions', array( $this, 'extra_extensions_actions' ), 30 );

		add_action( 'wp_ajax_modula-get-all-addons', array( $this, 'get_all_addons' ), 30 );

		add_action( 'modula_addon_info', array( $this, 'addon_update' ), 30, 2 );
		
		// Add install addon notifications in admin settings tabs
		add_action('modula_admin_tab_compression', array( $this, 'modula_speedup_notification' ) );
		add_action('modula_admin_tab_standalone', array( $this, 'modula_albums_notification' ) );
		add_action('modula_admin_tab_shortcodes', array( $this, 'modula_advanced_shortcodes_notification' ) );
		add_action('modula_admin_tab_watermark', array( $this, 'modula_watermark_notification' ) );
		add_action('modula_admin_tab_roles', array( $this, 'modula_roles_notification' ) );

		// Get website domain
		if ( function_exists( 'domain_mapping_siteurl' ) ) {
			$this->site_host = domain_mapping_siteurl( get_current_blog_id() );
		} else {
			$this->site_host = site_url();
		}

		// Get License key
		$this->license_key = trim( get_option( 'modula_pro_license_key' ) );

		add_action( 'plugins_loaded', array( $this, 'load_addon_upsells' ), 15 );

	}

	/**
	 * Load the upsells class
	 */
	public function load_addon_upsells() {

		// Initialize WPChill upsell class
		$args = apply_filters( 'modula_upsells_args', array(
			'shop_url' => 'https://wp-modula.com',
			'slug'     => 'modula',
		) );

		if ( class_exists( 'WPChill_Upsells' ) ) {
			$this->wpchill_upsells = WPChill_Upsells::get_instance( $args );
		}

	}

	private function check_for_addons() {

		if ( false !== ( $data = get_transient( 'modula_pro_licensed_extensions' ) ) ) {
			$this->addons = is_array( $data ) ? $data : array();
			return;
		}

		// Make sure this matches the exact URL from your site.
		$url = apply_filters( 'modula_addon_server_url', MODULA_PRO_STORE_URL . '/wp-json/mt/v1/get-licensed-extensions' );
		$url = add_query_arg(
			array(
				'license' => $this->license_key,
				'url'     => $this->site_host,
			),
			$url
		);

		// Get data from the remote URL.
		$response = wp_remote_get( $url );

		if ( ! is_wp_error( $response ) ) {

			// Decode the data that we got.
			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $data ) && is_array( $data ) ) {

				$this->addons = $data;

				// Store the data for a week.
				set_transient( 'modula_pro_licensed_extensions', $data, 7 * DAY_IN_SECONDS );
			}
		}

	}

	/**
	 * Sort addons based on packages
	 *
	 * @param $addons
	 *
	 * @return mixed
	 * @since 2.5.0
	 */
	public function sort_addons( $addons ) {

		$this->check_for_addons();
		if ( $this->addons && $addons ) {

			$i = 99;
			$j = 1;

			foreach ( $addons as $key => $addon ) {

				if ( array_key_exists( $addon[ 'slug' ], $this->addons ) ) {
					$addons[ $key ][ 'priority' ] = $j;
					$j++;
				} else {
					$addons[ $key ][ 'priority' ] = $i;
					$i--;
				}
			}
		}

		uasort( $addons, array( 'Modula_Helper', 'sort_data_by_priority' ) );

		return $addons;
	}

	public function addons_scripts( $hook ) {

		if ( 'modula-gallery_page_modula-addons' == $hook ) {
			wp_enqueue_script( 'modula-pro-addon', MODULA_PRO_URL . 'assets/js/wp-modula-addons.js', array( 'jquery' ), '2.0.0', true );
			$args = array(
				'install_nonce'                     => wp_create_nonce( 'modula-pro-install' ),
				'connect_error'                     => esc_html__( 'ERROR: There was an error connecting to the server, Please try again.', 'modula-pro' ),
				'installing_text'                   => esc_html__( 'Installing addon...', 'modula-pro' ),
				'activating_text'                   => esc_html__( 'Activating addon...', 'modula-pro' ),
				'deactivating_text'                 => esc_html__( 'Deactivating addon...', 'modula-pro' ),
				'installing_mass_addons'            => esc_html__( 'Installing & Activating all addons, please wait..','modula-pro' ),
				'deactivating_mass_addons'          => esc_html__( 'Deactivating all addons, please wait..','modula-pro'  ),
				'installing_mass_addons_complete'   => esc_html__( 'All addons have been installed & activated.','modula-pro'  ),
				'deactivating_mass_addons_complete' => esc_html__( 'All addons have been deactivated.','modula-pro'  )
			);
			wp_localize_script( 'modula-pro-addon', 'modulaPRO', $args );
		}

	}

	public function add_license_to_url( $url ) {
		return add_query_arg(
			array(
				'license' => get_option( 'modula_pro_license_key' ),
				'url'     => site_url(),
			),
			$url
		);
	}

	public function output_download_link( $link, $addon ) {

		if ( empty( $this->addons ) && '' != $this->license_key ) {
			$this->check_for_addons();
		}

		$action = 'install';

		if ( ! isset( $addon['slug'] ) ) {
			$url  = admin_url( 'edit.php?post_type=modula-gallery&page=modula&modula-tab=licenses' );
			$link = '<a href="' . $url . '" class="button button-primary">' . esc_html__( 'Add license', 'modula-pro' ) . '</a>';
			return $link;
		}

        $licenses_status = get_option('modula_pro_license_status', false);

		if ( array_key_exists( $addon['slug'], $this->addons ) && !(!$licenses_status || 'valid' != $licenses_status->license) ) {

			$slug        = $addon['slug'];
			$plugin_path = $slug . '/' . $slug . '.php';

			if ( $this->check_plugin_is_installed( $addon['slug'] ) && ! $this->check_plugin_is_active( $plugin_path ) ) {
				$action = 'activate';
			} elseif ( $this->check_plugin_is_active( $plugin_path ) ) {
				$action = 'installed';
			}

			if ( 'install' != $action ) {
				$url = $this->create_plugin_link( $action, $plugin_path );
			} else {
				$url = $this->addons[ $addon['slug'] ]['download_link'];
			}


			$settings_url = '';

			switch ( $slug ) {
				case 'modula-albums':
					$settings_url = admin_url( 'edit.php?post_type=modula-gallery&page=modula&modula-tab=standalone' );
					break;
				case 'modula-speedup':
					$settings_url = admin_url( 'edit.php?post_type=modula-gallery&page=modula&modula-tab=compression' );
					break;
				case 'modula-watermark':
					$settings_url = admin_url( 'edit.php?post_type=modula-gallery&page=modula&modula-tab=watermark' );
					break;
				case 'modula-roles':
					$settings_url = admin_url( 'edit.php?post_type=modula-gallery&page=modula&modula-tab=roles' );
					break;

			}


			$attr = '';

			if ( 'installed' != $action ) {
				$attr = 'disabled="disabled"';
			}

			$addons_with_settings = array(
				'modula-watermark','modula-albums','modula-speedup','modula-roles'
			);

			$link = '';

			$link .= '<div class="modula-toggle">';
			$link .= '<input class="modula-toggle__input" type="checkbox" name="modula-settings[helpergrid]" data-action="' . esc_attr( $action ) . '" data-addonurl="' . esc_url( $url ) . '" value="1" data-path="'.esc_attr($plugin_path).'" ' . checked( 'installed', $action, false ) . '>';
			$link .= '<div class="modula-toggle__items">';
			$link .= '<span class="modula-toggle__track"></span>';
			$link .= '<span class="modula-toggle__thumb"></span>';
			$link .= '<svg class="modula-toggle__off" width="6" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 6 6"><path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path></svg>';
			$link .= '<svg class="modula-toggle__on" width="2" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 2 6"><path d="M0 0h2v6H0z"></path></svg>';
			$link .= '</div>';
			$link .= '</div>';
			$link .= '<span class="modula-action-texts"></span>';

			if ( in_array( $slug, $addons_with_settings ) ) {
				$link .= '<a href="' . esc_url( $settings_url ) . '" ' . $attr . ' class="button button-secondary modula-addon-action">' . esc_html__( 'Settings','modula-pro' ) . '</a>';
			}

		} elseif ( '' != $this->license_key && !(!$licenses_status || 'valid' != $licenses_status->license) ) { //the user has entered a license key, but this extension requires an upgrade
			$url  = MODULA_PRO_STORE_UPGRADE_URL . '?utm_source=modula-pro&utm_campaign=upsell&utm_medium=' . $addon['slug'] . '&license=' . $this->license_key;
			$link = '<a target="_blank" href="' . esc_url($url) . '" class="button button-primary">' . esc_html__( 'Upgrade', 'modula-pro' ) . '</a>';
		} else {
			$url  = admin_url( 'edit.php?post_type=modula-gallery&page=modula&modula-tab=licenses' );
			$link = '<a href="' . esc_url($url) . '" class="button button-primary">' . esc_html__( 'Add license', 'modula-pro' ) . '</a>';
		}

		return $link;
	}

	// Function to check if a plugin is active
	private function create_plugin_link( $state, $slug ) {
		$string = '';
		switch ( $state ) {
			case 'installed':
				$string = add_query_arg(
					array(
						'action'        => 'deactivate',
						'plugin'        => rawurlencode( $slug ),
						'plugin_status' => 'all',
						'paged'         => '1',
						'_wpnonce'      => wp_create_nonce( 'deactivate-plugin_' . $slug ),
					),
					admin_url( 'plugins.php' )
				);
				break;
			case 'activate':
				$string = add_query_arg(
					array(
						'action'        => 'activate',
						'plugin'        => rawurlencode( $slug ),
						'plugin_status' => 'all',
						'paged'         => '1',
						'_wpnonce'      => wp_create_nonce( 'activate-plugin_' . $slug ),
					),
					admin_url( 'plugins.php' )
				);
				break;
			default:
				$string = '';
				break;
		}// End switch().

		return $string;
	}

	private function _get_plugins( $plugin_folder = '' ) {

		if ( ! empty( $this->plugins ) ) {
			return $this->plugins;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->plugins = get_plugins( $plugin_folder );
		return $this->plugins;
	}

	private function check_plugin_is_installed( $slug ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {
			return true;
		}
		return false;
	}
	/**
	 * @return bool
	 */
	private function check_plugin_is_active( $plugin_path ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_path ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			return is_plugin_active( $plugin_path );
		}
	}

	// Install Addons
	public function install_addons() {

		// Run a security check first.
		check_admin_referer( 'modula-pro-install', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			echo json_encode( array( 'error' => esc_html__( 'There was an error installing the addon. Please try again.', 'modula-pro' ) ) );
			die;
		}

		if ( ! isset( $_POST['plugin'] ) ) {
			echo json_encode( array( 'error' => esc_html__( 'There was an error installing the addon. Please try again.', 'modula-pro' ) ) );
			die;
		}

		$download_url = esc_url( $_POST['plugin'] );
		if ( false === strpos( $download_url, MODULA_PRO_STORE_URL ) ) {
			echo json_encode( array( 'error' => esc_html__( 'There was an error installing the addon. Please try again.', 'modula-pro' ) ) );
			die;
		}

		global $hook_suffix;

		// Set the current screen to avoid undefined notices.
		set_current_screen();

		// Prepare variables.
		$method = '';
		$url    = add_query_arg(
			array(
				'page' => 'modula-pro-settings',
			),
			admin_url( 'admin.php' )
		);
		$url    = esc_url( $url );

		// Start output bufferring to catch the filesystem form if credentials are needed.
		ob_start();
		if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, null ) ) ) {
			$form = ob_get_clean();
			echo json_encode( array( 'form' => $form ) );
			die;
		}

		// If we are not authenticated, make it happen now.
		if ( ! WP_Filesystem( $creds ) ) {
			ob_start();
			request_filesystem_credentials( $url, $method, true, false, null );
			$form = ob_get_clean();
			echo json_encode( array( 'form' => $form ) );
			die;
		}

		// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once MODULA_PRO_PATH . 'includes/admin/class-modula-pro-skin.php';

		// Create the plugin upgrader with our custom skin.
		$installer = new Plugin_Upgrader( $skin = new Modula_PRO_Skin() );
		$installer->install( $download_url );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();
		if ( $installer->plugin_info() ) {
			$plugin_basename = $installer->plugin_info();
			echo json_encode(
				array(
					'plugin' => $plugin_basename,
				)
			);
			die;
		}


		// Send back a response.
		echo json_encode( true );
		die;

	}

	/**
	 * Activate Modula's Addons
	 *
	 * @since 2.5.0
	 */
	public function activate_addon() {

		check_admin_referer( 'modula-pro-install', 'nonce' );

		if ( !isset( $_POST[ 'plugin_path' ] ) ) {
			echo json_encode( array( 'error' => esc_html__( 'No such addons exists.', 'modula-pro' ) ) );
			die;
		}

		activate_plugin( $_POST[ 'plugin_path' ] );

		echo json_encode(
			array(
				'text' => esc_html__( 'Addon activated', 'modula-pro' )
			)
		);

		die;

	}

	/**
	 * Dectivate Modula's Addons
	 *
	 * @since 2.5.0
	 */
	public function deactivate_addon(){

		check_admin_referer( 'modula-pro-install', 'nonce' );

		if ( !isset( $_POST[ 'plugin_path' ] ) ) {
			echo json_encode( array( 'error' => esc_html__( 'No such addons exists.', 'modula-pro' ) ) );
			die;
		}

		deactivate_plugins( $_POST[ 'plugin_path' ] );

		echo json_encode(
			array(
				'text' => esc_html__( 'Addon deactivated.', 'modula-pro' )
			)
		);

		die;

	}

	/**
	 * Add extra action links to extensions tab
	 *
	 * @since 2.5.0
	 */
	public function extra_extensions_actions() {

		if ( isset( $_GET['extensions'] ) && 'free' == $_GET['extensions'] ) {
			return;
		}

		$quick_action_options = apply_filters(
			'modula_extensions_quick_actions',
			array(
				'modula-install-all-addons'   => esc_html__( 'Install all addons', 'modula-pro' ),
				'modula-uninstall-all-addons' => esc_html__( 'Deactivate all addons', 'modula-pro' )
			) );

		$html = '<div class="modula-pro-extensions-actions">';
		$html .= '<select id="modula-extensions-quick-actions">';
		$html .= '<option value="false" selected="selected">' . esc_html__( 'Quick actions', 'modula-pro' ) . '</option>';

		foreach ( $quick_action_options as $value => $name ) {
			$html .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $name ) . '</option>';
		}

		$html .= '</select>';

		$html .= '<a href="#" id="modula-quick-actions-go" class="button button-secondary">' . esc_html__( 'Apply', 'modula-pro' ) . '</a>';
		$html .= '</div>';

		echo $html;
	}

	/**
	 * Install all addons
	 *
	 * @since 2.5.0
	 */
	public function get_all_addons() {

		check_admin_referer( 'modula-pro-install', 'nonce' );

		if ( empty( $this->addons ) && '' != $this->license_key ) {
			$this->check_for_addons();
		}

		if ( empty( $this->addons ) ) {
			echo json_encode( array( 'error' => esc_html__( 'No addons found.', 'modula-pro' ) ) );
			wp_die();
		}

		echo json_encode( $this->addons );
		wp_die();

	}

	/**
	 * Update addon from extensions tab
	 *
	 * @param $addon
	 * @param $plugin_data
	 * @Since 2.5.0
	 */
	public function addon_update( $addon, $plugin_data ){
		if ( $plugin_data && $addon['version'] != $plugin_data['Version'] && class_exists( 'Modula_PRO' ) ){
			echo '<a href="' . wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . esc_attr( $addon['slug'] . '/' . $addon['slug'] . '.php' ) ), 'upgrade-plugin_' . esc_attr( $addon['slug'] . '/' . $addon['slug'] . '.php' ) ) . '"><span>' . esc_html__( 'Update now', 'modula-best-grid-gallery' ) . '</span></a>';
		}

	}

	/**
	 * Add notification to install Modula Albums
	 * @Since 2.5.5
	 */
	public function modula_albums_notification(){

		if ( ! class_exists( 'Modula_Albums' ) && $this->wpchill_upsells &&  ! $this->wpchill_upsells->is_upgradable_addon( 'modula-albums' ) ){
			
			echo sprintf( esc_html__( 'In order to use Modula Albums addon you need to install it from %shere%s.', 'modula-pro' ), '<a href="' . admin_url( 'edit.php?post_type=modula-gallery&page=modula-addons' ) . '" target="blank">', '</a>' );
		}

	}

	/**
	 * Add notification to install Modula SpeedUp
	 * @Since 2.5.5
	 */
	public function modula_speedup_notification(){

		if ( !class_exists( 'Modula_SpeedUp' ) && $this->wpchill_upsells && ! $this->wpchill_upsells->is_upgradable_addon( 'modula-speedup' ) ){
			?>

			<div class="modula-settings-tab-upsell">
				<h3><?php esc_html_e( 'Modula SpeedUp', 'modula-best-grid-gallery' ) ?></h3>
				<p><?php echo sprintf( esc_html__( 'In order to use Modula SpeedUp addon you need to install it from %shere%s.', 'modula-pro' ), '<a href="' . admin_url( 'edit.php?post_type=modula-gallery&page=modula-addons' ) . '" target="blank">', '</a>' ); ?></p>
			</div>
			
			<?php
		}

	}

	/**
	 * Add notification to install Modula Advanced Shortcodes
	 * @Since 2.5.5
	 */
	public function modula_advanced_shortcodes_notification(){

		if ( ! class_exists( 'Modula_Advanced_Shortcodes' ) && $this->wpchill_upsells && ! $this->wpchill_upsells->is_upgradable_addon( 'modula-advanced-shortcodes' ) ){
			?>

			<div class="modula-settings-tab-upsell">
				<h3><?php esc_html_e( 'Modula Advanced Shortcodes', 'modula-best-grid-gallery' ) ?></h3>
				<p><?php echo sprintf( esc_html__( 'In order to use Modula Advanced Shortcodes addon you need to install it from %shere%s.', 'modula-pro' ), '<a href="' . admin_url( 'edit.php?post_type=modula-gallery&page=modula-addons' ) . '" target="blank">', '</a>' ); ?></p>
			</div>
			
			<?php
		}

	}

	/**
	 * Add notification to install Modula Watermark
	 * @Since 2.5.5
	 */
	public function modula_watermark_notification(){

		if ( ! class_exists( 'Modula_Watermark' ) && $this->wpchill_upsells && ! $this->wpchill_upsells->is_upgradable_addon( 'modula-watermark' )){
			?>

			<div class="modula-settings-tab-upsell">
				<h3><?php esc_html_e( 'Modula Watermark', 'modula-best-grid-gallery' ) ?></h3>
				<p><?php echo sprintf( esc_html__( 'In order to use Modula Watermark addon you need to install it from %shere%s.', 'modula-pro' ), '<a href="' . admin_url( 'edit.php?post_type=modula-gallery&page=modula-addons' ) . '" target="blank">', '</a>' ); ?></p>
			</div>
			
			<?php
		}

	}

	/**
	 * Add notification to install Modula Roles
	 * @Since 2.5.5
	 */
	public function modula_roles_notification(){

		if ( !class_exists( 'Modula_Roles' ) && $this->wpchill_upsells && ! $this->wpchill_upsells->is_upgradable_addon( 'modula-roles' )){
			?>

			<div class="modula-settings-tab-upsell">
				<h3><?php esc_html_e( 'Modula Roles', 'modula-best-grid-gallery' ) ?></h3>
				<p><?php echo sprintf( esc_html__( 'In order to use Modula Roles addon you need to install it from %shere%s.', 'modula-pro' ), '<a href="' . admin_url( 'edit.php?post_type=modula-gallery&page=modula-addons' ) . '" target="blank">', '</a>' ); ?></p>
			</div>
			
			<?php
		}

	}

}

new Modula_PRO_Addon();
