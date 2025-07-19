<?php

namespace Nimbbl\Api;

use Exception;
use JsonSerializable;


class NimbblOrder extends NimbblEntity implements JsonSerializable
{
    public $token; // Add this line to declare the token property

    public function entityClass()
    {
        return 'Nimbbl\\Api\\NimbblOrder';
    }

    /**
     *  @param $id Customer id description
     */

    public function retrieveMany($options = array())
    {
        error_log(__FILE__ . ": NimbblOrder::retrieveMany START" . PHP_EOL);
        $f = base64_encode($this->buildHttpQuery($options));
        $nimbblRequest = new NimbblRequest();
        $manyEntities = $nimbblRequest->request('GET', 'orders/many?f=' . $f . '&pt=no');
        $users = array();
        if (is_array($manyEntities) && isset($manyEntities['items']) && is_array($manyEntities['items'])) {
            foreach ($manyEntities['items'] as $idx => $oneEntity) {
                $users[] = $this->fillOne($oneEntity);
            }
        }
        error_log(__FILE__ . ": NimbblOrder::retrieveMany END" . PHP_EOL);
        return [
            'items' => $users,
            'meta' => $manyEntities['_meta'] ?? []
        ];
    }

    public function create($attributes = array(), $apiVersion = 'v3')
    {
        error_log(__FILE__ . ": NimbblOrder::create START" . PHP_EOL);
        try {
            $endpoint = $apiVersion.'/create-order';
            $fullUrl = \Nimbbl\Api\NimbblApi::getFullUrl($endpoint);
            $nimbblRequest = new NimbblRequest();
            $headers = $nimbblRequest->getRequestHeaders();
            $tokenArr = $nimbblRequest->generateToken();
            $headers['Authorization'] = 'Bearer ' . $tokenArr['token'];
            $requestBody = json_encode($attributes);
            error_log(__FILE__ . ": NimbblOrder::create ENDPOINT: $endpoint" . PHP_EOL);
            error_log(__FILE__ . ": NimbblOrder::create FULL REQUEST: " . print_r([
                'method' => 'POST',
                'endpoint' => $endpoint,
                'url' => $fullUrl,
                'headers' => $headers,
                'body' => $requestBody
            ], true) . PHP_EOL);
            // Only make one API request and log the response
            $hooks = new \Requests_Hooks();
            $hooks->register('curl.before_send', array($nimbblRequest, 'setCurlSslOpts'));
            $options = [
                'hook' => $hooks,
                'timeout' => 60,
            ];
            $rawResponse = \Requests::request($fullUrl, $headers, $requestBody, 'POST', $options);
            error_log(__FILE__ . ": NimbblOrder::create FULL RESPONSE: " . print_r([
                'status_code' => $rawResponse->status_code,
                'headers' => $rawResponse->headers,
                'body' => $rawResponse->body
            ], true) . PHP_EOL);
            // Log the raw JSON response for debugging
            error_log(__FILE__ . ": NimbblOrder::create RAW JSON RESPONSE: " . $rawResponse->body . PHP_EOL);
            $createdEntity = json_decode($rawResponse->body, true);
            $newCreatedEntity = new NimbblOrder();
            if (is_array($createdEntity) && isset($createdEntity['token'])) {
                // Set all top-level fields as direct properties for easy access
                foreach ($createdEntity as $key => $value) {
                    $newCreatedEntity->$key = $value;
                }
                $newCreatedEntity->attributes = $createdEntity;
            } elseif (is_array($createdEntity) && isset($createdEntity['order']) && is_array($createdEntity['order'])) {
                $attributes = $createdEntity['order'];
                $newCreatedEntity->attributes = $attributes;
                if (isset($attributes['token'])) {
                    $newCreatedEntity->token = $attributes['token'];
                }
            } elseif (is_array($createdEntity) && isset($createdEntity['error'])) {
                $newCreatedEntity->error = $createdEntity['error'];
                error_log(__FILE__ . ": NimbblOrder::create ERROR: " . print_r($createdEntity['error'], true) . PHP_EOL);
            } else {
                error_log(__FILE__ . ": NimbblOrder::create ERROR: Unexpected API response: " . print_r($createdEntity, true) . PHP_EOL);
            }
            error_log(__FILE__ . ": NimbblOrder::create END" . print_r($newCreatedEntity, true). PHP_EOL);
            return $newCreatedEntity;
        } catch (\Exception $e) {
            error_log(__FILE__ . ": NimbblOrder::create ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
            throw $e;
        }
    }

    public function retrieveOne($id)
    {
        error_log(__FILE__ . ": NimbblOrder::retrieveOne START" . PHP_EOL);
        $nimbblRequest = new NimbblRequest();
        $oneEntity = $nimbblRequest->request('GET', 'v2/get-order/' . $id);
        $loadedEntity = $this->fillOne($oneEntity);
        $this->attributes = $loadedEntity->attributes;
        $this->error = $loadedEntity->error;
        error_log(__FILE__ . ": NimbblOrder::retrieveOne END" . PHP_EOL);
        return $this;
    }

    public function edit($attributes = null)
    {
        error_log(__FILE__ . ": NimbblOrder::edit START" . PHP_EOL);
        throw new Exception("Unsupported operation.");
        error_log(__FILE__ . ": NimbblOrder::edit END" . PHP_EOL);
    }

    public function getOrderByInvoiceId($id, $apiVersion = 'v3'){
        error_log(__FILE__ . ": NimbblOrder::getOrderByInvoiceId START" . PHP_EOL);
        $nimbblrequest = new NimbblRequest();
        $response = $nimbblrequest->request('GET', $apiVersion.'/order?invoice_id='.$id);
        if (is_array($response) && key_exists('error', $response)){
            error_log('['.date("Y-m-d H:i:s").'] [ERROR] => Get Order By Invoice Id failed due to '.($response['error']['nimbbl_error_code'] ?? 'unknown'));
            error_log(__FILE__ . ": NimbblOrder::getOrderByInvoiceId END" . PHP_EOL);
            return (array) $response['error'];
        }
        error_log(__FILE__ . ": NimbblOrder::getOrderByInvoiceId END" . PHP_EOL);
        return $response;
    }

    public function getOrderByOrderId($id, $apiVersion = 'v3'){
        error_log(__FILE__ . ": NimbblOrder::getOrderByOrderId START" . PHP_EOL);
        $nimbblrequest = new NimbblRequest();
        $response = $nimbblrequest->request('GET', $apiVersion.'/order?order_id='.$id);
        if (is_array($response) && key_exists('error', $response)){
            error_log('['.date("Y-m-d H:i:s").'] [ERROR] => Get Order By Order Id failed due to '.($response['error']['nimbbl_error_code'] ?? 'unknown'));
            error_log(__FILE__ . ": NimbblOrder::getOrderByOrderId END" . PHP_EOL);
            return (array) $response['error'];
        }
        error_log(__FILE__ . ": NimbblOrder::getOrderByOrderId END" . PHP_EOL);
        return $response;
    }
}