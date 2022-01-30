<?php

declare(strict_types=1);

namespace Usox\LanguageNegotiator;

use Psr\Http\Server\MiddlewareInterface;

interface LanguageNegotiatorInterface extends MiddlewareInterface
{
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
    ): string;
}