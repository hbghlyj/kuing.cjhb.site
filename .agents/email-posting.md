# Email posting

Inbound mail for the recipient domain is delivered to postfix on the web server and piped to `api/emailpost_pipe.php`, which imports each message as a member post synchronously on arrival. Copy `config/config_emailpost_default.php` to the ignored `config/config_emailpost.php` and set `enabled => true` and the `recipient_domain`.

postfix setup:

- `recipient_delimiter = +` in `main.cf`.
- In `virtual_alias_maps`, map `forum@cjhb.site` to a local alias (more specific than the domain catchall), and in `/etc/aliases` pipe that alias to the CLI entrypoint.

- Start a thread by sending to `forum+FID@recipient-domain`. The subject becomes the thread subject.
- Replies are routed only through RFC `In-Reply-To` and `References` identifiers. The direct parent is preferred, then references are searched newest-first.
- Every accepted inbound `Message-ID` is mapped to its resulting `pid` and `tid`. Site-generated post mail should use a unique ID such as `<post-PID@site-domain>`; a `tid` alone is not a unique message identifier.
- The sender must match a verified, active forum member. Header data never supplies a UID.
- Special threads and email attachments are not supported. Existing posting permissions, moderation, flood control, and credit limits still apply.
