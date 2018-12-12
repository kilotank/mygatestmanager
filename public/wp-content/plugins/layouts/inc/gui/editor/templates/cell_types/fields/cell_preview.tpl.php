<?php
/**
 * Fields cell preview
 *
 * @since 2.4
 */
?>

<div class="cell-content">
	<p class="cell-name"><?php _e( 'Field content', 'ddl-layouts' ); ?></p>
	<div class="cell-preview">
		<p class="cell-preview-desc"><?php _e( 'Displays the content of a Custem Field related to the current post', 'ddl-layouts' ); ?></p>
		<p class="ddl-field-preview preview-centered">
			<span class="{{ content.icon }}"></span>
			{{ content.field }}
		</p>
	</div>
</div>
