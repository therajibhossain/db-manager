<?php


namespace WeatherInfo;


class Front
{
    public function __construct()
    {
        $this->index();
    }

    public function index()
    {
        $res = array();
        $loc = Config::visitor_location();
        if ($loc) {
            $res = $this->openweatherdata($loc['latitude'], $loc['longitude'], 'current');
        }
        return $res;
    }

    //getting weather and forecast data from openweathermap.org
    private function openweatherdata($lat, $lon, $part = '')
    {
        $result = get_transient('weather_info');
        if (false === $result) {
            $option_name = Config::option_name()[0];
            $appId = Config::option_value()[$option_name]['openweatherapikey'];
//Please note that, this plugin relies on openweathermap.org to get weather & forecast data
            $url = "https://api.openweathermap.org/data/2.5/onecall?lat=$lat&lon=$lon&units=metric&appid=$appId";
            $result = wp_remote_retrieve_body(wp_remote_get($url));
            if ($result) {
                $result = json_decode($result, true);
                set_transient('weather_info', $result, 1 * HOUR_IN_SECONDS);
            }
        }
        return $result;
    }
}