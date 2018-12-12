<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Shared\PageExtension;

use OTGS\Toolset\CRED\Controller\LinksManager;

/**
 * Form Notifications metabox extension.
 * 
 * @since 2.1
 * 
 * @todo Evaluate the tip texts, they do not belong here.
 */
class Notifications {

    /**
     * Generate the section for the Access integration information.
     *
     * @param object $form
     * @param array $callback_args
     * 
     * @since 2.1
     */
    public function print_metabox_content( $form, $callback_args = array() ) {
        $form = $form->filter( 'raw' );
        $form_type = $form->post_type;
        $notifications_object = toolset_getnest( $callback_args, array( 'args', 'notification' ), array() );
        $notifications = isset( $notifications_object->notifications ) 
            ? (array) $notifications_object->notifications 
            : array();
        $enableTestMail = ! \CRED_Helper::$currentPage->isCustomPostNew;

        $template_repository = \CRED_Output_Template_Repository::get_instance();
        $renderer = \Toolset_Renderer::get_instance();
        $templates_data = array(
            'enableTestMail' => $enableTestMail,
            'form_type' => $form_type,
            'form' => $form
        );

        $links_manager = new LinksManager();

        ?>
        <!-- templates here-->
        <script type="text/html-template" id="cred_notification_field_condition_template">
        <?php
        $conditions_data = $templates_data;
        $conditions_data['ii'] = '__i__';
        $conditions_data['jj'] = '__j__';
        $conditions_data['notification'] = array();
        $conditions_data['condition'] = array();
        $renderer->render(
            $template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_SECTION_SHARED_TRIGGER_META_CONDITION ),
            $conditions_data
        );
        ?>
        </script>
        <script type="text/html-template" id="cred_notification_template">
        <?php
        $templates_data['ii'] = '__i__';
        $templates_data['notification'] = array();
        $renderer->render(
            $template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_ITEM ),
            $templates_data
        );
        ?>
        </script>
        <!-- /end templates -->

        <!-- Tips texts -->
        <div style="display:none">
            <div id="recipients_tip">
                <h3><?php _e('Notification recipients', 'wp-cred'); ?></h3>
                <p><?php _e('You can select multiple recipients for email notifications. Select the check-boxes for different recipient types and their target type (to/cc/bcc).', 'wp-cred'); ?></p>
            </div>
            <div id="additional_recipients_tip">
                <h3><?php _e('Additional notification recipients', 'wp-cred'); ?></h3>
                <p><?php _e('You can enter additional recipients as:<br />email<br />name &lt;email&gt;<br />to/cc/bcc: name &lt;email&gt;<br /><br />If no recipient type is specified, the recipient will be added as \'to\'.<br />Separate multiple recipients with commas.', 'wp-cred'); ?></p>
            </div>
        </div>
        <!-- /End tips texts -->


        <div id='cred_notification_settings_panel_container'>

            <div class="clearfix cred-notification-settings-panel-header">
                <p class='cred-explain-text alignleft'>
                    <?php
                    _e( 'Add notifications to send emails after submitting this form.', 'wp-cred' );
                    echo CRED_STRING_SPACE;
                    $documentation_link = $links_manager->get_escaped_link(
                        CRED_DOC_LINK_NOTIFICATIONS,
                        array(
                            'utm_source' => 'formsplugin',
                            'utm_campaign' => 'forms',
                            'utm_medium' => 'forms-gui',
                            'utm_term' => 'email-notifications'
                        )
                    );
                    echo sprintf(
                        '<a href="%1$s" title="%2$s" target="_blank">%3$s %4$s</a>.',
                        $documentation_link,
                        esc_attr( __( 'Check our documentation', 'wp-cred' ) ),
                        __( 'Check our documentation', 'wp-cred' ),
                        '<i class="fa fa-external-link"></i>'
                    );
                    ?>
                </p>

                <a id='cred-notification-add-button' 
                    class='button button-secondary alignright cred-notification-add-button' 
                    data-cred-bind="{
                        event: 'click',
                        action: 'addItem',
                        tmplRef: '#cred_notification_template',
                        modelRef: '_cred[notification][notifications][__i__]',
                        domRef: '#cred_notification_settings_panel_container',
                        replace: [
                        '__i__', {next: '_cred[notification][notifications]'}
                        ]
                    }">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align:text-top"></span>
                    <?php _e( 'Add new notification', 'wp-cred' ); ?>
                </a>

            </div>

            <?php
            foreach ( $notifications as $ii => $notification ) {
                $templates_data[ 'ii' ] = $ii;
                $templates_data[ 'notification' ] = $notification;

                $renderer->render(
                    $template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_ITEM ),
                    $templates_data
                );
            }
            ?>
        </div>
        <?php
   }
}