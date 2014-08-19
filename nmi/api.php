<?php


/**
 * NMI API
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @copyright Daniel Boorn
 * @license Creative Commons Attribution-Commercial 3.0 Unported (CC BY 3.0)
 * @namespace NMI
 */


namespace NMI;

class Exception extends \Exception
{

    public $response;
    public $request;

    public function __construct($message, $code = 0, $response = null, $request = null, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->request = $request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }

}

class API
{
    const DIRECT_POST_API_URL = 'https://secure.nmi.com/api/transact.php';
    const RESPONSE_APPROVED = 1;
    const RESPONSE_DECLINED = 2;
    const RESPONSE_ERROR = 3;

    protected $username;
    protected $password;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public static function forge($username, $password)
    {
        return new self($username, $password);
    }

    public function addCustomer(array $params)
    {
        $params['customer_vault'] = 'add_customer';
        $response = $this->fetch($params);
        return $response['customer_vault_id'];
    }

    public function updateCustomer($id, array $params)
    {
        $params['customer_vault'] = 'update_customer';
        $params['customer_vault_id'] = $id;
        $response = $this->fetch($params);
        return $response['customer_vault_id'];
    }

    public function deleteCustomer($id, array $params)
    {
        $params['customer_vault'] = 'delete_customer';
        $params['customer_vault_id'] = $id;
        return $this->fetch($params);
    }

    public function chargeCustomer($id, array $params)
    {
        $params['customer_vault_id'] = $id;
        return $this->fetch($params);
    }

    public function urlFetch(array $params)
    {
        //updated with ssl support, updates still to be tested.
        $data = http_build_query($params);
        $url = parse_url(self::DIRECT_POST_API_URL);

        $context = stream_context_create(array(
            'http' => array(
                'follow_location' => false,
                'header'        => "Content-Type: application/x-www-form-urlencoded\r\nContentLength: " . strlen($data) . "\r\n",
                'timeout'         => 60.0,
                'method'          => 'POST',
                'content'         => $data
            ),
            'ssl'  => array(
                'verify_peer'         => true,
                'cafile'              => dirname(dirname(__FILE__)) . '/ssl/cert.pem',
                'verify_depth'        => 5,
                'CN_match'            => $url['host'],
                'disable_compression' => true,
                'SNI_enabled'         => true,
                'ciphers'             => 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:AES128:AES256:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK',
            ),
        ));

        return file_get_contents(self::DIRECT_POST_API_URL, false, $context);
    }

    public function curlFetch(array $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::DIRECT_POST_API_URL);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_POST, 1);
        if (($response = curl_exec($ch)) === false) {
            throw new Exception(curl_error($ch), self::RESPONSE_ERROR, $response, $params);
        }
        curl_close($ch);
        return $response;
    }

    public function fetch(array $params)
    {
        $params['username'] = $this->username;
        $params['password'] = $this->password;

        if (!defined('CURLOPT_URL')) {
            $response = $this->urlFetch($params);
        } else {
            $response = $this->curlFetch($params);
        }

        if (($pos = strpos($response, 'response')) === false) {
            throw new Exception('Response not detected from gateway', self::RESPONSE_ERROR, $response, $params);
        }

        $response = substr($response, $pos);
        parse_str($response, $data);

        if ($data['response'] != self::RESPONSE_APPROVED) {
            throw new Exception($data['responsetext'], $data['response'], $data, $params);
        }

        return $data;
    }

}
