<?php
$id = !empty( $message_data['id'] ) ? $message_data['id'] : $_POST['post_ID'];
?>
<div id="popup_main_<?php echo $id; ?>">
	<div class="icegram popup popup-container" id="popup_box_<?php echo $id; ?>">
		<div class="popup-close" id="popup_box_close_<?php echo $id; ?>"></div>
		<div class="popup-headline"></div>
		<div class="popup-content">
			<div class="popup-image">
				<img class="popup-icon" />
			</div>
			<div class="popup-message"></div>
		</div>
		<div class="popup-button"></div>
	</div>
</div>