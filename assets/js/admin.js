jQuery(function() {
	var src = window.location.href;
	var home_url = src.slice(0,src.indexOf('wp-admin'));
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

		jQuery('.inside').on('change', '.message_type', function(e) {
			var t = jQuery(e.target).parents('.message-setting-fields');
			display_message_themes(t);
		});

		jQuery('#campaign_data').on('change', '.message_theme', function(e) {
			var t = jQuery(e.target).parents('.message-setting-fields');
			var message_type 	= jQuery(t).find('.message_type').val();
			var message_theme 	= jQuery(t).find('.message_row.'+message_type).find('.message_theme').val();
			var message_thumb 	= jQuery(t).find('#message_theme_'+message_type).find('.'+message_theme).attr('style');
			jQuery(t).find('.message_row.'+message_type).find('.message_theme').next().find('.chosen-single span').attr('style',message_thumb);

		});

	    jQuery('#campaign_data').on('click', '.message_image_button', function(event) {
			var that = this;
			window.send_to_editor = function(html) {
				imgurl = jQuery('img', html).attr('src');
				jQuery(that).parent().find('#upload_image').val(imgurl);
				tb_remove();
				window.send_to_editor = original_send_to_editor;
			};
			return false;
		});

		jQuery('#campaign_data').on('click','.message_headline_button', function() {
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
	jQuery('#campaign_data').on('click','.message_delete', function() {
		jQuery(this).parent().parent().next().remove();
		jQuery(this).parent().parent().remove();
		hide_empty_campaign_message();

	});

	jQuery('#campaign_data').on( 'click', '.message_edit' ,function() {
		jQuery(this).parent().parent().next().toggle();
		jQuery(this).parent().parent().find('.message-title-text, .message-title-input').toggle();	
	});

	jQuery('#campaign_data').on( 'change', '.message-title-input',function() {
		jQuery(this).prev().text(jQuery(this).val());
	});

	jQuery("select.ajax_chosen_select_messages").ajaxChosen({
		type: 'GET',
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

	jQuery(document).on('click', '#embed_form_but', function(event) {
		jQuery.magnificPopup.open({ 
	            items: {
	            src: '#popup_container',
	            type: 'inline'
	            },
	            modal : true
			});

	});
	           
	jQuery(document).on('click', '.cancel_parse_form', function(event) {
		jQuery.magnificPopup.close({ items: {
            src: '#popup_container',
            type: 'inline'
        }});
		jQuery(this).closest('form').get(0).reset();
		return false;
	});

	jQuery(document).on('click', '.parse_form' ,function(event) {
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
				// For now : we are hiding fields with tabindex -1 and hidden fields
				if(el_obj.attr('tabindex') == -1 || el_obj.attr('type') == 'hidden'){
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
					.not('input[type=hidden]')
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

			//closing a popup
			jQuery.magnificPopup.close({ items: {
	            src: '#popup_container',
	            type: 'inline'
	        }});
			// reset all fields of Embed form setting
			jQuery(that).closest('form').get(0).reset();
			window.send_to_editor(jQuery('<div/>').append(form_object).html());
			return false;
		});
	

	//var message_rows = jQuery(this).parent().siblings('.campaign_target_rules_panel').find('.message-row').length;
	jQuery('.ajax_chosen_select_messages').chosen();
	jQuery('#campaign_data').on('change', '.ajax_chosen_select_messages' , function() {
		var selected_tab = jQuery('#ig-tabs li.current').attr('variation_id');
		var newSettings = jQuery.extend( {}, tinyMCEPreInit.mceInit[ 'content' ] );
		var newQTS = jQuery.extend( {}, tinyMCEPreInit.qtInit[ 'content' ] );
		var parent_campaign_box = jQuery(this).parent().siblings('.campaign_target_rules_panel');
		var message_rows = jQuery(parent_campaign_box).find('.message-row').length;
		var message_id = jQuery(this).val();
		if( message_id == '' ) {
			jQuery(".ajax_chosen_select_messages").val('').trigger("chosen:updated");
			return;
		}

		jQuery('.message-edit-row').hide();
		jQuery('.message-title-text').show();
		jQuery('.message-title-input').hide();
		jQuery.ajax({
			type: 'POST',
			url: icegram_writepanel_params.ajax_url,
			dataType: 'json',
			data: {
				action: 'get_message_action_row',
				message_id: message_id,
				row: message_rows
			},
			success: function(response) {

				message_rows++;
				//jQuery('.messages-list .messages_list_table tbody').append(response.main);
				jQuery(parent_campaign_box).find('.messages-list .messages_list_table tbody').append(response.main);
				jQuery('.color-field').wpColorPicker();
				display_message_themes(jQuery('#'+response.id));				
				jQuery(".ajax_chosen_select_messages").val('').trigger("chosen:updated");
				jQuery("select.icegram_chosen_page").chosen({
					disable_search_threshold: 10
				});
				hide_empty_campaign_message();
				jQuery('.message-setting-fields').trigger('change');
				jQuery(".tips, .help_tip").tipTip({'attribute' : 'data-tip'});
				// text editor issue fix
				if ( typeof( tinyMCEPreInit.mceInit[ 'edit'+response.id ] ) === 'undefined' ) {
					for ( _prop in newSettings ) {
						if ( 'string' === typeof( newSettings[_prop] ) ) {
							if(_prop !== 'content_css'){
								newSettings[_prop] = newSettings[_prop].replace( new RegExp( 'content', 'g' ), 'edit'+response.id );
							}
						}
					}
					tinyMCEPreInit.mceInit[ 'edit'+response.id ] = newSettings;
				}
				if ( typeof( tinyMCEPreInit.qtInit[ 'edit'+response.id ] ) === 'undefined' ) {
					for ( _prop in newQTS ) {
						if ( 'string' === typeof( newQTS[_prop] ) ) {
							if(_prop !== 'content_css'){
								newQTS[_prop] = newQTS[_prop].replace( new RegExp( 'content', 'g' ), 'edit'+response.id );
							}
						}
					}
					tinyMCEPreInit.qtInit[ 'edit'+response.id ] = newQTS;
				}
				tinyMCE.init({ id : tinyMCEPreInit.mceInit[ 'edit'+response.id ]});
            	quicktags({id : 'edit'+response.id});
				QTags._buttonsInit();
				if(jQuery('#wp-edit'+response.id+'-wrap').hasClass('tmce-active')){
					jQuery('#edit'+response.id+'-tmce').click();
				}else{
					jQuery('#edit'+response.id+'-html').click();
				}
				if(typeof(selected_tab) !== 'undefined'){
						jQuery( window ).trigger( "icegram_tab_selected" ,[selected_tab]);
				}
			}
		});
	});
    //add local url
	jQuery('#campaign_target_rules').on('click', '#add_local_url_row' ,function(e) {
		e.preventDefault();
		var row = add_url_row();
		if(jQuery('.local_url').find('.url_input_field').length){
			jQuery(row).insertAfter(jQuery('.local_url').find('.url_input_field').last().parent('span'));
		}else{
			jQuery(row).insertBefore(jQuery('.local_url').find('#add_local_url_row_label'));
			
		}

	});
	jQuery('#campaign_target_rules').on('click', '.delete-url',function(e) {
		jQuery(this).parent().remove();
	});
	
	function add_url_row(){
		var row = '<span><label class="options_header"><span id="valid-field"> </span></label> <input  type="text" class="url_input_field" data-option="local_url"  name="campaign_target_rules[local_urls][]" value="'+home_url+'*"/><span class="delete-url"></span></span>';
		return row;
	}
	function hide_empty_campaign_message() {
		if( jQuery('.message-row').length == 0 ) {
			jQuery('.empty_campaign').show();
		} else {
			jQuery('.empty_campaign').hide();
		}
	}

	jQuery('select.ajax_chosen_select_messages').next('div').on('click', 'div.chosen-drop' ,function() {
		jQuery(this).closest('h3.hndle').trigger('click');
	});

	jQuery('#campaign_data').on( 'click','.campaign_preview' ,function(event) {
		jQuery(this).closest('h3.hndle').trigger('click');
		
		if( jQuery('.message-row').length == 0 )
			return;
		// trigger event for saving visual content
		tinyMCE.triggerSave();
		// Change action
		params = jQuery("#post").serializeArray();
		params.push( {name: 'action', value: 'save_campaign_preview' });

		jQuery.ajax({
			type: 'POST',
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
	jQuery('input#where_local_url').on('change', function() {
		jQuery('.local_url').slideToggle();
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
	
	jQuery('#campaign_target_rules').on('focusout','input.url_input_field',function() {
        var url = this;
		jQuery(url).parent().find('span#valid-field').removeClass('error');	
		if(jQuery(url).data("option") !== 'undefine' && jQuery(url).data("option") == 'local_url' && jQuery(url).val() != '*'){
			var url_val = url.value;
			if(url_val.indexOf(home_url) < 0){
				jQuery(url).val(home_url + url_val);	
				return;	
			}
		}
	});
});