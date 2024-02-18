<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsPlanning\Output\BatchOutput as BatchOutput;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Planning as PlanningBase;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\ResourceCounter;
use SportsPlanning\Resource\ResourceType;

class PlanningOutput extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function output(PlanningBase $planning, bool $withInput, string $prefix = null, string $suffix = null, int $colorNr = -1): void
    {
        $this->outputHelper($planning, $withInput, false, false, $prefix, $suffix, $colorNr);
    }

    public function outputWithGames(
        PlanningBase $planning,
        bool $withInput,
        string $prefix = null,
        string $suffix = null
    ): void {
        $this->outputHelper($planning, $withInput, true, false, $prefix, $suffix);
    }

    public function outputWithTotals(
        PlanningBase $planning,
        bool $withInput,
        string $prefix = null,
        string $suffix = null
    ): void {
        $this->outputHelper($planning, $withInput, false, true, $prefix, $suffix);
    }

    protected function outputHelper(
        PlanningBase $planning,
        bool $withInput,
        bool $withGames,
        bool $withTotals,
        string $prefix = null,
        string $suffix = null,
        int $colorNr = -1
    ): void {
        $timeoutState = $planning->getTimeoutState()?->value ?? 'no timeout';
        $output = 'batchGames ' . $planning->getNrOfBatchGames()->getMin()
            . '->' . $planning->getNrOfBatchGames()->getMax()
            . ', gamesInARow ' . $planning->getMaxNrOfGamesInARow()
            . ', timeoutState "' . $timeoutState . '"';
        if ($withInput) {
            $output = $this->getInputConfigurationAsString($planning->createInputConfiguration()) . ', ' . $output;
        }
        $color = $this->convertNumberToColor($colorNr);
        $output = Color::getColored($color, ($prefix ?? '') . $output . ($suffix ?? ''));
        $this->logger->info($output);
        if ($withGames) {
            $batchOutput = new BatchOutput($this->logger);
            $batchOutput->output($planning->createFirstBatch());
        }
        if ($withTotals) {
            $resourceCounter = new ResourceCounter($planning);
            $this->outputTotals($resourceCounter->getCounters());
        }
    }

    public function outputInputConfig(InputConfiguration $inputConfiguration, string $prefix = null, string $suffix = null): void
    {
        $output = $this->getInputConfigurationAsString($inputConfiguration, $prefix, $suffix);
        $this->logger->info($output);
    }

    public function getInputConfigurationAsString(InputConfiguration $inputConfiguration,
                                                  string $prefix = null, string $suffix = null): string
    {
        return ($prefix ?? '') . $inputConfiguration->getName() . ($suffix ?? '');
    }

    /**
     * @param array<int,array<string,GameCounter>> $planningTotals
     */
    protected function outputTotals(array $planningTotals): void
    {
        foreach ($planningTotals as $totalsType => $gameCounters) {
            $name = '';
            if ($totalsType === ResourceType::Fields->value) {
                $name = 'fields';
            } else {
                if ($totalsType === ResourceType::Referees->value) {
                    $name = 'referees';
                } else {
                    if ($totalsType === ResourceType::RefereePlaces->value) {
                        $name = 'refereeplaces';
                    }
                }
            }
            $this->logger->info($this->getPlanningTotalAsString($name, $gameCounters));
        }
    }

    /**
     * @param string $name
     * @param array<string,GameCounter> $gameCounters
     * @return string
     */
    protected function getPlanningTotalAsString(string $name, array $gameCounters): string
    {
        $retVal = "";
        foreach ($gameCounters as $gameCounter) {
            $retVal .= $gameCounter->getIndex() . ":" . $gameCounter->getNrOfGames() . ", ";
        }
        return $name . " => " . $retVal;
    }
}
