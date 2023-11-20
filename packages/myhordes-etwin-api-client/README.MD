# PHP EternalTwin API Client

## How to use

The core of the client is the API class.

```php
$etwin_api = new EternalTwinClient\API(
    'my_sk',                            // Your secret key
    'my_app_name',                      // Your app name
    'http://example.com/from/etwin',    // Return URL
    
    // You can optionally specify the API and OAuth URLs for ET,
    // which is useful if you're running your own ETwin server
    'https://eternal-twin.net/api/v1',              
    'https://eternal-twin.net/oauth/authorize',
    'https://eternal-twin.net/oauth/token',
);
```

From this object, you can request a URL to send the user to, which will
display a login window from ET. Once the user has authenticated, they
will be redirected to the specified return URL, with the authentication
code and state identifier attached as get parameters

Getting the authentication URL:
```php
$etwin_api->createAuthorizationRequest('some-state');
```

After authentication, the user will be redirected to 
`http://example.com/from/etwin?code=<code>&state=some-state`.
With the code, you can enable the EternalTwin API client to make
additional requests.

```php
$etwin_api->setAuthorizationCode( $the_code );

// Let's get some info about the users EternalTwin account
$user = $etwin_api->requestAuthSelf();
```
