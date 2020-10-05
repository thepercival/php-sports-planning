<?php

namespace SportsPlanning\Input;

use SportsPlanning\Input;
use SportsHelpers\Math;
use SportsHelpers\SportConfig as SportConfigHelper;
use SportsHelpers\PouleStructure;

class GCDService
{
    public function __construct()
    {
    }

    public function hasGCD(Input $input): bool
    {
        if ($input->selfRefereeEnabled()) {
            return false;
        }
        $gcd = $this->getGCDRaw($input->getPouleStructure(), $input->getSportConfigHelpers(), $input->getNrOfReferees());
        return $gcd > 1;
    }

    public function getGCDInput(Input $input): Input
    {
        $gcd = $this->getGCDRaw($input->getPouleStructure(), $input->getSportConfigHelpers(), $input->getNrOfReferees());

        $structureConfig = $this->createGCDPouleStructure( $gcd, $input->getPouleStructure() );
        $sportConfigHelpers = $this->createGCDSportConfigHelpers( $gcd, $input->getSportConfigHelpers() );
        $nrOfReferees = $this->getGCDNrOfReferees( $gcd, $input->getNrOfReferees() );

        return new Input(
            $structureConfig,
            $sportConfigHelpers,
            $nrOfReferees,
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead()
        );
    }

    public function getPolynomial(Input $input, int $polynomial): Input
    {
        $structureConfig = $this->createGCDPouleStructure( 1 / $polynomial, $input->getPouleStructure() );
        $sportConfigHelpers = $this->createGCDSportConfigHelpers( 1 / $polynomial, $input->getSportConfigHelpers() );
        $nrOfReferees = $this->getGCDNrOfReferees( 1 / $polynomial, $input->getNrOfReferees() );

        return new Input(
            $structureConfig,
            $sportConfigHelpers,
            $nrOfReferees,
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead()
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
     * @param array|SportConfigHelper[] $sportConfigHelpers
     * @return array|SportConfigHelper[]
     */
    public function createGCDSportConfigHelpers(float $gcd, array $sportConfigHelpers): array
    {
        $newSportConfigHelpers = [];
        foreach ( $sportConfigHelpers as $sportConfigHelper) {
            $newSportConfigHelpers[] = new SportConfigHelper(
                (int)($sportConfigHelper->getNrOfFields() / $gcd), $sportConfigHelper->getNrOfGamePlaces() );
        }
        return $newSportConfigHelpers;
    }

    public function getGCDNrOfReferees(float $gcd, int $nrOfReferees ): int
    {
        return (int)($nrOfReferees / $gcd);
    }

    public function getGCD(Input $input): int
    {
        return $this->getGCDRaw(
            $input->getPouleStructure(),
            $input->getSportConfigHelpers(),
            $input->getNrOfReferees()
        );
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfigHelper[] $sportConfigs
     * @param int $nrOfReferees
     * @return int
     */
    protected function getGCDRaw(PouleStructure $pouleStructure, array $sportConfigs, int $nrOfReferees): int
    {
        $math = new Math();
        $gcdStructure = $math->getGreatestCommonDivisor($pouleStructure->getNrOfPoulesByNrOfPlaces());
        $gcdSports = $sportConfigs[0]->getNrOfFields();

//        $gcds = [$gcdStructure, $gcdSports];
//        if ($nrOfReferees > 0) {
//            $gcd[] = $nrOfReferees;
//        }
        return $math->getGreatestCommonDivisor([$gcdStructure, $nrOfReferees, $gcdSports]);
    }
}
