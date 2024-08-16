<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\DuoPlaceNr;

class DuoPlaceNrTest extends TestCase
{

    public function testHomePlaceNrSmallerThanOne(): void
    {
        self::expectException(\Exception::class);
        new DuoPlaceNr(0, 2);
    }

    public function testAwayPlaceNrSmallerThanOne(): void
    {
        self::expectException(\Exception::class);
        new DuoPlaceNr(1, 0);
    }

    public function testHomeAwayPlaceNrsAreEqual(): void
    {
        self::expectException(\Exception::class);
        new DuoPlaceNr(1, 1);
    }

    public function testCount(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 2);
        self::assertCount(2, $duoPlaceNr->getPlaceNrs());
    }

    public function testToString(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 2);
        self::assertSame('1 & 2', (string)$duoPlaceNr);
    }

    public function testGetIndex(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 2);
        self::assertSame((string)$duoPlaceNr, $duoPlaceNr->getIndex());
    }

    public function testCreateUniqueNumberItOne(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 2);
        // 2^(1-1) + 2^(2-1)
        self::assertSame(3, $duoPlaceNr->createUniqueNumber());
    }

    public function testCreateUniqueNumberItTwo(): void
    {
        $duoPlaceNr = new DuoPlaceNr(2, 3);
        // 2^(2-1) + 2^(3-1)
        self::assertSame(6, $duoPlaceNr->createUniqueNumber());
    }

    public function testHasPlaceNrItOne(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 2);
        self::assertTrue($duoPlaceNr->has(1));
        self::assertTrue($duoPlaceNr->has(2));
        self::assertFalse($duoPlaceNr->has(3));
    }

    public function testHasPlaceNrItTwo(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 3);
        self::assertTrue($duoPlaceNr->has(1));
        self::assertTrue($duoPlaceNr->has(3));
        self::assertFalse($duoPlaceNr->has(2));
        self::assertFalse($duoPlaceNr->has(4));
    }

    public function testHasPlaceNrItThree(): void
    {
        $duoPlaceNr = new DuoPlaceNr(2, 3);
        self::assertTrue($duoPlaceNr->has(2));
        self::assertTrue($duoPlaceNr->has(3));
        self::assertFalse($duoPlaceNr->has(1));
        self::assertFalse($duoPlaceNr->has(4));
    }

    public function testHasOverlapItOne(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrTwo = new DuoPlaceNr(2, 3);
        self::assertTrue($duoPlaceNrOne->hasOverlap($duoPlaceNrTwo));
        self::assertTrue($duoPlaceNrTwo->hasOverlap($duoPlaceNrOne));
    }

    public function testHasOverlapItTwo(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrTwo = new DuoPlaceNr(3, 4);
        self::assertFalse($duoPlaceNrOne->hasOverlap($duoPlaceNrTwo));
        self::assertFalse($duoPlaceNrTwo->hasOverlap($duoPlaceNrOne));
    }

    public function testEqualsItOne(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrTwo = new DuoPlaceNr(2, 3);
        self::assertFalse($duoPlaceNrOne->equalsUniqueNumberOf($duoPlaceNrTwo));
        self::assertFalse($duoPlaceNrTwo->equalsUniqueNumberOf($duoPlaceNrOne));
    }

    public function testEqualsItTwo(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrTwo = new DuoPlaceNr(1, 2);
        self::assertTrue($duoPlaceNrOne->equalsUniqueNumberOf($duoPlaceNrTwo));
        self::assertTrue($duoPlaceNrTwo->equalsUniqueNumberOf($duoPlaceNrOne));
    }
}
