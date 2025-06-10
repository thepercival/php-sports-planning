<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\SportRange;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;

final class BatchOutput extends OutputHelper
{
    private GameOutput $gameOutput;
    private PlaceOutput $placeOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->gameOutput = new GameOutput($logger);
        $this->placeOutput = new PlaceOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        string                                                      $title = null,
        SportRange|null                                             $numberRange = null,
        bool                                                        $showUnassigned = false
    ): void {
        if ($title === null) {
            $title = '';
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->outputHelper($batch->getFirst(), $numberRange, $showUnassigned);
    }

    protected function outputHelper(
        Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch,
        SportRange|null                                             $numberRange = null,
        bool                                                        $showUnassigned = false
    ): void {
        if ($numberRange !== null && $batch->getNumber() < $numberRange->getMin()) {
            $nextBatch = $batch->getNext();
            if ($nextBatch !== null) {
                $this->outputHelper($nextBatch, $numberRange, $showUnassigned);
            }
            return;
        }
        if ($numberRange !== null && $batch->getNumber() > $numberRange->getMax()) {
            return;
        }

        $this->outputGames($batch);
        if ($showUnassigned) {
            $this->outputUnassigned($batch);
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->outputHelper($nextBatch, $numberRange, $showUnassigned);
        }
    }

    public function outputGames(Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): void {
        foreach ($batch->getGames() as $game) {
            $gamePoule = $batch->getPoule($game->pouleNr);
            $this->gameOutput->output($gamePoule, $game, $batch);
        }
    }

    protected function outputUnassigned(Batch|SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule $batch): void {
        $useColors = $this->useColors();
        $unassignedPlaces = $batch instanceof Batch ? $batch->getUnassignedPlaces() : $batch->getUnassignedPlaces(
            true
        );
        $placesAsArrayOfStrings = array_map(
            function (Place $place) use ($useColors, $batch): string {
                $previous = $batch->getPrevious();
                $gamesInARow = $previous?->getGamesInARow($place);
                return $this->placeOutput->getPlace(
                    $place,
                    $gamesInARow !== null ? '(' . $gamesInARow . ')' : '',
                    $useColors
                );
            },
            $unassignedPlaces
        );
        $this->logger->info(
            'unassigned places: ' . implode(' & ', $placesAsArrayOfStrings)
        );
    }
}
