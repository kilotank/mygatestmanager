<?php

/**
 * Class CRED_Association_Form_Repository
 */
class CRED_Association_Form_Repository{
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * CRED_Association_Form_Database_Helper constructor.
	 *
	 * @param wpdb|null $wpdb_wp
	 */
	public function __construct(
		wpdb $wpdb_wp = null
	) {
		if( null === $wpdb_wp ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_wp;
		}
	}

	/**
	 * Fetch all posts with association_forms type, also get slug of the related relationship
	 * @return array
	 */

	public function get_association_forms_as_posts_and_their_relationship_slug(){

		$get_forms_query = $this->wpdb->prepare("
			SELECT ID, post_name, post_title, post_status,
			(SELECT meta_value FROM {$this->wpdb->postmeta} relationship 
				WHERE 
					posts.ID = relationship.post_id AND 
					relationship.meta_key = %s 
				LIMIT 1
			) AS relationship_slug
		    FROM {$this->wpdb->posts} posts
		    WHERE posts.post_type = %s
		    ORDER BY posts.post_date DESC
		", CRED_Association_Form_Main::RELATIONSHIP_POST_TYPE, CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE );

		$association_forms_data = $this->wpdb->get_results( $get_forms_query, ARRAY_A );

		return $association_forms_data;

	}
}