<?php

declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;

class Service
{
    public function __construct()
    {
    }


    /*public function canTeamupBeAvailable(PouleStructure $pouleStructure, array $sportConfigs): bool
    {
        if (count($sportConfigs) > 1) {
            return false;
        }
        return $pouleStructure->getBiggestPoule() <= Input::TEAMUP_MAX
            && $pouleStructure->getSmallestPoule() >= Input::TEAMUP_MIN;
    }*/

    /**
     * @param PouleStructure $pouleStructure
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|Single|AllInOneGame> $sportVariants
     * @return bool
     */
    public function canSelfRefereeBeAvailable(PouleStructure $pouleStructure, array $sportVariants): bool
    {
        return $this->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sportVariants)
            || $this->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
    }

    public function canSelfRefereeOtherPoulesBeAvailable(PouleStructure $pouleStructure): bool
    {
        return $pouleStructure->getNrOfPoules() > 1;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|Single|AllInOneGame> $sportVariants
     * @return bool
     */
    public function canSelfRefereeSamePouleBeAvailable(PouleStructure $pouleStructure, array $sportVariants): bool
    {
        $smallestNrOfPlaces = $pouleStructure->getSmallestPoule();
        foreach ($sportVariants as $sportVariant) {
            if ($sportVariant instanceof AllInOneGame) {
                return false;
            }
            $nrOfGamePlaces = ($sportVariant instanceof Single) ? $sportVariant->nrOfGamePlaces : $sportVariant->getNrOfGamePlaces();
            if ($nrOfGamePlaces >= $smallestNrOfPlaces) {
                return false;
            }
        }
        return true;
    }

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
//        /** @var \SportsPlanning\Poule $poule */
//        foreach ($roundNumber->getPoules() as $poule) {
//            $nrOfGames += $sportService->getNrOfGamesPerPoule($poule->getPlaces()->count(), $teamup, $nrOfHeadtohead);
//        }
//        return $nrOfGames;
//    }
//



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
}
