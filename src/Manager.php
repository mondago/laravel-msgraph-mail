<?php
namespace Mondago\MSGraph\Mail;

use GuzzleHttp\Client;
use Illuminate\Mail\TransportManager;

class Manager extends TransportManager
{
    protected function createGraphApiDriver()
    {
        $config = $this->app['config']->get('graph-api', []);

        return new Transport(new Client(), $config);
    }

}
