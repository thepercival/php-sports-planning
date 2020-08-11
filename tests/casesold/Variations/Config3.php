<?php

namespace SportsPlanning\Tests\Planning\Variations;

use SportsPlanning\Tests\Planning\AssertConfig;

class Config3
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
                                        1 => new AssertConfig(3, 2, 3, [2]),
                                        2 => new AssertConfig(6, 2, 6, [4]),
                                        3 => new AssertConfig(9, 2, 9, [6]),
                                        4 => new AssertConfig(12, 2, 12, [8]),
                                    ]
                                ],
                                2 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(3, 2, 3, [2]),
                                        2 => new AssertConfig(6, 2, 6, [4]),
                                        3 => new AssertConfig(9, 2, 9, [6]),
                                        4 => new AssertConfig(12, 2, 12, [8]),
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
