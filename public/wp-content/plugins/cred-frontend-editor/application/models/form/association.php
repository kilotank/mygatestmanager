<?php

/**
 * Class relationship association content data handler
 *
 * @since m2m
 */
class CRED_Form_Association {

	/**
	 * @var Toolset_Association_Query_V2|null
	 */
	private $_association_query;

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * CRED_Form_Association constructor.
	 *
	 * @param Toolset_Association_Query_V2|null $association_query
	 */
	public function __construct( Toolset_Association_Query_V2 $association_query = null ) {
		$this->_association_query = $association_query;
	}

	/**
	 * @return Toolset_Association_Query_V2
	 */
	public function get_association_query() {
		if ( ! $this->_association_query ) {
			return new Toolset_Association_Query_V2();
		}

		return $this->_association_query;
	}

	/**
	 * @param int $object_id
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param string $role
	 *
	 * @return IToolset_Association[]
	 */
	public function get_associations( $object_id, $relationship_definition, $role = Toolset_Relationship_Role::CHILD ) {
		$args = array(
			Toolset_Association_Query::QUERY_RELATIONSHIP_ID => $relationship_definition->get_row_id(),
			Toolset_Association_Query::OPTION_RETURN => Toolset_Association_Query::RETURN_ASSOCIATIONS
		);
		if ( Toolset_Relationship_Role::PARENT == $role ) {
			$args[ Toolset_Association_Query::QUERY_PARENT_ID ] = $object_id;
		} else {
			$args[ Toolset_Association_Query::QUERY_CHILD_ID ] = $object_id;
		}
		$query = new Toolset_Association_Query( $args );

		return $query->get_results();
	}

	/**
	 * @param IToolset_Association $association
	 * @param string $role
	 *
	 * @return int
	 */
	public function get_associated_object_id_by_role( $association, $role ) {
		return $association->get_element( $role )->get_id();
	}

	/**
	 * Get associations by a given item, relationship definition, and role.
	 *
	 * @param int $id
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param string $relationship_role
	 * @param int $limit
	 *
	 * @return array
	 *
	 * @note Disable the associations query cache because sometimes this needs to run while rendering a form after saving it,
	 *       and right now the saving mechanism prints, saves, and prints again, hence the cache holds the previous value.
	 */
	public function get_association_by_role( $id, $relationship_definition, $relationship_role, $limit = 1 ) {
		$query = $this->get_association_query();
		$role_id = $relationship_role == Toolset_Relationship_Role::CHILD ? $query->child_id( $id ) : $query->parent_id( $id );
		$relationship_condition = $this->get_association_query()->relationship_id( $relationship_definition->get_row_id() );
		$results = $query
			->use_cache( false )
			->add( $relationship_condition )
			->add( $role_id )
			->limit( $limit )
			->get_results();

		return $this->get_related_content_data( $results, Toolset_Relationship_Role::other( $relationship_role ) );
	}

	/**
	 * Get related posts data from array of associations
	 *
	 * @param IToolset_Association[] $associations Array of related content.
	 *
	 * @return array
	 */
	public function get_related_content_data( $associations, $role ) {
		$related_posts = array();

		foreach ( $associations as $association ) {

			// The related post.
			try {
				$post = $association->get_element( (string) $role );
				$fields = $association->get_fields();
				$uid = $association->get_uid();
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				// An element was supposed to be in the database but it's missing. We're going to
				// report a data integrity issue and skip it.
				do_action(
					'toolset_report_m2m_integrity_issue',
					new Toolset_Relationship_Database_Issue_Missing_Element(
						$e->get_domain(),
						$e->get_element_id()
					)
				);

				continue;
			}
			$related_posts[] = array(
				'uid' => $uid,
				'role' => $role,
				'post' => $post,
				'fields' => $fields,
				'has_intermediary_fields' => ( $fields && count( $fields ) > 0 ),
			);
		}

		return $related_posts;
	}

	/**
	 * Do disconnect association by association uid
	 *
	 * @param int $uid
	 *
	 * @return Toolset_Result
	 */
	private function delete_by_uid( $uid ) {
		$association = $this->get_association_by_association_uid( $uid );
		if ( ! $association ) {
			return new Toolset_Result( false );
		}

		return $this->delete( $association );
	}

	/**
	 * @param IToolset_Association $association
	 *
	 * @return Toolset_Result
	 */
	public function delete( $association ) {
		return $association->get_driver()->delete_association( $association );
	}
}