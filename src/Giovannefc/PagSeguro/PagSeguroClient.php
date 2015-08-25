<?php
namespace Giovannefc\PagSeguro;

use Giovannefc\PagSeguro\PagSeguroException;

class PagSeguroClient
{
    /**
     * url do pagseguro para criar uma sessão
     * @var string
     */
    protected $urlSession;

    /**
     * url do pagseguro para enviar uma transação
     * @var string
     */
    protected $urlTransactions;

    /**
     * url do pagseguro para solicitar recebimento de notificações
     * @var string
     */
    protected $urlNotifications;

    /**
     * Session instance
     * @var object
     */
    protected $session;

    /**
     * Config instance
     * @var object
     */
    protected $config;

    /**
     * Log instance
     * @var object
     */
    protected $log;

    /**
     * object constructor
     * @param $session
     * @param $validator
     * @param $config
     * @param $log
     */
    public function __construct($session, $config, $log)
    {
        $this->session = $session;
        $this->config = $config;
        $this->log = $log;

        $this->setUrl();
    }


    /**
     * define o ambiente de trabalho: sandbox ou production
     */
    protected function setUrl()
    {

        $env = $this->config->get('pagseguro.env');

        if ($env == 'sandbox') {
            $this->urlSession = 'https://ws.sandbox.pagseguro.uol.com.br/v2/sessions';
            $this->urlTransactions = 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions';
            $this->urlNotifications = 'https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/notifications/';
        } elseif ($env == 'production') {
            $this->urlSession = 'https://ws.pagseguro.uol.com.br/v2/sessions';
            $this->urlTransactions = 'https://ws.pagseguro.uol.com.br/v2/transactions';
            $this->urlNotifications = 'https://ws.pagseguro.uol.com.br/v3/transactions/notifications/';
        }
    }

    /**
     * coloca o id de sessão do pagseguro na sessão
     * @return  string
     */
    public function setSessionId()
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

        $result = curl_exec($ch);

        if ($result == 'Unauthorized' || $result == 'Forbidden') {
            throw new PagSeguroException($result . ': Provavelmente você precisa solicitar a liberação do pagamento transparente em sua conta.', 1);
        }

        $result = simplexml_load_string(curl_exec($ch));

        curl_close($ch);

        $result = json_decode(json_encode($result));

        $this->session->put('pagseguro.sessionId', $result->id);

        return $result->id;
    }

    /**
     * envia a transação para o pagseguro e retorna
     * um array com o resultado
     * @return array|false
     */
    public function sendTransaction(array $settings)
    {

        $data = '';
        foreach ($settings as $key => $value) {
            $data .= $key . '=' . $value . '&';
        }

        $data = rtrim($data, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->urlTransactions);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded; charset=ISO-8859-1']);
        curl_setopt($ch, CURLOPT_POST, count($settings));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = simplexml_load_string(curl_exec($ch));

        $result = json_decode(json_encode($result), true);

        curl_close($ch);

        if (isset($result['status'])) {
            return $result;
        }

        $this->log->error('Error sending PagSeguro transaction', ['Return:' => $result]);

        return false;
    }

    /**
     * monta a url para retorna uma mudança de status do pedido
     * @param  string $code
     * @param  string $type
     * @return string
     */
    public function getNotifications($code, $type)
    {

        $url = $this->urlNotifications . $code
            . '?email=' . $this->config->get('pagseguro.email')
            . '&token=' . $this->config->get('pagseguro.token');

        $result = simplexml_load_string(file_get_contents($url));

        $result = json_decode(json_encode($result));

        return $result;
    }
}