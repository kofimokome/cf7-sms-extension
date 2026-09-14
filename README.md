# SMS Extension for Contact Form 7
Receive text message notifications when a form is submited. 

This Plugin adds SMS Texting capability to Contact Form 7, from supported providers ( see list below ).
Configure your forms to send Text messages to you and your visitors from the SMS tab in Contact Form 7 plugin.
It also allows for Text notifications when you receive through your forms.
<br>

<b>Note: This is just an extension. This plugin is not affiliated with or endorsed by Contact Form 7.</b>

# Security Policy
## Reporting Security Bugs

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/cf7-sms-extension)

## Supported Providers
1. Twilio
2. Nexmo (Vonage)
3. WhatsApp
4. ClickSend
5. MessageBird
6. TextLocal
7. Telnyx

## Contribution
The latest codes are found in the `develop` branch. If you would like to contribute, you should use the `develop` branch.
Bear in mind that the codes in the `develop` branch may be unstable. If you are looking for the lastest stable codes, you should checkout from the `master` branch

## Development setup (ONLY FOR PREMIUM PLUGIN)

This plugin depends on the Composer package `kofimokome/wordpress-tools`
([WP-Tools on GitHub](https://github.com/kofimokome/WP-Tools)).

After cloning the repository, run:

```bash
composer install
```

Composer will install the package from the GitHub VCS repository and
automatically create the `lib/wordpress_tools` symlink pointing to
`vendor/kofimokome/wordpress-tools/src`.

If you ever need to recreate the symlink manually:

```bash
composer setup-wordpress-tools
```

### Running `wptools`

Use the Composer script to run the WordPress Tools CLI from the plugin root:

```bash
composer wptools

# Examples
composer wptools -- make:model User
composer wptools -- make:migration create_users_table --table=users
composer wptools -- make:migration add_slug_to_users --table=users --update
```

`lib/wordpress_tools` is gitignored and should never be committed.

## Testing

See [TESTING.md](TESTING.md) for instructions on configuring and running the
unit and integration tests.

## This plugin is used by
1. **[I-Mbu](https://i-mbu.com)**