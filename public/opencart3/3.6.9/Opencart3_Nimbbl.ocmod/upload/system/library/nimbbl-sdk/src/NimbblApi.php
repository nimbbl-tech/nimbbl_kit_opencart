<?php

namespace Nimbbl\Api;

class NimbblApi
{
    protected static $baseUrl = 'https://api.nimbbl.tech/api/';

    protected static $apiVersion = 'v3';

    protected static $key;

    protected static $secret;

    protected static $merchantId;

    /*
     * App info is to store the Plugin/integration
     * information
     */
    // public static $appsDetails = array();

    const VERSION = '3.0.0-vdc';

    /*
     * App info is to store the Plugin/integration
     * information
     */
    public static $appsDetails = [];

    /**
     * @param string $key
     * @param string $secret
     */
    public function __construct($key, $secret, $url=null, $apiVersion = null)
    {
        error_log(__FILE__ . ": NimbblApi::__construct START" . PHP_EOL);
        self::$key = $key;
        self::$secret = $secret;
        if($url != null)
            self::$baseUrl = $url;
        if($apiVersion != null)
            self::$apiVersion = $apiVersion;
        error_log(__FILE__ . ": NimbblApi::__construct END" . PHP_EOL);
    }

    /*
     *  Set Headers
     */
    public function setHeader($header, $value)
    {
        error_log(__FILE__ . ": NimbblApi::setHeader START" . PHP_EOL);
        \Nimbbl\Api\NimbblRequest::addHeader($header, $value);
        error_log(__FILE__ . ": NimbblApi::setHeader END" . PHP_EOL);
    }

    // public function setAppDetails($title, $version = null)
    // {
    //     $app = array(
    //         'title' => $title,
    //         'version' => $version
    //     );

    //     array_push(self::$appsDetails, $app);
    // }

    // public function getAppsDetails()
    // {
    //     return self::$appsDetails;
    // }

    // public function setBaseUrl($baseUrl)
    // {
    //     self::$baseUrl = $baseUrl;
    // }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        error_log(__FILE__ . ": NimbblApi::__get START" . PHP_EOL);
        $className = __NAMESPACE__ . '\\Nimbbl' . ucwords($name);

        $entity = new $className();

        error_log(__FILE__ . ": NimbblApi::__get END" . PHP_EOL);
        return $entity;
    }

    public static function getBaseUrl()
    {
        error_log(__FILE__ . ": NimbblApi::getBaseUrl START" . PHP_EOL);
        $url = self::$baseUrl;
        // Set default if not set or empty
        if (empty($url)) {
            $url = 'https://api.nimbbl.tech/api/';
            self::$baseUrl = $url;
        }
        error_log(__FILE__ . ": NimbblApi::getBaseUrl value: $url" . PHP_EOL);
        error_log(__FILE__ . ": NimbblApi::getBaseUrl END" . PHP_EOL);
        return $url;
    }

    public static function getAPIVersion() {
        error_log(__FILE__ . ": NimbblApi::getAPIVersion START" . PHP_EOL);
        $ver = self::$apiVersion;
        error_log(__FILE__ . ": NimbblApi::getAPIVersion END" . PHP_EOL);
        return $ver;
    }

    public static function getKey()
    {
        error_log(__FILE__ . ": NimbblApi::getKey START" . PHP_EOL);
        $key = self::$key;
        error_log(__FILE__ . ": NimbblApi::getKey END" . PHP_EOL);
        return $key;
    }

    public static function getSecret()
    {
        error_log(__FILE__ . ": NimbblApi::getSecret START" . PHP_EOL);
        $secret = self::$secret;
        error_log(__FILE__ . ": NimbblApi::getSecret END" . PHP_EOL);
        return $secret;
    }

    public static function getTokenEndpoint()
    {
        error_log(__FILE__ . ": NimbblApi::getTokenEndpoint START" . PHP_EOL);
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        $apiVersion = ltrim(self::getAPIVersion(), '/');
        $endpoint = $baseUrl . '/' . $apiVersion . '/generate-token';
        error_log(__FILE__ . ": NimbblApi::getTokenEndpoint value: $endpoint" . PHP_EOL);
        error_log(__FILE__ . ": NimbblApi::getTokenEndpoint END" . PHP_EOL);
        return $endpoint;
    }

    public static function getFullUrl($relativeUrl)
    {
        error_log(__FILE__ . ": NimbblApi::getFullUrl START" . PHP_EOL);
        $baseUrl = rtrim(self::getBaseUrl(), '/');
        $relativeUrl = ltrim($relativeUrl, '/');
        $url = $baseUrl . '/' . $relativeUrl;
        error_log(__FILE__ . ": NimbblApi::getFullUrl END" . PHP_EOL);
        return $url;
    }

    public static function setMerchantId($merchantId){
        error_log(__FILE__ . ": NimbblApi::setMerchantId START" . PHP_EOL);
        self::$merchantId = $merchantId;
        error_log(__FILE__ . ": NimbblApi::setMerchantId END" . PHP_EOL);
        return true;
    }

    public static function getMerchantId(){
        error_log(__FILE__ . ": NimbblApi::getMerchantId START" . PHP_EOL);
        $id = self::$merchantId;
        error_log(__FILE__ . ": NimbblApi::getMerchantId END" . PHP_EOL);
        return $id;
    }
}
