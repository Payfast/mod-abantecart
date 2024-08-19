<?php
/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

if (!defined('DIR_CORE')) {
    header('Location: static_pages/');
}

$controllers = array(
    'storefront' => array(
        'pages/extension/payfast',
        'responses/extension/payfast',
        'blocks/payfast_button',
        'blocks/payfast_banner',
    ),
    'admin'      => array(
        'responses/extension/payfast',
    ),
);

$models = array(
    'storefront' => array(
        'extension/payfast',
    ),
    'admin'      => array(
        'extension/payfast',
    ),
);

$languages = array(
    'storefront' => array('payfast/payfast'),
    'admin'      => array('payfast/payfast')
);

$templates = array(
    'storefront' => array(
        'blocks/payfast_button.tpl',
        'blocks/payfast_cart_button.tpl',
        'responses/payfast.tpl',
        'responses/payfast_error.tpl',
        'blocks/payfast_banner_left.tpl',
        'blocks/payfast_banner_right.tpl',
        'blocks/payfast_banner_footer_top.tpl',
        'blocks/payfast_banner_footer.tpl',
        'blocks/payfast_banner_header_bottom.tpl',
    ),
    'admin'      => array(
        'pages/sale/payfast_details.tpl',
        'pages/extension/payfast_settings.tpl',
    )
);
