<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps;

use PHPUnit\Framework\TestCase;

use SportsPlanning\Counters\Maps\PlaceNrCounterMapCreator;

final class PlaceNrCounterMapCreatorTest extends TestCase
{
    public function testItOne(): void
    {
        $map = (new PlaceNrCounterMapCreator())->initPlaceNrCounterMap(3);
        self::assertCount(3, $map);
    }
}
