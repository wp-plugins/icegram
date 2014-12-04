
    /**
     * Icegram Message Type - Toast
     **/
    function Icegram_Message_Type_Toast( data ) {
        var width ,sticky ,duration;
        this.width = 300;
        this.sticky  = false;
        this.duration    = 10000;
        Icegram_Message_Type.apply(this, arguments);
    }
    Icegram_Message_Type_Toast.prototype = Object.create(Icegram_Message_Type.prototype);
    Icegram_Message_Type_Toast.prototype.constructor = Icegram_Message_Type_Toast;

    Icegram_Message_Type_Toast.prototype.get_template_default = function () {
          //'<div id="icegram_message_{{=id}}">'+
        return  '<li class="icegram toast ig_container {{=animation}} {{=theme}} ig_cta" id="icegram_message_{{=id}}">'+
                    '<div class="ig_wrapper">'+
                        '<div class="ig_content">'+
                            '<div class="ig_base"></div>'+
                            '<div class="ig_line"></div>'+
                            '<img class="ig_icon" src="{{=icon}}"/>'+
                            '<div class="ig_headline">{{=headline}}</div>'+
                            '<div class="ig_message">{{=message}}</div>'+
                        '</div>'+
                    '</div>'+
                '</li>';
                //'</div>';
    };
    Icegram_Message_Type_Toast.prototype.pre_render = function ( ) {
        if( this.data.position == "10" || this.data.position == "12" ) {
            this.data.position = '20';
        }
        if (!(jQuery('ul#' + this.data.position).length)) {
            var ul = jQuery('<ul id="' + this.data.position + '"></ul>').addClass('ig_toast_block').appendTo(this.root_container).hide();
            ul.width(this.width);
            if (this.data.position == "00") {
                ul.css({top: '0', left: '0'}).addClass('left');
            } else if (this.data.position == "01") {                                      
                ul.css({top: '0', left: '50%', margin: '5px 0 0 -' + (this.width / 2) + 'px'}).addClass('center');
            } else if (this.data.position == "02") {                                      
                ul.css({top: '0', right: '0'}).addClass('right');
            } else if (this.data.position == "20") {                                      
                ul.css({bottom: '0', left: '0'}).addClass('left');
            } else if (this.data.position == "21") {                                      
                ul.css({bottom: '0', left: '50%', margin: '5px 0 0 -' + (this.width / 2) + 'px'}).addClass('center');
            } else if (this.data.position == "22") {                                      
                ul.css({bottom: '0', right: '0'}).addClass('right');
            } else if (this.data.position == "11") {                                      
                ul.css({top: '50%', left: '50%', margin: '-'+(this.width / 2) +'px 0 0 -' + (this.width / 2) + 'px'}).addClass('center');
            }
        }else {
            var ul = jQuery('ul#' + this.data.position);
        }
        this.root_container = ul;
    }
    
    Icegram_Message_Type_Toast.prototype.show = function ( options, silent ) {
        if ( this.is_visible() ) return;
        !this.root_container.hasClass('active') && this.root_container.addClass('active').show();
        var self = this;
        setTimeout(function() {
            self.el.show();
            self.el.fadeIn('slow');
        }, this.data.delay_time); 
        silent !== true && self.track( 'shown' );
        !this.sticky && this.duration > 0 && (setTimeout(function() {
                self.el.fadeOut('slow');
                self.hide();
                self.root_container.children().length || self.root_container.removeClass('active').hide();
        }, this.duration));

    };

    Icegram_Message_Type_Toast.prototype.hide = function ( options, silent ) {
        if ( !this.is_visible() ) return;
        this.el.hide();
        silent !== true && this.track( 'closed' );
    };
   
