<?php
/*
 * About Icegram
 */

if ( !defined( 'ABSPATH' ) ) exit;

// Actions for support
add_action( 'admin_footer', 'icegram_support_ticket_content' );

function icegram_support_ticket_content() {
    global $current_user, $pagenow, $typenow, $icegram;

    if ( $pagenow != 'edit.php' ) return;
    if ( $typenow != 'ig_campaign') return;
    if ( !( $current_user instanceof WP_User ) ) return;

    if( isset( $_POST['submit_query'] ) && $_POST['submit_query'] == "Send" && !empty($_POST['client_email'])){

        $additional_info = ( isset( $_POST['additional_information'] ) && !empty( $_POST['additional_information'] ) ) ? sanitize_text_field( $_POST['additional_information'] ) : '';
        $additional_info = str_replace( '###', '<br />', $additional_info );
        $additional_info = str_replace( array( '[', ']' ), '', $additional_info );

        $from = 'From: ';
        $from .= ( isset( $_POST['client_name'] ) && !empty( $_POST['client_name'] ) ) ? sanitize_text_field( $_POST['client_name'] ) : '';
        $from .= ' <' . sanitize_text_field( $_POST['client_email'] ) . '>' . "\r\n";
        $headers .= $from;
        $headers .= str_replace('From: ', 'Reply-To: ', $from);
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

        ob_start();
        echo $additional_info . '<br /><br />';
        echo nl2br($_POST['message']) ;
        $message = ob_get_clean();
        wp_mail( 'hello@icegram.com', $_POST['subject'], $message, $headers ); 
        header('Location: ' . $_SERVER['HTTP_REFERER'] );

    }
    ?>
    <div id="icegram_post_query_form" style="display: none;">
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

        ?>
        <form id="icegram_form_post_query" method="POST" action="" enctype="multipart/form-data">
            <script type="text/javascript">
                jQuery(function(){
                    jQuery('input#icegram_submit_query').click(function(e){
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

                        var subject = jQuery('table#icegram_post_query_table input#subject').val();
                        if ( subject == '' ) {
                            jQuery('input#subject').css('border-color', 'red');
                            error = true;
                        } else {
                            jQuery('input#subject').css('border-color', '');
                        }

                        var message = jQuery('table#icegram_post_query_table textarea#message').val();
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

                    jQuery(".icegram-contact-us a.thickbox").click( function(){ 
                        setTimeout(function() {
                            jQuery('#TB_ajaxWindowTitle').text('Send your query');
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
            <table id="icegram_post_query_table">
                <tr>
                    <td><label for="client_name"><?php _e('Name', 'icegram'); ?>*</label></td>
                    <td><input type="text" class="regular-text sm_text_field" id="client_name" name="client_name" value="<?php echo $customer_name; ?>" /></td>
                </tr>
                <tr>
                    <td><label for="client_email"><?php _e('E-mail', 'icegram'); ?>*</label></td>
                    <td><input type="email" class="regular-text sm_text_field" id="client_email" name="client_email" value="<?php echo $customer_email; ?>" /></td>
                </tr>
                <tr>
                    <td><label for="subject"><?php _e('Subject', 'icegram'); ?>*</label></td>
                    <td><input type="text" class="regular-text sm_text_field" id="subject" name="subject" value="<?php echo ( !empty( $subject ) ) ? $subject : ''; ?>" /></td>
                </tr>
                <tr>
                    <td style="vertical-align: top; padding-top: 12px;"><label for="message"><?php _e('Message', 'icegram'); ?>*</label></td>
                    <td><textarea id="message" name="message" rows="10" cols="60"><?php echo ( !empty( $message ) ) ? $message : ''; ?></textarea></td>
                </tr>
                <tr>
                    <td></td>
                    <td><label id="error_message" style="color: red;"></label></td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="submit" class="button" id="icegram_submit_query" name="submit_query" value="Send" /></td>
                </tr>
            </table>
            <input type="hidden" id="current_plugin" name="additional_info[current_plugin]" value="Icegram <?php echo $icegram->version; ?>" />
        </form>
    </div>
    <?php
}

if ( !wp_script_is( 'thickbox' ) ) {
    if ( !function_exists( 'add_thickbox' ) ) {
        require_once ABSPATH . 'wp-includes/general-template.php';
    }
    add_thickbox();
} 

?>
        <div class="wrap about-wrap icegram">             
            <h1><?php _e( "Welcome to Icegram", "icegram" ); ?></h1>
            <div class="about-text icegram-about-text">
                <?php _e( "Your sample campaign is ready. We've added a few messages for you to test.", "icegram" )?>
                <?php 
                    $sample_id = get_option('icegram_sample_data_imported');
                    $view_campaign = admin_url( 'post.php?post='.$sample_id[0].'&action=edit' );
                    $preview_url = home_url('?campaign_preview_id='.$sample_id[0]);
                    $assets_base = $this->plugin_url . '/assets/images/';
                ?>
                <p class="icegram-actions">
                    <a class="button button-primary button-large" href="<?php echo $view_campaign ; ?>"><?php _e( 'Edit & Publish it', 'icegram' ); ?></a>
                    <?php _e( "OR", "icegram")?>
                    <b><a href="<?php echo $preview_url; ?>" target="_blank"><?php _e( 'Preview Campaign', 'icegram' ); ?></a></b>
                </p>
            </div>
            
            <div class="icegram-badge">
               <?php printf(__( "Version: %s", "icegram"), $this->version ); ?>
            </div>
            <div class="icegram-support">
                    <?php _e( 'Questions? Need Help?', "icegram" ); ?>
                    <div id="icegram-contact-us" class="icegram-contact-us"><a class="thickbox"  href="<?php echo admin_url() . "#TB_inline?inlineId=icegram_post_query_form&post_type=ig_campaign" ?>"><?php _e("Contact Us", "icegram"); ?></a></div>
            </div>
            <hr>
            <div class="changelog">

                <?php do_action('icegram_about_changelog'); ?>

                <hr>

                <div class="about-text">
                <?php _e("Do read Icegram's core concepts below to understand how you can use Icegram to inspire, convert and engage your audience.", "icegram"); ?>
                </div>

                <div class="feature-section col three-col">
                        <div class="col-1">                                
                                <h2 class="icegram-dashicons dashicons-testimonial"><?php _e( "Messages", "icegram" ); ?></h2>
                                <p><?php _e("A 'Message' is a communication you want to deliver to your audience.","icegram"); ?></p>
                                <p><?php _e("And Icegram comes with not one, but four message types.","icegram"); ?></p>
                                <p><?php _e("Different message types look and behave differently, but they all have many common characteristics. For instance, most message types will allow you to set a headline, a body text, label for the ‘call to action’ button, a link for that button, theme and styling options, animation effect and position on screen where that message should show.","icegram"); ?></p>
                                <?php do_action('icegram_about_after_core_message_types_col1'); ?>
                        </div>
                        <div class="col-2">
                                <h4><?php _e("Action Bar", "icegram"); ?></h4>
                                <img src="<?php echo $assets_base; ?>/sketch-action-bar.png" width="180" height="145">
                                <p><?php _e("An action bar is a proven attention grabber. It shows up as a solid bar either at top or bottom. Use it for your most important messages or time sensitive announcements. Put longer content in it and it acts like a collapsible panel!", "icegram"); ?></p>
                                <h4><?php _e("Messenger", "icegram"); ?></h4>
                                <img src="<?php echo $assets_base; ?>/sketch-messenger.png" width="180" height="145">
                                <p><?php _e("A messenger is best used to invoke interest while your visitor is reading your content. Users perceive it as something new, important and urgent and are highly likely to click on it.", "icegram"); ?></p>
                                <?php do_action('icegram_about_after_core_message_types_col2'); ?>
                        </div>
                        <div class="col-3 last-feature">
                                <h4><?php _e("Toast Notification", "icegram"); ?></h4>
                                <img src="<?php echo $assets_base; ?>/sketch-toast-notification.png" width="180" height="145">
                                <p><?php _e("Want to alert your visitor about some news, an update from your blog, a social proof or an offer? Use Icegram’s unique toast notification, it will catch their attention, let them click on the message, and disappear after a while.", "icegram"); ?></p>
                                <h4><?php _e("Popup", "icegram"); ?></h4>
                                <img src="<?php echo $assets_base; ?>/sketch-popup.png" width="180" height="145">
                                <p><?php _e("Lightbox popup windows are most widely used for lead capture, promotions and additional content display. Ask visitors to sign up to your newsletter, or like you on social networks, or tell them about a special offer...", "icegram"); ?></p>
                                <?php do_action('icegram_about_after_core_message_types_col3'); ?>
                        </div>
                </div>                
                <hr>

                <?php do_action('icegram_about_after_core_message_types'); ?>
                
                <div class="feature-section col three-col">
                        <div class="col-1">                                
                                <h2 class="icegram-dashicons dashicons-megaphone"><?php _e("Campaigns", "icegram"); ?></h2>
                                <p><?php _e("Campaign = Messages + Rules", "icegram"); ?></p>
                                <p><?php _e("A campaign allows sequencing multiple messages and defining targeting rules. Create different campaigns for different marketing goals. Icegram supports showing multiple campaigns on any page.", "icegram"); ?></p>
								<p><?php _e("You can always preview your campaign to ensure campaign works the way you want, before making it live.", "icegram"); ?></p>
                                <?php do_action('icegram_about_after_core_campaigns_col1'); ?>
                        </div>
                        <div class="col-2">
                                <h4><?php _e("Multiple Messages & Sequencing", "icegram"); ?></h4>
                                <img src="<?php echo $assets_base; ?>/sketch-multiple-sequence.png" width="180" height="145">
                                <p><?php _e("Add one or as many messages to a campaign as you want. Also choose the number of seconds after which each message should show up. Showing multiple messages for same goal, but with slightly different content / presentation, greatly improves conversions.", "icegram"); ?></p>
                                <?php do_action('icegram_about_after_core_campaigns_col2'); ?>
                        </div>
                        <div class="col-3 last-feature">                                
                                <h4><?php _e("Targeting Rules", "icegram"); ?></h4>
                                <img src="<?php echo $assets_base; ?>/sketch-rules.png" width="180" height="145">
                                <p><?php _e("You can control who sees a campaign – and on what device, which pages does it show on, and what time period will it stay active for. You can run different campaigns with different rules to maximize engagement.", "icegram"); ?></p>
                                <?php do_action('icegram_about_after_core_campaigns_col3'); ?>
                        </div>
                </div>

                <?php do_action('icegram_about_after_core_campaigns'); ?>

                <hr>                
                <div class="feature-section col two-col">
                        <div class="col-1">
                            <h2 class="icegram-dashicons dashicons-editor-help"><?php _e("FAQ / Common Problems", "icegram"); ?></h2>

                                <h4><?php _e("Messages look broken / formatting is weird...", "icegram"); ?></h4>
                                <p><?php _e("This is most likely due to CSS conflicts with current theme. We suggest using simple formatting for messages. You can also write custom CSS in your theme to fix any problems.", "icegram"); ?></p>

                                <h4><?php _e("Extra Line Breaks / Paragraphs in messages...", "icegram"); ?></h4>
                                <p><?php _e("Go to HTML mode in content editor and pull your custom HTML code all together in one line. Don't leave blank lines between two tags. That should fix it.", "icegram"); ?></p>

                                <h4><?php _e("How do I add custom CSS for messages?", "icegram"); ?></h4>
                                <p><?php _e("You can use custom CSS/JS inline in your message HTML. You can also use your theme's custom JS / CSS feature to add your changes.", "icegram"); ?></p>

                                <h4><?php _e("Optin Forms / Mailing service integration...", "icegram"); ?></h4>
                                <p><?php _e("You can embed any optin / subscription form to your Icegram messages using 'Embed Form' button above text editor. Paste in form HTML code and let Icegram clean it up! You may even use a shortcode if you are using a WP plugin from your newsletter / lead capture service.", "icegram"); ?></p>

                                <h4><?php _e("How many messages should I show on a page?", "icegram"); ?></h4>
                                <p><?php _e("While Icegram provides you lots of different message types and ability to add multiple messages to a campaign, we discourage you to go overboard. We've observed two messages on a page work well, but YMMV!", "icegram"); ?></p>

                                <?php do_action('icegram_about_after_faq_col1'); ?>

                        </div>
                        <div class="col-2 last-feature">                                
                                <h4><?php _e("Preview does not work / not refreshing...", "icegram"); ?></h4>
                                <p><?php _e("Doing a browser refresh while previewing will not show your most recent changes. Click 'Preview' button to see a preview with your latest changes.", "icegram"); ?></p>

                                <h4><?php _e("Can I use shortcodes in a message?", "icegram"); ?></h4>
                                <p><?php _e("Yes! Messages support shortcodes. You may need to adjust CSS so the shortcode output looks good in your message.", "icegram"); ?></p>

                                <h4><?php _e("WPML / Multilingual usage...", "icegram"); ?></h4>
                                <p><?php _e("Go to <code>Messages</code> from Icegram menu. Edit a message and translate it like any other post. Icegram will show translated message where possible. Choose <code>All posts</code> under WPML Language setting - Blog Posts to display, to fall back to default language messages.", "icegram"); ?></p>

                                <?php do_action('icegram_about_after_faq_col2'); ?>

                                <h4><?php _e("I can't find a way to do X...", "icegram"); ?></h4>
                                <p><?php _e("Icegram is actively developed. If you can't find your favorite feature (or have a suggestion) contact us. We'd love to hear from you.", "icegram"); ?></p>

                                <h4><?php _e("I'm facing a problem and can't find a way out...", "icegram"); ?></h4>
                                <p><a class="thickbox"  href="<?php echo admin_url() . "#TB_inline?inlineId=icegram_post_query_form&post_type=ig_campaign" ?>"><?php _e("Contact Us", "icegram"); ?></a><?php _e(", provide as much detail of the problem as you can. We will try to solve the problem ASAP.", "icegram"); ?></p>

                        </div>
                </div>

                <?php do_action('icegram_about_after_faq'); ?>

            </div>            
        </div>