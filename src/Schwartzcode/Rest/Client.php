<?php
namespace Schwartzcode\Rest;


use ErrorException;
use Schwartzcode\Exceptions\ConfigurationException;
use Schwartzcode\Exceptions\EnvironmentException;
use Schwartzcode\Http\CurlClient;
use Schwartzcode\Http\Client as HttpClient;
use Schwartzcode\VersionInfo;

class Client
{
    protected $username;
    protected $password;
    protected $protocol;
    protected $domain;
    protected $port;
    protected $uri;
    protected $httpClient;

    /**
     * Initializes the Dinstar Client
     *
     * @param $params array
     * @param HttpClient $httpClient HttpClient, defaults to CurlClient
     * @throws ConfigurationException If valid authentication is not present
     */
    public function __construct(array $params, HttpClient $httpClient = null) {

        $this->username = !empty($params['username']) ? $params['username'] : null;
        $this->password = !empty($params['password']) ? $params['password'] : null;
        $this->protocol = !empty($params['protocol']) ? $params['protocol'] : null;
        $this->domain = !empty($params['domain']) ? $params['domain'] : null;
        $this->port = !empty($params['port']) ? $params['port'] : 80;

        if (!$this->username) {
            throw new ConfigurationException("Username is required to create a Client");
        }
        if (!$this->password) {
            throw new ConfigurationException("Password is required to create a Client");
        }
        if (!$this->protocol) {
            throw new ConfigurationException("Protocol is required to create a Client");
        }
        if (!$this->domain) {
            throw new ConfigurationException("Domain is required to create a Client");
        }
        if (!$this->port) {
            throw new ConfigurationException("Port is required to create a Client");
        }

        $this->uri = "$this->protocol://$this->domain:$this->port/api";

        if ($httpClient) {
            $this->httpClient = $httpClient;
        } else {
            $this->httpClient = new CurlClient();
        }
    }

    /**
     * Makes a request to the Dinstar API using the configured http client
     * Authentication information is automatically added if none is provided
     *
     * @param string $method HTTP Method
     * @param $uri string
     * @param string[] $params Query string parameters
     * @param string[] $data POST body data
     * @param string[] $headers HTTP Headers
     * @param string $username User for Authentication
     * @param string $password Password for Authentication
     * @param int $timeout Timeout in seconds
     * @return bool|string
     * @throws EnvironmentException
     * @throws ErrorException
     */
    public function request($method, $uri, $params = array(), $data = array(), $headers = array(), $username = null, $password = null, $timeout = null) {
        $username = $username ? $username : $this->username;
        $password = $password ? $password : $this->password;

        $headers['User-Agent'] = 'dinstar-php/' . VersionInfo::string() .
            ' (PHP ' . phpversion() . ')';
        $headers['Accept-Charset'] = 'utf-8';

        if ($method == 'POST' && !array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        if (!array_key_exists('Accept', $headers)) {
            $headers['Accept'] = 'application/json';
        }

        return $this->getHttpClient()->request(
            $method,
            $uri,
            $params,
            $data,
            $headers,
            $username,
            $password,
            $timeout
        );
    }


    /**
     * Retrieve the HttpClient
     *
     * @return HttpClient Current HttpClient
     */
    public function getHttpClient() {
        return $this->httpClient;
    }

    public function sendSms($dataArray){
        try {
            return $this->request(
                'POST',
                $this->uri . '/send_sms',
                null,
                json_encode($dataArray)
            );
        } catch (ErrorException $e) {
        } catch (EnvironmentException $e) {
        }
    }


}