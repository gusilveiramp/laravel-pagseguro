<?php namespace Giovannefc\PagSeguro;

use Giovannefc\PagSeguro\Exceptions\InvalidSenderInfoException;
use Giovannefc\PagSeguro\Exceptions\InvalidSenderAddressException;

class PagSeguro {
	
	protected $urlSession = 'https://ws.sandbox.pagseguro.uol.com.br/v2/sessions';
	protected $urlTransactions = 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions';
	protected $urlNotifications = 'https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/notifications/';

	protected $session;
	protected $validator;
	protected $config;

	protected $pagseguroSession;

	protected $senderInfo;
	protected $senderAddress;
	protected $items;
	protected $reference;
	protected $shippingCost;
	protected $paymentSettings;

	public function __construct($session, $validator, $config)
	{
		$this->session = $session;
		$this->validator = $validator;
		$this->config = $config;
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
		if ($this->session->has('pagseguro.SessionId'))
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
			'shippingAddressState' 		=> $senderAddress['estado'],
			'shippingAddressCountry'	=> 'BRA'
			);
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

	}

	/**
	* define um valor de referência da transação no pagseguro
	*
	* @param int $reference
	*
	*/

	public function setReference($reference)
	{
		$this->reference = $reference;
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
	}

	/**
	* define as configurações e o token do cartão de crédito
	* caso o mesmo seja usado. se esse método não for usado
	* será assumido o método de pagamento em boleto.
	*
	* @param int $totalAmount
	*
	*/

	public function setCreditCardToken($totalAmount)
	{
		$this->paymentSettings = array(
			'paymentMethod' 			=> 'credit_card',
			'creditCardToken' 			=> $this->session->get('pagseguro.creditCardToken'),
			'installmentQuantity' 		=> '1',
			'installmentValue' 			=> number_format($totalAmount, 2, '.', ''),
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
			throw new \Exception('SenderHash is not defined', 1);
		}

		if ($this->reference === null)
		{
			$this->reference = rand('1000', '10000');
		}

		if ($this->shippingCost === null)
		{
			$this->shippingCost = '0.00';
		}

		if ($this->paymentSettings === null)
		{
			$this->paymentSettings = ['paymentMethod' => 'boleto'];
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
			'estado'		=> 'required'
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

	public function jsAjaxFunctions($routeName)
	{
		return '

		function confirmBoleto() {
			$("#confirmBoleto").attr("disabled", "disabled");
			document.getElementById("loadPagamento").style.display = "block";
			senderHash = PagSeguroDirectPayment.getSenderHash();
			$.post( "'. route('PagSeguroAjaxSenderHash') . '", { _token: "' . csrf_token() . '", data: (senderHash) } );
			setTimeout(function(){
				window.location.href="' . route($routeName, 'boleto') . '";
			}, 2500);
}
function setSenderHash() {
	senderHash = PagSeguroDirectPayment.getSenderHash();
	setTimeout(function(){
		$.post( "' . route('PagSeguroAjaxSenderHash') . '", { _token: "' . csrf_token() . '", data: (senderHash) } );
	}, 1000);
}
function setInfoHolder() {
	$.post( "' . route('PagSeguroAjaxInfoHolder') . '", {
		_token: "' . csrf_token() . '",
		holderName: $("#holderName").val(),
		holderCpf: $("#holderCpf").val(),
		holderBirthDate: $("#holderBirthDate").val()
	});
}'
;
}

public function jsProcessPayment($routeName)
{
	return '

	var parametros = {

		cardNumber: $("#cardNumber").val(),
		cvv: $("#cvv").val(),
		expirationMonth: $("#expirationMonth :selected").val(),
		expirationYear: $("#expirationYear :selected").val(),
		success: function(data) {

			$.post( "' . route('PagSeguroAjaxCreditCardToken') . '", { _token: "' . csrf_token() . '", data: (JSON.stringify(data.card.token).replace(/"/g, \'\')) } );
		}
	}

	$("#confirmCartao").attr("disabled", "disabled");
	document.getElementById("loadPagamento").style.display = "block";

	setSenderHash();
	setInfoHolder();
	PagSeguroDirectPayment.createCardToken(parametros);

	setTimeout(function(){
		window.location.href="' . route($routeName, 'credit_card') . '";
	}, 2500);'
;
}

public function jsGetBrand()
{
	return '

	$("#cardNumber").blur(function() {
		var cardNumber = document.getElementById("cardNumber").value;
		PagSeguroDirectPayment.getBrand({
			cardBin: cardNumber.replace(/ /g,\'\'),
			success: function(data) {
				var brand = JSON.stringify(data.brand.name).replace(/"/g, \'\');
				$("#brand").fadeIn(600);
				$("#brandName").html(brand);
			}
		});
});'
;
}

}

