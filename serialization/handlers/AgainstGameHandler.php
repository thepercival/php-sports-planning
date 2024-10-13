<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

//use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Field;
use SportsPlanning\Game\AgainstGame as AgainstGame;
use SportsPlanning\Game\AgainstGamePlace as AgainstGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Referee;

/**
 * @psalm-type _AgainstGamePlace = array{side: string, placeLocation: string}
 * @psalm-type _Referee = array{number: int, priority: int}
 * @psalm-type _AgainstGame = array{planning: Planning, poule: Poule, places: list<_AgainstGamePlace> ,field: Field, gameRoundNumber: int, refereePlaceLocation: string|null, referee: _Referee|null, batchNr: int, placeLocationMap : array<string, Place>}
 */
class AgainstGameHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(/*protected DummyCreator $dummyCreator*/)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(AgainstGame::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _AgainstGame $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return AgainstGame
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): AgainstGame {

        $againstGame = new AgainstGame(
            $fieldValue['planning'],
            $fieldValue['poule'],
            $fieldValue['field'],
            $fieldValue['gameRoundNumber']);

        $againstGame->setBatchNr($fieldValue['batchNr']);

        $placeLocationMap = $fieldValue['placeLocationMap'];
        $refereePlace = null;
        if( isset($fieldValue['refereePlaceLocation'])) {
            $refereePlace = $placeLocationMap[ $fieldValue['refereePlaceLocation'] ];
        }

        $againstGame->setRefereePlace($refereePlace);

        if( isset($fieldValue['referee'])) {
            $fieldValue["referee"]["input"] = $againstGame->getPlanning()->getInput();
            /** @var Referee|null $referee */
            $referee = $this->getProperty(
                $visitor,
                $fieldValue,
                'referee',
                Referee::class
            );
            if ($referee !== null) {
                $againstGame->setReferee($referee);
            }
        }

        foreach ($fieldValue['places'] as $arrGamePlace) {
            $side = AgainstSide::from($arrGamePlace['side']);
            $place = $placeLocationMap[ $arrGamePlace['placeLocation'] ];
            new AgainstGamePlace($againstGame, $place, $side);
        }
        return $againstGame;
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
