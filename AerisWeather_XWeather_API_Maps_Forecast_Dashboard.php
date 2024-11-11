<?php

/**
 * My AerisWeather/XWeather API dashboard with Temp and Sea maps.
 * This also caches, and expects am accessible directory at x-cache/
 * Also: make sure to put the x-config.conf somewhere, that contains the API creds:
 * 
 * [API]
 * client_id = foobarbabababa
 * client_secret = U9999999999999999999999abcabc2
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = parse_ini_file('/home/somewhere/x-config.conf', true);
$client_id = $config['API']['client_id'];
$client_secret = $config['API']['client_secret'];

// Define cache settings
$cache_dir = 'x-cache/'; // Ensure this directory is writable and within the web root
$cache_time = 10800; // Cache for 3 hours

function get_cached_data($url, $cache_file) {
    global $cache_dir, $cache_time;

    $cache_path = $cache_dir . $cache_file;
    if (file_exists($cache_path) && (time() - filemtime($cache_path)) < $cache_time) {
        return file_get_contents($cache_path);
    }

    $data = file_get_contents($url);
    if ($data !== false) {
        file_put_contents($cache_path, $data);
    }

    return $data;
}

function get_cached_image($url, $cache_file) {
    global $cache_dir, $cache_time;

    $cache_path = $cache_dir . $cache_file;
    if (file_exists($cache_path) && (time() - filemtime($cache_path)) < $cache_time) {
        return $cache_path;
    }

    $image_data = file_get_contents($url);
    if ($image_data !== false) {
        file_put_contents($cache_path, $image_data);
        return $cache_path;
    }

    return null;  // Return null if fetching fails
}

function get_cached_image_info($cache_file) {
    global $cache_dir, $cache_time;

    $cache_path = $cache_dir . $cache_file;
    if (file_exists($cache_path)) {
        $cache_age = time() - filemtime($cache_path);
        $renewal_time = $cache_time - $cache_age;
        return [
            'age' => $cache_age,
            'renewal' => $renewal_time
        ];
    }
    return null;
}

// Map URLs
$map_url1 = "https://maps.aerisapi.com/{$client_id}_{$client_secret}/fires-obs-icons,temperatures:60,water-depth,terrain:90:blend(overlay),lightning-strikes-5m-icons,admin-cities-dk:65/800x800/-33.9935,21.9507,8/current.png";
$map_url2 = "https://maps.aerisapi.com/{$client_id}_{$client_secret}/admin-cities-dk,blue-marble,maritime-sst:80,satellite-infrared-color:blend(screen),humidity-text-dk:80/600x600/-33.9616,22.5439,6/current.png";

// Cache map images
$map_image1 = get_cached_image($map_url1, 'map1_cache.png');
$map_image2 = get_cached_image($map_url2, 'map2_cache.png');

$map_image1_info = get_cached_image_info('map1_cache.png');
$map_image2_info = get_cached_image_info('map2_cache.png');

// Forecast URLs
$forecast_url_3hr = "https://data.api.xweather.com/forecasts/-33.901849,21.553976?format=json&filter=3hr&limit=5&fields=periods.dateTimeISO,loc,periods.maxTempC,periods.minTempC,periods.pop,periods.maxHumidity,periods.minHumidity,periods.windSpeedMaxKPH,periods.weather&client_id={$client_id}&client_secret={$client_secret}";
$forecast_url_day = "https://data.api.xweather.com/forecasts/-33.901849,21.553976?format=geojson&filter=daynight&limit=3&fields=periods.maxTempC,periods.minTempC,periods.pop,periods.precipMM,periods.maxHumidity,periods.minHumidity,periods.maxDewpointC,periods.minDewpointC,periods.windSpeedMaxKPH,periods.windSpeedMinKPH,periods.windDirMax,periods.windDirMin,periods.weather&client_id={$client_id}&client_secret={$client_secret}";

// Lightning URL
$lightning_url = "https://data.api.xweather.com/lightning/-33.901849,21.553976?format=json&filter=cg&limit=10&fields=ob,loc,recISO&client_id={$client_id}&client_secret={$client_secret}";

// Fetch 3-hour forecast
$forecast_data_3hr = get_cached_data($forecast_url_3hr, 'forecast_3hr_cache.json');
$forecast_json_3hr = json_decode($forecast_data_3hr, true);
$periods_3hr = $forecast_json_3hr['response'][0]['periods'] ?? [];

// Fetch daily forecast
$forecast_data_day = get_cached_data($forecast_url_day, 'forecast_day_cache.json');
$forecast_json_day = json_decode($forecast_data_day, true);
$periods_day = $forecast_json_day['features'][0]['properties']['periods'] ?? [];

// Fetch lightning data
$lightning_data = get_cached_data($lightning_url, 'lightning_cache.json');
$lightning_json = json_decode($lightning_data, true);
$lightning_events = $lightning_json['response'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Simple-Earth.org Weather Station Forecast</title>
    <style>
        .cache-info {
            font-size: 0.7em;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple-Earth.org Weather Station Forecast</h1>
        <h2>Langeberg, Klein Karoo</h2>

        <div class="maps">
            <div class="map">
                <h3>Temperature Heat Map (+Lightning & Fire)</h3>
                <img src="<?php echo $map_image1; ?>" alt="Temperature Map">
                <?php if ($map_image1_info): ?>
                    <div class="cache-info">
                        Cached <?php echo round($map_image1_info['age'] / 60); ?> min ago, renews in <?php echo round($map_image1_info['renewal'] / 60); ?> min
                    </div>
                <?php endif; ?>
            </div>
            <div class="map">
                <h3>Sea Temps and Humidity %</h3>
                <img src="<?php echo $map_image2; ?>" alt="Sea Temps and Humidity">
                <?php if ($map_image2_info): ?>
                    <div class="cache-info">
                        Cached <?php echo round($map_image2_info['age'] / 60); ?> min ago, renews in <?php echo round($map_image2_info['renewal'] / 60); ?> min
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h3>3 Hour Forecast String</h3>
        <div class="forecast">
            <?php foreach ($periods_3hr as $period): ?>
                <div class="period">
                    <h4><?php echo date('l, H:i', strtotime($period['dateTimeISO'])); ?></h4>
                    <p>Max Temp: <?php echo $period['maxTempC']; ?>Â°C</p>
                    <p>Min Temp: <?php echo $period['minTempC']; ?>Â°C</p>
                    <p>Precipitation: <?php echo $period['pop']; ?>%</p>
                    <p>Max Humidity: <?php echo $period['maxHumidity']; ?>%</p>
                    <p>Min Humidity: <?php echo $period['minHumidity']; ?>%</p>
                    <p>Max Wind Speed: <?php echo $period['windSpeedMaxKPH']; ?> kph</p>
                    <p>Weather: <?php echo $period['weather']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>3 Day Forecast</h3>
        <div class="forecast-daily">
            <?php foreach ($periods_day as $index => $period): ?>
                <?php $day_label = ['Today', 'Tomorrow', 'Day After'][$index]; ?>
                <div class="period-day">
                    <h4><?php echo $day_label; ?></h4>
                    <p>Max Temp: <?php echo $period['maxTempC']; ?>Â°C</p>
                    <p>Min Temp: <?php echo $period['minTempC']; ?>Â°C</p>
                    <p>Precipitation: <?php echo $period['precipMM']; ?>mm</p>
                    <p>Max Humidity: <?php echo $period['maxHumidity']; ?>%</p>
                    <p>Min Humidity: <?php echo $period['minHumidity']; ?>%</p>
                    <p>Max Wind Speed: <?php echo $period['windSpeedMaxKPH']; ?> kph</p>
                    <p>Weather: <?php echo $period['weather']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>Lightning Information</h3>
        <div class="lightning-info">
            <?php if (!empty($lightning_events)): ?>
                <?php foreach ($lightning_events as $event): ?>
                    <div class="lightning-event">
                        <p>Time: <?php echo date('l, H:i', strtotime($event['ob']['dateTimeISO'])); ?></p>
                        <p>Location: Lat <?php echo $event['loc']['lat']; ?>, Long <?php echo $event['loc']['long']; ?></p>
                        <p>Type: <?php echo $event['ob']['pulse']['type']; ?></p>
                        <p>Peak Amplitude: <?php echo $event['ob']['pulse']['peakamp']; ?> A</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recent lightning data available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

