<?php
// PostgreSQL configuration
//host=cloud-app-meistergen-server.postgres.database.azure.com port=5432 dbname=cloud-app-meistergen-database sslmode=require user=wgwenaesai password=7E8ER7K8DOU8BSY6$
$dbname = "cloud-app-meistergen-database";
$dbuser = "wgwenaesai";
$dbpass = "7E8ER7K8DOU8BSY6$";
$dbhost = "cloud-app-meistergen-server.postgres.database.azure.com";
$dbport = "5432";

// Create a PDO connection to the PostgreSQL database
$pdo = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);

// Function to create the weather_data table if it doesn't exist
function createWeatherDataTable($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS weather_data (
        id SERIAL PRIMARY KEY,
        city VARCHAR(255) UNIQUE,
        temperature NUMERIC,
        humidity NUMERIC,
        wind_speed NUMERIC,
        description VARCHAR(255)
    )";
    $pdo->exec($sql);
    
}

// Call createWeatherDataTable to create the table if it doesn't exist
createWeatherDataTable($pdo);

function addWeatherData($pdo, $city, $temperature, $humidity, $windSpeed, $description) {
    // Convert city name to lowercase
    $cityLower = strtolower($city);

    // SQL query to insert or update weather data
    $sql = "INSERT INTO weather_data (city, temperature, humidity, wind_speed, description)
            VALUES (:city, :temperature, :humidity, :wind_speed, :description)
            ON CONFLICT (city) DO UPDATE 
            SET temperature = EXCLUDED.temperature,
                humidity = EXCLUDED.humidity,
                wind_speed = EXCLUDED.wind_speed,
                description = EXCLUDED.description";
    
    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':city' => $cityLower, // Use lowercase city name
        ':temperature' => $temperature,
        ':humidity' => $humidity,
        ':wind_speed' => $windSpeed,
        ':description' => $description
    ]);
}

// Function to fetch weather data from OpenWeatherMap API
function fetchWeatherData($pdo, $city) {
    // API key for OpenWeatherMap
    $apiKey = "aba6ff9d6de967d5eac6fd79114693cc";
    
    // URL to fetch weather data for the specified city
    $url = "https://api.openweathermap.org/data/2.5/weather?q=$city&units=metric&appid=$apiKey";
    
    // Fetch weather data from the API
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    // Extract relevant data from the API response
    $main = $data['main'];
    $weather = $data['weather'][0];
    $wind = $data['wind'];
    $temp = $main['temp'];
    $humidity = $main['humidity'];
    $windSpeed = $wind['speed'];
    $description = $weather['description'];
    $iconCode = $weather['icon']; // Added line to fetch icon code
    
    // Add weather data to the database
    addWeatherData($pdo, $city, $temp, $humidity, $windSpeed, $description);

    // Return weather data including an 'id' value (set to null as it's not available from the API)
    return [
        'id' => null,
        'city' => $city,
        'temperature' => $temp,
        'humidity' => $humidity,
        'wind_speed' => $windSpeed,
        'description' => $description,
        'icon' => $iconCode // Added line to include icon code
    ];
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['city'])) {
    $city = $_POST['city'];
    $weatherData = fetchWeatherData($pdo, $city);
} else {
    // Fetch weather data for Chennai by default if no city is specified
    $weatherData = fetchWeatherData($pdo, 'Chennai');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Web App</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background: black;
            background-image: url('https://source.unsplash.com/1600x900/?landscape');
            margin: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            background: rgba(0, 0, 0, 0);
            color: white;
            padding: 2em;
            border-radius: 10px;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .search {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1em;
        }

        input.search-bar {
            border: none;
            outline: none;
            padding: 0.5em 1em;
            border-radius: 24px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            font-size: 16px;
            width: calc(100% - 100px);
        }

        button {
            margin-left: 1em;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            color: white;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .weather {
            margin-top: 1em;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="search">
                <form method="post">
                    <input type="text" class="search-bar" name="city" placeholder="Search">
                    <button type="submit">&#x1F50D;</button>
                </form>
            </div>
            <div class="weather">
                <h2 class="city"><?= isset($weatherData['city']) ? ucfirst($weatherData['city']) : 'City' ?></h2>
                <h1 class="temp"><?= isset($weatherData['temperature']) ? $weatherData['temperature'] . '°C' : 'N/A' ?></h1>
                <div class="flex">
                    <img src="https://openweathermap.org/img/wn/<?= isset($weatherData['icon']) ? $weatherData['icon'] . '@2x.png' : 'na' ?>" alt="Weather Icon" class="icon" />
                    <div class="description"><?= isset($weatherData['description']) ? ucfirst($weatherData['description']) : 'N/A' ?></div>
                </div>

                <div class="humidity"><?= isset($weatherData['humidity']) ? 'Humidity: ' . $weatherData['humidity'] . '%' : 'Humidity: N/A' ?></div>
                <div class="wind"><?= isset($weatherData['wind_speed']) ? 'Wind speed: ' . $weatherData['wind_speed'] . ' km/h' : 'Wind speed: N/A' ?></div>
            </div>
        </div>
    </div>
</body>

</html>
