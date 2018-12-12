<?php if (!defined('ABSPATH')) die('Security check'); ?>
<p class="cred_post_expiration_options">
     <label class="cred-label">
        <input data-cred-bind="{
               validate: {
               required: {
               actions: [
               {action: 'validationMessage', domRef: '#notification_event_required-<?php echo $ii; ?>' },
               {action: 'validateSection' }
               ]
               }
               }
               }" type="radio" class="cred-radio-10 js-cred-notification-trigger" name="_cred[notification][notifications][<?php echo $ii; ?>][event][type]" value="expiration_date" <?php if ('expiration_date' == $notification['event']['type']) echo 'checked="checked"'; ?> />
        <span class="cred_expiration_period_by_span">
            <input data-cred-bind="{ action:'enable', condition:'_cred[notification][notifications][<?php echo $ii; ?>][event][type]=expiration_date' }" value='0' type="number" min="0" class="cred_when_status_changes cred_number_input cred_expiration_period_amount" name="_cred[notification][notifications][<?php echo $ii; ?>][event][expiration_date]" /> 
            <select data-cred-bind="{ action:'enable', condition:'_cred[notification][notifications][<?php echo $ii; ?>][event][type]=expiration_date' }" data-span_place_holder="{0} {1} <?php _e("before the automatic expiration date:", $cred_post_expiration->getLocalizationContext()); ?>" class="cred_expiration_period_by" name="_cred[notification][notifications][<?php echo $ii; ?>][event][expiration_period]">
                <option value="60"><?php _e("Minutes", $cred_post_expiration->getLocalizationContext());  ?></option>
                <option value="3600"><?php _e("Hours", $cred_post_expiration->getLocalizationContext());  ?></option>
                <option value="86400"><?php _e("Days", $cred_post_expiration->getLocalizationContext());  ?></option>
                <option value="604800"><?php _e("Weeks", $cred_post_expiration->getLocalizationContext());  ?></option>
            </select>
            <?php _e("before the automatic expiration date.", $cred_post_expiration->getLocalizationContext()); ?>
        </span>
    </label>
</p>