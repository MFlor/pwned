<?php

namespace MFlor\Pwned\Tests\Repositories\BreachRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Models\Breach;
use MFlor\Pwned\Models\DataClass;
use MFlor\Pwned\Repositories\BreachRepository;
use MFlor\Pwned\Tests\Factories\BreachFactory;
use MFlor\Pwned\Tests\Repositories\RepositoryTestCase;

class OutputTest extends RepositoryTestCase
{
    private BreachFactory $factory;

    public function setUp(): void
    {
        $this->factory = new BreachFactory();
    }

    /**
     * @throws GuzzleException
     */
    public function testCanGetAllBreaches(): void
    {
        $expectedBreaches = $this->factory->getBreaches();
        $testData = json_encode($expectedBreaches);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $breaches = $repository->getAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest('breaches');
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        foreach ($expectedBreaches as $index => $expectedBreach) {
            $this->assertBreachMatchesData($expectedBreach, $breaches[$index]);
        }
    }

    /**
     * @throws GuzzleException
     */
    public function testCanGetBreachesByDomain(): void
    {
        $expectedBreaches = $this->factory->getBreaches();
        $testData = json_encode($expectedBreaches);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $breaches = $repository->byDomain('example.com');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest('breaches', 'domain=example.com');
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        foreach ($expectedBreaches as $index => $expectedBreach) {
            $this->assertBreachMatchesData($expectedBreach, $breaches[$index]);
        }
    }

    /**
     * @throws GuzzleException
     */
    public function testCanGetBreachByName(): void
    {
        $expectedBreach = $this->factory->getBreach();
        $testData = json_encode($expectedBreach);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $breach = $repository->byName('Adobe');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest('breach/Adobe');
        $expectedBreach = json_decode($testData);
        $this->assertBreachMatchesData($expectedBreach, $breach);
    }

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function testCanGetFullBreachesByAccount(): void
    {
        $expectedBreaches = $this->factory->getBreaches();
        $testData = json_encode($expectedBreaches);
        $apiKey = bin2hex(random_bytes(16));

        $repository = new BreachRepository($this->getClientWithResponse($testData), $apiKey);

        try {
            $breaches = $repository->byAccount('test@example.com', ['truncateResponse' => false]);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest(
            'breachedaccount/' . urlencode('test@example.com'),
            'truncateResponse=false',
            ['hibp-api-key' => $apiKey]
        );
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        foreach ($expectedBreaches as $index => $expectedBreach) {
            $this->assertBreachMatchesData($expectedBreach, $breaches[$index]);
        }
    }

    public function testApiKeyHeaderIsOmittedWithoutAnApiKey(): void
    {
        $repository = new BreachRepository($this->getClientWithResponse('[]'));

        $repository->byAccount('test@example.com');

        // Asserts that only the User-Agent header was sent
        $this->assertRequest('breachedaccount/' . urlencode('test@example.com'));
    }

    /**
     * The HIBP API ignores "0" and "1" for boolean parameters,
     * so booleans must be sent as the strings "true" and "false".
     *
     * @dataProvider booleanOptionsProvider
     *
     * @param array<string, string|bool> $options
     * @param string $expectedQuery
     * @throws \Exception
     */
    public function testBooleanOptionsAreSentAsTrueOrFalse(array $options, string $expectedQuery): void
    {
        $apiKey = bin2hex(random_bytes(16));
        $repository = new BreachRepository($this->getClientWithResponse('[]'), $apiKey);

        $repository->byAccount('test@example.com', $options);

        $this->assertRequest(
            'breachedaccount/' . urlencode('test@example.com'),
            $expectedQuery,
            ['hibp-api-key' => $apiKey]
        );
    }

    /**
     * @return array<string, array{0: array<string, string|bool>, 1: string}>
     */
    public function booleanOptionsProvider(): array
    {
        return [
            'truncateResponse true' => [['truncateResponse' => true], 'truncateResponse=true'],
            'includeUnverified false' => [['includeUnverified' => false], 'includeUnverified=false'],
            'includeUnverified true' => [['includeUnverified' => true], 'includeUnverified=true'],
            'all options' => [
                ['truncateResponse' => false, 'domain' => 'adobe.com', 'includeUnverified' => false],
                'truncateResponse=false&domain=adobe.com&includeUnverified=false',
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testCanGetBreachNamesByAccount(): void
    {
        $expectedBreaches = $this->factory->getTruncatedBreaches();
        $testData = json_encode($expectedBreaches);
        $apiKey = bin2hex(random_bytes(16));

        $repository = new BreachRepository($this->getClientWithResponse($testData), $apiKey);

        try {
            $breaches = $repository->byAccount('test@example.com');
        } catch (AbstractException|GuzzleException $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest(
            'breachedaccount/' . urlencode('test@example.com'),
            '',
            ['hibp-api-key' => $apiKey]
        );
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        $this->assertEquals($expectedBreaches, $breaches);
        foreach ($breaches as $breach) {
            $this->assertInstanceOf(\stdClass::class, $breach);
            $this->assertTrue(property_exists($breach, 'Name'));
        }
    }

    /**
     * @throws GuzzleException
     */
    public function testCanGetDataClasses(): void
    {
        $expectedClasses = $this->factory->getDataClasses();
        $testData = json_encode($expectedClasses);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $dataClasses = $repository->getDataClasses();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest('dataclasses');
        $this->assertIsArray($dataClasses);
        $this->assertCount(count($expectedClasses), $dataClasses);
        $this->assertSame($expectedClasses, $dataClasses);
    }

    /**
     * @dataProvider methodProvider
     *
     * @param string $method
     * @param string|null $param
     */
    public function testMethodsReturnsNullWhenInvalidJsonIsReturned(string $method, ?string $param = null): void
    {
        $mock = new MockHandler([
           new Response(200, [], 'invalid json'),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $repository = new BreachRepository($client);

        if ($param) {
            $this->assertNull($repository->$method($param));
        } else {
            $this->assertNull($repository->$method());
        }
    }

    /**
     * @return string[][]
     */
    public function methodProvider(): array
    {
        return [
            'getAll' => ['getAll'],
            'byDomain' => ['byDomain', 'adobe.com'],
            'byName' => ['byName', 'Adobe'],
            'byAccount' => ['byAccount', 'test@example.com'],
            'getDataClasses' => ['getDataClasses'],
            'latest' => ['latest'],
            'getDataClassDescriptions' => ['getDataClassDescriptions'],
            'searchByRange' => ['searchByRange', '6B5917'],
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function testCanGetLatestBreach(): void
    {
        $expectedBreach = $this->factory->getBreach();
        $repository = new BreachRepository($this->getClientWithResponse((string) json_encode($expectedBreach)));

        $breach = $repository->latest();

        $this->assertRequest('latestBreach');
        $this->assertInstanceOf(Breach::class, $breach);
        $this->assertBreachMatchesData($expectedBreach, $breach);
    }

    /**
     * @dataProvider spamListFilterProvider
     * @throws GuzzleException
     */
    public function testCanFilterBreachesBySpamList(string $method, array $arguments, string $expectedQuery): void
    {
        $repository = new BreachRepository($this->getClientWithResponse('[]'));

        $repository->$method(...$arguments);

        $this->assertRequest('breaches', $expectedQuery);
    }

    /**
     * @return array<string, array{0: string, 1: array<mixed>, 2: string}>
     */
    public function spamListFilterProvider(): array
    {
        return [
            'all spam lists' => ['getAll', [true], 'isSpamList=true'],
            'all non-spam lists' => ['getAll', [false], 'isSpamList=false'],
            'no filter' => ['getAll', [null], ''],
            'spam lists by domain' => ['byDomain', ['adobe.com', true], 'domain=adobe.com&isSpamList=true'],
            'non-spam lists by domain' => ['byDomain', ['adobe.com', false], 'domain=adobe.com&isSpamList=false'],
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function testCanGetDataClassDescriptions(): void
    {
        // The API returns lowercase keys
        $testData = '[{"name":"Academic records","description":"School or university records."},'
            . '{"name":"Account balances","description":"Balances of financial accounts."}]';
        $repository = new BreachRepository($this->getClientWithResponse($testData));

        $dataClasses = $repository->getDataClassDescriptions();

        $this->assertRequest('dataClasses/descriptions');
        $this->assertIsArray($dataClasses);
        $this->assertCount(2, $dataClasses);
        $this->assertInstanceOf(DataClass::class, $dataClasses[0]);
        $this->assertSame('Academic records', $dataClasses[0]->getName());
        $this->assertSame('School or university records.', $dataClasses[0]->getDescription());
        $this->assertSame('Account balances', $dataClasses[1]->getName());
    }

    /**
     * @throws \Exception
     */
    public function testCanSearchByRange(): void
    {
        $apiKey = bin2hex(random_bytes(16));
        $repository = new BreachRepository($this->getClientWithResponse($this->getRangeResponse()), $apiKey);

        $results = $repository->searchByRange('6B5917');

        $this->assertRequest('breachedaccount/range/6b5917', '', ['hibp-api-key' => $apiKey]);
        $this->assertSame([
            '6b5917c6c0aade0c085843d66e4944e108c4a4cd' => ['Adobe', 'Gawker', 'Stratfor'],
            '6b5917000000000000000000000000000000abcd' => ['Dropbox'],
        ], $results);
    }

    /**
     * @throws \Exception
     */
    public function testCanGetBreachesByAccountAnonymously(): void
    {
        $apiKey = bin2hex(random_bytes(16));
        $repository = new BreachRepository($this->getClientWithResponse($this->getRangeResponse()), $apiKey);

        // Addresses are trimmed and lowercased before hashing, as they're case-insensitive
        $breaches = $repository->byAccountAnonymously(' Multiple-Breaches@hibp-integration-tests.com ');

        // Only the first 6 characters of the hash are sent, never the address
        $this->assertRequest('breachedaccount/range/6b5917', '', ['hibp-api-key' => $apiKey]);
        $this->assertSame(['Adobe', 'Gawker', 'Stratfor'], $breaches);
    }

    /**
     * @throws \Exception
     */
    public function testByAccountAnonymouslyReturnsEmptyArrayWhenNotFound(): void
    {
        $repository = new BreachRepository($this->getClientWithResponse($this->getRangeResponse()), 'key');

        // Hashes to a different range than the one in the response, so it can't match
        $this->assertSame([], $repository->byAccountAnonymously('not-pwned@example.com'));
    }

    public function testByAccountAnonymouslyReturnsEmptyArrayOnInvalidJson(): void
    {
        $repository = new BreachRepository($this->getClientWithResponse('invalid json'), 'key');

        $this->assertSame([], $repository->byAccountAnonymously('test@example.com'));
    }

    /**
     * The response for prefix 6B5917, as documented by HIBP, plus one more entry
     */
    private function getRangeResponse(): string
    {
        return (string) json_encode([
            ['hashSuffix' => 'C6C0AADE0C085843D66E4944E108C4A4CD', 'websites' => ['Adobe', 'Gawker', 'Stratfor']],
            ['hashSuffix' => '000000000000000000000000000000ABCD', 'websites' => ['Dropbox']],
        ]);
    }

    private function assertBreachMatchesData(\stdClass $expectedData, Breach $breach): void
    {
        $this->assertInstanceOf(Breach::class, $breach);
        $this->assertSame($expectedData->Name, $breach->getName());
        $this->assertSame($expectedData->Title, $breach->getTitle());
        $this->assertSame($expectedData->Domain, $breach->getDomain());
        $this->assertInstanceOf(\DateTime::class, $breach->getBreached());
        $this->assertSame($expectedData->BreachDate, $breach->getBreached()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTime::class, $breach->getAdded());
        $this->assertSame($expectedData->AddedDate, $breach->getAdded()->format('Y-m-d\TH:i:s\Z'));
        $this->assertInstanceOf(\DateTime::class, $breach->getModified());
        $this->assertSame($expectedData->ModifiedDate, $breach->getModified()->format('Y-m-d\TH:i:s\Z'));
        $this->assertSame($expectedData->PwnCount, $breach->getPwnCount());
        $this->assertSame($expectedData->Description, $breach->getDescription());
        $this->assertSame($expectedData->LogoPath, $breach->getLogo());
        $this->assertSame($expectedData->DataClasses, $breach->getClasses());
        $this->assertSame($expectedData->IsVerified, $breach->isVerified());
        $this->assertSame($expectedData->IsFabricated, $breach->isFabricated());
        $this->assertSame($expectedData->IsSensitive, $breach->isSensitive());
        $this->assertSame($expectedData->IsRetired, $breach->isRetired());
        $this->assertSame($expectedData->IsSpamList, $breach->isSpamList());
        $this->assertSame($expectedData->IsMalware, $breach->isMalware());
        $this->assertSame($expectedData->IsSubscriptionFree, $breach->isSubscriptionFree());
        $this->assertSame($expectedData->IsStealerLog, $breach->isStealerLog());
        $this->assertSame($expectedData->Attribution, $breach->getAttribution());
        $this->assertSame($expectedData->DisclosureUrl, $breach->getDisclosureUrl());
    }
}
