<?php

/**
 * Relationship Association Parents Query Handler
 *
 * @since 2.0
 */
class CRED_Form_Potential_Associable_Parent_Query {

	/**
	 * Retrieve only associable to $connect_to item, role parents/child elements
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition base relationship definition of form field
	 * @param IToolset_Relationship_Role $role_object role of items to search
	 * @param IToolset_Element $connect_to element to connect to
	 * @param array $args Additional query arguments:
	 *     - search_string: string
	 *     - count_results: bool
	 *     - items_per_page: int
	 *     - page: int
	 *     - wp_query_override: array
	 *
	 * @return IToolset_Element[]|null
	 */
	public function get_potential_associable_parent_result( $relationship_definition, $role_object, $connect_to, $args = array() ) {
		$association_query_factory = new Toolset_Potential_Association_Query_Factory();
		try {
			/** @var IToolset_Potential_Association_Query $query */
			$query = $association_query_factory->create(
				$relationship_definition,
				$role_object, // role
				$connect_to, // the known end $id
				$args // use this to search results by title or whatnot - depends of their domain
			);

			// Using parameter false in order to overwrite the element if exists
			// because in Toolset Form behavior we do not delete before replacing relationship element

			/*
			 TODO: use 'false' only when we are in relationship form 1 => many case
			 */
			return $query->get_results(false);
		} catch ( InvalidArgumentException $e ) {
			error_log( $e->getMessage() );

			return null;
		}
	}
}