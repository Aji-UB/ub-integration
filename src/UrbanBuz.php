<?php

namespace UrbanBuz\API;

//use libphonenumber\PhoneNumberUtil;
//use libphonenumber\PhoneNumberFormat;
//use libphonenumber\NumberParseException;

class UrbanBuz {

    private $_version = '3.1';
    private $_prefix = 'oauth_';
    private $_signature_method = 'SHA1';
    private $_url;
    private $_key;
    private $_secret;

    public function __construct($base, $key, $secret) {
        $this->_url = $base;
        $this->_key = $key;
        $this->_secret = $secret;
    }
    
    
    /*
     *   Return Version
     */
    

    public function version() {
        return $this->_version;
    }

    
    
    /*
     *   Prepare Authentication Headers
     */
    
    
    private function prepareAuth($method, $url, $args = []) {
        $url = str_replace('https://', 'http://', $url);
        $base_string = strtoupper($method) . '&' . urlencode(substr($url, 0, strpos($url, '?')));

        $oauth_headers = [];

        //$oauth_headers[$this->_prefix . 'nonce'] = bin2hex(random_bytes(16));
        $oauth_headers[$this->_prefix.'nonce'] = str_pad(mt_rand(0, 99999999), 16, STR_PAD_LEFT);
        $oauth_headers[$this->_prefix . 'timestamp'] = '' . time();
        $oauth_headers[$this->_prefix . 'key'] = $this->_key;
        // $oauth_headers[$this->_prefix . 'signature_method'] = 'HMAC-' . $this->_signature_method;
        // $oauth_headers[$this->_prefix . 'kit'] = 'php-ub3-' . $this->_version;

        $all_args = array_merge($args, $oauth_headers);
        ksort($all_args);

        $base_string .= '&' . urlencode(http_build_query($all_args, '', '&'));
        // echo $base_string . PHP_EOL;
        $signature = hash_hmac($this->_signature_method, $base_string, $this->_secret);
        // echo $signature . PHP_EOL;
        $oauth_headers[$this->_prefix . 'signature'] = $signature;

        $headers = [];
        foreach ($oauth_headers as $key => $val) {
            $headers[] = $key . ': ' . $val;
        }

        return $headers;
    }
    
    
    /*
     *   Do Curl Request
     */
    
    

    public function call($method, $call, $header_params = [], $query_params = [], $args = []) {
        $url = strtolower($this->_url) . $call . '?' . http_build_query($query_params);
        $response = null;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if (in_array(strtoupper($method), ['POST', 'PUT'])) {
            curl_setopt($curl, CURLOPT_POST, count($args));
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($args));
        }
        $headers = array_merge(['Accept: application/json', 'Content-Type: application/json'], $this->prepareAuth($method, $url, $query_params), $header_params);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $result = substr($response, $header_size);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        $unserialized_response = @unserialize($response);
        return $unserialized_response ? $unserialized_response : $response;
    }

    /*
     *   Utils & Logging
     */

    
    
    /*
     *   Validate Phone
     */
    
    
    public static function validatePhone($phone) {
        $util = PhoneNumberUtil::getInstance();
        try {
            $proto = $util->parse('+' . $phone, '');
            if (!$util->isValidNumber($proto))
                return null;
            $formatted = $util->format($proto, PhoneNumberFormat::INTERNATIONAL);
            return preg_replace(['/ /', '/\+/'], '', $formatted);
        } catch (NumberParseException $e) {
            return null;
        }
    }

    
    
    /*
     *   Validate Email
     */
    
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    
    
    /*
     *   Split name
     */
    
    
    
    public static function splitName($name) {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim(preg_replace('#' . $last_name . '#', '', $name));
        return array($first_name, $last_name);
    }

    
    
    /*
     *   Return Float
     */
    
    
    
    public static function getfloat($v) {
        $precision = 2;
        return number_format(floatval($v), $precision, '.', '');
    }

    
    
    /*
     *   Log the given String
     */
    
    public static function log($str,$write = false) {
        $timestamp = self::currentTimestamp();
        $content =  sprintf("%s - %s%s", $timestamp, $str, PHP_EOL);
        if($write){
            $month = date('Y-M');
            $fileLocation =  __DIR__ . "/../logs/{$month}.log";
            $mode = (!file_exists($fileLocation)) ? 'w':'a';
            $file = fopen($fileLocation,$mode);
            fwrite($file,$content);
            fclose($file);
        }else{
            echo $content;
        }
    }

    
    
    
    /*
     *   Return Current Time Stamp
     */
    
    
    private static function currentTimestamp() {
        $t = microtime(true);
        $micro = sprintf('%06d', ($t - floor($t)) * 1000000);
        $d = new \DateTime(date('Y-m-d H:i:s.' . $micro, $t));
        return $d->format('Y-m-d H:i:s.u');
    }

    
    
    
    function filter_array_by_value($array, $index, $value) {
        $newarray = [];
        if (!is_array($value)) {
            if (is_array($array) && count($array) > 0) {
                foreach (array_keys($array) as $key) {
                    if (isset($array[$key][$index])) {
                        $temp[$key] = $array[$key][$index];
                        if (strtolower($temp[$key]) == strtolower($value)) {
                            $newarray[$key] = $array[$key];
                        }
                    }
                }
            }
        } else {
            if (is_array($array) && count($array) > 0) {
                foreach (array_keys($array) as $key) {
                    $temp[$key] = $array[$key][$index];
                    if (in_array($temp[$key], $value)) {
                        $newarray[$key] = $array[$key];
                    }
                }
            }
        }

        return $newarray;
    }
    
	
	
    function getDate($date,$modify = ''){
        $newDate = new \DateTime($date);
        if($modify !=''){
            $newDate->modify("+$modify minutes");
        }
        return $newDate->format('Y-m-d H:i:s');
    }

}
