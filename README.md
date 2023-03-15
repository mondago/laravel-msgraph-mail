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
    'graph-api' => [
        'transport' => 'graph-api',
        'tenant' => env('GRAPH_API_TENANT'),
        'client_id' => env('GRAPH_API_CLIENT_ID'),
        'client_secret' => env('GRAPH_API_CLIENT_SECRET'),

         // This below is optional. By default we will use the 'from' email address
        'username' => 'myUser@contoso.com'
    ]
]
```


## Support Matrix

| Our Version | Supported Laravel Version |
|-------------|---------------------------|
| 1.x.x       | 5.5.x                     |
| ^2.0        | 7.x                       |
| ^2.0.2      | 8.x                       |
| ^3.0        | 9.x                       |
| ^3.1        | 9.x & 10.x                |
