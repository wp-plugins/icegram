<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
* Icegram Message class
*/
if ( !class_exists( 'Icegram_Message' ) ) {

	class Icegram_Message {
		
		var $_post;
		var $title;
		var $message_data;

		function __construct( $message_id = '' ) {

			if ( !empty( $message_id ) ) {
				$this->_post 			= get_post( $message_id );
				$this->title 			= $this->_post->post_title;
				$this->message_data 	= get_post_meta( $this->_post->ID, 'icegram_message_data', true );
			}
		}
	}
}