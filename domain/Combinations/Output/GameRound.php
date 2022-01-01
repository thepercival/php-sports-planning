<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\Output\HomeAway as HomeAwayOutput;
use SportsPlanning\GameRound\Against as AgainstGameRound;

class GameRound extends OutputHelper
{
    private HomeAwayOutput $homeAwayOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->homeAwayOutput = new HomeAwayOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        AgainstGameRound $gameRound,
        string $title = null,
        int $max = null,
        int $min = null
    ): void {
        if ($title === null) {
            $title = '';
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------gameround ' . $gameRound->getNumber() . ' ' . $title . ' -------------');
        $this->outputHelper($gameRound->getFirst(), $min, $max);
    }

    protected function outputHelper(
        AgainstGameRound $gameRound,
        int|null $min = null,
        int|null $max = null
    ): void {
        if ($min !== null && $gameRound->getNumber() < $min) {
            $nextGameRound = $gameRound->getNext();
            if ($nextGameRound !== null) {
                $this->outputHelper($nextGameRound, $max);
            }
            return;
        }
        if ($max !== null && $gameRound->getNumber() > $max) {
            return;
        }

        $this->outputHomeAways($gameRound->getHomeAways());
        $nextGameRound = $gameRound->getNext();
        if ($nextGameRound !== null) {
            $this->outputHelper($nextGameRound, $max);
        }
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @param AgainstGameRound|null $gameRound
     * @return void
     */
    public function outputHomeAways(array $homeAways, AgainstGameRound|null $gameRound = null, string|null $header = null): void
    {
        if ($header !== null) {
            $this->logger->info($header);
        }
        $prefix = ''; // $gameRound->getNumber() . ' : ';
        foreach ($homeAways as $homeAway) {
            $this->homeAwayOutput->output($homeAway, $gameRound, $prefix);
        }
    }
}
