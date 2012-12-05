<?php
require_once(dirname(__FILE__) . '/test_helper.php');
require_once(dirname(__FILE__) . '/../src/UserVoice/Client.php');

class ClientTest extends UnitTestCase {

    function setUp() {
        $config = readConfiguration('test/config.yml');
        $this->client = new UserVoice\Client($config['subdomain_name'], $config['api_key'], $config['api_secret'], $config);
    }

    function testShouldGet10FirstUsers() {
        $result = $this->client->get("/api/v1/users");
        $this->assertEqual(count($result['users']), 10);
    }

    function testShouldGetNonPrivateForumAsUnsignedClient() {
        $config = readConfiguration('test/config.yml');
        $client = new UserVoice\Client($config['subdomain_name'], $config['api_key'], $config);
        $forums = $client->get_collection("/api/v1/forums", array('limit' => 1));
        $this->assertFalse($forums[0]['private']);
    }

    function testShouldGetCollectionOfUsers() {
        $users = $this->client->get_collection("/api/v1/users", array('limit' => 11));
        $this->assertEqual(count($users), 11);
    }

    function testShouldGetEmptyCollectionOfUsers() {
        $users = $this->client->get_collection("/api/v1/users", array('limit' => 0));
        $this->assertEqual(count($users), 0);
    }

    function testShouldLoopThroughCollectionOfUsers() {
        $users = $this->client->get_collection("/api/v1/users", array('limit' => 7));
        $names = array();
        foreach ($users as $user) {
            $names[] = $user['name'];
        }
        $this->assertEqual(count($names), 7);
    }

    function testShouldGetFirst3Users() {
        $users = $this->client->get_collection("/api/v1/users", array('limit' => 7));
        $names = array();
        for ($i = 0; $i < 3; $i++) {
            $id = $users[$i]['id'];
            $names[] = $id;
            $this->assertTrue(0 < $id);
        }
        $this->assertEqual(count($names), 3);
    }

    function testShouldBeAbleToGetAccessTokenAsOwner() {
        $owner = $this->client->login_as_owner();
        $user = $owner->get_object("/api/v1/users/current");
        $this->assertEqual($user['roles']['owner'], true);

        $regular = $owner->login_as('regular@example.com');

        // The owner access token still works
        $user = $owner->get_object("/api/v1/users/current");
        $this->assertEqual($user['roles']['owner'], true);

        // User should NOT be owner
        $user = $regular->get_object("/api/v1/users/current");
        $this->assertEqual($user['roles']['owner'], false);
    }

    function testShouldNotBeAbleToCreateKBArticleAsNobody () {
        try {
            $r = $this->client->post("/api/v1/articles", array(
                'article' => array( 'title' => 'good morning')
            ));
            $this->fail('Expected Unauthorized');
        } catch (\UserVoice\Unauthorized $e) {
            $this->pass();
        }
    }
}
?>