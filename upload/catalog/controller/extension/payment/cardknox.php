<?php
class ControllerExtensionPaymentCardknox extends Controller {
	public function index() {

		$this->load->language('extension/payment/cardknox');
		$data['months'] = array();

		for ($i = 1; $i <= 12; $i++) {
			$data['months'][] = array(
				'text'  => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['year_expire'] = array();

		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$data['year_expire'][] = array(
				'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
				'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
			);
		}
		$log = new Log('LOG_NAME.log');
		$this->log->write('TEXT = ');
		$data['cardknox_token_key'] = $this->config->get('payment_cardknox_token_key');
		// $this->document->addScript('https://cdn.cardknox.com/ifields/ifields.min.js');
		return $this->load->view('extension/payment/cardknox', $data);
	}

	public function send() {
		$url = 'https://x1.cardknox.com/gateway';

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data = array();

		$data['xKey'] = $this->config->get('payment_cardknox_transaction_key');
		$data['xVersion'] = '4.5.5';
		$data['xSoftwareName'] = 'OpenCart';
		$data['xSoftwareVersion'] = '1.0.1';

		$data['xBillFirstname'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
		$data['xBillLastname'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
		$data['xBillCompany'] = html_entity_decode($order_info['payment_company'], ENT_QUOTES, 'UTF-8');
		$data['xBillStreet'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
		$data['xBillCity'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
		$data['xBillState'] = html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8');
		$data['xBillZip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
		$data['xBillCountry'] = html_entity_decode($order_info['payment_country'], ENT_QUOTES, 'UTF-8');
		$data['xBillPhone'] = $order_info['telephone'];
		$data['xIP'] = $this->request->server['REMOTE_ADDR'];
		$data['xEmail'] = $order_info['email'];
		$data['xDescription'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$data['xAmount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, false);
		$data['xCurrency'] = $this->session->data['currency'];
		$data['xCommand'] = ($this->config->get('payment_cardknox_method') == 'capture') ? 'cc:sale' : 'cc:authonly';
		$data['xCardNum'] = $this->request->post['xCardNum'];
		$data['xExp'] = $this->request->post['cc_expire_date_month'] . substr($this->request->post['cc_expire_date_year'],2,2);
		$data['xCVV'] = $this->request->post['xCVV'];
		$data['xInvoice'] = $this->session->data['order_id'];

		/* Customer Shipping Address Fields */
		if ($order_info['shipping_method']) {
			$data['xShipFirstname'] = html_entity_decode($order_info['shipping_firstname'], ENT_QUOTES, 'UTF-8');
			$data['xShipLastname'] = html_entity_decode($order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8');
			$data['xShipCompany'] = html_entity_decode($order_info['shipping_company'], ENT_QUOTES, 'UTF-8');
			$data['xShipStreet'] = html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order_info['shipping_address_2'], ENT_QUOTES, 'UTF-8');
			$data['xShipCity'] = html_entity_decode($order_info['shipping_city'], ENT_QUOTES, 'UTF-8');
			$data['xShipState'] = html_entity_decode($order_info['shipping_zone'], ENT_QUOTES, 'UTF-8');
			$data['xShipZip'] = html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8');
			$data['xShipCountry'] = html_entity_decode($order_info['shipping_country'], ENT_QUOTES, 'UTF-8');
		} else {
			$data['xShipFirstname'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
			$data['xShipLastname'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
			$data['xShipCompany'] = html_entity_decode($order_info['payment_company'], ENT_QUOTES, 'UTF-8');
			$data['xShipStreet'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');
			$data['xShipCity'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
			$data['xShipState'] = html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8');
			$data['xShipZip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
			$data['xShipCountry'] = html_entity_decode($order_info['payment_country'], ENT_QUOTES, 'UTF-8');
		}
		// var_dump($data);
		$this->log->write($data);
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));

		$response = curl_exec($curl);
		$json = array();

		if (curl_error($curl)) {
			$json['error'] = 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);

			$this->log->write('CARDKNOX CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));
		} elseif ($response) {
			$i = 1;

			$response_info = array();

			parse_str($response, $response_info);



			if (empty( $response_info['xError']) ){
				$message = '';

				if (isset($response_info['xAuthCode'])) {
					$message .= 'Authorization Code: ' . $response_info['xAuthCode'] . "\n";
				}

				if (isset($response_info['xAvsResult'])) {
					$message .= 'AVS Response: ' . $response_info['xAvsResult'] . "\n";
				}

				if (isset($response_info['xRefNum'])) {
					$message .= 'Reference Number: ' . $response_info['xRefNum'] . "\n";
				}

				if (isset($response_info['xCVVResult'])) {
					$message .= 'Card Code Response: ' . $response_info['xCVVResponse'] . "\n";
				}
				$comment = $response_info['xRefNum'] . " " . $response_info['xStatus'];
				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_cardknox_order_status_id'), $comment, true);
				$json['redirect'] = $this->url->link('checkout/success', '', true);
			} else {
				$json['error'] = $response_info['xError'];
			}
		} else {
			$json['error'] = 'Empty Gateway Response';

			$this->log->write('Cardknox CURL ERROR: Empty Gateway Response');
		}

		curl_close($curl);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
?>