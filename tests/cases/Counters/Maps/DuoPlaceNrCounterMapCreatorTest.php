<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMapCreator;

class DuoPlaceNrCounterMapCreatorTest extends TestCase
{
    public function testItOne(): void
    {
        $map = (new DuoPlaceNrCounterMapCreator())->initDuoPlaceNrCounterMap(4);
        self::assertCount(6, $map);
    }
}
