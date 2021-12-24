<?php


namespace Schwartzcode\Http;


use Schwartzcode\Exceptions\EnvironmentException;

class CurlClient implements Client {
    const DEFAULT_TIMEOUT = 30;
    protected $curlOptions = array();
    protected $debugHttp = false;


    public function __construct(array $options = array()) {
        $this->curlOptions = $options;
        $this->debugHttp = getenv('DEBUG_HTTP_TRAFFIC') === 'true';
    }


    /**
     * @param $method
     * @param $url
     * @param array $params
     * @param array $data
     * @param array $headers
     * @param null $user
     * @param null $password
     * @param null $timeout
     * @return bool|string
     * @throws EnvironmentException
     * @throws \ErrorException
     * https://stackoverflow.com/questions/20064271/how-to-use-basic-authorization-in-php-curl
     */
    public function request($method, $url, $params = array(), $data = array(),
                            $headers = array(), $user = null, $password = null,
                            $timeout = null) {

        $options = $this->options($method, $url, $params, $data, $headers,
                                  $user, $password, $timeout);

        
        try {
            if (!$curl = curl_init()) {
                throw new EnvironmentException('Unable to initialize cURL');
            }

            if (!curl_setopt_array($curl, $options)) {
                throw new EnvironmentException(curl_error($curl));
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            if (!$response = curl_exec($curl)) {
                throw new EnvironmentException(curl_error($curl));
            }



            curl_close($curl);

            return $response;

        } catch (\ErrorException $e) {
            if (isset($curl) && is_resource($curl)) {
                curl_close($curl);
            }

            if (isset($buffer) && is_resource($buffer)) {
                fclose($buffer);
            }

            throw $e;
        }
    }

    public function options($method, $url, $params = array(), $data = array(),
                            $headers = array(), $user = null, $password = null,
                            $timeout = null) {

        $timeout = is_null($timeout)
            ? self::DEFAULT_TIMEOUT
            : $timeout;
        $options = $this->curlOptions + array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
//            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_INFILESIZE => Null,
//            CURLOPT_HTTPHEADER => array(),
            CURLOPT_TIMEOUT => $timeout,
        );




        foreach ($headers as $key => $value) {
            $options[CURLOPT_HTTPHEADER][] = "$key: $value";
        }

        if ($user && $password) {
            $options[CURLOPT_USERPWD] = "$user:$password";
        }

        $body = $this->buildQuery($params);
        if ($body) {
            $options[CURLOPT_URL] .= '?' . $body;
        }

        switch (strtolower(trim($method))) {
            case 'get':
                $options[CURLOPT_HTTPGET] = true;
                break;
            case 'post':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $this->buildQuery($data);

                break;
            case 'put':
                $options[CURLOPT_PUT] = true;
                if ($data) {
                    if ($buffer = fopen('php://memory', 'w+')) {
                        $dataString = $this->buildQuery($data);
                        fwrite($buffer, $dataString);
                        fseek($buffer, 0);
                        $options[CURLOPT_INFILE] = $buffer;
                        $options[CURLOPT_INFILESIZE] = strlen($dataString);
                    } else {
                        throw new EnvironmentException('Unable to open a temporary file');
                    }
                }
                break;
            case 'head':
                $options[CURLOPT_NOBODY] = true;
                break;
            default:
                $options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        }

        return $options;
    }

    public function buildQuery($params) {
        $parts = array();

        if (is_string($params)) {
            return $params;
        }

        $params = $params ?: array();

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = urlencode((string)$key) . '=' . urlencode((string)$item);
                }
            } else {
                $parts[] = urlencode((string)$key) . '=' . urlencode((string)$value);
            }
        }

        return implode('&', $parts);
    }
}
