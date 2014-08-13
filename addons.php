<?php

if ( empty($ig_addons) ) {
	$ig_addons = get_transient( 'icegram_addons_data' );
}
if ( empty($ig_addons) ) {
	$ig_addons = array();
}

?>
<div class="wrap ig_addons_wrap">
	<h2>
		<?php _e( 'Icegram Add-ons', 'icegram' ); ?>
		<?php if (time() < strtotime("31 August 2014")) { ?>
		<span class="ig_addons_special_message">25% Discount on All Items till August 30, 2014. Use Coopon <code><a href="http://www.icegram.com/addons/?coupon-code=launch25&utm_source=inapp&utm_campaign=launch&utm_medium=store" target="_blank">LAUNCH25</a></code></span>
		<?php } ?>
	</h2>
	<ul class="addons">
	<?php
	if (count($ig_addons) > 0) {
		foreach ($ig_addons as $addon) {
			?>
			<li class="addon">
				<a href="<?php echo $addon->link;?>?utm_source=inapp&utm_campaign=addons&utm_medium=store" target="_blank">			
					<h3><?php echo $addon->name; ?></h3>
					<p>
						<?php
						if( !empty( $addon->image ) ) {
							echo "<img src=".$addon->image.">";
						}
						echo $addon->descripttion; ?>
					</p>
				</a>
			</li>
			<?php
		}
	} else {
		echo "<p>". __( 'Sorry! No Add-ons available currently.', 'icegram') . "</p>";
	}	
	?>
	</ul>
</div>