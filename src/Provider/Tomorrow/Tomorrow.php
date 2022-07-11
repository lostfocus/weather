<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\Tomorrow;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Http\Client\HttpClient;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\WeatherDataCollection;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\ForecastNoMaxDateException;
use Lostfocus\Weather\Exceptions\ForecastNotPossibleException;
use Lostfocus\Weather\Exceptions\HistoricalDataNotAvailableException;
use Lostfocus\Weather\Exceptions\WeatherException;
use Psr\Http\Message\RequestFactoryInterface;

class Tomorrow extends AbstractProvider
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

        $query = [
            'location' => implode(',', [$latitude, $longitude]),
            'fields' => $this->getQueryFields(),
            'units' => $units,
            'timesteps' => ['current'],
            'apikey' => $this->key,
        ];

        $queryString = sprintf('https://api.tomorrow.io/v4/timelines?%s', $this->createQueryString($query));

        $weatherRawData = $this->getArrayFromQueryString($queryString);

        return $this->mapRawData(
            $weatherRawData['data']['timelines'][0]['intervals'][0],
            $latitude,
            $longitude,
            WeatherDataInterface::CURRENT
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
        $forecastCollection = $this->getForecastCollection($latitude, $longitude, $units, $lang);

        $maxForecastDate = $forecastCollection->getMaxDate();
        if ($maxForecastDate === null) {
            throw new ForecastNoMaxDateException();
        }

        $limit = $maxForecastDate->add(new DateInterval('PT1H'));

        if ($dateTime > $limit) {
            throw new ForecastNotPossibleException();
        }

        return $forecastCollection->getClosest($dateTime);
    }

    /**
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  DateTimeInterface  $dateTime
     * @param  string  $units
     * @param  string  $lang
     * @return WeatherDataInterface|null
     * @throws WeatherException
     */
    public function getHistorical(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        $limit = (new DateTime())->sub(new DateInterval('PT5H45M'));
        if ($dateTime < $limit) {
            throw new HistoricalDataNotAvailableException();
        }

        $now = (new DateTime())->setTimezone(new DateTimeZone('UTC'));

        $weatherCollection = $this->getWeatherCollection($latitude, $longitude, $units, $dateTime, $now);

        return $weatherCollection->getClosest($dateTime);
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
        $now = (new DateTime())->setTimezone(new DateTimeZone('UTC'));

        return $this->getWeatherCollection($latitude, $longitude, $units, $now);
    }

    private function createQueryString(array $query): string
    {
        $lines = [];
        foreach ($query as $key => $value) {
            if (!is_array($value)) {
                $lines[] = sprintf('%s=%s', $key, $value);
            } else {
                foreach ($value as $valueItem) {
                    $lines[] = sprintf('%s=%s', $key, $valueItem);
                }
            }
        }

        return implode('&', $lines);
    }

    private function mapRawData(array $intervalRawData, float $latitude, float $longitude, ?string $type): ?TomorrowData
    {
        if (!array_key_exists('startTime', $intervalRawData)) {
            return null;
        }

        $dateTime = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
        $dateTime->setTimestamp(strtotime($intervalRawData['startTime']));
        $now = (new DateTime())->setTimezone(new DateTimeZone('UTC'));

        $weatherData = new TomorrowData();
        $weatherData->setLatitude($latitude);
        $weatherData->setLongitude($longitude);
        $weatherData->setUtcDateTime($dateTime);

        if ($type !== null) {
            $weatherData->setType($type);
        } elseif ($dateTime < $now) {
            $weatherData->setType(WeatherDataInterface::HISTORICAL);
        } else {
            $weatherData->setType(WeatherDataInterface::FORECAST);
        }

        if (array_key_exists('values', $intervalRawData)) {
            if (array_key_exists('temperature', $intervalRawData['values'])) {
                $weatherData->setTemperature($intervalRawData['values']['temperature']);
            }
            if (array_key_exists('temperatureApparent', $intervalRawData['values'])) {
                $weatherData->setFeelsLike($intervalRawData['values']['temperatureApparent']);
            }
            if (array_key_exists('humidity', $intervalRawData['values'])) {
                $weatherData->setHumidity($intervalRawData['values']['humidity'] / 100);
            }
            if (array_key_exists('pressureSeaLevel', $intervalRawData['values'])) {
                $weatherData->setPressure($intervalRawData['values']['pressureSeaLevel']);
            }
            if (array_key_exists('windSpeed', $intervalRawData['values'])) {
                $weatherData->setWindSpeed($intervalRawData['values']['windSpeed']);
            }
            if (array_key_exists('windDirection', $intervalRawData['values'])) {
                $weatherData->setWindDirection($intervalRawData['values']['windDirection']);
            }
            if (array_key_exists('precipitationIntensity', $intervalRawData['values'])) {
                $weatherData->setPrecipitation($intervalRawData['values']['precipitationIntensity']);
            }
            if (array_key_exists('precipitationProbability', $intervalRawData['values'])) {
                $weatherData->setPrecipitationProbability($intervalRawData['values']['precipitationProbability']);
            }
            if (array_key_exists('cloudCover', $intervalRawData['values'])) {
                $weatherData->setCloudCover($intervalRawData['values']['cloudCover']);
            }
        }

        return $weatherData;
    }

    /**
     * @throws WeatherException
     */
    private function getWeatherCollection(
        float $latitude,
        float $longitude,
        string $units,
        DateTimeInterface $dateTime,
        ?DateTime $endTime = null
    ): WeatherDataCollection {
        $query = [
            'location' => implode(',', [$latitude, $longitude]),
            'fields' => $this->getQueryFields(),
            'units' => $units,
            'timesteps' => ['current', '1h'],
            'startTime' => $dateTime->format('Y-m-d\TH:i:s\Z'),
            'apikey' => $this->key,
        ];

        if ($endTime !== null) {
            $query['endTime'] = $endTime->format('Y-m-d\TH:i:s\Z');
        }


        $queryString = sprintf('https://api.tomorrow.io/v4/timelines?%s', $this->createQueryString($query));

        $weatherRawData = $this->getArrayFromQueryString($queryString);
        $weatherCollection = new WeatherDataCollection();

        if (
            array_key_exists('data', $weatherRawData) &&
            is_array($weatherRawData['data']) &&
            array_key_exists('timelines', $weatherRawData['data']) &&
            is_array($weatherRawData['data']['timelines'])
        ) {
            foreach ($weatherRawData['data']['timelines'] as $timelineRawData) {
                if (!is_array($timelineRawData)) {
                    continue;
                }
                $type = null;
                if (
                    array_key_exists('timestep', $timelineRawData) &&
                    $timelineRawData['timestep'] === 'current'
                ) {
                    $type = WeatherDataInterface::CURRENT;
                }
                if (
                    array_key_exists('intervals', $timelineRawData) &&
                    is_array($timelineRawData['intervals'])
                ) {
                    foreach ($timelineRawData['intervals'] as $intervalRawData) {
                        $weatherData = $this->mapRawData($intervalRawData, $latitude, $longitude, $type);
                        if ($weatherData !== null) {
                            $weatherCollection->add($weatherData);
                        }
                    }
                }
            }
        }

        return $weatherCollection;
    }

    /**
     * @return string[]
     */
    private function getQueryFields(): array
    {
        return [
            'temperature',
            'windSpeed',
            'windDirection',
            'precipitationIntensity',
            'precipitationProbability',
            'pressureSeaLevel',
            'humidity',
            'precipitationType',
            'windGust',
            'temperatureApparent',
            'cloudCover',
            'cloudBase',
            'cloudCeiling',
            'weatherCode',
        ];
    }
}