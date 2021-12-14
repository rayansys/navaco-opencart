<?php

class ControllerExtensionPaymentnavaco extends Controller {
    private $urlService = "https://fcp.shaparak.ir/nvcservice/Api/v2/";
    public function index() {
        $this->load->language('extension/payment/navaco');

        $data1['text_connect'] = $this->language->get('text_connect');
        $data1['text_loading'] = $this->language->get('text_loading');
        $data1['text_wait'] = $this->language->get('text_wait');

        $data1['button_confirm'] = $this->language->get('button_confirm');

        return $this->load->view('extension/payment/navaco', $data1);
    }

    public function confirm() {
        $this->load->language('extension/payment/navaco');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $amount = $this->correctAmount($order_info);

        $data1['return'] = $this->url->link('checkout/success', '', true);
        $data1['cancel_return'] = $this->url->link('checkout/payment', '', true);
        $data1['back'] = $this->url->link('checkout/payment', '', true);

        $MerchantID = $this->config->get('payment_navaco_pin');  	//Required
        $username = $this->config->get('payment_navaco_username');  	//Required
        $password = $this->config->get('payment_navaco_password');  	//Required
        $Amount = $amount; 									//Amount will be based on Toman  - Required
        $data['order_id'] = $this->session->data['order_id'];
        //$data1['order_id'] = $this->encrypt($this->session->data['order_id']);
        $CallbackURL = $this->url->link('extension/payment/navaco/callback', 'order_id=' . $this->session->data['order_id'], true);  // Required

        $postField = [
            "CARDACCEPTORCODE"=>$MerchantID,
            "USERNAME"=>$username,
            "USERPASSWORD"=>$password,
            "PAYMENTID"=>$this->session->data['order_id'],
            "AMOUNT"=>$Amount,
            "CALLBACKURL"=>$CallbackURL,
        ];
        $result = $this->callCurl($postField,"PayRequest");

        if(!$result) {
            $json = array();
            $json['error']= $this->language->get('error_cant_connect');
        } elseif((int)$result->ActionCode == 0) {
            $data1['action'] = $result->RedirectUrl;
            $json['success']= $data1['action'];
        } else {
            $json = $this->checkState($result->ActionCode);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function callback() {
        if ($this->session->data['payment_method']['code'] == 'navaco') {
            $this->load->language('extension/payment/navaco');

            $this->document->setTitle($this->language->get('text_title'));

            $data1['heading_title'] = $this->language->get('text_title');
            $data1['results'] = "";

            $data1['breadcrumbs'] = array();
            $data1['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', '', true)
            );
            $data1['breadcrumbs'][] = array(
                'text' => $this->language->get('text_title'),
                'href' => $this->url->link('extension/payment/navaco/callback', '', true)
            );

            try {
                if (isset($this->session->data['order_id'])) {
                    $order_id = $this->session->data['order_id'];
                } else {
                    $order_id = 0;
                }

                $this->load->model('checkout/order');
                $order_info = $this->model_checkout_order->getOrder($order_id);

                if (!$order_info)
                    throw new Exception($this->language->get('error_order_id'));

                $dataRes = $this->request->post['Data'];
                
                $dataRes = str_replace("\\","",$dataRes);
                $dataRes = str_replace("\"{","{",$dataRes);
                $dataRes = str_replace("}\"","}",$dataRes);
                $dataRes = str_replace("&quot;",'"',$dataRes);
                $dataRes = json_decode($dataRes);

                $verifyResult = $this->verifyPayment($dataRes, $order_id);

                if (!$verifyResult)
                    throw new Exception($this->language->get('error_connect_verify'));

                switch ( array_keys($verifyResult)[0] ) {
                    case 'RefID': // success
                        $comment = $this->language->get('text_results') . $verifyResult['RefID'];
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_navaco_order_status_id'), $comment, true);

                        $data1['error_warning'] = NULL;
                        $data1['results'] = $verifyResult['RefID'];
                        $data1['button_continue'] = $this->language->get('button_complete');
                        $data1['continue'] = $this->url->link('checkout/success');
                        break;

                    case 'Status': // error with error status
                        throw new Exception($this->checkState($verifyResult["Status"])['error']);
                        break;
                }

            } catch (Exception $e) {
                $data1['error_warning'] = $e->getMessage();
                $data1['button_continue'] = $this->language->get('button_view_cart');
                $data1['continue'] = $this->url->link('checkout/cart');
            }

            $data1['column_left'] = $this->load->controller('common/column_left');
            $data1['column_right'] = $this->load->controller('common/column_right');
            $data1['content_top'] = $this->load->controller('common/content_top');
            $data1['content_bottom'] = $this->load->controller('common/content_bottom');
            $data1['footer'] = $this->load->controller('common/footer');
            $data1['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('extension/payment/navaco_confirm', $data1));
        }
    }

    private function correctAmount($order_info) {
        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        $amount = round($amount);
        $amount = $this->currency->convert($amount, $order_info['currency_code'], "TOM");
        return (int)$amount;
    }





    private function checkState($status) {
        $json = array();
        $json['error'] = $this->language->get('error_status_undefined');

        if ($this->language->get('error_status_' . $status) != 'error_status_' . $status ) {
            $json['error'] = $this->language->get('error_status_' . $status);
        }

        return $json;
    }



    private function verifyPayment($data, $orderId) {
        $MerchantID = $this->config->get('payment_navaco_pin');
        $username = $this->config->get('payment_navaco_username');
        $password = $this->config->get('payment_navaco_password');

        $postField = [
            "CARDACCEPTORCODE"=>$MerchantID,
            "USERNAME"=>$username,
            "USERPASSWORD"=>$password,
            "PAYMENTID"=>$orderId,
            "RRN"=>$data->RRN,
        ];
        $result = $this->callCurl($postField,"Confirm");
        if(!$result) {
            // echo  $this->language->get('error_cant_connect');
            return false;
        } elseif ((int)$result->ActionCode == 0) {
            return ['RefID' => $result->RRN];
        } else {
            return ['Status' => $result->ActionCode];
        }
    }
    private function callCurl($postField,$action){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->urlService.$action);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postField));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        $curl_exec = curl_exec($curl);
        $error = curl_errno($curl);
        curl_close($curl);

        return json_decode($curl_exec);
    }
}
?>
