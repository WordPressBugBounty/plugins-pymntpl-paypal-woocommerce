<?php

namespace PaymentPlugins\WooCommerce\PPCP\Shortcodes;

use PaymentPlugins\WooCommerce\PPCP\ContextHandler;

class CartPayLaterMessage extends AbstractPayLaterMessage {

    public $id = 'ppcp_cart_message';

    protected $style_key = 'cart';

    public function is_supported_page( ContextHandler $context ) {
        return $context->is_cart();
    }

    public function render() {
        ?>
        <div class="wc-ppcp-paylater-msg__container" style="display: none">
            <div id="wc-ppcp-paylater-msg-cart"></div>
        </div>
        <?php
    }

}