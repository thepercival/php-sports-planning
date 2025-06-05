<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\HomeAways;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;

final class OneVsTwoHomeAwayTest extends TestCase
{

    public function testGetHomeAndGetAway(): void
    {
        $away = new DuoPlaceNr(2, 3);
        $homeAway = new OneVsTwoHomeAway(1, $away);
        self::assertSame(1, $homeAway->getHome());
        self::assertSame($away, $homeAway->getAway());
    }

    public function testGetSide(): void
    {
        $away = new DuoPlaceNr(2, 3);
        $homeAway = new OneVsTwoHomeAway(1, $away);
        self::assertSame(1, $homeAway->get(AgainstSide::Home));
        self::assertSame($away, $homeAway->get(AgainstSide::Away));
    }

    public function testHasPlaceNr(): void
    {
        $homeAway = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        self::assertTrue($homeAway->hasPlaceNr(1));
        self::assertTrue($homeAway->hasPlaceNr(2));
        self::assertTrue($homeAway->hasPlaceNr(3));
        self::assertFalse($homeAway->hasPlaceNr(4));

        self::assertTrue($homeAway->hasPlaceNr(1, AgainstSide::Home));
        self::assertFalse($homeAway->hasPlaceNr(1, AgainstSide::Away));

        self::assertFalse($homeAway->hasPlaceNr(2, AgainstSide::Home));
        self::assertTrue($homeAway->hasPlaceNr(2, AgainstSide::Away));
    }

    public function testHasPlaceNrException(): void
    {
        $homeAway = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        self::expectException(\Exception::class);
        $homeAway->hasPlaceNr(0);
    }

    public function testPlaysAgainst(): void
    {
        $homeAway = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        self::assertTrue($homeAway->playsAgainst(1, 2));
        self::assertTrue($homeAway->playsAgainst(2, 1));
        self::assertTrue($homeAway->playsAgainst(1, 3));
        self::assertFalse($homeAway->playsAgainst(1, 4));
        self::assertFalse($homeAway->playsAgainst(2, 4));
    }

    public function testCreateAgainstDuoPlaceNrs(): void
    {
        $homeAway = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $againstDuoPlaceNrs = $homeAway->createAgainstDuoPlaceNrs();
        self::assertCount(2, $againstDuoPlaceNrs);
        self::assertSame(1, $againstDuoPlaceNrs[0]->placeNrOne);
        self::assertSame(2, $againstDuoPlaceNrs[0]->placeNrTwo);
        self::assertSame(1, $againstDuoPlaceNrs[1]->placeNrOne);
        self::assertSame(3, $againstDuoPlaceNrs[1]->placeNrTwo);
    }

    public function testGetWithDuoPlaceNr(): void
    {
        $away = new DuoPlaceNr(2, 3);
        $homeAway = new OneVsTwoHomeAway(1, $away);
        self::assertSame($away, $homeAway->getWithDuoPlaceNr());
    }

    public function testCreateTogetherDuoPlaceNrs(): void
    {
        $homeAway = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $againstDuoPlaceNrs = $homeAway->createTogetherDuoPlaceNrs();
        self::assertCount(3, $againstDuoPlaceNrs);
        self::assertSame(1, $againstDuoPlaceNrs[0]->placeNrOne);
        self::assertSame(2, $againstDuoPlaceNrs[0]->placeNrTwo);
        self::assertSame(1, $againstDuoPlaceNrs[1]->placeNrOne);
        self::assertSame(3, $againstDuoPlaceNrs[1]->placeNrTwo);
        self::assertSame(2, $againstDuoPlaceNrs[2]->placeNrOne);
        self::assertSame(3, $againstDuoPlaceNrs[2]->placeNrTwo);
    }

    public function testEqualsItOne(): void
    {
        $homeAwayOne = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $homeAwayTwo = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        self::assertTrue($homeAwayOne->equals($homeAwayTwo));
    }


    public function testEqualsItTwo(): void
    {
        $homeAwayOne = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $homeAwayTwo = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 4));
        self::assertFalse($homeAwayOne->equals($homeAwayTwo));
    }

    public function testEqualsItThree(): void
    {
        $homeAwayOne = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $homeAwayTwo = new OneVsTwoHomeAway(1, new DuoPlaceNr(3, 2));
        self::assertTrue($homeAwayOne->equals($homeAwayTwo));
    }

    public function testEqualsItFour(): void
    {
        $homeAwayOne = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $homeAwayTwo = new OneVsOneHomeAway(1, 2);
        self::assertFalse($homeAwayOne->equals($homeAwayTwo));
    }

    public function testHasOverlapItOne(): void
    {
        $homeAwayOne = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $homeAwayTwo = new OneVsTwoHomeAway(3, new DuoPlaceNr(4, 5));
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testHasOverlapItTwo(): void
    {
        $homeAwayOne = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $homeAwayTwo = new OneVsTwoHomeAway(4, new DuoPlaceNr(3, 5));
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testHasOverlapItThree(): void
    {
        $homeAwayOne = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        $homeAwayTwo = new OneVsTwoHomeAway(4, new DuoPlaceNr(5, 6));
        self::assertFalse($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testConvertToPlacesWithSide(): void
    {
        $homeAway = new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3));
        self::assertCount(1, $homeAway->convertToPlaceNrs(AgainstSide::Home));
        self::assertCount(2, $homeAway->convertToPlaceNrs(AgainstSide::Away));
        self::assertSame(1, $homeAway->convertToPlaceNrs(AgainstSide::Home)[0]);
        self::assertSame(2, $homeAway->convertToPlaceNrs(AgainstSide::Away)[0]);
        self::assertSame(3, $homeAway->convertToPlaceNrs(AgainstSide::Away)[1]);
    }
}
