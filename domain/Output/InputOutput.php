<?php

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsPlanning\Planning as PlanningBase;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Output\PlanningOutput\Extra as PlanningOutputExtra;
use SportsPlanning\Input;
use SportsPlanning\Planning\Filter as PlanningFilter;

final class InputOutput extends OutputHelper
{
    private PlanningOutput $planningOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->planningOutput = new PlanningOutput($logger);
    }

    public function output(Input $input, bool $withHistory): void
    {
        $planningFilter = new PlanningFilter(
            PlanningBase\Type::BatchGames, null, null, null
        );

        $this->planningOutput->outputInputConfig($input->createConfiguration());
        $filteredPlannings = $input->getFilteredPlannings($planningFilter);
        foreach ($filteredPlannings as $filteredPlanning) {
            $equalBatchGames = $filteredPlanning->getBatchGamesType() === PlanningBase\BatchGamesType::RangeIsZero ? '*' : ' ';
            $prefix = $equalBatchGames . ' ';

            $color = $this->getColor($filteredPlanning->getState());
            $extra = PlanningOutputExtra::NrOfBatchGamesRange->value;
            $suffix = null;
            if( $filteredPlanning->getState() === PlanningBase\State::Succeeded ) {
                $suffix = ', nrOfBatches: ' . $filteredPlanning->getNrOfBatches();
            }
            $this->planningOutput->outputState($filteredPlanning, $extra, $prefix, $suffix, $color);

            $gamesInARowPlannings = $filteredPlanning->getGamesInARowPlannings();
            foreach ($gamesInARowPlannings as $gamesInARowPlanning) {
                $prefix = '    ' . '  ';
                $color = $this->getColor($gamesInARowPlanning->getState());
                $extra = PlanningOutputExtra::MaxNrOfGamesInARow->value;
                $suffix = null;
                if( $gamesInARowPlanning->getState() === PlanningBase\State::Succeeded ) {
                    $suffix = ', nrOfBatches: ' . $gamesInARowPlanning->getNrOfBatches();
                }
                $this->planningOutput->outputState($gamesInARowPlanning, $extra, $prefix, $suffix, $color);
            }
        }
        if( $withHistory === false ) {
            return;
        }
        $historicalBestPlannings = $input->getHistoricalBestPlannings();
        foreach ($historicalBestPlannings as $historicalBestPlanning) {

            $output = 'removal ' . $historicalBestPlanning->getRemovalDateTime()->format('Y-m-d');
            $output .= ', batchGames: ' . ((string)$historicalBestPlanning->getNrOfBatchGames());
            $output .= ', maxNrOfGamesInARow: ' . $historicalBestPlanning->getMaxNrOfGamesInARow();
            $output .= ', nrOfBatches: ' . $historicalBestPlanning->getNrOfBatches();
            $output = Color::getColored(Color::Blue, $output);
            $this->logger->info($output);

//            $this->planningOutput->outputState($filteredPlanning, $extra, $prefix, $suffix, $color);
//
//            $gamesInARowPlannings = $filteredPlanning->getGamesInARowPlannings();
//            foreach ($gamesInARowPlannings as $gamesInARowPlanning) {
//                $prefix = '    ' . '  ';
//                $color = $this->getColor($gamesInARowPlanning->getState());
//                $extra = PlanningOutputExtra::MaxNrOfGamesInARow->value;
//                $suffix = null;
//                if( $gamesInARowPlanning->getState() === PlanningBase\State::Succeeded ) {
//                    $suffix = ', nrOfBatches: ' . $gamesInARowPlanning->getNrOfBatches();
//                }
//                $this->planningOutput->outputState($gamesInARowPlanning, $extra, $prefix, $suffix, $color);
//            }
        }
    }

    public function getColor(PlanningState $state): Color|null {
        $color = null;
        if( $state === PlanningState::Succeeded ) {
            $color = Color::Green;
        } elseif ($state === PlanningState::Failed) {
            $color = Color::Red;
        } else if ($state === PlanningState::TimedOut) {
            $color = Color::Yellow;
        }
        return $color;
    }
//
//    public function outputInputConfig(InputConfiguration $inputConfiguration, string $prefix = null, string $suffix = null): void
//    {
//        $output = $this->getInputConfigurationAsString($inputConfiguration, $prefix, $suffix);
//        $this->logger->info($output);
//    }
//
//    public function getInputConfigurationAsString(InputConfiguration $inputConfiguration,
//                                                  string $prefix = null, string $suffix = null): string
//    {
//        return ($prefix ?? '') . $inputConfiguration->getName() . ($suffix ?? '');
//    }
//
//    /**
//     * @param array<int,array<string,GameCounter>> $planningTotals
//     */
//    protected function outputTotals(array $planningTotals): void
//    {
//        foreach ($planningTotals as $totalsType => $gameCounters) {
//            $name = '';
//            if ($totalsType === ResourceType::Fields->value) {
//                $name = 'fields';
//            } else {
//                if ($totalsType === ResourceType::Referees->value) {
//                    $name = 'referees';
//                } else {
//                    if ($totalsType === ResourceType::RefereePlaces->value) {
//                        $name = 'refereeplaces';
//                    }
//                }
//            }
//            $this->logger->info($this->getPlanningTotalAsString($name, $gameCounters));
//        }
//    }
//
//    /**
//     * @param string $name
//     * @param array<string,GameCounter> $gameCounters
//     * @return string
//     */
//    protected function getPlanningTotalAsString(string $name, array $gameCounters): string
//    {
//        $retVal = "";
//        foreach ($gameCounters as $gameCounter) {
//            $retVal .= $gameCounter->getIndex() . ":" . $gameCounter->getNrOfGames() . ", ";
//        }
//        return $name . " => " . $retVal;
//    }
}