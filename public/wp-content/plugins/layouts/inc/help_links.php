<?php

define ( 'WPDDL_CSS_STYLING_LINK', 'https://toolset.com/documentation/getting-started-with-toolset/adding-custom-styling-to-a-layout/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=css-styling-tab&utm_term=help-link' );
define ( 'WPDDL_GLOBAL_JS_LINK', 'https://toolset.com/documentation/user-guides/adding-javascript-code-globally/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=global-js-tab&utm_term=help-link' );
define ( 'WPDLL_LEARN_ABOUT_SETTING_UP_TEMPLATE', 'https://toolset.com/documentation/user-guides/layouts-theme-integration/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=layouts-theme-integration&utm_term=help-link' );
define ( 'WPDLL_LEARN_ABOUT_ROW_MODES', 'https://toolset.com/documentation/user-guides/learn-how-rows-can-displayed-different-ways?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=row-edit&utm_term=help-link' );
define ( 'WPDLL_LEARN_ABOUT_GRIDS', 'https://toolset.com/documentation/user-guides/learn-creating-using-grids?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=grid-cell&utm_term=help-link' );
define ( 'WPDLL_RICH_CONTENT_CELL', 'https://toolset.com/documentation/user-guides/rich-content-cell-text-images-html?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=visual-editor-cell&utm_term=help-link' );
define ( 'WPDLL_WIDGET_CELL', 'https://toolset.com/documentation/user-guides/widget-cell?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=widget-cell&utm_term=help-link' );
define ( 'WPDLL_CHILD_LAYOUT_CELL', 'https://toolset.com/documentation/user-guides/hierarchical-layouts?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=child-layout-cell&utm_term=help-link' );
define ( 'WPDLL_THEME_INTEGRATION_QUICK', 'https://toolset.com/documentation/user-guides/layouts-theme-integration/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=theme-integration&utm_term=help-link' );
define ( 'WPDLL_CONTENT_TEMPLATE_CELL', 'https://toolset.com/documentation/user-guides/content-template-cell/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=content-template-cell&utm_term=help-link' );
define ( 'WPDLL_VIEWS_CONTENT_GRID_CELL', 'https://toolset.com/documentation/user-guides/view-cell/' );
define ( 'WPDLL_VIEWS_LOOP_CELL', 'https://toolset.com/documentation/user-guides/wordpress-archive-cell/' );
define ( 'WPDLL_COMMENTS_CELL', 'https://toolset.com/documentation/user-guides/comments-cell?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=comments-cell&utm_term=help-link' );
define ( 'WPDLL_CRED_CELL', 'https://toolset.com/documentation/user-guides/cred-form-cell?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=cred-cell&utm_term=help-link' );
define('WPDLL_WIDGET_AREA_CELL', 'https://toolset.com/documentation/user-guides/widget-area-cell/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=cred-cell&utm_term=help-link');
define('WPDLL_ACCORDION_CELL_HELP', 'https://toolset.com/documentation/user-guides/accordion-cell/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=cred-cell&utm_term=help-link');
define('WPDLL_TABS_CELL_HELP', 'https://toolset.com/documentation/user-guides/tabs-cell/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=cred-cell&utm_term=help-link');
define('WPDLL_FRONT_EDITOR', 'https://toolset.com/documentation/getting-started-with-toolset/front-end-editing/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=front-end-editor&utm_term=help-link');
define('WPDLL_POST_CONTENT_CELL', 'https://toolset.com/documentation/user-guides/the-content-cell/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=post-content-cell&utm_term=help-link');
define('WPDLL_PRIVATE_LAYOUT', 'https://toolset.com/documentation/getting-started-with-toolset/designing-contents-of-specific-pages/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=layouts-page-builder&utm_term=help-link');
define('WPDLL_PARENT_LAYOUT', 'https://toolset.com/documentation/user-guides/hierarchical-layouts/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=layout-kind-icon&utm_term=help-link');
define('WPDLL_LAYOUTS_STARTER', 'https://toolset.com/documentation/getting-started-with-toolset/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=activate-views&utm_term=help-link');
define( 'WPDDL_VIEWS_ARCHIVE_LEARN', "https://toolset.com/documentation/user-guides/designing-pages-archive-templates-using-views-plugin#layouts-as-templates-for-content?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-archive-templates&utm_term=help-link");
define( 'WPDDL_DISPLAY_POST_TYPES_LEARN', "https://toolset.com/documentation/getting-started-with-toolset/creating-templates-for-displaying-post-types/?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=views-archive-templates&utm_term=help-link");
define( 'WPDDL_BOOTSTRAP_GRID_SIZE', 'https://toolset.com/documentation/user-guides/selecting-the-base-size-of-your-layouts-grid?utm_source=layoutsplugin&utm_campaign=layouts&utm_medium=layouts-bootstrap-grid-size&utm_term=help-link');
define( 'WPDDL_CRED_EDIT_FORMS', 'https://toolset.com/documentation/getting-started-with-toolset/publish-content-from-the-front-end/forms-for-editing/?utm_source=layoutsplugin&utm_campaign=forms&utm_medium=layouts-gui&utm_term=forms-editing-doc');


function ddl_add_help_link_to_dialog($link, $text, $same_line = false) {
    if( $same_line == false ):?>
        <div class="clear"></div>
    <?php
    endif;
    ?>
            <a href="<?php echo $link; ?>" target="_blank" class="ddl-help-link-link">
                <?php echo $text; ?> &raquo;
            </a>

    <?php
    if( $same_line == false ):?>
        <div class="clear"></div>
        <?php
    endif;
}

