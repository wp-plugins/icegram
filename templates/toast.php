<?php
$id = !empty( $message_data['id'] ) ? $message_data['id'] : $_POST['post_ID'];
?>
<div id="toast_<?php echo $id; ?>">
    <li class="icegram toast toast-container">
        <div class="toast-wrapper">
            <div class="toast-content">
                <div class="toast-base"></div>
                <div class="toast-line"></div>
                <img class="toast-icon" />
                <div class="toast-title"></div>
                <div class="toast-message"></div>
            </div>
        </div>
    </li>
</div>