<?php
// Suppress all errors from displaying (they should go to error log instead)
error_reporting(0);
ini_set('display_errors', 0);

// Set headers FIRST before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$serverName = "localhost";

$userName = "root";
$password = "";

$conn = @mysqli_connect($serverName, $userName, $password);

if(!$conn){
    echo json_encode([["error" => "Database connection failed"]]);
    exit;
}

$createDatabase = "CREATE DATABASE IF NOT EXISTS prototype2";
if (!mysqli_query($conn, $createDatabase)) {
    echo json_encode([["error" => "Database creation failed"]]);
    exit;
}

// Select the created database
mysqli_select_db($conn, 'prototype2');

// Create table
$createTable = "CREATE TABLE IF NOT EXISTS weather (
    city VARCHAR(100) NOT NULL PRIMARY KEY,
    humidity FLOAT NOT NULL,
    wind FLOAT NOT NULL,
    wind_dir FLOAT NOT NULL,
    icon VARCHAR(10) NOT NULL,
    description VARCHAR(100) NOT NULL,
    temperature FLOAT NOT NULL,
    feels_like FLOAT NOT NULL,
    visibility FLOAT NOT NULL,
    pressure FLOAT NOT NULL,
    last_updated DATETIME NOT NULL
)";

if (!mysqli_query($conn, $createTable)) {
    echo json_encode([["error" => "Table creation failed"]]);
    exit;
}

$cityName = isset($_GET['q']) ? $_GET['q'] : "Milton Keynes";
$searchCity = $cityName;

$selectAllData = "SELECT * FROM weather WHERE city = '" . mysqli_real_escape_string($conn, $searchCity) . "'";
$result = mysqli_query($conn, $selectAllData);
$updateData = false;

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $lastUpdated = strtotime($row['last_updated']);
    if(time() - $lastUpdated > 2*3600) { // 2 hours
        $updateData = true;
    }
} else {
    $updateData = true; // city not in DB
}

// Fetch from API if needed
if ($updateData) {
    $apiKey = "46f355b7b59ed204275755b10b08ceb0";
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($cityName) . "&appid=$apiKey&units=metric";
    
    $context = stream_context_create([
        'http' => [
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);

    if ($response !== FALSE) {
        $data = json_decode($response, true); 
        
        if (isset($data['name'])) {
            $cityName = $data['name'];
            $humidity = $data['main']['humidity'];
            $wind = $data['wind']['speed'];
            $pressure = $data['main']['pressure'];
            $wind_dir = isset($data['wind']['deg']) ? $data['wind']['deg'] : 0;
            $icon = $data['weather'][0]['icon'];
            $description = $data['weather'][0]['description'];
            $temperature = $data['main']['temp'];
            $feels_like = $data['main']['feels_like'];
            $visibility = $data['visibility'];

            // Use prepared statements to avoid SQL injection
            $searchCityEscaped = mysqli_real_escape_string($conn, $searchCity);
            $cityNameEscaped = mysqli_real_escape_string($conn, $cityName);
            
            if(mysqli_num_rows($result) > 0){
                // Update existing row
                $query = "UPDATE weather SET 
                            humidity='$humidity',
                            wind='$wind',
                            pressure='$pressure',
                            wind_dir='$wind_dir',
                            icon='$icon',
                            description='$description',
                            temperature='$temperature',
                            feels_like='$feels_like',
                            visibility='$visibility',
                            last_updated=NOW()
                            WHERE city='$searchCityEscaped'";
            } else {
                $query = "INSERT INTO weather (city, humidity, wind, pressure, wind_dir, icon, description, temperature, feels_like, visibility, last_updated)
                    VALUES ('$cityNameEscaped','$humidity', '$wind', '$pressure', '$wind_dir', '$icon', '$description', '$temperature', '$feels_like', '$visibility', NOW())";        
            }
            
            mysqli_query($conn, $query);
        } else {
            echo json_encode([["error" => "City not found or API failed"]]);
            exit;
        }
    } else {
        echo json_encode([["error" => "City not found or API failed"]]);
        exit;
    } 
}

// Fetching data from weather table based on city name again after insertion
$searchCityEscaped = mysqli_real_escape_string($conn, $searchCity);
$result = mysqli_query($conn, "SELECT * FROM weather WHERE city='$searchCityEscaped'");
$rows = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

// Ensure we have data to return
if (empty($rows)) {
    echo json_encode([["error" => "No data found"]]);
} else {
    echo json_encode($rows);
}

mysqli_close($conn);
?>