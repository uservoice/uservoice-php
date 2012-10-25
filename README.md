UserVoice PHP library for API connections
=========================================

This library allows you to easily:
* Generate SSO token for creating SSO users / logging them into UserVoice (http://uservoice.com).
* Do 3-legged and 2-legged UserVoice API calls safely without having to worry about the cryptographic details (unless you want).


Installation
============

Install PECL/OAuth for PHP5 by following the instructions here: http://www.php.net/manual/en/oauth.setup.php
For installing, you need PEAR: http://pear.php.net/manual/en/installation.getting.php

And add location of PEAR into your php.ini. Find the lines which begin with 'include\_path = ' and add the following line:
```php
include_path = ".:/usr/lib/php/pear"
```
Now you should have the command 'pecl' available, so run:
```sh
sudo pecl install oauth
```
The installation script will finally suggest you to add the following line to php.ini:
```php
extension=oauth.so
```
Now you should be good to go!

Examples
========

Prerequisites:

* The mcrypt and oauth need to be installed. Check installation instructions for oauth above.
* Place the following configuration parameters somewhere in your application:

```php
# Suppose your UserVoice site is at http://uservoice-subdomain.uservoice.com/
const USERVOICE_SUBDOMAIN = 'uservoice-subdomain';
const SSO_KEY = '982c88f2df72572859e8e23423eg87ed'; # Admin Console -> Settings -> General -> User Authentication

# Define an API client at: Admin Console -> Settings -> Channels -> API
const API_KEY = 'oQt2BaunWNuainc8BvZpAm';
const API_SECRET = '3yQMSoXBpAwuK3nYHR0wpY6opE341inL9a2HynGF2';
```

SSO-token generation using uservoice library
--------------------------------------------

SSO-token can be used to create sessions for SSO users. They are capable of synchronizing the user information from one system to another.
Generating the SSO token from SSO key and given uservoice subdomain can be done by calling UserVoice\\generate\_sso\_token method like this:

```php
<?php
    require_once('uservoice.php');

    $sso_token = UserVoice\generate_sso_token(USERVOICE_SUBDOMAIN, SSO_KEY, array(
        'display_name' => "John Doe",
        'email' => 'john.doe@example.com'
    ), 5*60); # the token will be valid for 5 minutes (5*60 seconds) by default

    echo 'https://' . USERVOICE_SUBDOMAIN . '.uservoice.com/?sso='.$sso_token."\n";
?>
```

Making API calls
----------------

You need to create an instance of UserVoice\\Client. Get API_KEY and API_SECRET for an API client which you can create 
from Admin Console. Go to Settings -> Channels -> API.

```php
<?

try {
    require_once('uservoice.php');

    $client = new \UserVoice\Client(USERVOICE_SUBDOMAIN, API_KEY, API_SECRET);

    # Get users of a subdomain (requires trusted client, but no user)
    $users = $client->get_collection("/api/v1/users");

    print "Subdomain \"" . USERVOICE_SUBDOMAIN . "\" has " . count($users) . " users.\n";

    foreach($users as $user) {
        print("User: \"${user['name']}\", Profile URL: ${user['url']}\n");
    }


    # Now, let's login as mailaddress@example.com, a regular user
    $regular_access_token = $client->login_as('mailaddress@example.com');

    # Example request #1: Get current user.
    $r = $regular_access_token->get("/api/v1/users/current");
    $user = $r['user'];

    print("User: \"${user['name']}\", Profile URL: ${user['url']}\n");

    # Login as account owner
    $owner_access_token = $client->login_as_owner();

    # Example request #2: Create a new private forum limited to only example.com email domain.
    $r = $owner_access_token->post("/api/v1/forums", array(
        'forum' => array(
            'name' => 'PHP Client Private Feedback',
            'private' => true,
            'allow_by_email_domain' => true,
            'allowed_email_domains' => array(
                array('domain' => 'example.com')
            )
        )
    ));
    $forum = $r['forum'];

    print("Forum \"${forum['name']}\" created! URL: ${forum['url']}\n");
} catch (UserVoice\Unauthorized $e) {
    # Thrown usually due to faulty tokens, untrusted client or if attempting
    # operations without Admin Privileges
    var_dump($e);
} catch (UserVoice\NotFound $e) {
    # Thrown when attempting an operation to a resource that does not exist
    var_dump($e);
}

?>
```

Verifying a UserVoice user
--------------------------

If you want to make calls on behalf of a user, but want to make sure he or she
actually owns certain email address in UserVoice, you need to use 3-Legged API
calls. Just pass your user an authorize link to click, so that user may grant
your site permission to access his or her data in UserVoice.

```python
import uservoice
CALLBACK_URL = 'http://localhost:3000/' # your site

client = uservoice.Client(USERVOICE_SUBDOMAIN, API_KEY, API_SECRET, callback=CALLBACK_URL)

# At this point you want to print/redirect to client.authorize_url in your application.
# Here we just output them as this is a command-line example.
print "1. Go to {url} and click \"Allow access\".".format(url=client.authorize_url())
print "2. Then type the oauth_verifier which is passed as a GET parameter to the callback URL:"

# In a web app we would get the oauth_verifier via a redirection to CALLBACK_URL.
# In this command-line example we just read it from stdin:
access_token = client.login_with_verifier(raw_input())

# All done. Now we can read the current user's email address:
user = access_token.get("/api/v1/users/current")['user']
print "User logged in, Name: {name}, email: {email}".format(**user)

# To reuse the access token at a later point, store the token and secret. For example:
# 1. Find user (in Django)
u = User.objects.get(email=user['email'])
# 2. Associate token and secret with the user
u.access_tokens.create(system='uservoice', token=access_token.token, secret=access_token.secret)

# When you need the token again:
# 1. Find token of the current user
token = u.access_tokens.get(system='uservoice')
# 2. Use the token and secret to log in
access_token = client.login_with_access_token(token.token, token.secret)
```
