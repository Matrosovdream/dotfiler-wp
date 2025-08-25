<?php

class FrmTransActionsController {

	/**
	 * Track the entry IDs we're destroying so we don't attempt to delete an entry more than once.
	 * Set in self::destroy_entry_later.
	 *
	 * @var array
	 */
	private static $entry_ids_to_destroy_later = array();

	/**
	 * Register payment action type.
	 *
	 * @param array $actions
	 * @return array
	 */
	public static function register_actions( $actions ) {
		$actions['payment'] = 'FrmTransAction';
		return $actions;
	}

	/**
	 * Include scripts for handling payments at an administrative level.
	 * This includes handling the after payment settings for Stripe actions.
	 * It also handles refunds and canceling subscriptions.
	 *
	 * @return void
	 */
	public static function actions_js() {
		wp_enqueue_script( 'frmtrans_admin', FrmTransAppHelper::plugin_url() . '/js/frmtrans_admin.js', array( 'jquery', 'wp-hooks' ), FrmTransAppHelper::plugin_version() );
		wp_localize_script( 'frmtrans_admin', 'frm_trans_vars', array(
			'nonce' => wp_create_nonce( 'frm_trans_ajax' ),
		) );
	}

	/**
	 * Add event types for actions so an email can trigger on a successful payment.
	 *
	 * @param array $triggers
	 * @return array
	 */
	public static function add_payment_trigger( $triggers ) {
		$triggers['payment-success']       = __( 'Successful Payment', 'formidable' );
		$triggers['payment-failed']        = __( 'Failed Payment', 'formidable' );
		$triggers['payment-refunded']      = __( 'Refunded Payment', 'formidable' );
		$triggers['payment-processing']    = __( 'Processing Payment', 'formidable' );
		$triggers['payment-future-cancel'] = __( 'Canceled Subscription', 'formidable' );
		$triggers['payment-canceled']      = __( 'Subscription Canceled and Expired', 'formidable' );
		return $triggers;
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public static function add_trigger_to_action( $options ) {
		$options['event'][] = 'payment-success';
		$options['event'][] = 'payment-failed';
		$options['event'][] = 'payment-future-cancel';
		$options['event'][] = 'payment-canceled';
		$options['event'][] = 'payment-refunded';
		return $options;
	}

	/**
	 * Add the payment trigger to registration 2.0+.
	 *
	 * @since 1.09
	 *
	 * @param array $options
	 * @return array
	 */
	public static function add_trigger_to_register_user_action( $options ) {
		if ( is_callable( 'FrmRegUserController::register_user' ) ) {
			$options['event'][] = 'payment-success';
		}

		return $options;
	}

	/**
	 * @return void
	 */
	public static function trigger_action( $action, $entry, $form ) {
		$gateway = self::get_gateway_for_entry( $action, $entry );
		if ( ! $gateway ) {
			return;
		}

		$class_name = FrmTransAppHelper::get_setting_for_gateway( $gateway, 'class' );
		if ( ! $class_name ) {
			return;
		}

		self::prepare_description( $action, compact( 'entry', 'form' ) );

		$class_name = 'Frm' . $class_name . 'ActionsController';
		$response   = $class_name::trigger_gateway( $action, $entry, $form );

		if ( ! $response['success'] ) {
			// the payment failed
			if ( $response['show_errors'] ) {
				self::show_failed_message( compact( 'action', 'entry', 'form', 'response' ) );
			}
		} elseif ( $response['run_triggers'] ) {
			$status = 'complete';
			self::trigger_payment_status_change( compact( 'status', 'action', 'entry' ) );
		}
	}

	private static function get_gateway_for_entry( $action, $entry ) {
		$gateway_field = FrmAppHelper::get_post_param( 'frm_gateway', '', 'absint' );
		if ( empty( $gateway_field ) ) {
			$field = FrmField::getAll( array( 'fi.form_id' => $action->menu_order, 'type' => 'gateway' ) );
			if ( ! empty( $field ) ) {
				$field = reset( $field );
				$gateway_field = $field->id;
			}
		}

		$gateway = '';
		if ( ! empty( $gateway_field ) ) {
			$posted_value = ( isset( $_POST['item_meta'][ $gateway_field ] ) ? sanitize_text_field( $_POST['item_meta'][ $gateway_field ] ) : '' );
			$gateway = isset( $entry->metas[ $gateway_field ] ) ? $entry->metas[ $gateway_field ] : $posted_value;
		}

		return $gateway;
	}

	public static function trigger_gateway( $action, $entry, $form ) {
		// This function must be overridden in a subclass.
		return array(
			'success'      => false,
			'run_triggers' => false,
			'show_errors'  => true,
		);
	}

	/**
	 * @param array $args
	 * @return void
	 */
	public static function show_failed_message( $args ) {
		global $frm_vars;
		$frm_vars['frm_trans'] = array(
			'pay_entry' => $args['entry'],
			'error'     => isset( $args['response']['error'] ) ? $args['response']['error'] : '',
		);

		add_filter( 'frm_success_filter', 'FrmTransActionsController::force_message_after_create' );
		add_filter( 'frm_pre_display_form', 'FrmTransActionsController::include_form_with_success' );
		add_filter( 'frm_main_feedback', 'FrmTransActionsController::replace_success_message', 5 );
		add_filter( 'frm_setup_new_fields_vars', 'FrmTransActionsController::fill_entry_from_previous', 20, 2 );
	}

	/**
	 * @return string
	 */
	public static function force_message_after_create() {
		return 'message';
	}

	/**
	 * @since 2.07
	 *
	 * @param stdClass $form
	 * @return stdClass
	 */
	public static function include_form_with_success( $form ) {
		$form->options['show_form'] = 1;
		return $form;
	}

	/**
	 * @return string
	 */
	public static function replace_success_message() {
		global $frm_vars;
		$message = isset( $frm_vars['frm_trans']['error'] ) ? $frm_vars['frm_trans']['error'] : '';
		if ( empty( $message ) ) {
			$message = __( 'There was an error processing your payment.', 'formidable' );
		}

		$message = '<div class="frm_error_style">' . $message . '</div>';

		return $message;
	}

	/**
	 * Entries are deleted on payment failure so set the form values after an error from the entry data that gets deleted.
	 *
	 * @param array    $values
	 * @param stdClass $field
	 * @return array
	 */
	public static function fill_entry_from_previous( $values, $field ) {
		global $frm_vars;
		$previous_entry = isset( $frm_vars['frm_trans']['pay_entry'] ) ? $frm_vars['frm_trans']['pay_entry'] : false;
		if ( empty( $previous_entry ) || $previous_entry->form_id != $field->form_id ) {
			return $values;
		}

		if ( is_array( $previous_entry->metas ) && isset( $previous_entry->metas[ $field->id ] ) ) {
			$values['value'] = $previous_entry->metas[ $field->id ];
		}

		$frm_vars['trans_filled'] = true;

		// CUSTOM UPDATE of the Core plugin
		if( get_option('frm_remove_failed_entries') ) {
			$previous_entry_id = $previous_entry->id;
			self::destroy_entry_later( $previous_entry_id );
		}

		return $values;
	}

	/**
	 * Destroy an entry, but delay it to happen when the form is displayed.
	 * It needs to happen late enough that FrmProNestedFormsController::display_single_iteration_of_nested_form is able to fill in data for repeater fields.
	 * See Formidable Stripe issue #136 for more information.
	 *
	 * @since 2.04
	 *
	 * @param string|int $entry_id
	 * @return void
	 */
	private static function destroy_entry_later( $entry_id ) {
		if ( in_array( (int) $entry_id, self::$entry_ids_to_destroy_later, true ) ) {
			// Avoid trying to delete this multiple times as fill_entry_from_previous is called more than once.
			return;
		}

		$destroy_callback =
			/**
			 * Destroy an entry and remove this action so it only tries to destroy the entry once.
			 *
			 * @param string|int $entry_id
			 * @param Closure    $destroy_callback
			 * @return void
			 */
			function() use ( $entry_id, &$destroy_callback ) {
				FrmEntry::destroy( $entry_id );
				remove_action( 'frm_entry_form', $destroy_callback ); // Only call this once.
				
			};
		add_action( 'frm_entry_form', $destroy_callback );

		self::$entry_ids_to_destroy_later[] = (int) $entry_id;
	}

	/**
	 * @since 1.12
	 *
	 * @param object $sub
	 * @return void
	 */
	public static function trigger_subscription_status_change( $sub ) {
		$frm_payment = new FrmTransPayment();
		$payment     = $frm_payment->get_one_by( $sub->id, 'sub_id' );

		if ( $payment && $payment->action_id ) {
			self::trigger_payment_status_change( array(
				'status'  => $sub->status,
				'payment' => $payment,
			) );
		}
	}

	/**
	 * @param array $atts
	 * @return void
	 */
	public static function trigger_payment_status_change( $atts ) {
		$action = isset( $atts['action'] ) ? $atts['action'] : $atts['payment']->action_id;
		$entry_id = isset( $atts['entry'] ) ? $atts['entry']->id : $atts['payment']->item_id;
		$atts = array( 'trigger' => $atts['status'], 'entry_id' => $entry_id );

		if ( ! isset( $atts['payment'] ) ) {
			$frm_payment     = new FrmTransPayment();
			$atts['payment'] = $frm_payment->get_one_by( $entry_id, 'item_id' );
		}

		if ( ! isset( $atts['trigger'] ) ) {
			$atts['trigger'] = $atts['status'];
		}

		// Set future-cancel as trigger when applicable.
		$atts['trigger'] = str_replace( '_', '-', $atts['trigger'] );

		self::set_fields_after_payment( $action, $atts );
		if ( $atts['payment'] ) {
			self::trigger_actions_after_payment( $atts['payment'], $atts );
		}
	}

	/**
	 * Maybe trigger payment-success or payment-failed event after payment so actions (like emails) can run.
	 *
	 * @param object $payment
	 * @param array  $atts
	 * @return void
	 */
	public static function trigger_actions_after_payment( $payment, $atts = array() ) {
		if ( ! is_callable( 'FrmFormActionsController::trigger_actions' ) ) {
			return;
		}

		if ( 'pending' === $payment->status ) {
			// 3D Secure has a delayed payment status, so avoid sending a payment failed email for a pending payment.
			return;
		}

		$entry = FrmEntry::getOne( $payment->item_id );

		if ( isset( $atts['trigger'] ) ) {
			$trigger_event = 'payment-' . $atts['trigger'];
		} else {
			$trigger_event = 'payment-' . $payment->status;
		}

		$allowed_triggers = array_keys( self::add_payment_trigger( array() ) );

		if ( ! in_array( $trigger_event, $allowed_triggers, true ) ) {
			$trigger_event = ( $payment->status === 'complete' ) ? 'payment-success' : 'payment-failed';
		}

		FrmFormActionsController::trigger_actions( $trigger_event, $entry->form_id, $entry->id );
	}

	/**
	 * @param mixed $action
	 * @param array $atts
	 * @return void
	 */
	public static function set_fields_after_payment( $action, $atts ) {
		/**
		 * @param array $atts
		 */
		do_action( 'frm_payment_status_' . $atts['trigger'], $atts );

		if ( ! is_callable( 'FrmProEntryMeta::update_single_field' ) || empty( $action ) ) {
			return;
		}

		if ( is_numeric( $action ) ) {
			$action = FrmTransAction::get_single_action_type( $action, 'payment' );
		}

		self::change_fields( $action, $atts );
	}

	/**
	 * @param WP_Post $action
	 * @param array   $atts
	 * @return void
	 */
	private static function change_fields( $action, $atts ) {
		if ( empty( $action->post_content['change_field'] ) ) {
			return;
		}

		foreach ( $action->post_content['change_field'] as $change_field ) {
			$is_trigger_for_field = $change_field['status'] == $atts['trigger'];
			if ( $is_trigger_for_field ) {
				$value = FrmTransAppHelper::process_shortcodes( array(
					'value' => $change_field['value'],
					'form'  => $action->menu_order,
					'entry' => isset( $atts['entry'] ) ? $atts['entry'] : $atts['entry_id'],
				) );

				FrmProEntryMeta::update_single_field( array(
					'entry_id' => $atts['entry_id'],
					'field_id' => $change_field['id'],
					'value'    => $value,
				) );
			}
		}
	}

	/**
	 * Filter fields in description.
	 *
	 * @param WP_Post $action
	 * @param array   $atts
	 * @return void
	 */
	public static function prepare_description( &$action, $atts ) {
		$description = $action->post_content['description'];
		if ( ! empty( $description ) ) {
			$atts['value']                       = $description;
			$description                         = FrmTransAppHelper::process_shortcodes( $atts );
			$action->post_content['description'] = $description;
		}
	}

	/**
	 * Convert the amount into 10.00.
	 *
	 * @param mixed $amount
	 * @param array $atts
	 * @return string
	 */
	public static function prepare_amount( $amount, $atts = array() ) {
		if ( isset( $atts['form'] ) ) {
			$atts['value'] = $amount;
			$amount = FrmTransAppHelper::process_shortcodes( $atts );
		}

		if ( is_string( $amount ) && strlen( $amount ) >= 2 && $amount[0] == '[' && substr( $amount, -1 ) == ']' ) {
			// make sure we don't use a field id as the amount
			$amount = 0;
		}

		$currency = self::get_currency_for_action( $atts );

		$total = 0;
		foreach ( (array) $amount as $a ) {
			$this_amount = self::get_amount_from_string( $a );
			self::maybe_use_decimal( $this_amount, $currency );
			self::normalize_number( $this_amount, $currency );

			$total += $this_amount;
			unset( $a, $this_amount );
		}

		return number_format ( $total, $currency['decimals'], '.', '' );
	}

	/**
	 * Get currency to use when preparing amount.
	 *
	 * @param array $atts
	 * @return array
	 */
	public static function get_currency_for_action( $atts ) {
		$currency = 'usd';
		if ( isset( $atts['form'] ) ) {
			$currency = $atts['action']->post_content['currency'];
		} elseif ( isset( $atts['currency'] ) ) {
			$currency = $atts['currency'];
		}

		return FrmTransAppHelper::get_currency( $currency );
	}

	/**
	 * @param string $amount
	 * @return string
	 */
	private static function get_amount_from_string( $amount ) {
		$amount = html_entity_decode( $amount );
		$amount = trim( $amount );
		preg_match_all( '/[0-9,.]*\.?\,?[0-9]+/', $amount, $matches );
		$amount = $matches ? end( $matches[0] ) : 0;
		return $amount;
	}

	/**
	 * @param string $amount
	 * @param array  $currency
	 * @return void
	 */
	private static function maybe_use_decimal( &$amount, $currency ) {
		if ( $currency['thousand_separator'] !== '.' ) {
			return;
		}

		$amount_parts = explode( '.', $amount );
		if ( 2 !== count( $amount_parts ) ) {
			return;
		}

		$strlen           = strlen( $amount_parts[1] );
		$used_for_decimal = $strlen >= 1 && $strlen <= 2;

		if ( $used_for_decimal ) {
			$amount = str_replace( '.', $currency['decimal_separator'], $amount );
		}
	}

	/**
	 * @param string $amount
	 * @param array  $currency
	 * @return void
	 */
	private static function normalize_number( &$amount, $currency ) {
		$amount = str_replace( $currency['thousand_separator'], '', $amount );
		$amount = str_replace( $currency['decimal_separator'], '.', $amount );
		$amount = number_format( (float) $amount, $currency['decimals'], '.', '' );
	}

	/**
	 * These settings are included in frm_stripe_vars.settings global JavaScript object on Stripe forms.
	 *
	 * @param int $form_id
	 * @return array
	 */
	public static function prepare_settings_for_js( $form_id ) {
		$payment_actions = self::get_actions_for_form( $form_id );
		$action_settings = array();
		foreach ( $payment_actions as $payment_action ) {
			$js_vars = array(
				'id'         => $payment_action->ID,
				'address'    => $payment_action->post_content['billing_address'],
				'first_name' => $payment_action->post_content['billing_first_name'],
				'last_name'  => $payment_action->post_content['billing_last_name'],
				'gateways'   => $payment_action->post_content['gateway'],
				'fields'     => self::get_fields_for_price( $payment_action ),
				'one'        => $payment_action->post_content['type'],
				'email'      => $payment_action->post_content['email'],
			);

			/**
			 * @param array   $js_vars
			 * @param WP_Post $payment_action
			 */
			$action_settings[] = apply_filters( 'frm_trans_settings_for_js', $js_vars, $payment_action );
		}

		return $action_settings;
	}

	/**
	 * Include the price field ids to pass to the javascript.
	 *
	 * @since 2.0
	 */
	private static function get_fields_for_price( $action ) {
		$amount = $action->post_content['amount'];
		if ( ! is_callable( 'FrmProDisplaysHelper::get_shortcodes' ) ) {
			return -1;
		}
		$shortcodes = FrmProDisplaysHelper::get_shortcodes( $amount, $action->menu_order );
		return isset( $shortcodes[2] ) ? $shortcodes[2] : -1;
	}

	/**
	 * Get all published payment actions.
	 *
	 * @param int|string $form_id
	 * @return array
	 */
	public static function get_actions_for_form( $form_id ) {
		$action_status   = array(
			'post_status' => 'publish',
		);
		$payment_actions = FrmFormAction::get_action_for_form( $form_id, 'payment', $action_status );
		if ( empty( $payment_actions ) ) {
			$payment_actions = array();
		}
		return $payment_actions;
	}

	/**
	 * Show a tooltip icon with the message passed.
	 *
	 * @since 2.09
	 *
	 * @param string $message The message to be displayed in the tooltip.
	 * @param array  $atts    The attributes to be added to the tooltip.
	 *
	 * @return void
	 */
	public static function show_svg_tooltip( $message, $atts = array() ) {
		if ( ! is_callable( 'FrmAppHelper::tooltip_icon' ) ) {
			return;
		}
		FrmAppHelper::tooltip_icon( $message, $atts );
	}

	/**
	 * @deprecated 2.07
	 *
	 * @param stdClass $form
	 * @return stdClass
	 */
	public static function include_form_with_sucess( $form ) {
		_deprecated_function( __METHOD__, '2.07', 'FrmTransActionsController::include_form_with_success' );
		return self::include_form_with_success( $form );
	}

	/**
	 * Filter payment action on save.
	 *
	 * @since 2.11
	 *
	 * @param array $settings
	 * @param array $action
	 * @return array
	 */
	public static function before_save_settings( $settings, $action ) {
		$settings['gateway'] = isset( $settings['gateway'] ) ? (array) $settings['gateway'] : array();

		if ( in_array( 'square', $settings['gateway'], true ) && is_callable( 'FrmSquareLiteConnectHelper::get_merchant_currency' ) ) {
			$currency = FrmSquareLiteConnectHelper::get_merchant_currency();
			if ( false !== $currency ) {
				$settings['currency'] = strtolower( $currency );
			} else {
				$settings['currency'] = 'usd';
			}
		} else {
			$settings['currency'] = strtolower( $settings['currency'] );
		}

		FrmTransFieldsController::auto_add_gateway_field( $settings, $action );

		return $settings;
	}
}
