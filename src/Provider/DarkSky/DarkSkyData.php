<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\DarkSky;

use Lostfocus\Weather\Common\WeatherData;

class DarkSkyData extends WeatherData
{
    protected ?string $source = 'darksky';
}