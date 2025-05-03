const DEFAULT_LOCATION = '55.7558,37.6176';
const API_ENDPOINTS = {
  ip: 'https://api.ipify.org?format=json',
  ipLocation: 'ajax/ipLocation.php',
  weather: 'ajax/weather.php'
};
const loadingBlocks = document.querySelectorAll('.section');
const currentWeatherContainer = document.querySelector('.current-weather__content');
const dayWeatherContainer = document.querySelector('.day-forecast__list');
const nextWeatherContainer = document.querySelector('.next-forecast__list');
const translations = {
  en: {
    now: 'At present:',
    temperature: '°C',
    windSpeed: 'm/s',
    humidity: '%',
    pressure: 'mmHg',
    morning: 'Morning:',
    afternoon: 'Afternoon:',
    evening: 'Evening:',
    windDirections: ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW']
  },
  ru: {
    now: 'Сейчас:',
    temperature: '°C',
    windSpeed: 'м/с',
    humidity: '%',
    pressure: 'мм рт. ст.',
    morning: 'Утром:',
    afternoon: 'Днем:',
    evening: 'Вечером:',
    windDirections: ['С', 'СВ', 'В', 'ЮВ', 'Ю', 'ЮЗ', 'З', 'СЗ']
  }
};
let nowTitle = '';
let temperatureUnit = '';
let windSpeedUnit = '';
let humidityUnit = '';
let pressureUnit = '';
let morningLabel = '';
let afternoonLabel = '';
let eveningLabel = '';
let windDirections = [];

document.addEventListener('DOMContentLoaded', async () => {
  try {
    changeLangValues();

    const location = await getIpLocation() || DEFAULT_LOCATION;
    const [latitude, longitude] = location.split(',');

    await fetchWeather(latitude, longitude);
    await getGeoLocation();
  }
  catch (error) {
    console.error('Ошибка при инициализации:', error);
  }
});

async function fetchWeather(latitude, longitude) {
  clearContent();

  loadingBlocks.forEach((item) => {
    item.classList.add('loading');
  })

  try {
    const response = await fetch(`${API_ENDPOINTS.weather}?latitude=${latitude}&longitude=${longitude}`);
    const responseData = await response.json();
    if (responseData.success) {
      loadingBlocks.forEach((item) => {
        item.classList.remove('loading');
      })

      showWeatherData(responseData.data);
    } else {
      console.error('Ошибка:', responseData.message);
    }
  }
  catch (error) {
    console.error('Ошибка при получении данных о погоде:', error);
  }
}

async function getIpLocation() {
  try {
    const responseIp = await fetch(API_ENDPOINTS.ip);
    const responseIpData = await responseIp.json();
    const currentIp = responseIpData.ip;

    const response = await fetch(`${API_ENDPOINTS.ipLocation}${currentIp ? `?currentIp=${currentIp}` : ''}`);
    const responseData = await response.json();

    if (responseData.success) {
      return `${responseData.data.latitude.toFixed(4)},${responseData.data.longitude.toFixed(4)}`;
    } else {
      console.error('Ошибка при получении IP-локации:', responseData.message);
      return null;
    }
  }
  catch (error) {
    console.error('Ошибка при получении IP-локации:', error);
    return null;
  }
}

async function getGeoLocation() {
  if (navigator.geolocation) {
    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(position => {
        const latitude = position.coords.latitude.toFixed(4);
        const longitude = position.coords.longitude.toFixed(4);
        fetchWeather(latitude, longitude).then(resolve).catch(reject);
      }, error => {
        console.error('Ошибка при получении геолокации:', error);
        alert('Не удалось получить ваше местоположение.');
        reject(error);
      });
    });
  } else {
    alert('Геолокация не поддерживается вашим браузером.');
    return Promise.reject(new Error('Геолокация не поддерживается'));
  }
}

function changeLangValues() {
  const lang = document.documentElement.lang || 'ru';

  if (translations[lang]) {
    nowTitle = translations[lang].now;
    temperatureUnit = translations[lang].temperature;
    windSpeedUnit = translations[lang].windSpeed;
    humidityUnit = translations[lang].humidity;
    pressureUnit = translations[lang].pressure;
    morningLabel = translations[lang].morning;
    afternoonLabel = translations[lang].afternoon;
    eveningLabel = translations[lang].evening;
    windDirections = translations[lang].windDirections;
  }
}

function clearContent() {
  currentWeatherContainer.innerHTML = '';
  dayWeatherContainer.innerHTML = '';
  nextWeatherContainer.innerHTML = '';
}

function showWeatherData(data) {
  showCurrentData(data);
  showForecastDayData(data);
  showForecastNextData(data);

  const buttonLocation = document.querySelector('.current-weather__location');

  buttonLocation.addEventListener('click', async () => {
    try {
      await getGeoLocation();
    }
    catch (error) {
      console.error('Ошибка при получении геолокации:', error);
    }
  });

  loadingBlocks.forEach((item) => {
    item.classList.remove('loading');
  })
}

function showCurrentData(data) {
  const weatherContainer = document.querySelector('.current-weather__content');
  const address = `${data.address.road}, ${data.address.city || data.address.town}, ${data.address.country}`;
  const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: false});
  const currentWeather = data.weather.current;
  const degrees = currentWeather.wind_direction.toFixed(0);
  const windDirection = degreesToWindDirection(degrees);
  const prefix = 'current-weather';

  weatherContainer.innerHTML = `
    <button class="current-weather__location" type="button">
      <svg class="current-weather__location-icon" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
          <use xlink:href="/public/img/sprite.svg#icon-compass"/>
      </svg>
      <p class="current-weather__location-address">${address}</p>
    </button>
    <p class="current-weather__time">${nowTitle} ${time}</p>
      <img class="current-weather__icon" src="/public/img/svg/weather/${currentWeather.icon}.svg" alt="${currentWeather.icon}">
    <div class="current-weather__detail">
      ${createWeatherDetail(currentWeather.air_temperature, 'temperature', prefix, `${temperatureUnit}`)}
      ${createWeatherDetail(currentWeather.wind_speed, 'wind-speed', prefix, `${windSpeedUnit}`)}
      ${createWeatherDetail(windDirection, 'wind-direction', prefix, '', '' + (parseFloat(degrees) + 180))}
      ${createWeatherDetail(currentWeather.humidity, 'humidity', prefix, `${humidityUnit}`)}
      ${createWeatherDetail(currentWeather.air_pressure, 'pressure', prefix, `${pressureUnit}`)}
    </div>`;
}

function showForecastDayData(data) {
  const weatherContainer = document.querySelector('.day-forecast__list');

  data.weather.day.forEach(item => {
    weatherContainer.appendChild(createForecastElement(item, 'day-forecast'));
  });
}

function showForecastNextData(data) {
  const weatherContainer = document.querySelector('.next-forecast__list');

  data.weather.next.forEach(item => {
    weatherContainer.appendChild(createForecastElement(item, 'next-forecast'));
  });
}

function createWeatherDetail(value, type, prefix, units, degrees = '') {
  const iconPath = prefix !== 'current-weather' ? `${type}-static` : type;

  return `
    <div class="${prefix}__detail-item">
      <img class="${prefix}__detail-icon" ${degrees ? `style="transform:rotate(${degrees}deg)"` : ''} src="/public/img/weather-symbols/${iconPath}" alt="">
      <span class="${prefix}__detail-value">${value}</span>
      ${units ? `<span class="${prefix}__detail-units">${units}</span>` : ''}
    </div>`;
}

function createForecastElement(item, prefix) {
  const degrees = item.wind_direction.toFixed(0);
  const windDirection = degreesToWindDirection(degrees);

  const element = document.createElement('li');
  element.classList.add(`${prefix}__item`, 'swiper-slide');
  element.innerHTML = `
    <p class="${prefix}${item.time ? '__time' : '__date'}">${item.time || item.date}</p>
    <img class="${prefix}__icon" src="/public/img/svg/weather/${item.icon}" alt="${item.icon}">
    <div class="${prefix}__detail">
      ${
    typeof item.air_temperature === 'object' && item.air_temperature !== null
      ? createTimeSpecificDetails(item.air_temperature, 'temperature', prefix, `${temperatureUnit}`)
      : createWeatherDetail(item.air_temperature, 'temperature', prefix, `${temperatureUnit}`)
  }
      ${createWeatherDetail(item.wind_speed, 'wind-speed', prefix, `${windSpeedUnit}`)}
      ${createWeatherDetail(windDirection, 'wind-direction', prefix, '', '' + (parseFloat(degrees) + 180))}
      ${createWeatherDetail(item.humidity, 'humidity', prefix, `${humidityUnit}`)}
      ${createWeatherDetail(item.air_pressure, 'pressure', prefix, `${pressureUnit}`)}
    </div>`;

  return element;
}

function createTimeSpecificDetails(temperatures, type, prefix, units) {
  const timeOrder = ['06', '12', '18'];
  const timeLabels = {
    '06': `${morningLabel}`,
    '12': `${afternoonLabel}`,
    '18': `${eveningLabel}`
  };

  return timeOrder.map(time => {
    const label = timeLabels[time];
    const value = temperatures[time] !== undefined ? temperatures[time] : 'N/A';
    return `
      <div class="${prefix}__detail-item">
        <span class="${prefix}__detail-time">${label}</span>
        <span class="${prefix}__detail-value">${value}</span>
        ${units ? `<span class="${prefix}__detail-units">${units}</span>` : ''}
      </div>`;
  }).join('');
}

function degreesToWindDirection(degrees) {
  const index = Math.round(degrees / 45) % 8;

  return windDirections[index];
}
