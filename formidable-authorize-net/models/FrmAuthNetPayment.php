<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetPayment' ) ) {
	return;
}

/**
 * Payment Class (Model)
 *
 * Setup payments from form
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetPayment {
	public $type;
	public $entry;
	public $form;
	public $action;
	public $invoice_id;
	public $invoice;
	public $amount;
	public $response;

	protected $request_type = 'createTransactionRequest';

	/**
	 * Setup attributes used across payments
	 *
	 * @param $atts
	 */
	public function __construct( $atts = array() ) {
		$this->entry  = $atts['entry'];
		$this->form   = $atts['form'];
		$this->action = $atts['action'];

		if ( isset( $atts['invoice_id'] ) ) {
			$this->invoice_id = $atts['invoice_id'];
		} else {
			$this->invoice_id = $this->create_invoice();
		}
		$this->invoice = FrmAuthNetHelper::get_invoice_pattern( $this->invoice_id );

		$this->set_amount();
	}

	/**
	 * Set the amount
	 *
	 * @since 1.0
	 */
	private function set_amount() {
		$content = $this->action->post_content;

		$amount = isset( $content['amount'] ) ? $content['amount'] : 0;

		// Check the following conditions to see which amount field will be used
		if ( ! empty( $amount ) && strpos( $amount, '[' ) !== false ) {
			$amount = apply_filters( 'frm_content', $amount, $this->form, $this->entry );
		}

		$amount = FrmAuthNetHelper::convert_price_string_to_value( $amount, $this->form );

		$this->amount = $amount;
	}

	/**
	 * Process the payment
	 */
	public function process_payment() {
		$this->get_response();
		$is_complete = $this->is_payment_successful();
		$this->update_invoice( $is_complete ? 'complete' : 'failed' );
		return $is_complete ? $this->invoice_id : 0;
	}

	/**
	 * Override this function in child class.
	 * Must prepare the request and trigger the send.
	 */
	public function get_response() {
		$transaction = $this->setup_transaction_type( 'authCaptureTransaction' );
		$request     = $this->transaction_request( $transaction );
		$this->send_api_request( $request );
	}

	/**
	 * Setup the transaction request
	 *
	 * @param $transaction
	 * @return $request
	 */
	public function transaction_request( $transaction ) {
		$request = array(
			'transactionRequest' => $transaction,
		);

		return $request;
	}

	/**
	 * Setup the transaction type and setup the amount
	 *
	 * @param $type
	 * @return $transaction
	 */
	public function setup_transaction_type( $type ) {
		return array(
			'transactionType' => $type,
			'amount'          => $this->amount,
		);
	}

	public function translate_response() {
		if ( $this->is_payment_successful() ) {
			$status = true;
		} else {
			$status = $this->get_error_message();
		}
		return $status;
	}

	/**
	 * Payment response
	 *
	 * @return boolean
	 */
	public function is_payment_successful() {
		$trans_response = $this->get_transaction_response();
		return $trans_response && $trans_response->responseCode == '1';
	}

	public function get_transaction_response() {
		return empty( $this->response ) || ! property_exists( $this->response, 'transactionResponse' ) ? false : $this->response->transactionResponse;
	}

	public function get_error_message() {
		$status = '';

		// If this object isn't empty there is a new Error messages
		// $this->messages
		
		if ( empty( $this->response ) && !empty($this->messages) ) {
			$status = __( 'There was a problem with your payment.', 'frmauthnet' );
		} else {
			$trans_response = $this->get_transaction_response();
			if ( ! empty( $trans_response ) && is_array( $trans_response->errors ) ) {
				$errors = $trans_response->errors;
			} else {
				$errors = $this->response->messages;
			}

			// New custom messages, it wasn't included in the initial version
			if( $this->response->messages->resultCode == 'Error' ) {
				$errors = $this->response->messages->message;
			}

			foreach ( $errors as $error ) {
				if ( ! is_object( $error ) ) {
					continue;
				}
				$code    = isset( $error->code ) ? $error->code : $error->errorCode;
				$message = isset( $error->text ) ? $error->text : $error->errorText;
				$status .= str_replace( "\r\n", '<br/>', $code . ' ' . $message ) . '<br/>';
			}

			// 

		}

		// Replace the error text to the custom message
		$message = Dotfiler_authnet_errors::get_error_by_code( $code );
		if( $message ) {
			$status = $message;
		}

		// Insert failed payment
		$values = array(
			'entry_id' => $this->entry->id,
			'form_id' => $this->entry->form_id,
			'payment_id' => $this->invoice_id,
			'error_code' => $code,
			'error_message' => $message,
		);
		Dotfiler_authnet::insert_payment_failed( $values );

		return $status;
	}

	/**
	 * Send request to the Authorize API
	 *
	 * @param array $request
	 * @return void
	 */
	public function send_api_request( $request ) {
		try {
			$this->remote_request( $request );
		} catch ( Exception $ex ) {
			$this->response = false;
		}
	}

	/**
	 * @since 2.0
	 *
	 * @param array $request
	 */
	protected function remote_request( $request ) {
		$api = new FrmAuthNetApi(
			array(
				'endpoint' => 'xml/v1/request.api',
				'request'  => $request,
			)
		);
		$this->response = $api->signed_request( $this->request_type );
	}

	/**
	 * Setup Authorize API info
	 *
	 * @return array
	 */
	public function setup_api() {
		$api = new FrmAuthNetApi();

		return $api->setup_api();
	}

	/**
	 * Process Refund
	 *
	 * @param $atts
	 */
	public function process_refund( $atts ) {

		$this->amount = $atts['amount'];

		$transaction               = $this->setup_transaction_type( 'refundTransaction' );
		$transaction['payment']    = $this->payment_for_refund( $atts );
		$transaction['refTransId'] = $atts['trans_id'];

		$request = $this->transaction_request( $transaction );
		$this->send_api_request( $request );

		return $this->get_status_from_response( 'refund' );
	}

	/**
	 * Process Void
	 *
	 * @param $atts
	 */
	public function process_void( $atts ) {
		$this->amount = 0;

		$transaction               = $this->setup_transaction_type( 'voidTransaction' );
		$transaction['refTransId'] = $atts['trans_id'];

		$request = $this->transaction_request( $transaction );
		$this->send_api_request( $request );

		return $this->get_status_from_response( 'void' );
	}

	/**
	 * Get the status response from the form
	 *
	 * @param string                  $status
	 * @return string                  $status
	 */
	public function get_status_from_response( $status ) {
		if ( ! empty( $this->response ) ) {
			$tresponse = $this->translate_response();
			if ( $tresponse === true ) {
				$this->update_invoice( $status );
			}
			$status = $tresponse;
		} else {
			$status = __( 'Call to Authorize.Net Failed!', 'frmauthnet' );
		}

		return $status;
	}

	private function create_invoice() {
		$frm_authnet = new FrmTransPayment();
		$id = $frm_authnet->create(
			array(
				'action_id' => $this->action->ID,
				'item_id'   => $this->entry->id,
				'status'    => 'pending',
				'paysys'    => $this->type,
			)
		);

		$this->invoice_id = $id;
		return $id;
	}

	protected function update_invoice( $status ) {
		$frm_authnet = new FrmTransPayment();

		$values = array(
			'status' => $status,
		);

		if ( $status == 'refund' || $status == 'void' ) {
			$payment = $frm_authnet->get_one( $this->invoice );

			/* translators: %1$s: Payment status, %2$s: Transaction ID */
			$note = sprintf( __( 'Payment %1$s: %2$s', 'frmauthnet' ), $status, $this->get_trans_id() );

			if ( $payment ) {
				$values['meta_value'] = FrmTransAppHelper::add_meta_to_payment( $payment->meta_value, $note );
			}
		} else {
			$values['invoice_id'] = $this->invoice;
			$values['amount']     = $this->get_invoice_amount();
			$values['receipt_id'] = $this->get_trans_id();
		}

		$frm_authnet->update( $this->invoice_id, $values );

		// DB
		if( $values['status'] == "complete" ) {

			// Prepare credentials
			if( defined( 'AUTHORIZENET_API_LOGIN_ID' ) ) {
				$login_id = AUTHORIZENET_API_LOGIN_ID;
				$transaction_id = AUTHORIZENET_TRANSACTION_KEY;
			} else {
				$settings = new FrmAuthNetSettings();
				$login_id = $settings->settings->login_id;
				$transaction_id = $settings->settings->transaction_key;
			}

			// Insert into DB
			$fields = array(
				'form_id' => $_POST['form_id'], 
				'payment_id' => '', 
				'invoice_id' => $values['invoice_id'],
				'authnet_login_id' => $login_id,
				'authnet_transaction_key' => $transaction_id,
				'created_at' => date('Y-m-d H:i:s'),
				'amount' => $this->get_invoice_amount()
			);

			global $wpdb;
			$done = $wpdb->insert( 'wp_frm_payments_authnet', $fields);

			/*
			echo "<pre>";
			print_r($values);
			echo "</pre>";
			die();
			*/

			/*
			$data = [
				"form_id" => $_POST['form_id'],
				"invoice_id" => $values['invoice_id'],
				"amount" => $this->get_invoice_amount(),
				"authnet_login_id" => AUTHORIZENET_API_LOGIN_ID,
				"authnet_transaction_key" => AUTHORIZENET_TRANSACTION_KEY
			];
			Dotfiler_authnet::insert_transaction( $data );
			*/
			
		}

		//$this->trigger_actions( $status );
	}

	public function get_invoice_amount() {
		return $this->amount;
	}

	public function get_trans_id() {
		$trans_response = $this->get_transaction_response();
		return $trans_response ? $trans_response->transId : 0;
	}

	/**
	 * Map the custom fields from the credit card field
	 *
	 * @return array
	 */
	private function get_card_mapping() {
		return array(
			'credit_card' => array(
				'field_id' => $this->action->post_content['credit_card'],
				'fields'   => array(
					'cardNumber'     => 'cc',
					'expirationDate' => array(
						'field' => array( 'year', 'month' ),
						'join'  => '-',
					),
					'cardCode'       => 'cvc',
				),
			),
		);
	}

	/**
	 * Fill the card info from form
	 *
	 * @param $transaction
	 */
	public function fill_card_info( &$transaction ) {
		$mapping = $this->get_card_mapping();
		$credit_card = array();
		$this->fill_info_from_map( $mapping, $credit_card );

		if ( isset( $credit_card['expirationDate'] ) && ! empty( $credit_card['expirationDate'] ) ) {
			$this->set_date_format( $credit_card['expirationDate'] );
		}

		$transaction['payment'] = array(
			'creditCard' => $credit_card,
		);
	}

	/**
	 * Set format to YYYY-MM.
	 */
	private function set_date_format( &$date ) {
		$split = explode( '-', $date );
		$month = sprintf( '%02d', $split[1] );
		$date  = $split[0] . '-' . $month;
	}

	/**
	 * Fill the customer info from form
	 *
	 * @param $transaction
	 */
	public function fill_customer_info( &$transaction ) {
		$customer = array();
		$mapping  = array(
			'email' => $this->action->post_content['email'],
		);
		$this->fill_info_from_map( $mapping, $customer );
		$transaction['customer'] = $customer;
	}

	/**
	 * Fill the order info from form
	 *
	 * @param $transaction
	 */
	public function fill_order_info( &$transaction ) {
		$transaction['order'] = array(
			'invoiceNumber' => $this->invoice,
			'description'   => $this->action->post_content['description'],
		);
	}

	/**
	 * Fill the address info from form
	 *
	 * @param $transaction
	 */
	public function fill_address_info( &$transaction ) {
		$billto = array();
		$this->fill_billto_info( $billto );

		$transaction['billTo'] = $billto;

		if ( $this->shipping_same_as_billing() ) {
			$shipto = array();
			$this->fill_shipto_info( $shipto );

			$transaction['shipTo'] = $shipto;
		}
	}

	/**
	 * Map the billing info from address field
	 *
	 * @param $billto
	 */
	public function fill_billto_info( &$billto ) {
		$settings = $this->action->post_content;
		$mapping = array(
			'firstName'   => $settings['billing_first_name'],
			'lastName'    => $settings['billing_last_name'],
			'company'     => $settings['billing_company'],
		);

		//$mapping = array_merge( $this->get_address_mapping(),$mapping );
		$this->add_address_mapping( 'billing', $mapping );
		$this->fill_info_from_map( $mapping, $billto );
	}

	/**
	 * Map the shipping info from address field
	 *
	 * @param $shipto
	 */
	public function fill_shipto_info( &$shipto ) {
		if ( $this->shipping_same_as_billing() ) {
			return;
		}
		$settings = $this->action->post_content;
		$mapping = array(
			'firstName' => $settings['shipping_first_name'],
			'lastName'  => $settings['shipping_last_name'],
			'company'    => $settings['shipping_company'],
		);

		//$mapping = array_merge( $this->get_address_mapping(), $mapping );
		$this->add_address_mapping( 'shipping', $mapping );
		$this->fill_info_from_map( $mapping, $shipto );
	}

	/**
	 * Build the address from mapping
	 *
	 * @param array $mapping
	 * @param array $info
	 */
	public function fill_info_from_map( $mapping, &$info ) {
		foreach ( $mapping as $name => $field ) {
			if ( is_array( $field ) ) {

				foreach ( $field['fields'] as $subname => $subfield ) {
					$value = $this->get_value_from_field(
						array(
							'field_id'    => $field['field_id'],
							'value'       => $subfield,
							'mapping_key' => $subname,
						)
					);
					if ( $value !== '' ) {
						$info[ $subname ] = $value;
					}
				}
			} else {
				$value = $this->get_value_from_field(
					array(
						'field_id'    => $field,
						'mapping_key' => $name,
					)
				);
				if ( $value !== '' ) {
					$info[ $name ] = $value;
				}
			}
		}
	}

	/**
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing
	 * We don't use nonce for front-end forms.
	 */
	public function get_value_from_field( $atts ) {
		$field_value = '';
		if ( empty( $atts['field_id'] ) ) {
			return $field_value;
		}

		if ( isset( $atts['value'] ) ) {
			if ( is_array( $atts['value'] ) ) {
				$field_value = array();

				foreach ( $atts['value']['field'] as $field ) {
					if ( isset( $_POST['item_meta'][ $atts['field_id'] ][ $field ] ) ) {
						$field_value[] = sanitize_text_field( wp_unslash( $_POST['item_meta'][ $atts['field_id'] ][ $field ] ) );
					} elseif ( isset( $atts['value']['default'] ) ) {
						$field_value[] = $atts['value']['default'];
					}
				}
				$field_value = implode( $atts['value']['join'], $field_value );
			} else {
				if ( isset( $_POST['item_meta'][ $atts['field_id'] ][ $atts['value'] ] ) ) {
					$field_value = sanitize_text_field( wp_unslash( $_POST['item_meta'][ $atts['field_id'] ][ $atts['value'] ] ) );
				} elseif ( isset( $atts['value']['default'] ) ) {
					$field_value = $atts['value']['default'];
				}

				if ( $atts['value'] == 'cc' ) {
					$field_value = str_replace( array( '-', ' ' ), '', $field_value );
				}
			}
		} elseif ( is_numeric( $atts['field_id'] ) && isset( $_POST['item_meta'][ $atts['field_id'] ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$meta = wp_unslash( $_POST['item_meta'][ $atts['field_id'] ] );

			if (
				isset( $atts['mapping_key'] ) && 'firstName' === $atts['mapping_key'] &&
				is_array( $meta ) && isset( $meta['first'] )
			) {
				return sanitize_text_field( $meta['first'] );
			}

			if (
				isset( $atts['mapping_key'] ) && 'lastName' === $atts['mapping_key'] &&
				is_array( $meta ) && isset( $meta['last'] )
			) {
				return sanitize_text_field( $meta['last'] );
			}

			$field_value = sanitize_text_field( $meta );
		} else {
			$field_value = apply_filters( 'frm_content', $atts['field_id'], $this->form, $this->entry );
		}

		return $field_value;
	}

	public function get_address_mapping() {

		return array(
			'address' => array(
				'field' => array( 'line1', 'line2' ),
				'join'  => ' ',
			),
			'city'    => 'city',
			'state'   => 'state',
			'zip'     => 'zip',
			'country' => array(
				'field'   => array( 'country' ),
				'default' => 'US',
				'join'    => '',
			),
		);
	}

	/**
	 * Adds address mapping.
	 *
	 * @since 2.02 Parameter `$type` is required.
	 *
	 * @param string $type    Address mapping type.
	 * @param array  $mapping The mapping array.
	 */
	public function add_address_mapping( $type, &$mapping ) {
		if ( $type == 'shipping' && $this->shipping_same_as_billing() ) {
			return;
		}

		$mapping[ $type . '_address' ] = array(
			'field_id' => $this->action->post_content[ $type . '_address' ],
			'fields'   => $this->get_address_mapping(),
		);
	}

	public function shipping_same_as_billing() {
		return ! $this->action->post_content['use_shipping'];
	}

	private function add_response_to_global() {
		global $frm_vars;
		$frm_vars['frman'] = array(
			'response' => $this->response,
			'type'     => $this->type,
			'entry'    => $this->entry,
		);
	}

	/**
	 * Controller class Authorize.net SDK
	 *
	 * @deprecated 2.0
	 * @param string         $request
	 * @param string         $type
	 */
	public function get_controller( $request, $type = 'payment' ) {
		return array();
	}
}
