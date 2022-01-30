<?php

declare(strict_types=1);

namespace Usox\LanguageNegotiator;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LanguageNegotiatorTest extends TestCase
{
    /**
     * @dataProvider languageDataProvider
     */
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

    /**
     * @dataProvider languageDataProvider
     * @dataProvider invalidHeaderDataProvider
     */
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

    public function languageDataProvider(): array {
        return [
            [['en', 'de'], 'en', null, 'en'],
            [[], 'de', null, 'de'],
            [['en', 'fr'], 'de', 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5', 'fr'],
            [['fr'], 'en-us', 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5', 'fr'],
        ];
    }

    public function invalidHeaderDataProvider(): array {
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
}
