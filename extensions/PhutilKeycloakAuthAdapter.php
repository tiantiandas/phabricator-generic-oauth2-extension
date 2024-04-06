<?php

final class PhutilKeycloakAuthAdapter extends PhutilOAuthAuthAdapter {

    private $wellKnownConfiguration;
    public $keycloakURI;
    public $keycloakRealm;

    public function getAdapterType() {
        return 'Keycloak';
    }

    public function getKeycloakRealm() {
        return $this->keycloakRealm;
    }

    public function setKeycloakRealm($realm) {
        $this->keycloakRealm = $realm;
        return $this;
    }

    public function getKeycloakURI() {
        return new PhutilURI($this->keycloakURI);
    }
    public function setKeycloakURI($uri) {
        $this->keycloakURI = $uri;
        return $this;
    }

    public function getAdapterDomain() {
        return $this->getKeycloakURI()->getDomain();
    }

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

    public function getAccountImageURI() {
        return null;
    }

    public function getAccountURI() {
        return null;
    }

    protected function getAuthenticateBaseURI() {
        return $this->getWellKnownConfiguration('authorization_endpoint');
    }

    protected function getTokenBaseURI() {
        return $this->getWellKnownConfiguration('token_endpoint');
    }

    public function getScope() {
        return 'openid profile email';
    }

    public function getExtraAuthenticateParameters() {
        return array(
            'response_type' => 'code',
        );
    }

    public function getExtraTokenParameters() {
        return array(
            'grant_type' => 'authorization_code',
        );
    }

    public function getAccessToken() {
        return $this->getAccessTokenData('access_token');
    }

    protected function loadOAuthAccountData() {
        $uri = $this->getWellKnownConfiguration('userinfo_endpoint');

        $future = new HTTPSFuture($uri);

        $token = $this->getAccessToken();
        $future->addHeader('Authorization', "Bearer {$token}");

        list($body) = $future->resolvex();

        try {
            $result = phutil_json_decode($body);
            return $result;
        } catch (PhutilJSONParserException $ex) {
            throw new PhutilProxyException(
                pht('Expected valid JSON response from OIDC account data request.'),
                $ex);
        }
    }

    private function getWellKnownConfiguration($key) {
        if ($this->wellKnownConfiguration === null) {
            $uri = $this->getKeycloakURI();

            $path = $uri->getPath();
            $path = rtrim($path, '/') . '/realms/' . $this->keycloakRealm . '/.well-known/openid-configuration';

            $uri->setPath($path);

            $uri = phutil_string_cast($uri);

            $future = new HTTPSFuture($uri);
            list($body) = $future->resolvex();

            $data = phutil_json_decode($body);

            $this->wellKnownConfiguration = $data;
        }

        if (!isset($this->wellKnownConfiguration[$key])) {
            throw new Exception(
                pht(
                    'Expected key "%s" in well-known configuration!',
                    $key));
        }

        return $this->wellKnownConfiguration[$key];
    }
}
