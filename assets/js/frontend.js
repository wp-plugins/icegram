jQuery(function() {

	if (typeof icegram_data === 'undefined') {
		return;
	}

	var icegram_default = icegram_data['icegram_default'];
	var icegram_event = [];
	for (var i = 0; i < icegram_data['messages'].length; i++) {
		display_icegram_message( icegram_data['messages'][i] );
	};

	function display_icegram_message(icegram, theme) {
		
		if (typeof(theme) != 'undefined') {

			icegram_preview = {};
			for (var i = 0; i < icegram.length; i++) {
				if ((icegram[i].name).indexOf("message_data") >= 0) {
					var name = (icegram[i].name).replace('message_data['+jQuery('#post_ID').val()+'][', '').replace(']', '').replace( jQuery('#post_ID').val(), '');
					icegram_preview[name] = icegram[i].value;
				}
			}

			icegram = icegram_preview;
			icegram.delay_time = 0;
			icegram.theme = theme;
		}

		var delay_time = icegram.delay_time * 1000;
		var event_data = {};

		// Powered by links
		var ig_powered_by_link = 'http://www.icegram.com/?utm_source=inapp&utm_campaign=poweredby&utm_medium=';

		if (icegram.type == "toast") {
			
			var th = null, cf = null;
			var	toast = function(icegram) {
				var popup_box 	= '#toast_' + icegram.id;
				var type 		= icegram['theme']['toast'];
				var width 		= 300;
				var duration 	= 10000;
				var sticky 		= false;

				if( icegram.position == "10" || icegram.position == "12" ) {
					icegram.position = '20';
				}

				cf = toast.config;
				if (!(jQuery('ul#' + icegram.position).length)) {
					th = jQuery('<ul id="' + icegram.position + '"></ul>').addClass('toast-block').appendTo(document.body).hide();
					th.width(width);

					if (icegram.position == "00") {
						th.css({top: '0', left: '0'});
					} else if (icegram.position == "01") {										
						th.css({top: '0', left: '50%', margin: '5px 0 0 -' + (width / 2) + 'px'});
					} else if (icegram.position == "02") {										
						th.css({top: '0', right: '0'});
					} else if (icegram.position == "20") {										
						th.css({bottom: '0', left: '0'});
					} else if (icegram.position == "21") {										
						th.css({bottom: '0', left: '50%', margin: '5px 0 0 -' + (width / 2) + 'px'});
					} else if (icegram.position == "22") {										
						th.css({bottom: '0', right: '0'});
					} else if (icegram.position == "11") {										
						th.css({top: '50%', left: '50%', margin: '-'+(width / 2) +'px 0 0 -' + (width / 2) + 'px'});
					}

				} else {
					th = jQuery('ul#' + icegram.position);
				}
				if (jQuery(popup_box).length) {

					if( typeof(promo_theme) == 'undefined' ) {
						jQuery(popup_box + ' li').addClass(icegram['theme']['toast']);
					}
			        else {			        	
						jQuery(popup_box + ' li').addClass(icegram.theme);
			        }
					jQuery(popup_box + ' li').addClass(icegram.toast_animation);
					jQuery(popup_box + ' li').attr('link', icegram.link);
					jQuery(popup_box + ' li').find('.toast-title').html(icegram.title);
					jQuery(popup_box + ' li').find('.toast-message').html(icegram.message);
					if( icegram.promo_image != '' ) {
						jQuery(popup_box + ' li').find('img').attr('src', icegram.promo_image);
					} else {
						jQuery(popup_box + ' li').find('img').attr('src', icegram_default.default_promo_image);
					}

					var ti = jQuery(popup_box + ' li').appendTo(th);
					jQuery(popup_box).remove();
					var cb = ti,
						to = null;
				} else {
					return;
				}

				ti.addClass(icegram['theme']['toast']);
				!th.hasClass('active') && th.addClass('active').show();
				ti.fadeIn('slow');

				!icegram_data.preview_id && icegram_track_event_type('shown', icegram);

				!sticky && duration > 0 && (to = setTimeout(function() {
					clearTimeout(to);
					ti.animate({
						height: 0,
						opacity: 0
					}, 'fast', function() {
						ti.remove();
						th.children().length || th.removeClass('active').hide();
					});

				}, duration));
			};

			jQuery('#toast_' + icegram.id).hide();
			setTimeout(function() {

				toast(icegram);
				jQuery('ul.toast-block').on('click', 'li', function() {
					if( jQuery(this).attr('link') == '' ) {
						jQuery(this).remove();
						return;				
					}
					!icegram_data.preview_id && icegram_track_event_type('clicked', icegram);
					//window.open(jQuery(this).attr('link'), "_parent");
					window.location.href = jQuery(this).attr('link');
				});

			}, delay_time);
			// Toast Message End
		} else if (icegram.type == "action-bar") {

			var popup_box = '#action_bar_' + icegram.id;
			var popup_box_close = '#action_bar_close_' + icegram.id;

	        if( typeof(promo_theme) == 'undefined' )
	            var theme       = icegram['theme']['action-bar'];
	        else
	            var theme       = icegram.theme;

			jQuery(popup_box).addClass(theme);
			jQuery(popup_box).find('.heading').html(icegram.title);
			jQuery(popup_box).find('.message').prepend(icegram.message);
			jQuery(popup_box).find('.popup_button').attr('value', icegram.link);
			jQuery(popup_box).find('.popup_close').css('background-color', icegram.bg_color);
			jQuery(popup_box).find('.content').css('color', icegram.text_color);
			jQuery(popup_box).css('background-color', icegram.bg_color);

			if( icegram_default.powered_by_logo != '' ) {
				if( theme != 'air-mail' ) {
					jQuery(popup_box).find('.content').before('<div class="powered_by" ><a href="'+ig_powered_by_link+icegram.type+'" target="_blank"><img src="'+icegram_default.powered_by_logo+'" title="'+icegram_default.powered_by_text+'"/></a></div>');
				} else {
					jQuery(popup_box).find('.content').prepend('<div class="powered_by" ><a href="'+ig_powered_by_link+icegram.type+'" target="_blank"><img src="'+icegram_default.powered_by_logo+'" title="'+icegram_default.powered_by_text+'"/></a></div>');
				}
				//jQuery(popup_box).find('.data').css({'width':'75%', 'max-width':'75%'});
			}

			if( theme == 'hello' ) {
				var popup_image = jQuery(popup_box).find('.popup_box_image');
				var popup_bottom = jQuery(popup_box).find('.popup_button');
				jQuery(popup_box).find('.data').prepend(popup_image);
				jQuery(popup_box).find('.data').append(popup_bottom);
			}
			if ( theme == 'air-mail') {
				jQuery(popup_box).find('.content').css('background-color', icegram.bg_color);
			}

			if( icegram.label == '' ) {
				jQuery(popup_box).find('.popup_button').hide();
			} else {
				jQuery(popup_box).find('.popup_button').html(icegram.label);
			}

			if (icegram.position == "00" || icegram.position == "01" || icegram.position == "02" || icegram.position == "10" || icegram.position == "11" ) {

				jQuery(popup_box).addClass('top');
				var popup_close_block 	= jQuery('.action_bar_' + icegram.id).find('.popup_close');
				var popup_template 		= jQuery('.action_bar_' + icegram.id);
				jQuery(popup_template).find('.popup_close').remove();
				jQuery(popup_template).find('.content').prepend(popup_close_block);
				jQuery(popup_box).parent('.action_bar_' + icegram.id).remove();
				jQuery('body').prepend(popup_template);
				
				function load_popup_on_top(popup_box, delay_time) {

					jQuery(popup_box).delay(delay_time).animate({
						marginTop: -jQuery(popup_box).outerHeight()
					}, 0, "linear", function() {
						jQuery(popup_box).show();
						jQuery(popup_box).find('.bar_close').show();
						jQuery(popup_box).find('.bar_open').hide();
						jQuery(popup_box).find('.popup_close').addClass('open').removeClass('border').css('position', 'initial');
						// jQuery(popup_box_close).css('margin-top', (jQuery(popup_box).find('.content').height()-jQuery(popup_box).find('.popup_close').height() - 5)+"px" );					

						!icegram_data.preview_id && icegram_track_event_type('shown', icegram);
					});
					jQuery(popup_box).animate({
						marginTop: 0
					}, 300);
				}

				load_popup_on_top(popup_box, delay_time);

				jQuery(popup_box).on('click', '.bar_close, .close', function() {

					var popup_id = "#" + jQuery(this).parent().parent().parent().attr('id');
					jQuery(popup_id).animate({
						marginTop: -jQuery(popup_id).outerHeight()
					}, 300, "linear", function() {
	                        
						jQuery(popup_box_close).css('margin-top', '0' );
						jQuery(popup_id).find('.bar_close').hide();
						jQuery(popup_id).find('.bar_open').show();
						jQuery(popup_id).find('.popup_close').removeClass('open').addClass('border').css({
							'position': 'absolute',
							'top': '0'
						});
						!icegram_data.preview_id && icegram_track_event_type('closed', icegram);
					});
				});
				jQuery(popup_box_close).on('click', '.bar_open', function() {
					load_popup_on_top("#" + jQuery(this).parent().parent().parent().attr('id'), 0);
				});

			} else if (icegram.position == "12" || icegram.position == "20" || icegram.position == "21" || icegram.position == "22" ) {

				jQuery(popup_box).addClass('bottom');
				jQuery(popup_box).css('position', 'fixed');

				function load_popup_on_bottom(popup_box, delay_time) {
					jQuery(popup_box).delay(delay_time).animate({
						bottom: 0,
						marginBottom: -jQuery(popup_box).outerHeight()

					}, 0, "linear", function() {
						jQuery(popup_box).show();
						jQuery(popup_box).find('.bar_close').hide();
						jQuery(popup_box).find('.bar_open').show();
						jQuery(popup_box).find('.popup_close').addClass('open').removeClass('border').css('position', 'initial');

						!icegram_data.preview_id && icegram_track_event_type('shown', icegram);
					});
					jQuery(popup_box).animate({
						marginBottom: 0
					}, 300);
				}
	            
				load_popup_on_bottom(popup_box, delay_time);

				jQuery(popup_box).on('click', '.bar_open, .close', function() {

					var popup_id = "#" + jQuery(this).parent().parent().parent().attr('id');
					jQuery(popup_id).animate({
						marginBottom: -jQuery(popup_id).outerHeight()
					}, 300, "linear", function() {
	                        
						jQuery(popup_id).find('.bar_close').show();
						jQuery(popup_id).find('.bar_open').hide();
						jQuery(popup_id).find('.popup_close').removeClass('open').addClass('border').css({
							'position': 'fixed',
							'bottom': '0'
						});
						!icegram_data.preview_id && icegram_track_event_type('closed', icegram);						
					});
				});

				jQuery(popup_box_close).on('click', '.bar_close', function() {
					load_popup_on_bottom("#" + jQuery(this).parent().parent().parent().attr('id'), 0);
				});
			}

			jQuery(popup_box).on('click', '.popup_button', function() {
				!icegram_data.preview_id && icegram_track_event_type('clicked', icegram);				
				if( jQuery(this).attr('value') == '' ) {
					jQuery(popup_box+' .close').trigger('click');
					return;
				} else {
					//window.open(jQuery(this).attr('value'), "_parent");
					window.location.href = jQuery(this).attr('value');
				}
			});
			// Action Bar End
		} else if (icegram.type == "messenger") {
	        
			var popup_box = '#popup_box_' + icegram.id;
			var popup_box_close = '#popup_box_close_' + icegram.id;

			if (typeof(promo_theme) == 'undefined') {
				var theme = icegram['theme']['messenger'];
			} else {
				var theme = icegram.theme;
			}

			jQuery(popup_box).addClass(theme);
			if( icegram_default.powered_by_text != '' ) 	{
				jQuery(popup_box).find('.popup_box_main').after('<div class="powered_by"><a href="'+ig_powered_by_link+icegram.type+'" target="_blank">'+icegram_default.powered_by_text+'</a></div>');		
			}
			jQuery(popup_box).find('.popup_box_heading').html(icegram.title);
			// jQuery(popup_box).find('.popup_box_body').html(icegram.message.replace(/\n/g, '<br />'));
			jQuery(popup_box).find('.popup_box_message').html(icegram.message);
			jQuery(popup_box).find('.popup_box_main').attr('value', icegram.link);

			if( icegram.promo_image != '' ) {
				jQuery(popup_box).find('.popup_box_body').prepend('<img class="popup_icon" src="'+icegram.promo_image+'"/>')
			} else {
				jQuery(popup_box).find('.popup_box_message').addClass('no-image');
			}
			jQuery(popup_box).click(function(event) {
	            if (jQuery(event.target).filter('.popup_box_close').length)
	                return;
				
				!icegram_data.preview_id && icegram_track_event_type('clicked', icegram);
				if( jQuery(this).find('.popup_box_main').attr('value') == '' ) {
					jQuery(popup_box_close).trigger('click');
					return;
				} else {
					//window.open(jQuery(this).find('.popup_box_main').attr('value'), "_parent");
					window.location.href = jQuery(this).find('.popup_box_main').attr('value');
				}
			});
	        
			if (icegram.position == "20" || icegram.position == "00" || icegram.position == "10" || icegram.position == "01" ) {
				var left_pos = 0;
			} else if (icegram.position == "22" || icegram.position == "11" || icegram.position == "21" || icegram.position == "02" || icegram.position == "12") {
				var left_pos = jQuery(window).width() - jQuery(popup_box).outerWidth();
			}

			if (icegram.animation == "slide") {

				jQuery(popup_box).delay(delay_time).animate({
					bottom: 0,
					left: left_pos,
					height: "toggle"
				}, 0);

				jQuery(popup_box).animate({
					height: "toggle",
					opacity: "toggle"
				}, 1500);
				!icegram_data.preview_id && icegram_track_event_type('shown', icegram);
				jQuery(popup_box_close).click(function() {
					jQuery("#" + jQuery(this).parent().attr('id')).animate({
						height: "toggle",
						opacity: 0
					}, 500, function() {
						jQuery("#" + jQuery(this).parent().attr('id')).hide("slide", {
							direction: "down"
						}, 100);
						!icegram_data.preview_id && icegram_track_event_type('closed', icegram);						
					});
				});
	            
			} else if (icegram.animation == "appear") {

				jQuery(popup_box).delay(delay_time).animate({
					bottom: 0,
					left: left_pos,
					opacity: 0
				}, 0, "linear", function() {
					jQuery(popup_box).show();
				});
				jQuery(popup_box).animate({
					opacity: 1
				}, 1500);
				!icegram_data.preview_id && icegram_track_event_type('shown', icegram);
				jQuery(popup_box_close).click(function() {
					var id = jQuery("#" + jQuery(this).parent().attr('id'));
					jQuery(id).animate({
						opacity: 0
					}, 500, "linear", function() {
						jQuery(id).hide();
						!icegram_data.preview_id && icegram_track_event_type('closed', icegram);						
					});
				});
			}
			// Messenger End
		} else if (icegram.type == "popup") {

			var popup_box = '#popup_box_' + icegram.id;
			var popup_box_close = '#popup_box_close_' + icegram.id;
			jQuery('#popup_main_' + icegram.id).hide();

	        if( typeof(promo_theme) == 'undefined' )
	            var theme       = icegram['theme']['popup'];
	        else
	            var theme       = icegram.theme;

	        function popup_resize() {
	        	if( jQuery( window ).height() < jQuery( '#TB_window' ).height() || jQuery( window ).width() < jQuery( '#TB_window' ).width() ) {

					var height 	= (jQuery( window ).height()-50)/(jQuery( '#TB_window' ).height());
					var width 	= (jQuery( window ).width()-50)/(jQuery( '#TB_window' ).width());
					if(height<width){
						jQuery('#TB_window').css({'-webkit-transform': 'scale('+height+')',
													'-moz-transform': 'scale('+height+')',
													'-ms-transform': 'scale('+height+')',
													'-o-transform': 'scale('+height+')',
													'transform': 'scale('+height+')'});
					} else {
						jQuery('#TB_window').css({'-webkit-transform': 'scale('+width+')',
													'-moz-transform': 'scale('+width+')',
													'-ms-transform': 'scale('+width+')',
													'-o-transform': 'scale('+width+')',
													'transform': 'scale('+width+')'});
					}
				} else {
					jQuery('#TB_window').css({'-webkit-transform': 'scale(1)',
												'-moz-transform': 'scale(1)',
												'-ms-transform': 'scale(1)',
												'-o-transform': 'scale(1)',
												'transform': 'scale(1)'});
				}
	        }

	        jQuery( window ).resize(function() {				
	        	popup_resize();
			});

			jQuery(popup_box).find('.popup-headline').html(icegram.title);
			jQuery(popup_box).find('.popup-message').html(icegram.message);
			jQuery(popup_box).find('.popup-button').attr('value', icegram.link);
			jQuery(popup_box).find('.popup-button').html(icegram.label);
			if( icegram.label == '' ) {
				jQuery(popup_box).find('.popup-button').hide();
			}
			if( icegram.promo_image ) {			
				jQuery(popup_box).find('.popup-icon').attr('src', icegram.promo_image);
			} else {
				jQuery(popup_box).find('.popup-image').hide();
				jQuery(popup_box).find('.popup-message').css({'width':'100%', 'margin-bottom': '0'});
			}

			setTimeout(function() {

				var popup_delay = 0;
				if( jQuery('body').find('#TB_window').length ) {				
					var popup_delay = 800;
					tb_remove();
				}

				setTimeout( function() {
					var popup_width = (jQuery(window).width() * 60) / 100;
					tb_show('Popup', "#TB_inline?width="+popup_width+"&modal=true&inlineId=popup_main_" + icegram.id, true);
					jQuery(popup_box).parent().parent().addClass(theme);
					if( icegram_default.powered_by_text != '' )	{
						jQuery('#TB_window').prev().append('<div class="powered_by"><a href="'+ig_powered_by_link+icegram.type+'" target="_blank">'+icegram_default.powered_by_text+'</a></div>');
					}
					popup_resize();
					!icegram_data.preview_id && icegram_track_event_type('shown', icegram);
				},popup_delay);
				
			}, delay_time);

			jQuery(popup_box_close).on('click', function() {
				self.parent.tb_remove();
				!icegram_data.preview_id && icegram_track_event_type('closed', icegram);					
				setTimeout( function() {
					jQuery(popup_box).hide();
				},500);
			});
			jQuery(popup_box).on('click', '.popup-button', function() {
				!icegram_data.preview_id && icegram_track_event_type('clicked', icegram);
				if( jQuery(this).attr('value') == '' ) {
					jQuery(popup_box_close).trigger('click');
					return;
				} else {					
					//window.open(jQuery(this).attr('value'), "_parent");
					window.location.href = jQuery(this).attr('value');
				}
			});

		}
		// Popup End
	} // end display_icegram_message function

	function icegram_track_event_type(type, icegram) {
		icegram_event.push({ 'type': type, 'params': {'message_id': icegram.id,
													  'campaign_id': icegram.campaign_id}
						});
	}

	function icegram_send_tracking_data() {
		if (icegram_event.length > 0) {
			jQuery.ajax({
				method: 'POST',
				url: icegram_data.ajax_url,
				async: false,
				data: {
					action: 'icegram_event_track',
					event_data: icegram_event
				}
			});
		}
	}

	jQuery( window ).unload( function() {
		icegram_send_tracking_data();
	} );
		
});