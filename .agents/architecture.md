# Architecture

## Network identity

Country/ASN data is used for online users, guests, and bots. Compact online displays use location information where available; detailed online-user lists include IP address and bot classification reason.

## Avatar fallback

Users without uploaded avatars render a deterministic colored initial from the username. The normal post renderer uses `avatarstatus` to mark missing images without requesting a nonexistent avatar file. JSON consumers, such as chat history, return an empty image URL and render the same fallback directly.

## Forum data

`forum_forum.lastpost` is ordered as:

```text
tid \t dateline \t author \t subject
```

Forum names are stored as a language-keyed JSON map in `forum_forum.name`. Runtime name readers must use the localization helper rather than assuming the stored value is a scalar string.
