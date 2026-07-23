# Sentinel's Journal

## 2026-07-23 - [Prevent PHP Object Injection via Unserialize]
**Vulnerability:** Use of raw `unserialize` on user-controlled cookies, oauth payload cookies, or inputs can lead to PHP Object Injection if classes can be instantiated.
**Learning:** `dunserialize()` is designed to handle unserialization safely with `['allowed_classes' => false]`. However, legacy/fallback code or class methods (such as `unserialize` in `discuz_application.php`, `connect_login.php`, `oauth.inc.php`) still call the native `unserialize()` function without restrictions or with missing safe wrappers.
**Prevention:** Always use `dunserialize()` or pass `['allowed_classes' => false]` when using `unserialize()`.
