<?php

/**
 * Helper class for Meli Payamak API
 * Based on https://github.com/Melipayamak/melipayamak-php
 */
class RWL_Melipayamak
{

    protected $username;
    protected $password;
    protected $api_url = 'https://rest.payamak-panel.com/api/SendSMS/';

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Send SMS using Shared Service (Pattern) via BaseServiceNumber
     * Use this when you have a pattern code (BodyId)
     */
    public function send_by_base_number($mobile, $text, $bodyId)
    {
        $url = $this->api_url . 'BaseServiceNumber';
        $data = array(
            'username' => $this->username,
            'password' => $this->password,
            'text' => $text,
            'to' => $mobile,
            'bodyId' => $bodyId
        );

        return $this->send_request($url, $data);
    }

    /**
     * Send normal SMS
     */
    public function send_sms($to, $from, $text, $isFlash = false)
    {
        $url = $this->api_url . 'SendSMS';
        $data = array(
            'username' => $this->username,
            'password' => $this->password,
            'to' => $to,
            'from' => $from,
            'text' => $text,
            'isFlash' => $isFlash
        );

        return $this->send_request($url, $data);
    }

    protected function send_request($url, $data)
    {
        $args = array(
            'body'        => json_encode($data),
            'headers'     => array(
                'Content-Type' => 'application/json',
            ),
            'timeout'     => 15,
            'blocking'    => true,
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return array('status' => false, 'message' => $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        // Meli Payamak usually returns RetStatus=1 for success in BaseService
        // And Value > 15 (length) for SendSMS success? Or RetStatus?
        // Let's standardise the return.

        if (isset($result['RetStatus']) && $result['RetStatus'] == 1) {
            return array('status' => true, 'response' => $result);
        }

        // For SendSMS, format might be different. 
        // Docs say SendSMS returns string ID or error.
        // But usually REST API returns JSON.

        return array('status' => false, 'response' => $result);
    }
}
