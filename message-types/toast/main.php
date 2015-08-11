<?php
if ( !defined( 'ABSPATH' ) ) exit;
if ( class_exists( 'Icegram_Message_Type_Toast' ) ) return;

/**
* Class Icegram Toast
*/
class Icegram_Message_Type_Toast extends Icegram_Message_Type {

	function __construct() {
		parent::__construct( dirname( __FILE__ ), plugins_url( '/', __FILE__ ) );
		add_filter( 'icegram_message_type_params_toast', array( $this, 'set_admin_style' ) );				
	}

	function define_settings() {
		parent::define_settings();
		$this->settings['position']['values'] 	= array( '00', '01', '02', '11', '20', '21', '22' );	
		$this->settings['position']['default'] 	= '02';
		$this->settings['theme']['default']		= 'announce';
		unset ( $this->settings['text_color'],
				$this->settings['bg_color'],
				$this->settings['label'],
				$this->settings['embed_form']
				);
	}

	function set_admin_style( $params ) {

		$params['admin_style'] = array( 'label_bg_color' 		=> '#EDBB00',
										'theme_header_height'	=> '5em',
										'thumbnail_width' 		=> '43%',
										'thumbnail_height' 		=> '7.5em'
										);
		return $params;
	}
}