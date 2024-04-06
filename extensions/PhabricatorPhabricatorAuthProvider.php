<?php

final class PhabricatorKeycloakAuthProvider extends PhabricatorOAuth2AuthProvider {

  const PROPERTY_KEYCLOAK_REALM = 'oauth2:keycloak:realm';
  const PROPERTY_KEYCLOAK_URI = 'oauth2:keycloak:uri';

  public function getProviderName() {
    return pht('keycloak');
  }

  protected function newOAuthAdapter() {
    $config = $this->getProviderConfig();
    return id(new PhutilKeycloakAuthAdapter())
      ->setKeycloakURI($config->getProperty(self::PROPERTY_KEYCLOAK_URI))
      ->setKeycloakRealm($config->getProperty(self::PROPERTY_KEYCLOAK_REALM));
  }

  protected function getProviderConfigurationHelp() {
    $login_uri = PhabricatorEnv::getURI($this->getLoginURI());

    return pht(
      "To configure Keycloak OAuth, create a new OpenID typed client." .
      "\n\n" .
      "When creating your OpenID typed client, use these settings:" .
      "\n\n" .
      "  - **Redirect URI:** Set this to: `%s`" .
      "\n\n" .
      "After completing configuration, copy the **Client ID** and " .
      "**Client Secret** to the fields above.",
      $login_uri);
  }

  private function isCreate() {
    return !$this->getProviderConfig()->getID();
  }

  public function readFormValuesFromProvider() {
    $config = $this->getProviderConfig();
    $uri = $config->getProperty(self::PROPERTY_KEYCLOAK_URI);
    $realm = $config->getProperty(self::PROPERTY_KEYCLOAK_REALM);

    return parent::readFormValuesFromProvider() + array(
      self::PROPERTY_KEYCLOAK_REALM => $realm,
      self::PROPERTY_KEYCLOAK_URI => $uri,
    );
  }

  public function readFormValuesFromRequest(AphrontRequest $request) {

    return parent::readFormValuesFromRequest($request) + array(
      self::PROPERTY_KEYCLOAK_REALM => $request->getStr(self::PROPERTY_KEYCLOAK_REALM),
      self::PROPERTY_KEYCLOAK_URI =>
        $request->getStr(self::PROPERTY_KEYCLOAK_URI),
    );
  }

  public function processEditForm(
    AphrontRequest $request,
    array $values) {

    $is_setup = $this->isCreate();

    if (!$is_setup) {
      list($errors, $issues, $values) =
        parent::processEditForm($request, $values);
    } else {
      $errors = array();
      $issues = array();
    }

    $key_realm = self::PROPERTY_KEYCLOAK_REALM;
    $key_uri = self::PROPERTY_KEYCLOAK_URI;

    if (!strlen($values[$key_realm])) {
      $errors[] = pht('Realm name is required.');
      $issues[$key_realm] = pht('Required');
    }

    if (!strlen($values[$key_uri])) {
      $errors[] = pht('Base URI is required.');
      $issues[$key_uri] = pht('Required');
    } else {
      $uri = new PhutilURI($values[$key_uri]);
      if (!$uri->getProtocol()) {
        $errors[] = pht(
          'Base URI should include protocol (like "%s").',
          'https://');
        $issues[$key_uri] = pht('Invalid');
      }
    }

    return array($errors, $issues, $values);
  }

  public function extendEditForm(
    AphrontRequest $request,
    AphrontFormView $form,
    array $values,
    array $issues) {

    $is_setup = $this->isCreate();

    $e_required = $request->isFormPost() ? null : true;

    $v_realm = $values[self::PROPERTY_KEYCLOAK_REALM];
    $e_realm = idx($issues, self::PROPERTY_KEYCLOAK_REALM, $e_required);

    $v_uri = $values[self::PROPERTY_KEYCLOAK_URI];
    $e_uri = idx($issues, self::PROPERTY_KEYCLOAK_URI, $e_required);

    $form
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Base URI'))
          ->setValue($v_uri)
          ->setName(self::PROPERTY_KEYCLOAK_URI)
          ->setError($e_uri))
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Realm Name'))
          ->setValue($v_realm)
          ->setName(self::PROPERTY_KEYCLOAK_REALM)
          ->setError($e_realm));

    if (!$is_setup) {
      parent::extendEditForm($request, $form, $values, $issues);
    }
  }

  public function hasSetupStep() {
    return true;
  }
}
