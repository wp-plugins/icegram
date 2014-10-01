<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
* Icegram Campaign class
*/
if ( !class_exists( 'Icegram_Campaign' ) ) {
	class Icegram_Campaign {

		var $_post;
		var $title;
		var $messages;
		var $rules;
		var $rules_summary;

		function __construct( $id = '' ) {

			if ( !empty( $id ) ) {

				$this->_post = get_post( $id );
				
				$this->title 		= $this->_post->post_title;
				$this->messages 	= get_post_meta( $this->_post->ID, 'messages', true );
				$this->rules 		= get_post_meta( $this->_post->ID, 'icegram_campaign_target_rules', true );
				$this->rules_summary['where'] = array(
										'homepage' 		=> ( !empty( $this->rules['homepage'] ) ) ? $this->rules['homepage'] : '',
										'other_page' 	=> ( !empty( $this->rules['other_page'] ) && $this->rules['other_page'] == 'yes' && !empty( $this->rules['page_id'] ) ) ? $this->rules['page_id'] : '',
										'blog' 			=> ( !empty( $this->rules['blog'] ) ) ? $this->rules['blog'] : '',
										'sitewide' 		=> ( !empty( $this->rules['sitewide'] ) ) ? $this->rules['sitewide'] : ''
									);
				$this->rules_summary['when'] = array(
										'when' 	=> ( !empty( $this->rules['when'] ) ) ? $this->rules['when'] : '',
										'from' 	=> ( !empty( $this->rules['from'] ) ) ? $this->rules['from'] : '',
										'to' 	=> ( !empty( $this->rules['to'] ) ) ? $this->rules['to'] : ''
									);
				$this->rules_summary['device'] = array(
										'mobile' => ( !empty( $this->rules['mobile'] ) ) ? $this->rules['mobile'] : '',
										'tablet' => ( !empty( $this->rules['tablet'] ) ) ? $this->rules['tablet'] : '',
										'laptop' => ( !empty( $this->rules['laptop'] ) ) ? $this->rules['laptop'] : ''
									);
				$this->rules_summary['users'] = ( !empty( $this->rules['logged_in'] ) && $this->rules['logged_in'] == 'logged_in' ) ? ( ( !empty( $this->rules['users'] ) ) ? $this->rules['users'] : array( 'none' ) ) : array($this->rules['logged_in']);

				$this->rules_summary['retargeting'] = array( 'retargeting' => ( !empty( $this->rules['retargeting'] ) ) ? $this->rules['retargeting'] : '' ,
															 'expiry_time' => ( !empty( $this->rules['retargeting']) ) ? $this->rules['expiry_time'] : '' );

			}	

			add_filter( 'icegram_campaign_validation', array( $this, '_is_valid_user_roles' ), 10, 3 );
			add_filter( 'icegram_campaign_validation', array( $this, '_is_valid_device' ), 10, 3 );
			add_filter( 'icegram_campaign_validation', array( $this, '_is_valid_time' ), 10, 3 );
			add_filter( 'icegram_campaign_validation', array( $this, '_is_valid_page' ), 10, 3 );

		}

		function get_message_meta_by_id( $message_id ) {
			foreach ($this->messages as $value) {
				if ($value['id'] == $message_id) {
					return $value;
				}
			}
		}

		function get_rule_value( $rule_type ) {
			return (isset($this->rules_summary[$rule_type])) ? $this->rules_summary[$rule_type] : '';
		}

		function is_valid( $options = array() ) {
			if( !empty( $this->_post->ID ) ) {
				return apply_filters( 'icegram_campaign_validation', true, $this, $options );
			}
			return false;
		}

		function _is_valid_user_roles( $campaign_valid, $campaign, $options ) {
			if( !$campaign_valid ) {
				return $campaign_valid;
			}
			if(in_array( 'not_logged_in', $campaign->rules_summary['users'], true ) && !is_user_logged_in() ){
					return true;
			}
			elseif ( in_array( 'all', $campaign->rules_summary['users'], true ) ) {
				return true;
			} elseif ( is_user_logged_in() && !in_array( 'none', $campaign->rules_summary['users'], true ) ) {
				$current_user = wp_get_current_user();
				if ( in_array( $current_user->roles[0], $campaign->rules_summary['users'], true ) ) {
					return true;
				}
			}
			return false;
		}
		
		function _is_valid_device( $campaign_valid, $campaign, $options ) {
			if( !$campaign_valid ) {
				return $campaign_valid;
			}
			$current_platform = Icegram::get_platform();
			if ( !empty( $campaign->rules_summary['device'][ $current_platform ] ) && $campaign->rules_summary['device'][ $current_platform ] == 'yes' ) {
				return true;
			}
			return false;
		}
		
		function _is_valid_time( $campaign_valid, $campaign, $options ) {
			if( !$campaign_valid ) {
				return $campaign_valid;
			}
			if ( !empty( $campaign->rules_summary['when']['when'] ) && $campaign->rules_summary['when']['when'] == 'always' ) {
				return true;
			}

			if ( ( !empty( $campaign->rules_summary['when']['from'] ) && time() > strtotime( $campaign->rules_summary['when']['from'] . " 00:00:00") ) && ( !empty( $campaign->rules_summary['when']['to'] ) && strtotime( $campaign->rules_summary['when']['to'] . " 23:59:59") > time() ) ) {
				return true;
			}

			return false;
		}
		
		function _is_valid_page( $campaign_valid, $campaign, $options ) {
			$page_id = Icegram::get_current_page_id();
			if( !$campaign_valid || !empty($options['skip_page_check']) ) {
				return $campaign_valid;
			}
			if ( (!empty( $campaign->rules_summary['where']['sitewide'] ) && $campaign->rules_summary['where']['sitewide'] == 'yes' ))  {
				if (!empty($campaign->rules['exclude_page_id']) && in_array($page_id, $campaign->rules['exclude_page_id'])){ 
					return false;
				}else{
					return true;
				}
			}
			if ( !empty( $campaign->rules_summary['where']['homepage'] ) && $campaign->rules_summary['where']['homepage'] == 'yes' && ( is_home() || is_front_page() ) ) {
					return true;
			}
			if ( !empty( $page_id ) ) {
				if ( !empty( $campaign->rules_summary['where']['other_page'] ) && in_array( $page_id, $campaign->rules_summary['where']['other_page'] ) ) {
					return true;
				}
			}
			return false;
		}
	}
}