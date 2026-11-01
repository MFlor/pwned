<?php

namespace MFlor\Pwned\Tests\Repositories\PasswordRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Models\Password;
use MFlor\Pwned\Repositories\PasswordRepository;
use MFlor\Pwned\Tests\Repositories\RepositoryTestCase;

class OutputTest extends RepositoryTestCase
{
    /**
     * @throws GuzzleException
     */
    public function testCanSearch()
    {
        $testData = $this->getTestData('passwordHashes.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        try {
            $passwords = $repository->search('e38ad');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
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

    /**
     * The real API separates lines with CRLF and, when padding is enabled,
     * adds fake entries with an occurrence count of 0 that must be discarded.
     *
     * @throws GuzzleException
     */
    public function testSearchDiscardsPaddingAndHandlesCrlf(): void
    {
        $testData = $this->getTestData('passwordHashesPadded.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        $passwords = $repository->search('e38ad');

        $this->assertIsArray($passwords);
        $this->assertCount(10, $passwords);
        $this->assertSame(array_keys($passwords), range(0, 9));
        foreach ($passwords as $password) {
            $this->assertGreaterThan(0, $password->getOccurrences());
            $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', (string) $password->getHash());
        }
    }

    public function testCanGetOccurrencesFromPaddedResponse(): void
    {
        $testData = $this->getTestData('passwordHashesPadded.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        $this->assertSame(2401761, $repository->occurrences('password1'));
    }

    /**
     * @throws GuzzleException
     */
    public function testCanSearchNtlm(): void
    {
        $testData = $this->getTestData('ntlmHashesPadded.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        $passwords = $repository->searchNtlm('8846F');

        $this->assertRequest('range/8846F', 'mode=ntlm', ['Add-Padding' => 'true']);
        $this->assertIsArray($passwords);
        // 10 lines, of which 2 are padding
        $this->assertCount(8, $passwords);
        foreach ($passwords as $password) {
            $this->assertGreaterThan(0, $password->getOccurrences());
            // NTLM hashes are 32 characters: the 5 character prefix and a 27 character suffix
            $this->assertMatchesRegularExpression('/^8846f[0-9a-f]{27}$/', (string) $password->getHash());
        }
    }

    /**
     * @throws GuzzleException
     */
    public function testCanSearchNtlmWithoutPadding(): void
    {
        $repository = new PasswordRepository($this->getClientWithResponse());

        $repository->searchNtlm('8846F', false);

        $this->assertRequest('range/8846F', 'mode=ntlm');
    }

    /**
     * @dataProvider ntlmHashProvider
     * @throws GuzzleException
     */
    public function testCanGetOccurrencesByNtlmHash(string $hash): void
    {
        $testData = $this->getTestData('ntlmHashesPadded.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        $occurrences = $repository->occurrencesByNtlmHash($hash);

        $this->assertRequest('range/8846f', 'mode=ntlm', ['Add-Padding' => 'true']);
        $this->assertSame(52372427, $occurrences);
    }

    /**
     * The NTLM hash of "password", in both cases
     *
     * @return string[][]
     */
    public function ntlmHashProvider(): array
    {
        return [
            'uppercase' => ['8846F7EAEE8FB117AD06BDD830B7586C'],
            'lowercase' => ['8846f7eaee8fb117ad06bdd830b7586c'],
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function testOccurrencesByNtlmHashIsZeroWhenNotFound(): void
    {
        $testData = $this->getTestData('ntlmHashesPadded.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        // Matches the prefix, but not any of the suffixes (including the padding entries)
        $this->assertSame(0, $repository->occurrencesByNtlmHash('8846F0123456789ABCDEF0123456789A'));
    }

    /**
     * @throws GuzzleException
     */
    public function testCanSearchWithoutPadding(): void
    {
        $repository = new PasswordRepository($this->getClientWithResponse());

        try {
            $repository->search('e38ad', false);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest('range/e38ad');
    }

    /**
     * @return void
     */
    public function testCanGetOccurrences(): void
    {
        $testData = $this->getTestData('passwordHashes.txt');
        $repository = new PasswordRepository($this->getClientWithResponse($testData));

        try {
            $occurrences = $repository->occurrences('password1');
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest(
            'range/' . substr(hash('sha1', 'password1'), 0, 5),
            '',
            ['Add-Padding' => 'true']
        );
        $this->assertSame(2401761, $occurrences);
    }

    public function testCanGetOccurrencesWithoutPadding(): void
    {
        $repository = new PasswordRepository($this->getClientWithResponse());

        try {
            $repository->occurrences('password1', false);
        } catch (\Exception $exception) {
            $this->fail(sprintf('Test threw unexpected exception (%s)', $exception->getMessage()));
        }
        $this->assertRequest('range/' . substr(hash('sha1', 'password1'), 0, 5));
    }

    /**
     * @dataProvider methodProvider
     *
     * @param string $method
     * @param string $param
     * @param mixed $expected
     */
    public function testMethodsReturnsNullWhenInvalidBodyIsReturned(string $method, string $param, $expected): void
    {
        $mock = new MockHandler([
            new Response(200),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $repository = new PasswordRepository($client);

        $this->assertSame($expected, $repository->$method($param));
    }

    /**
     * @return array[]
     */
    public function methodProvider(): array
    {
        return [
            'search' => ['search', 'abc12', null],
            'occurrences' => ['occurrences', 'password1', 0],
        ];
    }
}
