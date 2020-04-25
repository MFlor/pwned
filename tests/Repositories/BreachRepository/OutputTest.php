<?php

namespace MFlor\Pwned\Tests\Repositories\BreachRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Models\Breach;
use MFlor\Pwned\Repositories\BreachRepository;
use MFlor\Pwned\Tests\Factories\BreachFactory;
use MFlor\Pwned\Tests\Repositories\RepositoryTestCase;

class OutputTest extends RepositoryTestCase
{
    /** @var BreachFactory */
    private $breachFactory;

    public function setUp(): void
    {
        $this->breachFactory = new BreachFactory();
    }

    public function testCanGetAllBreaches()
    {
        $expectedBreaches = $this->breachFactory->getBreaches();
        $testData = json_encode($expectedBreaches);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $breaches = $repository->getAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest('breaches');
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        foreach ($expectedBreaches as $index => $expectedBreach) {
            $this->assertBreachMatchesData($expectedBreach, $breaches[$index]);
        }
    }

    public function testCanGetBreachesByDomain()
    {
        $expectedBreaches = $this->breachFactory->getBreaches();
        $testData = json_encode($expectedBreaches);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $breaches = $repository->byDomain('example.com');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest('breaches', 'domain=example.com');
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        foreach ($expectedBreaches as $index => $expectedBreach) {
            $this->assertBreachMatchesData($expectedBreach, $breaches[$index]);
        }
    }

    public function testCanGetBreachByName()
    {
        $expectedBreach = $this->breachFactory->getBreach();
        $testData = json_encode($expectedBreach);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $breach = $repository->byName('Adobe');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest('breach/Adobe');
        $expectedBreach = json_decode($testData);
        $this->assertBreachMatchesData($expectedBreach, $breach);
    }

    public function testCanGetFullBreachesByAccount()
    {
        $expectedBreaches = $this->breachFactory->getBreaches();
        $testData = json_encode($expectedBreaches);
        $apiKey = bin2hex(random_bytes(16));

        $repository = new BreachRepository($this->getClientWithResponse($testData), $apiKey);

        try {
            $breaches = $repository->byAccount('test@example.com', ['truncateResponse' => false]);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest(
            'breachedaccount/' . urlencode('test@example.com'),
            'truncateResponse=0',
            ['hibp-api-key' => $apiKey]
        );
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        foreach ($expectedBreaches as $index => $expectedBreach) {
            $this->assertBreachMatchesData($expectedBreach, $breaches[$index]);
        }
    }

    public function testCanGetBreachNamesByAccount()
    {
        $expectedBreaches = $this->breachFactory->getNames();
        $testData = json_encode($expectedBreaches);
        $apiKey = bin2hex(random_bytes(16));

        $repository = new BreachRepository($this->getClientWithResponse($testData), $apiKey);

        try {
            $breaches = $repository->byAccount('test@example.com');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest(
            'breachedaccount/' . urlencode('test@example.com'),
            '',
            ['hibp-api-key' => $apiKey]
        );
        $this->assertIsArray($breaches);
        $this->assertCount(count($expectedBreaches), $breaches);
        $this->assertSame($expectedBreaches, $breaches);
    }

    public function testCanGetDataClasses()
    {
        $expectedClasses = $this->breachFactory->getDataClasses();
        $testData = json_encode($expectedClasses);

        $repository = new BreachRepository($this->getClientWithResponse($testData));

        try {
            $dataClasses = $repository->getDataClasses();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
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
    public function testMethodsReturnsNullWhenInvalidJsonIsReturned(string $method, ?string $param = null)
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

    public function methodProvider()
    {
        return [
            'getAll' => ['getAll'],
            'byDomain' => ['byDomain', 'adobe.com'],
            'byName' => ['byName', 'Adobe'],
            'byAccount' => ['byAccount', 'test@example.com'],
            'getDataClasses' => ['getDataClasses'],
        ];
    }

    private function assertBreachMatchesData(\stdClass $expectedData, Breach $breach)
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
    }
}
