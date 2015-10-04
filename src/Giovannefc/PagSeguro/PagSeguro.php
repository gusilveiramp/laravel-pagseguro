<?php
namespace Giovannefc\PagSeguro;

use Giovannefc\PagSeguro\PagSeguroException;

class PagSeguro
{

    /**
     * Session instance
     * @var object
     */
    protected $session;

    /**
     * Validator instance
     * @var object
     */
    protected $validator;

    /**
     * Config instance
     * @var object
     */
    protected $config;

    /**
     * PagSeguroClient instance
     * @var object
     */
    protected $http;

    /**
     * informações do comprador
     * @var array
     */
    protected $senderInfo;

    /**
     * ambiente de trabalho (sandbox|production)
     * @var string
     */
    protected $environment;

    /**
     * endereço do comprador
     * @var array
     */
    protected $senderAddress;

    /**
     * itens da compra
     * @var array
     */
    protected $items;

    /**
     * id de referência da compra no pagseguro
     * @var string
     */
    protected $reference;

    /**
     * valor do frete da compra
     * @var float
     */
    protected $shippingCost;

    /**
     * valor total da compra
     * @var float
     */
    protected $totalAmount;

    /**
     * dados do cartão de crédito
     * @var array
     */
    protected $creditcardData;

    /**
     * configurações da compra
     * @var array
     */
    protected $paymentSettings;

    /**
     * object constructor
     * @param $session
     * @param $validator
     * @param $config
     * @param $http
     * @throws \Giovannefc\PagSeguro\PagSeguroException
     * @internal param $log
     */
    public function __construct($session, $validator, $config, $http)
    {
        $this->session = $session;
        $this->validator = $validator;
        $this->config = $config;
        $this->http = $http;

        $this->setEnvironment();
    }

    /**
     * define o ambiente de trabalho
     */
    protected function setEnvironment()
    {
        $env = $this->config->get('pagseguro.env');

        if ($env == 'sandbox') {
            $this->environment = $env;
        } elseif ($env == 'production') {
            $this->environment = $env;
        } else {
            throw new PagSeguroException('Invalid environment. Use sandbox or production', 1);
        }
    }

    /**
     * define os dados do comprador
     * @param array $senderInfo
     * @return $this
     */
    public function setSenderInfo(array $senderInfo)
    {
        $senderInfo = $this->validateSenderInfo($senderInfo);

        if ($this->environment == 'sandbox') {
            $senderEmail = 'teste@sandbox.pagseguro.com.br';
        } else {
            $senderEmail = $senderInfo['email'];
        }

        $this->senderInfo = array(
            'senderName' => $senderInfo['nome'],
            'senderCPF' => str_replace(['.', '-'], '', $senderInfo['cpf']),
            'senderAreaCode' => explode(' ', $senderInfo['telefone'])[0],
            'senderPhone' => explode(' ', $senderInfo['telefone'])[1],
            'senderEmail' => $senderEmail
        );

        return $this;
    }

    protected function validateSenderInfo($senderInfo)
    {
        $rules = array(
            'nome' => 'required',
            'email' => 'required|email',
            'cpf' => 'required',
            'telefone' => 'required'
        );

        $validator = $this->validator->make($senderInfo, $rules);

        if ($validator->fails()) {
            throw new PagSeguroException($validator->messages()->first());
        }

        return $senderInfo;
    }

    /**
     * define o endereço do comprador
     * @param array $senderAddress
     * @return $this
     */
    public function setSenderAddress(array $senderAddress)
    {
        $senderAddress = $this->validateSenderAddress($senderAddress);

        $this->senderAddress = array(
            'shippingAddressStreet' => $senderAddress['rua'],
            'shippingAddressNumber' => $senderAddress['numero'],
            'shippingAddressComplement' => $senderAddress['complemento'],
            'shippingAddressDistrict' => $senderAddress['bairro'],
            'shippingAddressPostalCode' => $senderAddress['cep'],
            'shippingAddressCity' => $senderAddress['cidade'],
            'shippingAddressState' => $senderAddress['uf'],
            'shippingAddressCountry' => 'BRA'
        );

        return $this;
    }

    /**
     * valida os dados contidos na array de endereço do comprador
     * @param  array $senderAddress
     * @return array
     * @throws \Giovannefc\PagSeguro\PagSeguroException
     */
    protected function validateSenderAddress($senderAddress)
    {
        $rules = array(
            'rua' => 'required',
            'numero' => 'required',
            'bairro' => 'required',
            'cep' => 'required',
            'cidade' => 'required',
            'uf' => 'required'
        );

        $validator = $this->validator->make($senderAddress, $rules);

        if ($validator->fails()) {
            throw new PagSeguroException($validator->messages()->first());
        }

        return $senderAddress;
    }

    /**
     * define os itens da compra
     * @param array $items
     * @return $this
     */
    public function setItems(array $items)
    {
        $itemsPagSeguro = [];
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

    /**
     * define o valor total da compra
     * @param float $totalAmount
     * @return $this
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * define um id de referência da compra no pagseguro
     * @param string $reference
     * @return $this
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }


    /**
     * define o valor do frete cobrado
     * @param $shippingCost
     * @return $this
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    /**
     * @param $data
     * @return mixed
     * @throws \Giovannefc\PagSeguro\PagSeguroException
     */
    public function sendCreditCard($data)
    {
        if ($this->totalAmount === null) {
            throw new PagSeguroException('For credit_card paymentMethod you need define totalAmount using setTotalAmount() method.', 1);
        }

        $this->paymentSettings = array(
            'paymentMethod' => 'credit_card',
            'senderHash' => $data['senderHash'],
            'creditCardToken' => $data['cardToken'],
            'installmentQuantity' => '1',
            'installmentValue' => number_format($this->totalAmount, 2, '.', ''),
            'creditCardHolderName' => $this->session->get('pagseguro.holderName'),
            'creditCardHolderCPF' => $this->session->get('pagseguro.holderCpf'),
            'creditCardHolderBirthDate' => $data['holderBirthDate'],
            'creditCardHolderAreaCode' => $this->senderInfo['senderAreaCode'],
            'creditCardHolderPhone' => $this->senderInfo['senderPhone'],
            'billingAddressStreet' => $this->senderAddress['shippingAddressStreet'],
            'billingAddressNumber' => $this->senderAddress['shippingAddressNumber'],
            'billingAddressComplement' => $this->senderAddress['shippingAddressComplement'],
            'billingAddressDistrict' => $this->senderAddress['shippingAddressDistrict'],
            'billingAddressPostalCode' => $this->senderAddress['shippingAddressPostalCode'],
            'billingAddressCity' => $this->senderAddress['shippingAddressCity'],
            'billingAddressState' => $this->senderAddress['shippingAddressState'],
            'billingAddressCountry' => 'BRA'
        );

        return $this->send();
    }

    /**
     * @return mixed
     */
    protected function send()
    {
        $this->validate();

        $config = array(
            'email' => $this->config->get('pagseguro.email'),
            'token' => $this->config->get('pagseguro.token'),
            'paymentMode' => 'default',
            'receiverEmail' => $this->config->get('pagseguro.email'),
            'currency' => 'BRL',
            'reference' => $this->reference,
            'shippingCost' => $this->shippingCost
        );

        $settings = array_merge($config, $this->senderInfo, $this->senderAddress, $this->items, $this->paymentSettings);

        return $this->http->sendTransaction($settings);
    }

    /**
     * seta valores padrões caso não forem definidos
     */
    protected function validate()
    {
        if ($this->reference === null) {
            $this->reference = rand('1000', '10000');
        }

        if ($this->shippingCost === null) {
            $this->shippingCost = '0.00';
        }
    }

    public function sendBillet($senderHash)
    {
        $this->paymentSettings = array(
            'paymentMethod' => 'boleto',
            'senderHash' => $senderHash
        );

        return $this->send();
    }

    public function clear()
    {
        $this->session->forget('pagseguro');
    }

    public function getNotifications($code, $type)
    {
        return $this->http->getNotifications($code, $type);
    }

    /**
     * retorna os códigos de status do pedido do pagseguro
     * @return mixed
     */
    public function listStatus()
    {
        return (new PagSeguroCollection([
            '0' => [
                'name' => 'Sem pagamento',
                'bs' => 'warning'
            ],
            '1' => [
                'name' => 'Aguardando Pagamento',
                'bs' => 'default'
            ],
            '2' => [
                'name' => 'Em Análise',
                'bs' => 'info'
            ],
            '3' => [
                'name' => 'Pago',
                'bs' => 'success'
            ],
            '4' => [
                'name' => 'Disponível',
                'bs' => 'default'
            ],
            '5' => [
                'name' => 'Em disputa',
                'bs' => 'danger',
            ],
            '6' => [
                'name' => 'Devolvida',
                'bs' => 'danger'
            ],
            '7' => [
                'name' => 'Cancelada',
                'bs' => 'danger'
            ],
            '8' => [
                'name' => 'Chargeback debitado',
                'bs' => 'warning'
            ],
            '9' => [
                'name' => 'Em contestação',
                'bs' => 'danger'
            ]
        ]));
    }

    /**
     * retorna meses e anos para usar na view do formulário
     * de pagamento para escolher a validade do cartão de
     * crédito
     * @return array
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
     * retorna o nome da rota criada e definida no config
     * para envia o pagamento. Default: sendPayment
     * @return string
     */

    public function viewSendRoute()
    {
        return $this->config->get('pagseguro.send_route');
    }

    /**
     * retorna a função em javascript para inciar a sessão no pagseguro
     * @return string
     */
    public function jsSetSessionId()
    {
        return 'PagSeguroDirectPayment.setSessionId(\'' . $this->getSessionId() . '\');';
    }

    /**
     * retorna o id da sessão do pagseguro
     * caso ainda não exista, é executado o método
     * setSessionId() e é retornado o id da sessão
     * @return string
     */
    public function getSessionId()
    {
        if ($this->session->has('pagseguro.sessionId')) {
            return $this->session->get('pagseguro.sessionId');
        } else {
            return $this->http->setSessionId();
        }
    }

}