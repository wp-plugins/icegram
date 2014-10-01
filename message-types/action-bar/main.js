/**
 * Icegram Message Type - Action_Bar
 **/
function Icegram_Message_Type_Action_Bar( data ) {
    Icegram_Message_Type.apply(this, arguments);
}

Icegram_Message_Type_Action_Bar.prototype = Object.create(Icegram_Message_Type.prototype);
Icegram_Message_Type_Action_Bar.prototype.constructor = Icegram_Message_Type_Action_Bar;

Icegram_Message_Type_Action_Bar.prototype.get_template_default = function () {
    return  '<div class="icegram action_bar_{{=id}}" >'+
                '<div class="action_bar ig_container {{=theme}}" id="icegram_message_{{=id}}">'+
                    '<div class="ig_content ig_clear">'+
                        '<div class="ig_arrow_block" id="action_bar_close_{{=id}}">'+
                            '<span class="ig_arrow"></span>'+
                        '</div>'+
                        '<div>'+
                            '<div class="ig_close"></div>'+
                        '</div>'+
                        '<div class="ig_data">'+
                            '<div class="ig_headline">{{=headline}}</div>'+
                            '<div class="ig_message">{{=message}}</div>'+
                        '</div>'+
                        '<div class="ig_button">{{=label}}</div>'+
                    '</div>'+
                '</div>'+
            '</div>';
};

Icegram_Message_Type_Action_Bar.prototype.post_render = function ( ) {
    this.el.find('.ig_arrow_block').css('background-color', this.data.bg_color);
    if( this.data.theme == 'hello' ) {
        var message_button = this.el.find('.ig_button');
        this.el.find('.ig_data').append(message_button);
    }
};

Icegram_Message_Type_Action_Bar.prototype.set_position = function ( ) {
    switch(this.data.position) {
        case "21":
            this.el.addClass('bottom');
            this.el.css('position', 'fixed'); 
            break;
        case "01":
        default:
            this.el.addClass('top');
            break;
    }

};

Icegram_Message_Type_Action_Bar.prototype.is_visible = function ( ) {
        return this.el.find('.ig_arrow_block').hasClass('open');
};

Icegram_Message_Type_Action_Bar.prototype.show = function ( options, silent ) {
   if ( this.is_visible() ) return; //TODO:: we are not hiding action bar we are sliding up with css need to check
    var anim_delay = silent !== true ? 1000 : 0;
    switch(this.data.position) {
        case "21":
            var self = this;
            this.el.delay(this.data.delay_time).animate({
                bottom: 0,
                marginBottom: -this.el.outerHeight()

            }, 0, "linear", function() {
                self.el.show();
                self.el.find('.ig_arrow').show();
                self.el.find('.ig_arrow_block').addClass('open').removeClass('rotate').removeClass('border').css('position', 'initial');
            });
            this.el.animate({
                marginBottom: 0
            }, 300);
            break;
        case "01":
        default:
            var close_block = this.el.parent().find('.ig_arrow_block');
            var message_template = this.el.parent();
            this.el.parent().find('.ig_arrow_block').remove();
            this.el.parent().find('.ig_content').prepend(close_block);
            jQuery('body').prepend(message_template);

            this.el.find('.ig_arrow').hide();
            this.el.find('.ig_arrow_block').addClass('open').removeClass('border').css('position', 'initial');

            var self = this;
            this.el.delay(this.data.delay_time).animate({
                marginTop: -this.el.outerHeight()
            }, 0, "linear", function() {
                    self.el.show();
                    self.el.find('.ig_arrow').show();
                    self.el.find('.ig_arrow_block').addClass('open').addClass('rotate').removeClass('border').css('position', 'initial');
                    self.el.find('.ig_arrow_block').css('background-color', '');
            });
            this.el.animate({
                marginTop: 0
            }, 300);
            break;
    }
    silent !== true && this.track( 'shown' );
};

Icegram_Message_Type_Action_Bar.prototype.add_powered_by = function ( pb ) {
    if( this.data.theme != 'air-mail' ) {
        this.el.find('.ig_content').before('<div class="powered_by" ><a href="'+pb.link+'" target="_blank"><img src="'+pb.logo+'" title="'+pb.text+'"/></a></div>');
    } else {
        this.el.find('.ig_content').prepend('<div class="powered_by" ><a href="'+pb.link+'" target="_blank"><img src="'+pb.logo+'" title="'+pb.text+'"/></a></div>');
    }
};

Icegram_Message_Type_Action_Bar.prototype.hide = function ( options, silent ) {
   if ( !this.is_visible() ) return; //TODO:: need to check this this is not workig for action bar
    var self = this;
    var anim_delay = silent !== true ? 1000 : 0;
    switch(this.data.position) {
        case "21":
            this.el.animate({
                marginBottom: -this.el.outerHeight()
            }, 300, "linear", function() {
                 self.el.find('.ig_arrow').show();
                 self.el.find('.ig_arrow_block').removeClass('open').addClass('border').addClass('rotate').css({
                    'position': 'fixed',
                    'bottom': '0'
                });
            });
            break;
        case "01":
        default:
            this.el.animate({
                marginTop: -this.el.outerHeight()
            }, 300, "linear", function() {
                self.el.find('.ig_arrow_block').css('background-color', self.data.bg_color);                
                self.el.find('.ig_arrow_block').css('margin-top', '0' );
                self.el.find('.ig_arrow').show();
                self.el.find('.ig_arrow_block').removeClass('open').removeClass('rotate').addClass('border').css({
                    'position': 'absolute',
                    'top': '0'
                });
            });
            break;
    }
    silent !== true && this.track( 'closed' );
};


Icegram_Message_Type_Action_Bar.prototype.on_click = function ( e ) {
    e.data = e.data || { self: this };
    // Clicked on close button
    if (jQuery(e.target).filter('.open,.open span.ig_arrow,.ig_close').length) {
        e.data.self.hide();
        return;
    }else if(jQuery(e.target).filter('.border,.border span.ig_arrow').length){
        e.data.self.show();
        return;
    }
    // Now let the parent handle the rest...
    Icegram_Message_Type.prototype.on_click.apply(this, arguments);
};