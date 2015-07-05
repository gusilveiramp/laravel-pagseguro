<?php

namespace Giovannefc\PagSeguro;

use Giovannefc\PagSeguro\Exceptions\InvalidSenderInfoException;
use Giovannefc\PagSeguro\Exceptions\InvalidSenderAddressException;
use Giovannefc\PagSeguro\Exceptions\InvalidSendException;

class PagSeguro {
	
	protected $urlSession;
	protected $urlTransactions;
	protected $urlNotifications;

	protected $session;
	protected $validator;
	protected $config;

	protected $pagseguroSession;

	protected $senderInfo;
	protected $senderAddress;
	protected $items;
	protected $reference;
	protected $shippingCost;
	protected $paymentMethod;
	protected $totalAmount;
	protected $paymentSettings;

	public function __construct($session, $validator, $config)
	{
		$this->session = $session;
		$this->validator = $validator;
		$this->config = $config;

		$this->setEnvironment();
	}

	protected function setEnvironment()
	{

		$env = $this->config->get('pagseguro.env');

		if ($env == 'sandbox')
		{
			$this->urlSession = 'https://ws.sandbox.pagseguro.uol.com.br/v2/sessions';
			$this->urlTransactions = 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions';
			$this->urlNotifications = 'https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/notifications/';
		}
		elseif ($env == 'production')
		{
			$this->urlSession = 'https://ws.pagseguro.uol.com.br/v2/sessions';
			$this->urlTransactions = 'https://ws.pagseguro.uol.com.br/v2/transactions';
			$this->urlNotifications = 'https://ws.pagseguro.uol.com.br/v3/transactions/notifications/';
		}

		return $this;
	}


	/**
	* retorna o id da sessão do pagseguro
	* caso ainda não exista, é executado o método
	* setSessionId() e é retornado o id da sessão
	* 
	* @return string
	*
	*/

	public function getSessionId()
	{
		if ($this->session->has('pagseguro.sessionId'))
		{
			return $this->session->get('pagseguro.sessionId');
		}
		else
		{
			$this->setSessionId();

			return $this->session->get('pagseguro.sessionId');
		}
	}
	

	/**
	* define os dados do comprador (senderInfo)
	* 
	* @param string|array $senderInfo
	*
	*/

	public function setSenderInfo(array $senderInfo)
	{
		$senderInfo = $this->validateSenderInfo($senderInfo);

		$this->senderInfo = array(
			'senderName' 		=> $senderInfo['nome'],
			'senderCPF'			=> str_replace(['.', '-'], '', $senderInfo['cpf']),
			'senderAreaCode'	=> explode(' ', $senderInfo['telefone'])[0],
			'senderPhone' 		=> explode(' ', $senderInfo['telefone'])[1],
            'senderEmail' 		=> 'c30088421023915411873@sandbox.pagseguro.com.br' //$senderInfo['email']
            );

		return $this;
	}

	/**
	* define o endereço do comprador (senderAddress)
	* 
	* @param string|array $senderAddress
	*
	*/

	public function setSenderAddress(array $senderAddress)
	{
		$senderAddress = $this->validateSenderAddress($senderAddress);

		$this->senderAddress = array(
			'shippingAddressStreet'		=> $senderAddress['rua'],
			'shippingAddressNumber' 	=> $senderAddress['numero'],
			'shippingAddressComplement' => $senderAddress['complemento'],
			'shippingAddressDistrict'   => $senderAddress['bairro'],
			'shippingAddressPostalCode' => $senderAddress['cep'],
			'shippingAddressCity'		=> $senderAddress['cidade'],
			'shippingAddressState' 		=> $senderAddress['uf'],
			'shippingAddressCountry'	=> 'BRA'
		);

		return $this;
	}

	/**
	* define os items da compra
	* 
	* @param string|array $items
	*
	*/

	public function setItems(array $items)
	{
		$i = 1;
		foreach ($items as $value) {
			$itemsPagSeguro['itemId' . $i] = $value['id'];
			$itemsPagSeguro['itemDescription' . $i] = $value['name'];
			$itemsPagSeguro['itemAmount' . $i] = number_format($value['price'], 2, '.', '');
			$itemsPagSeguro['itemQuantity' . $i++] = $value['quantity'];
		}

		$this->items = $itemsPagSeguro;

		return $this;

	}

	public function setPaymentMethod($paymentMethod)
	{
		$this->paymentMethod = $paymentMethod;

		return $this;
	}

	/**
	* define um valor de referência da transação no pagseguro
	*
	* @param int $reference
	*
	*/

	public function setTotalAmount($totalAmount)
	{
		$this->totalAmount = $totalAmount;

		return $this;
	}

	public function setReference($reference)
	{
		$this->reference = $reference;

		return $this;
	}

	/**
	* define o valor do frete cobrado
	*
	* @param int $shippingCost
	*
	*/

	public function setShippingCost($shippingCost)
	{
		$this->shippingCost;

		return $this;
	}

	/**
	* envia a transação para o pagseguro usando as configurações
	* setadas nos métodos e retorna um array com o resultado
	*
	* @return array
	*
	*/

	public function send()
	{

		if (!$this->session->has('pagseguro.senderHash'))
		{
			throw new InvalidSendException('SenderHash is not defined', 1);
		}

		if ($this->reference === null)
		{
			$this->reference = rand('1000', '10000');
		}

		if ($this->shippingCost === null)
		{
			$this->shippingCost = '0.00';
		}

		if ($this->paymentMethod == 'boleto')
		{
			$this->paymentSettings = ['paymentMethod' => 'boleto'];
		}
		elseif ($this->paymentMethod == 'credit_card')
		{
			$this->setCreditCardToken();
		}
		else
		{
			throw new InvalidSendException('paymentMethod is not valid. Use boleto or credit_card', 1);
		}

		$config = array(
			'email' 					=> $this->config->get('pagseguro.email'),
			'token' 					=> $this->config->get('pagseguro.token'),
			'paymentMode' 				=> 'default',
			'receiverEmail' 			=> $this->config->get('pagseguro.email'),
			'currency' 					=> 'BRL',
			'reference' 				=> $this->reference,
			'senderHash'				=> $this->session->get('pagseguro.senderHash'),
			'shippingCost' 				=> $this->shippingCost
		);

		$settings = array_merge($config, $this->senderInfo, $this->senderAddress, $this->items, $this->paymentSettings);

		return $this->sendTransaction($settings);
	}

	/**
	* define as configurações e o token do cartão de crédito
	* caso o mesmo seja usado. se esse método não for usado
	* será assumido o método de pagamento em boleto.
	*
	*/

	protected function setCreditCardToken()
	{
		if ($this->totalAmount === null)
		{
			throw new InvalidSendException('For credit_card paymentMethod you need define totalAmount using setTotalAmount() method.', 1);
		}

		if (!$this->session->has('pagseguro.creditCardToken'))
		{
			throw new InvalidSendException('creditCardToken is not defined.', 1);
		}

		$this->paymentSettings = array(
			'paymentMethod' 			=> 'credit_card',
			'creditCardToken' 			=> $this->session->get('pagseguro.creditCardToken'),
			'installmentQuantity' 		=> '1',
			'installmentValue' 			=> number_format($this->totalAmount, 2, '.', ''),
			'creditCardHolderName' 		=> $this->session->get('pagseguro.holderName'),
			'creditCardHolderCPF' 		=> $this->session->get('pagseguro.holderCpf'),
			'creditCardHolderBirthDate' => $this->session->get('pagseguro.holderBirthDate'),
			'creditCardHolderAreaCode' 	=> $this->senderInfo['senderAreaCode'],
			'creditCardHolderPhone' 	=> $this->senderInfo['senderPhone'],
			'billingAddressStreet' 		=> $this->senderAddress['shippingAddressStreet'],
			'billingAddressNumber' 		=> $this->senderAddress['shippingAddressNumber'],
			'billingAddressComplement' 	=> $this->senderAddress['shippingAddressComplement'],
			'billingAddressDistrict' 	=> $this->senderAddress['shippingAddressDistrict'],
			'billingAddressPostalCode' 	=> $this->senderAddress['shippingAddressPostalCode'],
			'billingAddressCity' 		=> $this->senderAddress['shippingAddressCity'],
			'billingAddressState' 		=> $this->senderAddress['shippingAddressState'],
			'billingAddressCountry' 	=> 'BRA'
		);

		return $this;
	}

	/**
	* retorna meses e anos para usar na view do formulário
	* de pagamento para escolher a validade do cartão de
	* crédito
	*
	* @return array
	*
	*/

	public function viewMesesAnos()
	{
		$dados['meses'][''] = '';
        $dados['anos'][''] = '';
        for ($i = 1; $i <= 12; $i++) {
            $dados['meses'][$i] = $i;
        }
        for ($i = 2015; $i <= 2030; $i++) {
            $dados['anos'][$i] = $i;
        }

        return $dados;
	}

	/**
	* Retorna o nome da rota criada e definida no config
	* para envia o pagamento. Default: enviaPagamento
	*
	* @return string
	*
	*/

	public function viewSendRoute()
	{
		return $this->config->get('pagseguro.send_route');
	}

	/**
	* envia a transação para o pagseguro e retorna
	* um array com o resultado
	*
	* @return array
	*
	*/

	protected function setSessionId()
	{

		$credentials = array(
			'email' => $this->config->get('pagseguro.email'),
			'token' => $this->config->get('pagseguro.token')
		);

		$data = '';
		foreach ($credentials as $key => $value) {
			$data .= $key . '=' . $value . '&';
		}

		$data = rtrim($data, '&');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->urlSession);
		curl_setopt($ch, CURLOPT_POST, count($credentials));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = simplexml_load_string(curl_exec($ch));

		$result = json_decode(json_encode($result));

		$this->session->put('pagseguro.sessionId', $result->id);

		curl_close($ch);

	}

	protected function sendTransaction(array $settings) {

		$data = '';
		foreach ($settings as $key => $value) {
			$data .= $key . '=' . $value . '&';
		}

		$data = rtrim($data, '&');

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->urlTransactions);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded; charset=UTF-8']);
		curl_setopt($ch, CURLOPT_POST, count($settings));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = simplexml_load_string(curl_exec($ch));

		$result = json_decode(json_encode($result), true);

		curl_close($ch);

		return $result;
	}

	protected function validateSenderInfo($senderInfo)
	{
		$rules = array(
			'nome' 	=> 'required',
			'cpf'	=> 'required',
			'telefone' 	=> 'required',
			'email'	=> 'email'
			);

		$validator = $this->validator->make($senderInfo, $rules);

		if ($validator->fails())
		{
			throw new InvalidSenderInfoException($validator->messages()->first());
		}

		return $senderInfo;
	}

	protected function validateSenderAddress($senderAddress)
	{
		$rules = array(
			'rua' 			=> 'required',
			'numero'		=> 'required',
			'complemento' 	=> 'required',
			'bairro' 		=> 'required',
			'cep'			=> 'required',
			'cidade' 		=> 'required',
			'uf'			=> 'required'
			);

		$validator = $this->validator->make($senderAddress, $rules);

		if ($validator->fails())
		{
			throw new InvalidSenderAddressException($validator->messages()->first());
		}

		return $senderAddress;
	}

	protected function getSession()
	{

		return (new PagSeguroCollection($this->session->get('pagseguro')));
	}

	public function clear() {

		$this->session->forget('pagseguro');

	}

	public function getNotifications($code, $type) {

		$url = $this->urlNotifications . $code
		. '?email=' . $this->config->get('pagseguro.email')
		. '&token=' . $this->config->get('pagseguro.token');

		$result = simplexml_load_string(file_get_contents($url));

		$result = json_decode(json_encode($result));

		return $result;

	}

	public function getPaymentMethods() {

		// Retorna em array

		$json = $this->session->get('pagseguro.paymentMethods');

		return json_decode($json, true);
	}

	// Javascript Functions

	public function jsSetSessionId()
	{
		return 'PagSeguroDirectPayment.setSessionId(\'' . $this->getSessionId() . '\');';
	}

}