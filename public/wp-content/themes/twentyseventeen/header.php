<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'twentyseventeen' ); ?></a>

	<header id="masthead" class="site-header" role="banner">

		<?php get_template_part( 'template-parts/header/header', 'image' ); ?>

		<?php if ( has_nav_menu( 'top' ) ) : ?>
			<div class="navigation-top">
				<div class="wrap">
					<?php get_template_part( 'template-parts/navigation/navigation', 'top' ); ?>
				</div><!-- .wrap -->
			</div><!-- .navigation-top -->
		<?php endif; ?>
	</header><!-- #masthead -->

	<?php

	/*
	 * If a regular post or page, and not the front page, show the featured image.
	 * Using get_queried_object_id() here since the $post global may not be set before a call to the_post().
	 */
	if ( ( is_single() || ( is_page() && ! twentyseventeen_is_frontpage() ) ) && has_post_thumbnail( get_queried_object_id() ) ) :
		echo '<div class="single-featured-image-header">';
		echo get_the_post_thumbnail( get_queried_object_id(), 'twentyseventeen-featured-image' );
		echo '</div><!-- .single-featured-image-header -->';
	endif;
	?>

	<div class="site-content-contain">
		<!-- <div id="content" class="site-content"><div id="translate"><?php if(function_exists("transposh_widget")) { transposh_widget(array(), array('title' => '', 'widget_file' => 'select2/tpw_select2.php'),true); }
				global $my_transposh_plugin;
if ($my_transposh_plugin->is_editing_permitted()) {
                $ref = transposh_utils::rewrite_url_lang_param($_SERVER["REQUEST_URI"], $my_transposh_plugin->home_url, $my_transposh_plugin->enable_permalinks_rewrite, ($my_transposh_plugin->options->is_default_language($my_transposh_plugin->target_language) ? "" : $my_transposh_plugin->target_language), !$my_transposh_plugin->edit_mode);
                echo '<input type="checkbox" name="' . EDIT_PARAM . '" value="1" ' .
                ($my_transposh_plugin->edit_mode ? 'checked="checked" ' : '') .
                ' onclick="document.location.href=\'' . $ref . '\';"/>&nbsp;Edit Translation';
}
?>

		</div> -->

