<?php 
class ModelExtensionPaymentnavaco extends Model {
  	public function getMethod($address) {
		$this->load->language('extension/payment/navaco');

		if ($this->config->get('payment_navaco_status')) {
      		$status = true;
      	} else {
			$status = false;
		}

		$method_data = array();
		
		if ($status) {
      		$method_data = array( 
        		'code'       => 'navaco',
        		'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_navaco_sort_order')
      		);
    	}
		
    	return $method_data;
  	}
}
?>