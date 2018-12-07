<?php

/**
 * When you have a relationship and a specific element in one role, this
 * query class will help you to find elements that can be associated with it.
 *
 * It takes into account all the aspects, like whether the relationship is distinct or not.
 *
 * @since m2m
 */
interface IToolset_Potential_Association_Query extends IToolset_Query {

	/**
	 * IToolset_Potential_Association_Query constructor.
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param IToolset_Relationship_Role_Parent_Child $target_role
	 * @param IToolset_Element $for_element
	 * @param array $args
	 * @param Toolset_Relationship_Query_Factory|null $query_factory_di
	 */
	public function __construct(
		IToolset_Relationship_Definition $relationship,
		IToolset_Relationship_Role_Parent_Child $target_role,
		IToolset_Element $for_element,
		$args,
		Toolset_Relationship_Query_Factory $query_factory_di = null
	);


	/**
	 * @return IToolset_Element[]
	 */
	public function get_results();


	/**
	 * @return int
	 */
	public function get_found_elements();


	/**
	 * Check whether a specific single element can be associated.
	 *
	 * The relationship, target role and the other element are those provided in the constructor.
	 *
	 * @param IToolset_Element $association_candidate Element that wants to be associated.
	 * @return Toolset_Result Result with an user-friendly message in case the association is denied.
	 * @since 2.5.6
	 */
	public function check_single_element( IToolset_Element $association_candidate );


	/**
	 * Check whether the element provided in the constructor can accept any new association whatsoever.
	 *
	 * @return Toolset_Result Result with an user-friendly message in case the association is denied.
	 * @since 2.5.6
	 */
	public function can_connect_another_element();

}