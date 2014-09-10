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
	</h2>
	<ul class="addons">
	<?php
	if (count($ig_addons) > 0) {
		foreach ($ig_addons as $addon) {
			?>
			<li class="addon">
				<a href="<?php echo $addon->link;?>?utm_source=inapp&utm_campaign=addons&utm_medium=store" target="_blank">			
					<h3><?php echo $addon->name; ?></h3>
					<?php
					if( !empty( $addon->category ) ) {
						$categories = explode(",", $addon->category);
						if (!empty($categories)) {
							echo "<div class='ig_addon_category'>";
							foreach ($categories as $cat) {
								echo "<span class='{$cat}'>{$cat}</span> ";
							}
							echo "</div>";							
						}
					}
					?>
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