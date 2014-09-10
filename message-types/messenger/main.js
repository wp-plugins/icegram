
    /**
     * Icegram Message Type - Messenger
     **/
    function Icegram_Message_Type_Messenger( data ) {
        Icegram_Message_Type.apply(this, arguments);
    }
    Icegram_Message_Type_Messenger.prototype = Object.create(Icegram_Message_Type.prototype);
    Icegram_Message_Type_Messenger.prototype.constructor = Icegram_Message_Type_Messenger;

    Icegram_Message_Type_Messenger.prototype.get_template_default = function () {
        return '<div class="icegram messenger {{=theme}} {{=animation}} ig_container ig_cta" data="{{=id}}" id="icegram_message_{{=id}}">' +
                '<div class="ig_content">' +
                    '<div class="ig_header">' +
                        '<div class="ig_header_image"></div>' +
                        '<div class="ig_header_text">' +
                            '<div class="ig_headline">{{=headline}}</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ig_header2_image"></div>' +
                    '<div class="ig_body">' +
                        '<img class="ig_icon" src="{{=icon}}"/>' +
                        '<div class="ig_message_body">{{=message}}</div>' +
                        '<div class="ig_separator"></div>' +
                    '</div>' +
                    '<div class="ig_footer">' +
                        '<div class="ig_footer_image"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="ig_close" id="ig_close_{{=id}}"></div>' +
            '</div>';
    };


    Icegram_Message_Type_Messenger.prototype.post_render = function ( ) {
        // No avatar...
        if (this.data.icon == '') {
            this.el.find('.ig_icon').remove();
            this.el.find('.ig_message_body').addClass('ig_no_icon');
        }
    };

    Icegram_Message_Type_Messenger.prototype.set_position = function ( ) {
        switch(this.data.position) {
            case "20":
                this.el.css( { 'left': 5, 'bottom': 0} );
                break;
            case "22":
            default: 
                this.el.css( {'left': jQuery(window).width() - this.el.outerWidth() - 5, 'bottom': 0} );
                break;
        }

    };

    Icegram_Message_Type_Messenger.prototype.show = function ( options, silent ) {

        if ( this.is_visible() ) return;

        var anim_delay = silent !== true ? 1000 : 0;
        switch(this.data.animation) {
            case "appear":
                this.el.fadeIn(anim_delay);
                break;
            case "slide":
                this.el.slideDown(anim_delay);
                break;
            default:
                break;
        }
        silent !== true && this.track( 'shown' );
    };

    Icegram_Message_Type_Messenger.prototype.add_powered_by = function ( pb ) {
        this.el.find('.ig_content').after('<div class="powered_by"><a href="'+pb.link+'" target="_blank">'+pb.text+'</a></div>');      
    };

    Icegram_Message_Type_Messenger.prototype.hide = function ( options, silent ) {
        
        if ( !this.is_visible() ) return;

        var anim_delay = silent !== true ? 1000 : 0;
        switch(this.data.animation) {
            case "appear":
                this.el.fadeOut(anim_delay);
                break;
            case "slide":
                this.el.slideUp(anim_delay);
                break;
            default:
                break;
        }
        silent !== true && this.track( 'closed' );
    };
