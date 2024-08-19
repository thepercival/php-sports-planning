<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\HomeAways;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class TwoVsTwoHomeAwayTest extends TestCase
{

    public function testGetHomeAndGetAway(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        self::assertSame($home, $homeAway->getHome());
        self::assertSame($away, $homeAway->getAway());
    }

    public function testGetSide(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        self::assertSame($home, $homeAway->get(Side::Home));
        self::assertSame($away, $homeAway->get(Side::Away));
    }

    public function testHasPlaceNr(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        self::assertTrue($homeAway->hasPlaceNr(1));
        self::assertTrue($homeAway->hasPlaceNr(2));
        self::assertTrue($homeAway->hasPlaceNr(3));
        self::assertTrue($homeAway->hasPlaceNr(4));
        self::assertFalse($homeAway->hasPlaceNr(0));
        self::assertFalse($homeAway->hasPlaceNr(5));

        self::assertTrue($homeAway->hasPlaceNr(1, Side::Home));
        self::assertFalse($homeAway->hasPlaceNr(1, Side::Away));

        self::assertTrue($homeAway->hasPlaceNr(2, Side::Home));
        self::assertFalse($homeAway->hasPlaceNr(2, Side::Away));

        self::assertFalse($homeAway->hasPlaceNr(3, Side::Home));
        self::assertTrue($homeAway->hasPlaceNr(3, Side::Away));

        self::assertFalse($homeAway->hasPlaceNr(4, Side::Home));
        self::assertTrue($homeAway->hasPlaceNr(4, Side::Away));
    }

    public function testPlaysAgainst(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        self::assertFalse($homeAway->playsAgainst(1, 2));
        self::assertTrue($homeAway->playsAgainst(1, 3));
        self::assertTrue($homeAway->playsAgainst(3, 1));
        self::assertTrue($homeAway->playsAgainst(1, 4));
        self::assertFalse($homeAway->playsAgainst(1, 5));

        self::assertTrue($homeAway->playsAgainst(2, 3));
        self::assertTrue($homeAway->playsAgainst(2, 4));
        self::assertFalse($homeAway->playsAgainst(2, 5));

    }

    public function testCreateAgainstDuoPlaceNrs(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        $againstDuoPlaceNrs = $homeAway->createAgainstDuoPlaceNrs();
        self::assertCount(4, $againstDuoPlaceNrs);
        self::assertSame(1, $againstDuoPlaceNrs[0]->placeNrOne);
        self::assertSame(3, $againstDuoPlaceNrs[0]->placeNrTwo);
        self::assertSame(1, $againstDuoPlaceNrs[1]->placeNrOne);
        self::assertSame(4, $againstDuoPlaceNrs[1]->placeNrTwo);
        self::assertSame(2, $againstDuoPlaceNrs[2]->placeNrOne);
        self::assertSame(3, $againstDuoPlaceNrs[2]->placeNrTwo);
        self::assertSame(2, $againstDuoPlaceNrs[3]->placeNrOne);
        self::assertSame(4, $againstDuoPlaceNrs[3]->placeNrTwo);
    }

    public function testGetWithDuoPlaceNr(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        $withDuoPlaceNrs = $homeAway->createWithDuoPlaceNrs();
        self::assertCount(2, $withDuoPlaceNrs);
        self::assertSame(1, $withDuoPlaceNrs[0]->placeNrOne);
        self::assertSame(2, $withDuoPlaceNrs[0]->placeNrTwo);
        self::assertSame(3, $withDuoPlaceNrs[1]->placeNrOne);
        self::assertSame(4, $withDuoPlaceNrs[1]->placeNrTwo);
    }

    public function testCreateTogetherDuoPlaceNrs(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        $againstDuoPlaceNrs = $homeAway->createTogetherDuoPlaceNrs();
        self::assertCount(6, $againstDuoPlaceNrs);
    }

    public function testEqualsItOne(): void
    {
        $homeAwayOne = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 4));
        $homeAwayTwo = new TwoVsTwoHomeAway(new DuoPlaceNr(2, 1), new DuoPlaceNr(4, 3));
        self::assertTrue($homeAwayOne->equals($homeAwayTwo));
    }


    public function testEqualsItTwo(): void
    {
        $homeAwayOne = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 4));
        $homeAwayTwo = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 5));
        self::assertFalse($homeAwayOne->equals($homeAwayTwo));
    }

    public function testEqualsItThree(): void
    {
        $homeAwayOne = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 4));
        $homeAwayTwo = new OneVsOneHomeAway(new DuoPlaceNr(1, 2));
        self::assertFalse($homeAwayOne->equals($homeAwayTwo));
    }

    public function testHasOverlapItOne(): void
    {
        $homeAwayOne = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 4));
        $homeAwayTwo = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 5), new DuoPlaceNr(6, 7));
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));

        $homeAwayOne = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 4));
        $homeAwayTwo = new TwoVsTwoHomeAway(new DuoPlaceNr(5, 1), new DuoPlaceNr(6, 7));
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));

        $homeAwayOne = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 4));
        $homeAwayTwo = new TwoVsTwoHomeAway(new DuoPlaceNr(5, 6), new DuoPlaceNr(1, 7));
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));

        $homeAwayOne = new TwoVsTwoHomeAway(new DuoPlaceNr(1, 2), new DuoPlaceNr(3, 4));
        $homeAwayTwo = new TwoVsTwoHomeAway(new DuoPlaceNr(5, 6), new DuoPlaceNr(7, 1));
        self::assertTrue($homeAwayOne->hasOverlap($homeAwayTwo));
    }

    public function testSwap(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        $homeAwaySwapped = $homeAway->swap();
        self::assertTrue($homeAway->getHome() === $homeAwaySwapped->getAway());
        self::assertTrue($homeAway->getAway() === $homeAwaySwapped->getHome());
    }

    public function testConvertToPlacesWithSide(): void
    {
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 4);
        $homeAway = new TwoVsTwoHomeAway($home, $away);
        self::assertCount(2, $homeAway->convertToPlaceNrs(Side::Home));
        self::assertCount(2, $homeAway->convertToPlaceNrs(Side::Away));
        self::assertSame(1, $homeAway->convertToPlaceNrs(Side::Home)[0]);
        self::assertSame(2, $homeAway->convertToPlaceNrs(Side::Home)[1]);
        self::assertSame(3, $homeAway->convertToPlaceNrs(Side::Away)[0]);
        self::assertSame(4, $homeAway->convertToPlaceNrs(Side::Away)[1]);
    }

    public function testValidate(): void
    {
        self::expectException(\Exception::class);
        $home = new DuoPlaceNr(1, 2);
        $away = new DuoPlaceNr(3, 2);
        new TwoVsTwoHomeAway($home, $away);
    }
}
