<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\BrightSky;


use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\ProviderInterface;
use Lostfocus\Weather\Common\WeatherDataCollection;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\HistoricalDataNotAvailableException;
use Lostfocus\Weather\Exceptions\WeatherException;

class BrightSky extends AbstractProvider
{

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
            'date' => $dateTime->format('c'),
            'lat' => $latitude,
            'lon' => $longitude,
            'units' => $this->mapUnits($units),
        ];
        $queryString = sprintf('https://api.brightsky.dev/weather?%s', http_build_query($query));

        $weatherRawData = $this->getArrayFromQueryString($queryString);

        $weatherDataCollection = new WeatherDataCollection();

        foreach ($weatherRawData['weather'] as $weatherHourRawData) {
            $weatherDataCollection->add($this->mapRawData($latitude, $longitude, $weatherHourRawData));

        }

        $closets = $weatherDataCollection->getClosest($dateTime);
        if ($closets === null) {
            throw new HistoricalDataNotAvailableException();
        }

        return $closets;
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
        if ($units === ProviderInterface::UNIT_IMPERIAL) {
            return 'si';
        }

        return 'dwd';
    }

    private function mapRawData(
        float $latitude,
        float $longitude,
        array $weatherHourRawData,
        ?string $type = null
    ): BrightSkyData {
        $weatherData = (new BrightSkyData())
            ->setLatitude($latitude)
            ->setLongitude($longitude);

        $utcDateTime = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
        $utcDateTime->setTimestamp(strtotime($weatherHourRawData['timestamp']));

        $weatherData->setUtcDateTime($utcDateTime);
        $weatherData->setType($type);
        if ($weatherData->getType() === null) {
            $now = new DateTime();
            if ($weatherData->getUtcDateTime() > $now) {
                $weatherData->setType(WeatherDataInterface::FORECAST);
            } else {
                $weatherData->setType(WeatherDataInterface::HISTORICAL);
            }
        }

        $weatherData->setTemperature($weatherHourRawData['temperature'])
            ->setHumidity($weatherHourRawData['relative_humidity'] / 100)
            ->setPressure($weatherHourRawData['pressure_msl'])
            ->setWindSpeed($weatherHourRawData['wind_speed'])
            ->setWindDirection($weatherHourRawData['wind_direction'])
            ->setPrecipitation($weatherHourRawData['precipitation'])
            ->setCloudCover($weatherHourRawData['cloud_cover']);

        return $weatherData;
    }
}