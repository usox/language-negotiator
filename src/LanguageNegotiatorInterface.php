<?php

declare(strict_types=1);

namespace Usox\LanguageNegotiator;

use Psr\Http\Server\MiddlewareInterface;

interface LanguageNegotiatorInterface extends MiddlewareInterface
{
    public function negotiate(
        ?string $headerLine
    ): string;
}