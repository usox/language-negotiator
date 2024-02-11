<?php

declare(strict_types=1);

namespace Usox\LanguageNegotiator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LanguageNegotiatorTest extends TestCase
{
	#[DataProvider(methodName: 'languageDataProvider')]
    public function testLanguageDetectionWithNegotiateMethod(
        array $supportedLanguages,
        string $fallbackLanguage,
        ?string $headerLine,
        string $expectedLanguage
    ): void {
        $subject = new LanguageNegotiator(
            $supportedLanguages,
            $fallbackLanguage,
        );

        parent::assertSame(
            $expectedLanguage,
            $subject->negotiate(['HTTP_ACCEPT_LANGUAGE' => $headerLine])
        );
    }

	#[DataProvider(methodName: 'languageDataProvider')]
	#[DataProvider(methodName: 'invalidHeaderDataProvider')]
    public function testLanguageDetectionWithServerDict(
        array $supportedLanguages,
        string $fallbackLanguage,
        mixed $headerLine,
        string $expectedLanguage
    ): void {
        $subject = new LanguageNegotiator(
            $supportedLanguages,
            $fallbackLanguage,
            ['accept-language' => $headerLine],
        );

        parent::assertSame(
            $expectedLanguage,
            $subject->negotiate()
        );
    }

    public static function languageDataProvider(): array {
        return [
            [['en', 'de'], 'en', null, 'en'], // fallback if requested language is empty
            [[], 'de', null, 'de'], // fallback if accepted languages is empty
            [['fr'], 'de', 'fr-CH, en;q=0.9, fr;q=0.8, de;q=0.7, *;q=0.5', 'fr'], // request language is second of accepted
            [['fr'], 'en-us', 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5', 'fr'], // requested language is first of accepted
            [['de'], 'fr', 'en', 'fr'], // requested language not in list of accepted
        ];
    }

    public static function invalidHeaderDataProvider(): array {
        return [
            [[], 'en', [], 'en'],
            [[], 'en', 666, 'en'],
        ];
    }

    public function testProcessEnrichesServerRequestWithDetectedLanguage(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $requestWithLanguage = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $subject = new LanguageNegotiator(
            ['en', 'de'],
            'en'
        );

        $request->expects($this->once())
            ->method('withAttribute')
            ->with(LanguageNegotiator::REQUEST_ATTRIBUTE_NAME, 'de')
            ->willReturn($requestWithLanguage);
        $request->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['accept-language' => 'de']);

        $handler->expects($this->once())
            ->method('handle')
            ->with($requestWithLanguage)
            ->willReturn($response);

        $this->assertSame(
            $response,
            $subject->process($request, $handler)
        );
    }

    public function testLanguageDetectionReturnsFallbackIfNoHeadersAreProvided(): void {
        $subject = new LanguageNegotiator(
            [],
            'en'
        );

        parent::assertSame(
            'en',
            $subject->negotiate()
        );
    }
}
