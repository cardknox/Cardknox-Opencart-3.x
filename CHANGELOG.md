# Changelog

## 1.1.0 — final release

Correctness and security fixes only. This is the last release before end of life on 30 April 2027.

### Fixed
- **Wrong charge amount on multi-currency stores.** The amount used a hardcoded exchange rate of 1.0 while the currency sent was the shopper's selected currency. Amount and currency now both come from the order.
- **Repeat charges.** Submitting payment for an order that has already been processed is now refused, and the gateway request sets `xAllowDuplicate=FALSE`. A missing order in the session is also refused instead of being sent to the gateway.
- **Unauthenticated log writes.** The checkout error endpoint now logs only the `error` field, strips control characters, and caps it at 255 characters. It previously wrote the whole request body to the log. It also now returns valid JSON.
- **Personal data in logs.** Billing and shipping name, company, street, city, state, postcode, phone, email, and IP are now masked in `cardknox.log`, in both the request and the gateway response.
- **Configured endpoint ignored.** The Transaction URI Endpoint setting is now used. Blank or non-`https://` values fall back to the default gateway.
- **No gateway timeout.** The gateway call now has a 10-second connect timeout and a 45-second overall timeout.
- **Checkout form broken on PHP 8.1+.** Replaced deprecated `strftime()` in the expiry date dropdowns.
- **Admin validation errors not shown.** A missing Transaction Key or Token Key now shows an error on save.
- **Card code response never recorded.** Gateway response fields are now read case-insensitively and without warnings when absent, so the CVV result appears in order history and a malformed response no longer shows the shopper a blank error.
- Browser-side iFields debug logging is turned off.
- The token key is escaped for JavaScript in the checkout template.
- Server and browser now report the same plugin version, 1.1.0.

### Known issues not fixed
These are out of scope for the final release:
- The minimum order total setting is not enforced, and the geo-zone setting is not saved.
- State is sent as the full name rather than the state code.
- Billing address line 2 is not sent.
- The cardholder name field is collected but not sent.
- Declined payments are not recorded in order history.
