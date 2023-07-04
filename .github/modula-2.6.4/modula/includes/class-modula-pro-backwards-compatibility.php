<?php

/**
 *
 */
class Modula_PRO_Backward_Compatibility {

	/**
	 * Holds the class object.
	 *
	 * @since 2.5.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Modula_PRO_Backward_Compatibility constructor.
	 *
	 * @since 2.5.0
	 */
	function __construct(){

		// Margin from creative gallery
		add_filter( 'modula_admin_field_value', array( $this, 'backward_compatibility_admin_mobile_max_images' ), 15, 3 );
		add_filter( 'modula_backbone_settings', array( $this, 'backward_compatibility_backbone_mobile_max_images' ), 15 );

		// Mobile Dropdown Filters
		add_filter( 'modula_admin_field_value', array( $this, 'backward_compatibility_admin_mobile_dropdown' ), 15, 3 );
		add_filter( 'modula_backbone_settings', array( $this, 'backward_compatibility_backbone_mobile_dropdown' ), 15 );


	}


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The Modula_PRO_Backward_Compatibility object.
	 * @since 2.5.0
	 */
	public static function get_instance(){

		if ( !isset( self::$instance ) && !( self::$instance instanceof Modula_PRO_Backward_Compatibility ) ){
			self::$instance = new Modula_PRO_Backward_Compatibility();
		}

		return self::$instance;

	}

	/**
	 * Add mobile max image count compatibilty
	 *
	 * @param $value
	 * @param $key
	 * @param $settings
	 *
	 * @return mixed
	 * @since 2.5.0
	 */
	public function backward_compatibility_admin_mobile_max_images( $value, $key, $settings ){

		if ( 'maxImagesCount_mobile' == $key && !isset( $settings['maxImagesCount_mobile'] ) ){
			if ( isset( $settings['maxImagesCount'] ) ){
				return $settings['maxImagesCount'];
			}
		}

		return $value;

	}


	/**
	 *  Add mobile max image count compatibilty
	 *
	 * @param $settings
	 *
	 * @return mixed
	 * @since 2.5.0
	 */
	public function backward_compatibility_backbone_mobile_max_images( $settings ){

		if ( isset( $settings['maxImagesCount'] ) && !isset( $settings['maxImagesCount_mobile'] ) ){
			$settings['maxImagesCount_mobile'] = absint( $settings['maxImagesCount'] );
		}

		return $settings;

	}

	/**
	 * Add mobile dropdown filters compatibilty
	 *
	 * @param $value
	 * @param $key
	 * @param $settings
	 *
	 * @return mixed
	 * @since 2.5.0
	 */
	public function backward_compatibility_admin_mobile_dropdown( $value, $key, $settings ){

		if ( 'enableMobileDropdownFilters' == $key && !isset( $settings['enableMobileDropdownFilters'] ) ){
			if ( isset( $settings['dropdownFilters'] ) ){
				return $settings['dropdownFilters'];
			}
		}

		return $value;

	}


	/**
	 *  Add mobile downdown filters compatibilty
	 *
	 * @param $settings
	 *
	 * @return mixed
	 * @since 2.5.0
	 */
	public function backward_compatibility_backbone_mobile_dropdown( $settings ){

		if ( isset( $settings['dropdownFilters'] ) && !isset( $settings['enableMobileDropdownFilters'] ) ){
			$settings['enableMobileDropdownFilters'] = $settings['dropdownFilters'];
		}

		return $settings;

	}

}

Modula_PRO_Backward_Compatibility::get_instance();