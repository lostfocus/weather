<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\VisualCrossing;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Http\Client\HttpClient;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\ProviderInterface;
use Lostfocus\Weather\Common\WeatherDataCollection;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\HistoricalDataNotAvailableException;
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

    /**
     * @throws WeatherException
     */
    public function getCurrentWeatherData(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataInterface {
        return $this->getHistorical(
            $latitude,
            $longitude,
            new DateTime(),
            $units,
            $lang
        );
    }

    /**
     * @throws WeatherException
     */
    public function getForecast(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        $limitInterval = new DateInterval('PT1H');

        return $this->getForecastFromCollectionWithLimit(
            $latitude,
            $longitude,
            $dateTime,
            $limitInterval,
            $units,
            $lang
        );
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

        $queryUrl = sprintf(
            'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/%s/%s',
            implode(',', [$latitude, $longitude]),
            $dateTime->getTimestamp()
        );

        $query = [
            'key' => $this->key,
            'lang' => $lang,
            'unitGroup' => $this->mapUnits($units),
        ];

        $queryString = sprintf(
            '%s?%s',
            $queryUrl,
            http_build_query($query)
        );

        $weatherCollection = $this->getWeatherCollectionFromQueryString($queryString);

        $historical = $weatherCollection->getClosest($dateTime);
        if ($historical === null) {
            throw new HistoricalDataNotAvailableException();
        }

        return $historical;
    }

    /**
     * @throws WeatherException
     */
    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface {
        $queryUrl = sprintf(
            'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/%s',
            implode(',', [$latitude, $longitude])
        );

        $query = [
            'key' => $this->key,
            'lang' => $lang,
            'unitGroup' => $this->mapUnits($units),
        ];

        $queryString = sprintf(
            '%s?%s',
            $queryUrl,
            http_build_query($query)
        );

        return $this->getWeatherCollectionFromQueryString($queryString);
    }

    private function mapUnits(string $units): string
    {
        if ($units === ProviderInterface::UNIT_METRIC) {
            return 'metric';
        }

        return 'us';
    }

    private function mapWeatherData(
        float $latitude,
        float $longitude,
        array $weatherHourRawData,
        array $weatherDayRawData
    ): VisualCrossingData {
        $weatherData = new VisualCrossingData();
        $weatherData->setLatitude($latitude)
            ->setLongitude($longitude)
            ->setTemperature($weatherHourRawData['temp'])
            ->setFeelsLike($weatherHourRawData['feelslike'])
            ->setTemperatureMin($weatherDayRawData['tempmin'])
            ->setTemperatureMax($weatherDayRawData['tempmax'])
            ->setHumidity($weatherHourRawData['humidity'] / 100)
            ->setPressure($weatherHourRawData['pressure'])
            ->setWindSpeed($weatherHourRawData['windspeed'])
            ->setWindDirection($weatherHourRawData['winddir'])
            ->setPrecipitation($weatherHourRawData['precip'])
            ->setPrecipitationProbability($weatherHourRawData['precipprob'])
            ->setCloudCover($weatherHourRawData['cloudcover'] / 100);

        $now = new DateTime();

        $utcDateTime = (new DateTime())
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTimestamp($weatherHourRawData['datetimeEpoch']);

        $weatherData->setUtcDateTime($utcDateTime);

        if ($now > $utcDateTime) {
            $weatherData->setType(WeatherDataInterface::HISTORICAL);
        } else {
            $weatherData->setType(WeatherDataInterface::FORECAST);
        }

        return $weatherData;
    }

    /**
     * @param  string  $queryString
     * @return WeatherDataCollection
     * @throws WeatherException
     */
    private function getWeatherCollectionFromQueryString(string $queryString): WeatherDataCollection
    {
        $weatherRawData = $this->getArrayFromQueryString($queryString);

        $weatherCollection = new WeatherDataCollection();

        foreach ($weatherRawData['days'] as $weatherDayRawData) {
            foreach ($weatherDayRawData['hours'] as $weatherHourRawData) {
                $weatherData = $this->mapWeatherData(
                    $weatherRawData['latitude'],
                    $weatherRawData['longitude'],
                    $weatherHourRawData,
                    $weatherDayRawData
                );
                $weatherCollection->add($weatherData);
            }
        }

        return $weatherCollection;
    }
}