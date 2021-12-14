<?php
class ControllerExtensionPaymentnavaco extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/navaco');

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_navaco', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['pin'])) {
			$data['error_pin'] = $this->error['pin'];
		} else {
			$data['error_pin'] = '';
		}

		if (isset($this->error['username'])) {
			$data['error_username'] = $this->error['username'];
		} else {
			$data['error_username'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_username'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/navaco', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/navaco', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_navaco_pin'])) {
			$data['payment_navaco_pin'] = $this->request->post['payment_navaco_pin'];
		} else {
			$data['payment_navaco_pin'] = $this->config->get('payment_navaco_pin');
		}

		if (isset($this->request->post['payment_navaco_username'])) {
			$data['payment_navaco_username'] = $this->request->post['payment_navaco_username'];
		} else {
			$data['payment_navaco_username'] = $this->config->get('payment_navaco_username');
		}

		if (isset($this->request->post['payment_navaco_password'])) {
			$data['payment_navaco_password'] = $this->request->post['payment_navaco_password'];
		} else {
			$data['payment_navaco_password'] = $this->config->get('payment_navaco_password');
		}

		if (isset($this->request->post['payment_navaco_host_type'])) {
			$data['payment_navaco_host_type'] = $this->request->post['payment_navaco_host_type'];
		} else {
			$data['payment_navaco_host_type'] = $this->config->get('payment_navaco_host_type');
		}

		if (isset($this->request->post['payment_navaco_order_status_id'])) {
			$data['payment_navaco_order_status_id'] = $this->request->post['payment_navaco_order_status_id'];
		} else {
			$data['payment_navaco_order_status_id'] = $this->config->get('payment_navaco_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_navaco_status'])) {
			$data['payment_navaco_status'] = $this->request->post['payment_navaco_status'];
		} else {
			$data['payment_navaco_status'] = $this->config->get('payment_navaco_status');
		}

		if (isset($this->request->post['payment_navaco_sort_order'])) {
			$data['payment_navaco_sort_order'] = $this->request->post['payment_navaco_sort_order'];
		} else {
			$data['payment_navaco_sort_order'] = $this->config->get('payment_navaco_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/navaco', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/navaco')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['payment_navaco_pin']) {
			$this->error['pin'] = $this->language->get('error_pin');
		}

		if (!$this->request->post['payment_navaco_username']) {
			$this->error['username'] = $this->language->get('error_username');
		}

		if (!$this->request->post['payment_navaco_password']) {
			$this->error['password'] = $this->language->get('error_password');
		}

		return !$this->error;
	}
}
?>