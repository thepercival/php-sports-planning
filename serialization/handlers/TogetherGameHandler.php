<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

//use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use SportsHelpers\SportRange;
use SportsPlanning\Field;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;

use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Referee;

/**
 * @psalm-type _TogetherGamePlace = array{side: string, placeLocation: string, gameRoundNumber: int}
 * @psalm-type _TogetherGame = array{planning: Planning, poule: Poule, places: list<_TogetherGamePlace> ,field: Field, refereePlaceLocation: string|null, refereePlace: Place|null, batchNr: int, placeLocationMap : array<string, Place>}
 */
class TogetherGameHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(/*protected DummyCreator $dummyCreator*/)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(TogetherGame::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _TogetherGame $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return TogetherGame
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): TogetherGame {

        $togetherGame = new TogetherGame(
            $fieldValue['planning'],
            $fieldValue['poule'],
            $fieldValue['field']);

        $togetherGame->setBatchNr($fieldValue['batchNr']);

        $placeLocationMap = $fieldValue['placeLocationMap'];
        $refereePlace = null;
        if( isset($fieldValue['refereePlaceLocation'])) {
            $refereePlace = $placeLocationMap[ $fieldValue['refereePlaceLocation'] ];
        }

        $togetherGame->setRefereePlace($refereePlace);

        /** @var Referee|null $referee */
        $referee = $this->getProperty(
            $visitor,
            $fieldValue,
            'referee',
            Referee::class
        );
        if( $referee !== null) {
            $togetherGame->setReferee($referee);
        }
        foreach ($fieldValue['places'] as $arrGamePlace) {
            $place = $placeLocationMap[ $arrGamePlace['placeLocation'] ];
            new TogetherGamePlace($togetherGame, $place, $arrGamePlace['gameRoundNumber']);
        }
        return $togetherGame;
    }



//    /**
//     * @param array<string, int|bool|array<string, int|bool>> $arrConfig
//     * @param CompetitionSport $competitionSport
//     * @param Round $round
//     * @param ScoreConfig|null $previous
//     * @return ScoreConfig
//     */
//    protected function createAgainstGame(
//        array $arrConfig,
//        CompetitionSport $competitionSport,
//        Round $round,
//        ScoreConfig $previous = null
//    ): ScoreConfig {
//        $config = new ScoreConfig(
//            $competitionSport,
//            $round,
//            $arrConfig['direction'],
//            $arrConfig['maximum'],
//            $arrConfig['enabled'],
//            $previous
//        );
//        if (isset($arrConfig['next'])) {
//            $this->createScoreConfig($arrConfig['next'], $competitionSport, $round, $config);
//        }
//        return $config;
//    }
//
//    /**
//     * @param array<string, int|bool|array<string, int|bool|PointsCalculation>> $arrConfig
//     * @param CompetitionSport $competitionSport
//     * @param Round $round
//     * @return AgainstQualifyConfig
//     */
//    protected function createAgainstQualifyConfig(
//        array $arrConfig,
//        CompetitionSport $competitionSport,
//        Round $round
//    ): AgainstQualifyConfig {
//        $config = new AgainstQualifyConfig($competitionSport, $round, $arrConfig['pointsCalculation']);
//        $config->setWinPoints($arrConfig['winPoints']);
//        $config->setWinPointsExt($arrConfig['winPointsExt']);
//        $config->setDrawPoints($arrConfig['drawPoints']);
//        $config->setDrawPointsExt($arrConfig['drawPointsExt']);
//        $config->setLosePointsExt($arrConfig['losePointsExt']);
//        return $config;
//    }
}
