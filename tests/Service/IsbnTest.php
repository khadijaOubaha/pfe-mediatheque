<?php

namespace App\Tests\Service;

use App\Service\Isbn;
use PHPUnit\Framework\TestCase;

class IsbnTest extends TestCase
{
    public function testIsbn13ValideEstConserve(): void
    {
        $this->assertSame('9782070612758', Isbn::normaliser('9782070612758'));
    }

    public function testLesTiretsEtEspacesSontIgnores(): void
    {
        $this->assertSame('9782070612758', Isbn::normaliser('978-2-07-061275-8'));
        $this->assertSame('9782070612758', Isbn::normaliser(' 978 2070612758 '));
    }

    public function testIsbn10EstConvertiEnIsbn13(): void
    {
        $this->assertSame('9782070612758', Isbn::normaliser('2070612759'));
    }

    public function testCleDeControleIncorrecteEstRefusee(): void
    {
        $this->assertNull(Isbn::normaliser('9782070612759'));
        $this->assertNull(Isbn::normaliser('2070612758'));
    }

    public function testSaisieQuiNEstPasUnIsbnEstRefusee(): void
    {
        $this->assertNull(Isbn::normaliser('abc'));
        $this->assertNull(Isbn::normaliser(''));
        $this->assertNull(Isbn::normaliser('12345'));
    }
}
