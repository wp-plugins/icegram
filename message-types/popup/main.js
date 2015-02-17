    /**
     * Icegram Message Type - Popup
     **/
    function Icegram_Message_Type_Popup( data ) {
        Icegram_Message_Type.apply(this, arguments);
    }
    Icegram_Message_Type_Popup.prototype = Object.create(Icegram_Message_Type.prototype);
    Icegram_Message_Type_Popup.prototype.constructor = Icegram_Message_Type_Popup;

    Icegram_Message_Type_Popup.prototype.get_template_default = function () {
        return  '<div id="popup_main_{{=id}}" class="mfp-hide" data={{=id}}>'+
                    '<div class="icegram ig_popup ig_container {{=theme}}" id="icegram_message_{{=id}}" data={{=id}}>'+
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
        var popup_id = '#icegram_message_'+this.data.id;
        jQuery.magnificPopup.open({ 
            items: {
            src: popup_id,
            type: 'inline'
            },
            showCloseBtn :false
        });
        jQuery('.mfp-content').removeClass().addClass('mfp-content ig_popup ' + self.data.theme);
        silent !== true && this.track( 'shown' );
    };
    
    Icegram_Message_Type_Popup.prototype.add_powered_by = function ( pb ) {        
        setTimeout( function() {
            jQuery('.mfp-wrap').append('<div class="powered_by"><a href="'+pb.link+'" target="_blank">'+pb.text+'</a></div>');
        },1000 + this.data.delay_time * 1000);
    };

    Icegram_Message_Type_Popup.prototype.hide = function ( options, silent ) {
        if ( !this.is_visible() ) return;
        var popup_id = '#icegram_message_'+this.data.id;
        jQuery.magnificPopup.close({ items: {
            src: popup_id,
            type: 'inline'
        }});
        silent !== true && this.track( 'closed' );
    };
   