# Email posting

The system cron `cron_emailpost.php` imports ordinary member posts from an IMAP mailbox. Copy `config/config_emailpost_default.php` to the ignored `config/config_emailpost.php`, then configure the mailbox, recipient domain, and trusted mail server authentication-results ID. PHP IMAP is required.

- Start a thread by sending to `forum+FID@recipient-domain`. The subject becomes the thread subject.
- Replies are routed only through RFC `In-Reply-To` and `References` identifiers. The direct parent is preferred, then references are searched newest-first.
- Every accepted inbound `Message-ID` is mapped to its resulting `pid` and `tid`. Site-generated post mail should use a unique ID such as `<post-PID@site-domain>`; a `tid` alone is not a unique message identifier.
- The sender must match a verified, active forum member and pass DMARC at the configured trusted authentication server. Header data never supplies a UID.
- Special threads and email attachments are not supported. Existing posting permissions, moderation, flood control, and credit limits still apply.
