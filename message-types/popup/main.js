    /**
     * Icegram Message Type - Popup
     **/
    function Icegram_Message_Type_Popup( data ) {

        Icegram_Message_Type.call(this, data);
    }
    Icegram_Message_Type_Popup.prototype = Object.create(Icegram_Message_Type.prototype);
    Icegram_Message_Type_Popup.prototype.constructor = Icegram_Message_Type_Popup;

    Icegram_Message_Type_Popup.prototype.get_template_default = function () {
        return  '<div id="popup_main_{{=id}}" data={{=id}}>'+
                    '<div class="icegram popup ig_container {{=theme}}" id="icegram_message_{{=id}}">'+
                       '<div class="ig_close" id="popup_box_close_{{=id}}"></div>'+
                        '<div class="ig_headline">{{=headline}}</div>'+
                        '<div class="ig_content">'+
                            '<div class="ig_image">'+
                                '<img class="ig_icon" src="{{=icon}}"/>'+
                            '</div>'+
                            '<div class="ig_message">{{=message}}</div>'+
                        '</div>'+
                        '<div class="ig_button" >{{=label}}</div>'+
                    '</div>'+
                '</div>';
    };
    Icegram_Message_Type_Popup.prototype.show = function ( options, silent ) {
        if ( this.is_visible() ) return;
        var self = this;
        var popup_delay = 0;
        if( jQuery('body').find('#TB_window').length ) {   
            popup_delay = 800;
            tb_remove();
        }
        setTimeout( function() {
            var popup_width = (jQuery(window).width() * 60) / 100;
            self.el.show();
            tb_show('Popup', "#TB_inline?width="+popup_width+"&modal=true&inlineId=popup_main_" + self.data.id, true);
            jQuery('#popup_main_' + self.data.id).remove();
            self.el = jQuery('#TB_window .popup');
            self.el.on('click', {self: self}, self.on_click);
            jQuery('#TB_window').addClass(self.data.theme).addClass(self.data.type);
            silent !== true && self.track( 'shown' );
        }, popup_delay);
    };
    
    Icegram_Message_Type_Popup.prototype.add_powered_by = function ( pb ) {        
        setTimeout( function() {
            jQuery('#TB_window').prev().append('<div class="powered_by"><a href="'+pb.link+'" target="_blank">'+pb.text+'</a></div>');
        },1000 + this.data.delay_time * 1000);
    };

    Icegram_Message_Type_Popup.prototype.hide = function ( options, silent ) {
        if ( !this.is_visible() ) return;
        var self = this;
        tb_remove();
        setTimeout( function() {
            self.el.hide();
        },0);
        silent !== true && this.track( 'closed' );
    };
    Icegram_Message_Type_Popup.prototype.on_resize = function(){
        if( jQuery( window ).height() < jQuery( '#TB_window' ).height() || jQuery( window ).width() < jQuery( '#TB_window' ).width() ) {

            var height  = (jQuery( window ).height()-50)/(jQuery( '#TB_window' ).height());
            var width   = (jQuery( window ).width()-50)/(jQuery( '#TB_window' ).width());
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
    Icegram_Message_Type_Popup.prototype.on_cta_click = function ( e ) {
        e.data = e.data || { self: this };
        e.data.self.track( 'clicked' );
        if(jQuery(e.target).filter('.popup .ig_button').length){
            typeof(e.data.self.data.link) === 'string' && e.data.self.data.link != '' ? window.location.href = e.data.self.data.link : e.data.self.hide();
        }
    };
