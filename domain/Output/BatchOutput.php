<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\SportRange;
use SportsPlanning\Batch as BatchBase;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Output\GameOutput as GameOutput;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Output\PlaceOutput as PlaceOutput;

final class BatchOutput extends OutputHelper
{
    private GameOutput $gameOutput;
    private PlaceOutput $placeOutput;

    public function __construct(LoggerInterface $logger)
    {
        $this->gameOutput = new GameOutput($logger);
        $this->placeOutput = new PlaceOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        string|null $title = null,
        SportRange|null $numberRange = null,
        bool $showUnassigned = false
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
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        SportRange|null $numberRange = null,
        bool $showUnassigned = false
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

        $this->outputGames($batch->getGames(), $batch);
        if ($showUnassigned) {
            $this->outputUnassigned($batch);
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->outputHelper($nextBatch, $numberRange, $showUnassigned);
        }
    }

    /**
     * @param list<AgainstGame|TogetherGame> $games
     * @param BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule|null $batch
     * @return void
     */
    public function outputGames(
        array $games,
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule|null $batch = null
    ): void {
        foreach ($games as $game) {
            $this->gameOutput->output($game, $batch);
        }
    }

    protected function outputUnassigned(
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch
    ): void {
        $useColors = $this->useColors();
        $unassignedPlaces = $batch instanceof BatchBase ? $batch->getUnassignedPlaces() : $batch->getUnassignedPlaces(
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
