<?php

namespace PaymentPlugins\WooCommerce\PPCP\Payments\Gateways;

use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\Messages;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Tokens\CreditCardToken;
use PaymentPlugins\WooCommerce\PPCP\Traits\TokenizationTrait;
use PaymentPlugins\WooCommerce\PPCP\Traits\VaultTokenTrait;
use PaymentPlugins\WooCommerce\PPCP\Traits\ThreeDSecureTrait;

class CreditCardGateway extends AbstractGateway {

	use VaultTokenTrait;
	use TokenizationTrait;
	use ThreeDSecureTrait;

	public $id = 'ppcp_card';

	protected $template = 'credit-card.php';

	protected $token_class = CreditCardToken::class;

	protected $tab_label_priority = 40;

	protected $payment_method_type = 'card';

	public function __construct( ...$args ) {
		parent::__construct( ...$args );
		$this->method_title       = __( 'PayPal Credit Card Gateway By Payment Plugins', 'pymntpl-paypal-woocommerce' );
		$this->tab_label          = __( 'PayPal Credit Card Settings', 'pymntpl-paypal-woocommerce' );
		$this->icon               = $this->assets->assets_url( 'assets/img/paypal_logo.svg' );
		$this->method_description = __( 'Offer Credit Cards by PayPal', 'pymntpl-paypal-woocommerce' );
		$this->order_button_text  = $this->get_option( 'order_button_text' );
		$this->icon               = $this->get_option( 'card_icons_url', '' );
	}

	public function init_form_fields() {
		$this->form_fields = [
			'enabled'                  => [
				'title'       => __( 'Enabled', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'Enable this option to offer PayPal on your site.', 'pymntpl-paypal-woocommerce' )
			],
			'title_text'               => [
				'title'       => __( 'Title', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'text',
				'default'     => __( 'Credit/Debit Cards', 'pymntpl-paypal-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'This is the title of the payment gateway which appears on the checkout page.', 'pymntpl-paypal-woocommerce' )
			],
			'description'              => [
				'title'       => __( 'Description', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'This is the description that appears when the payment gateway is selected on the checkout page.', 'pymntpl-paypal-woocommerce' )
			],
			'intent'                   => [
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'title'       => __( 'Transaction Type', 'pymntpl-paypal-woocommerce' ),
				'default'     => 'capture',
				'options'     => [
					'capture'   => __( 'Capture', 'pymntpl-paypal-woocommerce' ),
					'authorize' => __( 'Authorize', 'pymntpl-paypal-woocommerce' ),
				],
				'desc_tip'    => true,
				'description' => __(
					'If set to capture, funds will be captured immediately during checkout. Authorized transactions put a hold on the customer\'s funds but
						no payment is taken until the charge is captured. Authorized charges can be captured on the Admin Order page.',
					'pymntpl-paypal-woocommerce'
				),
			],
			'authorize_status'         => [
				'type'              => 'select',
				'class'             => 'wc-enhanced-select',
				'title'             => __( 'Authorized Order Status', 'pymntpl-paypal-woocommerce' ),
				'default'           => 'wc-on-hold',
				'options'           => function_exists( 'wc_get_order_statuses' )
					? wc_get_order_statuses()
					: [
						'wc-pending'    => _x( 'Pending payment', 'Order status', 'woocommerce' ),
						'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
						'wc-on-hold'    => _x( 'On hold', 'Order status', 'woocommerce' ),
						'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
						'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
						'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
						'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
					],
				'custom_attributes' => [
					'data-show-if' => 'intent=authorize'
				],
				'desc_tip'          => true,
				'description'       => __( 'If the transaction is authorized, this is the status applied to the order.', 'pymntpl-paypal-woocommerce' )
			],
			'order_button_text'        => [
				'title'       => __( 'Button Text', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'The text for the Place Order button when PayPal is selected. Leave blank to use the default WooCommerce text.',
					'pymntpl-paypal-woocommerce' )

			],
			'payment_format'           => [
				'title'       => __( 'Payment Method Format', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'select',
				'default'     => 'type_ending_in',
				'options'     => wp_list_pluck( $this->get_payment_method_token_instance()->get_payment_method_formats(), 'example' ),
				'desc_tip'    => true,
				'description' => __( 'This option controls how the PayPal payment method appears on the frontend.', 'pymntpl-paypal-woocommerce' )
			],
			'card_icons'               => [
				'title'       => __( 'Card Icons', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select stripe-accepted-cards',
				'default'     => [ 'amex', 'discover', 'visa', 'mastercard' ],
				'options'     => [
					'visa'       => __( 'Visa', 'pymntpl-paypal-woocommerce' ),
					'amex'       => __( 'Amex', 'pymntpl-paypal-woocommerce' ),
					'discover'   => __( 'Discover', 'pymntpl-paypal-woocommerce' ),
					'mastercard' => __( 'MasterCard', 'pymntpl-paypal-woocommerce' ),
					'jcb'        => __( 'JCB', 'pymntpl-paypal-woocommerce' ),
					'maestro'    => __( 'Maestro', 'pymntpl-paypal-woocommerce' ),
				],
				'desc_tip'    => true,
				'description' => __( 'These are the icons that show next to the payment method title.', 'pymntpl-paypal-woocommerce' )
			],
			'card_options'             => [
				'type'  => 'title',
				'title' => __( 'Card Form Options', 'pymntpl-paypal-woocommerce' ),
			],
			'card_save_enabled'        => [
				'title'       => __( 'Card Save Enabled', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'When enabled, customers will have the option to save their credit card on the checkout page. If you don\'t have vaulting enabled on the Advanced Settings page, this option will be disabled.', 'pymntpl-paypal-woocommerce' )
			],
			'cardholder_name'          => [
				'title'       => __( 'Enable Cardholder Name', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'When enabled, the credit card form will show a cardholder name input field.', 'pymntpl-paypal-woocommerce' )
			],
			'cardholder_name_required' => [
				'title'             => __( 'Cardholder Name Required', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'checkbox',
				'default'           => 'no',
				'value'             => 'yes',
				'desc_tip'          => true,
				'description'       => __( 'When enabled, the cardholder name field will be required rather than optional.', 'pymntpl-paypal-woocommerce' ),
				'custom_attributes' => [
					'data-show-if' => 'cardholder_name=true'
				],
			],
			'3ds_title'                => [
				'type'  => 'title',
				'title' => __( '3D Secure Options', 'pymntpl-paypal-woocommerce' ),
			],
			'3ds_enabled'              => [
				'title'       => __( 'Enable 3DS', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'When enabled, 3DS will be triggered when required.', 'pymntpl-paypal-woocommerce' )
			],
			'3ds_forced'               => [
				'title'             => __( 'Force 3DS', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'checkbox',
				'default'           => 'no',
				'value'             => 'yes',
				'desc_tip'          => true,
				'description'       => __( 'When enabled, 3DS forced for all transactions when supported.', 'pymntpl-paypal-woocommerce' ),
				'custom_attributes' => [
					'data-show-if' => '3ds_enabled=true'
				],
			],
			'3ds_config'               => [
				'type'              => 'configure_3ds',
				'label'             => __( 'Configure 3DS', 'pymntpl-paypal-woocommerce' ),
				'default'           => $this->get_3ds_actions(),
				'sanitize_callback' => function ( $value ) {
					return ! is_array( $value ) ? [] : array_map( 'wc_clean', array_map( 'stripslashes', $value ) );
				}
			],
			'fastlane_title'           => [
				'type'  => 'title',
				'title' => __( 'Fastlane by PayPal', 'pymntpl-paypal-woocommerce' ),
			],
			'fastlane_enabled'         => [
				'title'       => __( 'Fastlane Enabled', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'description' => __( 'Available for US PayPal Business accounts. When enabled, Fastlane by PayPal will be available on the checkout page.', 'pymntpl-paypal-woocommerce' )
			],
			'fastlane_flow'            => [
				'title'             => __( 'Fastlane Type', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'select',
				'default'           => 'email_detection',
				'options'           => [
					'express_button'  => __( 'Express Checkout Button', 'pymntpl-paypal-woocommerce' ),
					'email_detection' => __( 'Email Recognition', 'pymntpl-paypal-woocommerce' )
				],
				'value'             => 'yes',
				'description'       => __( 'If the express checkout option is enabled, a Fastlane express button will be available. The email recognition option will use dynamic
				email detection to render the Fastlane payment flow.', 'pymntpl-paypal-woocommerce' ),
				'custom_attributes' => [
					'data-show-if' => 'fastlane_enabled=true'
				],

			],
			'fastlane_signup'          => [
				'title'             => __( 'Fastlane Signup Link', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'checkbox',
				'default'           => 'yes',
				'value'             => 'yes',
				'description'       => __( 'If enabled, a signup button will be visible above the credit card form on the checkout page so customers can signup for Fastlane.', 'pymntpl-paypal-woocommerce' ),
				'custom_attributes' => [
					'data-show-if' => 'fastlane_enabled=true,fastlane_flow=email_detection'
				],
			],
			'fastlane_pageload'        => [
				'title'             => __( 'Authenticate On Page Load', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'checkbox',
				'default'           => 'no',
				'value'             => 'yes',
				'description'       => __( 'If enabled and the billing email is populated, the plugin will attempt to authenticate the user when the checkout page loads.', 'pymntpl-paypal-woocommerce' ),
				'custom_attributes' => [
					'data-show-if' => 'fastlane_enabled=true'
				]
			],
			'fastlane_icon_enabled'    => [
				'title'             => __( 'Fastlane Icon Enabled', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'checkbox',
				'default'           => 'yes',
				'value'             => 'yes',
				'description'       => __( 'If enabled, a PayPal Fastlane icon will render below the checkout page email field.', 'pymntpl-paypal-woocommerce' ),
				'custom_attributes' => [
					'data-show-if' => 'fastlane_enabled=true'
				],
			],
			'fastlane_email_top'       => [
				'title'             => __( 'Email At Top', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'checkbox',
				'default'           => 'yes',
				'value'             => 'yes',
				'description'       => __( 'When enabled, the email field appears at the top of the checkout page. This setting only applies when using the checkout shortcode on your site.', 'pymntpl-paypal-woocommerce' ),
				'custom_attributes' => [
					'data-show-if' => 'fastlane_enabled=true'
				]
			]
		];
	}

	public function admin_options() {
		if ( ! wc_ppcp_get_container()->get( AdvancedSettings::class )->is_vault_enabled() ) {
			$this->form_fields['card_save_enabled']['disabled'] = true;
		}
		parent::admin_options();
	}

	public function is_express_section_enabled() {
		return $this->is_fastlane_enabled()
		       && $this->get_option( 'fastlane_flow' ) === 'express_button';
	}

	public function express_checkout_fields() {
		$icon_url = $this->assets->assets_url( 'assets/img/fastlane.svg' );

		return $this->template_loader->load_template( 'fastlane/express-checkout.php', [ 'gateway' => $this, 'icon_url' => $icon_url ] );
	}

	public function get_admin_script_dependencies() {
		$src = add_query_arg( [
			'client-id'      => 'sb',
			'components'     => 'buttons',
			'enable-funding' => 'paylater,venmo'
		], 'https://www.paypal.com/sdk/js' );
		wp_register_script( 'wc-ppcp-sdk', $src, [], null, true );
		$this->assets->register_script( 'wc-ppcp-settings', 'build/js/credit-card-settings.js', [
			'jquery-ui-sortable',
			'jquery-ui-widget',
			'jquery-ui-core'
		] );

		return [ 'wc-ppcp-settings', 'wc-ppcp-sdk', 'jquery-ui-slider' ];
	}

	public function get_checkout_script_handles() {
		$this->assets->register_script( 'wc-ppcp-card-gateway', 'build/js/credit-cards.js', [ 'wc-ppcp-frontend-commons' ] );
		$this->tokenization_script();

		return [ 'wc-ppcp-card-gateway' ];
	}

	public function get_payment_method_data( $context ) {
		$cardname_required = wc_string_to_bool( $this->get_option( 'cardholder_name_required', 'no' ) ) && wc_string_to_bool( $this->get_option( 'cardholder_name', 'no' ) );
		$data              = [
			'needsSetupToken'  => $context->is_add_payment_method(),
			'cardNameRequired' => $cardname_required,
			'fields'           => [
				'nameField'   => [
					'placeholder' => $cardname_required ? __( 'Cardholder name', 'pymntpl-paypal-woocommerce' ) : __( 'Cardholder name (optional)', 'pymntpl-paypal-woocommerce' ),
				],
				'numberField' => [
					'placeholder' => __( 'Card number', 'pymntpl-paypal-woocommerce' )
				],
				'cvvField'    => [
					'placeholder' => __( 'CVV', 'pymntpl-paypal-woocommerce' )
				],
				'expiryField' => __( 'MM / YY', 'pymntpl-paypal-woocommerce' )
			],
			'i18n'             => [
				'error_codes'          => [
					'INVALID_NAME'   => __( 'Your card name is incomplete', 'pymntpl-paypal-woocommerce' ),
					'INVALID_NUMBER' => __( 'Your card number is incomplete', 'pymntpl-paypal-woocommerce' ),
					'INVALID_EXPIRY' => __( 'Your card\'s expiration date is incomplete.', 'pymntpl-paypal-woocommerce' ),
					'INVALID_CVV'    => __( 'Your card\'s security code is incomplete.', 'pymntpl-paypal-woocommerce' )
				],
				'not_available_admin'  => __( 'Advanced card processing is not available. Login to developer.paypal.com > Apps & Credentials and click your application. Under "Features" check "Advanced Card Processing".', 'pymntpl-paypal-woocommerce' ),
				'not_available'        => __( 'Credit card processing is not available. Please use another payment method.', 'pymntpl-paypal-woocommerce' ),
				'incomplete_card_form' => __( 'The credit card form is incomplete', 'pymntpl-paypal-woocommerce' )
			],
			'styles'           => [
				'input'          => [
					'padding'       => '0.75rem',
					'border'        => '1px solid #e6e6e6',
					'box-shadow'    => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02)',
					'border-radius' => '5px',
					'transition'    => 'background 0.15s ease, border 0.15s ease, box-shadow 0.15s ease, color 0.15s ease'
				],
				':focus'         => [
					'border'     => '1px solid #0570de',
					'box-shadow' => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02), 0 0 0 3px hsla(210, 96%, 45%, 25%), 0 1px 1px 0 rgba(0, 0, 0, 0.08)'
				],
				'.invalid'       => [
					'color'      => '#df1b41',
					'border'     => '1px solid #df1b41',
					'box-shadow' => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02), 0 0 0 1px #df1b41'
				],
				':focus.invalid' => [
					'box-shadow' => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02), 0 0 0 1px #df1b41'
				]
			]
		];

		return $data;
	}

	/**
	 * Returns true if the cardholder name field is enabled.
	 *
	 * @return bool
	 */
	public function is_cardholder_name_enabled() {
		return wc_string_to_bool( $this->get_option( 'cardholder_name', 'yes' ) );
	}

	/**
	 * Returns true if 3DS is enabled.
	 *
	 * @return bool
	 */
	public function is_3ds_enabled() {
		return wc_string_to_bool( $this->get_option( '3ds_enabled', 'no' ) );
	}

	public function show_card_save_checkbox() {
		$bool = $this->is_card_save_enabled() && ! is_add_payment_method_page();
		if ( $bool ) {
			$bool = wc_ppcp_get_container()->get( AdvancedSettings::class )->is_vault_enabled();
		}

		return apply_filters( 'wc_ppcp_show_card_save_checkbox', $bool, $this );
	}

	public function validate_card_icons_field( $key, $value ) {
		if ( ! \is_array( $value ) ) {
			$value = [];
		}
		$svg = '';

		if ( $value ) {
			// Calculate total width for the container SVG
			$svg_width = ( \count( $value ) * 750 ) + ( ( \count( $value ) - 1 ) * 78 );

			// Fix the string concatenation in viewBox (removed the + operator)
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $svg_width / 15.625 . '" height="30" viewBox="0 0 ' . $svg_width . ' 468.75">';

			foreach ( $value as $idx => $key ) {
				$icon = wc_ppcp_load_template_html( "icons/cards/{$key}.php" );
				// Calculate correct transform position (48px width + 10px spacing)
				$transform_x = $idx > 0 ? $idx * ( 750 + 78 ) : 0;
				// Apply transformation
				$svg .= str_replace( 'transform="translate(0,0)"', 'transform="translate(' . $transform_x . ',0)"', $icon );
			}

			$svg .= '</svg>';
		}

		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		WP_Filesystem();

		global $wp_filesystem;

		if ( $wp_filesystem ) {
			$uploads = wp_upload_dir( current_time( 'mysql' ) );
			$file    = $uploads['path'] . '/ppcp-card-icons.svg';
			if ( $wp_filesystem->put_contents( $file, $svg ) ) {
				$this->settings['card_icons_url'] = $uploads['url'] . '/ppcp-card-icons.svg';
			}
		}

		return $value;
	}

	public function add_payment_complete_note( \WC_Order $order, PaymentResult $result ) {
		if ( $result->is_captured() ) {
			$charge_text = sprintf( __( 'Capture ID: %s', 'pymntpl-paypal-woocommerce' ), $result->get_capture_id() );
		} else {
			$charge_text = sprintf( __( 'Authorization ID: %s', 'pymntpl-paypal-woocommerce' ), $result->get_authorization_id() );
		}
		$token = $this->get_payment_method_token_instance();
		$token->initialize_from_paypal_order( $result->get_paypal_order() );

		$order->add_order_note(
			sprintf(
				__( 'PayPal order %1$s created. %2$s. Payment method: %3$s', 'pymntpl-paypal-woocommerce' ),
				$result->paypal_order->id,
				$charge_text,
				$token->get_payment_method_title()
			)
		);
	}

	/**
	 * Returns true if Fastlane is enabled.
	 *
	 * @return bool
	 *
	 */
	public function is_fastlane_enabled() {
		return \wc_string_to_bool( $this->get_option( 'fastlane_enabled', 'no' ) );
	}

	public function validate_paypal_order( $paypal_order, $order ) {
		if ( $this->is_3ds_enabled() ) {
			// 3DS is enabled so check conditions for payments.
			$payment_source        = $paypal_order->getPaymentSource();
			$card                  = $payment_source->getCard();
			$authentication_result = $card->getAuthenticationResult();

			if ( ! $authentication_result ) {
				$key = 'N_N_NO';
			} else {
				$threed_result = $authentication_result->getThreeDSecure();

				$key = sprintf(
					'%1$s_%2$s_%3$s',
					$threed_result->getEnrollmentStatus(),
					$threed_result->getAuthenticationStatus(),
					$authentication_result->getLiabilityShift()
				);
			}

			$recommended_actions = wp_parse_args( $this->get_option( '3ds_config', [] ), $this->get_3ds_actions() );

			if ( isset( $recommended_actions[ $key ] ) ) {
				$messages = wc_ppcp_get_container()->get( Messages::class );
				$action   = $recommended_actions[ $key ];

				switch ( $action ) {
					case 'reject':
						$text = $messages->get_message( $key, __( '3DS payment has been rejected.', 'pymntpl-paypal-woocommerce' ) );
						throw new \Exception( $text );
				}
			}
		}
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @since 1.1.5
	 * @return void
	 */
	public function generate_configure_3ds_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$data      = wp_parse_args( $data, [
			'label'       => '',
			'desc_tip'    => false,
			'description' => ''
		] );

		$value = $this->get_option( $key, [] );
		if ( ! \is_array( $value ) ) {
			$value = [];
		}
		$actions = $this->get_3ds_actions();
		$value   = wp_parse_args( $value, $actions );

		ob_start();
		?>
        <tr valign="top">
            <th scope="row" class="titledesc"></th>
            <td class="forminp">
                <fieldset>
                    <button class="button-secondary show3DSModal">
						<?php echo wp_kses_post( $data['label'] ) ?>
                    </button>
                    <div id="3ds-app"></div>
					<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
					<?php foreach ( $value as $k => $v ) : ?>
                        <input type="hidden" name="<?php echo esc_attr( $field_key ) . '[' . $k . ']' ?>" value="<?php echo esc_attr( $v ) ?>"
                               data-key="<?php echo esc_attr( $k ) ?>" data-default="<?php echo esc_attr( $actions[ $k ] ?? '' ) ?>"/>
					<?php endforeach; ?>
                </fieldset>
            </td>
        </tr>
		<?php
		return ob_get_clean();
	}

	private function get_3ds_actions() {
		return apply_filters( 'wc_ppcp_get_3ds_actions', [
			'N_N_NO'       => 'continue',
			'Y_Y_POSSIBLE' => 'continue',
			'Y_Y_YES'      => 'continue',
			'Y_N_NO'       => 'reject',
			'Y_R_NO'       => 'reject',
			'Y_A_POSSIBLE' => 'continue',
			'Y_U_UNKNOWN'  => 'reject',
			'Y_U_NO'       => 'reject',
			'Y_C_UNKNOWN'  => 'reject',
			'Y__NO'        => 'reject',
			'N__NO'        => 'continue',
			'U__NO'        => 'continue',
			'U__UNKNOWN'   => 'reject',
			'B__NO'        => 'continue',
			'__UNKNOWN'    => 'reject',
		] );
	}

	public function get_admin_script_data() {
		return [
			'i18n' => [
				'save'                  => __( 'Save', 'pymntpl-paypal-woocommerce' ),
				'cancel'                => __( 'Cancel', 'pymntpl-paypal-woocommerce' ),
				'reset'                 => __( 'Reset Settings', 'pymntpl-paypal-woocommerce' ),
				'description'           => __( 'These settings define how payment transactions should be handled based on the customer\'s 3D 
				Secure enrollment status, authentication outcome, and liability shift result.', 'pymntpl-paypal-woocommerce' ),
				'continue'              => __( 'Continue', 'pymntpl-paypal-woocommerce' ),
				'reject'                => __( 'Reject', 'pymntpl-paypal-woocommerce' ),
				'enrollment_status'     => __( 'Enrollment Status', 'pymntpl-paypal-woocommerce' ),
				'authentication_status' => __( 'Authentication Status', 'pymntpl-paypal-woocommerce' ),
				'liability_shift'       => __( 'Liability Shift', 'pymntpl-paypal-woocommerce' ),
				'action'                => __( 'Action', 'pymntpl-paypal-woocommerce' ),
				'desc2'                 => sprintf( __( 'For a full description of each response code, click %1$shere%2$s.' ), '<a href="https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/#supported-parameters" target="_blank">', '<a/>' )
			]
		];
	}

}