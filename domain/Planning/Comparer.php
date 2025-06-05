<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use SportsPlanning\Planning;

final class Comparer
{
    public function compare(Planning|HistoricalBestPlanning $first, Planning|HistoricalBestPlanning $second): int
    {
        if ($first->getNrOfBatches() !== $second->getNrOfBatches()) {
            return $first->getNrOfBatches() - $second->getNrOfBatches();
        }
        if( $first->maxNrOfGamesInARow === $second->maxNrOfGamesInARow  ) {
            $diff = $first->getNrOfBatchGames()->difference() - $second->getNrOfBatchGames()->difference();
            if( $diff === 0 ) {
                return $second->getNrOfBatchGames()->getMin() - $first->getNrOfBatchGames()->getMin();
            }
            return $diff;
        }
        if ($first->maxNrOfGamesInARow > 0 && $second->maxNrOfGamesInARow > 0) {
            return $first->maxNrOfGamesInARow - $second->maxNrOfGamesInARow;
        }
        return $first->maxNrOfGamesInARow === 0 ? 1 : -1;
    }
}
