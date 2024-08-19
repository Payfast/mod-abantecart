<?php
/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

if (!defined('DIR_CORE')) {
    header('Location: static_pages/');
}
require_once __DIR__ . '/vendor/autoload.php';

use Payfast\PayfastCommon\PayfastCommon;

define("CHECKOUT_GUEST_STEP_2", 'checkout/guest_step_2');
define("CHECKOUT_GUEST_STEP_3", 'checkout/guest_step_3');
define("CHECKOUT_PAYMENT", 'checkout/payment');
$defaultSandboxCredentials = false;

class ControllerResponsesExtensionPayfast extends AController
{
    public $data = array();

    public function main()
    {
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['button_back']    = $this->language->get('button_back');

        if (!$this->config->get('payfast_test')) {
            $this->data['action'] = 'https://www.payfast.co.za/eng/process';
        } else {
            $this->data['action'] = 'https://sandbox.payfast.co.za/eng/process';
        }

        //solution for embed mode do submit to parent window
        if ($this->config->get('embed_mode')) {
            $this->data['target_parent'] = 'target="_parent"';
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($this->request->get['rt'] != 'checkout/guest_step_3') {
            $cancel_url = $this->html->getSecureURL(CHECKOUT_PAYMENT);
        } else {
            $cancel_url = $this->html->getSecureURL(CHECKOUT_GUEST_STEP_2);
        }

        $this->data['merchant_id']   = $this->config->get('payfast_merchant_id');
        $this->data['merchant_key']  = $this->config->get('payfast_merchant_key');
        $this->data['return_url']    = $this->html->getSecureURL('checkout/success');
        $this->data['cancel_url']    = $cancel_url;
        $this->data['notify_url']    = $this->html->getURL('extension/payfast/callback');
        $this->data['name_first']    = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
        $this->data['name_last']     = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
        $this->data['email_address'] = $order_info['email'];
        $this->data['m_payment_id']  = $this->session->data['order_id'];
        $this->data['amount']        = $this->currency->format(
            $order_info['total'],
            $order_info['currency'],
            $order_info['value'],
            false
        );
        $this->data['item_name']     = html_entity_decode($this->config->get('store_name'), ENT_QUOTES, 'UTF-8');

        $this->processTransaction($defaultSandboxCredentials, $order_info);
    }

    public function processTransaction($defaultSandboxCredentials, $order_info)
    {
        $pfOutput = '';
        // Create output string
        foreach ($this->data as $key => $value) {
            $pfOutput .= $key . '=' . urlencode(trim($value)) . '&';
        }

        $passPhrase = $this->config->get('payfast_passphrase');

        $pfOutput = $this->getPfOutput($passPhrase, $defaultSandboxCredentials, $pfOutput);

        $needle   = 'merchant_id';
        $pfString = strpos($pfOutput, $needle);
        $hashed   = substr($pfOutput, $pfString);

        $this->data['signature']  = md5($hashed);
        $this->data['user_agent'] = 'AbanteCart 1.2';

        $this->setLogo();

        $this->load->library('encryption');
        $encryption = new AEncryption($this->config->get('encryption_key'));

        $this->data['products'] = array();
        $products               = $this->cart->getProducts();
        foreach ($products as $product) {
            $option_data = array();

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $filename = $encryption->decrypt($option['value']);
                    $value    = mb_substr($filename, 0, mb_strrpos($filename, '.'));
                }

                $option_data[] = array(
                    'name'  => $option['name'],
                    'value' => (mb_strlen($value) > 20 ? mb_substr($value, 0, 20) . '..' : $value)
                );
            }

            $this->data['products'][] = array(
                'name'     => $product['name'],
                'model'    => $product['model'],
                'price'    => $this->currency->format(
                    $product['price'],
                    $order_info['currency'],
                    $order_info['value'],
                    false
                ),
                'quantity' => $product['quantity'],
                'option'   => $option_data,
                'weight'   => $product['weight']
            );
        }

        $this->checkAmount($order_info);

        $this->getTransactionType();

        $this->data['return'] = $this->html->getSecureURL('checkout/success');
        if ($this->request->get['rt'] != CHECKOUT_GUEST_STEP_3) {
            $this->data['cancel_return'] = $this->html->getSecureURL(CHECKOUT_PAYMENT);
        } else {
            $this->data['cancel_return'] = $this->html->getSecureURL(CHECKOUT_GUEST_STEP_2);
        }


        $this->data['custom'] = $encryption->encrypt($this->session->data['order_id']);

        if ($this->request->get['rt'] != CHECKOUT_GUEST_STEP_3) {
            $this->data['back'] = $this->html->getSecureURL(CHECKOUT_PAYMENT);
        } else {
            $this->data['back'] = $this->html->getSecureURL(CHECKOUT_GUEST_STEP_2);
        }

        $back                         = $this->request->get['rt'] != CHECKOUT_GUEST_STEP_3
            ? $this->html->getSecureURL(CHECKOUT_PAYMENT)
            : $this->html->getSecureURL(CHECKOUT_GUEST_STEP_2);
        $this->data['back']           = HtmlElementFactory::create(array(
                                                                       'type'  => 'button',
                                                                       'name'  => 'back',
                                                                       'text'  => $this->language->get('button_back'),
                                                                       'style' => 'button',
                                                                       'href'  => $back
                                                                   ));
        $this->data['button_confirm'] = HtmlElementFactory::create(
            array(
                'type'  => 'submit',
                'name'  => $this->language->get('button_confirm'),
                'style' => 'button',
            )
        );

        $this->view->batchAssign($this->data);
        $this->processTemplate('responses/payfast.tpl');
    }

    public function checkAmount($order_info)
    {
        $this->data['discount_amount_cart'] = 0;
        $totals                             = $this->cart->buildTotalDisplay();

        foreach ($totals['total_data'] as $total) {
            if (in_array($total['id'], array('subtotal', 'total'))) {
                continue;
            }
            if (in_array($total['id'], array('promotion', 'coupon', 'balance'))) {
                $total['value']                     = $total['value'] < 0 ? $total['value'] * -1 : $total['value'];
                $this->data['discount_amount_cart'] += $this->currency->format(
                    $total['value'],
                    $order_info['currency'],
                    $order_info['value'],
                    false
                );
            } else {
                $this->data['products'][] = array(
                    'name'     => $total['title'],
                    'model'    => '',
                    'price'    => $this->currency->format(
                        $total['value'],
                        $order_info['currency'],
                        $order_info['value'],
                        false
                    ),
                    'quantity' => 1,
                    'option'   => array(),
                    'weight'   => 0
                );
            }
        }
    }

    public function callback()
    {
        $debugLogMode  = $this->config->get('payfast_debug_mode') == 1 ? true : false;
        $payfastCommon = new PayfastCommon($debugLogMode);

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($_POST['m_payment_id']);

        $pfError       = false;
        $pfErrMsg      = '';
        $pfDone        = false;
        $pfData        = array();
        $pfParamString = '';

        $pfHost = $this->getHost();

        $payfastCommon->pflog('Payfast ITN call received');

        // Notify Payfast that information has been received
        $this->notifyPF($pfError, $pfDone);

        // Get data sent by Payfast
        if (!$pfError && !$pfDone) {
            $payfastCommon->pflog('Get posted data');

            // Posted variables from ITN
            $pfData = $payfastCommon->pfGetData();

            $payfastCommon->pflog('Payfast Data: ' . print_r($pfData, true));

            if ($pfData === false) {
                $pfError  = true;
                $pfErrMsg = $payfastCommon::PF_ERR_BAD_ACCESS;
            }
        }

        // Verify security signature
        if (!$pfError && !$pfDone) {
            $payfastCommon->pflog('Verify security signature');

            $passPhrase   = $this->config->get('payfast_passphrase');
            $pfPassPhrase = empty($passPhrase) ? null : $passPhrase;

            // If signature different, log for debugging
            if (!$payfastCommon->pfValidSignature($pfData, $pfParamString, $pfPassPhrase)) {
                $pfError         = true;
                $this->$pfErrMsg = $payfastCommon::PF_ERR_INVALID_SIGNATURE;
            }
        }

        // Verify data received
        if (!$pfError) {
            $payfastCommon->pflog('Verify data received');

            $moduleInfo = [
                "pfSoftwareName"       => 'AbanteCart',
                "pfSoftwareVer"        => '1.3.x',
                "pfSoftwareModuleName" => 'Payfast-AbanteCart',
                "pfModuleVer"          => '1.1.0',
            ];


            $pfValid = $payfastCommon->pfValidData($moduleInfo, $pfHost, $pfParamString);

            if (!$pfValid) {
                $pfError  = true;
                $pfErrMsg = $payfastCommon::PF_ERR_BAD_ACCESS;
            }
        }

        // Check amounts
        if (!$pfError && !$pfDone) {
            $payfastCommon->pflog('Check amounts');
            $total_order_amount = $order_info['total'] * $order_info['value'];
            if ($_POST['amount_gross'] != round($total_order_amount)) {
                $pfError  = true;
                $pfErrMsg = $payfastCommon::PF_ERR_AMOUNT_MISMATCH;
            }
        }

        $this->updateOrderStatus($pfError, $pfDone, $payfastCommon, $pfData);

        $this->ifErrorOccured($pfError, $payfastCommon, $pfErrMsg);

        $this->model_checkout_order->update($pfData['m_payment_id'], $this->config->get('payfast_order_status_id'));
        $payfastCommon->pflog('Order Status: Complete!');
    }

    /**
     * @return void
     */
    public function setLogo(): void
    {
        if (has_value($this->config->get('payfast_logo'))) {
            if (strpos($this->config->get('payfast_logo'), 'http') === 0) {
                $this->data['logoimg'] = $this->config->get('payfast_logo');
            } else {
                $this->data['logoimg'] = HTTPS_SERVER . 'resources/' . $this->config->get('payfast_logo');
            }
        }
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        if (!$this->config->get('payfast_test')) {
            $pfHost = 'www.payfast.co.za';
        } else {
            $pfHost = 'sandbox.payfast.co.za';
        }

        return $pfHost;
    }

    /**
     * @param bool $pfError
     * @param bool $pfDone
     *
     * @return void
     */
    public function notifyPF(bool $pfError, bool $pfDone): void
    {
        if (!$pfError && !$pfDone) {
            header('HTTP/1.0 200 OK');
            flush();
        }
    }

    /**
     * @param bool $pfError
     * @param bool $pfDone
     * @param PayfastCommon $payfastCommon
     * @param mixed $pfData
     *
     * @return void
     */
    public function updateOrderStatus(bool $pfError, bool $pfDone, PayfastCommon $payfastCommon, mixed $pfData): void
    {
        if (!$pfError && !$pfDone) {
            $payfastCommon->pflog('Check Status and Update Order');

            if ($pfData['payment_status'] == 'COMPLETE') {
                $this->model_checkout_order->confirm(
                    $pfData['m_payment_id'],
                    $this->config->get('payfast_order_status_id')
                );
            } else {
                $this->model_checkout_order->confirm(
                    $pfData['m_payment_id'],
                    $this->config->get('config_order_status_id')
                );
            }
        }
    }

    /**
     * @param bool $pfError
     * @param PayfastCommon $payfastCommon
     * @param string $pfErrMsg
     *
     * @return void
     */
    public function ifErrorOccured(bool $pfError, PayfastCommon $payfastCommon, string $pfErrMsg): void
    {
        if ($pfError) {
            $payfastCommon->pflog('Error occurred: ' . $pfErrMsg);
        }
    }

    /**
     * @return void
     */
    public function getTransactionType(): void
    {
        if (!$this->config->get('payfast_transaction')) {
            $this->data['paymentaction'] = 'authorization';
        } else {
            $this->data['paymentaction'] = 'sale';
        }
    }

    /**
     * @param $passPhrase
     * @param $defaultSandboxCredentials
     * @param string $pfOutput
     *
     * @return string
     */
    public function getPfOutput($passPhrase, $defaultSandboxCredentials, string $pfOutput): string
    {
        if (empty($passPhrase) || $defaultSandboxCredentials) {
            $pfOutput = substr($pfOutput, 0, -1);
        } else {
            $pfOutput = $pfOutput . "passphrase=" . urlencode($passPhrase);
        }

        return $pfOutput;
    }

}
