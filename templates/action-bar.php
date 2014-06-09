<?php
$id = !empty( $message_data['id'] ) ? $message_data['id'] : $_POST['post_ID'];
?>
<div class="icegram action-bar action_bar_<?php echo $id; ?>">
    <div class="container" id="action_bar_<?php echo $id; ?>">
        <div class="content">
            <div class="popup_close" id="action_bar_close_<?php echo $id; ?>">
                <span class="bar_open"></span>
                <span class="bar_close"></span>
            </div>
            <div>
                <div class="close"></div>
            </div>
            <div class="popup_box_image"></div>
            <div class="data">
                <div class="heading"></div>
                <div class="message"></div>
            </div>
            <div class="popup_button"></div>
        </div>
    </div>
</div>