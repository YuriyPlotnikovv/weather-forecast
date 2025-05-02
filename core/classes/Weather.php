<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/languages.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/classes/Tools.php';

class Weather
{
    private string $latitude;
    private string $longitude;
    private array $data = [];
    private const CACHE_FILE = '/data/weather.json';
    private const CACHE_DURATION = 3600;
    private const LOG_FILE = '/errors.log';

    public function __construct($latitude, $longitude)
    {
        $this->latitude = $this->sanitizeCoordinate($latitude);
        $this->longitude = $this->sanitizeCoordinate($longitude);
    }

    private function sanitizeCoordinate($coordinate): string
    {
        if (!is_numeric($coordinate) || $coordinate < -180 || $coordinate > 180) {
            throw new InvalidArgumentException('Некорректное значение координаты.');
        }
        return (string) $coordinate;
    }

    public function getCityName()
    {
        $data = Tools::getCityName($this->latitude, $this->longitude);

        if (!empty($data['address'])) {
            $this->data['address'] = [
                'road' => $data['address']['road'] ?? '',
                'city' => $data['address']['city'] ?? $data['address']['town'] ?? '',
                'country' => $data['address']['country'] ?? ''
            ];
        } else {
            $this->logError('Не удалось найти адрес.');
            return null;
        }
    }

    public function getWeather()
    {
        $zone = Tools::getTimeZone($this->latitude, $this->longitude);

        if ($zone === null || empty($zone['zoneName'])) {
            return null;
        }

        $data = Tools::getWeather($this->latitude, $this->longitude);

        if ($data === null) {
            return null;
        }

        $this->data['time_zone'] = $zone['zoneName'];
        $this->processWeatherData($data, $zone['zoneName']);
    }

    private function processWeatherData($data, $zoneName): void
    {
        $timeSeries = $data['properties']['timeseries'] ?? [];
        if (empty($timeSeries)) {
            $this->logError('Не удалось получить данные о погоде.');
            return;
        }

        $currentWeather = $timeSeries[0];
        $this->data['weather']['current'] = $this->extractWeatherDetails($currentWeather, $zoneName);

        for ($i = 1, $k = 0; $i < 25; $i++, $k++) {
            if (isset($timeSeries[$i])) {
                $this->data['weather']['day'][$k] = $this->extractWeatherDetails($timeSeries[$i], $zoneName);
            }
        }

        $this->extractForecastData($timeSeries, $zoneName);
    }

    private function extractWeatherDetails($timeSeries, $zoneName): array
    {
        $time = new DateTime($timeSeries['time']);
        $time->setTimezone(new DateTimeZone($zoneName));

        return [
            'time' => $time->format('H:i'),
            'icon' => $timeSeries['data']['next_1_hours']['summary']['symbol_code'] ?? '',
            'air_pressure' => round(($timeSeries['data']['instant']['details']['air_pressure_at_sea_level'] ?? 0) / 1.333),
            'air_temperature' => $timeSeries['data']['instant']['details']['air_temperature'] ?? '',
            'humidity' => $timeSeries['data']['instant']['details']['relative_humidity'] ?? '',
            'wind_speed' => $timeSeries['data']['instant']['details']['wind_speed'] ?? '',
            'wind_direction' => $timeSeries['data']['instant']['details']['wind_from_direction'] ?? ''
        ];
    }

    private function extractForecastData($timeSeries, $zoneName): void
    {
        $tomorrow = new DateTime('tomorrow', new DateTimeZone($zoneName));
        $endOfNextDays = clone $tomorrow;
        $endOfNextDays->modify('+7 days');

        $forecastData = array_filter($timeSeries, function ($item) use ($zoneName, $tomorrow, $endOfNextDays) {
            $itemDate = new DateTime($item['time']);
            $hour = $itemDate->format('H');
            return in_array($hour, ['06', '12', '18']) && $itemDate >= $tomorrow && $itemDate < $endOfNextDays;
        });

        $groupedData = [];
        foreach ($forecastData as $item) {
            $itemDate = (new DateTime($item['time']))->setTimezone(new DateTimeZone($zoneName))->format('Y-m-d');
            $hour = (new DateTime($item['time']))->format('H');
            $groupedData[$itemDate][$hour] = $item;
        }

        $j = 0;
        foreach ($groupedData as $date => $times) {
            $this->data['weather']['next'][$j] = [
                'date' => (new DateTime($date))->format('d.m.y'),
                'icon' => $times['06']['data']['next_12_hours']['summary']['symbol_code'] ?? null,
                'air_pressure' => round(($times['12']['data']['instant']['details']['air_pressure_at_sea_level'] ?? 0) / 1.333),
                'air_temperature' => [
                    '06' => $times['06']['data']['instant']['details']['air_temperature'] ?? null,
                    '12' => $times['12']['data']['instant']['details']['air_temperature'] ?? null,
                    '18' => $times['18']['data']['instant']['details']['air_temperature'] ?? null,
                ],
                'humidity' => $times['12']['data']['instant']['details']['relative_humidity'] ?? '',
                'wind_speed' => $times['12']['data']['instant']['details']['wind_speed'] ?? '',
                'wind_direction' => $times['12']['data']['instant']['details']['wind_from_direction'] ?? ''
            ];
            $j++;
        }
    }

    public function getData()
    {
        global $LANG;
        $cacheKey = $this->latitude . ',' . $this->longitude;

        if ($this->isCacheValid($cacheKey)) {
            $cachedData = $this->getCache();
            if ($cachedData !== null) {
                return $cachedData[$LANG][$cacheKey]['data'];
            }
        }

        $this->getCityName();
        $this->getWeather();

        if (!empty($this->data)) {
            $this->setCache($cacheKey, $this->data);
            return $this->data;
        } else {
            $this->logError('Не удалось получить данные.');
            return null;
        }
    }

    private function isCacheValid($cacheKey): bool
    {
        global $LANG;

        $cacheFilePath = $_SERVER['DOCUMENT_ROOT'] . self::CACHE_FILE;

        if (!file_exists($cacheFilePath)) {
            return false;
        }

        $cachedData = file_get_contents($cacheFilePath);
        if ($cachedData === false) {
            return false;
        }

        $data = json_decode($cachedData, true);

        if (!isset($data[$LANG]) || !isset($data[$LANG][$cacheKey])) {
            return false;
        }

        $cacheTimestamp = $data[$LANG][$cacheKey]['timestamp'];
        $currentTime = time();
        $nextHourTimestamp = strtotime(date('Y-m-d H:00:00', $currentTime)) + self::CACHE_DURATION;

        return ($currentTime - $cacheTimestamp < ($nextHourTimestamp - $currentTime));
    }

    private function getCache()
    {
        $cacheFilePath = $_SERVER['DOCUMENT_ROOT'] . self::CACHE_FILE;

        if (!file_exists($cacheFilePath)) {
            if (file_put_contents($cacheFilePath, json_encode([])) === false) {
                $this->logError('Ошибка создания файла кеша: ' . $cacheFilePath);
                return [];
            }
            return [];
        }

        $cachedData = file_get_contents($cacheFilePath);

        if ($cachedData === false) {
            $this->logError('Ошибка чтения файла кеша: ' . $cacheFilePath);
            return null;
        }

        $data = json_decode($cachedData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError('JSON decode error: ' . json_last_error_msg());
            return null;
        }
        return $data;
    }

    private function setCache($cacheKey, $data): void
    {
        global $LANG;

        $cacheFilePath = $_SERVER['DOCUMENT_ROOT'] . self::CACHE_FILE;
        $cachedData = $this->getCache() ?? [];

        $cachedData[$LANG][$cacheKey] = [
            'timestamp' => time(),
            'data' => $data
        ];

        if (file_put_contents($cacheFilePath, json_encode($cachedData)) === false) {
            $this->logError('Ошибка записи в файл кеша: ' . $cacheFilePath);
        }
    }

    private function logError($message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] {$message}\n";
        error_log($formattedMessage, 3, $_SERVER['DOCUMENT_ROOT'] . self::LOG_FILE);
    }
}
