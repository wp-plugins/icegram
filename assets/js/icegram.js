	/**
	 * Icegram JS class
	 * Initialize, run and manage all messages
	 * Public interface
	 **/
	function Icegram( ) { 
		var data, defaults, message_data, messages, map_id_to_index, map_type_to_index, 
			timer, message_template_cache, mode;
		var tracking_data, powered_by;
	}

	Icegram.prototype.init = function ( data ) {
		if (data == undefined) {
			return;
		}
		// Pre-init - can allow others to modify message data
		jQuery( window ).trigger( 'preinit.icegram', [ data ] );

		this.data = data;
		this.defaults = jQuery.extend( {}, data.defaults );
		this.message_data = data.messages;
		this.messages, this.tracking_data = [];
		this.message_template_cache = {};
		this.map_id_to_index = {};
		this.map_type_to_index = {};
		this.mode = (window.ig_mode == undefined) ? 'local' : window.ig_mode;
		this.powered_by = { link: 'http://www.icegram.com/?utm_source=inapp&utm_campaign=poweredby&utm_medium=' };
		//this.timer = setInterval( this.timer_tick, 1000 );

		// Add powered by properties
		this.powered_by.text = this.defaults.powered_by_text;
		this.powered_by.logo = this.defaults.powered_by_logo;

		// Add container div for Icegram
		jQuery('body').append('<div id="icegram_messages_container"></div>');
		// Loop over message data and create messages & maps
		var i = 0;
		this.messages = [];
		var self = this;
		if (this.message_data.length > 0) {
			jQuery.each( this.message_data, function ( i, v ) {
				try {
					// dont check cookies in preview mode
					if(window.location.href.indexOf("campaign_preview_id") == -1){
						//check cookies in js 
						if(v['retargeting'] == 'yes' && jQuery.cookie('icegram_messages_shown_'+v['id']) == 1){
							return;
						} 
						if(v['retargeting_clicked'] == 'yes' && jQuery.cookie('icegram_messages_clicked_'+v['id']) == 1){
							return;
						} 
					}
					
					var m = null;
					var classname_suffix = v['type'].split('-').join(' ').ucwords().split(' ').join('_');
					if (typeof (window['Icegram_Message_Type_' + classname_suffix]) === 'function') {
						m = new window['Icegram_Message_Type_' + classname_suffix]( v );
					} else {
						m = new Icegram_Message_Type( v );
					}
					self.messages.push( m );
					self.map_id_to_index['_'+v['id'] ] = i;
					self.map_type_to_index[ v['type'] ] = jQuery.isArray(self.map_type_to_index[ v['type'] ]) ? self.map_type_to_index[ v['type'] ] : new Array();
					self.map_type_to_index[ v['type'] ].push(i);
				
				} catch( e ) {
				}
			});
		}

		// Submit event data on unload and at every 5 seconds...
		jQuery( window ).unload( function() {
			if (typeof(window.icegram.submit_tracking_data) === 'function') {
				window.icegram.submit_tracking_data();
			}
		} );
		setInterval( function() { 
			if (typeof(window.icegram.submit_tracking_data) === 'function') { 
				window.icegram.submit_tracking_data();
			} } , 5 * 1000 );


		// Trigger event for others!
		jQuery( window ).trigger( 'init.icegram', [ this ] );
	};


	Icegram.prototype.timer_tick = function (  ) {
		
	};

	// Message template cache - get / set
	Icegram.prototype.get_template_fn = function ( type ) {
		return this.message_template_cache[ type ];
	};
	Icegram.prototype.set_template_fn = function ( type, fn ) {
		this.message_template_cache[ type ] = fn;
	};

	// Utility functions to get message instances
	Icegram.prototype.get_message = function ( index ) {
		if (this.messages.length > index) {
			return this.messages[ index ];
		}
		return undefined;
	};

	Icegram.prototype.get_message_by_id = function ( id ) {
		if ( this.map_id_to_index.hasOwnProperty( '_'+id )) {
			var index = this.map_id_to_index[ '_'+id ];
			return this.get_message( index );
		}  
		return undefined;
	};

	Icegram.prototype.get_messages_by_type = function ( type ) {
		if ( this.map_type_to_index.hasOwnProperty( type )) {
			var indices = this.map_type_to_index[ type ];
			var matches = [];
			if (jQuery.isArray( indices )) {
				var self = this;
				jQuery.each( indices, function ( i, v ) {
					matches.push( self.get_message( v ) );
				} );
			}
			return matches;
		}  
		return undefined;
	};

	//Get powered by link
	Icegram.prototype.get_powered_by = function ( type ) {
		var res = jQuery.extend( {}, this.powered_by );
		res.link = res.link + (type || '');
		return res;
	}
	

	//Event tracking
	Icegram.prototype.track = function ( ev, params ) {
		if (typeof(params) === 'object' && params.hasOwnProperty('message_id') && params.hasOwnProperty('campaign_id')) {
			jQuery( window ).trigger( 'track.icegram', [ ev, params ] );
			this.tracking_data.push( { 'type': ev, 'params': params} );
		}
	}
	Icegram.prototype.submit_tracking_data = function ( ev, params ) {
		if (this.tracking_data.length > 0 && window.location.href.indexOf("campaign_preview_id") == -1) {
			var params = {
				method: 'POST',
				url: this.data.ajax_url,
				async: false,
				data: {
					action: 'icegram_event_track',
					event_data: JSON.parse(JSON.stringify(this.tracking_data)),
					ig_remote_url: (this.mode == 'remote') ? window.location.href : undefined,
				},
				success: function(data, status, xhr) {
				},
				error: function(data, status, xhr) {
				}
			};
			if (this.mode == 'remote') {
				params['xhrFields'] = { withCredentials: true };
				params['crossDomain'] = true;
				params['async'] = true;
			}
			jQuery.ajax(params);
			this.tracking_data = [];
		}
	}




	/**
	 * Icegram Message Type - Base class
	 **/
	function Icegram_Message_Type( data ) {
		
		var data, template, dom_id, el, type, root_container;

		this.root_container = "#icegram_messages_container";
		this.data = data;
		this.type = data.type;
		this.data.delay_time = parseInt(this.data.delay_time);
		this.set_template( this.get_template_default() );
		this.init();
	}

	Icegram_Message_Type.prototype.init = function ( ) {
		// Render HTML
		this.render();

		// Add handlers
		this.add_event_handlers();
	};

	Icegram_Message_Type.prototype.add_event_handlers = function ( ) {
		this.el.on('click', {self: this}, this.on_click);
		jQuery( window ).on('resize' , {self: this} , this.on_resize);
	}

	Icegram_Message_Type.prototype.render = function ( ) {

		this.pre_render();

		var html = this.render_template();

		// Add html to DOM, Setup dom_id, el etc.
		try {
			jQuery(this.root_container).append(html);
		} catch ( e ) {}
		
		this.dom_id = 'icegram_message_'+this.data.id;
		this.el = jQuery('#'+this.dom_id);
		this.set_position();

		var pb = window.icegram.get_powered_by( this.type );
		if ( pb.hasOwnProperty('link') && pb.hasOwnProperty('text') && pb.text != '' ) {
			this.add_powered_by( pb );
		}

		// Hide elements if insufficient data...
    	if(this.data.headline == undefined || this.data.headline == '') {
            this.el.find('.ig_headline').hide();
        }
        if(this.data.icon == undefined || this.data.icon == '') {
            this.el.find('.ig_icon').remove();
        }
        if(this.data.message == undefined || this.data.message == '') {
            this.el.find('.ig_message').hide();
        }else{

        	var form_el = this.el.find('.ig_embed_form').get(0);
        	if(form_el){
	        	if(jQuery(window).width() < 644){
	        		jQuery(form_el)
	        					.removeClass('ig_horizontal ig_full ig_half ig_quarter')
	        					.addClass('ig_vertical ig_full')
	        					.find('.ig_form_el_group')
	        					.css('width', 96 + '%');
	        	}
        		var form_content = jQuery(form_el).html();
	        	form_el = jQuery(form_el).empty();
	        	jQuery(form_el).replaceWith(form_content);
		        this.el.find('.ig_message').html(form_el.append(this.el.find('.ig_message').html()));
	        	var prev_tag = this.el.find('.ig_embed_form_container').prev();
	        	var next_tag = this.el.find('.ig_embed_form_container').next();
	        	// var allowed_tags = ['P', 'DIV', 'SPAN']; // dont need this, no working !
	        	// if(form_el.hasClass('ig_inline') && prev_tag.get(0) && jQuery.inArray(prev_tag.get(0).tagName, allowed_tags) != -1){
	        	if(form_el.hasClass('ig_inline') && prev_tag.get(0)){
		        	this.el.find('.ig_embed_form_container')
		        		.appendTo(prev_tag);
	        		if(next_tag.get(0) && next_tag.get(0).tagName == prev_tag.get(0).tagName){
	        			prev_tag.append(next_tag.html());
	        			next_tag.remove();
	        		}
	        	}
        	}
        }
        if(this.data.label == undefined || this.data.label == '') {
            this.el.find('.ig_button').hide();
        }

        // Apply colors if available
        if (this.data.text_color != undefined && this.data.text_color != '') {
        	this.el.css('color', this.data.text_color);
        	this.el.find('.ig_content').css('color', this.data.text_color);
        }

        if (this.data.bg_color != undefined && this.data.bg_color != '') {
        	this.el.css('background-color', this.data.bg_color);
        	this.el.find('.ig_content').css('background-color', this.data.bg_color);
    	}

    	if(this.data.label == undefined || this.data.label == '') {
            this.el.find('.ig_button').hide();
        }
        if (this.data.bg_color != undefined && this.data.bg_color != '') {
        	var hsl_color = window.icegram.get_complementary_color(this.data.bg_color);
            this.el.find('.ig_button, form input[type="submit"]').css('background', "hsl(" + hsl_color.h + "," + hsl_color.s + "%," + hsl_color.l + "%)" ).css('background-color', "hsl(" + hsl_color.h + "," + hsl_color.s + "%," + hsl_color.l + "%)" );
        }
    	// Hint clickability for buttons / ctas
    	if (typeof(this.data.link) === 'string' && this.data.link != '') {
    		this.el.parent().find('.ig_cta, .ig_button').css('cursor', 'pointer');
    	}

		this.post_render();
		
		// Hide the message by default
		this.hide( {}, true );

		// Set up message display trigger
		this.set_up_show_trigger();
	}
	

	Icegram_Message_Type.prototype.render_template = function ( ) {
		if ( typeof(window.icegram.get_template_fn( this.type ) ) !== 'function') {
			// Adapted from John Resig's Simple JavaScript Templating
			window.icegram.set_template_fn( this.type, new Function("obj",
								"var p=[],print=function(){p.push.apply(p,arguments);};" +
							        "with(obj){p.push('" +
								this.template
								  .replace(/[\r\t\n]/g, " ")
								  .split("{{").join("\t")
								  .replace(/((^|\}\})[^\t]*)'/g, "$1\r")
								  .replace(/\t=(.*?)\}\}/g, "',$1,'")
								  .split("\t").join("');")
								  .split("}}").join("p.push('")
								  .split("\r").join("\\'")
								+ "');}return p.join('');") );
		}
		return window.icegram.get_template_fn( this.type )( this.data );
	};

	Icegram_Message_Type.prototype.pre_render = function ( ) {
		
	};

	Icegram_Message_Type.prototype.post_render = function ( ) {
    	
	};

	Icegram_Message_Type.prototype.set_up_show_trigger = function ( ) {
		if (!isNaN(this.data.delay_time)) {
			if( this.data.delay_time >= 0 ) {				
				var self = this;
				this.timer = setTimeout( function() { self.show(); } , this.data.delay_time * 1000 );
			}
		} else {
			this.show();
		}
	};

	Icegram_Message_Type.prototype.set_template = function ( str ) {
		this.template = str;
	};

	Icegram_Message_Type.prototype.get_template_default = function () {
		return '<div id="icegram_message_{{=id}}" class="icegram">' + 
				'<div class="ig_headline">{{=headline}}</div>' +
				'</div>';
	};

	Icegram_Message_Type.prototype.show = function ( options, silent ) {
		if ( !this.is_visible() ) {
			this.el.show( options );
			silent !== true && this.track( 'shown' );
		}
	};

	Icegram_Message_Type.prototype.hide = function ( options, silent ) {
		if ( this.is_visible() ) {
			this.el.hide( options );
			silent !== true && this.track( 'closed' );
		}
	};

	Icegram_Message_Type.prototype.set_position = function ( ) {
		
	};


	Icegram_Message_Type.prototype.add_powered_by = function ( pb ) {
		
	};

	// Event tracking wrapper
	Icegram_Message_Type.prototype.track = function ( e, params ) {
		if (typeof( window.icegram.track ) === 'function' ) {
			params = params || {};
			jQuery.extend( params, {message_id: this.data.id, campaign_id: this.data.campaign_id ,expiry_time:this.data.expiry_time ,expiry_time_clicked:this.data.expiry_time_clicked} );
			window.icegram.track( e, params);
		}
	};

	Icegram_Message_Type.prototype.is_visible = function ( ) {
		return this.el.is(':visible');
	};

	// Click and other event handlers
	Icegram_Message_Type.prototype.toggle = function ( options ) {
		if ( this.is_visible() ) {
			this.hide( options );
		} else {
			this.show( options );
		};
	};
	
	Icegram_Message_Type.prototype.on_click = function ( e ) {
		e.data = e.data || { self: this };
		// Clicked on close button
		if (jQuery(e.target).filter('.ig_close').length) {
			e.data.self.hide();
			return;
		}
		var form = jQuery(e.target).closest('.icegram').find('form').first();
		// Clicking on ig_button or any other link with a class ig_cta will trigger cta click
		if(jQuery(e.target).filter('.ig_button, .ig_cta ,:submit').length || jQuery(e.target).parents('.ig_button, .ig_cta ').length && !(form.find('ig_button').length > 0 || form.find('input[type=button]').length > 0 || form.find('input[type=submit]').length > 0 )){
            e.data.self.on_cta_click( e );
        }
	};
	Icegram_Message_Type.prototype.on_resize = function ( e ) {

	};

	Icegram_Message_Type.prototype.on_cta_click = function ( e ) {
		e.data = e.data || { self: this };
		e.data.self.track( 'clicked' );
		if(jQuery(e.target).closest('.icegram').find('form').length ){
			var form = jQuery(e.target).closest('.icegram').find('form').first();
			jQuery(form).submit();
		}else if (typeof(e.data.self.data.link) === 'string' && e.data.self.data.link != '') {
	        window.location.href = e.data.self.data.link;
	    }else if(e.data.self.data.hide !== false){
	    	e.data.self.hide()
	    }
	};


	/**
	 * Utilities
	 */
	String.prototype.ucwords = function() {
    	return this.toLowerCase().replace(/\b[a-z]/g, function(letter) {
        	return letter.toUpperCase();
    	});
	}

	Icegram.prototype.get_complementary_color = function (hex) {

	    hex = hex.replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i , function(m, r, g, b) {
	        return r + r + g + g + b + b;
	    });

	    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	    if(result){
	        var r = parseInt(result[1], 16);
	        var g = parseInt(result[2], 16);
	        var b = parseInt(result[3], 16);
			var brightness = Math.sqrt(r * r * .241 + g * g * .691 + b * b * .068);
			r /= 255;
	        g /= 255;
	        b /= 255;

		    var maxColor = Math.max(r, g, b);
		    var minColor = Math.min(r, g, b);
		    //Calculate L:
		    var L = (maxColor + minColor) / 2 ;
		    var S = 0;
		    var H = 0;
		    if(maxColor != minColor){
		        //Calculate S:
		        S = (L < 0.5) ? (maxColor - minColor) / (maxColor + minColor) : (maxColor - minColor) / (2.0 - maxColor - minColor) ;
		        //Calculate H:
		        if(r == maxColor){
		            H = (g - b) / (maxColor - minColor);
		        }else if(g == maxColor){
		            H = 2.0 + (b - r) / (maxColor - minColor);
		        }else{
		            H = 4.0 + (r - g) / (maxColor - minColor);
		        }
		    }

		    L = Math.floor(L * 100);
		    S = Math.floor(S * 100);
		    H = Math.floor(H * 60);
		    if(H<0){
		        H += 360;
		    }
			if(brightness > 130){
				S -= 15;
				L -= 25;
			}else{
				S += 15;
				L += 25;
			}
			
		    return {h: H, s: S, l: L};
	    } // result if
	    return null;
	}

	if (typeof Object.create != 'function') {
	    (function () {
	        var F = function () {};
	        Object.create = function (o) {
	            if (arguments.length > 1) { 
	              throw Error('Second argument not supported');
	            }
	            if (o === null) { 
	              throw Error('Cannot set a null [[Prototype]]');
	            }
	            if (typeof o != 'object') { 
	              throw TypeError('Argument must be an object');
	            }
	            F.prototype = o;
	            return new F();
	        };
	    })();
	}
// jQuery Cookies
(function(e){if(typeof define==="function"&&define.amd){define(["jquery"],e)}else if(typeof exports==="object"){e(require("jquery"))}else{e(jQuery)}})(function(e){function n(e){return u.raw?e:encodeURIComponent(e)}function r(e){return u.raw?e:decodeURIComponent(e)}function i(e){return n(u.json?JSON.stringify(e):String(e))}function s(e){if(e.indexOf('"')===0){e=e.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\")}try{e=decodeURIComponent(e.replace(t," "));return u.json?JSON.parse(e):e}catch(n){}}function o(t,n){var r=u.raw?t:s(t);return e.isFunction(n)?n(r):r}var t=/\+/g;var u=e.cookie=function(t,s,a){if(s!==undefined&&!e.isFunction(s)){a=e.extend({},u.defaults,a);if(typeof a.expires==="number"){var f=a.expires,l=a.expires=new Date;l.setTime(+l+f*864e5)}return document.cookie=[n(t),"=",i(s),a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":""].join("")}var c=t?undefined:{};var h=document.cookie?document.cookie.split("; "):[];for(var p=0,d=h.length;p<d;p++){var v=h[p].split("=");var m=r(v.shift());var g=v.join("=");if(t&&t===m){c=o(g,s);break}if(!t&&(g=o(g))!==undefined){c[m]=g}}return c};u.defaults={};e.removeCookie=function(t,n){if(e.cookie(t)===undefined){return false}e.cookie(t,"",e.extend({},n,{expires:-1}));return!e.cookie(t)}});

// This is called onReady
jQuery(function() {
  	// FINALLY: Create object in window for later usage
	window.icegram = new Icegram();
	window.icegram.init( icegram_data );
});