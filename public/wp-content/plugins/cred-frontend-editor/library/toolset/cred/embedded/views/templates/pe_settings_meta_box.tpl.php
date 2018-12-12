<?php if (!defined('ABSPATH')) die('Security check'); ?>
<?php
$expiration_cron_schedule_selected = isset( $settings['post_expiration_cron']['schedule'] ) 
	? $settings['post_expiration_cron']['schedule'] 
	: '';
$schedules = wp_get_schedules();
?>
<div id="cred_post_expiration_cron" style="margin-top:5px;margin-left:23px;clear:both;">
    <a id="cred-post-expiration-form"></a>

    <?php _e('Check for expired content:', $cred_post_expiration->getLocalizationContext()); ?>
    <select id="cred_post_expiration_cron" autocomplete="off" name="cred_post_expiration_cron_schedule" class='cred_ajax_change'>
        <?php
		if ( ! array_key_exists( $expiration_cron_schedule_selected, $schedules ) ) {
			?>
			<option value="" selected="selected" disabled="disabled"><?php _e( 'Select one interval', $cred_post_expiration->getLocalizationContext() ); ?></option>
			<?php
		}
		?>
		<?php foreach ( $schedules as $schedule => $schedule_definition ) { ?>
            <option value="<?php echo esc_attr( $schedule ); ?>" <?php selected( $schedule, $expiration_cron_schedule_selected ); ?>><?php echo $schedule_definition['display']; ?></option>
        <?php } ?>
    </select>

</div>
