<?php

namespace Mondago\MSGraph\Mail;

use Illuminate\Mail\MailServiceProvider;

class ServiceProvider extends MailServiceProvider
{
    protected function registerSwiftTransport()
    {
        # https://stackoverflow.com/questions/44901912/creating-own-mail-provider-for-laravel-5-4
        $this->app->singleton('swift.transport', function ($app) {
            return new Manager($app);
        });

        $this->publishes([__DIR__.'/../config/graph-api.php' => config_path('graph-api.php')]);
    }
}
