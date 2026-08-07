# Email posting

Inbound mail for the recipient domain is delivered to postfix on the web server and piped to `api/emailpost_pipe.php`, which imports each message as a member post synchronously on arrival. Copy `config/config_emailpost_default.php` to the ignored `config/config_emailpost.php` and set `enabled => true` and the `recipient_domain`.

postfix setup (all on the web server):

- `recipient_delimiter = +` in `main.cf`.
- In `virtual_alias_maps`, `forum@cjhb.site` maps to `forum-pipe@mailpipe.local` (more specific than the domain catchall). The `+FID` extension propagates to the lookup result.
- `transport_maps` routes the internal domain `mailpipe.local` to the `emailpipe` transport, defined in `master.cf` as a `pipe` service running as `www-data` and executing `/usr/bin/php .../api/emailpost_pipe.php`. The pipe runs as `www-data`, never root.

- Start a thread by sending to `forum+FID@recipient-domain`. The subject becomes the thread subject.
- Replies are routed only through RFC `In-Reply-To` and `References` identifiers. The direct parent is preferred, then references are searched newest-first.
- Every accepted inbound `Message-ID` is mapped to its resulting `pid` and `tid`. Site-generated post mail should use a unique ID such as `<post-PID@site-domain>`; a `tid` alone is not a unique message identifier.
- The sender must match a verified, active forum member. Header data never supplies a UID.
- Special threads and email attachments are not supported. Existing posting permissions, moderation, flood control, and credit limits still apply.
- `emailpost::config()` exposes the merged config (defaults + the ignored local override). The `forumdisplay` module publishes `$emailpost_mailto` and the template renders a `mailto:forum+FID@...` button next to "New topic" — only for logged-in members with a verified email, and only when the feature is enabled.
