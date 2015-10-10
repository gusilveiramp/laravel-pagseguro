<?php
namespace Giovannefc\PagSeguro;

use Illuminate\Session\SessionManager as Session;
use Illuminate\Validation\Factory as Validator;
use Illuminate\Config\Repository as Config;
use Illuminate\Log\Writer as Log;

class PagSeguroConfig
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
     * Log instance
     * @var object
     */
    protected $log;

    /**
     * Token da conta PagSeguro
     * @var string
     */
    protected $token;

    /**
     * Email da conta PagSeguro
     * @var string
     */
    protected $email;

    /**
     * Armazena as url's para conexão com o PagSeguro
     * @var array
     */
    protected $url = [];

    /**
     * @param $session
     * @param $validator
     * @param $config
     * @param $log
     */
    public function __construct(Session $session, Validator $validator, Config $config, Log $log)
    {
        $this->session = $session;
        $this->validator = $validator;
        $this->config = $config;
        $this->log = $log;

        $this->setEnvironmentToken();
        $this->setUrl();
        $this->setEmail();
    }

    /**
     * define o ambiente de trabalho
     */
    private function setEnvironmentToken()
    {
        if(app()->environment('production')) {
            $this->token = $this->config->get('pagseguro.token_production');
        } else {
            $this->token = $this->config->get('pagseguro.token_sandbox');
        }
    }

    private function setEmail() {
        $this->email = $this->config->get('pagseguro.email');
    }

    /**
     * define as url's de acordo com o ambiente de trabalho
     */
    private function setUrl()
    {
        $sandbox = null;

        if (!app()->environment('production')) {
            $sandbox = 'sandbox.';
        }

        $url = [
            'session' => 'https://ws.' . $sandbox . 'pagseguro.uol.com.br/v2/sessions',
            'transactions' => 'https://ws.' . $sandbox . 'pagseguro.uol.com.br/v2/transactions',
            'notifications' => 'https://ws.' . $sandbox . 'pagseguro.uol.com.br/v3/transactions/notifications/',
            'javascript' => 'https://stc.' . $sandbox . 'pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js'
        ];

        $this->url = $url;
    }
}