<?php

namespace MFlor\Pwned\Tests\Repositories\PasswordRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Models\Password;
use MFlor\Pwned\Repositories\PasswordRepository;
use MFlor\Pwned\Tests\Repositories\RepositoryTestCase;

class OutputTest extends RepositoryTestCase
{
    public function testCanSearch()
    {
        $testData = $this->getTestData('passwordHashes.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        try {
            $passwords = $repository->search('e38ad');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest('range/e38ad', '', ['Add-Padding' => 'true']);
        $this->assertIsArray($passwords);
        $this->assertCount(10, $passwords);
        $lines = explode("\n", $testData);
        $expectedData = array_map(function (string $line) {
            return explode(':', $line);
        }, $lines);
        /**
         * @var int $index
         * @var Password $password
         */
        foreach ($passwords as $index => $password) {
            $this->assertInstanceOf(Password::class, $password);
            $expectedParts = $expectedData[$index];
            $this->assertSame('e38ad' . strtolower($expectedParts[0]), $password->getHash());
            $this->assertSame(intval($expectedParts[1]), $password->getoccurrences());
        }
    }

    public function testCanSearchWithoutPadding()
    {
        $repository = new PasswordRepository($this->getClientWithResponse());

        try {
            $repository->search('e38ad', false);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest('range/e38ad', '', null);
    }

    public function testCanGetoccurrences()
    {
        $testData = $this->getTestData('passwordHashes.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        try {
            $occurrences = $repository->occurrences('password1');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest(
            'range/' . substr(hash('sha1', 'password1'), 0, 5),
            '',
            ['Add-Padding' => 'true']
        );
        $this->assertSame(2401761, $occurrences);
    }

    public function testCanGetoccurrencesWithoutPadding()
    {
        $repository = new PasswordRepository($this->getClientWithResponse());

        try {
            $repository->occurrences('password1', false);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest(
            'range/' . substr(hash('sha1', 'password1'), 0, 5),
            '',
            null
        );
    }

    /**
     * @dataProvider methodProvider
     *
     * @param string $method
     * @param string $param
     * @param mixed $expected
     */
    public function testMethodsReturnsNullWhenInvalidBodyIsReturned(string $method, string $param, $expected)
    {
        $mock = new MockHandler([
            new Response(200),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $repository = new PasswordRepository($client);

        $this->assertSame($expected, $repository->$method($param));
    }

    public function methodProvider()
    {
        return [
            'search' => ['search', 'abc12', null],
            'occurrences' => ['occurrences', 'password1', 0],
        ];
    }
}
