<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/apiKeys.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/languages.php';

class Tools
{
    public static function includeFile(string $fileName): void
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/include/' . $fileName . '.php';

        if (file_exists($filePath)) {
            include $filePath;
        } else {
            error_log("File not found: " . $filePath, 3, $_SERVER['DOCUMENT_ROOT'] . '/errors.log');
        }
    }

    public static function addTimestampToFile($filePath)
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $filePath)) {
            $timestamp = filemtime($_SERVER['DOCUMENT_ROOT'] . $filePath);
            return $filePath . '?v=' . $timestamp;
        } else {
            return $filePath;
        }
    }

    public static function fetchData($url)
    {
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: WeatherForecast/1.0.0",
                'Accept' => 'application/json'
            ],
        ]);

        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === FALSE) {
                throw new Exception("Ошибка при выполнении запроса к URL: $url");
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON decode error: ' . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            $timestamp = date('Y-m-d H:i:s');
            $formattedMessage = "[{$timestamp}] {$e->getMessage()}\n";
            error_log($formattedMessage, 3, $_SERVER['DOCUMENT_ROOT'] . '/errors.log');
            return null;
        }
    }


    public static function getCityName($latitude, $longitude)
    {
        global $LANG;
        $language = $LANG .  '-' . mb_strtoupper($LANG);
        $url = "https://nominatim.openstreetmap.org/reverse?lat={$latitude}&lon={$longitude}&accept-language={$language}&format=json";
        $data = self::fetchData($url);

        if ($data !== null) {
            return $data;
        }

        return null;
    }

    public static function getIpLocation($ip)
    {
        global $ipKey;
        $url = "https://api.ipdata.co/{$ip}?api-key={$ipKey}&fields=latitude,longitude";
        $data = self::fetchData($url);

        if ($data !== null) {
            return $data;
        }

        return null;
    }

    public static function getWeather($latitude, $longitude)
    {
        $url = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat={$latitude}&lon={$longitude}";
        $data = self::fetchData($url);

        if ($data !== null) {
            return $data;
        }

        return null;
    }

    public static function getTimeZone($latitude, $longitude)
    {
        global $timeKey;
        $url = "https://api.timezonedb.com/v2.1/get-time-zone?key={$timeKey}&format=json&by=position&lat={$latitude}&lng={$longitude}";
        $data = self::fetchData($url);

        if ($data !== null) {
            return $data;
        }

        return null;
    }
}