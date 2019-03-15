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
        $this->assertRequest('range/e38ad');
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
            $this->assertSame(intval($expectedParts[1]), $password->getOccurences());
        }
    }

    public function testCanGetOccurences()
    {
        $testData = $this->getTestData('passwordHashes.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        try {
            $occurences = $repository->occurences('password1');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
            return;
        }
        $this->assertRequest('range/' . substr(hash('sha1', 'password1'), 0, 5));
        $this->assertSame(2401761, $occurences);
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
            'occurences' => ['occurences', 'password1', 0],
        ];
    }
}
