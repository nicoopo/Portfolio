<?php

namespace App\Tests;

use App\Entity\Projet;
use PHPUnit\Framework\TestCase;

final class ProjetTest extends TestCase
{
    public function testLesTechnosSontDecoupeesSansVides(): void
    {
        self::assertSame(['Symfony', 'Docker'], (new Projet())->setTech(' Symfony, Docker ,')->getTechs());
        self::assertSame([], (new Projet())->setTech('')->getTechs());
    }
}
