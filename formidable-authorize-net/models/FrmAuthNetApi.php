<?php

/**
 * @since 2.0
 */
class FrmAuthNetApi {

	private $endpont = 'xml/v1/request.api';

	private $request = array();

	/**
	 * @since 2.0
	 */
	public function __construct( $args = array() ) {
		if ( isset( $args['endpoint'] ) ) {
			$this->endpoint = $args['endpoint'];
		}

		if ( isset( $args['request'] ) ) {
			$this->request = $args['request'];
		}
	}

	/**
	 * Send a Basic Auth request.
	 *
	 * @since 2.0
	 *
	 * @return object|bool
	 */
	public function remote_request() {
		$this->prepare_request();

		return $this->send_request();
	}

	/**
	 * Send request using the api key and transaction id.
	 *
	 * @since 2.0
	 */
	public function signed_request( $request_type ) {
		$this->prepare_signed_request( $request_type );
		return $this->send_request();
	}

	/**
	 * Trigger the API send request.
	 *
	 * @since 2.0
	 */
	private function send_request() {
		$settings   = new FrmAuthNetSettings();
		$is_sandbox = ( 'sandbox' == $settings->settings->environment );
		$prefix     = $is_sandbox ? 'apitest' : 'api';
		$url        = 'https://' . $prefix . '.authorize.net/' . $this->endpoint;

		$response = wp_remote_request( $url, $this->request );
		FrmTransLog::log_message( 'API response: ' . print_r( $response, 1 ) );
		$response = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) ) {
			$response = false;
		}

		if ( $response ) {
			// Remove BOM character in API response.
			$response = preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $response );

			$response = json_decode( $response );
		}

		return $response;
	}

	/**
	 * @since 2.0
	 */
	private function prepare_request() {
		$this->add_basic_auth_header();

		$request = array(
			'method'  => $this->request['method'],
			'timeout' => 45,
			'headers' => array(
				'Authorization' => $this->request['Authorization'],
				'Content-Type'  => 'application/json',
			),
		);

		if ( isset( $this->request['body'] ) ) {
			$request['body'] = json_encode( $this->request['body'] );
		}

		$this->request = $request;
	}

	/**
	 * @since 2.0
	 *
	 * @param string $request_type
	 */
	private function prepare_signed_request( $request_type ) {
		$request = array(
			'method'  => 'POST',
			'timeout' => 45,
			'headers' => array(
				'content-type' => 'application/json',
			),
		);

		$this->merchent_authentication();

		$request['body'] = array(
			$request_type => $this->request,
		);

		$request['body'] = json_encode( $request['body'] );

		$this->request = $request;
	}

	/**
	 * Add api key to the request.
	 *
	 * @since 2.0
	 */
	protected function merchent_authentication() {
		$auth = array(
			'merchantAuthentication' => $this->setup_api(),
			'refId'                  => 'ref' . time(),
		);
		$this->request = $auth + $this->request;
	}

	/**
	 * Setup Authorize API info
	 *
	 * @return array
	 */
	public function setup_api() {
		
		$settings = new FrmAuthNetSettings();

		// Rotation of API keys.
		$authnet = new Dotfiler_authnet;
		$authnet->init_authnet_credentials();

		// Get the API login ID from Global Settings.
		$api_key = ( defined( 'AUTHORIZENET_API_LOGIN_ID' ) ? AUTHORIZENET_API_LOGIN_ID : $settings->settings->login_id );

		// Get the API login ID from Global Settings.
		$transaction_key = ( defined( 'AUTHORIZENET_TRANSACTION_KEY' ) ? AUTHORIZENET_TRANSACTION_KEY : $settings->settings->transaction_key );

		return array(
			'name'           => $api_key,
			'transactionKey' => $transaction_key,
		);
	}

	/**
	 * @since 2.0
	 */
	private function add_basic_auth_header() {
		$auth = $this->setup_api();
		$this->request['Authorization'] = 'Basic ' . base64_encode( $auth['name'] . ':' . $auth['transactionKey'] );
	}
}
