<?php

namespace Mondago\MSGraph\Mail;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot()
    {
        Mail::extend('graph-api', function (array $config) {
            return new Transport($config);
        });
    }
}
