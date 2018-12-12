<!-- PREVIEW -->
<script type="text/html" id="ddl-generic-dialog-tpl">
	<div id="js-dialog-dialog-container">
		<div class="ddl-dialog-content" id="js-dialog-content-dialog">
			<?php printf(
				__('%sTo see the preview, you need to allow this page to show popups.%sHow to enable popups in your browser%s', 'ddl-layouts'),
				'<p>',
				'<br><a href="https://toolset.com/documentation/user-guides/enable-pop-ups-browser/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=enable-pop-ups-browser&utm_term=help-link" title="enable popups" target="_blank">',
				'</a></p>'
			);
			?>
			<p>
				<label for="disable-popup-message"><input type="checkbox" name="<?php echo WPDD_GUI_EDITOR::POPUP_MESSAGE_OPTION; ?>" value="true" id="disable-popup-message"> <?php _e('Don\'t show this message again', 'ddl-layouts'); ?></label>
			</p>
		</div>
	</div>
</script>