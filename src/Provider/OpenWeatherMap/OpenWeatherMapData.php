<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\OpenWeatherMap;

use Lostfocus\Weather\Common\Source;
use Lostfocus\Weather\Common\WeatherData;

final class OpenWeatherMapData extends WeatherData
{
    public function __construct()
    {
        $this->source = new Source(
            'openweathermap',
            'OpenWeather',
            'https://openweathermap.org'
        );
    }
}