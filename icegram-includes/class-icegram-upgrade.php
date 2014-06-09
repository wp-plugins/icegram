<?php
class IceGram_Upgrade {

    var $base_name;
    var $check_update_timeout;
    var $last_checked;
    var $plugin_data;
    var $sku;
    var $license_key;
    var $download_url;
    var $installed_version;
    var $live_version;
    var $changelog;
    var $slug;
    var $name;
    var $documentation_link;
    var $prefix;
    var $text_domain;
    var $login_link;
    var $due_date;
    
    function __construct( $file, $sku, $prefix, $plugin_name, $text_domain, $documentation_link ) {
        
        $this->check_update_timeout = (24 * 60 * 60); // 24 hours
            
        if (! function_exists( 'get_plugin_data' )) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data( $file );
        $this->base_name = plugin_basename( $file );
        $this->slug = dirname( $this->base_name );
        $this->name = $plugin_name;
        $this->sku = $sku;
        $this->documentation_link = $documentation_link;
        $this->prefix = $prefix;
        $this->text_domain = $text_domain;
        
        add_site_option( $this->prefix.'_last_checked', '' );
        add_site_option( $this->prefix.'_download_url', '' );
        add_site_option( $this->prefix.'_installed_version', '' );
        add_site_option( $this->prefix.'_live_version', '' );
        
        if ( empty( $this->last_checked ) ) {
            $this->last_checked = (int)get_site_option( $this->prefix.'_last_checked' );
        }

        if (get_site_option( $this->prefix.'_installed_version' ) != $this->plugin_data ['Version']) {
            update_site_option( $this->prefix.'_installed_version', $this->plugin_data ['Version'] );
        }

        if ( ( get_site_option( $this->prefix.'_live_version' ) == '' ) || ( get_site_option( $this->prefix.'_live_version' ) < get_site_option( $this->prefix.'_installed_version' ) ) ) {
            update_site_option( $this->prefix.'_live_version', $this->plugin_data['Version'] );
        }

        // Actions for License Validation & Upgrade process
        add_action( 'admin_footer', array ($this, 'support_ticket_content' ) );
        add_action( "after_plugin_row_".$this->base_name, array ($this, 'update_row' ), 10, 2 );

        // add_filter( 'plugins_api', array( $this, 'overwrite_wp_plugin_api_for_plugin' ), 10, 3 );
        // add_filter( 'site_transient_update_plugins', array ($this, 'overwrite_site_transient' ), 10, 2 );

        add_filter( 'plugin_row_meta', array( $this, 'add_support_link' ), 10, 4 );

    }

    function check_for_updates() {
        
        $this->live_version = get_site_option( $this->prefix.'_live_version' );
        $this->installed_version = get_site_option( $this->prefix.'_installed_version' );
        
        if (version_compare( $this->installed_version, $this->live_version, '<=' )) {

            $result = wp_remote_post( 'http://www.icegram.com/wp-admin/admin-ajax.php?action=get_products_latest_version&uuid=' . urlencode( admin_url( '/' ) ) . '&requester=' . get_option('admin_email') );
            
            if (is_wp_error($result)) {
                return;
            }
            
            $response = json_decode( $result ['body'] );
            
            $live_version = $response->version;
            $download_url = $response->download_url;
            
            if ($this->live_version == $live_version || $response == 'false') {
                return;
            }
            
            update_site_option( $this->prefix.'_live_version', $live_version );
            update_site_option( $this->prefix.'_download_url', $download_url );

        }
    }

    function overwrite_site_transient($plugin_info, $force_check_updates = false) {

        if (empty( $plugin_info->checked ))
            return $plugin_info;

        $time_not_changed = isset( $this->last_checked ) && $this->check_update_timeout > ( time() - $this->last_checked );

        if ( $force_check_updates || !$time_not_changed ) {
            $this->check_for_updates();
            $this->last_checked = time();
            update_site_option( $this->prefix.'_last_checked', $this->last_checked );
        }

        $plugin_base_file = $this->base_name;
        $live_version = get_site_option( $this->prefix.'_live_version' );
        $installed_version = get_site_option( $this->prefix.'_installed_version' );

        if (version_compare( $live_version, $installed_version, '>' )) {
            $plugin_info->response [$plugin_base_file] = new stdClass();
            $plugin_info->response [$plugin_base_file]->slug = substr( $plugin_base_file, 0, strpos( $plugin_base_file, '/' ) );
            $plugin_info->response [$plugin_base_file]->new_version = $live_version;
            $plugin_info->response [$plugin_base_file]->url = 'http://www.icegram.com';
            $plugin_info->response [$plugin_base_file]->package = get_site_option( $this->prefix.'_download_url' );
        }

        return $plugin_info;
    }

    function overwrite_wp_plugin_api_for_plugin($api = false, $action = '', $args = '') {

        if ($args->slug != $this->slug)
            return $api;

        if ('plugin_information' == $action || false === $api || $_REQUEST ['plugin'] == $args->slug) {
            $api->name = $this->name;
            $api->version = get_site_option( $this->prefix.'_live_version' );
            $api->download_link = get_site_option( $this->prefix.'_download_url' );
        }

        return $api;
    }

    function update_row($file, $plugin_data) {
        $license_key = get_site_option( $this->prefix.'_license_key' );
        $valid_color = '#AAFFAA';
        $invalid_color = '#FFAAAA';
        $color = ($license_key != '') ? $valid_color : $invalid_color;
        ?>
            <style type="text/css">
                div#TB_window {
                    background: lightgrey;
                }
            </style>
            <script type="text/javascript">
                    jQuery(function(){
                        jQuery(document).ready(function(){
                            var loaded_url = jQuery('a.<?php echo $this->prefix; ?>_support_link').attr('href');
                            
                            if ( loaded_url != undefined && ( loaded_url.indexOf('width') == -1 || loaded_url.indexOf('height') == -1 ) ) {
                                var width = jQuery(window).width();
                                var H = jQuery(window).height();
                                var W = ( 720 < width ) ? 720 : width;
                                var adminbar_height = 0;

                                if ( jQuery('body.admin-bar').length )
                                    adminbar_height = 28;

                                jQuery('a.<?php echo $this->prefix; ?>_support_link').each(function(){
                                    var href = jQuery(this).attr('href');
                                    if ( ! href )
                                            return;
                                    href = href.replace(/&width=[0-9]+/g, '');
                                    href = href.replace(/&height=[0-9]+/g, '');
                                    jQuery(this).attr( 'href', href + '&width=' + ( W - 80 ) + '&height=' + ( H - 85 - adminbar_height ) );
                                });

                            }

                        });

                    });
            </script>
            <?php
    }

    function support_ticket_content() {
        global $current_user, $wpdb, $pagenow;

        if ( $pagenow != 'plugins.php' ) return;

        if ( !( $current_user instanceof WP_User ) ) return;

        if( isset( $_POST['icegram_submit_query'] ) && $_POST['icegram_submit_query'] == "Send" ){
            
            check_admin_referer( 'icegram-submit-query_' . $this->sku );

            $additional_info = ( isset( $_POST['additional_information'] ) && !empty( $_POST['additional_information'] ) ) ? sanitize_text_field( $_POST['additional_information'] ) : '';
            $additional_info = str_replace( '=====', '<br />', $additional_info );
            $additional_info = str_replace( array( '[', ']' ), '', $additional_info );

            $headers = 'From: ';
            $headers .= ( isset( $_POST['client_name'] ) && !empty( $_POST['client_name'] ) ) ? sanitize_text_field( $_POST['client_name'] ) : '';
            $headers .= ' <' . sanitize_text_field( $_POST['client_email'] ) . '>' . "\r\n";
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

            ob_start();
            echo $additional_info . '<br /><br />';
            echo nl2br($_POST['message']) ;
            $message = ob_get_clean();
            if ( empty( $_POST['name'] ) ) {
                wp_mail( 'hello@icegram.com', $_POST['subject'], $message, $headers );
                header('Location: ' . $_SERVER['HTTP_REFERER'] );
            }
            
        }
        
        ?>
        <div id="<?php echo $this->prefix; ?>_post_query_form" style="display: none;">
            <style>
                table#<?php echo $this->prefix; ?>_post_query_table {
                    padding: 5px;
                }
                table#<?php echo $this->prefix; ?>_post_query_table tr td {
                    padding: 5px;
                }
                input.<?php echo $this->sku; ?>_text_field {
                    padding: 5px;
                }
                label {
                    font-weight: bold;
                }
            </style>
            <?php

                if ( !wp_script_is('jquery') ) {
                    wp_enqueue_script('jquery');
                    wp_enqueue_style('jquery');
                }

                $first_name = get_user_meta($current_user->ID, 'first_name', true);
                $last_name = get_user_meta($current_user->ID, 'last_name', true);
                $name = $first_name . ' ' . $last_name;
                $customer_name = ( !empty( $name ) ) ? $name : $current_user->data->display_name;
                $customer_email = $current_user->data->user_email;
                $wp_version = ( is_multisite() ) ? 'WPMU ' . get_bloginfo('version') : 'WP ' . get_bloginfo('version');
                $admin_url = admin_url();
                $php_version = ( function_exists( 'phpversion' ) ) ? phpversion() : '';
                $wp_max_upload_size = size_format( wp_max_upload_size() );
                $server_max_upload_size = ini_get('upload_max_filesize');
                $server_post_max_size = ini_get('post_max_size');
                $wp_memory_limit = WP_MEMORY_LIMIT;
                $wp_debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) ? 'On' : 'Off';
                $this_plugins_version = $this->plugin_data['Name'] . ' ' . $this->plugin_data['Version'];
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $additional_information = "===== [Additional Information] =====
                                           [WP Version: $wp_version] =====
                                           [Admin URL: $admin_url] =====
                                           [PHP Version: $php_version] =====
                                           [WP Max Upload Size: $wp_max_upload_size] =====
                                           [Server Max Upload Size: $server_max_upload_size] =====
                                           [Server Post Max Size: $server_post_max_size] =====
                                           [WP Memory Limit: $wp_memory_limit] =====
                                           [WP Debug: $wp_debug] =====
                                           [" . $this->plugin_data['Name'] . " Version: " . $this->plugin_data['Version'] . "] =====
                                           [IP Address: $ip_address] =====
                                          ";

            ?>
            <form id="<?php echo $this->prefix; ?>_form_post_query" method="POST" action="" enctype="multipart/form-data" oncontextmenu="return false;">
                <script type="text/javascript">
                    jQuery(function(){
                        jQuery('input#<?php echo $this->prefix; ?>_submit_query').click(function(e){
                            var error = false;

                            var client_name = jQuery('input#client_name').val();
                            if ( client_name == '' ) {
                                jQuery('input#client_name').css('border-color', 'red');
                                error = true;
                            } else {
                                jQuery('input#client_name').css('border-color', '');
                            }

                            var client_email = jQuery('input#client_email').val();
                            if ( client_email == '' ) {
                                jQuery('input#client_email').css('border-color', 'red');
                                error = true;
                            } else {
                                jQuery('input#client_email').css('border-color', '');
                            }

                            var subject = jQuery('table#<?php echo $this->prefix; ?>_post_query_table input#subject').val();
                            if ( subject == '' ) {
                                jQuery('input#subject').css('border-color', 'red');
                                error = true;
                            } else {
                                jQuery('input#subject').css('border-color', '');
                            }

                            var message = jQuery('table#<?php echo $this->prefix; ?>_post_query_table textarea#message').val();
                            if ( message == '' ) {
                                jQuery('textarea#message').css('border-color', 'red');
                                error = true;
                            } else {
                                jQuery('textarea#message').css('border-color', '');
                            }

                            if ( error == true ) {
                                jQuery('label#error_message').text('* All fields are compulsory.');
                                e.preventDefault();
                            } else {
                                jQuery('label#error_message').text('');
                            }

                        });

                        jQuery("span.<?php echo $this->prefix; ?>_support a.thickbox").click( function(){                                    
                            setTimeout(function() {
                                jQuery('#TB_ajaxWindowTitle strong').text('Send your query');
                            }, 0 );
                        });

                        jQuery('div#TB_ajaxWindowTitle').each(function(){
                           var window_title = jQuery(this).text(); 
                           if ( window_title.indexOf('Send your query') != -1 ) {
                               jQuery(this).remove();
                           }
                        });

                        jQuery('input,textarea').keyup(function(){
                            var value = jQuery(this).val();
                            if ( value.length > 0 ) {
                                jQuery(this).css('border-color', '');
                                jQuery('label#error_message').text('');
                            }
                        });

                    });
                </script>
                <table id="<?php echo $this->prefix; ?>_post_query_table">
                    <tr>
                        <td><label for="client_name"><?php _e('Name', $this->text_domain); ?>*</label></td>
                        <td><input type="text" class="regular-text <?php echo $this->sku; ?>_text_field" id="client_name" name="client_name" value="<?php echo $customer_name; ?>" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/></td>
                    </tr>
                    <tr>
                        <td><label for="client_email"><?php _e('E-mail', $this->text_domain); ?>*</label></td>
                        <td><input type="email" class="regular-text <?php echo $this->sku; ?>_text_field" id="client_email" name="client_email" value="<?php echo $customer_email; ?>" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/></td>
                    </tr>
                    <tr>
                        <td><label for="current_plugin"><?php _e('Product', $this->text_domain); ?></label></td>
                        <td><input type="text" class="regular-text <?php echo $this->sku; ?>_text_field" id="current_plugin" name="current_plugin" value="<?php echo $this_plugins_version; ?>" readonly autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/><input type="text" name="name" value="" style="display: none;" /></td>
                    </tr>
                    <tr>
                        <td><label for="subject"><?php _e('Subject', $this->text_domain); ?>*</label></td>
                        <td><input type="text" class="regular-text <?php echo $this->sku; ?>_text_field" id="subject" name="subject" value="<?php echo ( !empty( $subject ) ) ? $subject : ''; ?>" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"/></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding-top: 12px;"><label for="message"><?php _e('Message', $this->text_domain); ?>*</label></td>
                        <td><textarea id="message" name="message" rows="10" cols="60" autocomplete="off" oncopy="return false;" onpaste="return false;" oncut="return false;"><?php echo ( !empty( $message ) ) ? $message : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><label id="error_message" style="color: red;"></label></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit" class="button" id="<?php echo $this->prefix; ?>_submit_query" name="icegram_submit_query" value="Send" ><?php _e( 'Send', $this->text_domain ) ?></button></td>
                    </tr>
                </table>
                <?php wp_nonce_field( 'icegram-submit-query_' . $this->sku ); ?>
                <input type="hidden" name="sku" value="<?php echo $this->sku; ?>" />
                <input type="hidden" class="hidden_field" name="wp_version" value="<?php echo $wp_version; ?>" />
                <input type="hidden" class="hidden_field" name="admin_url" value="<?php echo $admin_url; ?>" />
                <input type="hidden" class="hidden_field" name="php_version" value="<?php echo $php_version; ?>" />
                <input type="hidden" class="hidden_field" name="wp_max_upload_size" value="<?php echo $wp_max_upload_size; ?>" />
                <input type="hidden" class="hidden_field" name="server_max_upload_size" value="<?php echo $server_max_upload_size; ?>" />
                <input type="hidden" class="hidden_field" name="server_post_max_size" value="<?php echo $server_post_max_size; ?>" />
                <input type="hidden" class="hidden_field" name="wp_memory_limit" value="<?php echo $wp_memory_limit; ?>" />
                <input type="hidden" class="hidden_field" name="wp_debug" value="<?php echo $wp_debug; ?>" />
                <input type="hidden" class="hidden_field" name="current_plugin" value="<?php echo $this_plugins_version; ?>" />
                <input type="hidden" class="hidden_field" name="ip_address" value="<?php echo $ip_address; ?>" />
                <input type="hidden" class="hidden_field" name="additional_information" value='<?php echo $additional_information; ?>' />
            </form>
        </div>
        <?php
    }

    function add_support_link( $plugin_meta, $plugin_file, $plugin_data, $status ) {
        
        if ( $this->base_name == $plugin_file ) {
            $query_char = ( strpos( $_SERVER['REQUEST_URI'], '?' ) !== false ) ? '&' : '?';
            $plugin_meta[] = '<a href="#TB_inline'.$query_char.'inlineId='.$this->prefix.'_post_query_form" class="thickbox '.$this->prefix.'_support_link" title="' . __( 'Submit your query', $this->text_domain ) . '">' . __( 'Support', $this->text_domain ) . '</a>';
            if ( !empty( $this->documentation_link ) ) {
                $plugin_meta[] = '<a href="'.$this->documentation_link.'" target="_blank" title="' . __( 'Documentation', $this->text_domain ) . '">' . __( 'Docs', $this->text_domain ) . '</a>';
            }
        }
        
        return $plugin_meta;
        
    }
}