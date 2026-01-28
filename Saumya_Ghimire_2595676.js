const API_KEY = '46f355b7b59ed204275755b10b08ceb0';
const API_URL = 'https://api.openweathermap.org/data/2.5/weather';


const cityInput = document.getElementById('cityInput');
const searchBtn = document.getElementById('searchBtn');
const loading = document.getElementById('loading');
const weatherContent = document.getElementById('weatherContent');
const errorMsg = document.getElementById('errorMsg');
const cityName = document.getElementById('cityName');
const currentDate = document.getElementById('currentDate');
const weatherIcon = document.getElementById('weatherIcon');
const weatherDescription = document.getElementById('weatherDescription');
const temperature = document.getElementById('temperature');
const feelsLike = document.getElementById('feelsLike');
const windSpeed = document.getElementById('windSpeed');
const windDirection = document.getElementById('windDirection');
const humidity = document.getElementById('humidity');
const visibility = document.getElementById('visibility');
const pressure = document.getElementById('pressure');



// Initial vaules
function init() {
    updateDate();
    fetchWeather('Milton Keynes');// Is the default city
    
    // Event listeners
    searchBtn.addEventListener('click', handleSearch);
    cityInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    });
}

// Updating the current date
function updateDate() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    currentDate.textContent = now.toLocaleDateString('en-US', options);
}

// Handle search
function handleSearch() {
    const city = cityInput.value.trim();
    if (city) {
        fetchWeather(city);
        cityInput.value = '';
    }
}

// Fetching weather data from the API
async function fetchWeather(city) {
    try {
        showLoading();
        hideError();
         
        const response = await fetch(`http://localhost/Prototype2/connection.php?q=${city}`);
        
        if (!response.ok) {
            throw new Error('City not found');
        }
        
        const data = await response.json();
        displayWeather(data);
        
    } catch (error) {
        showError();
    }finally {
        hideLoading();
    }
}

// Displaying the weather data provided by the API
function displayWeather(data) {
    hideError();
    const weatherData = data[0]; // Get the first item from the array
    
    // --- ADD THIS SECTION TO FIX THE LOCAL STORAGE ISSUE ---
    // This saves the city name as the key and the weather description as the value
    localStorage.setItem(weatherData.city, weatherData.description);
    // -------------------------------------------------------

    cityName.textContent = `${weatherData.city}`;

    const iconCode = weatherData.icon;  
    const iconUrl = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
    
    weatherIcon.innerHTML = `<img src="${iconUrl}" alt="${weatherData.description}" style="width: 100px; height: 100px;">`;
    weatherDescription.textContent = weatherData.description;
    
    temperature.textContent = `${Math.round(weatherData.temperature)}°C`;
    feelsLike.textContent = `${Math.round(weatherData.feels_like)}°C`;

    windSpeed.textContent = `${weatherData.wind} m/s`;
    windDirection.textContent = `Direction: ${weatherData.wind_dir}°`;
    humidity.textContent = `${weatherData.humidity}%`;
    visibility.textContent = `${(weatherData.visibility / 1000).toFixed(1)} km`;
    pressure.textContent = `${weatherData.pressure} hPa`;

    hideLoading();
    weatherContent.classList.remove('hidden');
}

// Show loading by removing hidden class from loading and adding it to weather content
function showLoading() {
    loading.classList.remove('hidden');
    weatherContent.classList.add('hidden');
}

// Hide loading by adding hidden class to loading
function hideLoading() {
    loading.classList.add('hidden');
}

// Show error when city is not found 
function showError() {
    errorMsg.classList.remove('hidden');
}

// Hide error when city is found
function hideError() {
    errorMsg.classList.add('hidden');
}

// Start the app
init();