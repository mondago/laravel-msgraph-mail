<?php

namespace Mondago\MSGraph\Mail;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    protected function boot()
    {
        $this->app->get('mail.manager')->extend('graph-api', function (array $config) {
            return new Transport(new Client(), $config);
        });
    }
}
