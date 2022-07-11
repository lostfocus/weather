<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\VisualCrossing;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Http\Client\HttpClient;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\ProviderInterface;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\WeatherException;
use Psr\Http\Message\RequestFactoryInterface;

class VisualCrossing extends AbstractProvider
{
    private string $key;

    /**
     * @noinspection PhpOptionalBeforeRequiredParametersInspection
     */
    public function __construct(HttpClient $client, ?RequestFactoryInterface $requestFactory = null, string $key)
    {
        $this->key = $key;

        parent::__construct($client, $requestFactory);
    }

    public function getCurrentWeatherData(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataInterface {
        // TODO: Implement getCurrentWeatherData() method.
    }

    public function getForecast(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        // TODO: Implement getForecast() method.
    }

    /**
     * @throws WeatherException
     */
    public function getHistorical(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {

        $query = [
            'location' => implode(',', [$latitude, $longitude]),
            'aggregateHours' => 1,
            'startDateTime' => $dateTime->format('Y-m-d\TH:i:s'),
            'timezone' => 'Z',
            'key' => $this->key,
            'contentType' => 'json',
            'lang' => $lang,
            'unitGroup' => $this->mapUnits($units),
        ];

        $queryString = sprintf(
            'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/weatherdata/history?%s',
            http_build_query($query)
        );

        $weatherRawData = $this->getArrayFromQueryString($queryString);

        $arrayReverse = array_reverse($weatherRawData['locations']);
        $location = array_pop($arrayReverse);
        $value = $location['values'][0];

        $weatherData = new VisualCrossingData();
        $weatherData->setLatitude($location['latitude'])
            ->setLongitude($location['longitude'])
            ->setTemperature($value['temp'])
            ->setTemperatureMin($value['mint'])
            ->setTemperatureMax($value['maxt'])
            ->setHumidity($value['humidity'] / 100)
            ->setPressure($value['sealevelpressure'])
            ->setWindSpeed($value['wspd'])
            ->setWindDirection($value['wdir'])
            ->setPrecipitation($value['precip'])
            ->setCloudCover($value['cloudcover'] / 100)
            ->setType(WeatherDataInterface::HISTORICAL);

        $utcDateTime = (new DateTime())
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTimestamp($value['datetime'] / 1000);

        $weatherData->setUtcDateTime($utcDateTime);

        return $weatherData;
    }

    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface {
        // TODO: Implement getForecastCollection() method.
    }

    private function mapUnits(string $units): string
    {
        if ($units === ProviderInterface::UNIT_METRIC) {
            return 'metric';
        }

        return 'us';
    }
}