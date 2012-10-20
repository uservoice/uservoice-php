<?php
class UserVoiceHelper
{
	const OAUTH_CONSUMER_KEY = "<Consumer Key>";
	const OAUTH_CONSUMER_SECRET = "<Consumer Secret>";

	const ADMIN_OAUTH_TOKEN = "<Get from Authorize API>";
	const ADMIN_OAUTH_SECRET = "<Get from Authorize API>";

	const SUBDOMAIN = "acme";


	public static function CreateTicketNote($ticket_id,$msg)
	{
		$oauthObject=self::GetOauth(true);

		$res = self::MakeRequest($oauthObject,'https://'.self::SUBDOMAIN.'.uservoice.com/api/v1/tickets/'.$ticket_id.'/notes.json',array("note[text]" => $msg),"POST");

		return $res;
	}

	public static function GetTicketsBySearch($query)
	{
		$oauthObject=self::GetOauth();

		$res = self::MakeRequest($oauthObject, 'http://'.self::SUBDOMAIN.'.uservoice.com/api/v1/tickets/search.json', array('query' => $query));

		return $res->tickets;
	}

	public static function AuthorizeBySSO($user_data)
	{
		$oauthObject=self::GetOauth();

		$token = self::MakeRequest($oauthObject, 'http://'.self::SUBDOMAIN.'.uservoice.com/api/v1/oauth/request_token.json', array());

		$sso=self::GetSSOToken($user_data);

		$oauthObject=self::GetOauth();
		$res = self::MakeRequest($oauthObject, 'http://'.self::SUBDOMAIN.'.uservoice.com/api/v1/oauth/authorize.json', array(
			'sso' => urlencode($sso),
			'guid'=> $user_data['guid'],
			'scheme'=> 'aes_cbc_128',
			'request_token' => $token->token->oauth_token
		));

		return $res;
	}

	public static function GetOauth($use_admin=false)
	{
		$oauthObject = new OAuthSimple(self::OAUTH_CONSUMER_KEY,self::OAUTH_CONSUMER_SECRET);
		/*$oauthObject->sig = array(
			'consumer_key'     => self::OAUTH_CONSUMER_KEY,
			'shared_secret'    => self::OAUTH_CONSUMER_SECRET);
		*/

		if($use_admin)
		{
			$oauthObject->setTokensAndSecrets(array(
				"access_token"=>self::ADMIN_OAUTH_TOKEN,
				"access_secret"=> self::ADMIN_OAUTH_SECRET
			));
		}

		return $oauthObject;
	}

	public static function MakeRequest($oauthObject, $apiUrl, $params, $action="GET")
	{
		$oauthObject->setAction($action);

		$result = $oauthObject->sign(array(
			'path'      => $apiUrl,
			'parameters'=> $params,
			'signatures'=> $oauthObject->sig,
			'action'=>$action
		));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $result['signed_url']);

		if($action=="POST")
		{
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
		}

		$r = curl_exec($ch);
		curl_close($ch);

		$res=json_decode($r);

		return $res;
	}

}
?>
