<?php namespace Giovannefc\Pagseguro;

use Config;
use Session;

class PagSeguro {
	
	protected $urlSession = 'https://ws.sandbox.pagseguro.uol.com.br/v2/sessions';
	protected $urlTransactions = 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions';
	protected $urlNotifications = 'https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/notifications/';

	public function setSessionId() {

		$credentials = array(
			'email' => Config::get('pagseguro.email'),
			'token' => Config::get('pagseguro.token')
			);

		$data = '';
		foreach ($credentials as $campo => $valor) {
			$data .= $campo . '=' . $valor . '&';
		}

		$data = rtrim($data, '&');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->urlSession);
		curl_setopt($ch, CURLOPT_POST, count($credentials));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = simplexml_load_string(curl_exec($ch));

		$result = json_decode(json_encode($result));

		Session::put('pagseguro.sessionToken', $result->id);

		curl_close($ch);

	}

	public function getSessionId() {

		if (Session::has('pagseguro.sessionToken')) {

			return Session::get('pagseguro.sessionToken');

		} else {

			return false;

		}
	}

	public function getPaymentMethods() {

		// Retorna em array

		$json = Session::get('pagseguro.paymentMethods');

		return json_decode($json, true);
	}


	public function sendTransaction($infos = array()) {

		$data = '';
		foreach ($infos as $campo => $valor) {
			$data .= $campo . '=' . $valor . '&';
		}

		$data = rtrim($data, '&');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->urlTransactions);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded; charset=UTF-8']);
		curl_setopt($ch, CURLOPT_POST, count($infos));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = simplexml_load_string(curl_exec($ch));

		$result = json_decode(json_encode($result), true);

		curl_close($ch);

		return $result;
	}

	public function clear() {

		Session::forget('pagseguro');

	}

	public function getNotifications($code, $type) {

		$url = $this->urlNotifications . $code
		. '?email=' . Config::get('pagseguro.email')
		. '&token=' . Config::get('pagseguro.token');

		$result = simplexml_load_string(file_get_contents($url));

		$result = json_decode(json_encode($result));

		return $result;

	}

}