<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace SportsPlanning\Input;

use SportsPlanning\Input;
use SportsHelpers\Math;
use SportsHelpers\SportConfig as SportConfigHelper;

class Service
{
    public function __construct()
    {
    }

//    public function get(RoundNumber $roundNumber, int $nrOfReferees): PlanningInput
//    {
//        $config = $roundNumber->getValidPlanningConfig();
//        $planningConfigService = new PlanningConfigService();
//        $teamup = $config->getTeamup() ? $planningConfigService->isTeamupAvailable($roundNumber) : $config->getTeamup();
//
//        $sportConfigBases = array_map(
//            function (SportConfig $sportConfig): SportConfigBase {
//                return $sportConfig->getBase();
//            },
//            $roundNumber->getSportConfigs()
//        );
//        $selfReferee = $this->getSelfReferee(
//            $config,
//            $sportConfigBases,
//            $roundNumber->getNrOfPlaces(),
//            count($roundNumber->getPoules())
//        );
//        $nrOfReferees = $selfReferee === PlanningInput::SELFREFEREE_DISABLED ? $nrOfReferees : 0;
//        /*
//                pas hier gcd toe op poules/aantaldeelnemers(structureconfig), aantal scheidsrechters en aantal velden/sport(sportconfig)
//                zorg dat deze functie ook kan worden toegepast vanuit fctoernooi->create_default_planning_input
//                dus bijv. [8](8 poules van x deelnemers), 4 refs en [2] kan worden herleid naar een planninginput van [4], 2 refs en [1]
//
//                en bijv. [8,2](8 poules van x aantal deelnemers en 2 poules van y aantal deelnemers ), 4 refs en [2] kan worden herleid naar een planninginput van [4,1], 1 refs en [1]
//
//
//        */
//        $nrOfHeadtohead = $config->getNrOfHeadtohead();
//        $structureConfig = $this->getStructureConfig($roundNumber);
//        $sportConfig = $this->getSportConfig($roundNumber, $nrOfHeadtohead, $teamup);
//
//        // $multipleSports = count($sportConfig) > 1;
////        if ($multipleSports) {
////            $nrOfHeadtohead = $this->getSufficientNrOfHeadtoheadByRoundNumber($roundNumber, $sportConfig);
////        }
//        return new PlanningInput(
//            $structureConfig,
//            $sportConfig,
//            $nrOfReferees,
//            $teamup,
//            $selfReferee,
//            $nrOfHeadtohead
//        );
//    }
//
//    /**
//     * @param Config $config
//     * @param array|SportConfigBase[] $sportConfigs
//     * @param int $nrOfPlaces
//     * @param int $nrOfPoules
//     * @return int
//     */
//    protected function getSelfReferee(Config $config, array $sportConfigs, int $nrOfPlaces, int $nrOfPoules): int
//    {
//        $sportConfigService = new SportConfigService();
//        $maxNrOfGamePlaces = $sportConfigService->getMaxNrOfGamePlaces($sportConfigs, $config->getTeamup(), false);
//
//        $planningConfigService = new PlanningConfigService();
//
//        $otherPoulesAvailable = $planningConfigService->canSelfRefereeOtherPoulesBeAvailable($nrOfPoules);
//        $samePouleAvailable = $planningConfigService->canSelfRefereeSamePouleBeAvailable(
//            $nrOfPoules,
//            $nrOfPlaces,
//            $maxNrOfGamePlaces
//        );
//        if (!$otherPoulesAvailable && !$samePouleAvailable) {
//            return PlanningInput::SELFREFEREE_DISABLED;
//        }
//        if ($config->getSelfReferee() === PlanningInput::SELFREFEREE_OTHERPOULES && !$otherPoulesAvailable) {
//            return PlanningInput::SELFREFEREE_SAMEPOULE;
//        }
//        if ($config->getSelfReferee() === PlanningInput::SELFREFEREE_SAMEPOULE && !$samePouleAvailable) {
//            return PlanningInput::SELFREFEREE_OTHERPOULES;
//        }
//        return $config->getSelfReferee();
//    }
//
    public function hasGCD(Input $input): bool
    {
        if ($input->selfRefereeEnabled()) {
            return false;
        }
        $gcd = $this->getGCDRaw($input->getStructureConfig(), $input->getSportConfigHelpers(), $input->getNrOfReferees());
        return $gcd > 1;
    }

    public function getGCDInput(Input $input): Input
    {
        $gcd = $this->getGCDRaw($input->getStructureConfig(), $input->getSportConfigHelpers(), $input->getNrOfReferees());
        list($structureConfig, $sportConfig, $nrOfReferees) = $this->modifyByGCD(
            $gcd,
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees()
        );
        return new Input(
            $structureConfig,
            $sportConfig,
            $nrOfReferees,
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead()
        );
    }

    public function getReverseGCDInput(Input $input, int $reverseGCD): Input
    {
        list($structureConfig, $sportConfig, $nrOfReferees) = $this->modifyByGCD(
            1 / $reverseGCD,
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees()
        );
        return new Input(
            $structureConfig,
            $sportConfig,
            $nrOfReferees,
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead()
        );
    }

    /**
     * @param float $gcd
     * @param array|int[] $structureConfig
     * @param array|array[] $sportConfig
     * @param int $nrOfReferees
     * @return array
     */
    public function modifyByGCD(float $gcd, array $structureConfig, array $sportConfig, int $nrOfReferees)
    {
        $nrOfPoulesByNrOfPlaces = $this->getNrOfPoulesByNrOfPlaces($structureConfig);
        // divide with gcd
        foreach ($nrOfPoulesByNrOfPlaces as $nrOfPlaces => $nrOfPoules) {
            $nrOfPoulesByNrOfPlaces[$nrOfPlaces] = (int)($nrOfPoules / $gcd);
        }
        $retStrucureConfig = [];
        // create structure
        foreach ($nrOfPoulesByNrOfPlaces as $nrOfPlaces => $nrOfPoules) {
            for ($pouleNr = 1; $pouleNr <= $nrOfPoules; $pouleNr++) {
                $retStrucureConfig[] = $nrOfPlaces;
            }
        }

        for ($i = 0; $i < count($sportConfig); $i++) {
            $sportConfig[$i]["nrOfFields"] = (int)($sportConfig[$i]["nrOfFields"] / $gcd);
        }
        $nrOfReferees = (int)($nrOfReferees / $gcd);

        return [$retStrucureConfig, $sportConfig, $nrOfReferees];
    }
//
    public function getGCD(Input $input): int
    {
        return $this->getGCDRaw(
            $input->getStructureConfig(),
            $input->getSportConfigHelpers(),
            $input->getNrOfReferees()
        );
    }

    /**
     * @param array|int[] $structureConfig
     * @param array|SportConfigHelper[] $sportConfigs
     * @param int $nrOfReferees
     * @return int
     */
    protected function getGCDRaw(array $structureConfig, array $sportConfigs, int $nrOfReferees): int
    {
        $math = new Math();
        $gcdStructure = $math->getGreatestCommonDivisor($this->getNrOfPoulesByNrOfPlaces($structureConfig));
        $gcdSports = $sportConfigs[0]->getNrOfFields();

//        $gcds = [$gcdStructure, $gcdSports];
//        if ($nrOfReferees > 0) {
//            $gcd[] = $nrOfReferees;
//        }
        return $math->getGreatestCommonDivisor([$gcdStructure, $nrOfReferees, $gcdSports]);
    }

    /**
     * @param array|int[] $structureConfig
     * @return array
     */
    protected function getNrOfPoulesByNrOfPlaces(array $structureConfig): array
    {
        $nrOfPoulesByNrOfPlaces = [];
        foreach ($structureConfig as $pouleNrOfPlaces) {
            if (array_key_exists($pouleNrOfPlaces, $nrOfPoulesByNrOfPlaces) === false) {
                $nrOfPoulesByNrOfPlaces[$pouleNrOfPlaces] = 0;
            }
            $nrOfPoulesByNrOfPlaces[$pouleNrOfPlaces]++;
        }
        return $nrOfPoulesByNrOfPlaces;
    }
//
//    public function getStructureConfig(RoundNumber $roundNumber): array
//    {
//        $nrOfPlacesPerPoule = [];
//        foreach ($roundNumber->getPoules() as $poule) {
//            $nrOfPlacesPerPoule[] = $poule->getPlaces()->count();
//        }
//        uasort(
//            $nrOfPlacesPerPoule,
//            function (int $nrOfPlacesA, int $nrOfPlacesB) {
//                return $nrOfPlacesA > $nrOfPlacesB ? -1 : 1;
//            }
//        );
//        return array_values($nrOfPlacesPerPoule);
//    }
//
//    /**
//     * @param RoundNumber $roundNumber
//     * @param int $nrOfHeadtohead
//     * @param bool $teamup
//     * @return array
//     */
//    protected function getSportConfig(RoundNumber $roundNumber, int $nrOfHeadtohead, bool $teamup): array
//    {
//        $maxNrOfFields = $this->getMaxNrOfFields($roundNumber, $nrOfHeadtohead, $teamup);
//
//        $sportConfigRet = [];
//        /** @var SportConfig $sportConfig */
//        foreach ($roundNumber->getSportConfigs() as $sportConfig) {
//            $nrOfFields = $sportConfig->getFields()->count();
//            if ($nrOfFields > $maxNrOfFields) {
//                $nrOfFields = $maxNrOfFields;
//            }
//            $sportConfigRet[] = [
//                "nrOfFields" => $nrOfFields,
//                "nrOfGamePlaces" => $sportConfig->getNrOfGamePlaces()
//            ];
//        }
//        uasort(
//            $sportConfigRet,
//            function (array $sportA, array $sportB) {
//                return $sportA["nrOfFields"] > $sportB["nrOfFields"] ? -1 : 1;
//            }
//        );
//        return array_values($sportConfigRet);
//    }
//
//    protected function getMaxNrOfFields(RoundNumber $roundNumber, int $nrOfHeadtohead, bool $teamup): int
//    {
//        $sportService = new SportService();
//        $nrOfGames = 0;
//        /** @var \Voetbal\Poule $poule */
//        foreach ($roundNumber->getPoules() as $poule) {
//            $nrOfGames += $sportService->getNrOfGamesPerPoule($poule->getPlaces()->count(), $teamup, $nrOfHeadtohead);
//        }
//        return $nrOfGames;
//    }
//
//
//    public function areEqual(PlanningInput $inputA, PlanningInput $inputB): bool
//    {
//        return $inputA->getStructureConfig() === $inputB->getStructureConfig()
//            && $inputA->getSportConfig() === $inputB->getSportConfig()
//            && $inputA->getNrOfReferees() === $inputB->getNrOfReferees()
//            && $inputA->getTeamup() === $inputB->getTeamup()
//            && $inputA->getSelfReferee() === $inputB->getSelfReferee()
//            && $inputA->getNrOfHeadtohead() === $inputB->getNrOfHeadtohead();
//    }

    /**
     * @param RoundNumber $roundNumber
     * @param array $sportConfig
     * @return int
     */
//    public function getSufficientNrOfHeadtoheadByRoundNumber(RoundNumber $roundNumber, array $sportConfig): int
//    {
//        $config = $roundNumber->getValidPlanningConfig();
//        $poule = $this->getSmallestPoule($roundNumber);
//        $pouleNrOfPlaces = $poule->getPlaces()->count();
//        return $this->getSufficientNrOfHeadtohead(
//            $config->getNrOfHeadtohead(),
//            $pouleNrOfPlaces,
//            $config->getTeamup(),
//            $config->getSelfReferee(),
//            $sportConfig
//        );
//    }

    /**
     * @param int $defaultNrOfHeadtohead
     * @param int $pouleNrOfPlaces
     * @param bool $teamup
     * @param bool $selfReferee
     * @param array $sportConfig
     * @return int
     */
//    public function getSufficientNrOfHeadtohead(
//        int $defaultNrOfHeadtohead,
//        int $pouleNrOfPlaces,
//        bool $teamup,
//        bool $selfReferee,
//        array $sportConfig
//    ): int {
//        $sportService = new SportService();
//        $nrOfHeadtohead = $defaultNrOfHeadtohead;
//        //    $nrOfHeadtohead = $roundNumber->getValidPlanningConfig()->getNrOfHeadtohead();
//        //        sporten zijn nu planningsporten, maar voor de berekening heb ik alleen een array
//        //        zodra de berekening is gedaan hoef je daarna bij het bepalen van het aantal games
//        //        niet meer te kijken als je het aantal velden kan verkleinen!
//        $sportsNrFields = $this->convertSportConfig($sportConfig);
//        $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames(
//            $sportsNrFields,
//            $pouleNrOfPlaces,
//            $teamup,
//            $selfReferee,
//            $nrOfHeadtohead
//        );
//        $nrOfPouleGamesBySports = $sportService->getNrOfPouleGamesBySports(
//            $pouleNrOfPlaces,
//            $sportsNrFieldsGames,
//            $teamup,
//            $selfReferee
//        );
//        while (($sportService->getNrOfPouleGames(
//                $pouleNrOfPlaces,
//                $teamup,
//                $nrOfHeadtohead
//            )) < $nrOfPouleGamesBySports) {
//            $nrOfHeadtohead++;
//        }
//        if (($sportService->getNrOfPouleGames(
//                $pouleNrOfPlaces,
//                $teamup,
//                $nrOfHeadtohead
//            )) === $nrOfPouleGamesBySports) {
//            $nrOfGamePlaces = array_sum(
//                array_map(
//                    function (SportNrFields $sportNrFields) {
//                        return $sportNrFields->getNrOfFields() * $sportNrFields->getNrOfGamePlaces();
//                    },
//                    $sportsNrFields
//                )
//            );
//            if (($nrOfGamePlaces % $pouleNrOfPlaces) !== 0
//                && ($pouleNrOfPlaces % 2) !== 0  /* $pouleNrOfPlaces 1 van beide niet deelbaar door 2 */) {
//                $nrOfHeadtohead++;
//            }
//        }
//
//        if ($nrOfHeadtohead < $defaultNrOfHeadtohead) {
//            return $defaultNrOfHeadtohead;
//        }
//        return $nrOfHeadtohead;
//    }
//
//    protected function getSmallestPoule(RoundNumber $roundNumber): Poule
//    {
//        $smallestPoule = null;
//        foreach ($roundNumber->getPoules() as $poule) {
//            if ($smallestPoule === null || $poule->getPlaces()->count() < $smallestPoule->getPlaces()->count()) {
//                $smallestPoule = $poule;
//            }
//        }
//        return $smallestPoule;
//    }
//
//    /**
//     * @param array $sportsConfigs
//     * @return array|SportNrFields[]
//     */
//    protected function convertSportConfig(array $sportsConfigs): array
//    {
//        $sportNr = 1;
//        return array_map(
//            function ($sportConfig) use (&$sportNr) {
//                return new SportNrFields($sportNr++, $sportConfig["nrOfFields"], $sportConfig["nrOfGamePlaces"]);
//            },
//            $sportsConfigs
//        );
//    }
}
