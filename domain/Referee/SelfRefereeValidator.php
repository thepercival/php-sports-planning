<?php

declare(strict_types=1);

namespace SportsPlanning\Referee;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

final class SelfRefereeValidator
{
    public function __construct()
    {
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
     * @return bool
     */
    public function canSelfRefereeBeAvailable(PouleStructure $pouleStructure, array $sports): bool
    {
        return $this->canSelfRefereeSamePouleBeAvailable($pouleStructure, $sports)
            || $this->canSelfRefereeOtherPoulesBeAvailable($pouleStructure);
    }

    public function canSelfRefereeOtherPoulesBeAvailable(PouleStructure $pouleStructure): bool
    {
        return $pouleStructure->getNrOfPoules() > 1;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
     * @return bool
     */
    public function canSelfRefereeSamePouleBeAvailable(PouleStructure $pouleStructure, array $sports): bool
    {
        $smallestNrOfPlaces = $pouleStructure->getSmallestPoule();
        foreach ($sports as $sport) {
            if ($sport instanceof TogetherSport && $sport->getNrOfGamePlaces() === null) {
                return false;
            }
            if ($sport->getNrOfGamePlaces() >= $smallestNrOfPlaces) {
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
