<?php

namespace Features\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

use Behat\MinkExtension\Context\MinkContext;
use Exception;

class FeatureContext extends MinkContext implements Context, SnippetAcceptingContext
{
    /**
     * Try every second until we don't get an exception
     */
    public function spin($lambda, $wait = 20)
    {
        for ($i = 0; $i < $wait; $i++) {
            try {
                if ($lambda($this)) {
                    return true;
                }
            } catch (Exception $e) {
                // do nothing
                $erreur = $e->getMessage();
            }
            sleep(1);
        }
        $backtrace = debug_backtrace();
        if (isset($backtrace[1]['file']) && isset($backtrace[1]['function']) && isset($backtrace[1]['file'])) {
            throw new Exception(
                "Timeout thrown by " . $backtrace[1]['class'] . "::" . $backtrace[1]['function'] . "()\n" .
                $backtrace[1]['file'] . ", line " . $backtrace[1]['line']
            );
        } else {
            throw new Exception($erreur);
        }
    }

    protected function executeJavascript($javascript) {
        $this->getSession()->executeScript("function() { {$javascript} }();");
    }
}
