<?php
$id = !empty( $message_data['id'] ) ? $message_data['id'] : $_POST['post_ID'];
?>
<div class="icegram messenger popup_box" id="popup_box_<?php echo $id ?>">
    <div class="popup_box_main">
        <div class="popup_box_header">     
            <div class="popup_box_header_image"></div>  
            <div class="popup_box_header_text">     
                <div class="popup_box_heading"></div>
            </div>
        </div>
        <div class="popup_box_header2_image"></div>
        <div class="popup_box_body">         
            <div class="popup_box_message"></div>
            <div class="popup_box_hr"></div>
        </div>
        <div class="popup_box_footer">
            <div class="popup_box_footer_image"></div>
        </div>
    </div>
    <div class="popup_box_close" id="popup_box_close_<?php echo $id ?>"></div> 
</div>