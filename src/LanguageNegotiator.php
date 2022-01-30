<?php

declare(strict_types=1);

namespace Usox\LanguageNegotiator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Negotiate the http client language
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Language
 */
final class LanguageNegotiator implements LanguageNegotiatorInterface
{
    /** @var string Public name of the psr server request attribute */
    public const REQUEST_ATTRIBUTE_NAME = 'negotiated-request-language';

    /** @var array<string> List of allowed accept-language header names */
    private const ACCEPT_LANGUAGE_HEADERS = ['accept-language', 'http_accept_language'];

    /**
     * @param array<string> $supportedLanguages List of language codes you application supports
     * @param string $fallbackLanguage language code of the fallback language if negotiation fails
     * @param null|array<string, mixed> $serverRequest Optional server request input ($_SERVER)
     * @param string $attributeName Optional alternate name of the server request attribute for the negotiated language
     */
    public function __construct(
        private array $supportedLanguages,
        private string $fallbackLanguage = 'en',
        private ?array $serverRequest = null,
        private string $attributeName = self::REQUEST_ATTRIBUTE_NAME
    ) {
    }

    /**
     * Negotiates the client language
     * If the serverRequest was set by constructor, the parameter can be omitted
     *
     * @param array<string, mixed>|null $request Optional dict of server request variables; overwrites serverRequest defined in constructor
     *
     * @return string language code of the negotiated client language
     */
    public function negotiate(
        ?array $request = null
    ): string {
        // return fallback if both, headerLine and serverRequest is not set
        $requestHeaders = $request ?? $this->serverRequest ?? null;

        if ($requestHeaders === null) {
            return $this->fallbackLanguage;
        }

        $headerValue = $this->normalizeAcceptLanguageString($requestHeaders);

        // return the fallback if the header is not usable
        if ($headerValue === null) {
            return $this->fallbackLanguage;
        }

        // split up the header to determine a list of all accepted languages
        $acceptedLanguages = array_reduce(
            explode(',', $headerValue),
            static function (array $result, string $line): array {
                [$language, $priority] = array_merge(explode(';q=', $line), [1]);
                $result[trim((string) $language)] = (float) $priority;
                return $result;
            },
            []
        );

        // sort by value which is actually the language priority defined within the client
        arsort($acceptedLanguages);

        // determine the intersection between supported and available languages
        $result = array_intersect_key(
            $acceptedLanguages,
            array_flip($this->supportedLanguages),
        );

        // use the set fallback language if negotiation fails
        if ($result === []) {
            return $this->fallbackLanguage;
        }

        // returns the first element (the one having the highest priority)
        return key($result);
    }

    /**
     * Enriches the ServerRequest with the negotiated client language
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $request = $request->withAttribute(
            $this->attributeName,
            $this->negotiate($request->getHeaders())
        );

        return $handler->handle($request);
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function normalizeAcceptLanguageString(array $headers): ?string {
        $headerValue = null;

        // convert all keys to lowercase - just in case
        $headers = array_change_key_case($headers);

        // lookup all accepted header values
        foreach (self::ACCEPT_LANGUAGE_HEADERS as $headerName) {
            $headerValue = $headers[$headerName] ?? null;
            if ($headerValue !== null) {
                break;
            }
        }

        // maybe something goes wrong and we end up with some other scalar values in here - jest ensure, it's a string
        if (!is_string($headerValue)) {
            return null;
        }

        return strtolower($headerValue);
    }
}
