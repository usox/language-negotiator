[![Unittests](https://github.com/usox/language-negotiator/actions/workflows/php.yml/badge.svg)](https://github.com/usox/language-negotiator/actions/workflows/php.yml)

# language-negotiator

Negotiates the client language of a http request using the `Accept-Language` http [header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Language).

## Installation

```
composer require usox/language-negotiator
```

## Usage

There are several ways to use the negotiator.

### With $_SERVER superglobal

```php
use Usox\LanguageNegotiator\LanguageNegotiator;

$negotiator = new LanguageNegotiator(
    ['en', 'de'], // array of supported languages
    'en' // fallback language,
    $_SERVER
);

$clientLanguage = $negotiator->negotiate();
```

### With an already obtained http header string

```php
use Usox\LanguageNegotiator\LanguageNegotiator;

$negotiator = new LanguageNegotiator(
    ['en', 'de'], // array of supported languages
    'en' // fallback language,
);

$clientLanguage = $negotiator->negotiate(
    $acceptLanguageHeader
);
```

### As PSR15 middleware

The negotiator will automatically enrich `ServerRequest` with the negotiated client language. It will be added
as an attribute which can obtained using the attribute name constant.

```php
use Usox\LanguageNegotiator\LanguageNegotiator;

$negotiator = new LanguageNegotiator(
    ['en', 'de'], // array of supported languages
    'en' // fallback language,
);

// assumes, you have some kind of framework which supports PSR request handling
$myFramework->addMiddleware($negotiator);

// get the language from the psr server request
$clientLanguage = $request->getAttribute(LanguageNegotiator::REQUEST_ATTRIBUTE_NAME);
```