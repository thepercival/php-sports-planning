<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use SportsPlanning\Input;
use SportsPlanning\Input\Configuration;


use SportsPlanning\Poule;
use SportsPlanning\Referee;
use SportsPlanning\Sport;

/**
 * @psalm-type _FieldValue = array{poules: list<array>, sports: list<array>, referees: list<array>}
 */
class InputHandler extends Handler implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Input::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return Input
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Input {
        /** @var Configuration $configuration */
        $configuration = $this->getProperty(
            $visitor,
            $fieldValue,
            'configuration',
            Configuration::class
        );
        $input = new Input($configuration);

        foreach ($fieldValue['poules'] as $arrPoule) {
            $fieldValue['poule'] = $arrPoule;
            $fieldValue['poule']['input'] = $input;
            $this->getProperty(
                $visitor,
                $fieldValue,
                'poule',
                Poule::class
            );
        }

        foreach ($fieldValue['sports'] as $arrSport) {
            $fieldValue['sport'] = $arrSport;
            $fieldValue['sport']['input'] = $input;
            $this->getProperty(
                $visitor,
                $fieldValue,
                'sport',
                Sport::class
            );
        }

        foreach ($fieldValue['referees'] as $arrReferee) {
            $fieldValue['referee'] = $arrReferee;
            $fieldValue['referee']['input'] = $input;
            $this->getProperty(
                $visitor,
                $fieldValue,
                'referee',
                Referee::class
            );
        }

        return $input;
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
