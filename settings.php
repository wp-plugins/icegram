<?php
if ( !defined( 'ABSPATH' ) ) exit;

if( isset( $_POST['submit'] ) ) {
	if( isset( $_POST['icegram_share_love'] ) ) {
		update_option( 'icegram_share_love', $_POST['icegram_share_love'] );
	} else {		
		update_option( 'icegram_share_love', 'no' );
	}
	if( isset( $_POST['icegram_cache_compatibility'] ) ) {
		update_option( 'icegram_cache_compatibility', $_POST['icegram_cache_compatibility'] );
	} else {		
		update_option( 'icegram_cache_compatibility', 'no' );
	}
}

?>
<div class="wrap">
	<h2><?php _e( "Icegram Settings", "icegram" ) ?></h2>
	<form name="icegram_settings" method="POST" action="<?php echo admin_url(); ?>edit.php?post_type=ig_campaign&page=icegram-settings">
		<table class="form-table">
	        <tr>
				<th scope="row"><?php _e( 'Share Icegram', 'icegram' ) ?></th>
				<td>
	                <label for="icegram_share_love">
	                    <input type="checkbox" name="icegram_share_love" id="icegram_share_love"/ value="yes" <?php checked('yes', get_option('icegram_share_love')); ?> />
	                    <?php _e( 'Show "Powered by" link', 'icegram' ); ?>                        
	                </label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Cache Friendly?', 'icegram' ) ?></th>
				<td>
	                <label for="icegram_cache_compatibility">
	                    <input type="checkbox" name="icegram_cache_compatibility" id="icegram_cache_compatibility"/ value="yes" <?php checked('yes', get_option('icegram_cache_compatibility')); ?> />
	                    <?php _e( 'Turn on "Lazy Load" - load Icegram scripts and messages after page load to avoid caching problems.', 'icegram' ); ?>                        
	                </label>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
<?php do_action( 'icegram_settings_after' ); ?>