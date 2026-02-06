<?php

namespace PaymentPlugins\PPCP\Elementor\Widget;

use Elementor\Controls_Manager;

abstract class AbstractButtonWidget extends AbstractWidget {

    abstract protected function get_widget_page();

    abstract protected function is_supported_page();

    public function register_controls() {
        $this->register_general_settings();
        $this->register_paypal_button();
        $this->register_paylater_button();
        $this->register_card_button();
        $this->register_venmo_button();
        $this->register_googlepay_button();
    }

    protected function content_template() {
        ?>
        <# var ppcpSources = <?php echo json_encode( $this->payment_method_registry->get( 'ppcp' )->get_funding_types() ) ?>#>
        <div class="wc-ppcp-product-buttons-container">
            <#for(var i=0;i < ppcpSources.length;i++ ){#>
            <#if(settings['enable_' + ppcpSources[i]]){#>
            <div data-paypal-funding="{{ppcpSources[i]}}"></div>
            <#}#>
            <#}#>
            <#if(settings['enable_googlepay']){#>
            <div id="wc-ppcp-google-button"></div>
            <#}#>
        </div>
        <script>
            var settings = JSON.parse('{{{JSON.stringify(settings)}}}');

            function renderButtons() {
                var elements = document.querySelectorAll('.wc-ppcp-product-buttons-container [data-paypal-funding]');
                if (elements.length) {
                    elements.forEach(function (el, idx) {
                        var source = el.dataset.paypalFunding;
                        var options = {
                            fundingSource: source,
                            style: {
                                layout: 'vertical',
                                label: settings.button_label_paypal,
                                shape: settings.button_shape,
                                height: parseInt(settings.button_height),
                                color: settings['button_color_' + source]
                            }
                        }
                        if (source === 'venmo') {
                            delete options.style.label;
                            delete options.style.color;
                        }
                        var btn = paypal.Buttons(options);
                        if (btn.isEligible()) {
                            btn.render(el);
                        }
                    });
                }
            }

            function renderGooglePay() {
                if (!settings.enable_googlepay) {
                    return;
                }
                var paymentsClient = new google.payments.api.PaymentsClient({
                    environment: 'TEST'
                });
                var button = paymentsClient.createButton({
                    onClick: function () {
                    },
                    buttonBorderType: '<?php echo $this->get_gateway( 'ppcp_googlepay' )->get_option( 'button_border' ) ?>',
                    buttonColor: settings.button_color_googlepay,
                    buttonType: settings.button_type_googlepay,
                    buttonRadius: parseInt(<?php echo $this->get_gateway( 'ppcp_googlepay' )->get_option( 'button_radius', '4' ) ?>),
                    buttonSizeMode: settings.button_size_googlepay,
                });
                var container = document.getElementById('wc-ppcp-google-button');
                if (container) {
                    container.append(button);
                }
            }

            if (!window.paypal) {
                var script = document.createElement('script');
                script.src = '<?php echo esc_url( $this->get_paypal_editor_script() )?>';
                script.onload = function () {
                    renderButtons();
                };
                document.body.appendChild(script);
            } else {
                renderButtons();
            }
            if (!window.google) {
                var gpayScript = document.createElement('script');
                script.src = "https://pay.google.com/gp/p/js/pay.js";
                script.onload = function () {
                    renderGooglePay();
                };
                document.body.appendChild(gpayScript);
            } else {
                renderGooglePay();
            }
        </script>
        <?php
    }

    protected function register_general_settings() {
        $this->start_controls_section( 'ppcp_product_paypal_general', [
                'label' => __( 'General', 'pymntpl-paypal-woocommerce' )
        ] );
        $this->add_control( 'button_shape', [
                'label'   => __( 'Button Shape', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp' )->get_option( 'button_shape' ),
                'options' => $this->get_gateway( 'ppcp' )->get_form_fields()['button_shape']['options']
        ] );
        $this->add_control( 'button_height', [
                'label'      => __( 'Button Height', 'pymntpl-paypal-woocommerce' ),
                'type'       => Controls_Manager::NUMBER,
                'input_type' => 'number',
                'default'    => '40',
                'min'        => 25,
                'max'        => 55
        ] );
        $this->end_controls_section();
    }

    protected function register_paypal_button() {
        $this->start_controls_section( 'ppcp_paypal_button', [
                'label' => __( 'PayPal Button', 'pymntpl-paypal-woocommerce' )
        ] );
        $this->add_control( 'enable_paypal',
                [
                        'label'   => __( 'On', 'pymntpl-paypal-woocommerce' ),
                        'type'    => Controls_Manager::SWITCHER,
                        'default' => 'yes'
                ]
        );
        $this->add_control( 'button_label_paypal', [
                'label'   => __( 'Button Label', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp' )->get_option( 'button_label' ),
                'options' => $this->get_gateway( 'ppcp' )->get_form_fields()['button_label']['options']
        ] );
        $this->add_control( 'button_color_paypal', [
                'label'   => __( 'Button Color', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp' )->get_option( 'paypal_button_color' ),
                'options' => $this->get_gateway( 'ppcp' )->get_form_fields()['paypal_button_color']['options']
        ] );
        $this->end_controls_section();
    }

    protected function register_paylater_button() {
        $this->start_controls_section( 'ppcp_paylater_button', [
                'label' => __( 'PayLater Button', 'pymntpl-paypal-woocommerce' )
        ] );
        $this->add_control( 'enable_paylater',
                [
                        'label'   => __( 'On', 'pymntpl-paypal-woocommerce' ),
                        'type'    => Controls_Manager::SWITCHER,
                        'default' => 'yes'
                ]
        );
        $this->add_control( 'button_color_paylater', [
                'label'   => __( 'Button Color', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp' )->get_option( 'paylater_button_color' ),
                'options' => $this->get_gateway( 'ppcp' )->get_form_fields()['paylater_button_color']['options']
        ] );
        $this->end_controls_section();
    }

    protected function register_card_button() {
        $this->start_controls_section( 'ppcp_card_button', [
                'label' => __( 'Credit/Debit Card Button', 'pymntpl-paypal-woocommerce' )
        ] );
        $this->add_control( 'enable_card',
                [
                        'label'   => __( 'On', 'pymntpl-paypal-woocommerce' ),
                        'type'    => Controls_Manager::SWITCHER,
                        'default' => 'yes'
                ]
        );
        $this->add_control( 'button_color_card', [
                'label'   => __( 'Button Color', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp' )->get_option( 'card_button_color' ),
                'options' => $this->get_gateway( 'ppcp' )->get_form_fields()['card_button_color']['options']
        ] );
        $this->end_controls_section();
    }

    protected function register_venmo_button() {
        $this->start_controls_section( 'ppcp_venmo_button', [
                'label' => __( 'Venmo Button', 'pymntpl-paypal-woocommerce' )
        ] );
        $this->add_control( 'enable_venmo',
                [
                        'label'   => __( 'On', 'pymntpl-paypal-woocommerce' ),
                        'type'    => Controls_Manager::SWITCHER,
                        'default' => 'yes'
                ]
        );
        $this->end_controls_section();
    }

    protected function register_googlepay_button() {
        $this->start_controls_section( 'ppcp_googlepay_button', [
                'label' => __( 'Google Pay Button', 'pymntpl-paypal-woocommerce' )
        ] );
        $this->add_control( 'enable_googlepay',
                [
                        'label'   => __( 'On', 'pymntpl-paypal-woocommerce' ),
                        'type'    => Controls_Manager::SWITCHER,
                        'default' => 'yes'
                ]
        );
        $this->add_control( 'button_type_googlepay', [
                'label'   => __( 'Button Label', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp_googlepay' )->get_option( 'button_type' ),
                'options' => $this->get_gateway( 'ppcp_googlepay' )->get_form_fields()['button_type']['options']
        ] );
        $this->add_control( 'button_color_googlepay', [
                'label'   => __( 'Button Color', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp_googlepay' )->get_option( 'button_color' ),
                'options' => $this->get_gateway( 'ppcp_googlepay' )->get_form_fields()['button_color']['options']
        ] );
        $this->add_control( 'button_size_googlepay', [
                'label'   => __( 'Button Color', 'pymntpl-paypal-woocommerce' ),
                'type'    => Controls_Manager::SELECT,
                'default' => $this->get_gateway( 'ppcp_googlepay' )->get_option( 'button_size' ),
                'options' => $this->get_gateway( 'ppcp_googlepay' )->get_form_fields()['button_size']['options']
        ] );
        $this->end_controls_section();
    }

    protected function add_script_data() {
        add_action( 'wc_ppcp_add_script_data', function () {
            if ( $this->is_supported_page() && $this->is_frontend_request() ) {
                $settings = $this->get_settings_for_display();
                if ( $this->asset_data->exists( 'ppcp_data' ) ) {
                    $old_data            = $this->asset_data->get( 'ppcp_data' );
                    $old_data['funding'] = [];
                    foreach ( $this->get_gateway( 'ppcp' )->get_funding_types() as $type ) {
                        if ( \wc_string_to_bool( $settings["enable_{$type}"] ) ) {
                            $old_data['funding'][] = $type;
                            $key                   = $type . '_sections';
                            if ( $type === 'card' ) {
                                $key = 'credit_card_sections';
                            }
                            $old_data[ $key ]                       = array_merge( $old_data[ $key ], [ $this->get_widget_page() ] );
                            $old_data['buttons'][ $type ]['shape']  = $settings['button_shape'];
                            $old_data['buttons'][ $type ]['height'] = $settings['button_height'];
                        }
                    }
                    $old_data['buttons']['paypal']   = array_merge( $old_data['buttons']['paypal'], [
                            'label' => $settings['button_label_paypal'],
                            'color' => $settings['button_color_paypal']
                    ] );
                    $old_data['buttons']['paylater'] = array_merge( $old_data['buttons']['paylater'], [
                            'label' => $settings['button_label_paypal'],
                            'color' => $settings['button_color_paylater']
                    ] );
                    $old_data['buttons']['card']     = array_merge( $old_data['buttons']['card'], [
                            'label' => $settings['button_label_paypal'],
                            'color' => $settings['button_color_card']
                    ] );
                    $this->asset_data->add( 'ppcp_data', $old_data );
                }
                if ( $this->asset_data->exists( 'ppcp_googlepay_data' ) ) {
                    $data             = $this->asset_data->get( 'ppcp_googlepay_data' );
                    $data['disabled'] = $settings['enable_googlepay'] !== 'yes';
                    $data['button']   = array_merge(
                            $data['button'],
                            [
                                    'buttonColor'    => $settings['button_color_googlepay'],
                                    'buttonType'     => $settings['button_type_googlepay'],
                                    'buttonSizeMode' => $settings['button_size_googlepay'],
                            ]
                    );
                    $this->asset_data->add( 'ppcp_googlepay_data', $data );
                }
            }
        } );
    }

}