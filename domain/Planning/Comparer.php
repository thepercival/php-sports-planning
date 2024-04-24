<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use SportsPlanning\Planning;

class Comparer
{
    public function compare(Planning|HistoricalBestPlanning $first, Planning|HistoricalBestPlanning $second): int
    {
        if ($first->getNrOfBatches() !== $second->getNrOfBatches()) {
            return $first->getNrOfBatches() - $second->getNrOfBatches();
        }
        if( $first->getMaxNrOfGamesInARow() === $second->getMaxNrOfGamesInARow()  ) {
            $diff = $first->getNrOfBatchGames()->difference() - $second->getNrOfBatchGames()->difference();
            if( $diff === 0 ) {
                return $second->getNrOfBatchGames()->getMin() - $first->getNrOfBatchGames()->getMin();
            }
            return $diff;
        }
        if ($first->getMaxNrOfGamesInARow() > 0 && $second->getMaxNrOfGamesInARow() > 0) {
            return $first->getMaxNrOfGamesInARow() - $second->getMaxNrOfGamesInARow();
        }
        return $first->getMaxNrOfGamesInARow() === 0 ? 1 : -1;
    }
}
