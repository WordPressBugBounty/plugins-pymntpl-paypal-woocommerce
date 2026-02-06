<?php

namespace PaymentPlugins\WooCommerce\PPCP\Payments\Gateways;

use PaymentPlugins\PPCP\WooCommercePreOrders\Traits\PreOrdersTrait;
use PaymentPlugins\PPCP\WooCommerceSubscriptions\Traits\SubscriptionTrait;
use PaymentPlugins\WooCommerce\PPCP\ProductSettings;
use PaymentPlugins\WooCommerce\PPCP\Tokens\CreditCardToken;
use PaymentPlugins\WooCommerce\PPCP\Traits\CardPaymentNoteTrait;
use PaymentPlugins\WooCommerce\PPCP\Traits\ThreeDSecureTrait;
use PaymentPlugins\WooCommerce\PPCP\Traits\VaultTokenTrait;

class ApplePayGateway extends AbstractGateway {

	use VaultTokenTrait;
	use ThreeDSecureTrait;
	use CardPaymentNoteTrait;
	use SubscriptionTrait;
	use PreOrdersTrait;

	public $id = 'ppcp_applepay';

	protected $template = 'applepay.php';

	protected $token_class = CreditCardToken::class;

	protected $tab_label_priority = 51;

	protected $payment_method_type = 'apple_pay';

	private $supported_currencies = [
		'AUD',
		'BRL',
		'CAD',
		'CHF',
		'CZK',
		'DKK',
		'EUR',
		'GBP',
		'HKD',
		'HUF',
		'ILS',
		'JPY',
		'MXN',
		'NOK',
		'NZD',
		'PHP',
		'PLN',
		'SEK',
		'SGD',
		'THB',
		'TWD',
		'USD'
	];

	public function __construct( ...$args ) {
		parent::__construct( ...$args );
		$this->method_title       = __( 'Apple Pay Gateway By Payment Plugins', 'pymntpl-paypal-woocommerce' );
		$this->tab_label          = __( 'Apple Pay Settings', 'pymntpl-paypal-woocommerce' );
		$this->icon               = $this->assets->assets_url( 'assets/img/applepay/applepay.svg' );
		$this->method_description = __( 'Offer Apple Pay through PayPal', 'pymntpl-paypal-woocommerce' );
		$this->order_button_text  = $this->get_option( 'order_button_text' );
	}

	public function init_form_fields() {
		$this->form_fields = [
			'domain_file'        => [
				'title'       => __( 'Domain Association File', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'domain_file',
				'label'       => __( 'Add domain association file', 'pymntpl-paypal-woocommerce' ),
				'description' => sprintf( __( 'Apple Pay requires a domain association file to verify domain ownership. Follow the %1$sSetup Guide%2$s to complete your domain registration.', 'pymntpl-paypal-woocommerce' ), '<a target="_blank" href="https://paymentplugins.com/documentation/paypal/applepay/setup">', '</a>' ),
				'desc_tip'    => false
			],
			'enabled'            => [
				'title'       => __( 'Enabled', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'Enable this option to offer PayPal on your site.', 'pymntpl-paypal-woocommerce' )
			],
			'title_text'         => [
				'title'       => __( 'Title', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'text',
				'default'     => __( 'Apple Pay', 'pymntpl-paypal-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'This is the title of the payment gateway which appears on the checkout page.', 'pymntpl-paypal-woocommerce' )
			],
			'description'        => [
				'title'       => __( 'Description', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'This is the description that appears when the payment gateway is selected on the checkout page.', 'pymntpl-paypal-woocommerce' )
			],
			'intent'             => [
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
			'authorize_status'   => [
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
			'display_name'       => [
				'title'       => __( 'Display Name', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Display name for your store shown in the Apple Pay wallet.', 'pymntpl-paypal-woocommerce' )
			],
			'order_button_text'  => [
				'title'       => __( 'Button Text', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'The text for the Place Order button when Apple Pay is selected. Leave blank to use the default WooCommerce text.',
					'pymntpl-paypal-woocommerce' )

			],
			'sections'           => [
				'title'             => __( 'Apple Pay Payment Sections', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'default'           => [ 'cart', 'checkout', 'order_pay' ],
				'options'           => [
					'checkout'         => __( 'Checkout Page', 'pymntpl-paypal-woocommerce' ),
					'product'          => __( 'Product Page', 'pymntpl-paypal-woocommerce' ),
					'cart'             => __( 'Cart Page', 'pymntpl-paypal-woocommerce' ),
					'minicart'         => __( 'Minicart', 'pymntpl-paypal-woocommerce' ),
					'express_checkout' => __( 'Express Checkout', 'pymntpl-paypal-woocommerce' ),
					'order_pay'        => __( 'Order Pay', 'pymntpl-paypal-woocommerce' )
				],
				'sanitize_callback' => function ( $value ) {
					return ! is_array( $value ) ? [] : $value;
				},
				'desc_tip'          => true,
				'description'       => __( 'These are the sections that the Apple Pay payment button will appear. If Apple Pay is enabled, the button will show on the checkout page by default.', 'pymntpl-paypal-woocommerce' )
			],
			'payment_format'     => [
				'title'       => __( 'Payment Method Format', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'select',
				'default'     => 'type_ending_in',
				'options'     => wp_list_pluck( $this->get_payment_method_token_instance()->get_payment_method_formats(), 'example' ),
				'desc_tip'    => true,
				'description' => __( 'This option controls how the PayPal payment method appears on the frontend.', 'pymntpl-paypal-woocommerce' )
			],
			'checkout_placement' => [
				'title'       => __( 'Checkout page Button Placement', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'select',
				'options'     => [
					'place_order'    => __( 'Place Order Button', 'pymntpl-paypal-woocommerce' ),
					'payment_method' => __( 'In payment gateway section', 'pymntpl-paypal-woocommerce' )
				],
				'default'     => 'place_order',
				'desc_tip'    => true,
				'description' => __( 'You can choose to render the Apple Pay button in either the payment method section of the checkout page or where the Place Order button is rendered.', 'pymntpl-paypal-woocommerce' )
			],
			'3ds_title'          => [
				'type'  => 'title',
				'title' => __( '3D Secure Options', 'pymntpl-paypal-woocommerce' ),
			],
			'3ds_enabled'        => [
				'title'       => __( 'Enable 3DS', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'When enabled, 3DS will be triggered when required.', 'pymntpl-paypal-woocommerce' )
			],
			'3ds_forced'         => [
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
			'button_section'     => [
				'type'  => 'title',
				'title' => __( 'Button Options', 'pymntpl-paypal-woocommerce' ),
			],
			'button_style'       => [
				'title'       => __( 'Button Color', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => [
					'black'         => __( 'Black', 'pymntpl-paypal-woocommerce' ),
					'white'         => __( 'White', 'pymntpl-paypal-woocommerce' ),
					'white-outline' => __( 'White', 'pymntpl-paypal-woocommerce' ),
				],
				'default'     => 'black',
				'desc_tip'    => true,
				'description' => __( 'The style of the Apple Pay button.', 'pymntpl-paypal-woocommerce' ),
			],
			'button_type'        => [
				'title'       => __( 'Button Type', 'pymntpl-paypal-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => [
					'plain'     => __( 'Standard Button', 'pymntpl-paypal-woocommerce' ),
					'buy'       => __( 'Buy with Apple Pay', 'pymntpl-paypal-woocommerce' ),
					'check-out' => __( 'Checkout with Apple Pay', 'pymntpl-paypal-woocommerce' )
				],
				'default'     => 'plain',
				'desc_tip'    => true,
				'description' => __( 'The type/text of the Google Pay button.', 'pymntpl-paypal-woocommerce' ),
			],
			'button_height'      => [
				'type'              => 'slider',
				'title'             => __( 'Button Height', 'pymntpl-paypal-woocommerce' ),
				'default'           => 40,
				'custom_attributes' => [
					'data-height-min'  => 25,
					'data-height-max'  => 55,
					'data-height-step' => 1,
				]
			],
			'button_radius'      => [
				'title'             => __( 'Button Radius', 'pymntpl-paypal-woocommerce' ),
				'type'              => 'number',
				'default'           => '4',
				'custom_attributes' => [
					'min'  => '0',
					'step' => '1'
				],
				'desc_tip'          => true,
				'description'       => __( 'The border radius of the button in pixels. Must be a non-negative integer.', 'pymntpl-paypal-woocommerce' ),
				'sanitize_callback' => function ( $value ) {
					if ( ! preg_match( '/^[\d]+$/', $value ) ) {
						$value = 0;
					}

					return absint( $value );
				}
			]
		];
	}

	public function get_admin_script_dependencies() {
		$this->assets->register_script(
			'wc-ppcp-applepay-settings',
			'build/js/applepay-settings.js',
			[
				'jquery-ui-sortable',
				'jquery-ui-widget',
				'jquery-ui-core',
				'jquery-ui-slider'
			]
		);

		return [ 'wc-ppcp-applepay-settings' ];
	}

	public function get_checkout_script_handles() {
		$this->assets->register_script(
			'wc-ppcp-applepay-checkout',
			'build/js/applepay-checkout.js'
		);
		$this->tokenization_script();

		return [ 'wc-ppcp-applepay-checkout' ];
	}

	public function get_express_checkout_script_handles() {
		$this->assets->register_script(
			'wc-ppcp-applepay-express',
			'build/js/applepay-express.js',
		);

		return [ 'wc-ppcp-applepay-express' ];
	}

	public function get_cart_script_handles() {
		$this->assets->register_script(
			'wc-ppcp-applepay-cart',
			'build/js/applepay-cart.js',
		);

		return [ 'wc-ppcp-applepay-cart' ];
	}

	public function get_product_script_handles() {
		$this->assets->register_script(
			'wc-ppcp-applepay-product',
			'build/js/applepay-product.js',
		);

		return [ 'wc-ppcp-applepay-product' ];
	}

	public function get_minicart_script_handles() {
		$this->assets->register_script(
			'wc-ppcp-applepay-minicart',
			'build/js/applepay-minicart.js'
		);

		return [ 'wc-ppcp-applepay-minicart' ];
	}

	public function express_checkout_fields() {
		?>
        <div id="wc-ppcp_applepay-express-button"></div>
		<?php
	}


	public function get_payment_method_data( $context ) {
		return [
			'button'               => [
				'style'  => $this->get_option( 'button_style', 'black' ),
				'type'   => $this->get_option( 'button_type', 'plain' ),
				'radius' => $this->get_option( 'button_radius', '4' ) . 'px',
				'height' => $this->get_option( 'button_height', '40' ) . 'px',
			],
			'sections'             => $this->get_option( 'sections', [] ),
			'display_name'         => $this->get_option( 'display_name', get_bloginfo( 'name' ) ),
			'html'                 => [
				'button' => $this->template_loader->load_template_html( 'applepay/button.php' )
			],
			'supported_currencies' => $this->get_supported_currencies(),
			'i18n'                 => [
				'total_label' => __( 'Total', 'pymntpl-paypal-woocommerce' ),
			]
		];
	}

	public function get_supported_currencies() {
		return apply_filters( 'wc_ppcp_applepay_supported_currencies', $this->supported_currencies, $this );
	}

	public function get_admin_script_data() {
		return [
			'i18n' => [
				'file_error' => __( 'There was an error adding your domain association file. Reason:', 'pymntpl-paypal-woocommerce' ),
				'processing' => __( 'Processing...', 'pymntpl-paypal-woocommerce' ),
			]
		];
	}

	public function generate_domain_file_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$data      = wp_parse_args( $data, [
			'title'       => '',
			'label'       => '',
			'description' => '',
			'desc_tip'    => false,
		] );
		ob_start();
		?>
        <tr valign="top" class="wc-ppcp-domain-association-setting">
            <th class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>">
					<?php echo wp_kses_post( $data['title'] ) ?>
					<?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </label></th>
            <td>
                <fieldset>
                    <button class="button-secondary button-domain-association"><?php echo wp_kses_post( $data['label'] ) ?></button>
					<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </fieldset>
            </td>
        </tr>
		<?php
		return ob_get_clean();
	}

	public function is_product_section_enabled( $product ) {
		$setting = new ProductSettings( $product );

		return \wc_string_to_bool( $setting->get_option( 'applepay_enabled', 'no' ) );
	}

	public function get_product_form_fields( $fields ) {
		return array_merge( $fields, [
			'applepay_enabled' => [
				'title'   => __( 'Apple Pay Enabled', 'pymntpl-paypal-woocommerce' ),
				'type'    => 'checkbox',
				'default' => in_array( 'product', (array) $this->get_option( 'sections', [] ) ) ? 'yes' : 'no'
			],
		] );
	}
}