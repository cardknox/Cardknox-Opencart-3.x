<?php
class ControllerExtensionPaymentCardknox extends Controller {
	const DEFAULT_GATEWAY_URL = 'https://x1.cardknox.com/gateway';
	const ERROR_LOG_MAX_LENGTH = 255;
	const CONNECT_TIMEOUT = 10;
	const RESPONSE_TIMEOUT = 45;

	/* Masked before anything reaches cardknox.log. The key, card number and CVV
	   were already masked; the personal data alongside them was not. Expiry date
	   is deliberately not masked. */
	private $redacted_fields = array(
		'xkey', 'xcardnum', 'xcvv', 'xtoken',
		'xbillfirstname', 'xbilllastname', 'xbillcompany', 'xbillstreet', 'xbillstreet2',
		'xbillcity', 'xbillstate', 'xbillzip', 'xbillphone',
		'xshipfirstname', 'xshiplastname', 'xshipcompany', 'xshipstreet', 'xshipstreet2',
		'xshipcity', 'xshipstate', 'xshipzip',
		'xemail', 'xip', 'xname', 'xcardholdername'
	);

	public function index() {

		$this->load->language('extension/payment/cardknox');
		$data['months'] = array();

		for ($i = 1; $i <= 12; $i++) {
			$data['months'][] = array(
				'text'  => date('F', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['year_expire'] = array();

		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$data['year_expire'][] = array(
				'text'  => (string)$i,
				'value' => (string)$i
			);
		}
		$data['cardknox_token_key'] = $this->config->get('payment_cardknox_token_key');
		return $this->load->view('extension/payment/cardknox', $data);
	}

	public function send() {
		$this->load->language('extension/payment/cardknox');
		$this->load->model('checkout/order');

		$json = array();

		if (empty($this->session->data['order_id'])) {
			$json['error'] = $this->language->get('error_order');

			$this->respond($json);
			return;
		}

		$order_id = $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			$json['error'] = $this->language->get('error_order');

			$this->respond($json);
			return;
		}

		/* An order that already carries a status has been through the gateway.
		   Without this, a refresh, a double-click or a replayed POST charges again. */
		if ($order_info['order_status_id']) {
			$json['error'] = $this->language->get('error_order_paid');

			$this->respond($json);
			return;
		}

		$url = $this->getGatewayUrl();

		$data = array();

		$data['xKey'] = $this->config->get('payment_cardknox_transaction_key');
		$data['xVersion'] = '4.5.8';
		$data['xSoftwareName'] = 'OpenCart';
		$data['xSoftwareVersion'] = '1.1.0';

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

		/* The amount and the currency label have to describe the same currency.
		   The rate was previously hardcoded to 1.00000 while the label was the
		   shopper's selected currency, so multi-currency stores sent a
		   base-currency figure tagged as something else. */
		$data['xAmount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['xCurrency'] = $order_info['currency_code'];

		$data['xCommand'] = ($this->config->get('payment_cardknox_method') == 'capture') ? 'cc:sale' : 'cc:authonly';
		$data['xCardNum'] = isset($this->request->post['xCardNum']) ? $this->request->post['xCardNum'] : '';
		$exp_month = isset($this->request->post['cc_expire_date_month']) ? $this->request->post['cc_expire_date_month'] : '';
		$exp_year = isset($this->request->post['cc_expire_date_year']) ? $this->request->post['cc_expire_date_year'] : '';
		$data['xExp'] = $exp_month . substr($exp_year, 2, 2);
		$data['xCVV'] = isset($this->request->post['xCVV']) ? $this->request->post['xCVV'] : '';
		$data['xInvoice'] = $order_id;
		$data['xAllowDuplicate'] = 'FALSE';

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

		$this->logger($data);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
		curl_setopt($curl, CURLOPT_TIMEOUT, self::RESPONSE_TIMEOUT);

		$apiResponse = curl_exec($curl);

		if (curl_error($curl)) {
			$json['error'] = 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);

			$this->logger('CARDKNOX CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));
		} elseif ($apiResponse) {
			$response_info = array();
			parse_str($apiResponse, $response_info);
			$this->logger($response_info);

			if ($this->responseValue($response_info, 'xResult') == 'A') {
				$message = '';

				if ($this->responseValue($response_info, 'xAuthCode') !== '') {
					$message .= 'Authorization Code: ' . $this->responseValue($response_info, 'xAuthCode') . "\n";
				}

				if ($this->responseValue($response_info, 'xAvsResult') !== '') {
					$message .= 'AVS Response: ' . $this->responseValue($response_info, 'xAvsResult') . "\n";
				}

				if ($this->responseValue($response_info, 'xRefNum') !== '') {
					$message .= 'Reference Number: ' . $this->responseValue($response_info, 'xRefNum') . "\n";
				}

				if ($this->responseValue($response_info, 'xCvvResult') !== '') {
					$message .= 'Card Code Response: ' . $this->responseValue($response_info, 'xCvvResult') . "\n";
				}

				if ($this->responseValue($response_info, 'xStatus') !== '') {
					$message .= 'Status: ' . $this->responseValue($response_info, 'xStatus') . "\n";
				}

				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_cardknox_order_status_id'), $message, true);
				$json['redirect'] = $this->url->link('checkout/success', '', true);
			} else {
				$error = $this->responseValue($response_info, 'xError');
				$json['error'] = ($error !== '') ? $error : $this->language->get('error_gateway');
			}
		} else {
			$json['error'] = 'Empty Gateway Response';

			$this->logger('Cardknox CURL ERROR: Empty Gateway Response');
		}
		curl_close($curl);

		$this->respond($json);
	}

	public function error() {
		/* Public and unauthenticated: take only the one field the checkout page
		   sends, strip anything that could forge a log line, and cap the length. */
		$error = isset($this->request->post['error']) ? (string)$this->request->post['error'] : '';
		$error = preg_replace('/[^\x20-\x7E]/', '', $error);
		$error = substr($error, 0, self::ERROR_LOG_MAX_LENGTH);

		if ($error !== '') {
			$this->logger('CARDKNOX IFIELDS ERROR: ' . $error);
		}

		$this->respond(array());
	}

	public function logger($message) {
		$log = new Log('cardknox.log');
		$log->write($this->redact($message));
	}

	private function redact($message) {
		if (!is_array($message)) {
			return $message;
		}

		foreach ($message as $key => $value) {
			if (in_array(strtolower($key), $this->redacted_fields)) {
				$message[$key] = '******';
			}
		}

		return $message;
	}

	/* Gateway field names vary in case between responses, so match without it
	   and return '' rather than warning on a field the gateway omitted. */
	private function responseValue($response_info, $key) {
		foreach ($response_info as $name => $value) {
			if (strcasecmp($name, $key) === 0) {
				return $value;
			}
		}

		return '';
	}

	private function getGatewayUrl() {
		$url = trim((string)$this->config->get('payment_cardknox_server'));

		/* Fall back rather than post card data to a blank or non-https endpoint. */
		if ($url === '' || stripos($url, 'https://') !== 0 || !filter_var($url, FILTER_VALIDATE_URL)) {
			return self::DEFAULT_GATEWAY_URL;
		}

		return $url;
	}

	private function respond($json) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
