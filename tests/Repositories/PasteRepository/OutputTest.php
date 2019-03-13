<?php

namespace MFlor\Pwned\Tests\Repositories\PasteRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Models\Paste;
use MFlor\Pwned\Repositories\PasteRepository;
use MFlor\Pwned\Tests\Factories\PastesFactory;
use MFlor\Pwned\Tests\Repositories\RepositoryTestCase;

class OutputTest extends RepositoryTestCase
{
    public function testCanGetPastesByAccount()
    {
        $factory = new PastesFactory();
        $expectedData = $factory->getPastes();
        $testData = json_encode($expectedData);

        $repository = new PasteRepository($this->getClientWithResponse($testData));

        try {
            $pastes = $repository->byAccount('test@example.com');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest('pasteaccount/' . urlencode('test@example.com'));
        $this->assertIsArray($pastes);
        $this->assertCount(count($expectedData), $pastes);
        /**
         * @var int $index
         * @var Paste $paste
         */
        foreach ($pastes as $index => $paste) {
            $this->assertInstanceOf(Paste::class, $paste);
            $expectedPasteData = $expectedData[$index];
            $this->assertSame($expectedPasteData->Source, $paste->getSource());
            $this->assertSame($expectedPasteData->Id, $paste->getId());
            $this->assertSame($expectedPasteData->Title, $paste->getTitle());
            $this->assertSame($expectedPasteData->Date, $paste->getDate()->format('Y-m-d\TH:i:s\Z'));
            $this->assertSame($expectedPasteData->EmailCount, $paste->getEmailCount());
        }
    }

    public function testMethodsReturnsNullWhenInvalidJsonIsReturned()
    {
        $mock = new MockHandler([
            new Response(200, [], 'invalid json'),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $repository = new PasteRepository($client);

        try {
            $result = $repository->byAccount('test@example.com');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertNull($result);
    }
}
