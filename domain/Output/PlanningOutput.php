<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Output\BatchOutput;
use SportsPlanning\Output\PlanningOutput\Extra;
use SportsPlanning\Planning;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\ResourceCounter;
use SportsPlanning\Resource\ResourceType;

final class PlanningOutput extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function output(Planning $planning, int $extra, string $prefix = null, string $suffix = null, Color|null $color = null): void
    {
        $this->outputHelper($planning, $extra, $prefix, $suffix, $color);
    }

    public function outputState(Planning $planning, int $extra, string $prefix = null, string $suffix = null, Color|null $color = null): void
    {
        $this->outputHelper($planning, $extra, $prefix, $suffix, $color);
    }

    protected function outputHelper(
        Planning $planning,
        int $extra,
        string $prefix = null,
        string $suffix = null,
        Color|null $color = null
    ): void {
        $outputs = [];
        if (($extra & Extra::NrOfBatchGamesRange->value) === Extra::NrOfBatchGamesRange->value) {
            $outputs[] = 'batchGames ' . $planning->getNrOfBatchGames()->getMin() . '->' . $planning->getNrOfBatchGames()->getMax();
        }
        if (($extra & Extra::MaxNrOfGamesInARow->value) === Extra::MaxNrOfGamesInARow->value) {
            $outputs[] = 'gamesInARow ' . $planning->maxNrOfGamesInARow;
        }
        $timeoutState = $planning->getTimeoutState();
        if( $timeoutState !== null ) {
            $outputs[] = 'timeoutState "' . $timeoutState->value . '"';
        }
        if (($extra & Extra::Orchestration->value) === Extra::Orchestration->value) {
            $outputs[] = $this->getConfigurationAsString($planning->getConfiguration());
        }
        $output = ($prefix ?? '') . join(', ', $outputs) . ($suffix ?? '');
        if( $color !== null ){
            $output = Color::getColored($color,$output );
        }
        $this->logger->info($output);

        if (($extra & Extra::Games->value) === Extra::Games->value) {
            $batchOutput = new BatchOutput($this->logger);
            $batchOutput->output($planning->createFirstBatch());
        }
        if (($extra & Extra::Totals->value) === Extra::Totals->value) {
            $resourceCounter = new ResourceCounter($planning);
            $this->outputTotals($resourceCounter->getCounters());
        }
    }

    public function outputConfiguration(PlanningConfiguration $planningConfig, string $prefix = null, string $suffix = null): void
    {
        $output = $this->getConfigurationAsString($planningConfig, $prefix, $suffix);
        $this->logger->info($output);
    }

    public function getConfigurationAsString(PlanningConfiguration $planningConfig,
                                                  string $prefix = null, string $suffix = null): string
    {
        $configAsString = json_encode($planningConfig);
        $configAsString = $configAsString === false ? '?' : $configAsString;
        return ($prefix ?? '') . $configAsString . ($suffix ?? '');
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
