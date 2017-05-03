<?php
/*------------------------------------------------------------------------------
Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
------------------------------------------------------------------------------*/

if ( !defined ( 'DIR_CORE' ) )
{
    header ( 'Location: static_pages/' );
}

if( !class_exists( 'ExtensionDefaultPayFast') )
{
    include_once('core/payfast.php');
}

$controllers = array(
    'storefront' => array(
        'pages/extension/payfast',
        'responses/extension/payfast',
        'blocks/payfast_button',
        'blocks/payfast_banner',
    ),
    'admin' => array(
        'responses/extension/payfast',
    ),
);

$models = array(
    'storefront' => array(
        'extension/payfast',
    ),
    'admin' => array(
        'extension/payfast',
    ),
);

$languages = array(
    'storefront' => array( 'payfast/payfast' ),
    'admin' => array( 'payfast/payfast' )
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
    'admin' => array(
        'pages/sale/payfast_details.tpl',
        'pages/extension/payfast_settings.tpl',
    )
);