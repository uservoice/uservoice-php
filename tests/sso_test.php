<?php
require_once(dirname(__FILE__) . '/test_helper.php');
require_once(dirname(__FILE__) . '/../src/UserVoice/SSO.php');

class SsoTest extends UnitTestCase {

    function testShouldGet10FirstUsers() {
        $config = readConfiguration('test/config.yml');

        $token = \UserVoice\SSO::generate_token($config['subdomain_name'], $config['sso_key'], array(
            'email' => 'regular@example.com'
        ));

        // Try it out here:
        // echo $config['protocol'] . '://' . $config['subdomain_name'] . '.' . $config['uservoice_domain'] . '?sso='.$token;
        $this->assertTrue(strlen($token) > 0);
    }
}
?>