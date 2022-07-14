# Laravel Graph API Mail Transport

Adds support for sending mail via Microsoft's Graph API

## Getting Started

Install the package

```bash
composer install mondago/laravel-msgraph-mail
```

Add the configuration to your mail.php config file:

```php
'mailers' => [
    'microsoft-graph' => [
        'transport' => 'graph-api',
        'tenant' => env('GRAPH_API_TENANT'),
        'client' => env('GRAPH_API_CLIENT_ID'),
        'secret' => env('GRAPH_API_CLIENT_SECRET')
    ]
]
```


## Support Matrix

| Our Version | Supported Laravel Version |
|-------------|---------------------------|
| 1.x.x       | 5.5.x                     |
| ^2.0        | 7.x                       |
| ^2.0.2      | 8.x                       |
