<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Class CRED_Generic_Response
 */
class CRED_Generic_Response {

	public $form_id;
	public $form_html_id;
	public $form_type;
	public $post_id;
	public $is_ajax;
	public $result;
	public $output;
	public $form_helper;
	public $delay = 0;
	const CRED_GENERIC_RESPONSE_RESULT_REDIRECT = 'redirect';
	const CRED_GENERIC_RESPONSE_RESULT_OK = 'ok';
	const CRED_GENERIC_RESPONSE_RESULT_KO = 'ko';
	const CRED_GENERIC_RESPONSE_TYPE_JSON = 1;

	/**
	 * CRED_Generic_Response constructor.
	 *
	 * @param $result
	 * @param $output
	 * @param $is_ajax
	 * @param $form_data
	 * @param null $form_helper
	 * @param int $delay
	 */
	public function __construct( $result, $output, $is_ajax, $form_data, $form_helper = null, $delay = 0 ) {
		$this->form_id = isset( $form_data['id'] ) ? $form_data['id'] : 0;
		$this->post_id = isset( $form_data['post_id'] ) ? $form_data['post_id'] : 0;
		$this->form_type = isset( $form_data['form_type'] ) ? $form_data['form_type'] : '';
		$this->form_html_id = isset( $form_data['form_html_id'] ) ? $form_data['form_html_id'] : '';
		$this->result = $result;
		$this->output = $output;
		$this->is_ajax = $is_ajax;
		$this->form_helper = $form_helper;
		$this->delay = $delay;
	}

	/**
	 * @param $delay
	 */
	public function set_delay( $delay ) {
		$this->delay = $delay;
	}

	/**
	 * @return bool
	 */
	public function show() {
		switch ( $this->result ) {
			case self::CRED_GENERIC_RESPONSE_RESULT_OK:
			case self::CRED_GENERIC_RESPONSE_RESULT_KO:
				if ( $this->is_ajax == self::CRED_GENERIC_RESPONSE_TYPE_JSON ) {
					$current_select2_fields_list = CRED_Frontend_Select2_Manager::get_instance()->get_select2_fields_list();
					ob_start();
					?>
                    <script>
                        //Update current select2_list_fields if exists
                        if (typeof cred_select2_frontend_settings !== 'undefined') {
                            cred_select2_frontend_settings.select2_fields_list = <?php echo json_encode( $current_select2_fields_list ) ?>;
                        }
						<?php if ( $this->result == CRED_Generic_Response::CRED_GENERIC_RESPONSE_RESULT_OK ) { ?>
                        if (typeof jQuery('.wpt-form-error') !== 'undefined')
                            jQuery('.wpt-form-error').hide();
						<?php } ?>
                        jQuery(document).ready(function () {
                            _.defer(function () {
                                if (typeof wptRep !== 'undefined') {
                                    //console.log("wptRep.init();");
                                    wptRep.init();
                                }

                                if (typeof wptCredfile !== 'undefined') {
                                    //console.log("wptCredfile.init();");
                                    wptCredfile.init('body');
                                }

                                if (typeof toolsetForms !== 'undefined') {
                                    //console.log("toolsetForms.cred_tax = new toolsetForms.CRED_taxonomy();");
                                    toolsetForms.cred_tax = new toolsetForms.CRED_taxonomy();
                                    if (typeof initCurrentTaxonomy == 'function') {
                                        initCurrentTaxonomy();
                                    }
                                }

                                if (typeof jQuery('.wpt-suggest-taxonomy-term') && jQuery('.wpt-suggest-taxonomy-term').length)
                                    jQuery('.wpt-suggest-taxonomy-term').hide();

                                jQuery(document).trigger('js_event_cred_ajax_form_response_completed');
                            });
                        });
                    </script>

					<?php
					$script = ob_get_clean();

					$data = array(
						'result' => $this->result,
						'is_ajax' => $this->is_ajax,
						'output' => $this->output . "\n" . $script,
						'formtype' => $this->form_type,
					);
					if ( defined( 'CRED_DEBUG' ) && CRED_DEBUG ) {
						$data['debug'] = array();
						$data['debug']['post'] = $_POST;
						$data['debug']['files'] = $_FILES;
					}
					echo wp_json_encode( $data );
					die;
				} else {
					return $this->output;
				}

				break;
			case self::CRED_GENERIC_RESPONSE_RESULT_REDIRECT:
				if ( $this->is_ajax == self::CRED_GENERIC_RESPONSE_TYPE_JSON ) {
					$data = array(
						'result' => $this->result,
						'is_ajax' => $this->is_ajax,
						'output' => "<p>" . __( 'Please Wait. You are being redirected...', 'wp-cred' ) . "</p>" . $this->do_redirect( $this->output, $this->delay, true ),
						//($this->delay > 0) ? $this->form_helper->redirectDelayedFromAjax($this->output, $this->delay) : $this->form_helper->redirectFromAjax($this->output),
						'formtype' => $this->form_type,
					);
					if ( defined( 'CRED_DEBUG' ) && CRED_DEBUG ) {
						$data['debug'] = array();
						$data['debug']['post'] = $_POST;
						$data['debug']['files'] = $_FILES;
					}
					echo wp_json_encode( $data );
					die;
				} else {
					if ( $this->delay > 0 ) {
						remove_shortcode( 'cred-form' );
						remove_shortcode( 'cred_form' );
						remove_shortcode( 'cred-user-form' );
						remove_shortcode( 'cred_user_form' );

						add_shortcode( 'cred-form', array( $this, 'print_delayed_message' ) );
						add_shortcode( 'cred_form', array( $this, 'print_delayed_message' ) );
						add_shortcode( 'cred-user-form', array( $this, 'print_delayed_message' ) );
						add_shortcode( 'cred_user_form', array( $this, 'print_delayed_message' ) );
					} else {
						$this->print_delayed_message();
					}

					return true;
				}
				break;
		}
	}

	/**
	 * @return string
	 */
	public function print_delayed_message() {
		return "<p>" . __( 'Please Wait. You are being redirected...', 'wp-cred' ) . "</p>" . $this->do_redirect( $this->output, $this->delay, false );
	}

	/**
	 * @param $url
	 * @param $delay
	 * @param bool $with_ajax
	 *
	 * @return string
	 */
	private function do_redirect( $url, $delay, $with_ajax = false ) {
		if ( $with_ajax ) {
			return ( $delay > 0 ) ? $this->redirectDelayedFromAjax( $url, $delay ) : $this->redirectFromAjax( $url );
		} else {
			return ( $delay > 0 ) ? $this->redirectDelayed( $url, $delay ) : $this->redirect( $url, array( "HTTP/1.1 303 See Other" ) );
		}
	}

	/**
	 * @param $uri
	 * @param array $headers
	 */
	private function redirect( $uri, $headers = array() ) {
		if ( ! headers_sent() ) {
			// additional headers
			if ( ! empty( $headers ) ) {
				foreach ( $headers as $header ) {
					header( "$header" );
				}
			}
			// redirect
			header( "Location: $uri" );
			exit();
		} else {
			echo sprintf( "<script type='text/javascript'>document.location='%s';</script>", esc_url_raw( $uri ) );
			exit();
		}
	}

	/**
	 * @param $uri
	 * @param $delay
	 *
	 * @return string|void
	 */
	private function redirectDelayed( $uri, $delay ) {
		$delay = intval( $delay );
		if ( $delay <= 0 ) {
			$this->redirect( $uri );

			return;
		}
		if ( ! headers_sent() ) {
			$this->_uri_ = $uri;
			$this->_delay_ = $delay;
			add_action( 'wp_footer', array( $this, 'doDelayedRedirect' ), 1000 );
		} else {
			return sprintf( "<script type='text/javascript'>setTimeout(function(){document.location='%s';}, %d);</script>", esc_url_raw( $uri ), $delay * 1000 );
		}
	}

	/**
	 * @param $uri
	 *
	 * @return string
	 */
	private function redirectFromAjax( $uri ) {
		return sprintf( "<script type='text/javascript'>document.location='%s';</script>", esc_url_raw( $uri ) );
	}

	/**
	 * @param $uri
	 * @param $delay
	 *
	 * @return string
	 */
	private function redirectDelayedFromAjax( $uri, $delay ) {
		$delay = intval( $delay );
		if ( $delay <= 0 ) {
			return $this->redirectFromAjax( $uri );
		}

		return sprintf( "<script type='text/javascript'>setTimeout(function(){document.location='%s';}, %d);</script>", esc_url_raw( $uri ), $delay * 1000 );
	}

	/**
	 * redirection way when header is not sent
	 */
	public function doDelayedRedirect() {
		printf( "<script type='text/javascript'>setTimeout(function(){document.location='%s';}, %d);</script>", esc_url_raw( $this->_uri_ ), $this->_delay_ * 1000 );
	}

}
