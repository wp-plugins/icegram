	/**
	 * Icegram JS class
	 * Initialize, run and manage all messages
	 * Public interface
	 **/
	function Icegram( ) { 
		var data, defaults, message_data, messages, map_id_to_index, map_type_to_index, 
			timer, message_template_cache;
		var tracking_data, powered_by;
	}

	Icegram.prototype.init = function ( data ) {
		this.data = data;
		this.defaults = jQuery.extend( {}, data.defaults );
		this.message_data = data.messages;
		this.messages, this.tracking_data = [];
		this.message_template_cache = {};
		this.map_id_to_index = {};
		this.map_type_to_index = {};
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
		jQuery.each( this.message_data, function ( i, v ) {
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
		});

		// Submit event data on unload...
		jQuery( window ).unload( function() {
			if (typeof(window.icegram.submit_tracking_data) === 'function') {
				window.icegram.submit_tracking_data();
			}
		} );
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
			this.tracking_data.push( { 'type': ev, 'params': params} );
		}
	}
	Icegram.prototype.submit_tracking_data = function ( ev, params ) {
		if (this.tracking_data.length > 0) {
			jQuery.ajax({
				method: 'POST',
				url: this.data.ajax_url,
				async: false,
				data: {
					action: 'icegram_event_track',
					event_data: this.tracking_data
				}
			});
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
		jQuery(this.root_container).append(html);
		this.dom_id = 'icegram_message_'+this.data.id;
		this.el = jQuery('#'+this.dom_id);

		this.set_position();

		var pb = window.icegram.get_powered_by( this.type );
		if ( pb.hasOwnProperty('link') && pb.hasOwnProperty('text') && pb.text != '' ) {
			this.add_powered_by( pb );
		}

		// Hide elements if insufficient data...
    	if(this.data.headline == '') {
            this.el.find('.ig_headline').hide();
        }
        if(this.data.icon == '') {
            this.el.find('.ig_icon').hide();
        }
        if(this.data.message == '') {
            this.el.find('.ig_message').hide();
        }
        if(this.data.label == '') {
            this.el.find('.ig_button').hide();
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
			jQuery.extend( params, {message_id: this.data.id, campaign_id: this.data.campaign_id } );
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
		// Any other click is considered as CTA click
		e.data.self.on_cta_click( e );
	};
	Icegram_Message_Type.prototype.on_resize = function ( e ) {

	};

	Icegram_Message_Type.prototype.on_cta_click = function ( e ) {
		e.data = e.data || { self: this };
		e.data.self.track( 'clicked' );
		typeof(e.data.self.data.link) === 'string' && e.data.self.data.link != '' ? window.location.href = e.data.self.data.link : e.data.self.hide();
	};



	/**
	 * Utilities
	 */
	String.prototype.ucwords = function() {
    	return this.toLowerCase().replace(/\b[a-z]/g, function(letter) {
        	return letter.toUpperCase();
    	});
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

// This is called onReady
jQuery(function() {
  	// FINALLY: Create object in window for later usage
	window.icegram = new Icegram();
	window.icegram.init( icegram_data );
});