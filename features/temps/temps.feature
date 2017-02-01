Feature: Gestion du temps

  @javascript
  Scenario: Clocker du temps
    Given Je suis connecté
    When je clock du temps
    Then mon dashboard est mis à jour