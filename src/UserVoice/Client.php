<?php
namespace UserVoice;

class APIError extends \Exception { }
class ApplicationError extends APIError { }
class NotFound extends APIError { }
class Unauthorized extends APIError { }

class Client
{
    public $token;
    public $secret;

    private $subdomain;
    private $api_key;
    private $api_secret;
    private $opts;

    function __construct($subdomain, $api_key, $api_secret, $opts=array()) {
        if (is_array($api_secret)) {
            $opts = $api_secret;
            $api_secret = null;
        }
        $this->subdomain = $subdomain;
        if (!isset($opts['uservoice_domain'])) {
            $opts['uservoice_domain'] = 'uservoice.com';
        }
        if (!isset($opts['protocol'])) {
            $opts['protocol'] = 'https';
        }
        if (!isset($opts['oauth_token'])) {
            $opts['oauth_token'] = '';
        }
        if (!isset($opts['oauth_token_secret'])) {
            $opts['oauth_token_secret'] = '';
        }
        if (isset($opts['callback'])) {
            $this->callback = $opts['callback'];
        }
        $this->opts = $opts;
        $this->api_url = "${opts['protocol']}://$subdomain.${opts['uservoice_domain']}";
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->token = $opts['oauth_token'];
        $this->secret = $opts['oauth_token_secret'];
        $this->access_token = new \OAuth($api_key, $api_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_AUTHORIZATION); 
        $this->access_token->setToken($this->token, $this->secret);
        $this->default_headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json', 'API-Client' => '0.0.8');
    }

    function authorize_url() {
        $this->request_token = $this->get_request_token();
        return $this->api_url . '/oauth/authorize?oauth_token=' . $this->request_token->token;
    }

    function request($method, $path, $params=null) {
        try {
            $method = strtoupper($method);
            $url = $this->api_url . $path;
            //print("Making request to $url\n");
            $body = null;
            if ($params) {
                $body = json_encode($params);
            }
            $result = $this->access_token->fetch($url, $body, $method, $this->default_headers);
            $json = $this->access_token->getLastResponse();
            //print("$json\n");
            $attrs = json_decode($json, true);
            return $attrs;
        } catch(\OAuthException $oauthException) {
            $json_error = json_decode($oauthException->lastResponse, true);
            if (isset($json_error['errors']) && isset($json_error['errors']['type'])) {
                $msg = $json_error['errors']['message'];

                switch ($json_error['errors']['type']) {
                    case 'application_error': throw new ApplicationError($msg);
                    case 'record_not_found': throw new NotFound($msg);
                    case 'unauthorized': throw new Unauthorized($msg);
                    default: break;
                }
            }
            throw new APIError($oauthException->lastResponse);
        }
    }
    function get($path) { return $this->request('get', $path); }
    function delete($path) { return $this->request('delete', $path); }
    function post($path, $params) { return $this->request('post', $path, $params); }
    function put($path, $params) { return $this->request('put', $path, $params); }

    function get_collection($path, $opts=array()) {
        return new Collection($this, $path, $opts);
    }
    function get_object($path, $key=null) {
        $result = $this->get($path);
        if ($key !== null) {
            if (isset($result[$key])) {
                return $result[$key];
            } else {
                throw new NotFound('The resource "' . $path . "' does not have '$key'.");
            }
        } elseif (count($result) !== 1) {
            throw new NotFound('The resource "' . $path . "' is not a single object.");
        }
        return array_pop($result);
    }
    public function get_request_token() {
        try {
            $url = $this->api_url . '/oauth/request_token';
            $result = FALSE;
            if (isset($this->callback)) {
                $result = $this->access_token->getRequestToken($url, $this->callback);
            } else {
                $result = $this->access_token->getRequestToken($url);
            }
            if (is_array($result)) {
                return $this->login_with_access_token(
                           $result['oauth_token'],
                           $result['oauth_token_secret']);
            } else {
                throw new Unauthorized($this->access_token->getLastResponse());
            }
        } catch(\OAuthException $oauthException) {
            throw new Unauthorized($oauthException->lastResponse);
        }
    }

    public function login_with_verifier($verifier) {
        try {
            $token = null;
            $request_token_client = $this->access_token;
            if ($request_token_client->fetch($this->api_url . '/oauth/access_token', array('oauth_verifier' => $verifier), 'POST')) {
                $result = $request_token_client->getLastResponse(); 
                parse_str($result, $token);
            }
            if (is_array($token)) {
                return $this->login_with_access_token(
                           $token['oauth_token'],
                           $token['oauth_token_secret']);
            } else {
                throw new Unauthorized('Could not get Access Token');
            }
        } catch(\OAuthException $oauthException) {
            throw new Unauthorized($oauthException->lastResponse);
        }
    }

    public function login_as_owner() {
        $result = $this->post('/api/v1/users/login_as_owner', array(
            'request_token' => $this->get_request_token()->token
        ));
        if (is_array($result) && isset($result['token'])) {
            return $this->login_with_access_token(
                $result['token']['oauth_token'],
                $result['token']['oauth_token_secret']);
        } else {
            throw new Unauthorized($this->access_token->getLastResponse());
        }
    }
    public function login_as($email) {
        $result = $this->post('/api/v1/users/login_as', array(
            'request_token' => $this->get_request_token()->token,
            'user' => array('email' => $email)
        ));
        if (is_array($result) && isset($result['token'])) {
            return $this->login_with_access_token(
                $result['token']['oauth_token'],
                $result['token']['oauth_token_secret']);
        } else {
            throw new Unauthorized($this->access_token->getLastResponse());
        }
    }
    public function login_with_access_token($token, $secret) {
        $opts = $this->opts;
        $opts['oauth_token'] = $token;
        $opts['oauth_token_secret'] = $secret;
        return new Client($this->subdomain, $this->api_key, $this->api_secret, $opts);
    }
}
?>
