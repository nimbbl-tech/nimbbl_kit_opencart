<?php

namespace Nimbbl\Api;

class NimbblUtil
{
    const SHA256 = 'sha256';

    public function verifyPaymentSignature($attributes, $orderAmount)
    {
        error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature START" . PHP_EOL);
        try {
            error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature INPUT: attributes = " . print_r($attributes, true) . ", orderAmount = $orderAmount" . PHP_EOL);
            $actualSignature = $attributes['transaction']['signature'];
            $nimbbl_transaction_id = $attributes['nimbbl_transaction_id'];
            $orderId = $attributes['order']['invoice_id'];
            $signature_string = "";
            if ($attributes['transaction']['signature_version'] != null && $attributes['transaction']['signature_version'] === 'v3') {
                $amount = $this->formatAmount($attributes['transaction']['transaction_amount']);
                $signature_string = $orderId . '|' . $nimbbl_transaction_id . '|' . $amount . '|' . $attributes['transaction']['transaction_currency'] . '|' . $attributes['transaction']['status'] . '|' . $attributes['transaction']['transaction_type'];
            } else {
                $amount = sprintf("%.2f", $orderAmount);
                $signature_string = $orderId . '|' . $nimbbl_transaction_id . '|' . $amount . '|' . $attributes['transaction']['transaction_currency'];
            }
            $secret = NimbblApi::getSecret();
            $maskedSecret = substr($secret, 0, 4) . str_repeat('*', max(0, strlen($secret) - 8)) . substr($secret, -4);
            error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature SIGNATURE STRING: $signature_string" . PHP_EOL);
            error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature SECRET (masked): $maskedSecret" . PHP_EOL);
            error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature ACTUAL SIGNATURE: $actualSignature" . PHP_EOL);
            $result = $this->verifySignature($signature_string, $actualSignature, $secret, $attributes);
            error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature RESULT: " . var_export($result, true) . PHP_EOL);
            error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature END" . PHP_EOL);
            return $result;
        } catch (\Exception $e) {
            error_log(__FILE__ . ": NimbblUtil::verifyPaymentSignature ERROR: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
            throw $e;
        }
    }

    public function verifySignature($payload, $actualSignature, $secret, $attributes)
    {
        error_log(__FILE__ . ": NimbblUtil::verifySignature START" . PHP_EOL);
        error_log(__FILE__ . ": NimbblUtil::verifySignature PAYLOAD: $payload" . PHP_EOL);
        error_log(__FILE__ . ": NimbblUtil::verifySignature SECRET (masked): " . substr($secret, 0, 4) . str_repeat('*', max(0, strlen($secret) - 8)) . substr($secret, -4) . PHP_EOL);
        error_log(__FILE__ . ": NimbblUtil::verifySignature ACTUAL SIGNATURE: $actualSignature" . PHP_EOL);
        $expectedSignature = hash_hmac(self::SHA256, $payload, $secret);
        error_log(__FILE__ . ": NimbblUtil::verifySignature EXPECTED SIGNATURE: $expectedSignature" . PHP_EOL);
        if (function_exists('hash_equals')) {
            $verified = hash_equals($expectedSignature, $actualSignature);
        } else {
            $verified = $this->hashEquals($expectedSignature, $actualSignature);
        }
        error_log(__FILE__ . ": NimbblUtil::verifySignature RESULT: " . var_export($verified, true) . PHP_EOL);
        error_log(__FILE__ . ": NimbblUtil::verifySignature END" . PHP_EOL);
        return $verified;
    }

    public function formatAmount($amount){
        error_log(__FILE__ . ": NimbblUtil::formatAmount START" . PHP_EOL);
        $totalAmount = "";
        $inp = (string)$amount;
        $inp = str_replace(',','', $inp);
        $array = explode('.', $inp);
        $totalAmount = $totalAmount.$array[0];
        if(sizeof($array) == 1){
            $totalAmount = $totalAmount.".00";
        }
        else{
            $secondHalf = $array[1];
            $counter = 0;
            $totalAmount .=".";
            foreach(str_split($secondHalf) as $char){
                $counter++;
                $totalAmount .= $char;
                if($counter == 2){
                    break;
                }
            }
            if(strlen($secondHalf) == 1){
                $totalAmount .="0";
            }
        }
        error_log(__FILE__ . ": NimbblUtil::formatAmount END" . PHP_EOL);
        return $totalAmount;
    }

    private function hashEquals($expectedSignature, $actualSignature)
    {
        if (strlen($expectedSignature) === strlen($actualSignature)) {
            $res = $expectedSignature ^ $actualSignature;
            $return = 0;
            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $return |= ord($res[$i]);
            }
            return ($return === 0);
        }
        return false;
    }
}
