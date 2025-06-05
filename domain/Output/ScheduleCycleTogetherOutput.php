<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;

final class ScheduleCycleTogetherOutput extends OutputHelper
{
    private GameOutput $gameOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->gameOutput = new GameOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        ScheduleCycleTogether $cycle,
        string                $title = null,
        int                   $max = null,
        int                   $min = null
    ): void {
        if ($title !== null) {
            $this->logger->info('------ title: ' . $title . ' -------------');
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->outputHelper($cycle->getFirst(), $min, $max);
    }

    protected function outputHelper(
        ScheduleCycleTogether   $cycle,
        int|null                $min = null,
        int|null                $max = null
    ): void {
        if ($min !== null && $cycle->getNumber() < $min) {
            $nextGameRound = $cycle->getNext();
            if ($nextGameRound !== null) {
                $this->outputHelper($nextGameRound, $max);
            }
            return;
        }
        if ($max !== null && $cycle->getNumber() > $max) {
            return;
        }

        foreach ($cycle->getGames() as $game) {
            $this->logger->info($game);
        }

        $nextGameRound = $cycle->getNext();
        if ($nextGameRound !== null) {
            $this->outputHelper($nextGameRound, $max);
        }
    }
}
