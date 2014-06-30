<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
* WP Message class
*/
class WP_Message {
	
	var $title;
	var $message_data;
	var $message_types;
	var $message_themes;
	var $default_message_color;
	var $default_message_data;
	var $available_headlines;

	function __construct( $message_id = '' ) {

		if ( !empty( $message_id ) ) {

			$post 					= get_post( $message_id );
			$this->title 			= $post->post_title;
			$this->message_data 	= get_post_meta( $post->ID, 'icegram_message_data', true );
		}

		add_action( 'init', array( &$this, 'message_types' ) );
		add_action( 'add_meta_boxes', array( &$this, 'add_message_meta_boxes' ) );
		add_action( 'save_post', array( &$this, 'update_message_settings' ), 10, 2 );
		add_action( 'wp_ajax_update_message_data', array( &$this , 'update_message_data' ) );
		add_action( 'wp_ajax_get_message_setting', array( &$this , 'message_form_fields' ) );

		add_filter( 'icegram_all_message_type', array( &$this, 'icegram_all_message_type' ) );
		add_filter( 'icegram_all_message_theme', array( &$this, 'icegram_all_message_theme' ) );
		add_filter( 'icegram_available_headlines', array( &$this, 'available_headlines' ) );

		add_filter( 'wp_insert_post_data', array( &$this, 'save_message_in_post_content' )  );        
        add_filter( 'manage_edit-message_columns', array( $this, 'edit_columns' ) );
		add_action( 'manage_message_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		
		$this->default_message_color = array( 'text_color' => array( 'action-bar' 	=> '#ffffff',
															            'messenger' 	=> '#000',
															            'popup' 		=> '#333',
															            'toast' => '#222'
															            ),
												'bg_color' => array( 'action-bar' 	=> '#eb593c',
														            'messenger' 	=> '#fff',
														            'popup' 		=> '#fff',
														            'toast' => '#fff'
														            )
												);
		$this->default_message_data = array(  'type' 			=> 'action-bar',
											    'position' 		=> '21',
											    'text_color' 	=> '#000000',
											    'bg_color' 		=> '#ffffff',
											    'theme'			=> array ( 'action-bar' => 'hello',
											    							'messenger' => 'social',
											    							'popup' => 'persuade',
											    							'toast' => 'announce'
											    						 )
									        );

		if ( empty( $this->available_headlines ) ) {
			$this->available_headlines = apply_filters( 'icegram_available_headlines', array() );
		}

	}

	public function edit_columns( $existing_columns ) {

		$date = $existing_columns['date'];
		unset( $existing_columns['date'] );
		
		$existing_columns['message_type']      	= __( 'Type', 'icegram' );
		$existing_columns['message_theme']    	= __( 'Theme', 'icegram' );
		$existing_columns['message_thumbnail'] 	= __( 'Thumbnail', 'icegram' );
		$existing_columns['date'] 				= $date;

		return $existing_columns;

	}

	public function custom_columns( $column ) {
		global $post ,$icegram;

		if( ( is_object( $post ) && $post->post_type != 'message' ) )
			return;

		$message_data = get_post_meta( $post->ID, 'icegram_message_data', true );
		$type 	= ucwords( str_replace( "-", ' ', $message_data['type'] ) );
		$theme 	= ucwords( str_replace( "-", ' ', $message_data['theme'][$message_data['type']] ) );
		$bg_img = "" . $icegram->plugin_url . "/assets/images/themes/" . $message_data['type'] . "/" . $message_data['theme'][$message_data['type']] .".png";

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
			
		}

	}

	function message_types() {
		global $icegram;

		$message_templates = glob( $icegram->plugin_path . '/templates' . '/*.php' );
		if( empty( $message_templates ) )
			return;

		foreach ( $message_templates as $type ) {
			$type 				= str_replace( ".php", "", basename( $type ) );
			$message_types[$type] = ucwords( str_replace( "-", ' ', $type ) );
		}

		$this->message_types = $message_types;
		if( empty( $message_types ) )
			return;

		foreach ( $message_types as $type => $value ) {
			$themes = glob( $icegram->plugin_path . '/assets/css/' . $type . '/*.css' );
			if( empty( $themes ) )
				continue;

			$message_themes = array();
			foreach ( $themes as $theme ) {
				$theme = str_replace( ".css", "", basename( $theme ) );
				$message_themes[$theme] = ucwords( str_replace( "-", ' ', $theme ) );
			}
			$this->message_themes[$type] = $message_themes;
		}
	}

	public function add_message_meta_boxes() {		

		$meta_box_title =  __( 'Message Settings' );
		// $meta_box_title .= '<div class="button button-primary message_preview" style="margin-top: -5px;">' . __( 'Preview' ) . '</div>';
		
		add_meta_box( 'message-settings', $meta_box_title, array ( &$this, 'message_form_fields' ), 'message', 'normal', 'high' );

	}

	function message_form_fields( $post = '', $action = array() ) {

		global $icegram, $pagenow;

		if( ( is_object( $post ) && $post->post_type != 'message' ) )
			return;

		if( !empty( $action['message_id'] ) ) {
			$message_id = $action['message_id'];
		} else {
			$message_id = $post->ID;
		}

		if( !empty( $action['message_id'] ) ) {
			?>
			<div class="thickbox_edit_message" id="<?php echo $message_id; ?>">
		<?php } 

		wp_nonce_field( 'icegram_message_save_data', 'icegram_message_meta_nonce' );
		$message_animation 	= array( "slide" => "Slide", "appear" => "Appear" );
		$message_toast_animation 	= array( "bang" => "Bang",
											 "slide-down" => "Slide Down",
											 "pop" => "Pop",
											 "appear" => "Appear",								
											 "slide-left" => "Slide Right"
										 	);
		
		$message_types 		= array();
		$message_themes 	= array();
		$message_data 		= get_post_meta( $message_id, 'icegram_message_data', true );	
		$message_types 		= apply_filters( 'icegram_all_message_type', array() );
		$message_themes 	= apply_filters( 'icegram_all_message_theme', array() );
		$message_animation 	= apply_filters( 'icegram_all_message_animation', $message_animation );
		$show_powered_by 	= apply_filters( 'icegram_show_powered_by_message', true );
		$message_headlines 	= $this->available_headlines;

		if ( $pagenow == 'post-new.php' ) {
			$message_title_key = array_rand( $message_headlines );
			$default_message_title = $message_headlines[$message_title_key];
		} else {
			$default_message_title = $message_title_key = '';
		}

		if( !$message_data ) {
			$message_data = $this->default_message_data;
		}
		if( !empty( $action['promo_type'] ) ) {
			$message_data['type'] = $action['promo_type'];
		}
		?>
		<div class="wp_attachment_details edit-form-section message-setting-fields">
			<p>
				<label for="message_type" class="message_label"><strong><?php _e( 'Type', 'icegram' ); ?></strong></label> 
				<select id="message_type" name="message_data[<?php echo $message_id; ?>][type]" class="message_type icegram_chosen_page">
				<?php foreach ( $message_types as $value => $label ) { 
					$selected = ( ( !empty( $message_data['type'] )  && esc_attr( $value ) == $message_data['type'] ) ) ? 'selected' : '';
					?>
					<option value="<?php echo esc_attr( $value ) ?>" <?php echo $selected; ?>><?php echo esc_html( $label ) ?></option>
				<?php } ?>
				</select>
			</p>

			<?php foreach ( $message_themes as $message_type => $message_theme ) { ?>
				<p class="message_row <?php echo $message_type; ?>">
					<label for="message_theme_<?php echo $message_type; ?>" class="message_label"><strong><?php _e( 'Theme', 'icegram' ); ?></strong></label> 
					<select id="message_theme_<?php echo $message_type; ?>" name="message_data[<?php echo $message_id; ?>][theme][<?php echo $message_type; ?>]" class="icegram_chosen_page message_theme message_theme_<?php echo $message_type; ?>">
						<?php foreach ( $message_theme as $value => $label ) { 
						$bg_img = "background-image: url(" . $icegram->plugin_url . "/assets/images/themes/" . $message_type . "/" . esc_attr( $value ) .".png)";
						?>
						<option style="<?php echo $bg_img; ?>" value="<?php echo esc_attr( $value ) ?>" class="<?php echo esc_attr( $value ) ?>" <?php echo ( !empty( $message_data['theme'][$message_type] )  && esc_attr( $value ) == $message_data['theme'][$message_type] ) ? 'selected' : ''; ?>><?php echo esc_html( $label ) ?></option>
					<?php } ?>
					</select>
				</p>
			<?php }	?>

			<p class="message_row messenger">
				<label for="message_animation" class="message_label"><strong><?php _e( 'Animation', 'icegram' ); ?></strong></label> 
				<select id="message_animation" name="message_data[<?php echo $message_id; ?>][animation]" class="icegram_chosen_page">
				<?php foreach ( $message_animation as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ) ?>" <?php echo ( !empty( $message_data['animation'] ) && esc_attr( $value ) == $message_data['animation'] ) ? 'selected' : ''; ?>><?php echo esc_html( $label ) ?></option>
				<?php } ?>
				</select>
			</p>
			<p class="message_row toast">
				<label for="message_toast_animation" class="message_label"><strong><?php _e( 'Animation', 'icegram' ); ?></strong></label> 
				<select id="message_toast_animation" name="message_data[<?php echo $message_id; ?>][toast_animation]" class="icegram_chosen_page">
				<?php foreach ( $message_toast_animation as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ) ?>" <?php echo ( !empty( $message_data['toast_animation'] ) && esc_attr( $value ) == $message_data['toast_animation'] ) ? 'selected' : ''; ?>><?php echo esc_html( $label ) ?></option>
				<?php } ?>
				</select>
			</p>
			<p class="message_row all_promo">
				<label for="message_title" class="message_label">
					<strong><?php _e( 'Headline', 'icegram' ); ?></strong>
					<span class="help_tip admin_field_icon" data-tip="<?php _e( 'Shown with highest prominence. Click on idea button on right to get a new headline.', 'icegram' ); ?>"></span>
				</label>
				<input type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][title]" id="message_title" value="<?php echo ( isset( $message_data['title'] ) ) ? $message_data['title'] : $default_message_title; ?>" data-headline="<?php echo $message_title_key; ?>" data-max="<?php echo ( count( $message_headlines ) - 1 ); ?>" />
				<a class="button message_title_button tips" data-tip="<?php _e( 'Give Me Another Headline', 'icegram' ); ?>">
					<span class="title-buttons-icon admin_field_icon"></span>
				</a>
			</p>
			<p class="message_row action-bar popup">
				<label for="message_label" class="message_label">
					<strong><?php _e( 'Button Label', 'icegram' ); ?></strong>
					<span class="help_tip admin_field_icon" data-tip="<?php _e( 'Your call to action text. Something unusual will increase conversions.', 'icegram' ); ?>"></span>
				</label>
				<input type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][label]" id="message_label" value="<?php if( isset( $message_data['label'] ) ) echo $message_data['label']; ?>" />
			</p>
			<p class="message_row all_promo">
				<label for="message_link" class="message_label">
					<strong><?php _e( 'Target Link', 'icegram' ); ?></strong>
					<span class="help_tip admin_field_icon" data-tip="<?php _e( 'Enter destination URL here. Clicking will redirect to this link.', 'icegram' ); ?>"></span>
				</label>
				<input type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][link]" id="message_link" value="<?php if( isset( $message_data['link'] ) ) echo $message_data['link']; ?>" />
			</p>
			<p class="message_row toast messenger">
				<label for="upload_image" class="message_label">
					<strong><?php _e( 'Icon / Avatar Image', 'icegram' ); ?></strong>
					<span class="help_tip admin_field_icon" data-tip="<?php _e( 'This image will appear in message content.', 'icegram' ); ?>"></span>
				</label>
				<input id="upload_image" type="text" class="message_field" name="message_data[<?php echo $message_id; ?>][promo_image]" value="<?php if( isset( $message_data['promo_image'] ) ) echo $message_data['promo_image']; ?>"/>
				<a class="button message_image_button tips" data-tip="<?php _e( 'Upload / Select an image', 'icegram' ); ?>" onclick="tb_show('<?php _e( 'Upload / Select Image' ); ?>', 'media-upload.php?type=image&TB_iframe=true', false);" >
					<span class="image-buttons-icon admin_field_icon"></span>
				</a>
			</p>
			<p class="message_row action-bar">
				<label for="message_bg_color" class="message_label"><strong><?php _e( 'Backgound Color', 'icegram' ); ?></strong></label>
				<input type="text" class="message_field color-field" name="message_data[<?php echo $message_id; ?>][bg_color]" id="message_bg_color" value="<?php if( isset( $message_data['bg_color'] ) ) echo $message_data['bg_color']; ?>" data-default-color="<?php if( isset( $this->default_message_color['bg_color'][$message_data['type']] ) ) echo $this->default_message_color['bg_color'][$message_data['type']]; ?>" />
			</p>
			<p class="message_row action-bar">
				<label for="message_text_color" class="message_label"><strong><?php _e( 'Text Color', 'icegram' ); ?></strong></label>
				<input type="text" class="message_field color-field" name="message_data[<?php echo $message_id; ?>][text_color]" id="message_text_color" value="<?php if( isset( $message_data['text_color'] ) ) echo $message_data['text_color']; ?>" data-default-color="<?php if( isset( $this->default_message_color['text_color'][$message_data['type']] ) ) echo $this->default_message_color['text_color'][$message_data['type']]; ?>" />
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
			<p class="message_row all_promo">
				<label for="message_message" class="message_label"><strong><?php _e( 'Message Body', 'icegram' ); ?></strong></label>
				<?php $message = ( !empty( $message_data['message'] ) ) ? $message_data['message'] : ''; ?>
				<?php wp_editor( $message, 'edit'.$message_id, $editor_args ); ?>
			</p>			
			<p class="message_row messenger action-bar toast position">
				<label for="message_position" class="message_label"><strong><?php _e( 'Position', 'icegram' ); ?></strong></label>
				<span class="message_field location-selector message_label">
					<input type="radio" id="radio01_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="00" <?php echo ( !empty( $message_data['position'] ) && "00" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio01_<?php echo $message_id;?>" title="Top Left">
						<span class="location toast top left" data-position="top left"></span>
					</label>
					<input type="radio" id="radio02_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="01" <?php echo ( !empty( $message_data['position'] ) && "01" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio02_<?php echo $message_id;?>" title="Top">
						<span class="location toast action-bar top" data-position="top"></span>
					</label>
					<input type="radio" id="radio03_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="02" <?php echo ( !empty( $message_data['position'] ) && "02" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio03_<?php echo $message_id;?>" title="Top Right">
						<span class="location toast top right" data-position="top right"></span>
					</label>
					<input type="radio" id="radio04_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="10" <?php echo ( !empty( $message_data['position'] ) && "10" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio04_<?php echo $message_id;?>" title="Middle Left">
						<span class="location middle left" data-position="middle left"></span>
					</label>
					<input type="radio" id="radio05_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="11" <?php echo ( !empty( $message_data['position'] ) && "11" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio05_<?php echo $message_id;?>" title="Middle">
						<span class="location toast middle middle" data-position="middle middle"></span>
					</label>
					<input type="radio" id="radio06_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="12" <?php echo ( !empty( $message_data['position'] ) && "12" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio06_<?php echo $message_id;?>" title="Middle Right">
						<span class="location middle right" data-position="middle right"></span>
					</label>
					<input type="radio" id="radio07_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="20" <?php echo ( !empty( $message_data['position'] ) && "20" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio07_<?php echo $message_id;?>" title="Bottom Left">
						<span class="location messenger toast bottom left" data-position="bottom left"></span>
					</label>
					<input type="radio" id="radio08_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="21" <?php echo ( !empty( $message_data['position'] ) && "21" == $message_data['position'] || !isset( $message_data['position'] ) ) ? 'checked' : ''; ?> />
					<label for="radio08_<?php echo $message_id;?>" title="Bottom">
						<span class="location toast action-bar bottom" data-position="bottom"></span>
					</label>
					<input type="radio" id="radio09_<?php echo $message_id;?>" name="message_data[<?php echo $message_id; ?>][position]" value="22" <?php echo ( !empty( $message_data['position'] ) && "22" == $message_data['position'] ) ? 'checked' : ''; ?> />
					<label for="radio09_<?php echo $message_id;?>" title="Bottom Right">
						<span class="location messenger toast bottom right" data-position="bottom right"></span>
					</label>
				</span>
			</p>

		</div>
		<input type="hidden" name="message_data[<?php echo $message_id; ?>][id]" value="<?php echo $message_id; ?>">
	 	<input type="hidden" class="message_id" name="message_id" value="<?php echo $message_id; ?>">
		<?php

		if( !empty( $action['message_id'] ) ) {
			
			?></div><?php
			
			if( !empty( $action['new_message_row'] ) && $action['new_message_row'] ) {
				\_WP_Editors::enqueue_scripts();
			    // print_footer_scripts();
			    \_WP_Editors::editor_js();
			}
			
		} else {
			
			?>
				<span class="shortcode_description admin_field_icon"></span>
			<?php 
			echo sprintf(__( 'You can insert <code>[%s]</code> wherever you want to show this message. We recommend running a campaign though.', 'icegram' ), 'icegram messages="' .$post->ID . '"' );

		}

	}

	//Used to save the settings which are being made in the message form and added to message page appropriately 
	public function update_message_settings( $post_id, $post ) {

		if (empty( $post_id ) || empty( $post ) || empty( $_POST )) return;
		if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) return;
		if (is_int( wp_is_post_revision( $post ) )) return;
		if (is_int( wp_is_post_autosave( $post ) )) return;
		if ( empty( $_POST['icegram_message_meta_nonce'] ) || ! wp_verify_nonce( $_POST['icegram_message_meta_nonce'], 'icegram_message_save_data' ) ) return;
		if (! current_user_can( 'edit_post', $post_id )) return;
		if ($post->post_type != 'message') return;

		update_post_meta( $post_id, 'icegram_message_data', $_POST['message_data'][$post_id] );
		
	}

	//Thickbox update settings
	function update_message_data() {

		parse_str( $_POST['message_data'], $post_data );

		if( !empty( $_POST['message_id'] ) ) {
			update_post_meta( $_POST['message_id'], 'icegram_message_data', $post_data['message_data'][$_POST['message_id']] );
		}		
		die();

	}

	function save_message_in_post_content( $post_data ) {

	    if( !empty( $_POST['post_type'] ) && $_POST['post_type'] == 'message' && !empty( $_POST['message_data'] ) ) {

			$message_id = $_POST['ID'];
			$post_data['post_content'] = $_POST['message_data'][$message_id]['message'];
			
			if( isset( $_POST['message_data'][$message_id]['post_title'] ) ) {

				if( !empty( $_POST['message_data'][$message_id]['post_title'] ) ) {
					$post_data['post_title'] = $_POST['message_data'][$message_id]['post_title'];
				} else {
					$post_data['post_title'] = $_POST['message_data'][$message_id]['title'];				
				}
				
			}

	    }

		return $post_data;

	}

	function icegram_all_message_type() {
		return $this->message_types;
	}

	function icegram_all_message_theme() {
		return $this->message_themes;
	}

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

$GLOBALS['wp_message'] = new WP_Message();