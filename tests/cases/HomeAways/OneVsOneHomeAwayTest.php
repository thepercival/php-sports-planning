<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\HomeAways;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;

class OneVsOneHomeAwayTest extends TestCase
{

    public function testGetHomeAndGetAway(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        self::assertSame(1, $homeAway->getHome());
        self::assertSame(2, $homeAway->getAway());
    }

    public function testHasPlaceNr(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        self::assertTrue($homeAway->hasPlaceNr(1));
        self::assertTrue($homeAway->hasPlaceNr(2));
        self::assertFalse($homeAway->hasPlaceNr(3));

        self::assertTrue($homeAway->hasPlaceNr(1, Side::Home));
        self::assertFalse($homeAway->hasPlaceNr(1, Side::Away));

        self::assertFalse($homeAway->hasPlaceNr(2, Side::Home));
        self::assertTrue($homeAway->hasPlaceNr(2, Side::Away));
    }

    public function testPlaysAgainst(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        self::assertTrue($homeAway->playsAgainst(1, 2));
        self::assertTrue($homeAway->playsAgainst(2, 1));
        self::assertFalse($homeAway->playsAgainst(1, 3));
        self::assertFalse($homeAway->playsAgainst(2, 3));
    }

    public function testCreateAgainstDuoPlaceNr(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        $againstDuoPlaceNr = $homeAway->createAgainstDuoPlaceNr();
        self::assertSame($homeAway->getHome(), $againstDuoPlaceNr->placeNrOne);
        self::assertSame($homeAway->getAway(), $againstDuoPlaceNr->placeNrTwo);
    }

    public function testEqualsItOne(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(1, 2);
        self::assertTrue($homeAwayOne->equals($homeAwayTwo));
    }


    public function testEqualsItTwo(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(1, 3);
        self::assertFalse($homeAwayOne->equals($homeAwayTwo));
    }

    public function testEqualsItThree(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(3, 1);
        self::assertFalse($homeAwayOne->equals($homeAwayTwo));
    }

    public function testEqualsItFour(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(2, 1);
        self::assertTrue($homeAwayOne->equals($homeAwayTwo));
    }

    public function testEqualsItFive(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        self::assertFalse($homeAwayOne->equals($homeAwayTwo));
    }

    public function testHasOverlapItOne(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(1, 2);
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testHasOverlapItTwo(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(2, 1);
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testHasOverlapItThree(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(1, 3);
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testHasOverlapItFour(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(2, 3);
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testHasOverlapItFive(): void
    {
        $homeAwayOne = new OneVsOneHomeAway(1, 2);
        $homeAwayTwo = new OneVsOneHomeAway(3, 4);
        self::assertFalse($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testSwap(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        $homeAwaySwapped = $homeAway->swap();
        self::assertTrue($homeAway->getHome() === $homeAwaySwapped->getAway());
        self::assertTrue($homeAway->getAway() === $homeAwaySwapped->getHome());
    }

    public function testConvertToPlacesWithSide(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        self::assertCount(1, $homeAway->convertToPlaceNrs(Side::Home));
        self::assertCount(1, $homeAway->convertToPlaceNrs(Side::Away));
        self::assertSame(1, $homeAway->convertToPlaceNrs(Side::Home)[0]);
        self::assertSame(2, $homeAway->convertToPlaceNrs(Side::Away)[0]);
    }

    public function testGetIndex(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        self::assertSame('1 vs 2', $homeAway->getIndex());
    }

    public function testToString(): void
    {
        $homeAway = new OneVsOneHomeAway(1, 2);
        self::assertSame('1 vs 2', (string)$homeAway);
    }
}