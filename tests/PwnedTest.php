<?php

namespace MFlor\Pwned\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Pwned;
use MFlor\Pwned\Repositories\BreachRepository;
use MFlor\Pwned\Repositories\DomainRepository;
use MFlor\Pwned\Repositories\PasswordRepository;
use MFlor\Pwned\Repositories\PasteRepository;
use MFlor\Pwned\Repositories\StealerLogRepository;
use MFlor\Pwned\Repositories\SubscriptionRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class PwnedTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface, options: array<string, mixed>}> */
    private array $history = [];

    public function testCanInstantiateClass(): void
    {
        $pwned = new Pwned();
        $this->assertInstanceOf(Pwned::class, $pwned);
        $this->assertInstanceOf(BreachRepository::class, $pwned->breaches());
        $this->assertInstanceOf(PasteRepository::class, $pwned->pastes());
        $this->assertInstanceOf(PasswordRepository::class, $pwned->passwords());
        $this->assertInstanceOf(StealerLogRepository::class, $pwned->stealerLogs());
        $this->assertInstanceOf(DomainRepository::class, $pwned->domains());
        $this->assertInstanceOf(SubscriptionRepository::class, $pwned->subscription());
    }

    public function testDefaultsAreUsed(): void
    {
        $pwned = new Pwned('my-api-key', ['handler' => $this->getHandler(3)]);

        $pwned->breaches()->getAll();
        $pwned->breaches()->byAccount('test@example.com');
        $pwned->passwords()->search('e38ad');

        [$breaches, $account, $passwords] = $this->history;

        $this->assertSame('https://haveibeenpwned.com/api/v3/breaches', (string) $breaches['request']->getUri());
        $this->assertSame('mflor-pwned-php-library/2.0', $breaches['request']->getHeaderLine('User-Agent'));
        $this->assertSame('application/json', $breaches['request']->getHeaderLine('Accept'));
        $this->assertFalse($breaches['request']->hasHeader('hibp-api-key'));
        $this->assertSame(10, $breaches['options']['connect_timeout']);
        $this->assertSame(30, $breaches['options']['timeout']);

        $this->assertSame('my-api-key', $account['request']->getHeaderLine('hibp-api-key'));

        $this->assertSame('https://api.pwnedpasswords.com/range/e38ad', (string) $passwords['request']->getUri());
        $this->assertSame('mflor-pwned-php-library/2.0', $passwords['request']->getHeaderLine('User-Agent'));
        $this->assertSame('text/plain', $passwords['request']->getHeaderLine('Accept'));
        $this->assertSame('true', $passwords['request']->getHeaderLine('Add-Padding'));
        $this->assertSame(10, $passwords['options']['connect_timeout']);
        $this->assertSame(30, $passwords['options']['timeout']);
    }

    public function testOptionsAreAppliedToBothApis(): void
    {
        $pwned = new Pwned(null, [
            'handler' => $this->getHandler(2),
            'timeout' => 5,
            'headers' => [
                // Lowercase on purpose: header names are case-insensitive
                'user-agent' => 'my-app/1.0',
                'X-Custom' => 'value',
            ],
            // Ignored, as each API needs its own base URI
            'base_uri' => 'https://example.com/',
        ]);

        $pwned->breaches()->getAll();
        $pwned->passwords()->search('e38ad');

        foreach ($this->history as $transaction) {
            $request = $transaction['request'];
            $this->assertSame(['my-app/1.0'], $request->getHeader('User-Agent'));
            $this->assertSame('value', $request->getHeaderLine('X-Custom'));
            $this->assertStringNotContainsString('example.com', (string) $request->getUri());
            $this->assertSame(5, $transaction['options']['timeout']);
            $this->assertSame(10, $transaction['options']['connect_timeout']);
        }
        $this->assertSame('application/json', $this->history[0]['request']->getHeaderLine('Accept'));
        $this->assertSame('text/plain', $this->history[1]['request']->getHeaderLine('Accept'));
    }

    private function getHandler(int $responses): HandlerStack
    {
        $handler = HandlerStack::create(new MockHandler(array_fill(0, $responses, new Response(200, [], '[]'))));
        $handler->push(Middleware::history($this->history));

        return $handler;
    }
}
