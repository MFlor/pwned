<?php

namespace MFlor\Pwned\Tests\Repositories;

use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Repositories\StealerLogRepository;

class StealerLogRepositoryTest extends RepositoryTestCase
{
    public function testCanGetWebsiteDomainsByEmail(): void
    {
        $repository = new StealerLogRepository(
            $this->getClientWithResponse('["netflix.com","spotify.com"]'),
            'api-key'
        );

        $domains = $repository->byEmail('jane+test@example.com');

        // rawurlencode, so "+" and "@" are encoded
        $this->assertRequest('stealerLogsByEmail/jane%2Btest%40example.com', '', ['hibp-api-key' => 'api-key']);
        $this->assertSame(['netflix.com', 'spotify.com'], $domains);
    }

    public function testCanGetEmailsByWebsiteDomain(): void
    {
        $repository = new StealerLogRepository(
            $this->getClientWithResponse('["andy@gmail.com","jane@gmail.com"]'),
            'api-key'
        );

        $emails = $repository->byWebsiteDomain('netflix.com');

        $this->assertRequest('stealerLogsByWebsiteDomain/netflix.com', '', ['hibp-api-key' => 'api-key']);
        $this->assertSame(['andy@gmail.com', 'jane@gmail.com'], $emails);
    }

    public function testCanGetAliasesByEmailDomain(): void
    {
        $repository = new StealerLogRepository(
            $this->getClientWithResponse('{"andy":["netflix.com"],"jane":["netflix.com","spotify.com"],"123":["x.com"]}'),
            'api-key'
        );

        $aliases = $repository->byEmailDomain('gmail.com');

        $this->assertRequest('stealerLogsByEmailDomain/gmail.com', '', ['hibp-api-key' => 'api-key']);
        $this->assertSame([
            'andy' => ['netflix.com'],
            'jane' => ['netflix.com', 'spotify.com'],
            // Numeric aliases become integer keys, as in any PHP array
            123 => ['x.com'],
        ], $aliases);
    }

    /**
     * @dataProvider methodProvider
     */
    public function testMethodsReturnNullOnInvalidJson(string $method, string $argument): void
    {
        $repository = $this->getRepositoryWithResponse(StealerLogRepository::class, new Response(200, [], 'invalid'));

        $this->assertNull($repository->$method($argument));
    }

    /**
     * @dataProvider authenticatedBadResponseProvider
     */
    public function testMethodsCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
        foreach ($this->methodProvider() as [$method, $argument]) {
            $repository = $this->getRepositoryWithResponse(StealerLogRepository::class, $response);
            try {
                $repository->$method($argument);
                $this->fail(sprintf('%s failed throwing an exception!', $method));
            } catch (AbstractException $exception) {
                $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
            }
        }
    }

    /**
     * @return string[][]
     */
    public function methodProvider(): array
    {
        return [
            'byEmail' => ['byEmail', 'jane@example.com'],
            'byWebsiteDomain' => ['byWebsiteDomain', 'netflix.com'],
            'byEmailDomain' => ['byEmailDomain', 'gmail.com'],
        ];
    }
}
