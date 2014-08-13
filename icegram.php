<?php
/*
 * Plugin Name: Icegram
 * Plugin URI: http://www.icegram.com/
 * Description: All in one solution to inspire, convert and engage your audiences. Action bars, Popup windows, Messengers, Toast notifications and more. Awesome themes and powerful rules.
 * Version: 1.3
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
    var $version;
    var $_wpautop_tags;
    var $message_types;
    var $message_type_objs;
    var $shortcode_instances;
    var $available_headlines;

    public static $current_page_id;
    
    function __construct() {

        $this->version = "1.3";
        $this->shortcode_instances = array();
        $this->plugin_url   = untrailingslashit( plugins_url( '/', __FILE__ ) );
        $this->plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) );
        $this->include_classes();

        if( is_admin() && current_user_can( 'manage_options' ) ) {
            new Icegram_Campaign_Admin();
            new Icegram_Message_Admin();
            add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_styles_and_scripts' ) );
            add_action( 'admin_print_styles', array( &$this, 'remove_preview_button' ) );        
            add_filter( 'post_row_actions', array( &$this , 'remove_row_actions' ), 10, 2 );

            add_action( 'admin_menu', array( &$this, 'admin_menus') );
            add_action( 'admin_init', array( &$this, 'welcome' ) );

            add_action( 'icegram_settings_after', array( &$this, 'klawoo_subscribe_form' ) ); 
            add_action( 'icegram_about_changelog', array( &$this, 'klawoo_subscribe_form' ) ); 
        } else {
            add_action( 'wp_footer', array( &$this, 'display_messages' ) );
            //add_action( 'wp_head', array( &$this, 'nofollow_noindex' ) );
            add_action( 'wp_print_scripts', array( &$this, 'identify_current_page' ) );
            add_shortcode( 'icegram', array( &$this, 'execute_shortcode' ) );
            add_filter( 'icegram_branding_data', array( &$this , 'branding_data_remove' ), 10 );
            add_filter( 'icegram_get_valid_campaigns_sql', array( &$this , 'append_to_valid_campaigns_sql' ), 10, 2 );
            // WPML compatibility
            add_filter( 'icegram_identify_current_page',  array( &$this, 'wpml_get_parent_id' ), 10 );
        }

        // common
        add_action( 'init', array( &$this, 'register_campaign_post_type' ) );
        add_action( 'init', array( &$this, 'register_message_post_type' ) );

        if ( defined( 'DOING_AJAX' ) ) {
            add_action( 'wp_ajax_icegram_event_track', array( &$this, 'icegram_event_track' ) );
            add_action( 'wp_ajax_nopriv_icegram_event_track', array( &$this, 'icegram_event_track' ) );
            add_action( 'wp_ajax_klawoo_subscribe', array( &$this, 'klawoo_subscribe' ) );
        }

    }

    public function klawoo_subscribe_form() {
        ?>
        <div class="wrap">
            <?php 
            if ( stripos(get_current_screen()->base, 'settings') !== false ) {
                echo "<h2>".__( 'Get Updates', 'icegram' )."</h2>";
            }
            ?>
            <table class="form-table">
                 <tr>
                    <th scope="row"><?php _e( 'Join our newsletter', 'icegram' ) ?></th>
                    <td>
                        <form name="klawoo_subscribe" action="#" method="POST" accept-charset="utf-8">
                            <input class="ltr" type="text" name="name" id="name" placeholder="Name"/>
                            <input class="regular-text ltr" type="text" name="email" id="email" placeholder="Email"/>
                            <input type="hidden" name="list" value="7I763v6Ldrs3YhJeee5EOgFA"/>
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Subscribe">
                            <br/>
                            <div id="klawoo_response"></div>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
        <script type="text/javascript">
            jQuery(function () {
                jQuery("form[name=klawoo_subscribe]").submit(function (e) {
                    e.preventDefault();
                    
                    jQuery('#klawoo_response').html('');
                    params = jQuery("form[name=klawoo_subscribe]").serializeArray();
                    params.push( {name: 'action', value: 'klawoo_subscribe' });
                    
                    jQuery.ajax({
                        method: 'POST',
                        type: 'text',
                        url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                        data: params,
                        success: function(response) {                   
                            if (response != '') {
                                jQuery('#klawoo_response').html(response);
                            } else {
                                jQuery('#klawoo_response').html('error!');
                            }
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function klawoo_subscribe() {
        $url = 'http://app.klawoo.com/subscribe';

        if( !empty( $_POST ) ) {
            $params = $_POST;
        } else {
            exit();
        }
        $method = 'POST';
        $qs = http_build_query( $params );

        $options = array(
            'timeout' => 15,
            'method' => $method
        );

        if ( $method == 'POST' ) {
            $options['body'] = $qs;
        } else {
            if ( strpos( $url, '?' ) !== false ) {
                $url .= '&'.$qs;
            } else {
                $url .= '?'.$qs;
            }
        }

        $response = wp_remote_request( $url, $options );
        if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
            $data = $response['body'];
            if ( $data != 'error' ) {
                             
                $message_start = substr( $data, strpos( $data,'<body>' ) + 6 );
                $remove = substr( $message_start, strpos( $message_start,'</body>' ) );
                $message = trim( str_replace( $remove, '', $message_start ) );
                echo ( $message );
                exit();                
            }
        }
        exit();
    }

    public function icegram_event_track() {        

        if( !empty( $_POST['event_data'] ) ) {

            $messages_shown = (array) explode(",", html_entity_decode( (!empty($_COOKIE['icegram_messages_shown']) ? stripslashes( $_COOKIE['icegram_messages_shown'] ) : '') ) );

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
        setcookie('icegram_messages_shown', implode(",", $messages_shown), 0, '/');    
        exit();
    }

    static function install() {
        // Redirect to welcome screen 
        delete_option( '_icegram_activation_redirect' );      
        add_option( '_icegram_activation_redirect', 'pending' );
    }

    public function welcome() {

        $this->db_update();
        // Bail if no activation redirect transient is set
        if ( false === get_option( '_icegram_activation_redirect' ) )
            return;

        // Delete the redirect transient
        delete_option( '_icegram_activation_redirect' );

        wp_redirect( admin_url( 'edit.php?post_type=ig_campaign&page=icegram-support' ) );
        exit;
    }

    function db_update() {

        $current_db_version = get_option( 'icegram_db_version' );
        if ( !$current_db_version || version_compare( $current_db_version, '1.2', '<' ) ) {
            include( 'updates/icegram-update-1.2.php' );
        }

    }

    public function admin_menus() {

        $welcome_page_title     = __( 'Welcome to Icegram', 'icegram' );
        $settings_page_title    = __( 'Settings', 'icegram' ); 
        $addons_page_title      = __( 'Add-ons', 'icegram' );

        /*
        if ( false === ( $ig_addons = get_transient( 'icegram_addons_data' ) ) ) {
            $this->check_for_addons();
        }
        $addon_count            = get_option( 'icegram_no_of_addons' );
        
        if ($addon_count > 0) {
            $addons_page_title .= " <span class='awaiting-mod update-plugins count-$addon_count'><span class='addon-count'>" . number_format_i18n( $addon_count ) . "</span></span>" ;
        }
        */

        $menu_title = __( 'Docs & Support', 'icegram' );
        $about      = add_submenu_page( 'edit.php?post_type=ig_campaign', $welcome_page_title,  $menu_title, 'manage_options', 'icegram-support', array( $this, 'about_screen' ) );
        $settings   = add_submenu_page( 'edit.php?post_type=ig_campaign', $settings_page_title,  $settings_page_title, 'manage_options', 'icegram-settings', array( $this, 'settings_screen' ) );
        $addons     = add_submenu_page( 'edit.php?post_type=ig_campaign', $addons_page_title,  $addons_page_title, 'manage_options', 'icegram-addons', array( $this, 'addons_screen' ) );

        add_action( 'admin_print_styles-'. $about, array( $this, 'admin_css' ) );
        add_action( 'admin_print_styles-'. $settings, array( $this, 'admin_css' ) );
        add_action( 'admin_print_styles-'. $addons, array( $this, 'admin_css' ) );

    }

    public function admin_css() {
        wp_enqueue_style( 'icegram-activation', $this->plugin_url . '/assets/css/admin.css' );
    }

    public function about_screen() {

        // Import data if not done already
        if( false === get_option( 'icegram_sample_data_imported' ) ) {
            $this->import( $this->get_sample_data() );
        }

        include ( 'about-icegram.php' );
    }

    public function settings_screen() {        
        include ( 'settings.php' );
    }

    public function addons_screen() {        
        $ig_addons = $this->check_for_addons( true );
        include ( 'addons.php' );
    }

    public function check_for_addons( $force_update = false ) {

        if ( $force_update === true || false === ( $ig_addons = get_transient( 'icegram_addons_data' ) ) ) {
            $ig_addons_json = wp_remote_get( 'http://icegram.com/addons.json', array( 'user-agent' => 'Icegram Addons' ) );

            if ( ! is_wp_error( $ig_addons_json ) ) {
                $ig_addons      = json_decode( wp_remote_retrieve_body( $ig_addons_json ) );
                $addon_count    = get_option( 'icegram_no_of_addons', 0 );
                if ( !empty($ig_addons) && is_array($ig_addons) ) {
                    set_transient( 'icegram_addons_data', $ig_addons, 24 * HOUR_IN_SECONDS ); // 1 day
                    update_option( 'icegram_no_of_addons', count( $ig_addons ) - $addon_count ); // display count of newly added addons
                }
            }
        }
        return $ig_addons;
    }

    public function branding_data_remove( $icegram_branding_data ) {
        if( !empty( $icegram_branding_data ) && 'yes' != get_option('icegram_share_love', 'no') ) {
            $icegram_branding_data['powered_by_logo'] = '';
            $icegram_branding_data['powered_by_text'] = '';
        }
        return $icegram_branding_data;
    }

    function execute_shortcode( $atts = array() ) {
        // When shortcode is called, it will only prepare an array with conditions
        // And add a placeholder div
        // Display will happen in footer via display_messages()
        $i = count($this->shortcode_instances);
        $this->shortcode_instances[ $i ] = shortcode_atts( array(
                'campaigns' => '',
                'messages'  => '',
                'skip_others' => 'no'
            ), $atts );
        $html[] = "<div class='ig_shortcode_container' id='icegram_shortcode_{$i}'";
        foreach ($atts as $key => $value) {
            $value = str_replace(",", " ", $value);
            $html[] = " data-{$key}=\"".htmlentities($value)."\" ";
        }
        $html[] = " >"."</div>";
        return implode(" ", $html);
    }

    // Do not index Icegram campaigns / messages...
    // Not using currently - made custom post types non public...
    function nofollow_noindex() {
        $post = get_queried_object();
        if ( (!empty($post) && !empty( $post->post_type ) && ( $post->post_type == 'ig_campaign' || $post->post_type == 'ig_message' ))
            || is_post_type_archive( array('ig_message', 'ig_campaign') ) ) {
            echo PHP_EOL . '<meta name="robots" content="NOINDEX,NOFOLLOW" />' . PHP_EOL;
        }
    }

    function display_messages() {
        
        $skip_others    = $preview_mode = false;
        $campaign_ids   = $message_ids  = array();

        // Pull in message and campaign IDs from shortcodes - if set
        if( !empty( $this->shortcode_instances ) ) {
            foreach ($this->shortcode_instances as $i => $value) {
                $cids   = array_map( 'trim', (array) explode( ',', $value['campaigns'] ) );
                $mids   = array_map( 'trim', (array) explode( ',', $value['messages'] ) );
                if ($value['skip_others'] == 'yes' && (!empty($cids) || !empty($mids))) {
                    $skip_others = true;
                }
                $campaign_ids   = array_merge($campaign_ids, $cids);
                $message_ids    = array_merge($message_ids, $mids);
            }
        }

        if( !empty( $_GET['campaign_preview_id'] ) && current_user_can( 'manage_options' ) ) {
            $campaign_ids = array( $_GET['campaign_preview_id'] );
            $preview_mode = true;
        }

        $messages = $this->get_valid_messages( $message_ids, $campaign_ids, $preview_mode, $skip_others );

        if( empty( $messages ) ) {
            return;
        }

        $messages_shown = (array) explode(",", html_entity_decode( (!empty($_COOKIE['icegram_messages_shown']) ? stripslashes( $_COOKIE['icegram_messages_shown'] ) : '') ) );
        $types_shown    = array(); $messages_to_show_ids = array();

        foreach ( $messages as $key => $message_data ) {

            if( !is_array( $message_data ) || empty( $message_data ) ) {
                continue;
            }

            // Don't show a seen message again - if needed
            if( !empty( $message_data['id'] ) &&
                empty( $_GET['campaign_preview_id'] ) &&
                in_array( $message_data['id'], $messages_shown ) &&
                !empty( $message_data['retargeting'] ) &&
                $message_data['retargeting'] == 'yes' 
            ) {
                unset( $messages[$key] );
                continue;
            }

            // Avoid showing the same message twice
            if (in_array($message_data['id'], $messages_to_show_ids)) {
                unset ( $messages[$key] );
                continue;
            } else {
                $messages_to_show_ids[] = $message_data['id'];    
            }
            
            $types_shown[] = $message_data['type'];

            /*
            // Our own implementation so WP does not mess with script, style and pre tags
            add_filter('the_content', array( $this, 'before_wpautop' ) , 9);
            add_filter('the_content', array( $this, 'after_wpautop' ) , 11);
            $messages[$key]['message'] = apply_filters( 'the_content', $message_data['message'] );
            remove_filter('the_content', array( $this, 'before_wpautop' ) , 9);
            remove_filter('the_content', array( $this, 'after_wpautop' ) , 11);
            */
            // Redo the_content functionality to avoid other plugins adding extraneous code to messages
            $content = $message_data['message'];
            $content = convert_chars( convert_smilies( wptexturize( $content ) ) );
            if(isset($GLOBALS['wp_embed'])) {
                $content = $GLOBALS['wp_embed']->autoembed($content);
            }
            $content = $this->after_wpautop( wpautop( $this->before_wpautop( $content ) ) );
            $content = do_shortcode( shortcode_unautop( $content ) );
            $messages[$key]['message'] = $content;
            
        }

        if( empty( $messages ) )
            return;
        
        wp_register_script( 'icegram_js', $this->plugin_url . '/assets/js/icegram.js', array ( 'jquery' ), '', true);
        wp_enqueue_style( 'icegram_css', $this->plugin_url . '/assets/css/frontend.css' );
        wp_enqueue_style( 'dashicons' );

        $icegram_default = apply_filters( 'icegram_branding_data', 
                                            array ( 'icon'   => $this->plugin_url . '/assets/images/icegram-logo-branding-64-grey.png',
                                                    'powered_by_logo'       => $this->plugin_url . '/assets/images/icegram-logo-branding-64-grey.png',
                                                    'powered_by_text'       => __( 'Powered by Icegram', 'icegram' )
                                                    ) );
        $messages   = apply_filters( 'icegram_messages_to_show', $messages );
        $icegram    = array ( 'messages'       => array_values( $messages ),
                           'ajax_url'       => admin_url( 'admin-ajax.php' ),
                           'preview_id'     => !empty( $_GET['campaign_preview_id'] ) ? $_GET['campaign_preview_id'] : '',
                           'defaults'       => $icegram_default
                        );
        if (empty($icegram['preview_id'])) {
            unset($icegram['preview_id']);
        }

        if( !wp_script_is( 'icegram_js' ) ) {
            wp_enqueue_script( 'icegram_js' );
            wp_localize_script( 'icegram_js', 'icegram_data', $icegram );
        }

        // Load JS and default CSS
        $types_shown = array_unique($types_shown);
        
        if (in_array('popup', $types_shown)) {
            wp_enqueue_script( 'thickbox' );
            wp_enqueue_style( 'thickbox' ); 
        }

        foreach ($types_shown as $message_type) {
            wp_register_script( 'icegram_message_type_'.$message_type, $this->message_types[$message_type]['baseurl'] . "main.js" , array ( 'icegram_js' ), '', true );
            wp_enqueue_script( 'icegram_message_type_'.$message_type );  
            wp_enqueue_style( 'icegram_css_'.$message_type, $this->message_types[$message_type]['baseurl'] . 'default.css' );
        }

        // Load theme CSS
        foreach ($messages as $message) {
            if (!empty( $this->message_types[ $message['type'] ]['themes'][ $message['theme'] ]) ) {
                $theme = $this->message_types[ $message['type'] ]['themes'][ $message['theme'] ];
                wp_enqueue_style( 'icegram_css_'.$message['type'].'_'.$message['theme'], $theme['baseurl'] . $message['theme'].'.css' );
            }
        }
        
    }

    function enqueue_admin_styles_and_scripts() {
        
        $screen = get_current_screen();   
        if ( !in_array( $screen->id, array( 'ig_campaign', 'ig_message' ), true ) ) return;

        // Register scripts
        wp_register_script( 'icegram_writepanel', $this->plugin_url . '/assets/js/admin.js' , array ( 'jquery', 'wp-color-picker' ) );
        wp_register_script( 'icegram_chosen', $this->plugin_url . '/assets/js/chosen.jquery.min.js' , array ( 'jquery' ), '1.0' );
        wp_register_script( 'icegram_ajax-chosen', $this->plugin_url . '/assets/js/ajax-chosen.jquery.min.js' , array ( 'icegram_chosen' ), '1.0' );
        wp_register_script( 'icegram_tiptip', $this->plugin_url . '/assets/js/jquery.tipTip.min.js' , array ( 'jquery' ), get_bloginfo( 'version' ) );
        
        wp_enqueue_script( 'icegram_writepanel' );
        wp_enqueue_script( 'icegram_ajax-chosen' );
        wp_enqueue_script( 'icegram_tiptip' );
        wp_enqueue_script( 'thickbox' );
        
        $icegram_writepanel_params  = array ( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'search_message_nonce' => wp_create_nonce( "search-messages" ) );
        $this->available_headlines  = apply_filters( 'icegram_available_headlines', array() );
        $icegram_writepanel_params  = array_merge( $icegram_writepanel_params, array( 'available_headlines' => $this->available_headlines ) );
        
        wp_localize_script( 'icegram_writepanel', 'icegram_writepanel_params', $icegram_writepanel_params );
        
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'icegram_admin_styles', $this->plugin_url . '/assets/css/admin.css' );
        wp_enqueue_style( 'icegram_jquery-ui-style', $this->plugin_url . '/assets/css/jquery-ui.min.css' );
        wp_enqueue_style( 'icegram_chosen_styles', $this->plugin_url . '/assets/css/chosen.min.css' );

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

        $message_data = array();
        $meta_key = $preview ? 'icegram_message_preview_data' : 'icegram_message_data';
        $message_data_query = "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '$meta_key'";
        if ( !empty( $message_ids ) && is_array( $message_ids ) ) {
            // For WPML compatibility
            if ( function_exists('icl_object_id') ) {
                $wpml_settings = get_option('icl_sitepress_settings');
                $original_if_missing = (is_array($wpml_settings) && array_key_exists('show_untranslated_blog_posts', $wpml_settings) && !empty($wpml_settings['show_untranslated_blog_posts']) ) ? true : false;
                foreach ($message_ids as $i=>$id ) {
                    $message_ids[ $i ] = icl_object_id( $id, 'ig_message', $original_if_missing );
                }              
            }
            $message_ids  = array_filter(array_unique($message_ids));
            if ( !empty( $message_ids ) ) {
                $message_data_query .= " AND post_id IN ( " . implode( ',', $message_ids ) . " )";
                $message_data_results = $wpdb->get_results( $message_data_query, 'ARRAY_A' );
                foreach ( $message_data_results as $message_data_result ) {
                    $message_data[$message_data_result['post_id']] = maybe_unserialize( $message_data_result['meta_value'] );
                }
            } 
        }
        return $message_data;
    }

    function get_valid_messages( $message_ids = array(), $campaign_ids = array(), $preview_mode = false, $skip_others = false) {

        $valid_messages = $valid_campaigns = $message_campaign_map = array();
        
        $campaign_ids        = array_filter(array_unique($campaign_ids));
        $message_ids        = array_filter(array_unique($message_ids));

        if ( !empty( $campaign_ids ) ) {
            $valid_campaigns = $this->get_valid_campaigns( $campaign_ids, true );
        }
        // When skip_others is true, we won't load campaigns / messages from db
        if (!$skip_others && !$preview_mode) {
            $campaigns = $this->get_valid_campaigns();
            if (!empty($campaigns)) {
                foreach ($campaigns as $id => $campaign) {
                    if (!array_key_exists($id, $valid_campaigns)) {
                        $valid_campaigns[ $id ] = $campaign;
                    }
                }
            }
        }
        // Create a map to look up campaign id for a given message
        if( !empty( $valid_campaigns ) ) {
            foreach ($valid_campaigns as $id => $campaign) {
                if ($preview_mode) {
                    $campaign->messages = get_post_meta( $id, 'campaign_preview', true );
                }

                foreach( $campaign->messages as $msg) {
                    $message_ids[] = $msg['id'];
                    if (!array_key_exists( $msg['id'], $message_campaign_map)) {
                        $message_campaign_map[ $msg['id'] ] = $id;
                    }
                }
            }
        }

        // We don't display same message twice...
        $message_ids        = array_unique($message_ids);

        if( empty( $message_ids ) ) {
            return array();
        }
        $valid_messages     = $this->get_message_data( $message_ids, $preview_mode );

        foreach ($valid_messages as $id => $message_data) {
			// Remove message if message type is uninstalled
            $class_name = 'Icegram_Message_Type_' . str_replace(' ', '_', ucwords(str_replace('-', ' ', $message_data['type'])));
            if( !class_exists( $class_name ) ) {
                unset( $valid_messages[$id] );
                continue;
            }
            $message_data['delay_time']     = 0;
            $message_data['retargeting']    = '';
            $message_data['campaign_id']    = ($preview_mode) ? $_GET['campaign_preview_id'] : '';

            // Pull display time and retargeting rule from campaign if possible
            if (!empty($message_campaign_map[ $id ])) {
                $message_data['campaign_id'] = $message_campaign_map[ $id ];
                $campaign = $valid_campaigns[ $message_data['campaign_id'] ];
                if (!empty($campaign) && $campaign instanceof Icegram_Campaign) {
                    $message_meta_from_campaign = $campaign->get_message_meta_by_id( $id );
                    if (!empty($message_meta_from_campaign['time'])) {
                       $message_data['delay_time'] = $message_meta_from_campaign['time'];
                    }
                    $rule_value = $campaign->get_rule_value('retargeting');
                    $message_data['retargeting']   = !empty( $rule_value['retargeting'] ) ? $rule_value['retargeting'] : '';
                }
            }
            $valid_messages[$id] = $message_data;
        }

        $valid_messages = apply_filters( 'icegram_valid_messages', $valid_messages );        
        return $valid_messages;
    }

    function get_valid_campaigns( $campaign_ids = array(), $skip_page_check = false ) {
        global $wpdb;

        if ( empty( $campaign_ids ) ) {
            $sql = "SELECT pm.post_id 
                    FROM {$wpdb->prefix}posts AS p 
                    LEFT JOIN {$wpdb->prefix}postmeta AS pm ON ( pm.post_id = p.ID ) 
                    WHERE p.post_status = 'publish' ";
            // Filter handler within this file (and possibly others) will append to this SQL 
            // and provide arguments for wpdb->prepare if needed. 
            // First element in the array is SQL, remaining are values for placeholders in SQL
            $sql_params = apply_filters( 'icegram_get_valid_campaigns_sql', array($sql), array() );
            $campaign_ids = $wpdb->get_col( $wpdb->prepare( array_shift($sql_params), $sql_params ) );
        }
        $valid_campaigns = array();
        foreach ( (array) $campaign_ids as $campaign_id ) {
            $campaign = new Icegram_Campaign( $campaign_id );
            if ( $campaign->is_valid( array('skip_page_check' =>  $skip_page_check) ) ) {
                $valid_campaigns[$campaign_id] = $campaign;
            } else {
                // Campgain is invalid!
            }
        }
        return $valid_campaigns;
    }

    function append_to_valid_campaigns_sql( $sql_params = array(), $options = array() ) {

        // Page check conditions
        $pid = Icegram::get_current_page_id();
        $sql = " AND ( 
                pm.meta_key = 'icegram_campaign_target_rules' AND (
                ( pm.meta_value LIKE '%%%s%%' ) 
                OR ( pm.meta_value LIKE '%%%s%%' AND pm.meta_value LIKE '%%%s%%' AND pm.meta_value LIKE '%%%s%%' )
                ";
        $sql_params[] = 's:8:"sitewide";s:3:"yes";';
        $sql_params[] = 's:10:"other_page";s:3:"yes";';
        $sql_params[] = 's:7:"page_id";a:';
        $sql_params[] = serialize( (string) $pid );

        if (is_home() || is_front_page()) {
            $sql .= " OR ( pm.meta_value LIKE '%%%s%%' ) 
                    ";
            $sql_params[] = 's:8:"homepage";s:3:"yes";';
        }
        $sql .=" ) )"; 
        $sql_params[0] .= $sql;        

        //s:9:"logged_in";s:3:"all";

        return $sql_params;
    }

    // Include all classes required for Icegram plugin
    function include_classes() {

        $classes = glob( $this->plugin_path . '/classes/*.php' );
        foreach ( $classes as $file ) {
            // Files with 'admin' in their name are included only for admin section
            if ( is_file( $file ) && ( (strpos($file, '-admin') >= 0 && is_admin()) || (strpos($file, '-admin') === false) ) ) {
                include_once $file;
            } 
        }

        // Load built in message types
        $icegram_message_type_basedirs = glob( $this->plugin_path . '/message-types/*' );
        // Allow other plugins to add new message types
        $icegram_message_type_basedirs = apply_filters( 'icegram_message_type_basedirs',  $icegram_message_type_basedirs );
        // Set up different message type classes
        foreach ( $icegram_message_type_basedirs as $dir ) {
            $type = basename ( $dir );
            $class_file = $dir . "/main.php";
            if( is_file( $class_file ) ) {
                include_once( $class_file );
            }
            $class_name = 'Icegram_Message_Type_' . str_replace(' ', '_', ucwords(str_replace('-', ' ', $type)));
            if (class_exists($class_name)) {
                $this->message_type_objs[ $type ] = new $class_name();
            }
        }
        $this->message_types    = apply_filters( 'icegram_message_types', array() );
        
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
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'ig_campaign' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor' )
        );

        register_post_type( 'ig_campaign', $args );
    }

    // Register Message post type
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
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=ig_campaign',
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'ig_message' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' )
        );

        register_post_type( 'ig_message', $args );
    }

    function import( $data = array() ) {

        if ( empty( $data['campaigns'] ) && empty( $data['messages'] ) ) return;
       
        $default_theme = $default_type = '';
        $first_message_type = current( $this->message_types );

        if( is_array( $first_message_type ) ) {
            $default_type  = $first_message_type['type'];
            if( !empty( $first_message_type['themes'] ) ) {
                $default_theme = key( $first_message_type['themes'] );
            }
        }

        $new_campaign_ids = array();
        foreach ( (array) $data['campaigns'] as $campaign ) {

            $args = array( 
                        'post_content'   =>  ( !empty( $campaign['post_content'] ) ) ? esc_attr( $campaign['post_content'] ) : '',
                        'post_name'      =>  ( !empty( $campaign['post_title'] ) ) ? sanitize_title( $campaign['post_title'] ) : '',
                        'post_title'     =>  ( !empty( $campaign['post_title'] ) ) ? $campaign['post_title'] : '',
                        'post_status'    =>  ( !empty( $campaign['post_status'] ) ) ? $campaign['post_status'] : 'draft',
                        'post_type'      =>  'ig_campaign'
                     );

            $new_campaign_id    = wp_insert_post( $args );
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
                                'post_type'      =>  'ig_message'
                             );

                    $new_message_id = wp_insert_post( $args );
                    $new_message    = array(
                                        'id'    => $new_message_id,
                                        'time'  => ( !empty( $message['time'] ) ) ? $message['time'] : 0
                                    );
                    $messages[]     = $new_message;

                    unset( $message['post_content'] );
                    unset( $message['time'] );

                    $message['id']  = $new_message_id;

                    $defaults = array (
                                    'post_title'    => '',
                                    'type'          => $default_type,
                                    'theme'         => $default_theme,
                                    'animation'     => '',
                                    'headline'      => '',
                                    'label'         => '',
                                    'link'          => '',
                                    'icon'          => '',
                                    'message'       => '',
                                    'position'      => '',
                                    'text_color'    => '#000000',
                                    'bg_color'      => '#ffffff',
                                    'id'            => ''
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
                                                                'theme'                 => 'hello',
                                                                'headline'              => 'Get 2x more Contacts with Your Website',
                                                                'label'                 => 'Show Me How',
                                                                'link'                  => '',
                                                                'icon'                  => '',
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
                                                                'theme'                 => 'social',
                                                                'animation'             => 'slide',
                                                                'headline'              => '20% Off - for you',
                                                                'label'                 => '',
                                                                'link'                  => '',
                                                                'icon'                  => '',
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
                                                                'theme'                 => 'air-mail',
                                                                'headline'              => 'How this blog makes over $34,800 / month for FREE.',
                                                                'label'                 => 'FREE INSTANT ACCESS',
                                                                'link'                  => '',
                                                                'icon'                  => '',
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
                                                                'theme'                 => 'stand-out',
                                                                'animation'             => 'pop',
                                                                'headline'              => 'Exclusive Marketing Report',
                                                                'label'                 => '',
                                                                'link'                  => '',
                                                                'icon'                  => '',
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

        if( $post_type == 'ig_message' || $post_type == 'ig_campaign' ) {

            ?>
            <style>
                #preview-action { display:none; }
            </style>
            <?php

        }

    }

    function remove_row_actions( $actions, $post ) {

        if ( empty( $post->post_type ) || ( $post->post_type != 'ig_campaign' && $post->post_type != 'ig_message' ) ) return $actions;
        
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['view'] );
        
        return $actions;

    }

    function identify_current_page() {
        global $post, $wpdb;

        $obj = get_queried_object();
        if( !empty( $obj->has_archive ) ) {
            $id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='page'", $obj->has_archive ) );
        } elseif( is_object( $post ) && isset( $post->ID ) ) {            
            $id = $post->ID;
        }        
        $id = apply_filters('icegram_identify_current_page', $id );
        self::$current_page_id = $id;
    }

    static function get_current_page_id() {
        return self::$current_page_id;
    }

    function wpml_get_parent_id( $id ) {
        global $post;
        if (function_exists('icl_object_id') && function_exists('icl_get_default_language') ) {
            $id = icl_object_id($id, $post->post_type, true, icl_get_default_language() );
        }
        return $id;
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
    global $icegram;

    // i18n / l10n - load translations
    load_plugin_textdomain( 'icegram', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' ); 

    $icegram = new Icegram();
}

add_action( 'plugins_loaded', 'initialize_icegram' );
register_activation_hook( __FILE__, array( 'Icegram', 'install' ) );