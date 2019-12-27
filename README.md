EmailLabs Symfony Mailer
=============

Features included:

- Configuration based on Symfony DSN in `.env` files
- Sending emails with EmailLabs REST API
- All benefits from [symfony/mailer](https://github.com/symfony/mailer)

Installation
------------

1.  [symfony/mailer](https://github.com/symfony/mailer) is required in version 4.4.*
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

TODO
------------
1.  Verify string length? For example subject in EmailLabs is max 128 chars.
1.  Add recipe which automatically add service declaration.
1.  Add recipe which automatically add .env custom configuration example.
1.  Support for EmailLabs TAGS functionality: [http://docs.emaillabs.io/smtp-en/tags/](http://docs.emaillabs.io/smtp-en/tags/)
