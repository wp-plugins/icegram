<?php
/*
 * Plugin Name: Icegram
 * Plugin URI: http://www.icegram.com/
 * Description: All in one solution to inspire, convert and engage your audiences. Action bars, Popup windows, Messengers, Toast notifications and more. Awesome themes and powerful rules.
 * Version: 1.8.8
 * Author: icegram
 * Author URI: http://www.icegram.com/
 *
 * Copyright (c) 2014-15 Icegram
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
    var $mode;

    public static $current_page_id;
    
    function __construct() {

        $this->version = "1.8.8";
        $this->shortcode_instances = array();
        $this->mode = 'local';
        $this->plugin_url   = untrailingslashit( plugins_url( '/', __FILE__ ) );
        $this->plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) );
        $this->include_classes();

        if( is_admin() && current_user_can( 'manage_options' ) ) {
            $ig_campaign_admin = Icegram_Campaign_Admin::getInstance();
            $ig_message_admin = Icegram_Message_Admin::getInstance();
            add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_styles_and_scripts' ) );
            add_action( 'admin_print_styles', array( &$this, 'remove_preview_button' ) );        
            add_filter( 'post_row_actions', array( &$this , 'remove_row_actions' ), 10, 2 );

            add_action( 'admin_menu', array( &$this, 'admin_menus') );
            add_action( 'admin_init', array( &$this, 'welcome' ) );
            add_action( 'admin_init', array( &$this, 'dismiss_admin_notice' ) );

            add_action( 'icegram_settings_after', array( &$this, 'klawoo_subscribe_form' ) ); 
            add_action( 'icegram_about_changelog', array( &$this, 'klawoo_subscribe_form' ) ); 
            add_action( 'icegram_settings_after', array( &$this, 'icegram_houskeeping' ) ); 
            add_action( 'admin_notices', array( &$this,'add_admin_notices'));
        } else {
            add_action( 'icegram_print_js_css_data', array( &$this, 'print_js_css_data' ), 10, 1); 
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
            add_action( 'wp_ajax_icegram_run_housekeeping', array( &$this, 'run_housekeeping' ) );
        }

    }
    public function add_admin_notices(){
        $active_plugins =  get_option( 'active_plugins', array() );
        ?>
        <style>
            p.icegram_notice {
                border-left: 4px solid hsl(94, 61%, 52%);
                padding: 10px 12px;
                background-color: hsl(0, 100%, 100%);
                -webkit-box-shadow: 0 1px 1px 0 hsla(0, 0%, 0%, 0.1);
                box-shadow: 0 1px 1px 0 hsla(0, 0%, 0%, 0.1);
                margin-right: 20px;
            }
            a.ig-admin-btn{
                margin-left: 10px;
                padding: 4px 8px;
                position: relative;
                text-decoration: none;
                border: none;
                -webkit-border-radius: 2px;
                border-radius: 2px;
                background: hsl(0, 0%, 88%);
                text-shadow: none;
                font-weight: 600;
                font-size: 13px;
            }
            a.ig-admin-btn-secondary{
                background: hsl(0, 0%, 98%);
                margin-left: 20px;
                font-weight: 400;
            }

            a.ig-admin-btn:hover{
                color: hsl(0, 100%, 100%);
                background-color: hsl(207, 8%, 23%);
            }
        </style>
        <?php
        // Admin notices for free bonuses!
        if(!get_option('dismiss_admin_notice_from_icegram')){
            $admin_notice_text = '';
            if(in_array('ig-analytics/ig-analytics.php' ,$active_plugins)  && !in_array('ig-themes-pack-1/ig-themes-pack-1.php' , $active_plugins)){
                // themes 
                $admin_notice_text = "Get <b>17 bonus themes</b> for Icegram. Free!! <a class='ig-admin-btn' href='http://www.icegram.com/addons/theme-pack-1/?utm_source=inapp&utm_campaign=freebonus&utm_medium=notices' target='_blank'>Yes, I want them!</a>";
            }else if(in_array('ig-themes-pack-1/ig-themes-pack-1.php', $active_plugins)  && !in_array('ig-analytics/ig-analytics.php', $active_plugins)){
                //analytics 
                $admin_notice_text = "Measure performance of your Icegram messages with the <b>free Analytics addon</b>. <a class='ig-admin-btn' href='http://www.icegram.com/addons/analytics/?utm_source=inapp&utm_campaign=freebonus&utm_medium=notices' target='_blank'>Cool, Let's begin</a>";
            }else if(!in_array('ig-analytics/ig-analytics.php', $active_plugins) && !in_array('ig-themes-pack-1/ig-themes-pack-1.php', $active_plugins)){
                // both
                $admin_notice_text = "Claim your Icegram bonuses today. <b>17 themes &amp; Analytics addon</b> for free! <a class='ig-admin-btn' href='http://www.icegram.com/product-category/addons/free/?utm_source=inapp&utm_campaign=freebonus&utm_medium=notices' target='_blank'>Yes, Let's begin</a>";
            }
            if ($admin_notice_text != '') {
                echo "<p class='icegram_notice'>".$admin_notice_text." <a class='ig-admin-btn ig-admin-btn-secondary' href='?dismiss_admin_notice=1'>No, I don't like free bonuses...</a></p>";
            }
        }
    }
    public function dismiss_admin_notice(){
        if(isset($_GET['dismiss_admin_notice']) && $_GET['dismiss_admin_notice'] == '1'){
            update_option('dismiss_admin_notice_from_icegram', true);
            wp_safe_redirect($_SERVER['HTTP_REFERER']);
            exit();
        }
    }
    public function klawoo_subscribe_form() {
        ?>
        <div class="wrap">
            <?php 
            if ( stripos(get_current_screen()->base, 'settings') !== false ) {
                echo "<h2>".__( 'Free Add-ons, Proven Marketing Tricks and  Updates', 'icegram' )."</h2>";
            }
            ?>
            <table class="form-table">
                 <tr>
                    <th scope="row"><?php _e( 'Get add-ons and tips...', 'icegram' ) ?></th>
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

    public function icegram_houskeeping(){
        ?>
        <div class="wrap">
            <?php 
            if ( stripos(get_current_screen()->base, 'settings') !== false ) {
            ?>
                <form name="icegram_housekeeping" action="#" method="POST" accept-charset="utf-8">
                        <h2><?php _e( 'Housekeeping', 'icegram' ) ?></h2>
                        <p class="ig_housekeeping">
                            <label for="icegram_remove_shortcodes">
                                <input type="checkbox" name="icegram_remove_shortcodes" value="yes" />
                                <?php _e( 'Remove all Icegram shortcodes', 'icegram' ); ?>                        
                            </label>
                            <br/><br/>
                            <label for="icegram_remove_all_data">
                                <input type="checkbox" name="icegram_remove_all_data" value="yes" />
                                <?php _e( 'Remove all Icegram campaigns and messages', 'icegram' ); ?>                        
                            </label>
                            <br/><br/>
                            <img alt="" src="<?php echo admin_url( 'images/wpspin_light.gif' ) ?>" class="ig_loader" style="vertical-align:middle;display:none" />
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Clean Up', 'icegram' ); ?>">
                            <div id="icegram_housekeeping_response"></div>
                        </p>
                </form>
          
        </div>
        <script type="text/javascript">
            jQuery(function () {
                jQuery("form[name=icegram_housekeeping]").submit(function (e) {
                    if(confirm("<?php _e( 'You won\'t be able to recover this data once you proceed. Do you really want to perform this action?', 'icegram' ); ?>") == true){
                        e.preventDefault();
                        jQuery('.ig_loader').show();
                        jQuery('#icegram_housekeeping_response').text("");                        
                        params = jQuery("form[name=icegram_housekeeping]").serializeArray();
                        params.push( {name: 'action', value: 'icegram_run_housekeeping' });

                        jQuery.ajax({
                            method: 'POST',
                            type: 'text',
                            url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                            data: params,
                            success: function(response) {                   
                                jQuery('.ig_loader').hide();
                                jQuery('#icegram_housekeeping_response').text("<?php _e('Done!', 'icegram'); ?>");
                            }
                        });
                    }
                });
            });
        </script>
    <?php
        }
    }
    public function run_housekeeping() {
        global $wpdb;
        $params = $_POST; 
        $_POST = array();

        if(!empty($params['icegram_remove_shortcodes']) && $params['icegram_remove_shortcodes'] == 'yes') {
            // first get all posts with [icegram] shortcode in them
            $sql = "SELECT * FROM `$wpdb->posts` WHERE  `post_content` LIKE  '%[icegram %]%' and `post_type` != 'revision' ";
            $posts = $wpdb->get_results($sql, OBJECT);
            if ( !empty($posts) && is_array($posts) ) {
                foreach ($posts as $post) {
                    $post_content = $post->post_content;
                    // remove shortcode with regexp now
                    $re = "/\\[icegram(.)*\\]/i"; 
                    $post_content = preg_replace($re, '', $post_content);
                    // save post content back
                    if ($post_content && $post_content != $post->post_content) {
                        wp_update_post( array ( 'ID'            => $post->ID,
                                                'post_content'  => $post_content
                                        ) );
                    }
                }
            }
        }

        if(!empty($params['icegram_remove_all_data']) && $params['icegram_remove_all_data'] == 'yes') {
            $posts = get_posts( array( 'post_type' => array( 'ig_campaign', 'ig_message' ) ) );
            if ( !empty($posts) && is_array($posts) ) {
                foreach ($posts as $post) {
                    wp_delete_post( $post->ID, true);
                }
            }
            do_action('icegram_remove_all_data');
        }
        $_POST = $params;
    }

    public function icegram_event_track() { 

        if( !empty( $_POST['event_data'] ) ) {

            foreach ( $_POST['event_data'] as $event ) {
                switch ($event['type']) {
                    case 'shown':
                        if (is_array($event['params']) && !empty($event['params']['message_id'])) {
                            $messages_shown[] = $event['params']['message_id'];
                            if(!empty($event['params']['expiry_time'])){
                                if($event['params']['expiry_time'] =='today'){
                                    $event['params']['expiry_time'] = strtotime('+1 day', mktime(0, 0, 0));
                                }else if($event['params']['expiry_time'] == 'current_session'){
                                    $event['params']['expiry_time'] = 0;
                                }else{
                                    $event['params']['expiry_time'] = strtotime($event['params']['expiry_time']);
                                }
                                setcookie('icegram_messages_shown_'.$event['params']['message_id'],true , $event['params']['expiry_time'] , '/');    
                            }
                        }
                        break;
                    case 'clicked':
                    if (is_array($event['params']) && !empty($event['params']['message_id'])) {
                        $messages_clicked[] = $event['params']['message_id'];
                        if(!empty($event['params']['expiry_time_clicked'])){
                            if($event['params']['expiry_time_clicked'] =='today'){
                                $event['params']['expiry_time_clicked'] = strtotime('+1 day', mktime(0, 0, 0));
                            }else if($event['params']['expiry_time_clicked'] == 'current_session'){
                                $event['params']['expiry_time_clicked'] = 0;
                            }else{
                                $event['params']['expiry_time_clicked'] = strtotime($event['params']['expiry_time_clicked']);
                            }
                           setcookie('icegram_messages_clicked_'.$event['params']['message_id'],true , $event['params']['expiry_time_clicked'] , '/' );    
                        }
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

        $messages_to_show_ids = array();
        foreach ( $messages as $key => $message_data ) {

            if( !is_array( $message_data ) || empty( $message_data ) ) {
                continue;
            }
                
                
            // Don't show a seen message again - if needed
            if( !empty( $message_data['id'] ) &&
                empty( $_GET['campaign_preview_id'] ) &&
                !empty($_COOKIE['icegram_messages_shown_'.$message_data['id']])&&
                !empty( $message_data['retargeting'] ) &&
                $message_data['retargeting'] == 'yes' 
            ) {
                unset( $messages[$key] );
                continue;
            }
            if( !empty( $message_data['id'] ) &&
                empty( $_GET['campaign_preview_id'] ) &&
                !empty($_COOKIE['icegram_messages_clicked_'.$message_data['id']])  &&
                !empty( $message_data['retargeting_clicked'] ) &&
                $message_data['retargeting_clicked'] == 'yes' 
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
            

            /*
            // Our own implementation so WP does not mess with script, style and pre tags
            add_filter('the_content', array( $this, 'before_wpautop' ) , 9);
            add_filter('the_content', array( $this, 'after_wpautop' ) , 11);
            $messages[$key]['message'] = apply_filters( 'the_content', $message_data['message'] );
            remove_filter('the_content', array( $this, 'before_wpautop' ) , 9);
            remove_filter('the_content', array( $this, 'after_wpautop' ) , 11);
            */
            // Redo the_content functionality to avoid other plugins adding extraneous code to messages
            $this->process_message_body($messages[$key]);
        }

        if( empty( $messages ) )
            return;
        
        $icegram_default = apply_filters( 'icegram_branding_data', 
                                            array ( 'icon'   => $this->plugin_url . '/assets/images/icegram-logo-branding-64-grey.png',
                                                    'powered_by_logo'       => $this->plugin_url . '/assets/images/icegram-logo-branding-64-grey.png',
                                                    'powered_by_text'       => __( 'Powered by Icegram', 'icegram' )
                                                    ) );
        $messages       = apply_filters( 'icegram_messages_to_show', $messages );
        $icegram_data   = apply_filters( 'icegram_data', array ( 'messages'       => array_values( $messages ),
                           'ajax_url'       => admin_url( 'admin-ajax.php' ),
                           'preview_id'     => !empty( $_GET['campaign_preview_id'] ) ? $_GET['campaign_preview_id'] : '',
                           'defaults'       => $icegram_default
                        ));
        
        if (empty($icegram_data['preview_id'])) {
            unset($icegram_data['preview_id']);
        }
        
        do_action('icegram_print_js_css_data', $icegram_data);
        
    }

    function print_js_css_data( $icegram_data ) {

        $types_shown    = array(); 
        foreach ($icegram_data['messages'] as $key => $message_data) {
            $types_shown[] = $message_data['type'];
        }
        $types_shown = array_unique($types_shown);

        wp_register_script( 'icegram_js', $this->plugin_url . '/assets/js/icegram.js', array ( 'jquery' ), $this->version, true);
        wp_enqueue_style( 'icegram_css', $this->plugin_url . '/assets/css/frontend.css', array(), $this->version );
     
        // Load theme CSS
        foreach ($icegram_data['messages'] as $key => $message) {
            $ver = ( !empty($this->message_types[$message['type']]['version'])) ? $this->message_types[$message['type']]['version'] : $this->version;
            if (!empty( $this->message_types[ $message['type'] ]['themes'][ $message['theme'] ]) ) {
                $theme = $this->message_types[ $message['type'] ]['themes'][ $message['theme'] ];
                wp_enqueue_style( 'icegram_css_'.$message['type'].'_'.$message['theme'], $theme['baseurl'] .$message['theme'].'.css'  ,array(), $ver);
            }else{
                $theme_default = $this->message_types[ $message['type']] ['settings']['theme']['default'];
                $theme = $this->message_types[ $message['type'] ]['themes'][ $theme_default];
                wp_enqueue_style( 'icegram_css_'.$message['type'].'_'.$theme_default, $theme['baseurl'] .$theme_default.'.css'  ,array(), $ver);
                $icegram_data['messages'][$key]['theme'] = $theme_default;
            }
        }

        if( !wp_script_is( 'icegram_js' ) ) {
            wp_enqueue_script( 'icegram_js' );
            wp_localize_script( 'icegram_js', 'icegram_data', $icegram_data );
        }
        
        // Load JS and default CSS
        if (in_array('popup', $types_shown)) {
            wp_register_script( 'magnific_popup_js', $this->plugin_url . '/assets/js/magnific-popup.js', array ( 'jquery' ), $this->version, true);
            if( !wp_script_is( 'magnific_popup_js' ) ) {
                wp_enqueue_script( 'magnific_popup_js' );
            }
            wp_enqueue_style( 'magnific_popup_css', $this->plugin_url . '/assets/css/magnific-popup.css', array(), $this->version );
        }
        
        foreach ($types_shown as $message_type) {
            $ver = ( !empty($this->message_types[$message_type]['version'])) ? $this->message_types[$message_type]['version'] : $this->version;
            wp_register_script( 'icegram_message_type_'.$message_type, $this->message_types[$message_type]['baseurl'] . "main.js" , array ( 'icegram_js' ), $ver, true );
            wp_enqueue_script( 'icegram_message_type_'.$message_type );  
            wp_enqueue_style( 'icegram_css_'.$message_type, $this->message_types[$message_type]['baseurl'] . 'default.css', array(), $ver );
        }

    }
    
    // Process
    function process_message_body(&$message_data){
        $content = $message_data['message'];
        $content = convert_chars( convert_smilies( wptexturize( $content ) ) );
        if(isset($GLOBALS['wp_embed'])) {
            $content = $GLOBALS['wp_embed']->autoembed($content);
        }
        $content = $this->after_wpautop( wpautop( $this->before_wpautop( $content ) ) );
        $content = do_shortcode( shortcode_unautop( $content ) );
        $message_data['message'] = $content;
        //do_shortcode in headline
        $message_data['headline'] = do_shortcode( shortcode_unautop( $message_data['headline'] ) );
    }

    function enqueue_admin_styles_and_scripts() {
        
        $screen = get_current_screen();   
        if ( !in_array( $screen->id, array( 'ig_campaign', 'ig_message' ), true ) ) return;

        // Register scripts
        wp_register_script( 'icegram_writepanel', $this->plugin_url . '/assets/js/admin.js' , array ( 'jquery', 'wp-color-picker' ), $this->version );
        wp_register_script( 'icegram_chosen', $this->plugin_url . '/assets/js/chosen.jquery.min.js' , array ( 'jquery' ), $this->version );
        wp_register_script( 'icegram_ajax-chosen', $this->plugin_url . '/assets/js/ajax-chosen.jquery.min.js' , array ( 'icegram_chosen' ), $this->version );
        wp_register_script( 'icegram_tiptip', $this->plugin_url . '/assets/js/jquery.tipTip.min.js' , array ( 'jquery' ), $this->version );
        
        wp_enqueue_script( 'icegram_writepanel' );
        wp_enqueue_script( 'icegram_ajax-chosen' );
        wp_enqueue_script( 'icegram_tiptip' );
        wp_register_script( 'magnific_popup_js', $this->plugin_url . '/assets/js/magnific-popup.js', array ( 'jquery' ), $this->version, true);
        if( !wp_script_is( 'magnific_popup_js' ) ) {
            wp_enqueue_script( 'magnific_popup_js' );
        }
        
        $icegram_writepanel_params  = array ( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'search_message_nonce' => wp_create_nonce( "search-messages" ) );
        $this->available_headlines  = apply_filters( 'icegram_available_headlines', array() );
        $icegram_writepanel_params  = array_merge( $icegram_writepanel_params, array( 'available_headlines' => $this->available_headlines ) );
        
        wp_localize_script( 'icegram_writepanel', 'icegram_writepanel_params', $icegram_writepanel_params );
        
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'icegram_admin_styles', $this->plugin_url . '/assets/css/admin.css', array(), $this->version  );
        wp_enqueue_style( 'icegram_jquery-ui-style', $this->plugin_url . '/assets/css/jquery-ui.min.css', array(), $this->version );
        wp_enqueue_style( 'icegram_chosen_styles', $this->plugin_url . '/assets/css/chosen.min.css', array(), $this->version );
        wp_enqueue_style( 'magnific_popup_css', $this->plugin_url . '/assets/css/magnific-popup.css', array(), $this->version );

        if ( !wp_script_is( 'jquery-ui-datepicker' ) ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

    }

    public static function get_platform() {
        $mobile_detect = new Ig_Mobile_Detect();
        $mobile_detect->setUserAgent();
        if($mobile_detect->isMobile()){
            return ($mobile_detect->isTablet()) ? 'tablet' : 'mobile';
        }else if($mobile_detect->isTablet()){
            return 'tablet';
        }
        return 'laptop';
    }

    function get_message_data( $message_ids = array(), $preview = false ) {
        global $wpdb;

        $message_data = array();
        $original_message_id_map = array();
        $meta_key = $preview ? 'icegram_message_preview_data' : 'icegram_message_data';
        $message_data_query = "SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '$meta_key'";
        if ( !empty( $message_ids ) && is_array( $message_ids ) ) {
            // For WPML compatibility
            if ( function_exists('icl_object_id') ) {
                $wpml_settings = get_option('icl_sitepress_settings');
                $original_if_missing = (is_array($wpml_settings) && array_key_exists('show_untranslated_blog_posts', $wpml_settings) && !empty($wpml_settings['show_untranslated_blog_posts']) ) ? true : false;
                foreach ($message_ids as $i=>$id ) {
                    $translated = icl_object_id( $id, 'ig_message', $original_if_missing );
                    $message_ids[ $i ] = $translated;
                    $original_message_id_map[ $translated ] = $id;
                }              
            }
            $message_ids  = array_filter(array_unique($message_ids));
            if ( !empty( $message_ids ) ) {
                $message_data_query .= " AND post_id IN ( " . implode( ',', $message_ids ) . " )";
                $message_data_results = $wpdb->get_results( $message_data_query, 'ARRAY_A' );
                foreach ( $message_data_results as $message_data_result ) {
                    $data = maybe_unserialize( $message_data_result['meta_value'] );
                    if (!empty($data)) {
                        $message_data[$message_data_result['post_id']] = $data;
                        // For WPML compatibility
                        if (!empty( $original_message_id_map[ $message_data_result['post_id'] ])) {
                               $message_data[$message_data_result['post_id']]['original_message_id'] = $original_message_id_map[ $message_data_result['post_id'] ]; 
                        }
                    }
                }
            } 
        }
        
        return $message_data;
    }

    function get_valid_messages( $message_ids = array(), $campaign_ids = array(), $preview_mode = false, $skip_others = false) {
        
        list($message_ids, $campaign_ids, $preview_mode, $skip_others) = apply_filters('icegram_get_valid_messages_params', array( $message_ids, $campaign_ids, $preview_mode, $skip_others));

        $valid_messages = $valid_campaigns = $message_campaign_map = array();
        
        $campaign_ids        = array_filter(array_unique( (array) $campaign_ids));
        $message_ids        = array_filter(array_unique( (array) $message_ids));

        if ( !empty( $campaign_ids ) ) {
            $valid_campaigns = $this->get_valid_campaigns( $campaign_ids, true ,true);
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
                if( !empty( $campaign->messages ) ) {
                    foreach( $campaign->messages as $msg) {
                        $message_ids[] = $msg['id'];
                        if (!array_key_exists( $msg['id'], $message_campaign_map)) {
                            $message_campaign_map[ $msg['id'] ] = $id;
                        }
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
            // Remove message if required fields are missing
            if (empty($message_data) || empty($message_data['type'])) {
                unset( $valid_messages[$id] );
                continue;
            }
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
            $message_id = (!empty($message_data['original_message_id'])) ? $message_data['original_message_id'] : $id;
            if (!empty($message_campaign_map[ $message_id ])) {
                //modify campaign id 
                $message_data['campaign_id'] = apply_filters('modify_campaing_id'  , $message_campaign_map[ $message_id ] , $message_id) ;
                $campaign = $valid_campaigns[ floor($message_data['campaign_id']) ];
                if (!empty($campaign) && $campaign instanceof Icegram_Campaign) {
                    $message_meta_from_campaign = $campaign->get_message_meta_by_id( $message_id );
                    if (!empty($message_meta_from_campaign['time'])) {
                       $message_data['delay_time'] = $message_meta_from_campaign['time'];
                    }
                    $rule_value = $campaign->get_rule_value('retargeting');
                    $message_data['retargeting']   = !empty( $rule_value['retargeting'] ) ? $rule_value['retargeting'] : '';
                    $message_data['expiry_time']   = !empty( $rule_value['retargeting'] ) ? $rule_value['expiry_time'] : '';
                    $rule_value_retargeting_clicked = $campaign->get_rule_value('retargeting_clicked');
                    $message_data['retargeting_clicked']   = !empty( $rule_value_retargeting_clicked['retargeting_clicked'] ) ? $rule_value_retargeting_clicked['retargeting_clicked'] : '';
                    $message_data['expiry_time_clicked']   = !empty( $rule_value_retargeting_clicked['retargeting_clicked'] ) ? $rule_value_retargeting_clicked['expiry_time_clicked'] : '';
                    
                }
            }
            $valid_messages[$id] = $message_data;
        }
        $valid_messages = apply_filters( 'icegram_valid_messages', $valid_messages ); 
        return $valid_messages;
    }

    function get_valid_campaigns( $campaign_ids = array(), $skip_page_check = false ,$preview_mode = false) {
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
            if ( $preview_mode || $campaign->is_valid( array('skip_page_check' =>  $skip_page_check) ) ) {
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
        //local url
        $sql .= " OR ( pm.meta_value LIKE '%%%s%%' )";
        $sql_params[] = 's:9:"local_url";s:3:"yes";';
       
        if (is_home() || is_front_page()) {
            $sql .= " OR ( pm.meta_value LIKE '%%%s%%' )";
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
                <style type="text/css">
                    #message.updated.below-h2{ display: none; }
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
        $id = 0;
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
    static function get_current_page_url() {
        $pageURL = 'http';
        if( isset($_SERVER["HTTPS"]) ) {
            if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;
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

    static function duplicate( $original_id ){
        // Get access to the database
        global $wpdb;
        // Get the post as an array
        $duplicate = get_post( $original_id, 'ARRAY_A' );
        // Modify some of the elements
        $duplicate['post_title'] = $duplicate['post_title'].' '.__('Copy', 'icegram');
        $duplicate['post_status'] = 'draft';
        // Set the post date
        $timestamp = current_time('timestamp',0);
        
        $duplicate['post_date'] = date('Y-m-d H:i:s', $timestamp);

        // Remove some of the keys
        unset( $duplicate['ID'] );
        unset( $duplicate['guid'] );
        unset( $duplicate['comment_count'] );

        // Insert the post into the database
        $duplicate_id = wp_insert_post( $duplicate );
        
        // Duplicate all taxonomies/terms
        $taxonomies = get_object_taxonomies( $duplicate['post_type'] );
        foreach( $taxonomies as $taxonomy ) {
            $terms = wp_get_post_terms( $original_id, $taxonomy, array('fields' => 'names') );
            wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
        }

        // Duplicate all custom fields
        $custom_fields = get_post_custom( $original_id );
        foreach ( $custom_fields as $key => $value ) {
            add_post_meta( $duplicate_id, $key, maybe_unserialize($value[0]) );
        }
        $location = admin_url( 'post.php?post='.$duplicate_id.'&action=edit');
        header('Location:'.$location);
        exit;
    }

}

function initialize_icegram() {
    global $icegram;

    // i18n / l10n - load translations
    load_plugin_textdomain( 'icegram', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' ); 

    $icegram = new Icegram();
    do_action('icegram_loaded');
}

add_action( 'plugins_loaded', 'initialize_icegram' );
register_activation_hook( __FILE__, array( 'Icegram', 'install' ) );