<?php

namespace MFlor\Pwned\Tests\Models;

use MFlor\Pwned\Models\Paste;
use PHPUnit\Framework\TestCase;

class PasteTest extends TestCase
{
    public function testCanCreateEmptyPaste()
    {
        $paste = new Paste();
        $this->assertInstanceOf(Paste::class, $paste);
        $this->assertSame('', $paste->getSource());
        $this->assertSame('', $paste->getID());
        $this->assertSame('', $paste->getTitle());
        $this->assertNull($paste->getDate());
        $this->assertSame(0, $paste->getEmailCount());
    }

    public function testCanCreatePasteFromData()
    {
        $data = new \stdClass();
        $data->Source = "Pastebin";
        $data->Id = "8Q0BvKD8";
        $data->Title = "syslog";
        $data->Date = "2014-03-04T19:14:54Z";
        $data->EmailCount = 139;

        $paste = new Paste($data);
        $this->assertInstanceOf(Paste::class, $paste);
        $this->assertSame($data->Source, $paste->getSource());
        $this->assertSame($data->Id, $paste->getID());
        $this->assertSame($data->Title, $paste->getTitle());
        $this->assertInstanceOf(\DateTime::class, $paste->getDate());
        $this->assertSame($data->Date, $paste->getDate()->format('Y-m-d\TH:i:s\Z'));
        $this->assertSame($data->EmailCount, $paste->getEmailCount());
    }

    public function testProvidingInvalidDateSetsDateToNull()
    {
        $data = new \stdClass();
        $data->Date = "this is not a date";

        $paste = new Paste($data);
        $this->assertInstanceOf(Paste::class, $paste);
        $this->assertNull($paste->getDate());
    }
}
