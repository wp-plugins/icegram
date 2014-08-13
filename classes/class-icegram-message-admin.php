<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
* Icegram Message Admin class
*/
if ( !class_exists( 'Icegram_Message_Admin' ) ) {

	class Icegram_Message_Admin {
		
		var $message_themes;

		function __construct() {

			add_action( 'add_meta_boxes', array( &$this, 'add_message_meta_boxes' ) );
			add_action( 'wp_ajax_get_message_setting', array( &$this , 'message_form_fields' ) );
			
			add_action( 'save_post', array( &$this, 'update_message_settings' ), 10, 2 );
			add_filter( 'wp_insert_post_data', array( &$this, 'save_message_in_post_content' ) );        

	        add_filter( 'manage_edit-ig_message_columns', array( $this, 'edit_columns' ) );
			add_action( 'manage_ig_message_posts_custom_column', array( $this, 'custom_columns' ), 2 );
			add_filter( 'icegram_available_headlines', array( &$this, 'available_headlines' ) );

		}

		// Initialize message metabox		
		function add_message_meta_boxes() {
			global $icegram;
			add_meta_box( 'message-settings', __( 'Message Settings', 'icegram' ), array ( &$this, 'message_form_fields' ), 'ig_message', 'normal', 'high' );

			?>
			<style type="text/css">
			<?php
			foreach ( $icegram->message_types as $message_type => $message ) {
				if( !empty( $message['admin_style'] ) ) {
					$label_bg_color 		= $message['admin_style']['label_bg_color'];
					$theme_header_height 	= $message['admin_style']['theme_header_height'];
					$theme_header_bg_size	= ( $theme_header_height + 3 )."em";					
					$thumbnail_width 		= $message['admin_style']['thumbnail_width'];
					$thumbnail_height 		= $message['admin_style']['thumbnail_height'];
					echo "	.message_header .$message_type { 
								background-color: {$label_bg_color}; 
							} 
							.message_theme_{$message_type} + .chosen-container-single .chosen-single { 
								height: {$theme_header_height} !important;
							}
							.message_theme_{$message_type} + .chosen-container-single .chosen-single span {
								background-size: {$theme_header_bg_size} !important;
								line-height: {$theme_header_height} !important;
							} 
							.message_theme_{$message_type} + .chosen-container .chosen-results li {
								width: {$thumbnail_width} !important;
								height: {$thumbnail_height} !important;
							}";
				}
			}
			?>
			</style>
			<?php
		}
		
		// Display all message settings fields
		function message_form_fields( $post = '', $action = array() ) {

			global $icegram, $pagenow;

			if( ( is_object( $post ) && $post->post_type != 'ig_message' ) )
				return;

			$message_id 		= !empty( $action['message_id'] ) ? $action['message_id'] : $post->ID;
			$message_data 		= get_post_meta( $message_id, 'icegram_message_data', true );	
			$message_headlines 	= $icegram->available_headlines;
			$settings 			= $this->message_settings_to_show();
			$positions 			= $this->message_positions_to_show();

			if ( $pagenow == 'post-new.php' ) {
				$message_title_key = array_rand( $message_headlines );
				$default_message_title = $message_headlines[$message_title_key];
			} else {
				$default_message_title = $message_title_key = '';
			}

			if( empty( $message_data ) ) {
				$message_type = !empty( $action['message_type'] ) ? $action['message_type'] : '';
				$message_data = $this->default_message_data( $message_type );
			}

			if( !empty( $action['message_type'] ) ) {
				$message_data['type'] = $action['message_type'];
			}

			wp_nonce_field( 'icegram_message_save_data', 'icegram_message_meta_nonce' );				
			if( !empty( $action['message_id'] ) ) {
				?>
				<div class="thickbox_edit_message" id="<?php echo $action['message_id']; ?>">
				<?php 
			} 
			?>
			<div class="wp_attachment_details edit-form-section message-setting-fields">
				<p>
					<label for="message_type" class="message_label"><strong><?php _e( 'Type', 'icegram' ); ?></strong></label> 
					<select id="message_type" name="message_data[<?php echo $message_id; ?>][type]" class="message_type icegram_chosen_page">
					<?php foreach ( $icegram->message_types as $message ) { 
						$selected = ( ( !empty( $message_data['type'] )  && esc_attr( $message['type'] ) == $message_data['type'] ) ) ? 'selected' : '';
						?>
						<option value="<?php echo esc_attr( $message['type'] ) ?>" <?php echo $selected; ?>><?php echo esc_html( $message['name'] ) ?></option>
					<?php } ?>
					</select>
				</p>
				<?php foreach ( $icegram->message_types as $message ) { 
						if( empty( $message['themes'] ) ) {
							continue;
						}
				?>
					<p class="message_row <?php echo $message['type']; ?>">
						<label for="message_theme_<?php echo $message['type'] ?>" class="message_label"><strong><?php _e( 'Theme', 'icegram' ); ?></strong></label> 
						<select id="message_theme_<?php echo $message['type'] ?>" name="message_data[<?php echo $message_id; ?>][theme][<?php echo $message['type'] ?>]" class="icegram_chosen_page message_theme message_theme_<?php echo $message['type']; ?>">
							<?php 
							foreach ( $message['themes'] as $theme ) {
								$bg_img = "background-image: url(" .  $message['baseurl'] . "themes/" . $theme['type'] . ".png)";
								?>
								<option style="<?php echo $bg_img; ?>" value="<?php echo esc_attr( $theme['type'] ) ?>" class="<?php echo esc_attr( $theme['type'] ) ?>" <?php echo ( !empty( $message_data['theme'] )  && esc_attr( $theme['type'] ) == $message_data['theme'] ) ? 'selected' : ''; ?>><?php echo esc_html( $theme['name'] ) ?></option>
							<?php } ?>
						</select>
					</p>
				<?php }	?>
				<?php foreach ( $icegram->message_types as $message ) {
					if( empty( $message['settings']['animation']['values'] ) ) continue;
					$animations = $message['settings']['animation']['values']
					?>
					<p class="message_row <?php echo $message['type']; ?>">
						<label for="message_animation_<?php echo $message['type'] ?>" class="message_label"><strong><?php _e( 'Animation', 'icegram' ); ?></strong></label> 
						<select id="message_animation_<?php echo $message['type'] ?>" name="message_data[<?php echo $message_id; ?>][animation][<?php echo $message['type'] ?>]" class="icegram_chosen_page message_animation message_animation_<?php echo $message['type']; ?>">
							<?php foreach ( $animations as $value => $label ) { ?>
								<option value="<?php echo esc_attr( $value ) ?>" <?php echo ( !empty( $message_data['animation'] ) && esc_attr( $value ) == $message_data['animation'] ) ? 'selected' : ''; ?>><?php echo esc_html( $label ) ?></option>
							<?php } ?>					
						</select>
					</p>
				<?php }	?>
				<p class="message_row <?php echo implode( ' ', $settings['headline'] )?>">
					<label for="message_headline" class="message_label">
						<strong><?php _e( 'Headline', 'icegram' ); ?></strong>
						<span class="help_tip admin_field_icon" data-tip="<?php _e( 'Shown with highest prominence. Click on idea button on right to get a new headline.', 'icegram' ); ?>"></span>
					</label>
					<?php
					$message_headline = ( isset( $message_data['headline'] ) ) ? $message_data['headline'] : $default_message_title;
					?>
					<input type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][headline]" id="message_title" value="<?php echo esc_attr( $message_headline ); ?>" data-headline="<?php echo $message_title_key; ?>" />
					<a class="button message_headline_button tips" data-tip="<?php _e( 'Give Me Another Headline', 'icegram' ); ?>">
						<span class="headline-buttons-icon admin_field_icon"></span>
					</a>
				</p>
				<p class="message_row <?php echo implode( ' ', $settings['label'] )?>">
					<label for="message_label" class="message_label">
						<strong><?php _e( 'Button Label', 'icegram' ); ?></strong>
						<span class="help_tip admin_field_icon" data-tip="<?php _e( 'Your call to action text. Something unusual will increase conversions.', 'icegram' ); ?>"></span>
					</label>
					<input type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][label]" id="message_label" value="<?php if( isset( $message_data['label'] ) ) echo esc_attr( $message_data['label'] ); ?>" />
				</p>
				<p class="message_row <?php echo implode( ' ', $settings['link'] )?>">
					<label for="message_link" class="message_label">
						<strong><?php _e( 'Target Link', 'icegram' ); ?></strong>
						<span class="help_tip admin_field_icon" data-tip="<?php _e( 'Enter destination URL here. Clicking will redirect to this link.', 'icegram' ); ?>"></span>
					</label>
					<input type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][link]" id="message_link" value="<?php if( isset( $message_data['link'] ) ) echo esc_attr( $message_data['link'] ); ?>" />
				</p>
				<p class="message_row <?php echo implode( ' ', $settings['icon'] )?>">
					<label for="upload_image" class="message_label">
						<strong><?php _e( 'Icon / Avatar Image', 'icegram' ); ?></strong>
						<span class="help_tip admin_field_icon" data-tip="<?php _e( 'This image will appear in message content.', 'icegram' ); ?>"></span>
					</label>
					<input id="upload_image" type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][icon]" value="<?php if( isset( $message_data['icon'] ) ) echo esc_attr( $message_data['icon'] ); ?>"/>
					<a class="button message_image_button tips" data-tip="<?php _e( 'Upload / Select an image', 'icegram' ); ?>" onclick="tb_show('<?php _e( 'Upload / Select Image' ); ?>', 'media-upload.php?type=image&TB_iframe=true', false);" >
						<span class="image-buttons-icon admin_field_icon"></span>
					</a>
				</p>
				<?php 
				$default_text_color = ( !empty( $icegram->message_types[$message_data['type']]['settings']['text_color']['default'] ) ) ? $icegram->message_types[$message_data['type']]['settings']['text_color']['default'] : '';
				$default_bg_color 	= ( !empty( $icegram->message_types[$message_data['type']]['settings']['bg_color']['default'] ) ) ? $icegram->message_types[$message_data['type']]['settings']['bg_color']['default'] : '';
				$text_color 		= ( !empty( $message_data['text_color'] ) ) ? $message_data['text_color'] : $default_text_color;
				$bg_color 			= ( !empty( $message_data['bg_color'] ) ) ? $message_data['bg_color'] : $default_bg_color;
				?>
				<p class="message_row <?php echo implode( ' ', $settings['bg_color'] )?>">
					<label for="message_bg_color" class="message_label"><strong><?php _e( 'Backgound Color', 'icegram' ); ?></strong></label>
					<input type="text" class="message_field color-field" name="message_data[<?php echo $message_id; ?>][bg_color]" id="message_bg_color" value="<?php echo $bg_color; ?>" data-default-color="<?php echo $default_bg_color; ?>" />
				</p>
				<p class="message_row <?php echo implode( ' ', $settings['text_color'] )?>">
					<label for="message_text_color" class="message_label"><strong><?php _e( 'Text Color', 'icegram' ); ?></strong></label>
					<input type="text" class="message_field color-field" name="message_data[<?php echo $message_id; ?>][text_color]" id="message_text_color" value="<?php echo $text_color; ?>" data-default-color="<?php echo $default_text_color; ?>" />
				</p>
				<?php
					$editor_args = array(
						'textarea_name' => 'message_data[' . $message_id . '][message]',
						'textarea_rows' => 10,
						'editor_class' 	=> 'wp-editor-message',
						'media_buttons' => true,
						'tinymce' 		=> true
					);
				?>
				<p class="message_row <?php echo implode( ' ', $settings['message'] )?>">
					<label for="message_body" class="message_body message_label"><strong><?php _e( 'Message Body', 'icegram' ); ?></strong></label>
					<?php $message = ( !empty( $message_data['message'] ) ) ? $message_data['message'] : ''; ?>
					<?php wp_editor( $message, 'edit'.$message_id, $editor_args ); ?>
				</p>
				<p class="message_row position <?php echo implode( ' ', $settings['position'] )?>">
					<label for="message_position" class="message_label"><strong><?php _e( 'Position', 'icegram' ); ?></strong></label>
					<span class="message_field location-selector message_label">
						<input type="radio" id="radio01_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="00" <?php echo ( !empty( $message_data['position'] ) && "00" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio01_<?php echo $message_id;?>" title="Top Left">
							<span class="location <?php if( !empty( $positions['00'] ) ) { echo implode( ' ', $positions['00'] ); } ?> top left" data-position="top left"></span>
						</label>
						<input type="radio" id="radio02_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="01" <?php echo ( !empty( $message_data['position'] ) && "01" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio02_<?php echo $message_id;?>" title="Top">
							<span class="location <?php if( !empty( $positions['01'] ) ) { echo implode( ' ', $positions['01'] ); } ?> top" data-position="top"></span>
						</label>
						<input type="radio" id="radio03_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="02" <?php echo ( !empty( $message_data['position'] ) && "02" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio03_<?php echo $message_id;?>" title="Top Right">
							<span class="location <?php if( !empty( $positions['02'] ) ) { echo implode( ' ', $positions['02'] ); } ?> top right" data-position="top right"></span>
						</label>
						<input type="radio" id="radio04_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="10" <?php echo ( !empty( $message_data['position'] ) && "10" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio04_<?php echo $message_id;?>" title="Middle Left">
							<span class="location <?php if( !empty( $positions['10'] ) ) { echo implode( ' ', $positions['10'] ); } ?> middle left" data-position="middle left"></span>
						</label>
						<input type="radio" id="radio05_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="11" <?php echo ( !empty( $message_data['position'] ) && "11" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio05_<?php echo $message_id;?>" title="Middle">
							<span class="location <?php if( !empty( $positions['11'] ) ) { echo implode( ' ', $positions['11'] ); } ?> middle middle" data-position="middle middle"></span>
						</label>
						<input type="radio" id="radio06_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="12" <?php echo ( !empty( $message_data['position'] ) && "12" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio06_<?php echo $message_id;?>" title="Middle Right">
							<span class="location <?php if( !empty( $positions['12'] ) ) { echo implode( ' ', $positions['12'] ); } ?> middle right" data-position="middle right"></span>
						</label>
						<input type="radio" id="radio07_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="20" <?php echo ( !empty( $message_data['position'] ) && "20" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio07_<?php echo $message_id;?>" title="Bottom Left">
							<span class="location <?php if( !empty( $positions['20'] ) ) { echo implode( ' ', $positions['20'] ); } ?> bottom left" data-position="bottom left"></span>
						</label>
						<input type="radio" id="radio08_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="21" <?php echo ( !empty( $message_data['position'] ) && "21" == $message_data['position'] || !isset( $message_data['position'] ) ) ? 'checked' : ''; ?> />
						<label for="radio08_<?php echo $message_id;?>" title="Bottom">
							<span class="location <?php if( !empty( $positions['21'] ) ) { echo implode( ' ', $positions['21'] ); } ?> bottom" data-position="bottom"></span>
						</label>
						<input type="radio" id="radio09_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position][ig_default]" value="22" <?php echo ( !empty( $message_data['position'] ) && "22" == $message_data['position'] ) ? 'checked' : ''; ?> />
						<label for="radio09_<?php echo $message_id;?>" title="Bottom Right">
							<span class="location <?php if( !empty( $positions['22'] ) ) { echo implode( ' ', $positions['22'] ); } ?> bottom right" data-position="bottom right"></span>
						</label>
					</span>
				</p>
				<?php
				do_action( 'icegram_after_message_settings', $message_id, $message_data );
				?>
			</div>
			<input type="hidden" name="message_data[<?php echo $message_id; ?>][id]" value="<?php echo $message_id; ?>">
		 	<input type="hidden" class="message_id" name="message_id" value="<?php echo $message_id; ?>">
			<?php

			if( !empty( $action['message_id'] ) ) {
				
				?></div>
				<?php
				if( !empty( $action['new_message_row'] ) && $action['new_message_row'] ) {
					\_WP_Editors::enqueue_scripts();
				    // print_footer_scripts();
				    \_WP_Editors::editor_js();
				}
				
			} else {
				
				?>
					<p class="message_row">
					<label class="message_label">&nbsp;</label>
					<span>
					<span class="shortcode_description admin_field_icon"></span>
				<?php 
				echo sprintf(__( 'You may add <code>[%s]</code> where you want to show this message.', 'icegram' ), 'icegram messages="' .$post->ID . '"' );
				?>
					</span></p>
				<?php
			}

		}

		// Used to save the settings which are being made in the message form and added to message page appropriately 
		function update_message_settings( $post_id, $post ) {

			if (empty( $post_id ) || empty( $post ) || empty( $_POST )) return;
			if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) return;
			if (is_int( wp_is_post_revision( $post ) )) return;
			if (is_int( wp_is_post_autosave( $post ) )) return;
			if ( empty( $_POST['icegram_message_meta_nonce'] ) || ! wp_verify_nonce( $_POST['icegram_message_meta_nonce'], 'icegram_message_save_data' ) ) return;
			if (! current_user_can( 'edit_post', $post_id )) return;
			if ($post->post_type != 'ig_message') return;

			$message_data = $_POST['message_data'][$post_id];
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

			$message_data = apply_filters( 'icegram_update_message_data', $message_data, $post_id );
			update_post_meta( $post_id, 'icegram_message_data', $message_data );
			update_post_meta( $post_id, 'icegram_message_preview_data', $message_data );			
			
		}

		// Additionally save message body content in post_content of post table
		function save_message_in_post_content( $post_data ) {

		    if( !empty( $_POST['post_type'] ) && $_POST['post_type'] == 'ig_message' && !empty( $_POST['message_data'] ) ) {
				$message_id = $_POST['ID'];
				$post_data['post_content'] = $_POST['message_data'][$message_id]['message'];
				
				if( isset( $_POST['message_data'][$message_id]['post_title'] ) ) {

					if( !empty( $_POST['message_data'][$message_id]['post_title'] ) ) {
						$post_data['post_title'] = $_POST['message_data'][$message_id]['post_title'];
					} else {
						$post_data['post_title'] = $_POST['message_data'][$message_id]['headline'];				
					}
					
				}
		    }
			return $post_data;
		}

		// Add message columns to message dashboard
		function edit_columns( $existing_columns ) {

			$date = $existing_columns['date'];
			unset( $existing_columns['date'] );
			
			$existing_columns['message_type']      	= __( 'Type', 'icegram' );
			$existing_columns['message_theme']    	= __( 'Theme', 'icegram' );
			$existing_columns['message_thumbnail'] 	= __( 'Thumbnail', 'icegram' );
			$existing_columns['date'] 				= $date;

			return apply_filters( 'icegram_manage_message_columns', $existing_columns );

		}

		// Add message columns data to message dashboard
		function custom_columns( $column ) {
			global $post ,$icegram;

			if( ( is_object( $post ) && $post->post_type != 'ig_message' ) )
				return;

			$message_data 	= get_post_meta( $post->ID, 'icegram_message_data', true );
			if ( empty( $message_data['type'] ) ) {
				return;
			}
			$class_name 	= 'Icegram_Message_Type_' . str_replace(' ', '_', ucwords(str_replace('-', ' ', $message_data['type'])));
            if( !class_exists( $class_name ) ) {
            	return;
            }			
			$type 	= ucwords( str_replace( "-", ' ', $message_data['type'] ) );
			$theme 	= ucwords( str_replace( "-", ' ', $message_data['theme'] ) );
			$bg_img = $icegram->message_types[$message_data['type']]['baseurl'] . "themes/" . $message_data['theme'] . ".png";						

			switch ($column) {
				case 'message_type':
					echo $type;
					break;

				case 'message_theme':
					echo $theme;
					break;

				case 'message_thumbnail':
					echo "<img src='$bg_img' style='max-width: 200px; max-height: 100px;'>";
					break;

				default:
					do_action( 'icegram_manage_message_custom_column', $column, $message_data );
					break;
				
			}

		}

		// Create array for settings based on message types
		function message_settings_to_show() {

			global $icegram;
			$settings = array();
			foreach ( $icegram->message_types as $type => $value ) {
				foreach ( $value['settings'] as $setting => $property ) {
					$settings[$setting][] = $type;
				}
			}
			return apply_filters( 'icegram_message_settings_to_show', $settings );

		}
		
		// Create array for positions available for all message types		
		function message_positions_to_show() {

			global $icegram;
			$positions = array();
			foreach ( $icegram->message_types as $type => $value ) {
				if( empty( $value['settings']['position'] ) )
					continue;

				if( !empty( $value['settings']['position']['values'] ) ) {
					foreach ( $value['settings']['position']['values'] as $position ) {
						$positions[$position][] = $type;
					}					
				}
			}
			return apply_filters( 'icegram_message_positions_to_show', $positions );

		}
		
		// Default message data for newly created message
		function default_message_data( $message_type = '' ) {

			global $icegram;
			$default_themes = array();
			foreach ( $icegram->message_types as $type => $value ) {
				if( isset( $value['settings']['theme']['default'] ) ) {
					$default_themes[$type] = $value['settings']['theme']['default'];
				}
			}
			if( !empty( $message_type ) ) {
				$default_message = $icegram->message_types[$message_type];
			} else {
				$default_message = reset( $icegram->message_types );				
			}
			$default_message_data = array(  'type' 			=> $default_message['type'],
											'position' 		=> ( !empty( $default_message['settings']['position']['values'][0] ) ) ? $default_message['settings']['position']['values'][0] : '',
											'text_color' 	=> ( !empty( $default_message['settings']['text_color']['default'] ) ) ? $default_message['settings']['text_color']['default'] : '',
											'bg_color' 		=> ( !empty( $default_message['settings']['bg_color']['default'] ) ) ? $default_message['settings']['bg_color']['default'] : '',
											'theme'			=> $default_themes
											);
			return apply_filters( 'icegram_default_message_data', $default_message_data );

		}
		
		// All headline to generate randomly for messages
		function available_headlines( $available_headlines = array() ) {
			$available_headlines = array_merge( $available_headlines, array(
					__( 'Here Is A Method That Is Helping ____ To ____', 'icegram' ),
					__( '__ Little Known Ways To ____', 'icegram' ),
					__( 'Get Rid Of ____ Once And For All', 'icegram' ),
					__( 'How To ____ So You Can ____', 'icegram' ),
					__( 'They Didn\'t Think I Could ____, But I Did', 'icegram' ),
					__( 'How ____ Made Me ____', 'icegram' ),
					__( 'Are You ____ ?', 'icegram' ),
					__( 'Warning: ____ !', 'icegram' ),
					__( 'Do You Make These Mistakes With ____ ?', 'icegram' ),
					__( '7 Ways To ____', 'icegram' ),
					__( 'If You\'re ____, You Can ____', 'icegram' ),
					__( 'Turn your ____ into a ____', 'icegram' ),
					__( 'Want To Be A ____?', 'icegram' ),
					__( 'The Ugly Truth About Your Beautiful ____', 'icegram' ),
					__( 'The Secret to ____ Is Simply ____!', 'icegram' ),
					__( 'The Quickest Way I Know To ____', 'icegram' ),
					__( 'The Lazy Man\'s Way To ____', 'icegram' ),
					__( 'The Amazing Story Of ____ That Requires So Little Of ____ You Could ____', 'icegram' ),
					__( 'The Amazing Secret Of The ____ Genius Who Is Afraid Of ____', 'icegram' ),
					__( 'The 10 Wackiest Ideas That ____... And How You Can Too!', 'icegram' ),
					__( 'The Inside Secrets To ____ With Crazy, Outlandish And Outrageous ____', 'icegram' ),
					__( '____ Like A ____', 'icegram' ),
					__( 'Remember When You Could Have ____, And You Didn\'t?', 'icegram' ),
					__( 'Is The ____ Worth $x To You?', 'icegram' ),
					__( 'Increase your ____, reduce ____, maintain ____ and ____ with ____', 'icegram' ),
					__( 'If You Can ____ You Can ____', 'icegram' ),
					__( 'I Discovered How To ____... Now I\'m Revealing My Secret', 'icegram' ),
					__( 'How To Turn Your ____ Into The Most ____', 'icegram' ),
					__( 'How To Take The Headache Out Of ____', 'icegram' ),
					__( 'How To ____ ... Legally', 'icegram' ),
					__( 'How To ____ That ____', 'icegram' ),
					__( 'How To Discover The ____ That Lies Hidden In Your ____', 'icegram' ),
					__( 'How To ____ Even When Your Not ____', 'icegram' ),
					__( '____ With No ____!', 'icegram' ),
					__( 'Greatest Goldmine of ____ Ever Jammed Into One Big ____', 'icegram' ),
					__( 'Free ____ Tells How To Get Better ____', 'icegram' ),
					__( 'FREE ____ Worth $____ for the first 100 People to take Advantage of this Offer', 'icegram' ),
					__( 'Don\'t Try This With Any Other ____', 'icegram' ),
					__( 'Do You Honestly Want To ____?', 'icegram' ),
					__( 'Discover The Magic ____ That Will Bring You ____ & ____!', 'icegram' ),
					__( '____ Man Reveals A Short-Cut To ____', 'icegram' ),
					__( 'Confessions Of A ____', 'icegram' ),
					__( 'Are You Ready To ____?', 'icegram' ),
					__( 'An Open Letter To Everyone Who ____ More Than ____ Per ____', 'icegram' ),
					__( 'An Amazing ____ You Can Carry In Your ____', 'icegram' ),
					__( '21 Secret ____ that will ____... NOW!', 'icegram' )
				) );
			return $available_headlines;
		}
	}
}