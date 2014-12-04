    /**
     * Icegram Message Type - Popup
     **/
    function Icegram_Message_Type_Popup( data ) {
        Icegram_Message_Type.apply(this, arguments);
    }
    Icegram_Message_Type_Popup.prototype = Object.create(Icegram_Message_Type.prototype);
    Icegram_Message_Type_Popup.prototype.constructor = Icegram_Message_Type_Popup;

    Icegram_Message_Type_Popup.prototype.get_template_default = function () {
        return  '<div id="popup_main_{{=id}}" data={{=id}}>'+
                    '<div class="icegram popup ig_container {{=theme}}" id="icegram_message_{{=id}}" data={{=id}}>'+
                       '<div class="ig_close" id="popup_box_close_{{=id}}"></div>'+
                       '<div class="ig_data">'+
                            '<div class="ig_headline">{{=headline}}</div>'+
                            '<div class="ig_content">'+
                                '<div class="ig_message">{{=message}}</div>'+
                            '</div>'+
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
            var current_popup_id = jQuery('#TB_window').find('.popup').attr('data');
            if (typeof(current_popup_id) != 'undefined') {
                var current_popup = window.icegram.get_message_by_id(current_popup_id);
                if (typeof('current_popup') != 'Icegram_Message_Type_Popup') {
                    current_popup.hide();
                }
            }
            popup_delay = 800;
        }
        setTimeout( function() {
            var popup_width = (jQuery(window).width() * 60) / 100;
            self.el.show();
            tb_show('Popup', "#TB_inline?width="+popup_width+"&modal=true&inlineId=popup_main_" + self.data.id, true);
            self.el = jQuery('#TB_window .popup');
            self.el.on('click', {self: self}, self.on_click);
            var max_height = jQuery(window).height()-jQuery('#TB_window').height() + 150;
            self.el.find('.ig_data').css('max-height', max_height);
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
        self.el = jQuery('#popup_main_' + self.data.id);
        self.el.hide();
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