<?php

namespace SportsPlanning\Tests\Variations;

use SportsPlanning\Tests\AssertConfig;

class Config2
{
    public static function get(): array
    {
        return [
            "nrOfPoules" => [
                1 => [
                    "nrOfSports" => [
                        1 => [
                            "nrOfFields" => [
                                1 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(1, -1, 1, [1]),
                                        2 => new AssertConfig(2, -1, 2, [2]),
                                        3 => new AssertConfig(3, -1, 3, [3]),
                                        4 => new AssertConfig(4, -1, 4, [4]),
                                    ]
                                ],
                                2 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(1, -1, 1, [1]),
                                        2 => new AssertConfig(2, -1, 2, [2]),
                                        3 => new AssertConfig(3, -1, 3, [3]),
                                        4 => new AssertConfig(4, -1, 4, [4]),
                                    ]
                                ]
                            ]
                        ]/*,
                        2: {
                            nrOfFields: {
                                2: {
                                    nrOfHeadtohead: {
                                        1: { nrOfGames: 2, maxNrOfGamesInARow: 2, maxNrOfBatches: 2, nrOfPlaceGames: 2 },
                                        2: { nrOfGames: 2, maxNrOfGamesInARow: 2, maxNrOfBatches: 2, nrOfPlaceGames: 2 },
                                        3: { nrOfGames: 3, maxNrOfGamesInARow: 3, maxNrOfBatches: 3, nrOfPlaceGames: 3 },
                                        4: { nrOfGames: 4, maxNrOfGamesInARow: 4, maxNrOfBatches: 4, nrOfPlaceGames: 4 },
                                    }
                                },
                                3: {
                                    nrOfHeadtohead: {
                                        1: { nrOfGames: 3, maxNrOfGamesInARow: 3, maxNrOfBatches: 3, nrOfPlaceGames: 3 },
                                        2: { nrOfGames: 3, maxNrOfGamesInARow: 3, maxNrOfBatches: 3, nrOfPlaceGames: 3 },
                                        3: { nrOfGames: 3, maxNrOfGamesInARow: 3, maxNrOfBatches: 3, nrOfPlaceGames: 3 },
                                        4: { nrOfGames: 4, maxNrOfGamesInARow: 4, maxNrOfBatches: 4, nrOfPlaceGames: 4 },
                                    }
                                },
                                4: {
                                    nrOfHeadtohead: {
                                        1: { nrOfGames: 2, maxNrOfGamesInARow: 2, maxNrOfBatches: 2, nrOfPlaceGames: 2 },
                                        2: { nrOfGames: 2, maxNrOfGamesInARow: 2, maxNrOfBatches: 2, nrOfPlaceGames: 2 },
                                        3: { nrOfGames: 4, maxNrOfGamesInARow: 4, maxNrOfBatches: 4, nrOfPlaceGames: 4 },
                                        4: { nrOfGames: 4, maxNrOfGamesInARow: 4, maxNrOfBatches: 4, nrOfPlaceGames: 4 },
                                    }
                                }
                            }
                        }*/
                    ]
                ]
            ]
        ];
    }
}
