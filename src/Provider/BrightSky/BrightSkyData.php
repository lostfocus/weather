<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\BrightSky;

use Lostfocus\Weather\Common\Source;
use Lostfocus\Weather\Common\WeatherData;

class BrightSkyData extends WeatherData
{
    public function __construct()
    {
        $this->addSource(
            new Source(
                'brightsky',
                'Bright Sky',
                'https://brightsky.dev/'
            )
        );
        $this->addSource(
            new Source(
                'dwd',
                'Deutscher Wetterdienst',
                'https://www.dwd.de/'
            )
        );
    }
}