<?php
/*
 * Plugin Name: Icegram
 * Plugin URI: http://www.icegram.com/
 * Description: All in one solution to inspire, convert and engage your audiences. Action bars, Popup windows, Messengers, Toast notifications and more. Awesome themes and powerful rules.
 * Version: 1.1.2
 * Author: Icegram
 * Author URI: http://www.icegram.com/
 *
 * Copyright (c) 2014 Icegram
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: icegram
 * Domain Path: /lang/
*/

/**
* Main class Icegram
*/
class Icegram {

    var $plugin_url;
    var $plugin_path;
    var $_wpautop_tags;
    public static $current_page_id;
    
    function __construct() {

        $this->include_classes();
        $this->plugin_url   = untrailingslashit( plugins_url( '/', __FILE__ ) );
        $this->plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) );

        add_action( 'init', array( &$this, 'register_campaign_post_type' ) );
        add_action( 'init', array( &$this, 'register_message_post_type' ) );

        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_styles_and_scripts' ) );
        add_action( 'wp_footer', array( &$this, 'display_messages' ) );

        add_action( 'admin_print_styles', array( &$this, 'remove_preview_button' ) ) ;        
        add_filter( 'post_row_actions', array( &$this , 'remove_row_actions' ), 10, 2 );
        add_action( 'wp_print_scripts', array( &$this, 'identify_current_page' ) );

        add_shortcode( 'icegram', array( &$this, 'display_messages' ) );
        add_action( 'admin_menu', array( &$this, 'admin_menus') );
        add_action( 'admin_init', array( &$this, 'welcome' ) );

        add_action( 'wp_ajax_nopriv_icegram_event_track', array( &$this, 'icegram_event_track' ) );
        add_action( 'wp_ajax_icegram_event_track', array( &$this, 'icegram_event_track' ) );
        
    }

    public function icegram_event_track() {        

        if( !empty( $_POST['event_data'] ) ) {

            $messages_shown = (array) unserialize( (!empty($_COOKIE['icegram_messages_shown']) ? stripslashes( $_COOKIE['icegram_messages_shown'] ) : '') );

            foreach ( $_POST['event_data'] as $event ) {
                switch ($event['type']) {
                    case 'shown':
                        if (is_array($event['params']) && !empty($event['params']['message_id'])) {
                            $messages_shown[] = $event['params']['message_id'];
                        }
                        break;
                    
                    default:
                        break;
                }

                // Emit event for other plugins to handle it
                do_action('icegram_event_track', $event);
                do_action('icegram_event_track_'.$event['type'], $event['params']);
            }
        }
        $messages_shown = array_values ( array_filter ( array_unique($messages_shown) ) ); 
        setcookie('icegram_messages_shown', serialize($messages_shown), 0, '/');    
        exit();

    }

    static function install() {
        // Redirect to welcome screen 
        delete_option( '_icegram_activation_redirect' );      
        add_option( '_icegram_activation_redirect', 'pending' );
    }

    public function welcome() {
        // Bail if no activation redirect transient is set
        if ( false === get_option( '_icegram_activation_redirect' ) )
            return;

        // Delete the redirect transient
        delete_option( '_icegram_activation_redirect' );

        wp_redirect( admin_url( 'edit.php?post_type=campaign&page=icegram-support' ) );
        exit;
    }

    public function admin_menus() {

        $welcome_page_title = __( 'Welcome to Icegram', 'icegram' );
        $menu_title = __( 'Docs & Support', 'icegram' );
        $about = add_submenu_page( 'edit.php?post_type=campaign', $welcome_page_title,  $menu_title, 'manage_options', 'icegram-support', array( $this, 'about_screen' ) );

        add_action( 'admin_print_styles-'. $about, array( $this, 'admin_css' ) );

    }

    public function admin_css() {
        wp_enqueue_style( 'icegram-activation', $this->plugin_url . '/assets/css/admin.css' );
    }

    public function about_screen() {
        global $icegram, $icegram_upgrader;

        // Import data if not done already
        if( false === get_option( 'icegram_sample_data_imported' ) ) {
            $icegram->import( $icegram->get_sample_data() );
        }

        include ( 'about-icegram.php' );
    }

    function display_messages( $atts = array() ) {        
        
        if( !empty( $atts ) ) {
            extract( shortcode_atts( array(
                'campaigns' => '',
                'messages'  => ''
            ), $atts ) );

            $campaign_ids   = explode( ',', $campaigns );
            $campaign_ids   = array_map( 'trim', $campaign_ids );
            $message_ids    = explode( ',', $messages );
            $message_ids    = array_map( 'trim', $message_ids );
            $messages       = $this->get_valid_messages( $message_ids, $campaign_ids );

        } elseif( !empty( $_GET['campaign_preview_id'] ) ) {

            $message_ids    = get_post_meta( $_GET['campaign_preview_id'], 'campaign_preview', true );
            $messages       = $this->get_valid_messages( $message_ids, array(), true );

        } else {
            $messages   = $this->get_valid_messages();
        }

        if( empty( $messages ) )
            return;

        $messages_shown = (array) unserialize( (!empty($_COOKIE['icegram_messages_shown']) ? stripslashes( $_COOKIE['icegram_messages_shown'] ) : '') );

        foreach ( $messages as $key => $message_data ) {

            if( !empty( $message_data['id'] ) &&
                empty( $_GET['campaign_preview_id'] ) &&
                in_array( $message_data['id'], $messages_shown ) &&
                !empty( $message_data['retargeting'] ) &&
                $message_data['retargeting'] == 'yes' 
            ) {
                    unset( $messages[$key] );
                    continue;
            }
            
            // Our own implementation so WP does not mess with script, style and pre tags
            add_filter('the_content', array( $this, 'before_wpautop' ) , 9);
            add_filter('the_content', array( $this, 'after_wpautop' ) , 11);
            $messages[$key]['message'] = apply_filters( 'the_content', $message_data['message'] );
            remove_filter('the_content', array( $this, 'before_wpautop' ) , 9);
            remove_filter('the_content', array( $this, 'after_wpautop' ) , 11);
            $this->get_template( $message_data );
        }

        if( empty( $messages ) )
            return;
        
        wp_register_script( 'icegram_frontend_script', $this->plugin_url . '/assets/js/frontend.js', array ( 'jquery' ), '', true );
        wp_enqueue_style( 'icegram_frontend_styles', $this->plugin_url . '/assets/css/frontend.css' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_style( 'thickbox' ); 

        $icegram_default = apply_filters( 'icegram_branding_data', 
                                            array ( 'default_promo_image'   => $this->plugin_url . '/assets/images/icegram-logo-branding-64-grey.png',
                                                    'powered_by_logo'       => '',
                                                    'powered_by_text'       => ''
                                                    ) );
        $icegram = array ( 'messages'       => array_values( $messages ),
                           'ajax_url'       => admin_url( 'admin-ajax.php' ),
                           'preview_id'     => !empty( $_GET['campaign_preview_id'] ) ? $_GET['campaign_preview_id'] : '',
                           'icegram_default'=> $icegram_default
                        );

        if( !wp_script_is( 'icegram_frontend_script' ) ) {
            wp_enqueue_script( 'icegram_frontend_script' );
            wp_localize_script( 'icegram_frontend_script', 'icegram_data', $icegram );
        }
        
    }

    function get_template( $message_data = array() ) {

        if( empty( $message_data ) )
            return;

        $theme = $message_data['theme'][$message_data['type']];

        if( !wp_style_is( $message_data['type'] . "_" . $theme ) ) {
            wp_enqueue_style( $message_data['type'] . "_" . $theme , $this->plugin_url . '/assets/css/' . str_replace( "_", "-", $message_data['type'] ) . '/' . $theme . '.css' );
        }

        $template_file = str_replace( "_", "-", $message_data['type'] ) . ".php";
        include( $this->plugin_path . '/templates/' . $template_file );        
        
    }

    function enqueue_admin_styles_and_scripts() {
        
        $screen = get_current_screen();        
        if ( !in_array( $screen->id, array( 'campaign', 'message' ), true ) ) return;

        // Register scripts
        wp_register_script( 'icegram_writepanel', $this->plugin_url . '/assets/js/admin.js' , array ( 'jquery', 'wp-color-picker' ) );
        wp_register_script( 'icegram_frontend_script', $this->plugin_url . '/assets/js/frontend.js', array ( 'jquery' ), '', true );
        wp_register_script( 'icegram_chosen', $this->plugin_url . '/assets/js/chosen.jquery.js' , array ( 'jquery' ), '1.0' );
        wp_register_script( 'icegram_ajax-chosen', $this->plugin_url . '/assets/js/ajax-chosen.jquery.min.js' , array ( 'icegram_chosen' ), '1.0' );
        wp_register_script( 'icegram_tiptip', $this->plugin_url . '/assets/js/jquery.tipTip.min.js' , array ( 'jquery' ), get_bloginfo( 'version' ) );
        
        wp_enqueue_script( 'icegram_writepanel' );
        wp_enqueue_script( 'icegram_frontend_script' );
        wp_enqueue_script( 'icegram_ajax-chosen' );
        wp_enqueue_script( 'icegram_tiptip' );
        wp_enqueue_script( 'thickbox' );
        
        $icegram_witepanel_params       = array ('ajax_url' => admin_url( 'admin-ajax.php' ), 'search_message_nonce' => wp_create_nonce( "search-messages" ) );
        $icegram_available_headlines    = apply_filters( 'icegram_available_headlines', array() );
        $icegram_witepanel_params       = array_merge( $icegram_witepanel_params, array( 'available_headlines' => $icegram_available_headlines ) );
        
        wp_localize_script( 'icegram_writepanel', 'icegram_writepanel_params', $icegram_witepanel_params );
        
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'icegram_admin_styles', $this->plugin_url . '/assets/css/admin.css' );
        wp_enqueue_style( 'icegram_jquery-ui-style', $this->plugin_url . '/assets/css/jquery-ui.css' );
        wp_enqueue_style( 'icegram_chosen_styles', $this->plugin_url . '/assets/css/chosen.css' );

        if ( !wp_script_is( 'jquery-ui-datepicker' ) ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

    }

    public static function get_platform() {
        $platform = '';
        $user_agent = trim(strtolower($_SERVER['HTTP_USER_AGENT']));
        $pattern = '/(android\s\d|blackberry|ip(hone|ad|od)|iemobile|webos|palm|symbian|kindle|windows|win64|wow64|macintosh|intel\smac\sos\sx|ppx\smac\sos\sx|googlebot|googlebot-mobile)/';
        if ( preg_match( $pattern, $user_agent, $matches ) ) {
            $platform = $matches[0];
        }
        
        switch ( $platform ) {
            
            /* phones / smartphones */
            case 'android 1':
            case 'android 2':
            case 'blackberry':
            case 'iphone':
            case 'ipod':
            case 'iemobile':
            case 'webos':
            case 'palm':
            case 'symbian':
            case 'googlebot-mobile':
                $platform = 'mobile';
                break;
            
            /* tablets */
            case 'android 3':
            case 'android 4':
            case 'ipad':
            case 'kindle':
                $platform = 'tablet';
                break;
            
            /* desktops / laptops */
            case 'windows':
            case 'win64':
            case 'wow64':
            case 'macintosh':
            case 'ppx mac os x':
            case 'intel mac os x':
            case 'googlebot':
                $platform = 'laptop';
                break;
            
            /* in case nothing else matches */
            default:
                $platform = 'laptop';
                break;
        }
        return $platform;
    }

    function get_message_data( $message_ids = array(), $preview = false ) {
        global $wpdb;

        $meta_key = $preview ? 'icegram_message_preview_data' : 'icegram_message_data';
        $message_data_query = "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '$meta_key'";
        if ( !empty( $message_ids ) && is_array( $message_ids ) ) {
            $message_data_query .= " AND post_id IN ( " . implode( ',', $message_ids ) . " )";
        }
        $message_data_results = $wpdb->get_results( $message_data_query, 'ARRAY_A' );
        $message_data = array();
        foreach ( $message_data_results as $message_data_result ) {
            $message_data[$message_data_result['post_id']] = maybe_unserialize( $message_data_result['meta_value'] );
        }

        return $message_data;
    }

    function get_valid_messages( $message_ids = array(), $campaign_ids = array(), $preview = false ) {

        $valid_message_data   = $this->get_message_data( array(), $preview );

        $valid_messages   = array();
        $valid_campaigns   = array();

        if ( empty( $message_ids ) && empty( $campaign_ids ) ) {
            $valid_campaigns    = $this->get_valid_campaigns();
        } else {
            if ( !empty( $message_ids ) ) {

                if( $preview ) {
                    foreach ( $message_ids as $message ) {
                        if ( empty( $valid_message_data[$message['id']] ) ) continue;
                        $message_data = $valid_message_data[$message['id']];
                        if ( !empty( $message_data ) ) {
                            $message_data['delay_time']     = $message['time'];
                            $message_data['campaign_id']    = !empty( $_GET['campaign_preview_id'] ) ? $_GET['campaign_preview_id'] : '';
                            $message_data['retargeting']    = '';
                            $valid_messages[] = $message_data;
                        }
                    }
                } else {                    
                    foreach ( $message_ids as $message_id ) {
                        if ( empty( $valid_message_data[$message_id] ) ) continue;
                        $message_data = $valid_message_data[$message_id];
                        if ( !empty( $message_data ) ) {
                            $message_data['delay_time']     = 0;
                            $message_data['campaign_id']    = '';
                            $message_data['retargeting']    = '';
                            $valid_messages[] = $message_data;
                        }
                    }
                }
            }

            if ( !empty( $campaign_ids ) ) {
                foreach ( $campaign_ids as $campaign_id ) {                    
                    $valid_campaigns[$campaign_id] = new WP_Campaign( $campaign_id );
                }
            }

        }

        if ( !empty( $valid_campaigns ) ) {
            foreach ( $valid_campaigns as $campaign_id => $campaign ) {
                if ( !empty( $campaign->message_ids ) ) {
                    foreach ( $campaign->message_ids as $message ) {
                        $message_data = $valid_message_data[$message['id']];
                        if ( !empty( $message_data ) ) {
                            $campaign_rules  = get_post_meta( $campaign_id, 'icegram_campaign_target_rules', true );    
                            $message_data['delay_time']     = $message['time'];
                            $message_data['campaign_id']    = $campaign_id;
                            $message_data['retargeting']    = !empty( $campaign_rules['retargeting'] ) ? $campaign_rules['retargeting'] : '';
                            $valid_messages[] = $message_data;
                        }
                    }
                }
            }
        }

        return $valid_messages;

    }

    function get_valid_campaigns( $campaign_ids = array() ) {
        global $wpdb;

        if ( empty( $campaign_ids ) ) {
            $search_text = serialize((string)self::$current_page_id);
            $campaign_ids = $wpdb->get_col( "SELECT pm.post_id FROM {$wpdb->prefix}posts AS p LEFT JOIN {$wpdb->prefix}postmeta AS pm ON ( pm.post_id = p.ID ) WHERE pm.meta_key LIKE 'icegram_campaign_target_rules' AND pm.meta_value LIKE '%" . $search_text . "%' AND p.post_status LIKE 'publish'" );
        }
        $valid_campaigns = array();
        foreach ( $campaign_ids as $campaign_id ) {
            // if ( empty( $campaign_id['post_id'] ) ) continue;
            $campaign = new WP_Campaign( $campaign_id );
            if ( $campaign->is_valid() ) {
                $valid_campaigns[$campaign_id] = $campaign;
            }
        }
        return $valid_campaigns;
    }

    // Include all classes required for Icegram plugin
    function include_classes() {

        if ( ! class_exists( 'WP_Campaign' ) )
            include_once( 'classes/class-wp-campaign.php' );

        if ( ! class_exists( 'WP_Message' ) )
            include_once( 'classes/class-wp-message.php' );
        
    }
    
    // Register Campaign post type
    function register_campaign_post_type() {
        $labels = array(
            'name'               => __( 'Campaigns', 'icegram' ),
            'singular_name'      => __( 'Campaign', 'icegram' ),
            'add_new'            => __( 'Add New Campaign', 'icegram' ),
            'add_new_item'       => __( 'Add New Campaign', 'icegram' ),
            'edit_item'          => __( 'Edit Campaign', 'icegram' ),
            'new_item'           => __( 'New Campaign', 'icegram' ),
            'all_items'          => __( 'Campaigns', 'icegram' ),
            'view_item'          => __( 'View Campaign', 'icegram' ),
            'search_items'       => __( 'Search Campaigns', 'icegram' ),
            'not_found'          => __( 'No campaigns found', 'icegram' ),
            'not_found_in_trash' => __( 'No campaigns found in Trash', 'icegram' ),
            'parent_item_colon'  => __( '', 'icegram' ),
            'menu_name'          => __( 'Icegram', 'icegram' )
        );

        $args = array(
            'labels'             => $labels,
            'menu_icon'          => 'dashicons-info', 
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'campaign' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor' )
        );

        register_post_type( 'campaign', $args );
    }

    // Register Message promo type
    function register_message_post_type() {
        $labels = array(
            'name'               => __( 'Messages', 'icegram' ),
            'singular_name'      => __( 'Message', 'icegram' ),
            'add_new'            => __( 'Create New', 'icegram' ),
            'add_new_item'       => __( 'Create New Message', 'icegram' ),
            'edit_item'          => __( 'Edit Message', 'icegram' ),
            'new_item'           => __( 'New Message', 'icegram' ),
            'all_items'          => __( 'Messages', 'icegram' ),
            'view_item'          => __( 'View Message', 'icegram' ),
            'search_items'       => __( 'Search Messages', 'icegram' ),
            'not_found'          => __( 'No messages found', 'icegram' ),
            'not_found_in_trash' => __( 'No messages found in Trash', 'icegram' ),
            'parent_item_colon'  => __( '', 'icegram' ),
            'menu_name'          => __( 'Messages', 'icegram' )
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=campaign',
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'message' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' )
        );

        register_post_type( 'message', $args );
    }

    function import( $data = array() ) {

        if ( empty( $data['campaigns'] ) && empty( $data['messages'] ) ) return;

        $message_themes = apply_filters( 'icegram_all_message_theme', array() );

        $default_themes = array();

        foreach ( (array) $message_themes as $message_type => $available_themes ) {

            reset( $available_themes );
            $first_theme = key( $available_themes );
            $default_themes[ $message_type ] = $first_theme;

        }

        $new_campaign_ids = array();

        foreach ( (array) $data['campaigns'] as $campaign ) {

            $args = array( 
                        'post_content'   =>  ( !empty( $campaign['post_content'] ) ) ? esc_attr( $campaign['post_content'] ) : '',
                        'post_name'      =>  ( !empty( $campaign['post_title'] ) ) ? sanitize_title( $campaign['post_title'] ) : '',
                        'post_title'     =>  ( !empty( $campaign['post_title'] ) ) ? $campaign['post_title'] : '',
                        'post_status'    =>  ( !empty( $campaign['post_status'] ) ) ? $campaign['post_status'] : 'draft',
                        'post_type'      =>  'campaign'
                     );

            $new_campaign_id = wp_insert_post( $args );
            $new_campaign_ids[] = $new_campaign_id;

            if ( !empty( $campaign['target_rules'] ) ) {

                $defaults = array (
                                'homepage'   => 'yes',
                                'when'       => 'always',
                                'from'       => '',
                                'to'         => '',
                                'mobile'     => 'yes',
                                'tablet'     => 'yes',
                                'laptop'     => 'yes',
                                'logged_in'  => 'all'
                            );

                $target_rules = wp_parse_args( $campaign['target_rules'], $defaults );
                update_post_meta( $new_campaign_id, 'icegram_campaign_target_rules', $target_rules );
            }

            if ( !empty( $campaign['messages'] ) ) {

                $messages = array();

                foreach ( $campaign['messages'] as $message ) {

                    if ( !is_array( $message ) ) continue;

                    $args = array( 
                                'post_content'   =>  ( !empty( $message['message'] ) ) ? esc_attr( $message['message'] ) : '',
                                'post_name'      =>  ( !empty( $message['post_title'] ) ) ? sanitize_title( $message['post_title'] ) : '',
                                'post_title'     =>  ( !empty( $message['post_title'] ) ) ? $message['post_title'] : '',
                                'post_status'    =>  ( !empty( $message['post_status'] ) ) ? $message['post_status'] : 'publish',
                                'post_type'      =>  'message'
                             );

                    $new_message_id = wp_insert_post( $args );
                    $new_message = array(
                                        'id'    => $new_message_id,
                                        'time'  => ( !empty( $message['time'] ) ) ? $message['time'] : 0
                                    );
                    $messages[] = $new_message;

                    unset( $message['post_content'] );
                    unset( $message['time'] );

                    $message['id'] = $new_message_id;

                    $defaults = array (
                                    'post_title'            => '',
                                    'type'                  => $first_theme,
                                    'theme'                 => $default_themes,
                                    'animation'             => '',
                                    'toast_animation'       => '',
                                    'title'                 => '',
                                    'label'                 => '',
                                    'link'                  => '',
                                    'promo_image'           => '',
                                    'message'               => '',
                                    'position'              => '',
                                    'text_color'            => '#000000',
                                    'bg_color'              => '#ffffff',
                                    'id'                    => ''
                                );
                    
                    $icegram_message_data = wp_parse_args( $message, $defaults );

                    if ( !empty( $icegram_message_data ) ) {
                        update_post_meta( $new_message_id, 'icegram_message_data', $icegram_message_data );
                        update_post_meta( $new_message_id, 'icegram_message_preview_data', $icegram_message_data );
                    }
                }

                if ( !empty( $campaign['messages'] ) ) {
                    update_post_meta( $new_campaign_id, 'messages', $messages );
                    update_post_meta( $new_campaign_id, 'campaign_preview', $messages );
                }

            }
        }
        if( !empty( $new_campaign_ids ) ) {
            update_option( 'icegram_sample_data_imported', $new_campaign_ids );
        }

    }

    function get_sample_data() {
        global $icegram;
        return array(
                'campaigns' => array(
                        array(
                                'post_name'     => '',
                                'post_title'    => 'My First Icegram Campaign',
                                'target_rules'  => array (
                                                        'homepage'   => 'yes',
                                                        'when'       => 'always',
                                                        'from'       => '',
                                                        'to'         => '',
                                                        'mobile'     => 'yes',
                                                        'tablet'     => 'yes',
                                                        'laptop'     => 'yes',
                                                        'logged_in'  => 'all'
                                                    ),
                                'messages'      => array(
                                                        array (
                                                                'post_title'            => 'Get 2x more Contacts with Your Website',
                                                                'post_status'           => 'publish',
                                                                'time'                  => '0',
                                                                'type'                  => 'action-bar',
                                                                'theme'                 => array ( 'action-bar' => 'hello' ),
                                                                'animation'             => 'slide',
                                                                'toast_animation'       => 'effect1',
                                                                'title'                 => 'Get 2x more Contacts with Your Website',
                                                                'label'                 => 'Show Me How',
                                                                'link'                  => '',
                                                                'promo_image'           => '',
                                                                'message'               => 'Instant Results Guaranteed',
                                                                'position'              => '01',
                                                                'text_color'            => '#000000',
                                                                'bg_color'              => '#eb593c'
                                                            ),
                                                        array (
                                                                'post_title'            => '20% Off Coupon',
                                                                'post_status'           => 'publish',
                                                                'time'                  => '4',
                                                                'type'                  => 'messenger',
                                                                'theme'                 => array ( 'messenger' => 'social' ),
                                                                'animation'             => 'slide',
                                                                'toast_animation'       => '',
                                                                'title'                 => '20% Off - for you',
                                                                'label'                 => '',
                                                                'link'                  => '',
                                                                'promo_image'           => '',
                                                                'message'               => "Hey there! We are running a <strong>special 20% off this week</strong> for registered users - like you. 

                                                                Use coupon <code>LOYALTY20</code> during checkout.",
                                                                'position'              => '22',
                                                                'text_color'            => '#000000',
                                                                'bg_color'              => '#ffffff'                                                        
                                                            ),
                                                        array (
                                                                'post_title'            => 'How this blog makes over $34,800 / month for FREE.',
                                                                'post_status'           => 'publish',
                                                                'time'                  => '10',
                                                                'type'                  => 'popup',
                                                                'theme'                 => array ( 'popup' => 'air-mail' ),
                                                                'animation'             => '',
                                                                'toast_animation'       => '',
                                                                'title'                 => 'How this blog makes over $34,800 / month for FREE.',
                                                                'label'                 => 'FREE INSTANT ACCESS',
                                                                'link'                  => '',
                                                                'promo_image'           => '',
                                                                'message'               => "This website earns over $30,000 every month, every single month, almost on autopilot. I have 4 other sites with similar results. All I do is publish new regular content every week.

<strong>Download my free kit to learn how I do this.</strong>

<ul>
    <li>How to choose blog topics that create long term value</li>
    <li>The type of blog post that will make your site go viral</li>
    <li>How to free yourself from the routine tasks</li>
    <li>Resources and tips to get started quickly</li>
    <li>Private members club to connect with fellow owners</li>
</ul>",
                                                                'text_color'            => '#000000',
                                                                'bg_color'              => '#ffffff'
                                                                                                                        
                                                            ),
                                                        array (
                                                                'post_title'            => 'Exclusive Marketing Report',
                                                                'post_status'           => 'publish',
                                                                'time'                  => '6',
                                                                'type'                  => 'toast',
                                                                'theme'                 => array ( 'toast' => 'stand-out' ),
                                                                'animation'             => '',
                                                                'toast_animation'       => 'slide-down',
                                                                'title'                 => 'Exclusive Marketing Report',
                                                                'label'                 => '',
                                                                'link'                  => '',
                                                                'promo_image'           => '',
                                                                'message'               => 'FREE for every subscriber. Click here to download it.',
                                                                'position'              => '02',
                                                                'text_color'            => '#000000',
                                                                'bg_color'              => '#ffffff'
                                                            )
                            
                                                    )
                            )
                    )
            );
    }

    function remove_preview_button() {
        global $post_type;

        if( $post_type == 'message' || $post_type == 'campaign' ) {

            ?>
            <style>
                #preview-action { display:none; }
            </style>
            <?php

        }

    }

    function remove_row_actions( $actions, $post ) {

        if ( empty( $post->post_type ) || ( $post->post_type != 'campaign' && $post->post_type != 'message' ) ) return $actions;
        
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['view'] );
        
        return $actions;

    }

    function identify_current_page() {
        if ( is_page() ) {
            global $post;
            self::$current_page_id = $post->ID;
        }
    }

    /**
     * Our implementation of wpautop to preserve script and style tags
     */
    function before_wpautop($pee) {
        if ( trim($pee) === '' ) {
            $this->_wpautop_tags = array();
            return '';
        }

        $tags = array();
        // Pull out tags and add placeholders
        list( $pee, $tags['pre'] ) = $this->_wpautop_add_tag_placeholders( $pee, 'pre' );
        list( $pee, $tags['script'] ) = $this->_wpautop_add_tag_placeholders( $pee, 'script' );
        list( $pee, $tags['style'] ) = $this->_wpautop_add_tag_placeholders( $pee, 'style' );
        $this->_wpautop_tags = $tags;

        if( !empty( $pre_tags ) )
            $pee = $this->_wpautop_replace_tag_placeholders( $pee, $pre_tags );
        if( !empty( $script_tags ) )
            $pee = $this->_wpautop_replace_tag_placeholders( $pee, $script_tags );
        if( !empty( $style_tags ) )
            $pee = $this->_wpautop_replace_tag_placeholders( $pee, $style_tags );

        return $pee;
    }

    function after_wpautop($pee) {
        if ( trim($pee) === '' || empty($this->_wpautop_tags) )
            return '';

        // Replace placeholders with original content
        if (!empty($this->_wpautop_tags['pre'])) {
            $pee = $this->_wpautop_replace_tag_placeholders( $pee, $this->_wpautop_tags['pre'] );
        }
        if (!empty($this->_wpautop_tags['script'])) {
            $pee = $this->_wpautop_replace_tag_placeholders( $pee, $this->_wpautop_tags['script'] );
        }
        if (!empty($this->_wpautop_tags['style'])) {
            $pee = $this->_wpautop_replace_tag_placeholders( $pee, $this->_wpautop_tags['style'] );
        }

        $this->_wpautop_tags = array();

        return $pee;
    }


    function _wpautop_add_tag_placeholders( $pee, $tag ) {
            $tags = array();

            if ( false !== strpos( $pee, "<{$tag}" ) ) {
                    $pee_parts = explode( "</{$tag}>", $pee );
                    $last_pee = array_pop( $pee_parts );
                    $pee = '';
                    $i = 0;

                    foreach ( $pee_parts as $pee_part ) {
                            $start = strpos( $pee_part, "<{$tag}" );

                            // Malformed html?
                            if ( false === $start ) {
                                    $pee .= $pee_part;
                                    continue;
                            }

                            $name = "<{$tag} wp-{$tag}-tag-$i></{$tag}>";
                            $tags[ $name ] = substr( $pee_part, $start ) . "</{$tag}>";

                            $pee .= substr( $pee_part, 0, $start ) . $name;
                            $i++;
                    }

                    $pee .= $last_pee;
            }

            return array( $pee, $tags );
    }


    function _wpautop_replace_tag_placeholders( $pee, $tags ) {
            if ( ! empty( $tags ) ) {
                    $pee = str_replace( array_keys( $tags ), array_values( $tags ), $pee );
            }

            return $pee;
    }


}

function initialize_icegram() {
    global $icegram, $icegram_upgrader;

    // i18n / l10n - load translations
    load_plugin_textdomain( 'icegram', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' ); 

    $icegram = new Icegram();
}

add_action( 'plugins_loaded', 'initialize_icegram' );
register_activation_hook( __FILE__, array( 'Icegram', 'install' ) );