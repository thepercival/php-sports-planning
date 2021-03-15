<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\SportConfig;
use SportsPlanning\Sport;

class SportAndConfig
{
    public function __construct(private Sport $sport, private SportConfig $config)
    {
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }

    public function getConfig(): SportConfig
    {
        return $this->config;
    }
}
