<?php
namespace Giovannefc\PagSeguro;

class PagSeguro extends PagSeguroClient
{
    /**
     * informações do comprador
     * @var array
     */
    protected $senderInfo;

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
     * configurações da compra
     * @var array
     */
    protected $paymentSettings;

    /**
     * define número máximo de parcelas sem juros
     * @var int
     */
    protected $maxInstallmentsNoInterest;

    /**
     * define os dados do comprador
     * @param array $senderInfo
     * @return $this
     */
    public function setSenderInfo(array $senderInfo)
    {
        $senderInfo = $this->validateSenderInfo($senderInfo);

        if (app()->environment('production')) {
            $senderEmail = $senderInfo['email'];
        } else {
            $senderEmail = 'teste@sandbox.pagseguro.com.br';
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
     * default: valor randômico de 1000 a 10000
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
     * default: 0.00
     * @param $shippingCost
     * @return $this
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    /**
     * define o a quantidade máxima de parcelas sem juros
     * default: 12
     * @param int $maxInstalmments
     * @return $this
     */
    public function setMaxInstallmentsNoInterest($maxInstalmments)
    {
        $this->maxInstallmentsNoInterest = $maxInstalmments;

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

        $paymentSettings = array(
            'paymentMethod' => 'credit_card',
            'senderHash' => $data['senderHash'],
            'creditCardToken' => $data['cardToken'],
            'maxInstallmentsNoInterest' => $this->maxInstallmentsNoInterest,
            'installmentQuantity' => $data['installments'],
            'installmentValue' => number_format($data['installmentAmount'], '2','.',''),
            'creditCardHolderName' => $data['holderName'],
            'creditCardHolderCPF' => str_replace(['.', '-'], '', $data['holderCpf']),
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

        if ($data['installments'] > 1) {
            $this->paymentSettings = array_merge($paymentSettings, ['noInterestInstallmentQuantity' => $data['installments']]);
        } else {
            $this->paymentSettings = $paymentSettings;
        }

        return $this->send();
    }

    /**
     * @return mixed
     */
    protected function send()
    {
        $this->validate();

        $config = array(
            'email' => $this->email,
            'token' => $this->token,
            'paymentMode' => 'default',
            'receiverEmail' => $this->email,
            'currency' => 'BRL',
            'reference' => $this->reference,
            'shippingCost' => $this->shippingCost
        );

        $settings = array_merge($config, $this->senderInfo, $this->senderAddress, $this->items, $this->paymentSettings);

        return $this->sendTransaction($settings);
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

        if($this->maxInstallmentsNoInterest === null) {
            $this->maxInstallmentsNoInterest = 12;
        }
    }

    public function sendBillet($data)
    {
        $this->paymentSettings = array(
            'paymentMethod' => 'boleto',
            'senderHash' => $data['senderHash']
        );

        return $this->send();
    }

    public function clear()
    {
        $this->session->forget('pagseguro');
    }

    public function getNotifications($code, $type)
    {
        return $this->getNotifications($code, $type);
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
     * Gera o código html para carregar a API javascript do PagSeguro
     * @return string
     */
    public function getUrlApiJava()
    {
        return '<script src="'. $this->url['javascript'] . '"></script>';
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
            return $this->setSessionId();
        }
    }
}