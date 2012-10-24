<?php
require_once('simpletest/autorun.php');
require_once('lib/spyc.php');
require_once('uservoice.php');

class ClientTest extends UnitTestCase {
    function setUp() {
        $config = Spyc::YAMLLoad('test/config.yml');
        if (!(is_array($config) && isset($config['subdomain_name']) &&
                isset($config['api_key']) &&
                isset($config['api_secret']))) {
            print "Copy test/config.yml.templ to test/config.yml, and ";
            print "fill in the subdomain_name, api_key and api_secret.\n";
            exit(1);
        }
        $this->client = new \UserVoice\Client(
                            $config['subdomain_name'],
                            $config['api_key'],
                            $config['api_secret']);
    }

    function testAss() {
        $users = $this->client->get("/api/v1/users");
        $this->assertEqual(count($users), 10);
    }
}
?>