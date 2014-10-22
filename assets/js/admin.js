jQuery(function() {

	function display_message_themes(this_data) {
		var message_type 	= jQuery(this_data).find('.message_type option:selected').val();
		var message_theme 	= jQuery(this_data).find('.message_row.'+message_type).find('.message_theme').val();
		var message_thumb 	= jQuery(this_data).find('#message_theme_'+message_type).find('.'+message_theme).attr('style');
        jQuery(this_data).find('#embed_form_but').hide();
		jQuery(this_data).find('.message_row, .location').hide();
		jQuery(this_data).find('.' + message_type).show();
		jQuery(this_data).find('.message_row.'+message_type).find('.message_theme').next().find('.chosen-single span').attr('style',message_thumb);

		if( jQuery(this_data).find('.message_body').parent().css('display') !== 'block' ) {
			jQuery(this_data).find('.message_body').parent().next('.wp-editor-wrap').hide();
		} else {
			jQuery(this_data).find('.message_body').parent().next('.wp-editor-wrap').show();
		}
	}

	function get_random_int(current, min, max) {
		var random_int = Math.floor(Math.random() * (max - min + 1)) + min;
		if ( random_int == current ) {
			return get_random_int( random_int, min, max );
		} else {
			return random_int;
		}
	}


	// Type box
	jQuery('#campaign_data').find('h3.hndle').hide();
	jQuery('.target_rules_desc').appendTo( '#campaign_target_rules h3.hndle span' );

	jQuery(document).ready(function() {
		var original_send_to_editor = window.send_to_editor;

		jQuery('#postdivrich').hide();
		jQuery('.color-field').wpColorPicker();
		hide_empty_campaign_message();
		jQuery('.message_edit:first').trigger('click');

		this_data = jQuery('.message_type option:selected').closest('.message-setting-fields');
		for (var i = 0; i < this_data.length; i++) {
			display_message_themes(this_data[i]);
		};

		jQuery('.message-setting-fields').live('change', '.message_type', function() {
			display_message_themes(this);
		});

		jQuery('.message-setting-fields').live('change', '.message_theme', function() {
			
			var message_type 	= jQuery(this).find('.message_type').val();
			var message_theme 	= jQuery(this).find('.message_row.'+message_type).find('.message_theme').val();
			var message_thumb 	= jQuery(this).find('#message_theme_'+message_type).find('.'+message_theme).attr('style');

			jQuery(this).find('.message_row.'+message_type).find('.message_theme').next().find('.chosen-single span').attr('style',message_thumb);

		});

		jQuery('.message_image_button').live('click', function(event) {
			var that = this;
			window.send_to_editor = function(html) {
				imgurl = jQuery('img', html).attr('src');
				jQuery(that).parent().find('#upload_image').val(imgurl);
				tb_remove();
				window.send_to_editor = original_send_to_editor;
			};
			return false;
		});

		jQuery('.message_headline_button').live('click', function() {
			var headline_key = jQuery(this).prev().attr('data-headline');
			var headline_max = icegram_writepanel_params.available_headlines.length;
			var new_headline_key = get_random_int( headline_key, 0, headline_max );
			var new_headline = icegram_writepanel_params.available_headlines[ new_headline_key ];
			jQuery(this).prev().val( new_headline );
		});

		jQuery(".tips, .help_tip").tipTip({'attribute' : 'data-tip'});

		jQuery('span.test_class').hover(function(){
			jQuery(this).next().show();
		}, function(){
			jQuery(this).next().hide();
		});

		// Disable closing message list
		jQuery('#campaign_data .hndle, #campaign_data .handlediv').unbind('click');
		jQuery('#campaign_data .handlediv').hide();

	});

	jQuery('.message_delete').live( 'click', function() {
		jQuery(this).parent().parent().next().remove();
		jQuery(this).parent().parent().remove();
		hide_empty_campaign_message();

	});

	jQuery('.message_edit').live( 'click', function() {
		jQuery(this).parent().parent().next().toggle();
		jQuery(this).parent().parent().find('.message-title-text, .message-title-input').toggle();	
	});

	jQuery('.message-title-input').live( 'change', function() {
		jQuery(this).prev().text(jQuery(this).val());
	});

	jQuery("select.ajax_chosen_select_messages").ajaxChosen({
		method: 'GET',
		url: icegram_writepanel_params.ajax_url,
		dataType: 'json',
		afterTypeDelay: 100,
		data: {
			action: 'icegram_json_search_messages',
			security: icegram_writepanel_params.search_message_nonce
		}
	}, function(data) {
		var terms = {};
		jQuery.each(data, function(i, val) {
			terms[i] = val;
		});
		return terms;
	});
	
	jQuery('.cancel_parse_form').live('click', function(event) {
		tb_remove();
		jQuery(this).closest('form').get(0).reset();
		return false;
	});

	jQuery('.parse_form').live('click', function(event) {
			var that = this;
			var parent_node = jQuery(that).closest('form');
			var form_layout = jQuery(parent_node).find('.embed_form_layouts input[type=radio]:checked').val();
			var form_width = jQuery(parent_node).find('#embed_form_width option:selected').val();
			var form_position = jQuery(parent_node).find('#embed_form_positions option:selected').val();
			// var form_container = jQuery('<div class="ig_embed_form_container"></div>').addClass(form_layout);
			var form_container = jQuery('<ul class="ig_embed_form_container"></ul>').addClass('ig_clear');
			var has_label = (jQuery(parent_node).find('.has_label_check input[type=checkbox]:checked').length > 0) ? true : false;
			var form_text = jQuery(parent_node).find('textarea#form_data').val().trim();

			var form_tags = jQuery('<div/>')
								.html(form_text)
								.find('input, label, select, textarea, button')
								.not('br'); // Get only these tags from the form

			if(jQuery(parent_node).find('.use_cta_check input[type=checkbox]:checked').length > 0){
				form_tags = form_tags.not('input[type=submit]');
				form_tags = form_tags.not('button[type=submit]');
			}
			var form_html = '';
			var form_object = jQuery('<div/>')
							.html(form_text)
							.find('form')
							.removeAttr('class')
							.addClass('ig_embed_form')
							.addClass(form_layout)
							.addClass(form_width)
							.addClass(form_position)
							.addClass('ig_clear')
							.empty();
			var label_text = null;
			var el_count = 0;
			jQuery.each(form_tags, function(i, form_el){
				var el_obj = jQuery(form_el);
				var el_group = jQuery('<li class="ig_form_el_group"></li>');
					el_obj.removeAttr('class').removeAttr('style');
				// For now : we are hiding fields with tabindex -1
				if(el_obj.attr('tabindex') == -1){
					el_obj.addClass('ig_detected_bot_fields');
					el_count--;
				}
				if(el_obj.is('label')){
					label_text = el_obj.not('input, select, textarea, button, span, br').text().replace(/\s+/g, ' ');
				}else if((el_obj.is('input') || el_obj.is('button') || el_obj.is('textarea')) && !el_obj.is('input[type=radio]') ) {
					el_obj.removeAttr('id');
					if(el_obj.is('button')){
						var button_text = el_obj.not('br, span, div').text().trim() || '';
						el_obj.remove();
						el_obj = jQuery('<input type="submit">');
						if(button_text){
							el_obj.attr('value',button_text );
						}
					}

					if(has_label){
						el_obj.removeAttr('placeholder');
						if(label_text){
							jQuery('<label>' + label_text + '</label>').appendTo(el_group);
							label_text = null;
						}
					}else {
						if(label_text){
							el_obj.attr('placeholder', label_text);
							label_text = null;
						}
					}
					el_group.append(el_obj);
					if(el_obj.is('textarea')){
						el_group.append(el_obj).addClass('ig_form_el_textarea');
					}else{
						el_group.append(el_obj).addClass('ig_form_el_input');
					}
					form_container.append(el_group);
					el_count++;
				}else if(el_obj.is('select')) {
					if(label_text){
						if(has_label){
							jQuery('<label>' + label_text + '</label>').appendTo(el_group);
						}else{
							jQuery('<option>' + label_text + '</option>').prependTo(el_obj);
						}
						label_text = null;	
					}
					el_group.append(el_obj).addClass('ig_form_el_select');
					form_container.append(el_group);
					el_count++;
				}else if(el_obj.is('input[type=radio]') ) {
					if(label_text){
						jQuery('<label>' + label_text + '</label>').prepend(el_obj).appendTo(el_group);
						label_text = null;
					}
					el_group.addClass('ig_form_el_radio');
					form_container.append(el_group);
					el_count++;
				}
			});
			if(form_layout == 'ig_horizontal'){
				var max_el = (form_width == 'ig_full') ? 4 : ((form_width == 'ig_half') ? 2  : 1);
				el_count = (el_count > max_el ) ? max_el : el_count;
				var li_width = (100 - el_count) / el_count;
				form_container
					.find('input, select, textarea')
					.not('input[type=submit]')
					.not('input[type=radio]')
					.parent()
					.css('width', li_width + '%' );
				form_container
					.find('input[type=radio]')
					.parent()
					.parent()
					.css('width', li_width + '%' );
			}
			form_container.find('.ig_detected_bot_fields').parent().css('display', 'none');
			form_object.append(form_container);
			tb_remove();
			// reset all fields of Embed form setting
			jQuery(that).closest('form').get(0).reset();
			window.send_to_editor(jQuery('<div/>').append(form_object).html());
			return false;
		});
	

	var message_rows = jQuery('.message-row').length;
	jQuery('.ajax_chosen_select_messages').chosen().on('change', function() {
		var message_id = jQuery(this).val();
		if( message_id == '' ) {			
			jQuery(".ajax_chosen_select_messages").val('').trigger("chosen:updated");
			return;
		}

		jQuery('.message-edit-row').hide();
		jQuery('.message-title-text').show();
		jQuery('.message-title-input').hide();
		jQuery.ajax({
			method: 'POST',
			url: icegram_writepanel_params.ajax_url,
			dataType: 'json',
			data: {
				action: 'get_message_action_row',
				message_id: message_id,
				row: message_rows
			},
			success: function(response) {

				message_rows++;
				jQuery('.messages-list .messages_list_table tbody').append(response.main);
				jQuery('.color-field').wpColorPicker();
				display_message_themes(jQuery('#'+response.id));				
				jQuery(".ajax_chosen_select_messages").val('').trigger("chosen:updated");
				jQuery("select.icegram_chosen_page").chosen({
					disable_search_threshold: 10
				});
				hide_empty_campaign_message();
				jQuery('.message-setting-fields').trigger('change');
				jQuery(".tips, .help_tip").tipTip({'attribute' : 'data-tip'});
				QTags._buttonsInit();
            	//quicktags({id : 'edit'+response.id});
      //       	 tinyMCE.init({
				  //   skin : 'edit'+response.id
				  // });
            	//tinymce.init(tinyMCEPreInit.mceInit['edit'+response.id]);

			}
		});
	});
	
	function hide_empty_campaign_message() {
		if( jQuery('.message-row').length == 0 ) {
			jQuery('.empty_campaign').show();
		} else {
			jQuery('.empty_campaign').hide();
		}
	}

	jQuery('select.ajax_chosen_select_messages').next('div').find('div.chosen-drop').live('click', function() {
		jQuery(this).closest('h3.hndle').trigger('click');
	});

	jQuery('.campaign_preview').on( 'click', function(event) {
		jQuery(this).closest('h3.hndle').trigger('click');
		
		if( jQuery('.message-row').length == 0 )
			return;

		// Change action
		params = jQuery("#post").serializeArray();
		params.push( {name: 'action', value: 'save_campaign_preview' });

		jQuery.ajax({
			method: 'POST',
			async: false,
			url: icegram_writepanel_params.ajax_url,
			data: params,
			success: function(response) {
				if (response != '') {
					window.open(response, 'preview_window');
				}
			}
		});
	
	});

	jQuery("select.icegram_chosen_page").chosen({
		disable_search_threshold: 10
	});

	jQuery('input#users_logged_in, input#users_all ,input#users_not_logged_in').on('change', function() {
		if (jQuery(this).val() == 'logged_in') {
		    jQuery('select#users_roles').parent('p').show();
			jQuery('#users_roles_chosen').find('input').trigger('click');
		}else{
		    jQuery('select#users_roles').parent('p').hide();
		}
	});
	
	jQuery('.schedule_rule').on('change', function() {
		if (jQuery(this).attr('id') == "when_schedule") {
			jQuery('#date_picker').show();
		} else {
			jQuery('#date_picker').hide();
		}
	});

	jQuery('input#where_other_page').on('change', function() {
		jQuery('select#where_page_id').parent('p').slideToggle();
		if (jQuery(this).is(':checked')) {
			jQuery('#where_page_id_chosen').find('input').trigger('click');
		}
	});
	jQuery('input#where_sitewide').on('change', function() {
		jQuery('select#exclude_page_id').parent('p').slideToggle();
		if (jQuery(this).is(':checked')) {
			jQuery('#exclude_page_id_chosen').find('input').trigger('click');
		}
	});

	jQuery('.date-picker').datepicker({
		dateFormat: 'yy-mm-dd',
		defaultDate: 0,
		showOtherMonths: true,
		selectOtherMonths: true,
		changeMonth: true,
		changeYear: true,
		showButtonPanel: true
	});
});