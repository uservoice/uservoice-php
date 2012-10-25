UserVoice PHP library for API connections
=========================================

This library allows you to easily:
* Generate SSO token for creating SSO users / logging them into UserVoice (http://uservoice.com).

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

# Suppose your UserVoice site is at http://uservoice-subdomain.uservoice.com/
```php
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