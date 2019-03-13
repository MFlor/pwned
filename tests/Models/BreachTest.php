<?php

namespace MFlor\Pwned\Tests\Models;

use MFlor\Pwned\Models\Breach;
use MFlor\Pwned\Tests\Factories\BreachFactory;
use PHPUnit\Framework\TestCase;

class BreachTest extends TestCase
{
    public function testCanConstructEmpty()
    {
        $breach = new Breach();

        $this->assertInstanceOf(Breach::class, $breach);
        $this->assertSame('', $breach->getName());
        $this->assertSame('', $breach->getTitle());
        $this->assertSame('', $breach->getDomain());
        $this->assertNull($breach->getBreached());
        $this->assertNull($breach->getAdded());
        $this->assertNull($breach->getModified());
        $this->assertSame(0, $breach->getPwnCount());
        $this->assertSame('', $breach->getDescription());
        $this->assertSame('', $breach->getLogo());
        $this->assertIsArray($breach->getClasses());
        $this->assertEmpty($breach->getClasses());
        $this->assertFalse($breach->isVerified());
        $this->assertFalse($breach->isFabricated());
        $this->assertFalse($breach->isSensitive());
        $this->assertFalse($breach->isRetired());
        $this->assertFalse($breach->isSpamList());
    }

    public function testCanConstructWithStdClass()
    {
        $breachFactory = new BreachFactory();
        $data = $breachFactory->getBreach();
        $breach = new Breach($data);

        $this->assertSame($data->Name, $breach->getName());
        $this->assertSame($data->Title, $breach->getTitle());
        $this->assertSame($data->Domain, $breach->getDomain());
        $this->assertInstanceOf(\DateTime::class, $breach->getBreached());
        $this->assertSame($data->BreachDate, $breach->getBreached()->format('Y-m-d'));
        $this->assertInstanceOf(\DateTime::class, $breach->getAdded());
        $this->assertSame($data->AddedDate, $breach->getAdded()->format('Y-m-d\TH:i:s\Z'));
        $this->assertInstanceOf(\DateTime::class, $breach->getModified());
        $this->assertSame($data->ModifiedDate, $breach->getModified()->format('Y-m-d\TH:i:s\Z'));
        $this->assertSame($data->PwnCount, $breach->getPwnCount());
        $this->assertSame($data->Description, $breach->getDescription());
        $this->assertSame($data->LogoPath, $breach->getLogo());
        $this->assertSame($data->DataClasses, $breach->getClasses());
        $this->assertSame($data->IsVerified, $breach->isVerified());
        $this->assertSame($data->IsFabricated, $breach->isFabricated());
        $this->assertSame($data->IsSensitive, $breach->isSensitive());
        $this->assertSame($data->IsRetired, $breach->isRetired());
        $this->assertSame($data->IsSpamList, $breach->isSpamList());
    }

    public function testProvidingInvalidDateSetsDatesToNull()
    {
        $data = new \stdClass();
        $data->BreachDate = "this is not a date";
        $data->AddedDate = "this is not a date";
        $data->ModifiedDate = "this is not a date";

        $breach = new Breach($data);
        $this->assertInstanceOf(Breach::class, $breach);
        $this->assertNull($breach->getBreached());
        $this->assertNull($breach->getAdded());
        $this->assertNull($breach->getModified());
    }
}
