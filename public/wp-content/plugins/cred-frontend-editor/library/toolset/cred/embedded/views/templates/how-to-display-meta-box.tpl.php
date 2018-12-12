<?php
$links_manager = new OTGS\Toolset\CRED\Controller\LinksManager();
$doc_link_args = array(
    'utm_source' => 'formsplugin',
    'utm_campaign' => 'forms',
    'utm_medium' => 'forms-gui',
    'utm_term' => 'forms-creating-doc'
);
?>
<div class="howtodisplaybox" id="howtodisplay">
    <div id="minor-publishing">
        Read the documentation to learn how to <a href="<?php echo $links_manager->get_escaped_link( CRED_DOC_LINK_FRONTEND_CREATING_CONTENT, $doc_link_args, 'displaying-toolset-forms' ); ?>">display Toolset forms</a>.
        <div class="clear"></div>
    </div>
</div>