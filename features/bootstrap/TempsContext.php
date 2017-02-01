<?php

namespace Features\Bootstrap;

class TempsContext extends FeatureContext
{
    protected $uniqueComment;

    public function __construct()
    {
        $this->uniqueComment = time();
    }

    /**
     * @Given /^Je suis connecté$/
     */
    public function jeSuisConnecte()
    {
        $this->iAmOnHomepage();
        $this->fillField('username', 'test');
        $this->fillField('password', 'qweqwe');
        $this->pressButton('Connexion');
    }

    /**
     * @When /^je clock du temps$/
     */
    public function jeClockDuTemps()
    {
        $this->fillField('recherche', 'fait quelque chose');
        $this->spin(function () {
            $this->executeJavascript("$('[data-clock-task=123321]')[0].click()");
            return true;
        });
        $this->fillField('edit-time-input-hour-start', '11:00');
        $this->fillField('edit-time-input-hour-end', '11:11');
        $this->fillField('edit-time-input-comment', $this->uniqueComment);
        $this->pressButton('Ajouter');
    }

    /**
     * @Then /^mon dashboard est mis à jour$/
     */
    public function monDashboardEstMisÀJour()
    {
        $this->spin(function () {
            $this->assertElementContainsText('.clock-history-list', $this->uniqueComment);
            return true;
        });
    }
}