<?php

class CRED_Association_Form_Editor_Page extends CRED_Page_Manager_Abstract{

	const CREATE_EDIT_ASSOCIATION_NONCE = 'cred_associations_form_nonce';

	public function __construct( CRED_Association_Form_Model_Interface $model, CRED_Association_Form_Relationship_API_Helper $helper ) {
		parent::__construct( $model, $helper );
	}

	// Association forms page display

	public function print_page(){
		$this->prepare_dialogs();
		$this->add_meta_boxes();
		$this->render_page('@associations/', 'association_form_create_main' );
	}

	/**
	 * List of allowed actions on creation page
	 * @return array
	 */
	public function create_page_allowed_actions(){
		return array( 'edit', 'add_new' );
	}

	/**
	 * Check is action allowed, if it's not return default add_new
	 * @param $action
	 *
	 * @return string
	 */
	public function get_page_action( $action ){

		if( in_array( $action, $this->create_page_allowed_actions() ) ){
			return $action;
		} else {
			return 'add_new';
		}
	}

	// define possible editor page titles
	public function editor_page_titles(){
		return array(
			'currentPageTitle' => ( $this->get_page_action( toolset_getget('action') ) === 'add_new' ) ? __( 'Create New Relationship Form',  'wp-cred' ) : __( 'Edit Relationship Form', 'wp-cred' ),
			'pageTitle' => _x( 'Create New Relationship Form', 'add new relationship forms page title', 'wp-cred' ),
			'pageEditTitle' => _x( 'Edit Relationship Form', 'edit relationship forms page title', 'wp-cred' ),
		);
	}

	/**
	 * Prepare delete dialog
	 */
	public function prepare_dialogs() {

		if ( null === $this->_dialog_box_factory ) {
			$dialog = new Toolset_Twig_Dialog_Box_Factory();
		}

		$dialog->create(
			'cred-delete-association-form',
			$this->get_twig(),
			array(),
			'@associations/dialogs/delete_form.twig'
		);

	}

	/**
     * Prepare strings necessary for twig template
	 * @return array
	 */
	public function build_strings_for_twig() {

		$page_titles = $this->editor_page_titles();

		return array(
			'misc' => array(
				'currentPageTitle' => $page_titles['currentPageTitle'],
				'action'           => $this->get_page_action( toolset_getget( 'action' ) )
			),

		);
	}

	/**
	 * Prepare strings necessary for js
	 * @return array
	 */
	public function build_strings_for_js() {

		$page_titles = $this->editor_page_titles();

		return array(
			'currentPageTitle' => $page_titles['currentPageTitle'],
			'pageTitle'        => $page_titles['pageTitle'],
			'pageEditTitle'    => $page_titles['pageEditTitle'],
			'invalid_empty'    => __( 'This element cannot be empty, please specify a value then save again!', 'wp-cred')
		);
	}

	public function add_hooks() {
        add_action( 'admin_enqueue_scripts', array( $this, 'editor_page_scripts') );
	}

	public function editor_page_scripts(){
		wp_enqueue_media();
    }

	/**
	 * Register all metaboxes to be rendered on the Edit Relationship page.
	 * 
	 * Note that we render a dedicated metabox for the title just to be used
	 * in wizards, while the title plus main form actions belong to the
	 * top bar metabox when exiting, completing or ignoring the wizard.
	 *
	 * @since m2m
	 */
	public function add_meta_boxes() {
		add_screen_option( 'layout_columns', array( 'max' => 1, 'default' => 1 ) );

		add_meta_box(
			'association_form_name',
			__( 'Form','wp-cred' ),
			array( $this, 'render_metabox' ),
			null,
			'normal',
			'default',
			array(
				'title' => __( 'Form name', 'wp-cred' ),
				'template' => 'title'
			)
		);

		add_meta_box(
			'topbardiv',
			__( 'Top bar', 'wp-cred' ),
			array( $this, 'render_metabox' ),
			null,
			'normal',
			'default',
			array(
				'title' => __('Top bar', 'wp-cred'),
				'template' => 'topbar',
				'context' => $this->prepare_post_status(),
			)
		);

		add_meta_box(
			'association_form_settings',
			__( 'Settings', 'wp-cred' ),
			array( $this, 'render_metabox' ),
			null,
			'normal',
			'default',
			array(
				'title'    => __( 'Settings', 'wp-cred' ),
				'template' => 'settings',
				'context'  => array(
					'relationships_set' => $this->get_relatioship_set(),
					'redirect_to' => $this->prepare_redirect_options(),
				)
			)
		);

		add_meta_box(
			'association_form_content',
			__( 'Content', 'wp-cred' ),
			array( $this, 'render_metabox' ),
			null,
			'normal',
			'default',
			array(
				'title' => __( 'Content', 'wp-cred' ),
				'template' => 'content',
                'context' => array( 'id' => $this->model->get_id() )
			)
		);

		add_meta_box(
			'association_form_messages',
			__( 'Messages', 'wp-cred' ),
			array( $this, 'render_metabox' ),
			null,
			'normal',
			'default',
			array(
				'title' => __( 'Messages', 'wp-cred'),
				'template' => 'messages',
				'context' => array(
					'messages' => $this->prepare_messages(),
				)
			)
		);

		add_meta_box(
			'association_form_instructions',
			__( 'Instructions', 'wp-cred' ),
			array( $this, 'render_metabox' ),
			null,
			'normal',
			'default',
			array(
				'title' => __( 'Instructions', 'wp-cred'),
				'template' => 'instructions',
			)
		);

	}

	/**
	 * Render a single metabox from a dedicated Twig template.
	 *
	 * @param mixed $object Ignored.
	 * @param array $args Metabox arguments. One of the elements is 'args' passed
	 *     from the add_meta_box() call.
	 */
	public function render_metabox(
		/** @noinspection PhpUnusedParameterInspection */
		$object, $args
	) {
		$template_name = sprintf(
			'@associations_editor_metaboxes/%s.twig',
			toolset_getnest( $args, array( 'args', 'template' ) )
		);

		$context = toolset_ensarr(
			toolset_getnest( $args, array( 'args', 'context' ) )
		);

		$twig = $this->get_twig();
		echo $twig->render( $template_name, $context );

	}


	public function build_page_context() {

		// Basics for the listing page which we'll merge with specific data later on.
		$base_context = $this->gui_base->get_twig_context_base(
			Toolset_Gui_Base::TEMPLATE_BASE, $this->build_js_data()
		);

		$specific_context = array(
			'strings'                    => $this->build_strings_for_twig(),
			'has_relationship'           => $this->helper->has_relationships(),
			'relationships_set'          => $this->get_relatioship_set(),
			'messages'                   => $this->prepare_messages(),
			'redirect_to'                => $this->prepare_redirect_options(),
			'wpnonce'                    => self::CREATE_EDIT_ASSOCIATION_NONCE,
			'action'                     => self::ACTION_PREFIX . $this->get_page_action( toolset_getget( 'action' ) )
		);

		$context = toolset_array_merge_recursive_distinct( $base_context, $specific_context );

		return $context;
	}

	/**
     * Prepare data necessary for js file
	 * @return array
	 */
	function build_js_data() {
		$prepared_data = array(
			'toolsetFormsVersion' => CRED_FE_VERSION,
			'formModelData'       => $this->model->to_array(),
			'jsEditorIncludePath' => CRED_ABSURL . '/public/association_forms/js/editor_page',
			'jsIncludePath'       => CRED_ABSURL . '/public/association_forms/js',
			'has_relationships'   => $this->helper->has_relationships(),
			'relationships'       => $this->helper->get_relationships(),
			'selected_post'       => $this->prepare_selected_post(),
			'action'              => self::ACTION_PREFIX . $this->get_page_action( toolset_getget( 'action' ) ),
			'form_type'           => CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE,
			'wpnonce'             => self::CREATE_EDIT_ASSOCIATION_NONCE,
			'select2nonce'        => wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ),
			'strings'             => $this->build_strings_for_js()
		);

		return $prepared_data;
	}

	/**
     * Prepare data for relationship selector
	 * @return array
	 */
	private function get_relatioship_set(){

		$ret = array();

		$ret[] = (object) array(
			'value' => '',
			'title' => __('-- Select Relationship --', 'wp-cred'),
			'selected' => true,
			'disabled' => true
		);

		foreach( $this->helper->get_relationships() as $relationship ){
			$ret[] = (object) array(
				'value' => $relationship->get_slug(),
				'title' => $relationship->get_display_name_plural(),
				'selected' => ''
			);
		}

		return $ret;
	}

	/**
     * Prepare data for post status selector
	 * @return array
	 */
	private function prepare_post_status(){
		return array(
			'post_status' => array(
				'publish' => array( 'value' => 'publish', 'title' => __('Published') ),
				'draft'   => array( 'value' => 'draft', 'title' => __('Draft') ),
			)
		);
	}


	/**
	 * Prepare list of posts in case when we are on edit page and post type is selected
	 * @return array|null
	 */
	private function prepare_selected_post(){

		$post_data = null;

		// make sure that we are on edit page and we have all necessary details
		if(
			$this->model->action === 'edit' &&
			$this->model->redirect_to === 'custom_post' &&
			isset($this->model->redirect_custom_post) &&
			$this->model->redirect_custom_post != ''
		){

			$post = get_post( $this->model->redirect_custom_post );

			$post_data = array(
				'id' => $post->ID,
				'text' => $post->post_title,
				'post_type' => $post->post_type
			);

		}

		return $post_data;
	}

	/**
     * Prepare data for redirect options selector
	 * @return array
	 */
	private function prepare_redirect_options() {
		return array(
			'select'        => array(
				'value'    => '',
				'title'    => __( '-- Select Action --', 'wp-cred' ),
				'selected' => true,
				'disabled' => true
			),
			'custom_post'   => array(
				'value' => 'custom_post',
				'title' => __( 'Go to a specific page/post', 'wp-cred' )
			),
			'form'          => array(
				'value' => 'form',
				'title' => __( 'Stay on the same page', 'wp-cred' )
			),
			'redirect_back' => array(
				'value' => 'redirect_back',
				'title' => __( 'Redirect back to the page from which the user came from', 'wp-cred' )
			),
			/*
			 * ToDo: Implement in the next version
			'message'     => array(
				'value' => 'message',
				'title' => __( 'Display a message instead of the form...', 'wp-cred' )
			),
			*/
		);
	}


	private function prepare_messages() {
		return $this->model->get_default_messages();
	}
}