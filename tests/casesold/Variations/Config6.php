<?php

namespace SportsPlanning\Tests\Variations;

use SportsPlanning\Tests\AssertConfig;

class Config6
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
                                        1 => new AssertConfig(10, 1, 10, [4]),
                                        2 => new AssertConfig(20, 1, 20, [8]),
                                        3 => new AssertConfig(30, 1, 30, [12]),
                                        4 => new AssertConfig(40, 1, 40, [16]),
                                    ]
                                ],
                                2 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(10, 4, 5, [4]),
                                        2 => new AssertConfig(20, 4, 10, [8]),
                                        3 => new AssertConfig(30, 4, 15, [12]),
                                        4 => new AssertConfig(40, 4, 20, [16]),
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
                ],
                2 => [
                    "nrOfSports" => [
                        1 => [
                            "nrOfFields" => [
                                1 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(4, 2, 4, [1,2]),
                                        2 => new AssertConfig(8, 2, 8, [2,4]),
                                        3 => new AssertConfig(12, 2, 12, [3,6]),
                                        4 => new AssertConfig(16, 2, 16, [4,8]),
                                    ]
                                ],
                                2 => [
                                    "nrOfHeadtohead" => [
                                        1 => new AssertConfig(4, 2, 4, [1,2]),
                                        2 => new AssertConfig(8, 2, 6, [2,4]),
                                        3 => new AssertConfig(12, 2, 10, [3,6]),
                                        4 => new AssertConfig(16, 2, 13, [4,8]),
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
