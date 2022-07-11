<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\DarkSky;

use Lostfocus\Weather\Common\Source;
use Lostfocus\Weather\Common\WeatherData;

class DarkSkyData extends WeatherData
{
    public function __construct()
    {
        $this->addSource(
            new Source(
                'darksky',
                'Dark Sky',
                'https://darksky.net/poweredby/'
            )
        );
    }
}