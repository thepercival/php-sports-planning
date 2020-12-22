<?php

namespace SportsPlanning\Input;

use SportsPlanning\Input;
use SportsHelpers\SportMath;
use SportsHelpers\SportConfig;
use SportsHelpers\PouleStructure;

class GCDService
{
    public function __construct()
    {
    }

    public function isPolynomial(Input $input): bool
    {
        if ($input->selfRefereeEnabled()) {
            return false;
        }
        $gcd = $this->getGCDRaw($input->getPouleStructure(), $input->getSportConfigs(), $input->getNrOfReferees());
        return $gcd > 1;
    }

    public function getGCDInput(Input $input): Input
    {
        $gcd = $this->getGCDRaw($input->getPouleStructure(), $input->getSportConfigs(), $input->getNrOfReferees());

        $structureConfig = $this->createGCDPouleStructure( $gcd, $input->getPouleStructure() );
        $sportConfigs = $this->createGCDSportConfigs( $gcd, $input->getSportConfigs() );
        $nrOfReferees = $this->getGCDNrOfReferees( $gcd, $input->getNrOfReferees() );

        return new Input(
            $structureConfig,
            $sportConfigs,
            $input->getGameMode(),
            $nrOfReferees,
            $input->getSelfReferee()
        );
    }

    public function getPolynomial(Input $input, int $polynomial): Input
    {
        $structureConfig = $this->createGCDPouleStructure( 1 / $polynomial, $input->getPouleStructure() );
        $sportConfigs = $this->createGCDSportConfigs( 1 / $polynomial, $input->getSportConfigs() );
        $nrOfReferees = $this->getGCDNrOfReferees( 1 / $polynomial, $input->getNrOfReferees() );

        return new Input(
            $structureConfig,
            $sportConfigs,
            $input->getGameMode(),
            $nrOfReferees,
            $input->getSelfReferee()
        );
    }

    public function createGCDPouleStructure(float $gcd, PouleStructure $pouleStructure ): PouleStructure
    {
        $nrOfPoulesByNrOfPlaces = $pouleStructure->getNrOfPoulesByNrOfPlaces();
        // divide with gcd
        foreach ($nrOfPoulesByNrOfPlaces as $nrOfPlaces => $nrOfPoules) {
            $nrOfPoulesByNrOfPlaces[$nrOfPlaces] = (int)($nrOfPoules / $gcd);
        }
        $newStrucureConfig = [];
        // create structure
        foreach ($nrOfPoulesByNrOfPlaces as $nrOfPlaces => $nrOfPoules) {
            for ($pouleNr = 1; $pouleNr <= $nrOfPoules; $pouleNr++) {
                $newStrucureConfig[] = $nrOfPlaces;
            }
        }
        return new PouleStructure($newStrucureConfig);
    }

    /**
     * @param float $gcd
     * @param array|SportConfig[] $sportConfigs
     * @return array|SportConfig[]
     */
    public function createGCDSportConfigs(float $gcd, array $sportConfigs): array
    {
        $newSportConfigs = [];
        foreach ( $sportConfigs as $sportConfig) {
            $newSportConfigs[] = new SportConfig(
                $sportConfig->getSport(),
                (int)($sportConfig->getNrOfFields() / $gcd),
                $sportConfig->getGameAmount()
            );
        }
        return $newSportConfigs;
    }

    public function getGCDNrOfReferees(float $gcd, int $nrOfReferees ): int
    {
        return (int)($nrOfReferees / $gcd);
    }

    public function getGCD(Input $input): int
    {
        return $this->getGCDRaw(
            $input->getPouleStructure(),
            $input->getSportConfigs(),
            $input->getNrOfReferees()
        );
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfig[] $sportConfigs
     * @param int $nrOfReferees
     * @return int
     */
    protected function getGCDRaw(PouleStructure $pouleStructure, array $sportConfigs, int $nrOfReferees): int
    {
        $math = new SportMath();
        $gcdStructure = $math->getGreatestCommonDivisor($pouleStructure->getNrOfPoulesByNrOfPlaces());
        $gcdSports = $sportConfigs[0]->getNrOfFields();

//        $gcds = [$gcdStructure, $gcdSports];
//        if ($nrOfReferees > 0) {
//            $gcd[] = $nrOfReferees;
//        }
        return $math->getGreatestCommonDivisor([$gcdStructure, $nrOfReferees, $gcdSports]);
    }
}
