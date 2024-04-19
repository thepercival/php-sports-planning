<?php

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsHelpers\SportRange;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Output\BatchOutput as BatchOutput;
use SportsPlanning\Planning as PlanningBase;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\ResourceCounter;
use SportsPlanning\Resource\ResourceType;
use SportsPlanning\Input;
use SportsPlanning\Planning\Filter as PlanningFilter;

class InputOutput extends OutputHelper
{
    private PlanningOutput $planningOutput;
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->planningOutput = new PlanningOutput($logger);
    }

    public function output(Input $input, PlanningFilter|null $planningFilter/*, string $prefix = null, string $suffix = null, int $colorNr = -1*/): void
    {
//        new PlanningFilter(
//            null, PlanningBase\State::Succeeded, new SportRange(0,0), null
//        )

        $this->planningOutput->outputInputConfig($input->createConfiguration());
        $filteredPlannings = $input->getFilteredPlannings( $planningFilter);
        foreach( $filteredPlannings as $filteredPlanning ) {
            $prefix = '    ';
            $equalBatchGames = $filteredPlanning->getBatchGamesType() === PlanningBase\BatchGamesType::RangeIsZero ? '*' : ' ';
            $prefix .= $filteredPlanning->getState()->value . ' ' . $equalBatchGames . ' ';
            $this->planningOutput->output($filteredPlanning, false, $prefix);

            $gamesInARowPlannings = $filteredPlanning->getGamesInARowPlannings();
            foreach( $gamesInARowPlannings as $gamesInARowPlanning ) {
                $prefix = '    ';
                $prefix .= $gamesInARowPlanning->getState()->value . '   ';
                $this->planningOutput->output($gamesInARowPlanning, false, $prefix);
            }
        }
    }

//
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