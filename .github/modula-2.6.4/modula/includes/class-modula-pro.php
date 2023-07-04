<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      2.0.0
 */
class Modula_PRO {

	private $license = false;

	function __construct() {

		$this->license = self::check_for_license();

		$this->load_dependencies();

		// Check for LITE version of the plugin
		if ( !$this->check_for_lite() ) {
			return;
		}

		$wpchill_license_checker = Wpchill_License_Checker::get_instance( 'modula', $this->get_license_checker_args() );

		add_action( 'init', array( $this, 'set_locale' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_gallery_scripts' ) );

		add_filter( 'modula_necessary_scripts', array( $this, 'enqueue_necessary_scripts' ) );
		add_filter( 'modula_necessary_styles', array( $this, 'modula_necessary_styles' ) );

		// Add Filters for Modula Troubleshooting
		add_filter( 'modula_troubleshooting_fields', array( $this, 'add_troubleshooting_fields' ) );
		add_filter( 'modula_troubleshooting_defaults', array( $this, 'add_troubleshooting_defaults' ) );
		add_filter( 'modula_troubleshooting_frontend_handles', array( $this, 'add_main_pro_files' ), 60, 2 );
		add_filter( 'modula_troubleshooting_frontend_handles', array( $this, 'check_hovereffect' ), 20, 2 );
		add_filter( 'modula_troubleshooting_frontend_handles', array( $this, 'check_linkshortcode' ), 20, 2 );

		// modula-link shortcode
		add_action( 'init', array( $this, 'add_shortcode' ) );

		// Modify Modula Gallery config
		add_filter( 'modula_gallery_settings', array( $this, 'modula_pro_config' ), 10, 2 );
		add_action( 'modula_shortcode_before_items', 'modula_pro_output_filters', 15 );
		add_filter( 'modula_gallery_template_data', 'modula_pro_extra_modula_section_classes' );
		add_action( 'modula_shortcode_after_items', 'modula_pro_output_filters', 15 );
		add_filter( 'modula_shortcode_item_data', 'modula_pro_add_filters', 30, 3 );
		add_filter( 'modula_gallery_images', array( $this, 'modula_pro_max_count' ), 10, 2 );
		add_filter( 'modula_pagination_links', array( $this, 'modula_pro_max_count' ), 10, 2 );
		add_action( 'modula_shortcode_after_items', array( $this, 'output_removed_items' ), 10, 3 );

		// Shortpixel fix
		add_filter( 'modula_shortcode_item_data', array( $this, 'shortpixel_fix' ), 99, 3 );

		// Modify CSS
		add_filter( 'modula_shortcode_css', array( $this, 'generate_new_css' ), 10, 3 );

		// Output lightboxes options
		add_filter( 'modula_fancybox_options', array( $this, 'output_lightbox_options' ), 10, 2 );
		add_filter( 'modula_fancybox_options', array( $this, 'output_lightbox_close_button' ), 9999, 2 );
		add_action( 'modula_extra_scripts', array( $this, 'check_for_fonts' ) );

		add_filter( 'modula_shortcode_item_data', array( $this, 'modula_pro_extra_item_data' ), 16, 3 );

		add_action( 'modula_extra_scripts', array( $this, 'output_extra_effects_scripts' ) );

		add_action( 'modula_item_after_image', array( $this, 'extra_effects_extra_elements' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'preview_extra_effects_scripts' ) );

		// Add new path for templates
		add_filter( 'modula_template_paths', array( $this, 'add_modula_pro_templates_path' ), 20 );

		add_filter( 'modula_gallery_template_data', array( $this, 'filter_class_helper' ), 20, 1 );

		add_action( 'modula_shortcode_before_items', array( $this, 'modula_gallery_title' ), 5 );

		// Add aditional PRO effects
		add_filter( 'modula_effects_with_title', array( $this, 'aditional_pro_effects' ) );
		add_filter( 'modula_effects_with_description', array( $this, 'aditional_pro_effects' ) );
		add_filter( 'modula_effects_with_social', array( $this, 'aditional_pro_effects' ) );

		if ( is_admin() ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'license_script' ) );

			// Add license tab
			add_filter( 'modula_admin_page_tabs', array( $this, 'add_license_tab' ) );

			// Show the license tab content.
			add_action( 'modula_admin_tab_licenses', array( $this, 'show_licenses_tab' ) );

			add_action( 'admin_notices', array( $this, 'modula_license_notices' ), 99 );
			add_action( 'admin_enqueue_scripts', array( $this, 'modula_pro_license_check' ) );
			add_filter( 'modula_debug_information', array( $this, 'debug_information' ) );
			add_filter( 'modula_uninstall_db_options', array( $this, 'uninstall_process' ), 25, 1 );
			add_filter( 'modula_uninstall_transients', array( $this, 'uninstall_process_transients' ), 25, 1 );
			// Alter Shortcode column
			$cpt_name = apply_filters( 'modula_cpt_name', 'modula-gallery' );

			// Add needed filters onlyu if there is a license.
			if ( $this->license ) {

				// Remove Albums upsell metabox.
				add_action( 'do_meta_boxes', array( $this, 'remove_albums_upsell_metabox' ), 16, 1 );
				add_action( "manage_{$cpt_name}_posts_custom_column", array( $this, 'output_column' ), 20, 2 );
				add_action( 'modula_admin_after_shortcode_metabox', array( $this, 'output_link_shortcode' ) );

				// Enqueue block assets.
				add_action( 'modula_block_style', array( $this, 'enqueue_modula_block_styles') );
				add_action( 'modula_block_scripts', array( $this, 'enqueue_modula_block_scripts') );

			}
		}
	}

	/**
	 * Check if Modula Lite is Active
	 *
	 * @return bool
	 * @since 2.4.1
	 */
	private function check_for_lite() {

		$check = array(
			'installed' => Modula_Pro_Helper::check_plugin_is_installed( 'modula-best-grid-gallery' ),
			'active'    => Modula_Pro_Helper::check_plugin_is_active( 'modula-best-grid-gallery' ),
		);

		if ( $check['active'] ) {
			return true;
		}

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'display_lite_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_lite_scripts' ) );
		}

		return false;
	}

	/**
	 * Lets check for license
	 *
	 * @return bool
	*
	* @since 2.5.2
	* @since 2.5.6 moved here from class-modula-pro-upsells.php.
	*/
	public static function check_for_license() {

		$license_status = get_option( 'modula_pro_license_status' );

		// If the option is false it means it is empty
		if ( ! $license_status ) {
			return null;
		}

		// There is no license or license is not valid anymore, so we get all packages
		if ( 'valid' != $license_status->license ) {
			return false;
		}

		return true;
	}

	/**
	 * Display Install & Activate LITE Notice
	 *
	 * @since 2.4.1
	 */
	public function display_lite_notice() {

		$check = array(
			'installed' => Modula_Pro_Helper::check_plugin_is_installed( 'modula-best-grid-gallery' ),
			'active'    => Modula_Pro_Helper::check_plugin_is_active( 'modula-best-grid-gallery' ),
		);

		if ( !$check['installed'] ) {
			$label  = esc_html__( 'Install & Activate: Modula Lite', 'modula-pro' );
			$action = 'install';
			$url    = '#';
		} else {
			$label  = esc_html__( 'Activate: Modula Lite', 'modula-pro' );
			$action = 'activate';
			$url    = add_query_arg(
				array(
					'action'        => 'activate',
					'plugin'        => rawurlencode( Modula_Pro_Helper::_get_plugin_basename_from_slug( 'modula-best-grid-gallery' ) ),
					'plugin_status' => 'all',
					'paged'         => '1',
					'_wpnonce'      => wp_create_nonce( 'activate-plugin_' . Modula_Pro_Helper::_get_plugin_basename_from_slug( 'modula-best-grid-gallery' ) ),
				),
				admin_url( 'plugins.php' )
			);
		}

		wp_enqueue_style( 'modula-pro-install-lite', MODULA_PRO_URL . 'assets/css/install-lite.css' );

		echo '<div id="modula-install-lite" class="notice">';
		echo '<h1>' . esc_html__( 'Install & Activate Modula Lite', 'modula-pro' ) . '</h1>';
		echo '<h4>' . esc_html__( 'In order for Modula PRO to work properly, you\'ll also need to have Modula Lite installed & activated', 'modula-pro' ) . '</h4>';
		echo '<a href="' . esc_url( $url ) . '" data-action="' . esc_attr( $action ) . '" class="button button-primary button-hero" id="install-modula-lite">' . $label . '</a>';
		echo '</div>';
	}


	/**
	 * Displays an inline notice with some Modula styling.
	 *
	 * @param string  $notice  Programmatic Notice Name.
	 * @param string  $title   Title.
	 * @param string  $message Message.
	 * @param string  $type    Message Type (updated|warning|error) - green, yellow/orange and red respectively.
	 * @param integer $seconds Number of seconds transient is good for, after expires then notice re-appears - 0 means no transient, it's an option.
	 *
	 * @since 1.3.5
	 *
	 */
	public function display_inline_notice( $notice, $title, $message, $type = 'success', $button_text = '', $button_url = '', $is_dismissible = true, $seconds = false ) {

		// Display inline notice.
		?>
		<div class="updated modula-notice <?php echo sanitize_html_class( $type . ($is_dismissible ? ' -is-dismissible' : '') ); ?>"
		     data-seconds="<?php echo esc_attr( $seconds ); ?>" data-notice="<?php echo esc_attr( $notice ); ?>">
			<?php
			// Message.
			if ( !empty( $message ) ) {
				?>
				<p><?php echo $message; // @codingStandardsIgnoreLine ?></p>
				<?php
			}
			?>
		</div>
		<?php

	}

	public function modula_license_notices() {

		$licenses_status = get_option( 'modula_pro_license_status', false );

		if ( !get_option( 'modula_pro_license_status', false ) || 'valid' != get_option( 'modula_pro_license_status', false )->license ) {

			$message = sprintf( esc_html__( '%sModula Pro%s: No valid license key has been entered, so any installed Modula Addons have been turned off. %sPlease click here to enter your license key and begin receiving automatic updates.%s', 'modula-pro' ), '<strong>', '</strong>', '<a href="' . admin_url( 'edit.php?post_type=modula-gallery&page=modula&modula-tab=licenses' ) . '">', '</a>' );

			$this->display_inline_notice( 'warning-license-key', false, $message, 'error ', DAY_IN_SECONDS );

		}

	}

	public function modula_pro_license_check() {
		wp_enqueue_style( 'modula-pro-style', MODULA_PRO_URL . 'assets/css/modula-pro-admin-style.css' );
	}


	public function add_shortcode() {
		add_shortcode( 'modula-link', array( $this, 'modula_link_shortcode' ) );
	}


	public function modula_pro_extra_item_data( $item_data, $image, $settings ) {

		/**
		 * @since 2.2.2 after fancyBox update to v3.5.7
		 */
		// Check if not set prior by Modula Speedup with a highest priority filter
		if ( !isset( $item_data['link_attributes']['data-thumb'] ) ){
			$thumb = wp_get_attachment_image_src( $image['id'], 'thumbnail' );
			if ( $thumb ){
				$item_data['link_attributes']['data-thumb'] = $thumb[0];
			} else {
				$item_data['link_attributes']['data-thumb'] = $item_data['img_attributes']['data-src'];
			}
		}


		/**
		 * @since 2.3.0
		 */
		if ( isset( $item_data['link_attributes']['data-caption'] ) ) {

			if ( (isset( $settings['showTitleLightbox'] ) && '1' == $settings['showTitleLightbox']) && (isset( $settings['showCaptionLightbox'] ) && '1' == $settings['showCaptionLightbox']) ) {
				$item_data['link_attributes']['data-caption'] = esc_attr( '<p>' . $image['title'] . '</p><p>' . $image['description'] . '</p>' );
			} else if ( (isset( $settings['showTitleLightbox'] ) && '1' == $settings['showTitleLightbox']) && (!isset( $settings['showCaptionLightbox'] ) || '1' != $settings['showCaptionLightbox']) ) {
				$item_data['link_attributes']['data-caption'] = esc_attr( '<p>' . $image['title'] . '</p>' );
			} else if ( (!isset( $settings['showTitleLightbox'] ) || '1' != $settings['showTitleLightbox']) && (isset( $settings['showCaptionLightbox'] ) && '1' == $settings['showCaptionLightbox']) ) {
				$item_data['link_attributes']['data-caption'] = esc_attr( '<p>' . $image['description'] . '</p>' );
			} else {
				$item_data['link_attributes']['data-caption'] = '';
			}

		}

		if ( isset( $image['link'] ) && '' != $image['link'] ) {

			unset( $item_data['link_attributes']['data-caption'] );
			unset( $item_data['link_attributes']['rel'] );
			unset( $item_data['link_attributes']['data-fancybox'] );
			unset( $item_data['link_attributes']['aria-label'] );
			unset( $item_data['link_attributes']['title'] );

			$item_data['link_attributes']['href'] = $image['link'];

			if ( isset( $image['target'] ) && 1 == $image['target'] ) {
				$item_data['link_attributes']['target'] = '_blank';
			}

			$item_data['link_attributes']['class'][] = 'modula-simple-link';
			$item_data['item_classes'][]             = 'modula-simple-link';

		}

		return $item_data;
	}


	private function load_dependencies() {

		require_once MODULA_PRO_PATH . 'includes/modula-pro-helper-functions.php';
		require_once MODULA_PRO_PATH . 'includes/class-modula-pro-helper.php';
		require_once MODULA_PRO_PATH . 'includes/class-modula-pro-backwards-compatibility.php';
		require_once MODULA_PRO_PATH . 'includes/license-checker/class-wpchill-license-checker.php';

		if ( is_admin() ) {
			require_once MODULA_PRO_PATH . 'includes/admin/class-modula-pro-addon.php';
			require_once MODULA_PRO_PATH . 'includes/admin/modula-pro-addon-ajax.php';
			require_once MODULA_PRO_PATH . 'includes/admin/class-modula-pro-license-activator.php';
			// Load the PRO Smart Upsells
			require_once MODULA_PRO_PATH . 'includes/admin/class-modula-pro-upsells.php';

			// Load only license specific files.
			if ( $this->license ) {
				require_once MODULA_PRO_PATH . 'includes/admin/class-modula-pro-settings.php';
				require_once MODULA_PRO_PATH . 'includes/admin/class-modula-pro-migrate.php';
			}
		}
		
		if ( $this->license ) {
			require_once MODULA_PRO_PATH . 'includes/class-modula-pro-gutenberg.php';
		}		
	}

	// Register all pro scripts & style in order to be enqueue
	public function register_gallery_scripts() {

		// Modula PRO
		wp_register_style( 'modula-pro-effects', MODULA_PRO_URL . 'assets/css/effects.min.css', MODULA_PRO_VERSION, null );
		wp_register_script( 'modula-pro', MODULA_PRO_URL . 'assets/js/modula-pro.js', array( 'jquery' ), MODULA_PRO_VERSION, true );

		// Modula Pro scripts used for Tilt Hover Effects
		wp_register_script( 'modula-pro-tilt', MODULA_PRO_URL . 'assets/js/modula-pro-tilt.min.js', array( 'jquery' ), MODULA_PRO_VERSION, true );

		// Modula Link script
		wp_register_script( 'modula-link-shortcode', MODULA_PRO_URL . 'assets/js/modula-link.js', array( 'jquery' ), MODULA_PRO_VERSION, true );
	}


	public function check_for_fonts( $settings ) {

		if( ( isset( $settings['hide_title'] ) && "1" === $settings['hide_title'] ) && ( isset( $settings['hide_description'] ) && "1" === $settings['hide_description'] ) ){
			return;
		}

		if ( ! isset( $settings['captionsFontFamily'] ) || ! isset( $settings['titleFontFamily'] )  ) {
			return;
		}

		if ( 'Default' == $settings['captionsFontFamily'] && 'Default' == $settings['titleFontFamily'] ) {
			return;
		}

		$fonts = array();

		if ( 'Default' != $settings['titleFontFamily'] ) {
			if ( 'normal' == $settings['titleFontWeight'] ) {
				$fonts[$settings['titleFontFamily']] = array( 300, 400, 700 );
			} else {
				$fonts[$settings['titleFontFamily']] = array( intval( $settings['titleFontWeight'] ) );
			}
		}

		if ( 'Default' != $settings['captionsFontFamily'] ) {
			if ( 'normal' == $settings['captionFontWeight'] ) {
				$fonts_weights = array( 300, 400, 700 );
			} else {
				$fonts_weights = array( intval( $settings['captionFontWeight'] ) );
			}

			if ( isset( $fonts[$settings['captionsFontFamily']] ) ) {
				$fonts[$settings['captionsFontFamily']] = array_merge( $fonts[$settings['captionsFontFamily']], $fonts_weights );
			} else {
				$fonts[$settings['captionsFontFamily']] = $fonts_weights;
			}

		}

		if ( empty( $fonts ) ) {
			return;
		}

		$new_fonts = array();
		foreach ( $fonts as $font => $weights ) {
			$font_name   = str_replace( ' ', '+', $font );
			$new_fonts[] = $font_name . ':' . implode( ',', array_unique( $weights ) );
		}

		$fonts_string = implode( '|', $new_fonts );
		$font_url     = 'https://fonts.googleapis.com/css?family=' . $fonts_string . '&display=swap';
		wp_enqueue_style( 'modula-pro-font', $font_url, MODULA_PRO_VERSION, null );

	}

	public function output_lightbox_options( $fancybox_options, $settings ) {

		$fancybox_options['buttons'] = array();

		if ( isset( $settings['loop_lightbox'] ) && '1' == $settings['loop_lightbox'] ) {
			$fancybox_options['loop'] = true;
		}

		if ( isset( $fancybox_options['clickContent'] ) ) {
			unset( $fancybox_options['clickContent'] );
		}

		if ( isset( $settings['lightbox_toolbar'] ) && '1' == $settings['lightbox_toolbar'] ) {
			$fancybox_options['toolbar'] = true;
		} else {
			$fancybox_options['toolbar'] = false;
		}


		if ( isset( $settings['lightbox_infobar'] ) && '1' == $settings['lightbox_infobar'] ) {
			$fancybox_options['infobar'] = true;
		} else {
			$fancybox_options['infobar'] = false;
		}

		// Add all buttons

		if ( isset( $settings['lightbox_zoom'] ) && '1' == $settings['lightbox_zoom'] ) {
			$fancybox_options['buttons'][] = 'zoom';
		}

		if ( isset( $settings['lightbox_share'] ) && '1' == $settings['lightbox_share'] ) {
			$fancybox_options['buttons'][] = 'share';

			if ( isset( $settings['lightbox_facebook'] ) && '1' == $settings['lightbox_facebook'] ) {
				$fancybox_options['modulaShare'][] = 'facebook';
			}

			if ( isset( $settings['lightbox_twitter'] ) && '1' == $settings['lightbox_twitter'] ) {
				$fancybox_options['modulaShare'][] = 'twitter';
			}

			if ( isset( $settings['lightbox_whatsapp'] ) && '1' == $settings['lightbox_whatsapp'] ) {
				$fancybox_options['modulaShare'][] = 'whatsapp';
			}


			if ( isset( $settings['lightbox_linkedin'] ) && '1' == $settings['lightbox_linkedin'] ) {
				$fancybox_options['modulaShare'][] = 'linkedin';
			}

			if ( isset( $settings['lightbox_pinterest'] ) && '1' == $settings['lightbox_pinterest'] ) {
				$fancybox_options['modulaShare'][] = 'pinterest';
			}

			if ( isset( $settings['lightbox_email'] ) && '1' == $settings['lightbox_email'] ) {
				$fancybox_options['modulaShare'][] = 'email';
			}

			if ( isset( $settings['lightboxEmailSubject'] ) ) {
				$fancybox_options['lightboxEmailSubject'] = esc_html( $settings['lightboxEmailSubject'] );
			} else {
				$fancybox_options['lightboxEmailSubject'] = esc_html__( 'Check out this awesome image !!', 'modula-pro' );
			}

			if ( isset( $settings['lightboxEmailMessage'] ) ) {
				$fancybox_options['lightboxEmailMessage'] = esc_html( $settings['lightboxEmailMessage'] );
			} else {
				$fancybox_options['lightboxEmailMessage'] = esc_html__( 'Here is the link to the image : %%image_link%% and this is the link to the gallery : %%gallery_link%% ', 'modula-pro' );
			}

			if ( isset( $settings['galleryMessage'] ) ) {
				$fancybox_options['messageGallery'] = $settings['galleryMessage'];
			} else {
				$fancybox_options['messageGallery'] = esc_html__( 'Here is the link to the gallery :', 'modula-pro' );
			}

		}

		if ( isset( $settings['lightbox_download'] ) && '1' == $settings['lightbox_download'] ) {
			$fancybox_options['buttons'][] = 'download';
		}

		if ( isset( $settings['lightbox_thumbs'] ) && '1' == $settings['lightbox_thumbs'] ) {
			$fancybox_options['buttons'][] = 'thumbs';
		}

		if ( isset( $settings['lightbox_keyboard'] ) && '1' == $settings['lightbox_keyboard'] ) {
			$fancybox_options['keyboard'] = true;
		} else {
			$fancybox_options['keyboard'] = false;
		}

		if ( isset( $settings['lightbox_wheel'] ) && '1' == $settings['lightbox_wheel'] ) {
			$fancybox_options['wheel'] = true;
		} else {
			$fancybox_options['wheel'] = false;
		}

		if ( isset( $settings['lightbox_clickSlide'] ) && '1' == $settings['lightbox_clickSlide'] ) {
			$fancybox_options['clickSlide'] = 'close';
		}

		if ( isset( $settings['lightbox_dblclickSlide'] ) && '1' == $settings['lightbox_dblclickSlide'] ) {
			$fancybox_options['dblclickSlide'] = 'close';
		}

		if ( isset( $settings['lightbox_animationEffect'] ) && 'false' != $settings['lightbox_animationEffect'] ) {
			$fancybox_options['animationEffect'] = esc_html( $settings['lightbox_animationEffect'] );
		} else {
			$fancybox_options['animationEffect'] = false;
		}

		if ( isset( $settings['lightbox_animationDuration'] ) ) {
			$fancybox_options['animationDuration'] = (int)$settings['lightbox_animationDuration'];
		}

		if ( isset( $settings['lightbox_transitionEffect'] ) && 'false' != $settings['lightbox_transitionEffect'] ) {
			$fancybox_options['transitionEffect'] = esc_html( $settings['lightbox_transitionEffect'] );
		} else {
			$fancybox_options['transitionEffect'] = false;
		}

		if ( isset( $settings['lightbox_transitionDuration'] ) ) {
			$fancybox_options['transitionDuration'] = (int)$settings['lightbox_transitionDuration'];
		}

		if ( isset( $settings['lightbox_touch'] ) && '1' == $settings['lightbox_touch'] ) {
			$fancybox_options['touch'] = array(
				'vertical' => true,
				'momentum' => true
			);
		}

		if ( isset( $settings['lightbox_thumbsAutoStart'] ) && '1' == $settings['lightbox_thumbsAutoStart'] ) {
			$fancybox_options['thumbs']['autoStart'] = true;
		} else {
			$fancybox_options['thumbs']['autoStart'] = false;
		}

		if ( isset( $settings['lightbox_thumbsAxis'] ) && (!isset( $settings['lightbox_bottomThumbs'] ) || '1' != $settings['lightbox_bottomThumbs']) ) {
			$fancybox_options['thumbs']['axis'] = $settings['lightbox_thumbsAxis'];
		}

		if ( isset( $settings['lightbox_bottomThumbs'] ) && '1' == $settings['lightbox_bottomThumbs'] ) {
			$fancybox_options['thumbs']['axis'] = 'y';
		}

		// Set caption for desktop versions
		if ( isset( $settings['caption_overlap'] ) && '1' == $settings['caption_overlap'] ){
			$fancybox_options['preventCaptionOverlap'] = true;
		} else {
			$fancybox_options['preventCaptionOverlap'] = false;
		}

		// Set caption for mobile versions
		if ( wp_is_mobile() ) {
			if ( isset( $settings['mobile_caption_overlap'] ) && '1' == $settings['mobile_caption_overlap'] ) {
				$fancybox_options['preventCaptionOverlap']           = true;
				$fancybox_options['mobile']['preventCaptionOverlap'] = true;
			} else {
				$fancybox_options['preventCaptionOverlap']           = false;
				$fancybox_options['mobile']['preventCaptionOverlap'] = false;
			}
		}

		return $fancybox_options;

	}

	/**
	 * Add close button
	 * Desc: We add this way in order for the Close button to always be the last one
	 *
	 * @param $fancybox_options
	 * @param $settings
	 *
	 * @return array
	 *
	 * @since 2.5.4
	 */
	public function output_lightbox_close_button( $fancybox_options, $settings ) {

		if ( isset( $settings['lightbox_close'] ) && '1' == $settings['lightbox_close'] ) {
			$fancybox_options['buttons'][] = 'close';
		}

		return $fancybox_options;
	}

	// Add extra scripts for shortcode to enqueue
	public function enqueue_necessary_scripts( $scripts ) {

		$scripts[] = 'modula-pro';
		return $scripts;

	}

	// Add extra css for shortcode to enqueue
	public function modula_necessary_styles( $styles ) {

		// Search for css for effect in lite and remove it.
		$lite_effects = array_search( 'modula-effects', $styles );
		if ( false !== $lite_effects ) {
			unset( $styles[$lite_effects] );
		}

		$styles[] = 'modula-pro';
		$styles[] = 'modula-pro-effects';
		return $styles;

	}

	// Add extra parameter for javascript config
	public function modula_pro_config( $js_config, $settings ) {
		
		$js_config['lightbox'] = $settings['lightbox'];

		if ( apply_filters( 'modula_disable_lightboxes', true ) && !in_array( $settings['lightbox'], array( 'no-link', 'direct', 'attachment-page' ) ) ) {
			$js_config['lightbox'] = 'fancybox';
		}

		if ( isset( $settings['filterClick'] ) ) {
			$js_config['filterClick'] = esc_attr( $settings['filterClick'] );
		}

		if ( isset( $settings['dropdownFilters'] ) ) {
			$js_config['dropdownFilters'] = esc_attr( $settings['dropdownFilters'] );

			if( wp_is_mobile() && isset( $settings['enableMobileDropdownFilters'] ) && 1 == $settings['enableMobileDropdownFilters'] ) {
				$js_config['dropdownFilters'] = '1';
			}
		}

		$js_config['defaultActiveFilter'] = ( isset($settings['defaultActiveFilter']) ) ? esc_attr( sanitize_title( $settings['defaultActiveFilter'] ) ) : false;
		$js_config['initLightbox']        = 'modula_pro_init_lightbox';

		$js_config['haveFilters'] = 0;
		if ( isset( $settings['filters'] ) ) {
			$filters = Modula_Pro_Helper::remove_empty_items( $settings['filters'] );

			if ( is_array( $filters ) && !empty( $filters ) ) {
				$js_config['haveFilters'] = 1;
			}

		}

		// Copy caption on double tap but only on mobile - wp_is_mobile()
		if ( isset( $settings['mobile_caption_copy'] ) && '1' == $settings['mobile_caption_copy'] && wp_is_mobile() ){
			$js_config['copyCaptionMobile'] = 1;
		}

		if ( isset( $settings['open_Lightbox_on'] ) ){
			$js_config['lightbox_devices'] = sanitize_text_field( $settings['open_Lightbox_on'] );
		}

		if ( isset( $settings['doubleClick'] ) && '1' == $settings['doubleClick'] ){
			$js_config['mobileDoubleClick'] = 1;
		}

		return $js_config;
	}

	public function generate_new_css( $css, $gallery_id, $settings ) {

		if ( isset( $settings['hoverColor'] ) ) {
		
			$css .= "#{$gallery_id} .modula-item .modula-item-overlay, #{$gallery_id} .modula-item.effect-layla, #{$gallery_id}  .modula-item.effect-ruby,#{$gallery_id} .modula-item.effect-bubba,#{$gallery_id} .modula-item.effect-sarah,#{$gallery_id} .modula-item.effect-milo,#{$gallery_id} .modula-item.effect-julia,#{$gallery_id} .modula-item.effect-hera,#{$gallery_id} .modula-item.effect-winston,#{$gallery_id} .modula-item.effect-selena,#{$gallery_id} .modula-item.effect-terry,#{$gallery_id} .modula-item.effect-phoebe,#{$gallery_id} .modula-item.effect-apollo,#{$gallery_id} .modula-item.effect-steve,#{$gallery_id} .modula-item.effect-ming{ background-color:" . modula_pro_sanitize_color( $settings['hoverColor'] ) . "; }";

			$css .= "#{$gallery_id}  .modula-item.effect-oscar { background: -webkit-linear-gradient(45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#9b4a1b 40%," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 100%);background: linear-gradient(45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#9b4a1b 40%," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 100%);}";

			$css .= "#{$gallery_id}  .modula-item.effect-roxy {background: -webkit-linear-gradient(45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#05abe0 100%);background: linear-gradient(45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#05abe0 100%);}";

			$css .= "#{$gallery_id} .modula-item.effect-dexter {background: -webkit-linear-gradient(top," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,rgba(104,60,19,1) 100%); background: linear-gradient(to bottom," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,rgba(104,60,19,1) 100%);}";

			$css .= "#{$gallery_id}  .modula-item.effect-jazz {background: -webkit-linear-gradient(-45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#f33f58 100%);background: linear-gradient(-45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#f33f58 100%);}";

			$css .= "#{$gallery_id} .modula-item.effect-lexi {background: -webkit-linear-gradient(-45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#fff 100%);background: linear-gradient(-45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#fff 100%);}";

			$css .= "#{$gallery_id} .modula-item.effect-duke {background: -webkit-linear-gradient(-45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#cc6055 100%);background: linear-gradient(-45deg," . modula_pro_sanitize_color( $settings['hoverColor'] ) . " 0,#cc6055 100%);}";
		}

		if ( isset( $settings['hoverOpacity'] ) && isset( $settings['effect'] ) && absint( $settings['hoverOpacity'] ) <= 100 && 'none' != $settings['effect'] ) {
			$css .= "#{$gallery_id} .modula-item:hover img { opacity: " . (1 - absint( $settings['hoverOpacity'] ) / 100) . "; }";
		}

		// Settings for cursor preview
		if ( isset( $settings['cursor'] ) && isset( $settings['uploadCursor'] ) && 'custom' == $settings['cursor'] && $settings['uploadCursor'] != 0 ) {
			$image_src = wp_get_attachment_image_src( $settings['uploadCursor'] );
			$css       .= "#{$gallery_id} .modula-item > a, #{$gallery_id} .modula-item, #{$gallery_id} .modula-item-content > a { cursor:url(" . esc_url( $image_src[0] ) . "),auto ; } ";
		}

		//Settings for font family caption and title
		if ( isset( $settings['captionsFontFamily'] ) && 'Default' != $settings['captionsFontFamily'] && '' != $settings['captionsFontFamily'] ) {
			$css .= "#{$gallery_id} .description{ font-family:" . esc_attr( $settings['captionsFontFamily'] ) . "; }";
		}

		if ( isset( $settings['titleFontFamily'] ) &&  'Default' != $settings['titleFontFamily'] && '' != $settings['titleFontFamily'] ) {
			$css .= "#{$gallery_id} .jtg-title{ font-family:" . esc_attr( $settings['titleFontFamily'] ) . "; }";
		}
		// End of font family caption and title

		//Settings for Title Font Weight
		if ( isset( $settings['titleFontWeight'] ) &&  'default' != $settings['titleFontWeight'] ) {
			$css .= "#{$gallery_id} .jtg-title {font-weight:" . esc_attr( $settings['titleFontWeight'] ) . "; }";
		}


		//Settings for Captions Font Weight
		if ( isset( $settings['captionFontWeight'] ) && 'default' != $settings['captionFontWeight'] ) {
			$css .= "#{$gallery_id} p.description {font-weight:" . esc_attr( $settings['captionFontWeight'] ) . "; }";
		}

		if ( isset( $settings['loadedHSlide'] ) && isset( $settings['loadedScale'] ) && isset( $settings['loadedVSlide'] ) && isset( $settings['loadedRotate'] ) ) {
			$css .= "#{$gallery_id}:not(.modula-loaded-scale)  .modula-item .modula-item-content { transform: scale(" . sanitize_text_field( $settings['loadedScale'] ) / 100 . ") translate(" . sanitize_text_field( $settings['loadedHSlide'] ) . 'px,' . sanitize_text_field( $settings['loadedVSlide'] ) . "px) rotate(" . sanitize_text_field( $settings['loadedRotate'] ) . "deg); }";

			$css .= "@keyframes modulaScaling { 0% {transform: scale(1) translate(0px,p0x) rotate(0deg);} 50%{transform: scale(" . sanitize_text_field( $settings['loadedScale'] ) / 100 . ") translate(" . sanitize_text_field( $settings['loadedHSlide'] ) . 'px,' . sanitize_text_field( $settings['loadedVSlide'] ) . "px) rotate(" . sanitize_text_field( $settings['loadedRotate'] ) . "deg);}100%{transform: scale(1) translate(0px,p0x) rotate(0deg);}}";
		}

		// Filter Text Alignment
		if ( isset( $settings['filterTextAlignment'] ) && 'none' != $settings['filterTextAlignment'] ) {
			$css .= '#' . $gallery_id . '.modula-gallery .filters { text-align: ' . esc_attr( $settings['filterTextAlignment'] ) . ';}';
		}

		if ( isset( $settings['filterLinkColor'] ) && '' != $settings['filterLinkColor'] ) {
			$css .= '#' . $gallery_id . '.modula-gallery .filters a {color: ' . modula_pro_sanitize_color( $settings['filterLinkColor'] ) . ' !important;}';
		}

		if ( isset( $settings['filterLinkHoverColor'] ) && '' != $settings['filterLinkHoverColor'] ) {

			$css .= '#' . $gallery_id . ' .menu--ceres .modula_menu__item::before, #' . $gallery_id . ' .menu--ceres .modula_menu__item::after, #' . $gallery_id . ' .menu--ceres .modula_menu__link::after,#' . $gallery_id . ' .menu--ariel .modula_menu__item::before, #' . $gallery_id . ' .menu--ariel .modula_menu__item::after, #' . $gallery_id . ' .menu--ariel .modula_menu__link::after, #' . $gallery_id . ' .menu--ariel .modula_menu__link::before{background-color: ' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ' !important;}#' . $gallery_id . '.modula-gallery .filters a:hover, #' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current a {color: ' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ' !important;border-color: ' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ' !important}#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current a:hover:before,#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current a:hover:after,#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current:hover:before,#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current:hover:after { border-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . '}#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current a:before,#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current a:after,#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current:before,#' . $gallery_id . '.modula-gallery .filters li.modula_menu__item--current:after{border-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . '; background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Antonio
			$css .= '#' . $gallery_id . '.modula .menu--antonio .modula_menu__item::after, #' . $gallery_id . '.modula .menu--antonio .modula_menu__item::before, #' . $gallery_id . '.modula .menu--antonio .modula_menu__link::after, #' . $gallery_id . '.modula .menu--antonio .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Caliban
			$css .= '#' . $gallery_id . '.modula .menu--caliban .modula_menu__link::before,#' . $gallery_id . '.modula .menu--caliban .modula_menu__link::after{border-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Ferdinand
			$css .= '#' . $gallery_id . '.modula .menu--ferdinand .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Francisco & Trinculo
			$css .= '#' . $gallery_id . '.modula .menu--francisco .modula_menu__link::before, #' . $gallery_id . '.modula .menu--trinculo .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Horatio
			$css .= '#' . $gallery_id . '.modula .menu--horatio .modula_menu__item a::after, #' . $gallery_id . '.modula .menu--horatio .modula_menu__item a::before, #' . $gallery_id . '.modula .menu--horatio .modula_menu__item::after, #' . $gallery_id . '.modula .menu--horatio .modula_menu__item::before{border-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . '}';

			// Invulner
			$css .= '#' . $gallery_id . '.modula .menu--invulner .modula_menu__link::before{border-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . '}';

			// Iris
			$css .= '#' . $gallery_id . '.modula .menu--iris .modula_menu__link::after, #' . $gallery_id . '.modula .menu--iris .modula_menu__link::before{border-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . '}';

			// Juno
			$css .= '#' . $gallery_id . '.modula .menu--juno .modula_menu__item::after,#' . $gallery_id . '.modula .menu--juno .modula_menu__item::before,#' . $gallery_id . '.modula .menu--juno .modula_menu__link::after,#' . $gallery_id . '.modula .menu--juno .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Maria
			$css .= '#' . $gallery_id . '.modula .menu--maria .modula_menu__item::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Miranda
			$css .= '#' . $gallery_id . '.modula .menu--miranda .modula_menu__item::after,#' . $gallery_id . '.modula .menu--miranda .modula_menu__item::before,#' . $gallery_id . '.modula .menu--miranda .modula_menu__link::after,#' . $gallery_id . '.modula .menu--miranda .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Prospero
			$css .= '#' . $gallery_id . '.modula .menu--prospero .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Sebastian
			$css .= '#' . $gallery_id . '.modula .menu--sebastian .modula_menu__link::after,#' . $gallery_id . '.modula .menu--sebastian .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Shylock
			$css .= '#' . $gallery_id . '.modula .menu--shylock .modula_menu__link::after{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Stephano
			$css .= '#' . $gallery_id . '.modula .menu--stephano .modula_menu__item::before,#' . $gallery_id . '.modula .menu--stephano .modula_menu__link::after,#' . $gallery_id . '.modula .menu--stephano .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			// Tantalid
			$css .= '#' . $gallery_id . '.modula .menu--tantalid .modula_menu__link::after,#' . $gallery_id . '.modula .menu--tantalid .modula_menu__link::before{border-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . '}';

			// Valentin
			$css .= '#' . $gallery_id . '.modula .menu--valentine .modula_menu__item::after,#' . $gallery_id . '.modula .menu--valentine .modula_menu__item::before,#' . $gallery_id . '.modula .menu--valentine .modula_menu__link::after,#' . $gallery_id . '.modula .menu--valentine .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

			//
			$css .= '#' . $gallery_id . '.modula .menu--viola .modula_menu__item::after,#' . $gallery_id . '.modula .menu--viola .modula_menu__item::before,#' . $gallery_id . '.modula .menu--viola .modula_menu__link::after,#' . $gallery_id . '.modula .menu--viola .modula_menu__link::before{background-color:' . modula_pro_sanitize_color( $settings['filterLinkHoverColor'] ) . ';}';

		}

		// Terry
		$css .= '#' . $gallery_id . '.modula-gallery .modula-item.effect-terry .jtg-social a:not(:last-child){margin-bottom:' . absint( $settings['socialIconPadding'] ) . 'px;}';

		// caption positioning
		if ( isset( $settings['captionPosition'] ) ) {

			$css .= '.modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-caption .modula-fancybox-caption__body, .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-caption .modula-fancybox-caption__body p {text-align:' . esc_attr( $settings['captionPosition'] ) . '}';
		}

		if ( isset( $settings['lightbox_bottomThumbs'] ) && '1' == $settings['lightbox_bottomThumbs'] ) {
			$css .= '@media all and (min-width: 768px) { .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-thumbs { top: auto !important; width: auto !important; bottom: 0; left: 0; right : 0; height: 95px; padding: 10px 10px 5px 10px; box-sizing: border-box; background: rgba(0, 0, 0, 0.6) !important;} .modula-fancybox-container.modula-lightbox-' . $gallery_id . '.modula-fancybox-show-thumbs .modula-fancybox-inner {  right: 0 !important; bottom: 95px !important; } }';
		}

		if ( isset( $settings['lightbox_background_color'] ) && '' != $settings['lightbox_background_color'] ) {
			$css .= '.modula-fancybox-container.modula-lightbox-'.$gallery_id.' .modula-fancybox-bg{background:' . modula_pro_sanitize_color($settings['lightbox_background_color']) . ';opacity:1 !important;}';
		}

		if ( isset( $settings['lightbox_active_colors'] ) && '' != $settings['lightbox_active_colors'] ){
			$css .= 'html body .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-thumbs__list a::before{border-color:' . modula_pro_sanitize_color($settings['lightbox_active_colors']) . ';}';

			$css .= 'html body .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-progress{background-color:' . modula_pro_sanitize_color($settings['lightbox_active_colors']) . ';}';
		}

		$css .= 'html body .modula.modula-columns .modula-items.grid-gallery .modula-item.effect-under .modula-item-content { height:auto;}';

		return $css;
	}

	public function admin_lite_scripts() {
		wp_enqueue_script( 'modula-install-lite', MODULA_PRO_URL . 'assets/js/install-lite.js', array( 'jquery', 'updates' ), null, true );
	}

	public function modula_pro_max_count( $images, $settings ) {
		$supported_types = apply_filters( 'modula_supported_types', array( 'creative-gallery', 'custom-grid', 'grid' ) );

		if ( in_array( $settings['type'], $supported_types ) ){

			// Check if is mobile
			if ( wp_is_mobile() ){
				// Check if is set, else have a backup
				if ( isset( $settings['maxImagesCount_mobile'] ) ){
					if ( absint( $settings['maxImagesCount_mobile'] ) > 0 ){
						$images = array_slice( $images, 0, absint( $settings['maxImagesCount_mobile'] ) );
					}
					// Go to backup
				} else {
					if ( isset( $settings['maxImagesCount'] ) && absint( $settings['maxImagesCount'] ) > 0 ){
						$images = array_slice( $images, 0, absint( $settings['maxImagesCount'] ) );
					}
				}
			} else {
				if ( isset( $settings['maxImagesCount'] ) && absint( $settings['maxImagesCount'] ) > 0 ){
					$images = array_slice( $images, 0, absint( $settings['maxImagesCount'] ) );
				}
			}
		}

		return $images;
	}

	public function output_removed_items( $settings, $item_data, $images ) {

		if ( ! isset( $settings['maxImagesCount'] ) || ( absint( $settings['maxImagesCount'] ) == 0 && !wp_is_mobile() ) ) {
			return;
		}

		if ( ! isset( $settings['maxImagesCount_mobile'] ) || ( absint( $settings['maxImagesCount_mobile'] ) == 0 && wp_is_mobile() ) ){
			return;
		}

		if ( ! isset( $settings['showAllOnLightbox'] ) || absint( $settings['showAllOnLightbox'] ) != 1 ) {
			return;
		}

		$images_ids = wp_list_pluck( $images, 'id' );

		$chuks      = explode( '-', $settings['gallery_id'] );
		$gallery_id = $chuks[1];

		$all_images = get_post_meta( $gallery_id, 'modula-images', true );

		echo '<div class="hidden-items">';
		foreach ( $all_images as $image ) {

			if ( in_array( $image['id'], $images_ids ) ) {
				continue;
			}

			$attr = array(
				'class' => array( 'tile-inner', 'modula-hidden-item' ),
			);

			if ( isset( $image['filters'] ) ) {
				$filters = explode( ',', $image['filters'] );
				foreach ( $filters as $filter ) {
					$attr['class'][] = 'jtg-filter-all jtg-filter-' . esc_attr( urldecode( sanitize_title( $filter ) ) );
				}
			}

			$image_full = wp_get_attachment_image_src( $image['id'], 'full' );
			if ( is_array( $image_full ) ) {
				$attr['data-full'] = $image_full[0];
			}
			if ( isset( $item_data['link_attributes']['data-fancybox'] ) ) {
				$attr['data-fancybox'] = $item_data['link_attributes']['data-fancybox'];
			}
			$caption = isset( $image['description'] ) ? $image['description'] : '';

			if ( '' == $caption ) {
				$caption = wp_get_attachment_caption( $image['id'] );
			}

			if ( 'fancybox' == $settings['lightbox'] ) {
				if ( (isset( $settings['showTitleLightbox'] ) && '1' == $settings['showTitleLightbox']) && (isset( $settings['showCaptionLightbox'] ) && '1' == $settings['showCaptionLightbox']) ) {
					$attr['data-caption'] = esc_attr( '<p>' . $image['title'] . '</p><p>' . $image['description'] . '</p>' );
				} else if ( (isset( $settings['showTitleLightbox'] ) && '1' == $settings['showTitleLightbox']) && (!isset( $settings['showCaptionLightbox'] ) || '1' != $settings['showCaptionLightbox']) ) {
					$attr['data-caption'] = esc_attr( '<p>' . $image['title'] . '</p>' );
				} else if ( (!isset( $settings['showTitleLightbox'] ) || '1' != $settings['showTitleLightbox']) && (isset( $settings['showCaptionLightbox'] ) && '1' == $settings['showCaptionLightbox']) ) {
					$attr['data-caption'] = esc_attr( '<p>' . $image['description'] . '</p>' );
				} else {
					$attr['data-caption'] = '';
				}
			} else {
				$attr['data-title'] = $caption;
			}

			echo '<a ' . Modula_Helper::generate_attributes( apply_filters( 'modula_hidden_items_data', $attr, $settings, $image ) ) . '></a>';
		}
		echo '</div>';
	}

	public function shortpixel_fix( $item_data ) {
		if ( isset( $item_data['img_attributes']['src'] ) && strpos( $item_data['img_attributes']['src'], 'modula.shortpixel.ai' ) !== false ) {
			$item_data['img_attributes']['src'] = str_replace( 'modula.shortpixel.ai', 'cdn.wp-modula.com', $item_data['img_attributes']['src'] );
		}
		if ( isset( $item_data['image_full'] ) && strpos( $item_data['image_full'], 'modula.shortpixel.ai' ) !== false ) {
			$item_data['image_full'] = str_replace( 'modula.shortpixel.ai', 'cdn.wp-modula.com', $item_data['image_full'] );
		}

		if ( isset( $item_data['image_url'] ) && strpos( $item_data['image_url'], 'modula.shortpixel.ai' ) !== false ) {
			$item_data['image_url'] = str_replace( 'modula.shortpixel.ai', 'cdn.wp-modula.com', $item_data['image_url'] );
		}
		if ( isset( $item_data['img_attributes']['data-src'] ) && strpos( $item_data['img_attributes']['data-src'], 'modula.shortpixel.ai' ) !== false ) {
			$item_data['img_attributes']['data-src'] = str_replace( 'modula.shortpixel.ai', 'cdn.wp-modula.com', $item_data['img_attributes']['data-src'] );
		}
		if ( isset( $item_data['link_attributes']['href'] ) && strpos( $item_data['link_attributes']['href'], 'modula.shortpixel.ai' ) !== false ) {
			$item_data['link_attributes']['href'] = str_replace( 'modula.shortpixel.ai', 'cdn.wp-modula.com', $item_data['img_attributes']['data-src'] );
		}
		return $item_data;
	}


	/**
	 * Enqueue only the scripts that are required by a specific effect
	 *
	 * @param $settings
	 */
	public function output_extra_effects_scripts( $settings ) {

		$effect      = $settings['effect'];
		$tilt_effect = array( 'tilt_1', 'tilt_2', 'tilt_3', 'tilt_4', 'tilt_5', 'tilt_6', 'tilt_7', 'tilt_8' );

		if ( in_array( $effect, $tilt_effect ) ) {
			wp_enqueue_script( 'modula-pro-tilt' );
		}
	}


	/**
	 * Enqueue needed scripts for the tilt and caption hover effects
	 *
	 */
	public function preview_extra_effects_scripts() {

		$current_screen = get_current_screen();

		// Register and Enqueue scripts only for Modula Gallery posts
		if ( 'modula-gallery' == $current_screen->id ) {

			// Modula Pro scripts used for Tilt Hover Effects
			wp_register_script( 'modula-pro-tilt', MODULA_PRO_URL . 'assets/js/modula-pro-tilt.min.js', array( 'jquery' ), MODULA_PRO_VERSION, true );
			wp_enqueue_script( 'modula-pro-tilt' );
		}
	}

	/**
	 * Add extra elements for the tilt effect
	 *
	 * @param $data
	 */
	public function extra_effects_extra_elements( $data ) {

		if ( count( $data->item_classes ) > 1 ) {
			$hover_effect  = $data->item_classes[1];
			$effect        = explode( '-', $hover_effect );
			$effect_array  = array( 'tilt_1', 'tilt_2', 'tilt_3', 'tilt_4', 'tilt_5', 'tilt_6', 'tilt_7', 'tilt_8' );
			$overlay_array = array( 'tilt_2', 'tilt_3', 'tilt_4', 'tilt_5', 'tilt_6', 'tilt_7', 'tilt_8' );
			$svg_array     = array( 'tilt_1', 'tilt_2', 'tilt_4', 'tilt_5', 'tilt_6', 'tilt_7', 'tilt_8' );
			if ( in_array( $effect[1], $effect_array ) ) {
				echo '<div class="tilter__deco tilter__deco--shine"><div></div></div>';

				if ( in_array( $effect[1], $overlay_array ) ) {
					echo '<div class="tilter__deco tilter__deco--overlay"></div>';
				}

				if ( in_array( $effect[1], $svg_array ) ) {
					echo '<div class="tilter__deco tilter__deco--lines"></div>';
				}

			}
		}
	}

	public function modula_link_shortcode( $atts, $content = null ) {

		$default_atts = array(
			'id' => false,
		);

		$atts = wp_parse_args( $atts, $default_atts );

		if ( '' == $atts['id'] || !$atts['id'] ) {
			return esc_html( 'Gallery ID not found !' );
		}

		$gallery = get_post( absint( $atts['id'] ) );

		if ( 'modula-gallery' != get_post_type( $gallery ) ) {
			return esc_html__( 'Given ID doesn\'t belong to a Modula Gallery!', 'modula-pro' );
		}

		$script_manager = false;
		if ( class_exists( 'Modula_Script_Manager' ) ) {
			$script_manager = Modula_Script_Manager::get_instance();
		}


		$settings = get_post_meta( $atts['id'], 'modula-settings', true );
		$defaults = Modula_CPT_Fields_Helper::get_defaults();
		$settings = wp_parse_args( $settings, $defaults );

		// Need this for Modula Deeplink. Added the 'jtg-' string to comply with the search and replace
		$settings['gallery_id'] = 'jtg-link-' . $atts['id'];

		$pre_gallery_html = apply_filters( 'modula_pre_output_filter_check', false, $settings, $gallery );

		if ( false !== $pre_gallery_html ) {

			// If there is HTML, then we stop trying to display the gallery and return THAT HTML.
			$pre_output = apply_filters( 'modula_pre_output_filter', '', $settings, $gallery );
			return $pre_output;

		}

		$fancybox_options = Modula_Helper::lightbox_default_options();

		// This comes from the LITE version, se we need to set it here for modula link
		if ( isset( $settings['show_navigation'] ) && '1' == $settings['show_navigation'] ) {
			$fancybox_options['arrows'] = true;
		}

		$fancybox_options = apply_filters( 'modula_fancybox_options', $fancybox_options, $settings );

		// Enqueue only after we found a gallery
		$necessary_scripts = apply_filters( 'modula_link_necessary_scripts', array( 'modula-fancybox', 'modula-link-shortcode' ), $settings );

		$necessary_styles = apply_filters( 'modula_link_necessary_styles', array( 'modula-fancybox', 'modula' ), $settings );


		if ( !empty( $necessary_scripts ) ) {
			if ( !$script_manager ) {
				foreach ( $necessary_scripts as $script ) {
					wp_enqueue_script( $script );
				}
			} else {
				$script_manager->add_scripts( $necessary_scripts );
			}

		}

		if ( !empty( $necessary_styles ) ) {
			foreach ( $necessary_styles as $style ) {
				wp_enqueue_style( $style );
			}
		}

		do_action( 'modula_link_extra_scripts', $settings );

		$images      = get_post_meta( $atts['id'], 'modula-images', true );
		$imagesArray = array();

		if ( !empty( $images ) ) {
			foreach ( $images as $image ) {

				$image_url   = wp_get_attachment_image_src( $image['id'], 'full' );
				$image_thumb = wp_get_attachment_image_src( $image['id'] );

				$new_image = apply_filters( 'modula_link_item',
				                            array(
					                            'src'  => $image_url[0],
					                            'opts' => array(
						                            'caption'  => Modula_Pro::check_lightbox_link_title_caption( $settings, $image['title'], $image['description'] ),
						                            'thumb'    => $image_thumb[0],
						                            'image_id' => $image['id'] ),
				                            ), $image, $atts['id'], $settings );

				$imagesArray[] = $new_image;
			}
		}
		ob_start();

		$not_allowed_lightbox = array( 'no-link', 'direct', 'attachment-page' );
		$not_allowed_types    = array( 'slider' );

		$lightbox = 'true';

		if ( in_array( $settings['lightbox'], $not_allowed_lightbox ) || in_array( $settings['type'], $not_allowed_types ) ) {
			$lightbox = 'false';
		}

		if ( isset( $settings['mobile_caption_copy'] ) && '1' == $settings['mobile_caption_copy'] && wp_is_mobile() ){
			$fancybox_options['copyCaptionMobile'] = 1;
		}

		$link_js_config = apply_filters( 'modula_link_gallery_settings', array(
			'lightbox'     => $lightbox,
			'lightboxOpts' => $fancybox_options
		), $settings );


		echo $this->modula_link_css( $atts['id'], $settings );

		echo '<a id="' . esc_attr( $settings['gallery_id'] ) . '" data-images="' . esc_attr( json_encode( $imagesArray ) ) . '"' . ' data-config="' . esc_attr( json_encode( $link_js_config ) ) . '" class="jtg-' . $atts['id'] . ' modula-link" href="#">' . do_shortcode( $content ) . '</a>';

		$html = ob_get_clean();

		return $html;

	}

	/**
	 * Output for link lightbox settings
	 *
	 * @param $settings
	 *
	 * @param $title
	 * @param $caption
	 *
	 * @return string $html
	 */
	public static function check_lightbox_link_title_caption( $settings, $title, $caption ) {

		$html = '';
		if ( 1 == $settings['showTitleLightbox'] ) {
			$html .= "<p> {$title} </p> ";
		}
		if ( 1 == $settings['showCaptionLightbox'] ) {
			$html .= "<p> {$caption} </p> ";
		}
		return $html;
	}

	public function output_column( $column, $post_id ) {

		if ( 'shortcode' == $column ) {
			$shortcode = '[modula-link id="' . $post_id . '"]' . esc_html__( 'Click here', 'modula-pro' ) . '[/modula-link]';
			echo '<div class="modula-copy-shortcode">';
			echo '<input type="text" value="' . esc_attr( $shortcode ) . '"  onclick="select()" readonly>';
			echo '<a href="#" title="' . esc_attr__( 'Copy shortcode', 'modula-pro' ) . '" class="copy-modula-shortcode button button-primary dashicons dashicons-format-gallery" style="width:40px;"></a><span></span>';
			echo '</div>';
		}

	}

	public function output_link_shortcode( $post ) {
		$shortcode = '[modula-link id="' . $post->ID . '"]' . esc_html__( 'Click here', 'modula-pro' ) . '[/modula-link]';
		echo '<div class="modula-copy-shortcode">';
		echo '<input type="text" value="' . esc_attr( $shortcode ) . '"  onclick="select()" readonly>';
		echo '<a href="#" title="' . esc_attr__( 'Copy shortcode', 'modula-pro' ) . '" class="copy-modula-shortcode button button-primary dashicons dashicons-format-gallery" style="width:40px;"></a><span></span>';
		echo '<p>' . sprintf( esc_html__( 'You can use this to display your newly created gallery by clicking on a %s link or image %s ', 'modula-pro' ), '<u>', '</u>' ) . '</p>';
		echo '</div>';
	}


	/**
	 * Remove Albums upsells metabox
	 *
	 * @param $metaboxes
	 *
	 * @return array
	 * @since 2.2.1
	 */
	public function remove_albums_upsell_metabox() {

		remove_meta_box( 'modula-albums-upsell', 'modula-gallery', 'normal' );
	}

	/**
	 * Add extra path for modula templates
	 *
	 * @param $paths
	 *
	 * @return mixed
	 */
	public function add_modula_pro_templates_path( $paths ) {
		$paths[50] = trailingslashit( MODULA_PRO_PATH ) . '/includes/templates';
		return $paths;
	}

	/* Modula Troubleshooting */
	public function add_troubleshooting_fields( $fields ) {

		$fields['hover_effects'] = array(
			'label'       => esc_html__( 'Hover Effects', 'modula-pro' ),
			'description' => esc_html__( 'Check this if you\'re using hover effects with tilt effect', 'modula-pro' ),
			'type'        => 'toggle',
			'class'       => array( 'troubleshoot-subfield' ),
			'priority'    => 41,
		);

		$fields['link_shortcode'] = array(
			'label'       => esc_html__( 'Link Shortcode', 'modula-pro' ),
			'description' => esc_html__( 'Check this if you\'re using modula link ( [modula-link] ) shortcode', 'modula-pro' ),
			'type'        => 'toggle',
			'class'       => array( 'troubleshoot-subfield' ),
			'priority'    => 42,
		);

		return $fields;

	}

	public function add_troubleshooting_defaults( $defaults ) {
		$defaults['hover_effects']  = false;
		$defaults['link_shortcode'] = false;
		return $defaults;
	}

	public function add_main_pro_files( $handles ) {

		// remove modula lite effects
		$lite_effects = array_search( 'modula-effects', $handles['styles'] );
		if ( false !== $lite_effects ) {
			unset( $handles['styles'][$lite_effects] );
		}

		// add modula pro main files
		$handles['scripts'][] = 'modula-pro';
		$handles['styles'][]  = 'modula-pro-effects';

		return $handles;
	}

	public function check_hovereffect( $handles, $options ) {

		if ( $options['hover_effects'] ) {
			$handles['scripts'][] = 'modula-pro-tilt';
		}

		return $handles;

	}

	public function check_linkshortcode( $handles, $options ) {

		if ( $options['link_shortcode'] ) {
			$handles['scripts'][] = 'modula-link-shortcode';
		}

		return $handles;

	}

	/**
	 * Modula Link CSS
	 *
	 * @param $gallery_id
	 * @param $settings
	 *
	 * @return string
	 *
	 * @since 2.2.4
	 */
	public function modula_link_css( $gallery_id, $settings ) {

		$css = '<style>';

		if ( isset( $settings['lightbox_bottomThumbs'] ) && '1' == $settings['lightbox_bottomThumbs'] ) {
			$css .= '@media all and (min-width: 768px) { .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-thumbs { top: auto !important; width: auto !important; bottom: 0; left: 0; right : 0; height: 95px; padding: 10px 10px 5px 10px; box-sizing: border-box; background: rgba(0, 0, 0, 0.6) !important;} .modula-fancybox-container.modula-lightbox-' . $gallery_id . '.modula-fancybox-show-thumbs .modula-fancybox-inner {  right: 0 !important; bottom: 95px !important; } }';
		}

		if ( isset( $settings['lightbox_background_color'] ) && '' != $settings['lightbox_background_color'] ) {
			$css .= '.modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-bg{background:' . modula_pro_sanitize_color($settings['lightbox_background_color']) . ';opacity:1 !important;}';
		}

		if ( isset( $settings['lightbox_active_colors'] ) && '' != $settings['lightbox_active_colors'] ){
			$css .= 'html body .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-thumbs__list a::before{border-color:' . modula_pro_sanitize_color($settings['lightbox_active_colors']) . ';}';

			$css .= 'html body .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-progress{background-color:' . modula_pro_sanitize_color($settings['lightbox_active_colors']) . ';}';
		}

		if ( isset( $settings['captionPosition'] ) ) {

			$css .= '.modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-caption .modula-fancybox-caption__body, .modula-fancybox-container.modula-lightbox-' . $gallery_id . ' .modula-fancybox-caption .modula-fancybox-caption__body p {text-align:' . esc_attr( $settings['captionPosition'] ) . '}';
		}

		if ( strlen( $settings['style'] ) ) {
			$css .= esc_html( $settings['style'] );
		}

		$css = apply_filters( 'modula_link_shortcode_css', $css, $gallery_id, $settings );

		$css .= '</style>';

		return $css;
	}


	/**
	 * Add helper classes for filters
	 *
	 * @param $template_data
	 *
	 * @return mixed
	 *
	 * @since 2.3.0
	 */
	public function filter_class_helper( $template_data ) {

		if ( isset( $template_data['settings']['filters'] ) && '' != $template_data['settings']['filters'] ) {
			if ( isset( $template_data['settings']['filterPositioning'] ) ) {

				$horizontal_position = array( 'top', 'bottom', 'top_bottom' );
				$vertical_position   = array( 'left', 'right', 'left_right' );

				if ( in_array( $template_data['settings']['filterPositioning'], $vertical_position ) && !empty( Modula_Pro_Helper::remove_empty_items( $template_data['settings']['filters'] ) ) ) {
					$template_data['gallery_container']['class'][] = 'vertical-filters';
				}

				if ( in_array( $template_data['settings']['filterPositioning'], $horizontal_position ) && !empty( Modula_Pro_Helper::remove_empty_items( $template_data['settings']['filters'] ) ) ) {
					$template_data['gallery_container']['class'][] = 'horizontal-filters';
				}

			}
		}

		return $template_data;
	}


	/**
	 * Uninstall process
	 *
	 * @param $db_options
	 *
	 * @return mixed
	 *
	 * @since 2.3.0
	 */
	public function uninstall_process( $db_options ) {

		$db_options[] = 'modula_pro_license_status';
		$db_options[] = 'modula_pro_license_key';

		return $db_options;
	}

	/**
	 * Uninstall process for transients
	 *
	 * @param $transients
	 *
	 * @return mixed
	 *
	 * @since 2.5.2
	 */
	public function uninstall_process_transients( $transients ) {

		$transients[] = 'modula_pro_licensed_extensions';

		return $transients;
	}


	/**
	 * Add gallery title to page
	 *
	 * @param $settings
	 *
	 * @since 2.3.2
	 */
	public function modula_gallery_title( $settings ) {

		$title = get_the_title( explode( '-', $settings['gallery_id'] )[1] );
		if ( isset( $settings['show_gallery_title'] ) && '1' == $settings['show_gallery_title'] ) {

			echo " <" . esc_attr( $settings['gallery_title_type'] ) . " class='modula-gallery-title'> " . esc_html( $title ) . " </" . esc_attr( $settings['gallery_title_type'] ) . "> ";
		}

	}
	/**
	 * Enqueue gutenberg block styles
	 *
	 * @since 2.5.0
	 */
	public function enqueue_modula_block_styles() {
		wp_enqueue_style( 'modula-pro-effects', MODULA_PRO_URL . 'assets/css/effects.min.css', MODULA_PRO_VERSION, null );
	}

	/**
	 * Enqueue gutenberg block scripts
	 *
	 * @since 2.5.0
	 */
	public function enqueue_modula_block_scripts() {
		$screen = get_current_screen();
		if( $screen->base != 'post' && $screen->base != 'page' && $screen->base != 'widgets') {
			return;
		}
		wp_enqueue_script( 'modula-pro-gutenberg', MODULA_PRO_URL . 'assets/js/admin/modula_pro.js', array( 'jquery' ), MODULA_PRO_VERSION, true );
		wp_enqueue_script( 'modula-pro', MODULA_PRO_URL . 'assets/js/modula-pro.js', array( 'jquery' ), MODULA_PRO_VERSION, true );
		wp_enqueue_script( 'modula-pro-tilt', MODULA_PRO_URL . 'assets/js/modula-pro-tilt.min.js', array( 'jquery' ), MODULA_PRO_VERSION, true );

	}

	/**
	 * Debug Information for Health Check
	 *
	 * @param $info
	 *
	 * @return mixed
	 * @Since 2.5.0
	 */
	public function debug_information($info){

		$licenses_status = get_option( 'modula_pro_license_status', false );
		$date_format     = get_option( 'date_format' );
		$license_expire  = date( $date_format, strtotime( $licenses_status->expires ) );
		$alt_serv        = get_option( 'modula_pro_alernative_server' );
		$troubleshoot    = get_option( 'modula_troubleshooting_option' );

		$info['pro_version'] = array(
				'label' => __( 'PRO Version', 'modula-pro' ),
				'value' => MODULA_PRO_VERSION,
				'debug' => esc_html( 'PRO version ' . MODULA_PRO_VERSION ),
		);

		$info['license'] = array(
				'label' => __( 'License', 'modula-pro' ),
				'value' => ( !$licenses_status || 'valid' != $licenses_status->license ) ? __( 'License inactive', 'modula-pro' ) : __( 'License active untill ' . $license_expire, 'modula-pro' ),
				'debug' => ( !$licenses_status || 'valid' != $licenses_status->license ) ? esc_html( 'License inactive' ) : esc_html( 'License active  ' . $license_expire )
		);

		$info['alternative_server'] = array(
				'label' => __( 'Alternative license server', 'modula-pro' ),
				'value' => ( isset( $alt_serv ) && 'true' == $alt_serv ) ? __( 'Using alternative license server', 'modula-pro' ) : __( 'Using regular license server', 'modula-pro' ),
				'debug' => ( isset( $alt_serv ) && 'true' == $alt_serv ) ? 'Using alternative license server' : 'Using regular license server'
		);

		$info['troubleshoot_modula_link'] = array(
				'label' => __( 'Modula Link everywhere', 'modula-pro' ),
				'value' => ( isset( $troubleshoot['link_shortcode'] ) && '1' == $troubleshoot['link_shortcode'] ) ? __( 'Enabled', 'modula-pro' ) : __( 'Disabled', 'modula-pro' ),
				'debug' => ( isset( $troubleshoot['link_shortcode'] ) && '1' == $troubleshoot['link_shortcode'] ) ? 'Enabled' : 'Disabled'
		);

		return $info;
	}

	/**
	 * Loads the plugin textdomain for translation.
	 *
	 * @since 2.5.0
	 */
	public function set_locale() {
		load_plugin_textdomain( 'modula-pro', false, dirname( plugin_basename( MODULA_PRO_FILE ) ) . '/languages' ); 
	}

	/**
	 * Get license checker args
	 */
	private function get_license_checker_args() {
		$args = array(
			'plugin_slug' => 'modula-best-grid-gallery',
			'plugin_nicename' => 'Modula',
			'store_url' => MODULA_PRO_STORE_URL,
			'item_id'  => MODULA_PRO_STORE_ITEM_ID,
			'license' => 'modula_pro_license_key',
			'license_status' => 'modula_pro_license_status',
			'plugin_file' => MODULA_PRO_FILE,
		);

		return $args;
	}

	/**
	 * Add the license tab.
	 *
	 * @param [type] $tabs
	 * @return void
	 * @since 2.5.6 - has been moved from class-modula-pro-settings.php here in order to preserve the license tab
	 *                if the user doesn't have a valid license.
	 */
	public function add_license_tab( $tabs ) {

		$tabs['licenses'] = array(
			'label'    => esc_html__( 'Licenses', 'modula-pro' ),
			'priority' => -1,
		);

		return $tabs;

	}

	/**
	 * Show the license tab content
	 *
	 * @return void
	 * @since 2.5.6 - has been moved from class-modula-pro-settings.php here in order to preserve the license tab
	 *                if the user doesn't have a valid license.
	 */
	public function show_licenses_tab() {
		include 'admin/tabs/license.php';
	}

	/**
	 * The license script
	 *
	 * @return void
	 * @since 2.5.6 Moved here from class-modula-pro-settings.php for limtation reasons.
	 */
	public function license_script() {
		global $id, $post;


		// Get current screen.
		$screen = get_current_screen();

		// Add license protection scrip
		// Previous was inline
		if ( 'modula-gallery_page_modula' == $screen->base ){
			wp_enqueue_script( 'modula-license-protection_activation', MODULA_PRO_URL . 'assets/js/license-protection_activation.js', array( 'jquery' ), MODULA_PRO_VERSION, true );
			wp_localize_script( 'modula-license-protection_activation', 'modulaLicense', array(
				'nonce'   => wp_create_nonce( 'modula_license_save' ),
				'ajaxURL' => admin_url( 'admin-ajax.php' ),
				'activatingLicense' => esc_html__('Activating license. Please wait...','modula-pro'),
				'deactivatingLicense' => esc_html__('Deactivating license. Please wait...','modula-pro'),
			) );
		}
	}

	/**
	 * Adds centered bottom effect 
	 * 
	 * @param array $effects
	 *
	 * @return array $effects
	 * 
	 * @since 2.6.3 
	 */
	public function aditional_pro_effects( $effects ){

		$effects[] = 'centered-bottom';

		return $effects;
	}

}
