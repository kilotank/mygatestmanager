Version 2.5.1
 - Bug-fix: Fixed issue with hierarchical Parent Layout GUI showing unexpected string instead of the GUI when no Child Layout Cell is present in the Layout
 - Bug.fix: Fixed bug in loading sequence to restore Tabs and Accordion cells which were not loaded anymore because the filter they were hooking into was applied before they added it.
 - Bug.fix: Fixed Fatal Error when trying to modify Layouts Default Bootstrap Column Width settings.
 - Bug.fix: Fixed Warnings for "wp_enqueue_script" and "wp_add_inline_script" called incorrectly.

Version 2.5
 - Compatibility: Provides Content Layout compatibility with Gutenberg so that existing content rendered with Content Layouts can be managed in new editor too
 - Bug-fix: Fixed "Scripts and styles should not be registered or enqueued until the wp_enqueue_scripts hook" thrown when opening Toolset iFrame

Version 2.4.3
 - Feature: New options to allow user to decide whether Layouts CSS and/or JS should be loaded site-wide or only when the page renders with Layouts
 - Performance: Implemented routines to programmatically clean-up cached CSS and JS files from upload directory
 - Bug-fix: Fixed issue with save layout settings flow to prevent record duplication
 - Bug-fix: Fixed issue with WPML Packages list always showing previous layout data version rather than updated one
 - Bug-fix: Fixed issue with "wpv-post-body" shortcode in Content Template cell when renders a selected page in Content Layout context

Version 2.4.2
 - Feature: Added new option to Comments cell to let the user decide to print comments navigation either before or after the comments list.
 - Usability: Added tooltips to explain the difference between Layouts file system export and theme directory exports functionality.
 - Usability: Added feedback messages for when settings are imported successfully.
  - Performance: Reviewed and made sure all our scripts and styles in front end are loaded only when strictly necessary (used).
 - Bug-fix: Makes sure special characters are not converted to entities when Layouts cells text fields are escaped, while still stripping tags and sanitise strings for security.
 - Bug-fix: Makes sure Layouts settings .JSON file is handled on import also when uploaded as a single file out of a .zip file context.
 - Bug-fix: Makes sure Layouts settings .js file is handled on export/import also when uploaded as a single file out of a .zip file context.
 - Bug-fix: Fixes editing issues with Front End editor when multiple levels of parenthood are present in the same page: e.g. Layout Ancestor, Layout Parent, Layout Child etc.
 - Bug-fix: Fixed issue with password protected posts form with integrated themes rendering multiple times.
 - Documentation: Updated help links for Toolset Forms


Version 2.4.1
 - Compatibility: Made Layouts Options API compatible with Multisite and made sure cached values refresh when the blog is switched
 - Compatibility: Made Layouts custom cells repeating fields render in [wpml-strings] when in Content Layout context to force translation even if rendered content is cached
 - Compatibility: Added query string variable "?ver=VERSION" to script loaded with Head.js to prevent cached version loading when plugin updates 
 - Bug-fix: Made sure save overlay is removed - or not printed at all - if the layout cell/row don't require an ajax re-render when edited with front end editor
 - Bug-fix: Fixed warnings when layouts list misses has NULL items or items in the list miss some default properties, in Layouts listing page preventing Backbone.Model to parse JSON correctly
 - Bug-fix: Made sure Relationship Form cell short-code is parsed correctly when the cell renders in front-end


Version 2.4
 - Feature: Re-design Layouts listing page moving previous sections into tabs for a better and more organised design and performance improvement
 - Compatibility: Fixed issue with Layouts Archives preview when Woocommerce is active

Version 2.3.1
 - Compatibility: Ensured strings translated with "wpml-string" short code are exported and imported in new site correctly preserving their status in String Translation table and rendering in Front-end
 - Compatibility: Fixed issue with row/cell data (tags, classes and id) missing in WPML Content Layouts translations
 - Compatibility: Ensured Dynamic Sidebars created with Widget Area cells work in WPML tranlsations too 
 - Compatibility: Ensured compatibility with Virtue theme
 - Compatibility: Ensured compatibility with Wordpress feeds / RSS templates
 - Bug-fix: Fixed issue with Views Grid cell when rendering  parametric form and results in the same page into 2 different cells using Content Layout
 - Bug-fix: Fixed issue with posts and pages loosing their individually assigned Template Layout when their Content Layout is saved, in favour of the Template Layout assigned to their entire post type
 - Bug-fix: Fixed issue with Toolset Maps short code not rendering correctly when used in a Visual Editor cell edited with Front-end editor
 - Bug-fix: Fixed issue with Front-end editor not rendering overlays and editing controls for Content Layout
 - Bug-fix: Fixed issue with invalid JSON for Content Layout when copied / pasted to a new post
 - Bug-fix: Fixed other small cosmetic issues in editor and Front-end editor


Version 2.3
  - Usability: Explicitly marked layout kind icon as clickable and providing options
  - Usability: Improved usability for auto-suggest functionality for Tag Classes control in Layouts cells dialog
  - Usability: Removed elements that are not necessary in Views cell dialog to make more space
  - Usability: Added new tooltip to explain hierarchy settings icon
  - Compatibility: Fixed issue with WPML String shortcode context and name attributes not working in Layouts
  - Compatibility: ensure Bootstrap Tabs first tab is not marked as "active" when Tabs are not rendered by Layouts in a Layouts rendered page  
  - Bug-fix: Fixed issue with invalid Content Layouts JSON upon copy paste to a new or existing post
  - Bug-fix: Fixed issue with Theme Options sections not expanding correctly on slide-down
  - Bug-fix: Fixed issue with add shortcode button hidden in some popups
  - Bug-fix: Fixed issue with users with "assign layout to content" capabilities not able to un-assign a Layout after being assigned
  - Bug-fix: Fixed issue with Visual Editor Cell rendering 3rd party content more than once
  - Bug-fix: Fixes issue with copy clipboard when JSON textarea is not empty
  - Bug-fix: Moved all iFrame dialogs 40px to top to prevent button bar to be hidden behind outer dialog footer
  - Bug-fix: Raised execution priority to print admin pages to make sure that Layouts pages are printed after CRED
  - Bug-fix: Fixes issue with 'Stop using this Layout' link when user can only assign Layout
  - Bug-fix: Fixed issue with inserting css classes in Layout cells.
  - Bug-fix: Fixed several notices related to issues when using PHP 7.x
  - Bug-fix: Fixed problem with assigning Layout to archive, it was issue only with PHP 7+
  - User-interface: Renamed CRED to Toolset Forms
  - Feature: Added setting to enable/disable the_content filter from GUI
  - Feature: New Layouts cell implemented - CRED Relationship forms. It is possible now to insert CRED Relationship Form directly from Layouts using new cell.
  - Feature: Provide integration with Wordpress post password protection feature for Integrated Themes


Version 2.2
  - Bug-fix: Fixed issue with keeping old value in css editor in case when browser is reopened before without refresh
  - Bug-fix: Fixed issue with registering URL inside Image Box Cell for translation
  - Bug-fix: Fixed problem with missing ending semicolons for HTML special char "&quot"
  - Bug-fix: Fixed issue with loosing layout assigned when saving Draft
  - Bug-fix: Fixed broken WPML language switcher widget
  - Usability: Changed example video link in YouTube cell
  - Usability: Implemented "Save" button that will keep you on Content layout editor after saving instead of redirecting back to post editor
  - User-interface: Few typos fixed
  - Theme Compatibility: Fixed problem with OceanWP theme option to hide sidebar for WooCommerce product page


Version 2.1
 - Theme Compatibility: Improved compatibility layer for Genesis, Divi, Avada, Astra, Generate Press and Ocean WP themes  
  - Theme Compatibility: Added controls to hide/show elements such as header, sidebar, featured image, title etc per layout, in Genesis, Divi, Avada
  - Theme Compatibility: Improved Theme Settings compatibility for Astra, Generate Press and Ocean WP themes
  - User-interface: Fixed z-index problem for tag selector inside tinyMCE editor on Layouts editor page
  - Usability: Removed Front End editor button when previewing Layout
  - Bug-fix: Fixed problem with assigning Layouts to wrong after import when it is explicitly set "Don't use layout"
  - Bug-fix: Fixed issue with WPV conditional shortcodes when used with Post Body shortcode on WooCommerce product page
  - Bug-fix: Fixed issue with single Text Widget that was not showing options after update wordpress to the latest version
  - Bug-fix: Fixed issue with Media Widgets new GUI in Widget cell
  - Bug-fix: Fixed problem with assigning Layout to 404 pages for non-integrated themes
  - Bug-fix: Solved problem with active tabs panels when nested tabs structure is created.
  - Bug-fix: Fixed z-index issue with HTML style selector in Layouts Visual Editor cell.
  - Bug-fix: Fixed console error when trying to create a Content Layout
  - Bug-fix: Solved problem with PHP warning for Layouts assignment dialog when using PHP7
  - Bug-fix: Layout preview is triggered only on left mouse click on the preview button, this was usability problem.
  - Bug-fix: Fixed issue with wpautop and paragraphs rendering for Visual Editor cell
  - Bug-fix: Made sure Layouts custom CSS file is enqueued as the last style in the head of the document
  - Security: Prevent script tag to be used inside comments text area in Comments cell
  - Security:Prevent script tag to be used inside Layouts custom CSS editor


Version 2.1-b1

 - Theme Compatibility: Added controls to hide/show elements such as header, sidebar, featured image, title etc per layout, in Astra, Generate Press and Ocean WP themes

Version 2.0.3

 - Peformance: Included in CSS classes and CSS id scanning only published template layouts to reduce memory usage on large websites
 - Bug-fix: Ensured changes in Content Layout can be previewed also when rendered in Template Layout context
 - Bug-fix: Removed "full-width" generic CSS class to avoid conficts with 3rd party plugins and themes
 - Bug-fix: Fixed positioning of loading gif when layout name or slug are edited

Version 2.0.2

 - Feature: Made possible for layouts assigned to archives to work as templates with non integrated themes
 - Feature: Made possible for Content Layout to be rendered in Template Layout in non integrated themes
 - Feature: Implemented Layout storage feature to make layouts structure import/export easy
 - Compatibility: Fixed compatibility issues between Layouts and Elementor plugin
 - Compatibility: Fixed compatibility issues between Layouts and Easy Digital Downloads plugin
 - Theme Compatibility: Implemented minimal theme compatibility classes for Twenty Sixteen, Twenty Seventeen, Genesis, Avada, Divi, Astra, Generate Press and Ocean WP
 - Theme Compatibility: Added controls to hide/show elements such as header, sidebar, featured image, title etc per layout, in Astra, Generate Press and Ocean WP themes
 - User-interface: Prevented Toolset cells to perform ajax request to server to preview their changes (tag, CSS id, CSS classes) in front end editor
 - User-interface: Link to the video tutorials page added in help section
 - User-interface: Fixed cosmetic problem with Undo/Redo buttons in Layouts editor
 - Cosmetics: Fixed few small cosmetic issues on Layouts editor
 - Cosmetics: Changed YouTube video example since video was not available anymore
 - API: Created filter to return list of all posts that uses certain Layout
 - API: Created API to handle Woocommerce conditionals as Layouts Wordpress filters
 - Bug-fix: Fixes issue with Woocommerce archive cell not showing its content when some changes are performed in front end editor
 - Bug-fix: Fixed issue with WooCommerce archive title after updating cell with title shortcode using front-end editor
 - Bug-fix: Fixed issue with CSS classes not saving in cell model and in corresponding DOM element when changed in front end editor
  - Bug-fix: Fixed issue with courtesy message dialog for layout preview when pop up are blocked and only one assignment exists for the current layout
  - Bug-fix: Fixed issue with ghost items in Layouts selector in post edit page, when pages assigned to a template layout are exported and then imported without the layout they were originally assigned to. Those ghost records are prevented to show and they are removed from the database as soon as the ghost association is detected by accessing post edit page
  - Bug-fix: Fixed issue with cells rendering complex markup, plus CSS and JS snippets in Multisite when Content Layout has been created by non-super-admin user
  - Bug-fix: Fixed wrong help video alignment in Content Layout when user sets high resolution
  - Bug-fix: Fixes bug of Layouts assigned to Archives not working when the main query didn't have at least one post, even if the layout was displaying other content
  - Bug-fix: Fixes bug with Slider cell autoplay option


Version 2.0.1

 - Compatibility: Added Content Integration with Divi theme included as a Layout inner module loaded on demand
 - Compatibility: Ensured CRED User cell is creates shortcodes in Content/Template layouts using form slug rather than its title for import/export compatibility
 - GUI: Front-end editor button removed from Layout preview screen
 - Bug-fix: Fixed error when layout assignent is removed Woocommerce shop page previously assigned to a layout
 - Bug-fix: Fixed problem with import/export layouts and wrong assignments when entire post type is assigned and some of its items are not assigned
 - Bug-fix: Forced removing classes related to disabled button after removing disabled attribute from button
 - Bug-fix: Various small cosmetic fixes

Version 2.0

 - User-interface: Added GUI to control Bootstrap columns size per Layout
 - User-interface: Added icon and tooltip to quickly provide markup and attributes information about cells
 - User-interface: Removed distracting elements from Views iFrame and button to save Views elements from main dialog
 - Cosmetics: Changed Slider cell previous and next buttons icons from text only elements to Boostrap library icons in a placeholder
 - Compatibility: Fixed compatibility problem with Woocommerce and Woocommerce Views causing infinite loop when rendering in the same Layout wpv-post-body and wpv-woo-display-tabs Views short codes
 - Compatibility: Fixed issue with Woocommerce product loosing Template Layout when updated
 - Compatibility: Added the possiblity to define a layout to display a CRED Post/User form on demand
 - Compatibility: Fixed issue with Divi builder not loading properly in Post Content cell body
  - API: Added filters to override Slider cell previous and next buttons icons classes to change them or customise them
  - API: Added the possibility to override Template Layout output through URL agument "layout_id=ID"
  - API: Added filter "ddl-filter_layouts_by_cell_type" to filter layouts list by any combination of cells' property/value pair
  - Bug-fix: Fixed issue when rendering search archives with Layouts (e.g. Woocommerce product search)
  - Bug-fix: Fixed show/hide more posts assigned to layout issue in Layouts listing page
  - Bug-fix: Fixed issue with wpautop when rendering Visual Editor cell in Content Layout

Version 1.9.3

 - Compatibility: Content Layouts translations get content from original post Content Layout only when edited with WPML Translation Editor
 - User-interface: Added Global Javascript help link
 - User-interface: Removed broken CSS Styling help link
 - Bug-fix: Fixed cosmetic problem for Content Layout title overflow


Version 1.9.2

 - Feature: Implemented changes necessary for supporting Migration plugin
 - User-interface: Fixed styling problem for template layouts selector in case when user is not allowed to change template layout
 - Bug-fix: Select2 library replaced with Chosen for selectors with multiple select
 - Bug-fix: Fixed issues with passing Additional css classes from main dialog to CRED or Views iframe and back (Layouts editor)
 - Bug-fix: Fixed JavaScript error caused by compatibility between WooCommerce and new template layout selector
 - Bug-fix: Fixed permission issue with template layout selector, only users with permission to change layout are able to run ajax requests.
 - Bug-fix: Fixed problem with layout auto assignment in case when user with editor role creates the post
 - Bug-fix: Fixed styling issue with full width row padding in Content Layout

Version 1.9.1

 - Bug-fix: Fixed issue with special characters display in Row, Tab and Accordion names.
 - Bug-fix: Fixed issue with single page template assignment, when assigned together with entire post types.
 - Bug-fix: Fixed issue with events registration in front end editor dialogs when opened from wrench tool.
 - Bug-fix: Fixed issue with filter callback registration for Tabs tab class name override.

Version 1.9

 - Added content layouts to design specific pages in a page builder mode.
 - Added frontend editing mode for layouts.
 - Added post content cell in template layouts that renders content of the page.
 - Added feature in Toolset settings to choose different options for loading Bootstrap library.
 - Improved row modes to ensure that they are rendered properly within the container.
 - Improved integrations with supported themes.
 - Improved layouts editor providing more information about the current editing layout.
 - Improved the saving and preview mechanisms, providing feedback to the user about status change.
 - Improved the template layout selector and separated from page attributes.
 - Improved Views cell dialog, setting "Full custom display" mode as default type.
 - Improved information message about not assigned layout, providing a button to create Layout for the entire post type.
 - Improved "Design with Toolset" admin bar menu, making it visible only when there is no frontend editor button.
 - Improved assignment dialog, by moving individual pages box to the bottom and adding an information message.
 - Improved the Layouts listing page, display the most relevant sections on top
 - Improved compatibility with WPML, adding support for translatable strings in cells that contain text.
 - Improved meta key name for Layouts settings, changing from “dd_layouts_settings” to “_dd_layouts_settings”
 - Improved Layouts editor toolbar, changing the background and width.
 - Improved overall performance
 - Fixed an issue when adding custom classes to Layouts rows and cells.
 - Fixed an issue with target editor instance for Bootstrap component module.
 - Fixed an issue with extra upper space, when the row was as wide as Bootstrap container and its background extended to full width.
 - Fixed some issues regarding preview functionality.
 - Fixed an issue with nested Tabs cells.
 - Fixed an issue with colorpicker position in Visual editor cell.
 - Fixed an issue when inserting a CRED cell in a layout, when using Firefox or Safari.
 - Fixed an issue with Views cells that were not working in Firefox.
 - Fixed an issue with PHP notices in a fresh WordPress install.
 - Fixed compatibility issues with PHP 5.2.
 - Fixed compatibility issue with Divi theme about Toolset dialogs.

Version 1.8.9

  - Feature: Accordion cell title are clickable to expand/collapse the collapsible item
  - Bug-fix: Tabs and accordions CSS ids sanitised against special characters that were breaking the Javascript execution
  - Bug-fix: Fixed problem when inserting CRED Form and CRED Form User cell with Firefox
  - Bug-fix: Fixed issue with CRED Form preview when Layouts was active and assigned to home page
  - Security: prevent empty values to be added to Layouts create ajax request to avoid XSS vulnerability on empty width field

Version 1.8.8

  - Bug-fix: Reviewed and fixed all select2 edge cases related to additional classes save, rendering in form and rendering in front end
  - Bug-fix: Fixed warning in Javascript/CSS manager class

Version 1.8.7

  - Bug-fix: Fixes issue with select2 error when saving Layout when WPML active

Version 1.8.6

  - Bug-fix: Fixes select2 edge cases when methods are called on non-select2 initialised element
  - Bug-fix: Refines special handling of old inputs by making sure target is only a select and not the hidden relative element

Version 1.8.5

  - Bug-fix: Fixes Type error when user creates new row with given columns in Grid cell

Version 1.8.4

  - Feature: Added control to add / remove 15px left and right padding from containers elements
  - Feature: Added control to Menu cell to control menu floating, left or right, when rendering in front end
  - Compatibility: Upgraded select2 library to 4.0.3 version
  - Bug-fix: Fixes issue with CRED preview not rendering with some themes

Version 1.8.3

  - Bug-fix: Removes all "row-fluid" class names in favour of ".row" class only

Version 1.8.2

  - Bug-fix: Changes "row-fluid" class back to row to ensure 15px margin left/right

Version 1.8.1

  - Bug-fix: Changes "row-fluid" class back to row to ensure 15px margin left/right


Version 1.8

  - Feature: Re-enabled Widget Area Cell with ability to add custom Widget Areas.
  - Feature: Added Bootstrap Tabs cell: user can create tabbed structures as containers for other Layouts/Bootstrap structures
  - Feature: Added Bootstrap Accordion cell: user can create accordions as containers for other Layouts/Bootstrap structures
  - Feature: Added new buttons to tinyMCE and CodeMirror editors, to easily access Bootstrap CSS and Javascript components description and link
  - Feature: Implemented global Javascript editor
  - User-interface: Added copy/paste row functionality
  - User-interface: Prevented parent layout to display in "Not assigned" section when all its children have been assigned
  - User-interface: Added dialogs and proper functionality to let the user remove layout assignments when Child Layout cell is created
  - Compatibility: improved compatibility with CRED
  - Compatibility: Ensured compatibility with WordPress 4.6
  - Compatibility: Ensured compatibility with Bootstrap 4
  - Compatibility: Ensured compatibility with W3C Html validators for when CSS is loaded in footer
  - API: Created filter hook to check unobtrusively if Layouts is active
  - Security: Made sure protocol is https when loading dynamically generated CSS file under SSL
  - Bug-fix: Fixed posts and pages loosing layout assignment when user editing was not admin
  - Bug-fix: Fixed some little bug with Menu cell
  - Bug-fix: Fixed some little issues with template/layout selector in different themes
  - Bug-fix: Fixed issue with img-responsive class in some critical rendering positions
  - Bug-fix: Fixed issue with Vimeo embedded video rendering
  - Bug-fix: Fixed issue with Layouts usage layer when "Show all templates" is clicked

Version 1.7

  - Feature: Change default parent layout directly from Layout edit page, by clicking on "set parent layout" button
  - Feature: New Layouts shortcode to check if a Toolset plugin is active
  - Feature: New Layouts shortcode to check current user role
  - Feature: Separated archive for Home and Blog (in case when static pages option is enabled from Reading setting)
  - Feature: Added the possibility to turn on/off integrations cells based on conditions
  - User-interface: Improved editor usability by controlling scroll behaviour after render action takes place
  - User-interface: Added the possibility to bulk assign layout for an entire post type upon creation or to assign it only to the post type future entries or to the current entry only
  - User-interface: Added the possibility to set a default parent layout in "Set parent layout" dialog for current layout
  - User-interface: Added the possibility to set the current layout as the default parent and a way to display if current layout is the default parent
  - User-interface: Reviewed dialogs usability and made them compliant with WordPress design
  - User-interface: Added Toolset video help displayed in strategic time and place if the user needs help
  - User-interface: Added new column to display layouts IDs in Layouts listing page
  - User-interface: Messages about Views requirement for all Views cells updated
  - User-interface: Message about theme integration is now dismissible
  - User-interface: Added delete layout button in Layouts editor - if the layout is assigned can't be deleted and the user is warned
  - Compatibility: CRED button to insert CRED forms and form links shortcodes in Visual Editor and Content Template cells
  - Compatibility: Full native compatibility with Beaver builder
  - Compatibility: Only group of users with administrator permissions are allowed to add CRED cells to layout
  - Bug-fix: Fixed an issue with content template exclusion, so that when editing a Content Template, the current one should be avoided.
  - Bug-fix: Fixed problem with assigning layout when we don't have any template file
  - Bug-fix: Issue with image box cell height is fixed
  - Bug-fix: Fixed problem with iFrame loading animation for CRED and Views cells
  - Bug-fix: Fixed problem with showing content preview for Content Template cell
  - Bug-fix: Couple of bug fixes related with save_post hooks.
  - Bug-fix: Fixed missing widget_id argument for 3rd party widgets, when used via Single Widget Cell
  - Bug-fix: Fixed WPML double language switcher in Layouts editor page
  - Bug-fix: Fixing cells prefixing and doubling rows issue when using Thrive Builder with Layouts.
-------------------------------------------------------------------------------------------------------------------

Version 1.6

  - Feature: New direct create layouts process
  - Feature: New children list in parent layout preview
  - Feature: Updated and re-enabled BuddyPress cell to render default BuddyPress pages with BP latest version
  - User-interface: Integrated Layouts menu elements in Toolset menu
  - User-interface: Expanded draggable area to the whole Row surface
  - User-interface: New "All changes saved" and "Saving" messages on save actions
  - User-interface: Improved feedback if changes are not saved in assignment dialog
  - User-interface: Integrated Layouts settings elements in Toolset settings page within a dedicated tab
  - User-interface: Integrated Layouts import/export forms in Toolset import/export page within a dedicated tab
  - User-interface: Improved editor scroll re-positioning when elements are created, edited, resized, moved
  - User-interface: Improved interaction messages for single page assignment in assignment dialog
  - User-interface: Added number of children available and help text to guide user to children editing in Child Layout cell
  - Compatibility: WPML language switcher integration in listing page and editor page
  - Compatibility: Improved integration with Divi theme
  - Compatibility: Upgraded to Backbone 1.3.2
  - Compatibility: Upgraded to Underscore 1.8.3
  - Compatibility: Full compatibility with ACF Plugin
  - Compatibility: Improved and maximised compatibility with Woocommerce
  - Compatibility: Provided out of the box compatibility between Woocommerce and Genesis
  - Compatibility: Fixed Conflict between Layouts and Sage theme
  - Performance: Refactored and optimised listing page query methods to improve initial loading and ajax calls responses
  - Usability: Included cell description as a search field to filter cells in cell creation dialog
  - Usability: Improved descriptions for Content Template and Visual Editor cells
  - Security: Added programmatic sanitization of Layouts properties against SSI injection
  - Bug-fix: Fixed bug that prevented undefined post type to be unassigned and removed generated warning
  - Bug-fix: Fixed bug in layouts router with Woocommerce shop page
  - Bug-fix: Fixed bug that prevented undefined archive to be unassigned
  - Bug-fix: Fixed conflict with Toolset Bootstrap theme
  - Bug-fix: Fixed Menu cell bug with submenu items render in mobile browsers
  - Bug-fix: Fixed bug in Image alignment not working on frotnend if "Display responsive image" option is enabled
  - Bug-fix: Fixed Images added via Image box cell don't display alternative text bug
  - Bug-fix: Fixed Input field for cell class tag is too wide in iframe editors visual bug
  - Bug-fix: Fixed important bug when we have multiple levels of nested shortcodes and some of them are displaying a form textarea
  - Bug-fix: various bug fixing

-------------------------------------------------------------------------------------------------------------------

Version 1.5.1

- Feature: Default parent option GUI in settings page
- Feature: Menu cell placeholder message when no menu is defined in the theme
- Feature: Full Integration API
- Feature: Layouts Options API
- Bug-fix: Fixed Module Manager compatibility issue on layouts import

-------------------------------------------------------------------------------------------------------------------

Version 1.5
  - Feature: Created settings to render fall back content when layout is not assigned to a resource: can be either a message, a template or a default layout
  - User-interface: Added infinite scroll to "Show All" tab in "Assign Layout to Content" dialog.
  - User-interface: Added infinite scroll to "Search" tab in "Assign Layout to Content" dialog.
  - User-interface: Added infinite scroller to "Assign layouts to content" dialog "View all" tab
  - API: Full Theme Integration API.
  - API: Full Framework API.
  - API: Empowered Utils API.
  - Bug-fix: Fixed possible issue with Layouts CSS rendering with Woocommerce templates.
  - Bug-fix: Fixed possible issue when duplicating a Layout whose resources were externally deleted.
  - Bug-fix: Fixed compatibility issue with WP Super Cache.
  - Bug-fix: Fixed cache do not refresh after layout asssignment issue.
  - Bug-fix: Fixed parent do not refresh select box in "Create New Layout" dialog after parent has been deleted.

-------------------------------------------------------------------------------------------------------------------

Version 1.4.4
  - Bug-fix: Fixed issue with WordPress 4.4: row edit controls do not display.
  - Bug-fix: Fixed height discrepancy for missing cells

-------------------------------------------------------------------------------------------------------------------

Version 1.4.3
  - Bug-fix: Fixes duplication problem for cell creation description box when cells' rows are more than one.

-------------------------------------------------------------------------------------------------------------------

Version 1.4.2
  - Bug-fix: Added case for widget translation for when title sub-field is not explicitly declared

-------------------------------------------------------------------------------------------------------------------

Version 1.4.1
  - Feature: Added "show more" functionality to "Show where used" box in Layouts editor to show paginated assignment
  - Compatibility: Addeded generic handler for save post hook to handle automatic layouts assignment regardless the post is created
  - Compatibility: Addeded filter to add additional row mode GUI
  - Compatibility: Addeded filter to override row rendering in front end
  - Compatibility: Addeded filter to push new Layouts compatible templates from plugins
  - Compatibility: Addeded filter to force assignment dialog to render single posts and pages even if no compatible templates are present in theme's folder
  - Refactoring: Rewritten all default Layouts cells in Object Oriented Programming style
  - Bug-fix: Fixed bug in CSS sanitisation

-------------------------------------------------------------------------------------------------------------------

Version 1.4
  - Feature: Implemented Toolset resources duplication when duplicating a layout and new GUI to allow granular control on what's going to be duplicate
  - Feature: New Image Box cell implementing full WordPress and Bootstrap features
  - User-interface: Moved CSS Editor in a separate admin screen
  - User-interface: Brand new and simplified GUI for Content Template cell
  - User-interface: Improved controls organisation for Content Template cell, existing content templates are presented first to prevent useless elements creation
  - User-interface: Brand new and simplified GUI for Comment cell
  - User-interface: Brand new and simplified GUI for Views cell
  - User-interface: Brand new and simplified GUI for Grid cell
  - User-interface: Brand new and simplified GUI for Child Layout cell
  - User-interface: Removed cells and rows names inputs in favor of automatic generation of names
  - Compatibility: Brand new Layouts Framework API to run Layouts with CSS Framework other than Bootstrap (expertimental)
  - Bug-fix: Fixed a bug with CRED edit mode when rendering the CRED form in a Content Template cell
  - Bug-fix: Fixed method to check whether a php template supports Layouts or not in child themes
  - Bug-fix: Fixed method to check whether a layout is assigned to Blog page when not Home
  - Bug-fix: Fixed compatibility with php 5.2.x and prevented fatal errors
  - Bug-fix: various bug fixing
  - Security: Added programmatic sanitization of Layouts properties against SSI injection
  - Security: Improved editor nonce/referrer check when layout data is saved to the database

-------------------------------------------------------------------------------------------------------------------

Version 1.3.3
  - Bug-fix: Fixed compatibility problem with CRED 1.4.x

-------------------------------------------------------------------------------------------------------------------

Version 1.3.2
  - Bug-fix: Fixed undefined index in Layouts renderer when Content Template cell does not exist in current Layout rendered

-------------------------------------------------------------------------------------------------------------------

Version 1.3.1
  - Bug-fix: Fixed problem with Fields dialog positioning when opened in iFrame
  - Bug-fix: Fixed javascript error when Content Template cell has been deleted outside of Layouts
  - Bug-fix: Prevented javascript errors when Cells have a numeric name or name is not of string type

-------------------------------------------------------------------------------------------------------------------

Version 1.3
  - Feature: Brand new CRED User Form cell
  - Feature: WPML Language Switcher integration for "Change Use Dialog"
  - Feature: Brand new Settings Page
  - Feature: Enable disable cells with the as they were features, with the Feature API
  - Feature: Show / hide "Design with Toolset" Admin Toolbar button from Layouts Settings
  - Feature: Control the maximum number of posts to be refreshed when a layout has been saved - for caching plugin users
  - User-interface: "Change Use Dialog" shows content items in the right language
  - User-interface: Renewed and improved feedback GUI when importing layouts
  - User-interface: Added fatal error handler in case a fatal error occurs during import
  - User-interface: Disabled CSS tab and provided courtesy message in Child Layout cell and row dialog, when they don't render in front-end
  - User-interface: Improved "Remove layout assignement" GUI
  - User-interface: Added courtesy feedback message to the users in Listing Page, when they don't have the rights to perform an action
  - Compatibility: Further empowerment of WPML integration
  - Compatibility: Full compatibility with Module Manager
  - Compatibilty: Full compatibility with Woocommerce / Woocommerce Multilingual
  - Compatibility: Integration with caching plugins to refresh posts status automatically once the layout assigned to the resource has been edited
  - Performance: Improved performance in import layouts script
  - Performance: Improved performance to populate Layouts selector in post edit page
  - Bug-fix: Fixed bug with private and password protected pages when layout is assigned
  - Bug-fix: Fixed bug with second level links in menu cell on IOS platforms
  - Bug-fix: Fixed conflict between CRED and Content Template cell in front-end rendering, and generally improved compatibility with CRED
  - Bug-fix: Fixed graphic bug in CRED Form dialog and face-lift dialog GUI
  - Bug-fix: Fixed Visual Editor cell bug causing conflicts with WPML String Translation module
  - Bug-fix: Fixed bug causing pages loosing their layouts when edited with CRED Form
  - Bug-fix: Fixed bug causing posts to be created without their layouts - if assigned to their entire post type - if created with Types parent / child GUI
  - Bug-fix: Fixed bug causing a javascript error when a Child Layout cell is edited from the parent
  - Bug-fix: Fixed javascript error in front end when using ajax pagination with Comments cell
  - Bug-fix: Fixed javascript error in editor when Visual Editor cell is disabled


-----------------------------------------------------------------------------------------------------------------
Version 1.2
  - User-interface: New "Design with Toolset" button to help select the right tool to design given resource content rendering
  - User-interface: Added overlay to highlight editable area in Layouts editor
  - User-interface: Added progressive loading to populate posts selectors in Content Template cell for performance and usability
  - User-interface: Improved interaction in Content Template cells dialog to avoid possible input errors
  - User-interface: Renewed and improved GUI to remove single pages assignments
  - User-interface: Renewed and improved post edit page template/layouts selector to help select the right template for the given content
  - User-interface: Added Undo / Redo functionality to CSS editor
  - Performance: Reduced number of render calls and optimised the Layouts editor loading process
  - Performance: Implemented full background saving to allow users to perform actions during all ajax calls
  - API: Added filters to override $posts assignments at the time of database save
  - API: Added filters to override layout settings when saved to the database
  - API: Added filters to override layout $post data when saved to the database
  - API: Added new functions to the fields API to retrieve and print images handled by Layouts based on sizes (thumbnail, medium, large)
  - Compatibility: Created helper to clean orphaned Content Template cells and keep cells data consistent with Views data
  - Compatibility: Fixed compatibility issue with new Views fields insertion process
  - Compatibility: Fixed compatibility issue with other plugins using Underscore templates
  - Compatibility: Fixed compatibility problem with Views Embedded content templates
  - Compatibility: Implemented full compatibility with WPML
  - Compatibility: Implemented automatic layouts assignment for $post translations
  - Compatibility: Improved compatibility with Toolset Starter theme
  - Compatibility: Added ukraine language
  - Security: Improved method to load custom cells from theme to enhance security and prevent errors
  - Security: Added method to strip all html tags from Layouts element names to prevent malicious code injection
  - Bug-fix: Fixed Visual Editor cell tinyMCE bug when inserting a link
  - Bug-fix: Fixed problem when saving Content Template with empty name
  - Bug-fix: Fixed inconsistent height related to cell content issue for Content Template cell and Visual Editor cell
  - Bug-fix: Fixed inconsistent height issue of cells in a Row in relation to highest

------------------------------------------------------------------------------------------------------------------
Version 1.1
  - Feature: Added Embedded Mode to integrate Layouts in your theme
  - Feature: Improved import / export functions to update existing Layouts when importing
  - Feature: Added Layouts capabilities to create custom user roles
  - Feature: Added support for the Attachment (Media) Post Type
  - Feature: Full translation of the Layouts plugin in 5 languages
  - User-interface: Added new mode for HTML editor in Visual Editor cell
  - User-interface: Improved text editor for Visual Editor cell to make it more flexible and usable
  - User-interface: Added loading overlay and better user feedback for "Change layout use" dialog first loading
  - User-interface: Re-styled the Layouts Editor GUI
  - User-interface: Added keyboard interactions in cells creation dialog
  - API: Added hooks for cells render action (ddl_before_frontend_render_cell, ddl_after_frontend_render_cell)
  - API: Added hook for cells render filter (ddl_render_cell_content)
  - API: Added API function to check if a resource is assigned to a layout (is_ddlayout_assigned) working with any WordPress resource type
  - API: Added API functions to check current user capabilities in templates
  - API: Made general re-factoring of the code base and improved class organization
  - Compatibility: Added full Integration with Access Plugin
  - Compatibility: Integrated automatic resources updates for themes based on Layouts
  - Compatibility: Empowered WPML integration (still ongoing)
  - Compatibility: Added better integration with child themes
  - Security: Made full security review, every CRUD operation checks user capabilities
  - Bug-fix: Improved interaction when editing rows and row names
  - Bug-fix: Improved dialog iFrame display on narrow screens
  - Bug-fix: Added support for any tag in the Content Template cell text editor
  - Bug-fix: Improved and simplified Layout assignment in other languages (WPML integration)
  - Bug-fix: Added possibility to un-assign layout that was assigned to a deleted / missing post type
  - Bug-fix: Improved usability of iFrame based dialogs on small screens
  - Bug-fix: Added support for retro compatibility with PHP < 5.3
  - Bug-fix: Added more consistent "Change Layout use" dialog behaviour
  - Bug-fix: Fixed inconsistent behaviour for "Change layout use" dialog in post types section
  - Bug-fix: Fixed "Change layout use" dialog display bug when WPML is active

-------------------------------------------------------------------------------------------------------------------
Version 1.0
   - New: CRED Cell
   - New: Comments cell
   - New: New Menu cell (Responsive Menu Cell)
   - New: Translation of cells via WPML
   - New: Improvements to the Image Box cell
   - New: Improvements to the Menu cell
   - New: Removed the Post Content cell (users should use the Content Template cell instead)
   - New: Removed the Post Loop cell (users should use the WordPress Archive cell instead)
   - New: Removed the Widget Zone cell (users should use a grid of Widget cells instead)
   - New: Added container-fluid to rows when rendering
   - New: Overlay added to the post body when the associated layout doesn't include cells or shortcodes that display the post body
   - New: Improved workflow when assigning layouts to content
   - New: Import and export now includes data to associate layouts with content
   - New: Merged all the Views cells into one
   - New: New preview graphic design
   - New: New Create cell dialog design
   - New: New graphic design for cells' icons
   - New: Improved interactions in the create post page
   - New: Support for the embedded version of the CRED plugin
   - New: Improved the way Views cells are connected to their Views
   - New: Added Layouts cell information to the Views iframes
   - New: New where used listing box in the layout editing page
   - New: Improved cell descriptions and naming
   - New: Improved workflow for assigning parent layouts
   - New: Added quicktags to the editor in the Content Template cell
   - New: Improved integration with the Woocommerce Views plugin
   - New: New and improved caching system for PHP objects
   - New: Improved and optimised database queries
   - New: Updated the supported demo theme (BS3) to the latest Bootstrap version
   - New: Improved the preview system during editing
   - New: New and improved graphics for parametric search box and results
   - Fix: Content Template overlay works with multiple cells
   - Fix: Fixed links behaviour in third level of a responsive Menu cell
   - Fix: Fixed shortcode rendering for the Content Template cell and the Visual Editor cell
   - Fix: Fixed bugs in the post edit page templates / layouts combo-box
   - Fix: Improved integration with other Toolset libraries
   - Fix: Fixed bugs and improved layouts creation from the post edit page
   - Fix: Allow to create new layouts for post drafts
   - Fix: Fixed bugs related to the assignment import process for taxonomy and post types archives

-------------------------------------------------------------------------------------------------------------------
Version 0.9.2
   - New: Content Template cell (separate from Post Content cell)
   - New: Improved workflow for Views Conent Grid cell
   - New: Automatically create a Content Template to use with a View
   - New: Post Loop cell for archive pages
   - New: Views Post Loop cell for archive pages
   - New: Layout Assignment method
   - New: Layouts can be assigned to archives
   - New: Layouts can be assigned to 404 page
   - New: Better organized Cell Select dialog
   - New: Callback for Post Content cell for theme integration
   - New: Callback for Post Loop cell for theme integration
   - Fix: Problems with Unicode in Layout name
   - Fix: Problems with Unicode when duplicating Layouts
   - Fix: CRED forms not working with Layouts
   - Fix: Grid size selector of 6
   - Fix: Remove calls to Multibyte string library


-------------------------------------------------------------------------------------------------------------------
Version 0.9.1
   - New: New listing page
   - New: Show layout hierarchy in listing page
   - New: Post content cell allows creation and editing of Content Templates
   - New: New Views Content Grid cell with close Views integration
   - Fix: WP 3.9 support
   - Fix: support quotes in CSS file
   - Fix: use quotes in Layout names
   - Fix: loading css resources
   - Fix: hierarchical layouts on BS3 theme
   - Fix: codemirror conflicts with Views
   - Fix: bulk actions on listing page
   - Fix: don't allow parents to be assigned to post types or individual posts
   - Fix: handling of class names in CSS editor
   - Fix: many PHP warnings
   - Fix: importing of CSS from themes



-------------------------------------------------------------------------------------------------------------------
Version 0.9.0

First Beta release
