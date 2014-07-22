jQuery(function() {

	function display_message_themes(this_data) {
		var message_type 	= jQuery(this_data).find('.message_type option:selected').val();
		var message_theme 	= jQuery(this_data).find('.message_row.'+message_type).find('.message_theme').val();
		var message_thumb 	= jQuery(this_data).find('#message_theme_'+message_type).find('.'+message_theme).attr('style');

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

      //       	quicktags({id : 'edit'+response.id});
      //       	 tinyMCE.init({
				  //   skin : 'edit'+response.id
				  // });
      //       	tinymce.init(tinyMCEPreInit.mceInit['edit'+response.id]);

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

	jQuery('input#users_logged_in, input#users_all').on('change', function() {
		jQuery('select#users_roles').parent('p').slideToggle();
		if (jQuery(this).val() == 'logged_in') {
			jQuery('#users_roles_chosen').find('input').trigger('click');
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