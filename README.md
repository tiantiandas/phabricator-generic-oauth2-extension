# phabricator-generic-oauth2-extension
A versatile extension for Phabricator, enabling seamless integration with various OAuth 2.0 identity providers. Simplify authentication setup and enhance security with this customizable extension.

Code is based on https://secure.phabricator.com/T524.

## How to use the extension

1. Copy extension PHP files to `phabricator/src/extensions`
2. Restart Phabricator services
3. Add `Auth Provider` and choose `Keycloak`
4. Input Keycloak base URL and realm name
5. Save
6. Re-edit the provider
7. Configure App ID(Keycloak client), App Secret(Keycloak client secret)

## Customize user attributes

Update the attributes to get it right for you:

```php
final class PhutilKeycloakAuthAdapter extends PhutilOAuthAuthAdapter {
    ...
    public function getAccountID() {
        return $this->getOAuthAccountData('sub');
    }

    public function getAccountEmail() {
        return $this->getOAuthAccountData('email');
    }

    public function getAccountName() {
        return $this->getOAuthAccountData('preferred_username');
    }

    public function getAccountRealName() {
        return $this->getOAuthAccountData('name');
    }
    ...
}
```
