<?php
if ( !defined( 'ABSPATH' ) ) exit;
if ( class_exists( 'Icegram_Message_Type_Popup' ) ) return;

/**
* Class Icegram Popup
*/
class Icegram_Message_Type_Popup extends Icegram_Message_Type {
	var $popup_default_delay_time; 
	function __construct() {
		parent::__construct( dirname( __FILE__ ), plugins_url( '/', __FILE__ ) );		

		$this->popup_default_delay_time = 3;
		add_filter( 'icegram_messages_to_show', array( $this, 'arrange_proper_delay_time' ) );
		add_filter( 'icegram_message_type_params_popup', array( $this, 'set_admin_style' ) );		
	}

	function define_settings() {
		parent::define_settings();
		$this->settings['theme']['default']	= 'persuade';
		unset ( $this->settings['position'],
				$this->settings['text_color'],
				$this->settings['bg_color'],
				$this->settings['icon']
				);
	}

	function arrange_proper_delay_time( $messages ) {

		$popup_delay_times = array();
		foreach ($messages as $message_id => $message) {
			
			if( $message['type'] == 'popup' ) {
				while( in_array( $message['delay_time'], $popup_delay_times ) ) {
					$message['delay_time'] = $message['delay_time'] + $this->popup_default_delay_time;					
				}
				$messages[$message_id]['delay_time'] = $message['delay_time'];
				$popup_delay_times[] = $message['delay_time'];
			}

		}
		return $messages;
	}

	function set_admin_style( $params ) {

		$params['admin_style'] = array( 'label_bg_color' 		=> '#22B189',
										'theme_header_height'	=> '6em',
										'thumbnail_width' 		=> '43%',
										'thumbnail_height' 		=> '10em'
										);
		return $params;
	}
}