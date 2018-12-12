<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Shared\PageExtension;

/**
 * Form save metabox extension.
 * 
 * @since 2.1
 * @todo Review this HTML layout, FGS
 * @todo Review this $delete_link, FGS
 */
class Save {

    /**
     * Generate the Sve metabox.
     *
     * @param object $form
     * @param array $callback_args
     * 
     * @since 2.1
     */
    public function print_metabox_content( $form, $callback_args = array() ) {
        ?>
        <div id="save-form-actions" style="display:none">
            <label>
                <?php _e( 'Form slug:', 'wp-cred' ); ?> <input name="post_name" size="13" id="post_name" class="regular-text" value="<?php echo esc_attr( $form->post_name ); ?>" type="text">
            </label>
            <?php echo do_shortcode("[cred_delete_post_link class='submitdelete deletion js-cred-delete-form' text='" . __( 'Delete form', 'wp-cred' ) . "' action='delete' message='" . __( "Are you sure you want to delete this form?", "wp-cred" ) . "' message_show='1']"); ?>
            <input id="js-cred-save-form" name="save" type="submit" class="cred-save-form js-cred-save-form button button-primary" value="<?php esc_attr_e( "Save form", 'wp-cred' ); ?>">
        </div>
        <?php
   }
}