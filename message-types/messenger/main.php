<?php
if ( !defined( 'ABSPATH' ) ) exit;
if ( class_exists( 'Icegram_Message_Type_Messenger' ) ) return;

/**
* Class Icegram Messenger
*/
class Icegram_Message_Type_Messenger extends Icegram_Message_Type {

	function __construct() {
		parent::__construct( dirname( __FILE__ ), plugins_url( '/', __FILE__ ) );
		add_filter( 'icegram_message_type_params_messenger', array( $this, 'set_admin_style' ) );	

	}
	
	function define_settings() {
		parent::define_settings();
		$this->settings['position']['values'] 	= array( '20', '22' );	
		$this->settings['position']['default'] 	= '22';
		$this->settings['theme']['default']		= 'social';
		$this->settings['form_layout']['values'] 	= array('inline' );	 // May be 'bottom'
		$this->settings['form_layout']['default'] 	= 'inline';
		unset ( $this->settings['text_color'],
				$this->settings['bg_color'],
				$this->settings['label']
				);
	}

	function set_admin_style( $params ) {

		$params['admin_style'] = array( 'label_bg_color' 		=> '#883EB0',
										'theme_header_height'	=> '7em',
										'thumbnail_width' 		=> '27.6%',
										'thumbnail_height' 		=> '13.6em'
										);
		return $params;
	}
}