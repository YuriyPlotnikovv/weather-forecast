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
            error_log('File not found: ' . $filePath, 3, $_SERVER['DOCUMENT_ROOT'] . '/errors.log');
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
                'header' => 'User-Agent: WeatherForecast/1.0.0',
                'Accept' => 'application/json',
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
        $language = $LANG . '-' . mb_strtoupper($LANG);
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


    public static function getCurrentUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];

        return $protocol . $host . $requestUri;
    }

    public static function getHrefLang(): string
    {
        $url = self::getCurrentUrl();
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $server = $protocol . $host;

        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $segments = explode('/', trim($path, '/'));

        if (isset($segments[0]) && $segments[0] === 'en') {
            array_shift($segments);
        }

        $newPath = '/' . implode('/', $segments);

        if (!str_ends_with($newPath, '/')) {
            $newPath .= '/';
        }

        $hrefLangTags = [
            '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($server) . '/" />',
            '<link rel="alternate" hreflang="ru" href="' . htmlspecialchars($server . $newPath) . '" />',
            '<link rel="alternate" hreflang="en" href="' . htmlspecialchars($server . '/en' . $newPath) . '" />',
        ];

        return implode("\n", $hrefLangTags) . "\n";
    }

    public static function getOpenGraphMetaTags($pageTitle, $pageDescription): string
    {
        global $LANG;

        $title = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8');
        $locale = mb_strtolower($LANG) . '_' . mb_strtoupper($LANG);
        $currentUrl = htmlspecialchars(self::getCurrentUrl(), ENT_QUOTES, 'UTF-8');
        $host = htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8');

        $metaTags = [
            '<meta property="og:type" content="website" />',
            '<meta property="og:title" content="' . $title . '" />',
            '<meta property="og:description" content="' . $description . '" />',
            '<meta property="og:url" content="' . $currentUrl . '" />',
            '<meta property="og:locale" content="' . $locale . '" />',
            '<meta property="og:site_name" content="' . $title . '" />',
            '<meta property="og:image" content="https://' . $host . '/public/img/og-image.png" />',
            '<meta property="og:image:type" content="image/png" />',
            '<meta property="og:image:width" content="1200" />',
            '<meta property="og:image:height" content="630" />',
        ];

        return implode("\n", $metaTags) . "\n";
    }

    public static function getSchemaOrgTags(string $name, string $description): string
    {
        $currentUrl = htmlspecialchars(self::getCurrentUrl(), ENT_QUOTES, 'UTF-8');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => $name,
            'url' => $currentUrl,
            'description' => $description,
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'All',
            'browserRequirements' => 'Modern browser with JavaScript support',
            'creator' => [
                '@type' => 'Person',
                'name' => 'Yuriy Plotnikov',
                'url' => 'https://yuriyplotnikovv.ru/',
            ],
        ];

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "<script type=\"application/ld+json\">\n" . $json . "\n</script>\n";
    }
}