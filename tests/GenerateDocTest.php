<?php
namespace NeoClocking\Tests;

use Artisan;
use Dingo\Api\Console\Command\Docs;
use Mockery;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use TestCase;

class GenerateDocTest extends TestCase
{
    public function testICanRunTheGenerationForTheApiDoc()
    {
        try {
            Artisan::call('api:docs', [
                '--name'       => "NeoClocking"
            ]);
        } catch (\Exception $e) {
            $this->fail("Cannot generate the doc!");
        }
    }
}
