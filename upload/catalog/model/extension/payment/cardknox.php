<?php
class ModelExtensionPaymentCardknox extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/cardknox');

		$status = true;



		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'cardknox',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_cardknox_sort_order')
			);
		}

		return $method_data;
	}
}