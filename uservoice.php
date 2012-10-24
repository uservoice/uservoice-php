<?php
namespace UserVoice;

require_once('uservoice/client.php');

function generate_sso_token($subdomain_key, $sso_key, $user_hash, $valid_for = 300) {

    $salted = $sso_key . $subdomain_key;
    $hash = hash('sha1',$salted,true);
    $saltedHash = substr($hash,0,16);
    $iv = "OpenSSL for Ruby";

    if (!array_key_exists('expires', $user_hash)) {
        $user_hash['expires'] = gmdate('Y-m-d H:i:s', time()+$valid_for);
    }

    $data = json_encode($user_hash);


    for ($i = 0; $i < 16; $i++) {
        $data[$i] = $data[$i] ^ $iv[$i];
    }

    $pad = 16 - (strlen($data) % 16);
    $data = $data . str_repeat(chr($pad), $pad);

    $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128,'','cbc','');
    mcrypt_generic_init($cipher, $saltedHash, $iv);
    $encryptedData = mcrypt_generic($cipher,$data);
    mcrypt_generic_deinit($cipher);

    $encryptedData = base64_encode($encryptedData);

    return urlencode($encryptedData);
}

?>
