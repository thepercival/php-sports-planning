<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Resource\GameCounter;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\CounterForPlaceNr;

final class PlaceTest extends TestCase
{

    public function testCalculations(): void
    {
        $gameCounter = new CounterForPlaceNr(1);

        self::assertSame(1, $gameCounter->getPlaceNr());
    }
}
