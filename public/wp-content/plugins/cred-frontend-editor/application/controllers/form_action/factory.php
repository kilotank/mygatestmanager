<?php

namespace OTGS\Toolset\CRED\Controller\FormAction;

/**
 * Factory to generate form action controllers.
 * 
 * @since 2.1.2
 */
class Factory {

	/**
	 * Build a post form controller for message actions.
	 * 
	 * @return \OTGS\Toolset\CRED\Controller\Forms\Post\Action\Message
	 * 
	 * @since 2.1.2
	 */
	public function get_for_post_message() {
		return new \OTGS\Toolset\CRED\Controller\Forms\Post\Action\Message();
	}

	/**
	 * Build an user form controller for message actions.
	 * 
	 * @return \OTGS\Toolset\CRED\Controller\Forms\User\Action\Message
	 * .2
	 * @since 2.1
	 */
	public function get_for_user_message() {
		return new \OTGS\Toolset\CRED\Controller\Forms\User\Action\Message();
	}

}