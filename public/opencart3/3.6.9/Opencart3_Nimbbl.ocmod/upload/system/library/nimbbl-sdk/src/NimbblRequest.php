<?php

namespace Nimbbl\Api;

use Requests;
use Requests_Auth;
use Exception;
use Requests_Hooks;


// Available since PHP 5.5.19 and 5.6.3
// https://git.io/fAMVS | https://secure.php.net/manual/en/curl.constants.php
if (defined('CURL_SSLVERSION_TLSv1_1') === false) {
    define('CURL_SSLVERSION_TLSv1_1', 5);
}


// class NimbblAuth implements Requests_Auth
// {
//     protected $token;
//     protected $accessSecret;

//     public function __construct($token)
//     {
//         $this->token = $token;
//     }

//     public function register(Requests_Hooks $hooks)
//     {
//         $hooks->register('requests.before_request', array($this, 'before_request'));
//     }

//     public function before_request(&$url, &$headers, &$data, &$type, &$options)
//     {
//         $headers['Authorization'] = 'Bearer ' . $this->token;
//     }
// }

/**
 * Request class to communicate to the request libarary
 */
class NimbblRequest
{
    /**
     * Headers to be sent with every http request to the API
     * @var array
     */
    protected static $headers = array(
        'Nimbbl-API'  =>  1
    );

    /**
     * Fires a request to the API
     * @param  string   $method HTTP Verb
     * @param  string   $url    Relative URL for the request
     * @param  array $data Data to be passed along the request
     * @return array Response data in array format. Not meant
     * to be used directly
     */
    public function request($method, $url, $data = array())
    {
        error_log(__FILE__ . ": NimbblRequest::request START" . PHP_EOL);
        try {
            $endpoint = $url;
            $url = NimbblApi::getFullUrl($url);
            $hooks = new Requests_Hooks();
            $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));
            $nimbblToken = self::generateToken();
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $headers = $this->getRequestHeaders();
            $headers['Authorization'] = 'Bearer ' . $nimbblToken['token'];
            $requestBody = (strtolower($method) === 'post') ? json_encode($data) : $data;
            error_log(__FILE__ . ": NimbblRequest::request ENDPOINT: $endpoint" . PHP_EOL);
            error_log(__FILE__ . ": NimbblRequest::request FULL REQUEST: " . print_r([
                'method' => $method,
                'endpoint' => $endpoint,
                'url' => $url,
                'headers' => $headers,
                'body' => $requestBody
            ], true) . PHP_EOL);
            $response = Requests::request($url, $headers, $requestBody, $method, $options);
            error_log(__FILE__ . ": NimbblRequest::request FULL RESPONSE: " . print_r([
                'status_code' => $response->status_code,
                'headers' => $response->headers,
                'body' => $response->body
            ], true) . PHP_EOL);
            $result = json_decode($response->body, true);
            error_log(__FILE__ . ": NimbblRequest::request API DECODED RESPONSE: " . print_r($result, true) . PHP_EOL);
            error_log(__FILE__ . ": NimbblRequest::request END" . PHP_EOL);
            return $result;
        } catch (Exception $e) {
            error_log(__FILE__ . ": NimbblRequest::request ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
            throw $e;
        }
    }

    public function universalRequest($method, $url, $data = array())
    {
        error_log(__FILE__ . ": NimbblRequest::universalRequest START" . PHP_EOL);
        try {
            $endpoint = $url;
            $url = NimbblApi::getFullUrl($url);
            $hooks = new Requests_Hooks();
            $hooks->register('curl.before_send', array($this, 'setCurlSslOpts'));
            $nimbblToken = self::generateToken();
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $headers = $this->getRequestHeaders();
            $headers['Authorization'] = 'Bearer ' . $nimbblToken['token'];
            $requestBody = (strtolower($method) === 'post') ? json_encode($data) : $data;
            error_log(__FILE__ . ": NimbblRequest::universalRequest ENDPOINT: $endpoint" . PHP_EOL);
            error_log(__FILE__ . ": NimbblRequest::universalRequest FULL REQUEST: " . print_r([
                'method' => $method,
                'endpoint' => $endpoint,
                'url' => $url,
                'headers' => $headers,
                'body' => $requestBody
            ], true) . PHP_EOL);
            $response = Requests::request($url, $headers, $requestBody, $method, $options);
            error_log(__FILE__ . ": NimbblRequest::universalRequest FULL RESPONSE: " . print_r([
                'status_code' => $response->status_code,
                'headers' => $response->headers,
                'body' => $response->body
            ], true) . PHP_EOL);
            $result = json_decode($response->body, true);
            error_log(__FILE__ . ": NimbblRequest::universalRequest API DECODED RESPONSE: " . print_r($result, true) . PHP_EOL);
            error_log(__FILE__ . ": NimbblRequest::universalRequest END" . PHP_EOL);
            return $result;
        } catch (Exception $e) {
            error_log(__FILE__ . ": NimbblRequest::universalRequest ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
            throw $e;
        }
    }

    public function setCurlSslOpts($curl)
    {
        error_log("NimbblRequest::setCurlSslOpts START");
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1);
        error_log("NimbblRequest::setCurlSslOpts END");
    }

    /**
     * Adds an additional header to all API requests
     * @param string $key   Header key
     * @param string $value Header value
     * @return null
     */
    public static function addHeader($key, $value)
    {
        error_log("NimbblRequest::addHeader START");
        self::$headers[$key] = $value;
        error_log("NimbblRequest::addHeader END");
    }

    /**
     * Returns all headers attached so far
     * @return array headers
     */
    public static function getHeaders()
    {
        error_log("NimbblRequest::getHeaders START");
        $headers = self::$headers;
        error_log("NimbblRequest::getHeaders END");
        return $headers;
    }

    /**
     * Process the statusCode of the response and throw exception if necessary
     * @param Object $response The response object returned by Requests
     */
    protected function checkErrors($response)
    {
        error_log("NimbblRequest::checkErrors START");
        $body = $response->body;
        $httpStatusCode = $response->status_code;

        try {
            $body = json_decode($response->body, true);
        } catch (Exception $e) {
            error_log("NimbblRequest::checkErrors ERROR: " . $e->getMessage());
            $this->throwServerError($body, $httpStatusCode);
        }

        if (($httpStatusCode < 200) or ($httpStatusCode >= 300)) {
            $this->processError($body, $httpStatusCode, $response);
        }
        error_log("NimbblRequest::checkErrors END");
    }

    protected function processError($body, $httpStatusCode, $response)
    {
        error_log("NimbblRequest::processError START");
        // TODO: FIXME based on the error structure coming from NimbblAPI.
        $code = $body['error']['code'];
        $description = $body['error']['description'];
        error_log("NimbblRequest::processError ERROR: $description ($code)");
        throw new NimbblError($description, $code, $httpStatusCode);
        error_log("NimbblRequest::processError END");
    }

    protected function throwServerError($body, $httpStatusCode)
    {
        error_log("NimbblRequest::throwServerError START");
        $description = "The server did not send back a well-formed response. Server response: $body";
        error_log("NimbblRequest::throwServerError ERROR: $description");
        throw new NimbblError($description, NimbblErrorCode::SERVER_ERROR, $httpStatusCode);
        error_log("NimbblRequest::throwServerError END");
    }

    public function getRequestHeaders()
    {
        error_log("NimbblRequest::getRequestHeaders START");
        $uaHeader = array(
            'User-Agent' => $this->constructUa()
        );

        $headers = array_merge(self::$headers, $uaHeader);

        error_log("NimbblRequest::getRequestHeaders END");
        return $headers;
    }

    protected function constructUa()
    {
        error_log("NimbblRequest::constructUa START");
        $ua = 'Nimbbl/v1 PHPSDK/' . NimbblApi::VERSION . ' PHP/' . phpversion();

        $ua .= ' ' . $this->getAppDetailsUa();

        error_log("NimbblRequest::constructUa END");
        return $ua;
    }

    protected function getAppDetailsUa()
    {
        error_log("NimbblRequest::getAppDetailsUa START");
        $appsDetails = NimbblApi::$appsDetails;

        $appsDetailsUa = '';

        foreach ($appsDetails as $app) {
            if ((isset($app['title'])) and (is_string($app['title']))) {
                $appUa = $app['title'];

                if ((isset($app['version'])) and (is_scalar($app['version']))) {
                    $appUa .= '/' . $app['version'];
                }

                $appsDetailsUa .= $appUa . ' ';
            }
        }

        error_log("NimbblRequest::getAppDetailsUa END");
        return $appsDetailsUa;
    }

    public function generateToken()
    {
        error_log("NimbblRequest::generateToken START");
        try {
            $nimbblSegment = new NimbblSegment();
            $tokenResponse = Requests::post(NimbblApi::getTokenEndpoint(), ['Content-Type' => 'application/json'], json_encode(['access_key' => NimbblApi::getKey(), 'access_secret' => NimbblApi::getSecret()]));
            $tokenResponseBody = json_decode($tokenResponse->body, true);
            
            if (key_exists('error', $tokenResponseBody)) {
                error_log('['.date("Y-m-d H:i:s").'] [ERROR] => Generate Token failed due to '.$tokenResponseBody['error']['nimbbl_error_code']);
            }
            error_log("NimbblRequest::generateToken END");
            return $tokenResponseBody;
        } catch (Exception $e) {
            error_log("NimbblRequest::generateToken ERROR: " . $e->getMessage());
            throw $e;
        }
    }

    // /**
    //  * Verifies error is in proper format. If not then
    //  * throws ServerErrorException
    //  *
    //  * @param  array $body
    //  * @param  int $httpStatusCode
    //  * @return void
    //  */
    // protected function verifyErrorFormat($body, $httpStatusCode)
    // {
    //     if (is_array($body) === false)
    //     {
    //         $this->throwServerError($body, $httpStatusCode);
    //     }

    //     if ((isset($body['error']) === false) or
    //         (isset($body['error']['code']) === false))
    //     {
    //         $this->throwServerError($body, $httpStatusCode);
    //     }

    //     $code = $body['error']['code'];

    //     if (Errors\ErrorCode::exists($code) === false)
    //     {
    //         $this->throwServerError($body, $httpStatusCode);
    //     }
    // }
}
