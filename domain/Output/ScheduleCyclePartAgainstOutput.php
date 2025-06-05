<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Output\Combinations\HomeAwayOutput as HomeAwayOutput;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsOne;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsTwo;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstTwoVsTwo;

final class ScheduleCyclePartAgainstOutput extends OutputHelper
{
    private HomeAwayOutput $homeAwayOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->homeAwayOutput = new HomeAwayOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        ScheduleCyclePartAgainstOneVsOne|ScheduleCyclePartAgainstOneVsTwo|ScheduleCyclePartAgainstTwoVsTwo $cyclePart,
        bool                             $showGameRoundHeaderLine,
        string                           $title = null,
        int                              $max = null,
        int                              $min = null
    ): void {
        if ($title !== null) {
            $this->logger->info('------ title: ' . $title . ' -------------');
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->outputHelper($cyclePart, $showGameRoundHeaderLine, $min, $max);
    }

    protected function outputHelper(
        ScheduleCyclePartAgainstOneVsOne|ScheduleCyclePartAgainstOneVsTwo|ScheduleCyclePartAgainstTwoVsTwo $cyclePart,
        bool                             $showGameRoundHeaderLine,
        int|null                         $min = null,
        int|null                         $max = null
    ): void {
        if ($min !== null && $cyclePart->getNumber() < $min) {
            $nextGameRound = $cyclePart->getNext();
            if ($nextGameRound !== null) {
                $this->outputHelper($nextGameRound, $showGameRoundHeaderLine, $max);
            }
            return;
        }
        if ($max !== null && $cyclePart->getNumber() > $max) {
            return;
        }
        if( $showGameRoundHeaderLine) {
            $this->logger->info('------ gameround ' . $cyclePart->getNumber() . ' -------------');
        }
        $this->outputHomeAways($cyclePart->getGamesAsHomeAways(), $cyclePart);
        $nextGameRound = $cyclePart->getNext();
        if ($nextGameRound !== null) {
            $this->outputHelper($nextGameRound, $showGameRoundHeaderLine, $max);
        }
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param ScheduleCyclePartAgainstOneVsOne|ScheduleCyclePartAgainstOneVsTwo|ScheduleCyclePartAgainstTwoVsTwo $cyclePart
     * @param string|null $header
     * @return void
     */
    public function outputHomeAways(
        array                                 $homeAways,
        ScheduleCyclePartAgainstOneVsOne|ScheduleCyclePartAgainstOneVsTwo|ScheduleCyclePartAgainstTwoVsTwo $cyclePart,
        string|null                           $header = null): void
    {
        if ($header !== null) {
            $this->logger->info($header);
        }

        $prefix = '       ' . $this->stringToMinLength('' . $cyclePart->getNumber(), 2) . ' ';
        foreach ($homeAways as $homeAway) {
            $this->homeAwayOutput->output($homeAway, $cyclePart, $prefix);
        }
    }
}
