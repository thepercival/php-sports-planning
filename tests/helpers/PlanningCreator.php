<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:32
 */

namespace SportsPlanning\TestHelper;

use SportsHelpers\SportConfig as SportConfigHelper;
use SportsPlanning\Planning;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Service as PlanningService;
use SportsPlanning\Input;
use SportsPlanning\Input\Service as PlanningInputService;

trait PlanningCreator {

    /**
     * @return array|SportConfigHelper[]
     */
    protected function getDefaultSportConfig(): array {
        return $this->createSportConfig( 2 );
    }

    /**
     * @return array|SportConfigHelper[]
     */
    protected function createSportConfig(int $nrOfFields ): array {
        return [ new SportConfigHelper( $nrOfFields, 2 )];
    }

    protected function getDefaultNrOfReferees(): int {
        return 2;
    }

    /**
     * @param array|int[] $structureConfig
     * @param array|SportConfigHelper[]|null $sportConfig
     * @param int|null $nrOfReferees
     * @param bool|null $teamup
     * @param int|null $selfReferee
     * @param int|null $nrOfHeadtohead
     * @return Input
     */
    protected function createInput(
        array $structureConfig,
        array $sportConfig = null,
        int $nrOfReferees = null,
        bool $teamup = null,
        int $selfReferee = null,
        int $nrOfHeadtohead = null
    ) {
        if( $sportConfig === null ) {
            $sportConfig = $this->getDefaultSportConfig();
        }
        if( $nrOfReferees === null ) {
            $nrOfReferees = $this->getDefaultNrOfReferees();
        }
        if( $teamup === null ) {
            $teamup = false;
        }
        if( $selfReferee === null ) {
            $selfReferee = Input::SELFREFEREE_DISABLED;
        }
        if( $nrOfHeadtohead === null ) {
            $nrOfHeadtohead = 1;
        }
        return new Input(
            $structureConfig,
            $sportConfig,
            $nrOfReferees,
            $teamup,
            $selfReferee,
            $nrOfHeadtohead
        );
    }

    protected function createPlanning( Input $input ): Planning
    {
        $planningService = new PlanningService();
        $planning = $planningService->createNextMinIsMaxPlanning($input);
        if (Planning::STATE_SUCCESS !== $planningService->createGames($planning)) {
            throw new \Exception("planning could not be created", E_ERROR);
        }
        if ($input->selfRefereeEnabled()) {
            $refereePlaceService = new RefereePlaceService($planning);
            $refereePlaceService->assign($planning->createFirstBatch());
        }
        return $planning;
    }
}

