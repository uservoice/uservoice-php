<?php
require_once('simpletest/autorun.php');
require_once('lib/spyc.php');
require_once('uservoice.php');

function readConfiguration($file) {
    $config = Spyc::YAMLLoad('test/config.yml');
    if (!(is_array($config) && 
            isset($config['subdomain_name']) && strlen($config['subdomain_name']) &&
            isset($config['api_key']) && strlen($config['api_key']) &&
            isset($config['api_secret']) && strlen($config['api_secret'])
    )) {
        print "Copy test/config.yml.templ to test/config.yml, and ";
        print "fill in the subdomain_name, api_key and api_secret.\n";
        exit(1);
    }
    return $config;
}
?>
