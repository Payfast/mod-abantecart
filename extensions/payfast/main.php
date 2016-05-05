<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2015 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  Lincence details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
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