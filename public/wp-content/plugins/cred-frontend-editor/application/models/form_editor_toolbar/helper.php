<?php

namespace OTGS\Toolset\CRED\Model\FormEditorToolbar;

/**
 * Helper for the post forms content editor toolbar.
 * 
 * @since 2.1
 */
abstract class Helper {

    /**
     * Items to populate and return back:
     * basic fields, plus fields belonging to each of the domains.
     *
     * @var array
     * 
     * @since 2.1
     */
    protected $items = array();

    /**
     * List of field types that do not support being prefilled with a value or urlparam attribute.
     *
     * @var array
     * 
     * @since 2.1
     */
    protected $field_types_without_value_and_url_options = array(
        'post', 'audio', 'video', 'file', 'image', 'checkboxes', 'checkbox', 'skype'
    );

    /**
     * Populate and return the list of items.
     *
     * @return array
     * 
     * @since 2.1
     */
    abstract public function populate_items();

    /**
     * Populate the list of basic fields.
     *
     * @since 2.1
     */
    abstract protected function populate_basic_fields();

    /**
     * Populate the options for a field for prefilling with a value or urlparam attribute.
     *
     * @param array $options
     * 
     * @return array
     * 
     * @since 2.1
     */
    protected function get_value_and_url_options( $options = array() ) {
        $options['valueAndUrl'] = array(
            'type'   => 'group',
            'fields' => array(
                'value' => array(
                    'label' => __( 'Field default value', 'wp-cred' ),
                    'type'  => 'text',
                    'defaultValue' => '',
                    'description' => __( 'Set a default value for this field', 'wp-cred' )
                ),
                'urlparam' => array(
                    'label' => __( 'Set default value from an URL parameter', 'wp-cred' ),
                    'type'  => 'text',
                    'defaultValue' => '',
                    'description' => __( 'Listen to this URL parameter to set the default value', 'wp-cred' )
                )
            )
        );
        return $options; 
    }
    
    /**
     * Populate the options for a field for sorting and filtering its options.
     * 
     * Used on post selector fields (post reference, or hierarchical, legacy or m2m parents)
     * to sort the available options and filter them by author.
     * 
     * For backwards compatibility, post and user forms use the "order" attribute for the orderby query argument,
     * and the "ordering" attribute for the order query argument.
     *
     * @param array $options
     * @param string $orderby_slug
     * @param string $order_slug
     * 
     * @return array
     * 
     * @since 2.1
     */
    protected function get_sorting_and_author_options( $options = array(), $orderby_slug = 'orderby', $order_slug = 'order' ) {
        $options['sortingGroup'] = array(
            'type'   => 'group',
            'fields' => array(
                $orderby_slug => array(
                    'label' => __( 'Order by', 'wp-cred' ),
                    'type' => 'radio',
                    'options'      => array(
                        'title' => __( 'Order options by title', 'wp-cred' ),
                        'ID' => __( 'Order options by ID', 'wp-cred' ),
                        'date' => __( 'Order options by date', 'wp-cred' )
                    ),
                    'defaultValue' => 'title'
                ),
                $order_slug => array(
                    'label' => __( 'Order', 'wp-cred' ),
                    'type' => 'radio',
                    'options'      => array(
                        'asc' => __( 'Ascending', 'wp-cred' ),
                        'desc' => __( 'Descending', 'wp-cred' )
                    ),
                    'defaultValue' => 'asc'
                )
            )
        );
        $options['author'] = array(
            'label' => __( 'Filtering by author', 'wp-cred' ),
            'type' => 'radio',
            'options'      => array(
                '$current' => __( 'Get only options by the current author', 'wp-cred' ),
                '' => __( 'Get options by any author', 'wp-cred' )
            ),
            'defaultValue' => ''
        );
        return $options;
    }

}