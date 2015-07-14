<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
* Icegram Campaign Admin class
*/
if ( !class_exists( 'Icegram_Campaign_Admin' ) ) {
	class Icegram_Campaign_Admin {

		var $default_target_rules;
		var $site_url;
		function __construct() {

			add_action( 'add_meta_boxes', array( &$this, 'add_campaigns_metaboxes' ), 0 );
			add_action( 'save_post', array( &$this, 'save_campaign_settings' ), 10, 2 );
			add_action( 'wp_ajax_icegram_json_search_messages', array( &$this, 'icegram_json_search_messages' ) );
			add_action( 'wp_ajax_get_message_action_row', array( &$this, 'get_message_action_row' ) );		
		    // add_filter( 'wp_default_editor', create_function('', 'return "html";') );
	        add_action( 'wp_ajax_save_campaign_preview', array( &$this, 'save_campaign_preview' ) );
	        add_action( 'icegram_campaign_target_rules', array( &$this, 'icegram_add_campaign_target_rules' ), 10, 2 );
	        add_filter('icegram_campaign_messages' ,array( &$this, 'get_icegram_campaign_messages' ) ,10,2 );
	       	//duplicate campaign
	        add_filter( 'post_row_actions', array(&$this , 'add_campaign_action'), 10, 2 );
	        add_action('admin_init', array(&$this ,'duplicate_campaign') ,10, 1);
	        $this->site_url = home_url().'/';
			
			$this->default_target_rules = apply_filters( 'icegram_campaign_default_rules',
														array ( 'homepage' 	=> 'yes',
															    'when' 		=> 'always',
															    'mobile' 	=> 'yes',
															    'tablet' 	=> 'yes',
															    'laptop' 	=> 'yes',
															    'logged_in' => 'all'									    
														        )
														);

		}
		public static  function getInstance(){
		   static $ig_campaign_admin = null;
	        if (null === $ig_campaign_admin) {
	            $ig_campaign_admin = new Icegram_Campaign_Admin();
	        }
	        return $ig_campaign_admin;
		}

		// Initialize campaign metabox
		function add_campaigns_metaboxes() {
			$meta_box_title = __( 'Message', 'icegram' );
			add_meta_box( 'campaign_data', $meta_box_title, array( &$this, 'campaign_data_content' ), 'ig_campaign', 'normal', 'default' );
			add_meta_box( 'campaign_target_rules', __( 'Targeting Rules', 'icegram' ), array( &$this, 'campaign_target_rules_content' ), 'ig_campaign', 'normal' );
		}

		// Display list of messages of campaign
		function campaign_data_content() {
			global $post, $icegram;
			$ig_message_admin = Icegram_Message_Admin::getInstance();

			$campaign_box =  '<select id="icegram_messages" name="icegram_messages[]" class="ajax_chosen_select_messages" data-placeholder="' . __( 'Search to add / Create new&hellip;', 'icegram' ) . '">';
			$campaign_box .= '<option value=""></option>';
			foreach ( $icegram->message_types as $message ) {
				$campaign_box .= '<option value="'.$message['type'].'">'.__( 'Create new', 'icegram' ).' '.$message['name'].' ...</option>';
			}
			$campaign_box .= '</select>';
			$campaign_box .= '<div class="button button-primary campaign_preview">' . __( 'Preview', 'icegram' ) . '</div>';

			$title = '<label class="options_header" for="icegram_messages"><strong>' . __( 'Message', 'icegram' ) . '</strong></label>';
			?>
			<div class="campaign_box"><?php echo $title; ?> &mdash; <?php echo $campaign_box; ?></div>
			<div style="margin: 8px -12px 12px -12px">
			<hr/>
			</div>
			<div class="campaign_target_rules_panel">
				<div class="options_group">
					<div class="messages-list">
						<table class="messages_list_table">
							<?php
							$this->message_list_table_header();
							?>
							<tbody>
								<?php 
								    $messages = array();
									$messages = apply_filters('icegram_campaign_messages' , $messages ,$post->ID);
									$icegram_message_meta_key = apply_filters('icegram_message_meta_key' , 'messages');
									if ( !empty( $messages ) ) {
										foreach ( $messages as $row => $message ) {
											$message_title = get_the_title( $message['id'] );
											$message_data = get_post_meta( $message['id'], 'icegram_message_data', true );
											$message_type = ( !empty( $message_data['type'] ) ) ? $message_data['type'] : '';
											if ( empty( $icegram->message_types[ $message_type ] ) ) continue;
											?>
											<tr class="form-field message-row" value="<?php echo $message['id']; ?>">
												<td class="message_header">
													<label class="message_header_label <?php echo "ig_".$message_data['type']; ?>"><?php echo $icegram->message_types[ $message_data['type'] ]['name']; ?></label>
												</td>
												<td class="message_title">
													<div class="message-title-text"><?php echo $message_title; ?></div>
													<input type="text" class="message-title-input" name="message_data[<?php echo $message['id']; ?>][post_title]" value="<?php echo $message_title; ?>" placeholder="<?php echo __( 'Give this message a name for your own reference', 'icegram' ); ?>" style="display: none;">
												</td>
												<td class="message_seconds">
													<input type="hidden" name="<?php echo $icegram_message_meta_key .'['.$row; ?>][id]" value="<?php echo $message['id']; ?>" />
													<input type="number" class="seconds-text" name="<?php echo $icegram_message_meta_key .'['.$row; ?>][time]" min="-1" value="<?php echo ( !empty( $message['time'] ) ) ? $message['time'] : 0; ?>" size="3" />
													<?php _e( ' sec', 'icegram' )?>
												</td>
												<td class="action_links">
													<span class="actions message_edit" title="<?php _e( 'Edit Message', 'icegram' ); ?>" ></span> 
													<span class="actions message_delete" title="<?php _e( 'Remove from Campaign', 'icegram' ); ?>" ></span>
												</td>
											</tr>
											<tr id="message_row_<?php echo $message['id']; ?>" class="message-edit-row" style="display: none;">
												<td colspan="4">
												<?php 
													$ig_message_admin->message_form_fields( '', array( 'message_id' => $message['id'] ) );
												?>
												</td>
											</tr>
											<?php
										}
									}
								?>
							</tbody>
						</table>
						<div class="empty_campaign">
							<?php
							echo __( 'No messages yet. Use search / create bar above to add messages to this campaign.', 'icegram' );
							?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		function get_icegram_campaign_messages($messages , $campaign_id){
			$messages = get_post_meta($campaign_id , 'messages' ,true);
			return $messages;
		}
		// Campaign targeting rules metabox
		function campaign_target_rules_content() {
			global $post;
			
			wp_nonce_field( 'icegram_campaign_save_data', 'icegram_campaign_meta_nonce' );
			$campaign_target_rules = get_post_meta( $post->ID, 'icegram_campaign_target_rules', true );

			if( empty( $campaign_target_rules ) ) {
				$campaign_target_rules = $this->default_target_rules;
			}
			
			?>
			<span class="target_rules_desc"> <em><?php echo 'Messages in this campaign will be shown when all these rules match...' ?></em></span>
			<div class="campaign_target_rules_panel">						
				<?php do_action( 'icegram_campaign_target_rules', $post->ID, $campaign_target_rules ); ?>
			</div>
			<?php
		}

		// Display setting fields for campaign targeting rules
		function icegram_add_campaign_target_rules( $campaign_id, $campaign_target_rules  ) {
			global $wp_roles;
			?>
			<div class="options_group" id="campaign_target_rules_where">
				<p class="form-field">
					<label class="options_header"><?php _e( 'Where?', 'icegram' ); ?></label>
					<label for="where_sitewide">
						<input type="checkbox" name="campaign_target_rules[sitewide]" id="where_sitewide" value="yes" <?php ( !empty( $campaign_target_rules['sitewide'] ) ) ? checked( $campaign_target_rules['sitewide'], 'yes' ) : ''; ?> />
						<?php _e( 'Sitewide', 'icegram' ); ?>
						<span class="campaign_shortcode light">
							<?php echo sprintf(__( 'Additionally you can insert <code>[%s]</code> wherever you want to run this campaign.', 'icegram' ), 'icegram campaigns="' .$campaign_id . '"' ); ?>
						</span>
					</label>
				</p>
				<p class="form-field" <?php echo ( !empty( $campaign_target_rules['sitewide'] ) && $campaign_target_rules['sitewide'] == 'yes' ) ? '' : 'style="display: none;"'; ?>>
					<label class="options_header"></label>
					<?php 
						echo '<select name="exclude_page_id[]" id="exclude_page_id" data-placeholder="' . __( 'Select pages to exclude&hellip;', 'icegram' ) .  '" style="min-width:300px;" class="icegram_chosen_page" multiple>';
						foreach ( get_pages() as $page ) {
							echo '<option value="' . $page->ID . '"';
							if( !empty( $campaign_target_rules['exclude_page_id'] ) ) {
								echo selected( in_array( $page->ID, $campaign_target_rules['exclude_page_id'] ) );
							}
							echo '>' . $page->post_title . '</option>';
						}
						echo '</select>';
					?>
				</p>
				<p class="form-field">
					<label class="options_header">&nbsp;</label>
					<label for="where_homepage">
						<input type="checkbox" name="campaign_target_rules[homepage]" id="where_homepage" value="yes" <?php ( !empty( $campaign_target_rules['homepage'] ) ) ? checked( $campaign_target_rules['homepage'], 'yes' ) : ''; ?> />
						<?php _e( 'Homepage', 'icegram' ); ?>
					</label>
				</p>
				<p class="form-field">
					<label class="options_header">&nbsp;</label>
					<label for="where_other_page">
						<input type="checkbox" name="campaign_target_rules[other_page]" id="where_other_page" value="yes" <?php ( !empty( $campaign_target_rules['other_page'] ) ) ? checked( $campaign_target_rules['other_page'], 'yes' ) : ''; ?> />
						<?php _e( 'Selected pages', 'icegram' ); ?>
					</label>
				</p>
				<p class="form-field" <?php echo ( !empty( $campaign_target_rules['other_page'] ) && $campaign_target_rules['other_page'] == 'yes' ) ? '' : 'style="display: none;"'; ?>>
					<label class="options_header">&nbsp;</label>
					<?php 
						echo '<select name="page_id[]" id="where_page_id" data-placeholder="' . __( 'Select a page&hellip;', 'icegram' ) .  '" style="min-width:300px;" class="icegram_chosen_page" multiple>';
						foreach ( get_pages() as $page ) {
							echo '<option value="' . $page->ID . '"';
							if( !empty( $campaign_target_rules['page_id'] ) ) {
								echo selected( in_array( $page->ID, $campaign_target_rules['page_id'] ) );
							}
							echo '>' . $page->post_title . '</option>';
						}
						echo '</select>';
					?>
				</p>
				<p class="form-field">
					<label class="options_header">&nbsp;</label>
					<label for="where_local_url">
						<input type="checkbox" name="campaign_target_rules[local_url]" id="where_local_url" value="yes" <?php ( !empty( $campaign_target_rules['local_url'] ) ) ? checked( $campaign_target_rules['local_url'], 'yes' ) : ''; ?> />
						<?php _e( 'Specific URLs on this site', 'icegram' ); ?>
					</label>
				</p>
				<p class="form-field local_url" <?php echo ( !empty( $campaign_target_rules['local_url'] ) && $campaign_target_rules['local_url'] == 'yes' ) ? '' : 'style="display: none;"'; ?>>
					<?php 
					if(!empty($campaign_target_rules['local_urls'])){
						foreach ($campaign_target_rules['local_urls'] as $url) {?>
							<span><label class="options_header"><span id="valid-field"> </span></label>
							<input type="text" data-option="local_url"  class="url_input_field" name="campaign_target_rules[local_urls][]" value="<?php echo $this->site_url.$url ;?>"/><span class="delete-url"></span></span>
					<?php	
						}
					}else{ ?>
						<span><label class="options_header"><span id="valid-field"> </span></label>
						<input type="text" data-option="local_url" class="url_input_field" name="campaign_target_rules[local_urls][]" value="<?php echo $this->site_url.'*' ;?>"/><span class="delete-url"></span></span>
					<?php }
					?>
					<label class="options_header " id="add_local_url_row_label">&nbsp;</label><span id="add-url-icon"> </span><a  class="campaign_add_url" id="add_local_url_row" href="#"><?php _e( ' Add another', 'icegram' ); ?></a>
				</p>
				
				<?php
					do_action( 'icegram_after_campaign_where_rule', $campaign_id, $campaign_target_rules );
				?>
				
			</div>
			<div class="options_group" id="campaign_target_rules_when">
				<p class="form-field">
					<label class="options_header"><?php _e( 'When?', 'icegram' ); ?></label>
					<label for="when_always">
						<input type="radio" class="schedule_rule" name="campaign_target_rules[when]" id="when_always" value="always" <?php ( !empty( $campaign_target_rules['when'] ) ) ? checked( $campaign_target_rules['when'], 'always' ) : ''; ?> />
						<?php _e( 'Always', 'icegram' ); ?>
					</label>
				</p>
				<p class="form-field">
					<label class="options_header">&nbsp;</label>
					<label for="when_schedule">
						<input type="radio" class="schedule_rule" name="campaign_target_rules[when]" id="when_schedule" value="schedule" <?php ( !empty( $campaign_target_rules['when'] ) ) ? checked( $campaign_target_rules['when'], 'schedule' ) : ''; ?> />
						<?php _e( 'Schedule', 'icegram' ); ?>
						<span class="form-field" id="date_picker" <?php echo ( !empty( $campaign_target_rules['when'] ) && $campaign_target_rules['when'] == 'schedule' ) ? '' : 'style="display: none;"'; ?>>
							<label class="date_picker">
								<input type="text" class="date-picker" name="campaign_target_rules[from]" value="<?php echo ( !empty( $campaign_target_rules['from'] ) ) ? esc_attr( $campaign_target_rules['from'] ) : ''; ?>" placeholder="<?php _e( 'From&hellip;', 'icegram' );?>" />
							</label>
							<label class="date_picker">
								<input type="text" class="date-picker" name="campaign_target_rules[to]" value="<?php echo ( !empty( $campaign_target_rules['to'] ) ) ? esc_attr( $campaign_target_rules['to'] ) : ''; ?>" placeholder="<?php _e( 'To&hellip;', 'icegram' );?>" />
							</label>
						</span>
					</label>
				</p>
			</div>
			<div class="options_group" id="campaign_target_rules_device">
				<p class="form-field">
					<label class="options_header"><?php _e( 'Device?', 'icegram' ); ?></label>
					<label for="device_mobile" class="device" title="<?php _e( 'Mobile / Smartphones', 'icegram' ); ?>">
						<input type="checkbox" name="campaign_target_rules[mobile]" id="device_mobile" value="yes" <?php ( !empty( $campaign_target_rules['mobile'] ) ) ? checked( $campaign_target_rules['mobile'], 'yes' ) : ''; ?> />
						<span class="device_mobile"></span>
					</label>
					<label for="device_tablet" class="device" title="<?php _e( 'Tablet', 'icegram' ); ?>">
						<input type="checkbox" name="campaign_target_rules[tablet]" id="device_tablet" value="yes" <?php ( !empty( $campaign_target_rules['tablet'] ) ) ? checked( $campaign_target_rules['tablet'], 'yes' ) : ''; ?> />
						<span class="device_tablet"></span>
					</label>
					<label for="device_laptop" class="device" title="<?php _e( 'Desktop / Laptop', 'icegram' ); ?>">
						<input type="checkbox" name="campaign_target_rules[laptop]" id="device_laptop" value="yes" <?php ( !empty( $campaign_target_rules['laptop'] ) ) ? checked( $campaign_target_rules['laptop'], 'yes' ) : ''; ?> />
						<span class="device_laptop"></span>
					</label>
				</p>
			</div>
			<div class="options_group" id="campaign_target_rules_users">
				<p class="form-field">
					<label class="options_header"><?php _e( 'Who?', 'icegram' ); ?></label>
					<label for="users_all">
						<input type="radio" name="campaign_target_rules[logged_in]" id="users_all" value="all" <?php ( !empty( $campaign_target_rules['logged_in'] ) ) ? checked( $campaign_target_rules['logged_in'], 'all' ) : ''; ?> />
						<?php _e( 'All users', 'icegram' ); ?>
					</label>
				</p>
				<p class="form-field">
					<label class="options_header">&nbsp;</label>
					<label for="users_logged_in">
						<input type="radio" name="campaign_target_rules[logged_in]" id="users_logged_in" value="logged_in" <?php ( !empty( $campaign_target_rules['logged_in'] ) ) ? checked( $campaign_target_rules['logged_in'], 'logged_in' ) : ''; ?> />
						<?php _e( 'Logged in users only', 'icegram' ); ?>
					</label>
				</p>
				
				<div class="user_roles">
					<?php
						if ( !empty( $campaign_target_rules['logged_in'] ) && ($campaign_target_rules['logged_in'] == 'all' || $campaign_target_rules['logged_in'] == 'not_logged_in') ) {
							$campaign_logged_in_user_style = 'style="display: none;"';
						} else {
							$campaign_logged_in_user_style = 'style="display: block;"';
						}
					?>
					<p class="form-field" <?php echo $campaign_logged_in_user_style; ?>>
						<label class="options_header">&nbsp;</label>
					<?php
						if ( isset( $wp_roles ) ) {
							$wp_roles = new WP_Roles();
							$roles = $wp_roles->get_names();
							
							echo '<select name="campaign_target_rules[users][]" id="users_roles" data-placeholder="' . __( 'Select a user role&hellip;', 'icegram' ) .  '" style="min-width:300px;" class="icegram_chosen_page" multiple>';
							foreach ( $roles as $role_value => $role_name ) {
								echo '<option value="' . $role_value . '"';
								if( !empty( $campaign_target_rules['users'] ) ) {
									echo selected( in_array( $role_value, $campaign_target_rules['users'] ) );
								}
								echo '>' . $role_name . '</option>';
							}
							echo '</select>';
						}
					?>
				</div>
				<p class="form-field">
					<label class="options_header">&nbsp;</label>
					<label for="users_not_logged_in">
						<input type="radio" name="campaign_target_rules[logged_in]" id="users_not_logged_in" value="not_logged_in" <?php ( !empty( $campaign_target_rules['logged_in'] ) ) ? checked( $campaign_target_rules['logged_in'], 'not_logged_in' ) : ''; ?> />
						<?php _e( 'Not Logged in users', 'icegram' ); ?>
					</label>
				</p>
			</div>
			<?php 	$expiry_options_for_shown = array(  'current_session' => __('Current Session' ,'icegram'),
											'+50 years' => __('Never' ,'icegram'),
											'today' => __('Today' ,'icegram'),
											'+1 week' => __('One week' ,'icegram') ,
											'+2 week' => __('Two weeks' ,'icegram'),
											'+1 month' => __('One Month ' ,'icegram'),
											'+3 months' => __('Three Months ' ,'icegram') ,
											'+1 year' => __('One year' ,'icegram') ,
											'+2 years' => __('Two Years' ,'icegram')); 
					$expiry_options_for_clicked = array(  '+50 years' => __('Never' ,'icegram'),
												'current_session' => __('Current Session' ,'icegram'),
												'today' => __('Today' ,'icegram'),
												'+1 week' => __('One week' ,'icegram') ,
												'+2 week' => __('Two weeks' ,'icegram'),
												'+1 month' => __('One Month ' ,'icegram'),
												'+3 months' => __('Three Months ' ,'icegram') ,
												'+1 year' => __('One year' ,'icegram') ,
												'+2 years' => __('Two Years' ,'icegram')); 

											?>
			<div class="options_group" id="campaign_target_rules_retrageting">
				<p class="form-field">
					<label class="options_header"><?php _e( 'Retargeting', 'icegram' ); ?></label>
					<label for="retargeting">
						<input type="checkbox" name="campaign_target_rules[retargeting]" id="retargeting" value="yes" <?php ( !empty( $campaign_target_rules['retargeting'] ) ) ? checked( $campaign_target_rules['retargeting'], 'yes' ) : ''; ?> />
						<?php _e( 'Once shown, do NOT show this campaign again for', 'icegram' ); ?>
						<select name="campaign_target_rules[expiry_time]">
							<?php foreach($expiry_options_for_shown as $key => $option){
									?>
									<option value="<?php echo $key; ?>" <?php (!empty($campaign_target_rules['expiry_time'])) ? selected( $campaign_target_rules['expiry_time'], $key ) : ''; ?>><?php echo $option; ?></option>
							<?php
								  }
							?>
						</select>
					</label>
				</p>
				<p class="form-field">
					<label class="options_header">&nbsp;</label>
					<label for="retargeting_clicked">
						<input type="checkbox" name="campaign_target_rules[retargeting_clicked]" id="retargeting_clicked" value="yes" <?php ( !empty( $campaign_target_rules['retargeting_clicked'] ) ) ? checked( $campaign_target_rules['retargeting_clicked'], 'yes' ) : ''; ?> />
						<?php _e( 'Once CTA is clicked, do NOT show this campaign again for', 'icegram' ); ?>
						<select name="campaign_target_rules[expiry_time_clicked]">
							<?php foreach($expiry_options_for_clicked as $key => $option){
									?>
									<option value="<?php echo $key; ?>" <?php (!empty($campaign_target_rules['expiry_time_clicked'])) ? selected( $campaign_target_rules['expiry_time_clicked'], $key ) : ''; ?>><?php echo $option; ?></option>
							<?php
								  }
							?>
						</select>
					</label>
				</p>
			</div>
			<?php
		}

		// Return json encoded messages for searched term
		function icegram_json_search_messages( $x = '' ) {
			global $icegram;
			check_ajax_referer( 'search-messages', 'security' );

			header( 'Content-Type: application/json; charset=utf-8' );

			$term = ( string ) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			$post_types = array('ig_message');

			if ( empty( $term ) ) die();

			if ( is_numeric( $term ) ) {

				$args = array(
					'post_type'			=> $post_types,
					'post_status'	 	=> 'publish',
					'posts_per_page' 	=> -1,
					'post__in' 			=> array( 0, $term ),
					'fields'			=> 'ids'
				);

				$posts = get_posts( $args );

			} else {

				$args = array(
					'post_type'			=> $post_types,
					'post_status' 		=> 'publish',
					'posts_per_page' 	=> -1,
					's' 				=> $term,
					'fields'			=> 'ids'
				);

				$posts = get_posts( $args );

			}

			$found_messages = array();
			if ( $posts ) {

				foreach ( $posts as $post ) {

					$message_title 			= get_the_title( $post );
					$message_data 			= get_post_meta( $post, 'icegram_message_data', true );
					$message_type 				= ( !empty( $icegram->message_types[ $message_data['type'] ]['type'] ) ) ? $icegram->message_types[ $message_data['type'] ]['type'] : '';
					$found_messages[ $post ] 	= $message_type . ' &mdash; ' . $message_title;

				}
				$found_messages[''] 	= __( '- - - - - - - - - - - - - - - - - - - - - - - - - -', 'icegram' );
			}

			foreach ( $icegram->message_types as $message ) {
				$found_messages[ $message['type'] ] = __( 'Create new', 'icegram' ) . ' ' . $message['name'] . ' ...';
			}
			ob_clean();
			$found_messages = apply_filters( 'icegram_searched_messages', $found_messages, $term );
			echo json_encode( $found_messages );
			die();
		}

		// Constant table header for campaign
		function message_list_table_header() {
			?>
			<thead>
				<tr class="form-field message-row-header">
					<th class="message_header"><?php _e( 'Type', 'icegram' ); ?></th>
					<th class="message_title"><?php _e( 'Name', 'icegram' ); ?></th>
					<th class="message_seconds"><?php _e( 'Show after', 'icegram' ); ?></th>
					<th class="action_links"><?php _e( 'Actions', 'icegram' ); ?></th> 
				</tr>
			</thead>
			<?php
		}

		// Return html for message row in json encoded format
		function get_message_action_row() {

			$ig_message_admin = Icegram_Message_Admin::getInstance();
			$ig_message_admin->is_icegram_editor = true;

			if ( empty( $_POST['message_id'] ) || !is_numeric( $_POST['message_id'] ) ) {

				$my_post = array(
				  'post_status' => 'auto-draft',
				  'post_type' 	=> 'ig_message'
				);
				$message_id 	= wp_insert_post( $my_post );
				$message_title 	= '';
				$message_type 	= $_POST['message_id'];

			} else {
						
				$message_id 	= $_POST['message_id'];
				$message_title 	= get_the_title( $message_id );
				$message_data 	= get_post_meta( $message_id, 'icegram_message_data', true );
				$message_type 	= $message_data['type'];

			}
			
			ob_start();
			$icegram_message_meta_key = apply_filters('icegram_message_meta_key' , 'messages');
			?>
			<tr class="form-field message-row" value="<?php echo $message_id; ?>">
				<td class="message_header">
					<label class="message_header_label <?php echo "ig_".$message_type; ?>"><?php echo ucwords( str_replace( "-", ' ', $message_type ) ); ?></label>
				</td>
				<td class="message_title">
					<div class="message-title-text" style="display:none;"><?php echo $message_title; ?></div>
					<input type="text" class="message-title-input" name="message_data[<?php echo $message_id; ?>][post_title]" value="<?php echo $message_title; ?>" placeholder="<?php echo __( 'Give this message a name for your own reference', 'icegram' ); ?>">
				</td>
				<td class="message_seconds">
					<input type="hidden" name="<?php echo  $icegram_message_meta_key .'['.$_POST['row']; ?>][id]" value="<?php echo $message_id; ?>" />
					<input type="number" class="seconds-text" name="<?php echo  $icegram_message_meta_key .'['.$_POST['row']; ?>][time]" min="0" value="0" size="3" /><?php _e( ' sec', 'icegram' )?>
				</td>
				<td class="action_links">
					<span class="actions message_edit" title="<?php _e( 'Edit Message', 'icegram' ); ?>" ></span> 
					<span class="actions message_delete" title="<?php _e( 'Remove from Campaign', 'icegram' ); ?>" ></span>
				</td> 
			</tr>
			<tr id="message_row_<?php echo $message_id; ?>" class="message-edit-row">
				<td colspan="4">
				<?php 
					$ig_message_admin->message_form_fields( '', array( 'message_type' => $message_type, 'message_id' => $message_id, 'new_message_row' => true ) );
				?>
				</td>
			</tr>
			<?php
		     
			echo json_encode( array( 'id' => $message_id, 'main' => ob_get_clean() ) );
			die();

		}

		// Save all list of messages and targeting rules
		function save_campaign_settings( $post_id, $post ) {

			if (empty( $post_id ) || empty( $post ) || empty( $_POST )) return;
			if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) return;
			if (is_int( wp_is_post_revision( $post ) )) return;
			if (is_int( wp_is_post_autosave( $post ) )) return;
			if ( empty( $_POST['icegram_campaign_meta_nonce'] ) || ! wp_verify_nonce( $_POST['icegram_campaign_meta_nonce'], 'icegram_campaign_save_data' ) ) return;
			if (! current_user_can( 'edit_post', $post_id )) return;
			if ($post->post_type != 'ig_campaign') return;
			
			$campaign_target_rules = apply_filters( 'icegram_update_campaign_rules', $_POST['campaign_target_rules'], $post_id );
			
			if(!empty($campaign_target_rules) && !empty($campaign_target_rules['local_urls'])){
   				foreach ($campaign_target_rules['local_urls'] as $key => $url) {
   					if( !empty( $url ) ){
   						if( $url == '*'){
   							$campaign_target_rules['local_urls'][$key] = $url;		
   						}else{   							
   					    	$url = str_replace($this->site_url, '', $url);
   					    	$campaign_target_rules['local_urls'][$key] = $url;
   					    }				
   					} else {
   						unset($campaign_target_rules['local_urls'][$key]);
   					}
   				}
   				
   			}
   			
			if ( isset( $_POST['page_id'] ) ) {
				$campaign_target_rules['page_id'] = $_POST['page_id'];
				update_post_meta( $post_id, 'icegram_campaign_target_pages', $_POST['page_id'] );
			}
			if ( isset( $_POST['exclude_page_id'] ) ) {
				$campaign_target_rules['exclude_page_id'] = $_POST['exclude_page_id'];
				update_post_meta( $post_id, 'icegram_campaign_target_pages', $_POST['exclude_page_id'] );
			}

			if ( count( $campaign_target_rules ) > 0 ) {
				update_post_meta( $post_id, 'icegram_campaign_target_rules', $campaign_target_rules );
			}

			if ( empty( $_POST['messages'] ) ) {
				update_post_meta( $post_id, 'messages', array() );
			} else {

				update_post_meta( $post_id, 'messages', array_values( $_POST['messages'] ) );
				update_post_meta( $post_id, 'campaign_preview', array_values( $_POST['messages'] ) );

				// Saving $_POST to temp var before updating messages 
				// to avoid problems with action handlers that rely on
				// $_POST vars - e.g. WPML!!
				$old_post = $_POST;
				$_POST = array();
				foreach ( $old_post['message_data'] as $message_id => $message_data ) {

					$type = $message_data['type'];
					if( isset( $message_data['theme'][$type] ) ) {
						$message_data['theme'] = $message_data['theme'][$type];
					} else {
						unset( $message_data['theme'] );
					}
					if( isset( $message_data['animation'][$type] ) ) {
						$message_data['animation'] = $message_data['animation'][$type];
					} else {
						unset( $message_data['animation'] );
					}
					if( isset( $message_data['position'][$type] ) ) {
						$message_data['position'] = $message_data['position'][$type];
					} elseif( isset( $message_data['position']['ig_default'] ) ) {
						$message_data['position'] = $message_data['position']['ig_default'];
					}
					//save message data when campaign is save
					$message_data = apply_filters( 'icegram_update_message_data', $message_data, $message_id );
					update_post_meta( $message_id, 'icegram_message_data', $message_data );
					update_post_meta( $message_id, 'icegram_message_preview_data', $message_data );
					wp_update_post( array ( 'ID' 			=> $message_id,
			  								'post_content' 	=> $message_data['message'],
			  								'post_status'	=> 'publish',
			  								'post_title'	=> empty( $message_data['post_title'] ) ? $message_data['headline']: $message_data['post_title']
									) );			
				}
				$_POST = $old_post;
			}
		}

		// On preview button click save campaign messages list
		function save_campaign_preview() {

			if ( empty($_POST['post_ID']) ) die();
			if ( !current_user_can( 'edit_post', $_POST['post_ID'] ) ) die();

			$messages = apply_filters('campaign_preview_messages' ,  $_POST['messages'] , $_POST);
			
			if( !empty( $messages ) ) {
				update_post_meta( $_POST['post_ID'], 'campaign_preview', $messages ) ;

				foreach ( (array) $_POST['message_data'] as $message_id => $message_data ) {
					$type = $message_data['type'];
					if( isset( $message_data['theme'][$type] ) ) {
						$message_data['theme'] = $message_data['theme'][$type];
					} else {
						unset( $message_data['theme'] );
					}
					if( isset( $message_data['animation'][$type] ) ) {
						$message_data['animation'] = $message_data['animation'][$type];
					} else {
						unset( $message_data['animation'] );
					}
					if( isset( $message_data['position'][$type] ) ) {
						$message_data['position'] = $message_data['position'][$type];
					} elseif( isset( $message_data['position']['ig_default'] ) ) {					
						$message_data['position'] = $message_data['position']['ig_default'];
					}
					update_post_meta( $message_id, 'icegram_message_preview_data', $message_data );
				}
				// Determine page url to preview on...
				$page_url = '';
					
				if ( !empty($_POST['campaign_target_rules']) && !empty($_POST['campaign_target_rules']['other_page']) && !empty($_POST['page_id']) && is_array($_POST['page_id'])) {
					$page_url = get_permalink( $_POST['page_id'][0] );
				}
				if ($page_url == '') {
					if(!empty($_POST['campaign_target_rules']['local_url']) && is_array($_POST['campaign_target_rules']['local_urls'])){
						$page_url = (strpos($_POST['campaign_target_rules']['local_urls'][0], '*') === false) ? $_POST['campaign_target_rules']['local_urls'][0] : home_url();
					}else{
						$page_url = home_url();
					}
				}
				ob_clean();
				echo add_query_arg( 'campaign_preview_id', $_POST['post_ID'], $page_url );
			}
			die();

		}

		function add_campaign_action( $actions, $post ){
			if ($post->post_type != 'ig_campaign') return $actions;
			
			// Create a nonce & add an action
		    $actions['duplicate_campaign'] = '<a class="ig-duplicate-campaign"  href="post.php?campaign_id='.$post->ID.'&action=duplicate-campaign" >'.__('Duplicate' ,'icegram').'</a>';
			return $actions;
		}

		function duplicate_campaign(){
			if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'duplicate-campaign' && !empty($_REQUEST['campaign_id'])){
				Icegram::duplicate( $_REQUEST['campaign_id'] );
			}
		}
	}
}