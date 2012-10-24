<?php
namespace UserVoice;

require_once('uservoice/exceptions.php');

class Client
{
    var $subdomain;
    var $api_key;
    var $api_secret;
    var $token;
    var $secret;

    function __construct($subdomain, $api_key, $api_secret) {
        $this->subdomain = $subdomain;
        $this->api_url = "https://$subdomain.uservoice.com";
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->token = "";
        $this->secret = "";
        $this->access_token = new \OAuth($api_key, $api_secret); 
        $this->access_token->setToken($this->token, $this->secret);
        $this->default_headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
    }

    function request($method, $path, $params='') {
        try {
            $method = strtoupper($method);
            $url = $this->api_url . $path;
            print("Making request to $url\n");
            $result = $this->access_token->fetch($url, array(), $method, $this->default_headers);
            var_dump($result);
            return $result['body'];
        } catch(\OAuthException $oauthException) {
            $json_error = json_decode($oauthException->lastResponse, true);
            if (isset($json_error['errors']) && isset($json_error['errors']['type'])) {
                switch ($json_error['errors']['type']) {
                    case 'application_error': throw new ApplicationError($json_error);
                    case 'record_not_found': throw new NotFound($json_error);
                    case 'unauthorized': throw new Unauthorized($json_error);
                    default: break;
                }
            }
            throw new APIError($json_error);
        }
    }
    function get($path) {
        return $this->request('get', $path);
    }
}
?>
