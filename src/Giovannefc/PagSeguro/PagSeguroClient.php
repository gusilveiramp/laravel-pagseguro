<?php
namespace Giovannefc\PagSeguro;

class PagSeguroClient extends PagSeguroConfig
{
    /**
     * Coloca o sessionId do PagSeguro na sessão
     * @return mixed
     * @throws \Giovannefc\PagSeguro\PagSeguroException
     */
    public function setSessionId()
    {

        $credentials = array(
            'email' => $this->email,
            'token' => $this->token
        );

        $data = '';
        foreach ($credentials as $key => $value) {
            $data .= $key . '=' . $value . '&';
        }

        $data = rtrim($data, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url['session']);
        curl_setopt($ch, CURLOPT_POST, count($credentials));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(app()->environment() == 'production') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $result = curl_exec($ch);
        
        if (FALSE === $result)
            throw new PagSeguroException(curl_error($ch), curl_errno($ch));

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
     * @param array $settings
     * @return bool|mixed|\SimpleXMLElement
     */
    public function sendTransaction(array $settings)
    {

        $data = '';
        foreach ($settings as $key => $value) {
            $data .= $key . '=' . $value . '&';
        }

        $data = rtrim($data, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url['transactions']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded; charset=ISO-8859-1']);
        curl_setopt($ch, CURLOPT_POST, count($settings));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(app()->environment() == 'production') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

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

        $url = $this->url['notifications'] . $code
            . '?email=' . $this->email
            . '&token=' . $this->token;

        $result = simplexml_load_string(file_get_contents($url));

        $result = json_decode(json_encode($result));

        return $result;
    }
}
