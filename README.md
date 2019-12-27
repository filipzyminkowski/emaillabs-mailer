EmailLabs Symfony Mailer
=============

Features included:

- Configuration based on Symfony DSN in `.env` files
- Sending emails with EmailLabs REST API
- All benefits from [symfony/mailer](https://github.com/symfony/mailer)

Installation
------------
<a name="installation" />

1.  [symfony/mailer](https://github.com/symfony/mailer) is required in version 4.4.*
1.  Execute command:
    ```
    composer require globegroup/emaillabs-mailer
    ```
    
1.  Add service declaration in services.yaml:
    ```yaml
    GlobeGroup\EmailLabsMailer\Transport\EmailLabsTransportFactory:
        tags:
            - { name: mailer.transport_factory }
    ```
1.  In `.env.local` use the configuration listed below:
    ```dotenv
    ###> symfony/mailer ###
    EMAILLABS_APP_KEY=<YOUR_APP_KEY>
    EMAILLABS_SECRET=<YOUR_SECRET>
    EMAILLABS_SMTP_ACCOUNT=<YOUR_SMTP_ACCOUNT>
    MAILER_DSN=emaillabs+api://$EMAILLABS_APP_KEY:$EMAILLABS_SECRET@default?smtpAccount=$EMAILLABS_SMTP_ACCOUNT
    ###< symfony/mailer ###
    ```

LOCAL TESTING
------------
<a name="local-testing" />

1.  Clone repository into `symfony/localVendor` folder.
1.  Add into composer.json:
    ```json
    "repositories": [
        {
            "type": "path",
            "url": "localVendor/globegroup-emaillabs-mailer"
        }
    ],
    ```
1.  Check if `minimum-stability` is set to `dev`.
1.  Proceed to [Installation](#installation).

EmailLabs specific functions
------------
<a name="emaillabs-specific-functions" />

* Tags

    Add `X-MailTags` header when creating new email message and separate tags by `;` as in example code:
    ```php
    $email = new Email();
    $email->getHeaders()->addTextHeader('X-MailTags', 'tag_1;tag_2');
    ```

TODO
------------
<a name="todo" />

1.  Verify string length? For example subject in EmailLabs is max 128 chars.
1.  Add recipe which automatically add service declaration.
1.  Add recipe which automatically add .env custom configuration example.
