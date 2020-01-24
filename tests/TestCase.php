<?php

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{

    protected $api_token = "2e977692bbbf7b7a4a3da07397591714";
    protected $api_token_tsfail = 'f37f2e2d886b19da7cf5726b4a26b197';

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}
