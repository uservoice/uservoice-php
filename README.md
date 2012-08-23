UserVoice PHP library for API connections
=========================================

This library allows you to easily:
* Generate SSO token for creating SSO users / logging them into UserVoice (http://uservoice.com).

Examples
========

Prerequisites:
* Suppose your UserVoice site is at http://uservoice-subdomain.uservoice.com/
* **SSO\_KEY** = 982c88f2df72572859e8e23423eg87ed (Admin Console -> Settings -> General -> User Authentication)

SSO-token generation using uservoice library
--------------------------------------------

SSO-token can be used to create sessions for SSO users. They are capable of synchronizing the user information from one system to another.
Generating the SSO token from SSO key and given uservoice subdomain can be done by calling UserVoice.generate\_sso\_token method like this:

    echo 'https://uservoice-subdomain.uservoice.com/?sso='.UserVoice::generate_sso_token('uservoice-subdomain', '982c88f2df72572859e8e23423eg87ed', array(
      :display_name => "John Doe",
      :email => 'john.doe@example.com'
    ))."\n";