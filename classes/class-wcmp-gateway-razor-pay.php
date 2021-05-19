<?php

if (!defined('ABSPATH')) {
    exit;
}

use Razorpay\Api\Api;

class WCMp_Gateway_RazorPay extends WCMp_Payment_Gateway {

    public $id;
    public $message = array();
    private $test_mode = false;
    private $payout_mode = 'true';
    private $reciver_email;
    private $key_id;
    private $key_secret;
    private $api = '';

    public function __construct() {
        $this->id = 'razorpay';
        $this->enabled = get_wcmp_vendor_settings('payment_method_razorpay', 'payment');
        $this->key_id = get_wcmp_vendor_settings('key_id', 'payment', 'razorpay');
        $this->key_secret = get_wcmp_vendor_settings('key_secret', 'payment', 'razorpay');
        if (!empty($this->key_id) && !empty($this->key_secret) ) {
            $this->api = new Api($this->key_id, $this->key_secret);
        }
    }
    
    public function gateway_logo() { global $WCMp; return $WCMp->plugin_url . 'assets/images/'.$this->id.'.png'; }

    public function process_payment($vendor, $commissions = array(), $transaction_mode = 'auto', $transfer_args = array()) {
        $this->vendor = $vendor;
        $this->commissions = $commissions;
        $this->currency = get_woocommerce_currency();
        $this->transaction_mode = $transaction_mode;
        $this->reciver_email = wcmp_get_user_meta($this->vendor->id, '_vendor_razorpay_account_id', true);
        if ($this->validate_request()) {
            $paypal_response = $this->process_razorpay_payout();
            doProductVendorLOG(json_encode($paypal_response['error']));
            if ($paypal_response && isset($paypal_response['seccess']) && !empty($paypal_response['seccess'])) {
                $this->commissions = $paypal_response['seccess']['commission_id'];
                $this->record_transaction();
                if ($this->transaction_id) {
                    return array('message' => __('New transaction has been initiated', 'wcmp-razorpay-checkout-gateway'), 'type' => 'success', 'transaction_id' => $this->transaction_id);
                }
            } else {
                return false;
            }
        } else {
            return $this->message;
        }
    }

    public function validate_request() {
        global $WCMp;
        if ($this->enabled != 'Enable') {
            $this->message[] = array('message' => __('Invalid payment method', 'wcmp-razorpay-checkout-gateway'), 'type' => 'error');
            return false;
        } else if (!$this->key_id && !$this->key_secret) {
            $this->message[] = array('message' => __('Razorpay payout setting is not configured properly. Please contact site administrator', 'wcmp-razorpay-checkout-gateway'), 'type' => 'error');
            return false;
        } else if (!$this->reciver_email) {
            $this->message[] = array('message' => __('Please update your Razorpay Account information to receive commission', 'wcmp-razorpay-checkout-gateway'), 'type' => 'error');
            return false;
        }

        if ($this->transaction_mode != 'admin') {
            /* handel thesold time */
            $threshold_time = isset($WCMp->vendor_caps->payment_cap['commission_threshold_time']) && !empty($WCMp->vendor_caps->payment_cap['commission_threshold_time']) ? $WCMp->vendor_caps->payment_cap['commission_threshold_time'] : 0;
            if ($threshold_time > 0) {
                foreach ($this->commissions as $index => $commission) {
                    if (intval((date('U') - get_the_date('U', $commission)) / (3600 * 24)) < $threshold_time) {
                        unset($this->commissions[$index]);
                    }
                }
            }
            /* handel thesold amount */
            $thesold_amount = isset($WCMp->vendor_caps->payment_cap['commission_threshold']) && !empty($WCMp->vendor_caps->payment_cap['commission_threshold']) ? $WCMp->vendor_caps->payment_cap['commission_threshold'] : 0;
            if ($this->get_transaction_total() > $thesold_amount) {
                return true;
            } else {
                $this->message[] = array('message' => __('Minimum thesold amount to withdrawal commission is ' . $thesold_amount, 'wcmp-razorpay-checkout-gateway'), 'type' => 'error');
                return false;
            }
        }
        return parent::validate_request();
    }

    public function process_razorpay_payout() {
        $response = array();
        $response_success = array();
        if ($this->api && is_array($this->commissions)) {
            foreach ($this->commissions as $commission_id) {
                $commissionResponse = array();
                //check the order is payed with razor pay or not!!
                $vendor_order_id = wcmp_get_commission_order_id($commission_id);
                //get order details
                if ($vendor_order_id) {
                    $vendor_order = wc_get_order($vendor_order_id);
                    //check for valid vendor_order
                    if ($vendor_order) {
                        //get order payment mode
                        $paymentMode = $vendor_order->get_payment_method();
                        $orderStatus = $vendor_order->get_status();
                        $parent_order = wc_get_order($vendor_order->get_parent_id());
                        $order_transaction_id = $parent_order ? $parent_order->get_transaction_id() : 0;

                        //get commission amount to be transferred and commission note
                        $commission_amount = WCMp_Commission::commission_totals($commission_id, 'edit');
                        $transaction_total = (float) $commission_amount;
                        $amount_to_pay = round($transaction_total - ($this->transfer_charge($this->transaction_mode)/count($this->commissions)) - $this->gateway_charge(), 2);
                        $note = sprintf(__('Total commissions earned from %1$s as at %2$s on %3$s', 'wcmp-razorpay-checkout-gateway'), get_bloginfo('name'), date('H:i:s'), date('d-m-Y'));
                        $acceptedOrderStatus = apply_filters('wcmp_razorpay_payment_order_status', array('processing', 'on-hold', 'completed'));
                        //check payment mode
                        if ($paymentMode != 'razorpay') {
                            //payment method is not valid
                            $commissionResponse['message'] = "Order is not processed With Razorpay!"
                                . " Unable to Process #$vendor_order_id Order Commission!!";
                            $commissionResponse['type'] = 'error';
                        } elseif (!in_array($orderStatus, $acceptedOrderStatus)){
                            //order may not successfully paid unable to process the commission
                            $commissionResponse['message'] = "#$vendor_order_id is not paid properly or refunded!!"
                                . " Unable to Process #$commission_id Commission!!";
                            $commissionResponse['type'] = 'error';
                        } elseif ( empty($order_transaction_id) ) {
                            //unable to get the razor pay payment id
                            $commissionResponse['message'] = "Unable to get Transaction id of #$vendor_order_id order!!"
                                . " Unable to Process #$commission_id Commission!!";
                            $commissionResponse['type'] = 'error';
                        } elseif ( $amount_to_pay < 1 ) {
                            $commissionResponse['message'] = "Commission Amount is less than 1 !!"
                                . " Unable to Process #$commission_id Commission!!";
                            $commissionResponse['type'] = 'error';
                        } else {
                            $final_amount_to_oay = (float) ($amount_to_pay * 100);
                            //get payment details
                            try {
                                $transfer  = $this->api->payment->fetch($order_transaction_id)->transfer(array('transfers' => [ ['account' => $this->reciver_email, 'amount' => $final_amount_to_oay, 'currency' => $this->currency]]));
                                if ($transfer) {
                                    $response_success['success'] = 'success';
                                    $response_success['commission_id'][] = $commission_id;
                               }
                            } catch (Exception $e) {
                                //log gateway error
                                doProductVendorLOG('Razorpay Comission Payment Error!!'
                                    ."\n".$e->getCode().": ".$e->getMessage());
                                //set error message for the vendor_order id
                                $commissionResponse['message'] = "Something Went Wrong!"
                                    ." Unable to Process #$commission_id Commission!!";
                                $commissionResponse['type'] = "error";
                            }
                        }
                    } else {
                        //set error message for the vendor_order id
                        $commissionResponse['message'] = "Unable to get #$vendor_order_id Order Details!!";
                        $commissionResponse['type'] = "error";
                    }
                } else {
                    //set error message for the commission id
                    $commissionResponse['message'] = "Unable to get #$commission_id Commission Respective Order Id!!";
                    $commissionResponse['type'] = "error";
                }
                //set response
                $response['error'][] = $commissionResponse;
                $response['seccess'] = $response_success;
            }
        }
        return $response;
    }
}
