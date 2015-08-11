<?php
if ( !defined( 'ABSPATH' ) ) exit;
if ( class_exists( 'Icegram_Message_Type_Action_Bar' ) ) return;

/**
* Class Icegram Action Bar
*/
class Icegram_Message_Type_Action_Bar extends Icegram_Message_Type {

	function __construct() {
		parent::__construct( dirname( __FILE__ ), plugins_url( '/', __FILE__ ) );
		add_filter( 'icegram_message_type_params_action-bar', array( $this, 'set_admin_style' ) );				
	}

	function define_settings() {
		parent::define_settings();
		$this->settings['position']['values'] 		= array( '01', '21' );	
		$this->settings['position']['default'] 		= '01';
		$this->settings['form_layout']['values'] 	= array( 'left', 'right', 'bottom', 'inline' );	
		$this->settings['form_layout']['default'] 	= 'bottom';
		$this->settings['theme']['default']			= 'hello';
		$this->settings['bg_color']['default']		= ''; //#eb593c
		$this->settings['text_color']['default']	= ''; //#ffffff
		unset ($this->settings['icon']
				);
	}

	function set_admin_style( $params ) {

		$params['admin_style'] = array( 'label_bg_color' 		=> '#DF6B00',
										'theme_header_height'	=> '6em',
										'thumbnail_width' 		=> '92%',
										'thumbnail_height' 		=> '4.5em'
										);
		return $params;
	}
}