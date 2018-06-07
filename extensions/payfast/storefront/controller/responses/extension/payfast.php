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

define( 'SANDBOX_MERCHANT_ID' , '10000100' );
define( 'SANDBOX_MERCHANT_KEY' , '46f0cd694581a' );
$defaultSandboxCredentials = false;

class ControllerResponsesExtensionPayFast extends AController
{
    public $data = array();
    public function main() 
    {
        $this->data['button_confirm'] = $this->language->get( 'button_confirm' );
        $this->data['button_back'] = $this->language->get( 'button_back' );

        if ( !$this->config->get( 'payfast_test' ) )
        {
            $this->data['action'] = 'https://www.payfast.co.za/eng/process';
        }
        else
        {
            $this->data['action'] = 'https://sandbox.payfast.co.za/eng/process';
        }

        //solution for embed mode do submit to parent window
        if( $this->config->get( 'embed_mode' ) )
        {
            $this->data['target_parent'] = 'target="_parent"';
        }

        $this->load->model( 'checkout/order' );
        $order_info = $this->model_checkout_order->getOrder( $this->session->data['order_id'] );

        if ( $this->request->get['rt'] != 'checkout/guest_step_3' )
        {
            $cancel_url = $this->html->getSecureURL( 'checkout/payment' );
        }
        else
        {
            $cancel_url = $this->html->getSecureURL( 'checkout/guest_step_2' );
        }

        //checks to see whether the default sandbox credentials should be used or not
        if ( !$this->config->get( 'payfast_test' ) )
        {
            $this->data['merchant_id'] = $this->config->get( 'payfast_merchant_id' );
            $this->data['merchant_key'] = $this->config->get( 'payfast_merchant_key' );
        }
        else
        {
            if ( empty( $this->config->get( 'payfast_merchant_id' ) ) || empty( $this->config->get( 'payfast_merchant_key' ) ) )
            {
                $this->data['merchant_id'] = SANDBOX_MERCHANT_ID;
                $this->data['merchant_key'] = SANDBOX_MERCHANT_KEY;
                $defaultSandboxCredentials = true;
            }
            else
            {
                $this->data['merchant_id'] = $this->config->get( 'payfast_merchant_id' );
                $this->data['merchant_key'] = $this->config->get( 'payfast_merchant_key' );
            }
        }
        $this->data['return_url'] = $this->html->getSecureURL( 'checkout/success' );
        $this->data['cancel_url'] = $cancel_url;
        $this->data['notify_url'] = $this->html->getURL( 'extension/payfast/callback' );
        $this->data['name_first'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8' );
        $this->data['name_last'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8' );
        $this->data['email_address'] = $order_info['email'];
        $this->data['m_payment_id'] = $this->session->data['order_id'];
        $this->data['amount'] = $this->currency->format( $order_info['total'], $order_info['currency'], $order_info['value'], FALSE );
        $this->data['item_name'] = html_entity_decode($this->config->get( 'store_name' ), ENT_QUOTES, 'UTF-8' );

        $pfOutput = '';
        // Create output string
        foreach( $this->data as $key => $value )
        {
            $pfOutput .= $key . '=' . urlencode( trim( $value ) ) . '&';
        }

        $passPhrase = $this->config->get( 'payfast_passphrase' );

        if ( empty( $passPhrase ) || $defaultSandboxCredentials )
        {
            $pfOutput = substr( $pfOutput, 0, -1 );
        }
        else
        {
            $pfOutput = $pfOutput."passphrase=".urlencode( $passPhrase );
        }

        $needle = 'merchant_id';
        $pfString = strpos( $pfOutput, $needle );
        $hashed = substr ( $pfOutput, $pfString );

        $this->data['signature'] = md5( $hashed );
        $this->data['user_agent'] = 'AbanteCart 1.2';

        if ( has_value( $this->config->get( 'payfast_logo' ) ) )
        {
            if ( strpos( $this->config->get( 'payfast_logo' ), 'http' ) === 0 )
            {
                $this->data['logoimg'] = $this->config->get( 'payfast_logo' );
            }
            else
            {
                $this->data['logoimg'] = HTTPS_SERVER . 'resources/'.$this->config->get( 'payfast_logo' );
            }
        }

        $this->load->library( 'encryption' );
        $encryption = new AEncryption( $this->config->get( 'encryption_key' ) );

        $this->data['products'] = array();
        $products = $this->cart->getProducts();
        foreach ( $products as $product )
        {
            $option_data = array();

            foreach ( $product['option'] as $option )
            {
                if ( $option['type'] != 'file' )
                {
                    $value = $option['value'];
                }
                else
                {
                    $filename = $encryption->decrypt( $option['value'] );
                    $value = mb_substr( $filename, 0, mb_strrpos( $filename, '.' ) );
                }

                $option_data[] = array(
                    'name'  => $option['name'],
                    'value' => ( mb_strlen( $value ) > 20 ? mb_substr( $value, 0, 20 ) . '..' : $value )
                );
            }

            $this->data['products'][] = array(
                'name'     => $product['name'],
                'model'    => $product['model'],
                'price'    => $this->currency->format($product['price'], $order_info['currency'], $order_info['value'], FALSE ),
                'quantity' => $product['quantity'],
                'option'   => $option_data,
                'weight'   => $product['weight']
            );
        }


        $this->data['discount_amount_cart'] = 0;
        $totals = $this->cart->buildTotalDisplay();

        foreach( $totals['total_data'] as $total )
        {
            if( in_array( $total['id'],array( 'subtotal','total' ) ) )
            {
                continue;
            }
            if( in_array( $total['id'],array( 'promotion','coupon', 'balance' ) ) )
            {
                $total['value'] = $total['value']<0 ? $total['value']*-1 : $total['value'];
                $this->data['discount_amount_cart'] += $this->currency->format($total['value'], $order_info['currency'], $order_info['value'], FALSE );
            }
            else
            {
                $this->data['products'][] = array(
                    'name'     => $total['title'],
                    'model'    => '',
                    'price'    => $this->currency->format($total['value'], $order_info['currency'], $order_info['value'], FALSE ),
                    'quantity' => 1,
                    'option'   => array(),
                    'weight'   => 0
                );
            }
        }


        if  ( !$this->config->get( 'payfast_transaction' ) )
        {
            $this->data['paymentaction'] = 'authorization';
        }
        else
        {
            $this->data['paymentaction'] = 'sale';
        }

        $this->data['return'] = $this->html->getSecureURL( 'checkout/success' );

        if ( $this->request->get['rt'] != 'checkout/guest_step_3' )
        {
            $this->data['cancel_return'] = $this->html->getSecureURL( 'checkout/payment' );
        }
        else
        {
            $this->data['cancel_return'] = $this->html->getSecureURL( 'checkout/guest_step_2' );
        }


        $this->data['custom'] = $encryption->encrypt( $this->session->data['order_id'] );

        if ( $this->request->get['rt'] != 'checkout/guest_step_3' )
        {
            $this->data['back'] = $this->html->getSecureURL( 'checkout/payment' );
        }
        else
        {
            $this->data['back'] = $this->html->getSecureURL( 'checkout/guest_step_2' );
        }

        $back = $this->request->get[ 'rt' ] != 'checkout/guest_step_3'
            ? $this->html->getSecureURL( 'checkout/payment' )
            : $this->html->getSecureURL( 'checkout/guest_step_2' );
        $this->data[ 'back' ] = HtmlElementFactory::create( array( 'type' => 'button',
            'name' => 'back',
            'text' => $this->language->get( 'button_back' ),
            'style' => 'button',
            'href' => $back ) );
        $this->data[ 'button_confirm' ] = HtmlElementFactory::create(
            array( 'type' => 'submit',
                'name' => $this->language->get( 'button_confirm' ),
                'style' => 'button',
            ) );

        $this->view->batchAssign( $this->data );
        $this->processTemplate( 'responses/payfast.tpl' );
    }

    public function callback()
    {
        require_once( 'payfast_common.inc' );

        $this->load->model( 'checkout/order' );
        $order_info = $this->model_checkout_order->getOrder( $_POST['m_payment_id'] );

        $pfError = false;
        $pfErrMsg = '';
        $pfDone = false;
        $pfData = array();
        $pfParamString = '';

        if ( !$this->config->get( 'payfast_test' ) )
        {
            $pfHost = 'https://www.payfast.co.za';
        }
        else
        {
            $pfHost = 'https://sandbox.payfast.co.za';
        }

        pflog( 'PayFast ITN call received' );

//// Notify PayFast that information has been received
        if( !$pfError && !$pfDone )
        {
            header( 'HTTP/1.0 200 OK' );
            flush();
        }

//// Get data sent by PayFast
        if( !$pfError && !$pfDone )
        {
            pflog( 'Get posted data' );

            // Posted variables from ITN
            $pfData = pfGetData();

            pflog( 'PayFast Data: '. print_r( $pfData, true ) );

            if( $pfData === false )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

//// Verify security signature
        if( !$pfError && !$pfDone )
        {
            pflog( 'Verify security signature' );

            $passPhrase = $this->config->get( 'payfast_passphrase' );;
            $pfPassPhrase = empty( $passPhrase ) ? null : $passPhrase;

            // If signature different, log for debugging
            if( !pfValidSignature( $pfData, $pfParamString, $pfPassPhrase ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
            }
        }

//// Verify source IP (If not in debug mode)
        if( !$pfError && !$pfDone )
        {
            pflog( 'Verify source IP' );

            if( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
            }
        }

//// Verify data received
        if( !$pfError )
        {
            pflog( 'Verify data received' );

            $pfValid = pfValidData( $pfHost, $pfParamString );

//            if( !$pfValid )
//            {
//                $pfError = true;
//                $pfErrMsg = PF_ERR_BAD_ACCESS;
//            }
        }

//// Check amounts
        if( !$pfError && !$pfDone )
        {
            pflog( 'Check amounts');

            if( $_POST['amount_gross'] != $order_info['total'] )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
            }
        }



        if ( !$pfError && !$pfDone )
        {
            pflog( 'Check Status and Update Order' );

            if ( $pfData['payment_status'] == 'COMPLETE' )
            {
                $this->model_checkout_order->confirm($pfData['m_payment_id'], $this->config->get( 'payfast_order_status_id' ) );
            }
            else
            {
                $this->model_checkout_order->confirm($pfData['m_payment_id'], $this->config->get( 'config_order_status_id' ) );
            }
        }

        if( $pfError )
        {
            pflog( 'Error occurred: '. $pfErrMsg );
        }

        $this->model_checkout_order->updatePaymentMethodData( $this->session->data['order_id'], $response );

    }
}
