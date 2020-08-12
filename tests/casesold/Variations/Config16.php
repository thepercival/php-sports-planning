<?php

namespace SportsPlanning\Tests\Variations;

use SportsPlanning\Tests\AssertConfig;

class Config16
{
    public static function get(): array
    {
        return [
            "nrOfPoules" => [
                4 => [
                    "nrOfSports" => [
                        1 => [
                            "nrOfFields" => [
                                2 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, 1, 12, [3])/*,
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ],
                                3 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, 1, 8, [3])/*,
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ],
                                4 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, 2, 6, [3])/*,
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),*/
                                    ]
                                ],
                                6 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, 3, 4, [3]),
                                        2 => new AssertConfig(48, 3, 8, [6]),
                                        // 3 => new AssertConfig(30, 4, 15, [12]),
                                        // 4 => new AssertConfig(40, 4, 20, [16]),
                                    ]
                                ],
                                8 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(24, -1, 3, [3]),
                                        2 => new AssertConfig(48, -1, 6, [6]),
                                        3 => new AssertConfig(72, -1, 9, [9]),
                                        4 => new AssertConfig(96, -1, 12, [12]),
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
