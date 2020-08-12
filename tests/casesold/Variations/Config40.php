<?php

namespace SportsPlanning\Tests\Variations;

use SportsPlanning\Tests\AssertConfig;

class Config40
{
    public static function get(): array
    {
        return [
            "nrOfPoules" => [
                1 => [
                    "nrOfSports" => [
                        1 => [
                            "nrOfFields" => [
                                20 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(780, -1, 780, [39])/*,
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
