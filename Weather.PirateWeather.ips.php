<?php

declare(strict_types=1);

################################################################################
# Scriptbezeichnung: Weather.PirateWeather.ips.php
# Version: 3.4.20241221
# Author:  Heiko Wilknitz (@Pitti)
#
# Abruf von Wetterdaten via PirateWeather API!
# ACHTUNG: Nutzungsrechte beachten!
#
# Thanks to Bas Milius (https://bas.dev/) for the free to use animated SVG
# weather icons.
#
# ------------------------------ Installation ----------------------------------
#
# Zur Benutzung dieses Scriptes bedarf es ein Lizenzschlüssel (Secret Key).
# Alle Infos bekommt man auf https://pirateweather.net/ .
# Dieser Schlüssel und noch weitere Konfigurationsdaten müssen vor Ausführen
# des Scriptes getätigt werden.
# Dieses Skript richtet dann automatisch alle nötigen Objekte bei manueller
# Ausführung ein. Eine weitere manuelle Ausführung setzt alle benötigten Objekte
# wieder auf den Ausgangszustand.
#
# - API Key beantragen (Regisrieren)
# - Neues Skript erstellen
# - Diesen PHP-Code hineinkopieren
# - Skript abspeichern
# - Abschnitt 'Konfiguration' den persönlichen Begebenheiten anpassen
# - Skript ausführen
#
# ------------------------------ Changelog -------------------------------------
#
# 10.03.2019 - Initalversion (v1.0)
# 24.03.2023 - Umstellung auf PirateWeather API (v2.0)
# 06.11.2023 - Anpassung für Nutzung via Pitti's Skript-Bibliothek (v3.0)
# 16.11.2023 - Erweiterungen für Tile Visu (v3.1)
#              Unterstützung von Themes, Vorhersage und einiges mehr
# 04.03.2024 - Kleine Anpassungen für Tile Visu (v3.2)
# 12.08.2024 - Abruf auf CURL umgebaut (hoffentlich besseres Fehlerverhalten),
#              weitere Fixes für TileVisu und
#              Unterstützung für openHASP Mini Display (v3.3)
# 21.12.2024   Umstellung auf WwxTileVisu, Kleiner Anpassung (v3.4)
#
# ----------------------------- Konfigruration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = false;

$USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36';
#
# Globale Variable __WWX als Array via define() in __autoload definiert!!!
$API = __WWX['PWN_TOKEN']; // # API-Token (Key)
#
# Location Control ID (wenn 0 => müssen $LAT & $LON korrekt gesetzt werden)
$LCID = __WWX['IID_LC'];
# Latitude/Längengrad
$LAT = 52.5208;
# Longitude/Breitengrad
$LON = 13.4094;
#
# Ortsinfos setzen
if ($LCID >= __IPS_MIN_ID) {
    $location = json_decode(IPS_GetProperty($LCID, 'Location'), true);
    $LAT = $location['latitude'];
    $LON = $location['longitude'];
}
#
# Settings für HTML-Boxen;
$HTML = [
    'temp'      => 44569,   // ID eigenen Temperaturvariable, z.B. von Wetterstation
    'chance'    => 39027,   // ID eigener Niederschlagswahrscheinlichkeit
    'wind'      => 46226,   // ID eigener Windgeschwindigkeit
    'direction' => 37150,   // ID eigener Windrichtung
    'humidity'  => 50653,   // ID eigener Luftfeutigkeit (Aussen)
    'rain'      => 27144,   // ID eigener Niederschlag/Tag
    'sunrise'   => date_sunrise(time(), SUNFUNCS_RET_STRING, $LAT, $LON, 90, 1),  // Uhrzeit 'hh:mm' für Sonnenaufgang, oder via GetValue() vom Location Modul Variable => date('H:i', Getvalue(12345));
    'sunset'    => date_sunset(time(), SUNFUNCS_RET_STRING, $LAT, $LON, 90, 1),   // Uhrzeit 'hh:mm' für Sonnenuntergang, oder via GetValue() vom Location Modul Variable => date('H:i', Getvalue(12345));
    'webfront'  => true,    // true = Support für WebFront ('3-Tage-Wetter' & '24-Stunden--Wetter'), IPS <= v6.4
    'tilevisu'  => true,    // true = Support für Tile Visu ('Aktuelles-Wetter'). IPS >= v7.0
    'icon01'    => true,   // true = Nutzung eigener Icons für aktuelle Wetterlage (siehe Array $ICONS) oder nachfolgende URL hinterlegen
    'icon03'    => true,   // true = Nutzung eigener Icons für 3 Tage Vorhersage (siehe Array $ICONS) oder nachfolgende URL hinterlegen
    'icon07'    => false,   // true = Nutzung eigener Icons für 7 Tage Vorhersage (siehe Array $ICONS) oder nachfolgende URL hinterlegen
    'icon24'    => false,   // true = Nutzung eigener Icons für 24 Stunden Vorhersage (siehe Array $ICONS) oder nachfolgende URL hinterlegen
    'ibase'     => 'https://basmilius.github.io/weather-icons/production/line/all/', // URL Base für Online-Icons (.../fill/all/ or .../line/all/)
    'iext'      => '.svg',  // Image Type Extension (.png, .svg, .jpg, ...)
];
#
# Globale Übersetzungstabelle
$TRANS = [
    'Monday'    => 'Montag',
    'Tuesday'   => 'Dienstag',
    'Wednesday' => 'Mittwoch',
    'Thursday'  => 'Donnerstag',
    'Friday'    => 'Freitag',
    'Saturday'  => 'Samstag',
    'Sunday'    => 'Sonntag',
    'Mon'       => 'Mo',
    'Tue'       => 'Di',
    'Wed'       => 'Mi',
    'Thu'       => 'Do',
    'Fri'       => 'Fr',
    'Sat'       => 'Sa',
    'Sun'       => 'So',
    'January'   => 'Januar',
    'February'  => 'Februar',
    'March'     => 'März',
    'May'       => 'Mai',
    'June'      => 'Juni',
    'July'      => 'Juli',
    'October'   => 'Oktober',
    'December'  => 'Dezember',
    // Summary texts
    'Clear'         => 'Klar',
    'Cloudy'        => 'Bewölkt',
    'Partly Cloudy' => 'Teilweise bewölkt',
    'Rain'          => 'Regen',
    'Snow'          => 'Schnee',
    'Windy'         => 'Windig',
];
#
# Icon Mapping Array - freies Mapping auf eigene lokale Icons
# Pirate Weather kennt folgende Namen für Icons:
#  clear-day, clear-night,
#  rain, snow, sleet, wind, fog, cloudy,
#  partly-cloudy-day, partly-cloudy-night
$ICONS = [
    // -------------------------------- day ------------------------------------
    'day' => [
        'clear'  => '/user/weather/img/day/day_sunny.png',
        'rain'   => '/user/weather/img/day/day_heavy_rain.png',
        'snow'   => '/user/weather/img/day/day_heavy_snow.png',
        'sleet'  => '/user/weather/img/day/day_sleet.png',
        'wind'   => '/user/weather/img/day/day_overcast.png',
        'fog'    => '/user/weather/img/day/day_fog.png',
        'cloudy' => '/user/weather/img/day/day_clouded.png',
        'partly' => '/user/weather/img/day/day_cloudy.png',
    ],
    // ------------------------------- night -----------------------------------
    'night' => [
        'clear'  => '/user/weather/img/night/night_sunny.png',
        'rain'   => '/user/weather/img/night/night_heavy_rain.png',
        'snow'   => '/user/weather/img/night/night_heavy_snow.png',
        'sleet'  => '/user/weather/img/night/night_sleet.png',
        'wind'   => '/user/weather/img/night/night_overcast.png',
        'fog'    => '/user/weather/img/night/night_fog.png',
        'cloudy' => '/user/weather/img/night/night_clouded.png',
        'partly' => '/user/weather/img/night/night_cloudy.png',
    ],
    // ------------------------------- sign -----------------------------------
    'sign' => [
        'clear'  => '#FFC107 \uE5A8#',
        'rain'   => '#11A0F3 \uE596#',
        'snow'   => '#FFFFFF \uE598#',
        'sleet'  => '#11A0F3 \uE67F#',
        'wind'   => '#FFFFFF \uE59D#',
        'fog'    => '#999A9C \uE591#',
        'cloudy' => '#FFFFFF \uE590#',
        'partly' => '#FFC107 \uE595#',
    ],
    // -------------------------------- unknown --------------------------------
    'unknown' => [
        'icon' => '/user/weather/img/night/night_thunderstorm.png',
    ],
];
#
################################################################################
#
# Requires include of the global function script via autoload (__autoload.php)
# or direct in the script (uncomment next line)!
# require_once(IPS_GetKernelDir()."scripts".DIRECTORY_SEPARATOR.'System.Functions.ips.php');
# You can download it from here https://github.com/wilkware/ips-scripts
#
defined('WWX_FUNCTIONS') || die('Global function library not available!');

// INSTALLATION
if ($_IPS['SENDER'] == 'Execute') {
    $pos = 1; // 0 für Forecast
    // Variablen
    $vid = CreateVariableByName($_IPS['SELF'], 'Temperatur', 2, $pos++, '', '~Temperature');
    $vid = CreateVariableByName($_IPS['SELF'], 'Gefühlte Temperatur', 2, $pos++, '', '~Temperature');
    $vid = CreateVariableByName($_IPS['SELF'], 'Taupunkt', 2, $pos++, '', '~Temperature');
    $vid = CreateVariableByName($_IPS['SELF'], 'Bewölkung', 2, $pos++, 'Cloudy', '~Intensity.1');
    $vid = CreateVariableByName($_IPS['SELF'], 'Luftfeuchtigkeit', 2, $pos++, 'Drops', '~Intensity.1');
    $vid = CreateVariableByName($_IPS['SELF'], 'Luftdruck', 2, $pos++, '', '~AirPressure.F');
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlag', 3, $pos++, 'Rainfall');
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlag/h', 2, $pos++, '~Rainfall');
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlagswahrscheinlichkeit', 2, $pos++, 'Rainfall', '~Intensity.1');
    $vid = CreateVariableByName($_IPS['SELF'], 'Windgeschwindigkeit', 2, $pos++, '', '~WindSpeed.kmh');
    $vid = CreateVariableByName($_IPS['SELF'], 'Windrichtung', 2, $pos++, '', '~WindDirection.Text');
    $vid = CreateVariableByName($_IPS['SELF'], 'Windböe', 2, $pos++, '', '~WindSpeed.kmh');
    $vid = CreateVariableByName($_IPS['SELF'], 'Sichtweite', 2, $pos++);
    $vid = CreateVariableByName($_IPS['SELF'], 'UV Strahlung', 1, $pos++);
    $vid = CreateVariableByName($_IPS['SELF'], 'Ozonwert', 2, $pos++);
    $vid = CreateVariableByName($_IPS['SELF'], 'Zusammenfassung', 3, $pos++, 'Talk');
    $vid = CreateVariableByName($_IPS['SELF'], 'Icon', 3, $pos++);
    $vid = CreateVariableByName($_IPS['SELF'], 'Zeichen', 3, $pos++);
    $vid = CreateVariableByName($_IPS['SELF'], 'Uhrzeit', 1, $pos++, '~UnixTimestamp');
    // Weateher & Forecast HTML Boxes
    $vid = CreateVariableByName($_IPS['SELF'], 'Aktuelles-Wetter', 3, $pos++, '', '~HTMLBox');
    $vid = CreateVariableByName($_IPS['SELF'], '3-Tage-Wetter', 3, $pos++, '', '~HTMLBox');
    $vid = CreateVariableByName($_IPS['SELF'], '24-Stunden-Wetter', 3, $pos++, '', '~HTMLBox');
    // Service Update Timer (aller 10 Minuten)
    $eid = CreateTimerByName($_IPS['SELF'], 'ServiceUpdate', 10);
    IPS_SetHidden($eid, true);
    IPS_SetPosition($eid, -1);
}
// TIMER EVENT
elseif ($_IPS['SENDER'] == 'TimerEvent') {
    //  Daten abholen
    $url = "https://api.pirateweather.net/forecast/$API/$LAT,$LON?exclude=minutely&lang=de&units=ca";
    if ($DEBUG) EchoDebug('WEATHER', $url);
    // Query
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $USER_AGENT);
    $json = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    //var_dump($json);
    if (empty($json) || $json === false || !empty($error)) {
        $msg = 'Empty answer from pirateweather: ' . $error . "\n";
        IPS_LogMessage('WEATHER', $msg);
        return;
    }
    $data = json_decode($json);
    if (!isset($data->{'currently'})) {
        IPS_LogMessage('WEATHER', $json);
        return;
    }
    // Aktuelle Daten
    SetCurrentWeather($data->{'currently'});
    // Tägliche Daten holen
    SetDailyWeather($data->{'daily'}->{'data'});
    // 24h Daten holen
    SetHourlyWeather($data->{'hourly'}->{'data'});
}

// Extrahiert die aktuellen Wetterdaten
function SetCurrentWeather($current)
{
    global $TRANS;
    $vid = CreateVariableByName($_IPS['SELF'], 'Uhrzeit', 1);
    SetValue($vid, $current->time);
    $vid = CreateVariableByName($_IPS['SELF'], 'Zusammenfassung', 3);
    SetValue($vid, strtr($current->summary, $TRANS));
    $vid = CreateVariableByName($_IPS['SELF'], 'Icon', 3);
    SetValue($vid, $current->icon);
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlag', 3);
    @$niederschlag = $current->precipType;
    $niederschlag = GetPrecipitation($niederschlag);
    SetValue($vid, GetPrecipitation($niederschlag));
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlag/h', 2);
    SetValue($vid, $current->precipIntensity);
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlagswahrscheinlichkeit', 2);
    SetValue($vid, $current->precipProbability);
    $vid = CreateVariableByName($_IPS['SELF'], 'Temperatur', 2);
    SetValue($vid, $current->temperature);
    $vid = CreateVariableByName($_IPS['SELF'], 'Gefühlte Temperatur', 2);
    SetValue($vid, $current->apparentTemperature);
    $vid = CreateVariableByName($_IPS['SELF'], 'Taupunkt', 2);
    SetValue($vid, $current->dewPoint);
    $vid = CreateVariableByName($_IPS['SELF'], 'Luftfeuchtigkeit', 2);
    SetValue($vid, $current->humidity);
    $vid = CreateVariableByName($_IPS['SELF'], 'Luftdruck', 2);
    SetValue($vid, $current->pressure);
    $vid = CreateVariableByName($_IPS['SELF'], 'Windgeschwindigkeit', 2);
    SetValue($vid, $current->windSpeed);
    $vid = CreateVariableByName($_IPS['SELF'], 'Windböe', 2);
    SetValue($vid, $current->windGust);
    $vid = CreateVariableByName($_IPS['SELF'], 'Windrichtung', 2);
    SetValue($vid, $current->windBearing);
    $vid = CreateVariableByName($_IPS['SELF'], 'Bewölkung', 2);
    SetValue($vid, $current->cloudCover);
    $vid = CreateVariableByName($_IPS['SELF'], 'UV Strahlung', 1);
    SetValue($vid, $current->uvIndex);
    $vid = CreateVariableByName($_IPS['SELF'], 'Sichtweite', 2);
    SetValue($vid, $current->visibility);
    $vid = CreateVariableByName($_IPS['SELF'], 'Ozonwert', 1);
    SetValue($vid, $current->ozone);
    $vid = CreateVariableByName($_IPS['SELF'], 'Zeichen', 3);
    SetValue($vid, GetSign($current->icon));
}

// Extrahiere die Tageswerte und baue HTML Box zusammen
function SetDailyWeather($days)
{
    global $TRANS, $HTML;
    // Json Daten speichern
    $vid = CreateVariableByName($_IPS['SELF'], 'Forecast', 3);
    SetValue($vid, json_encode($days));
    // Icon holen
    $vid = CreateVariableByName($_IPS['SELF'], 'Icon', 3);
    $ico = GetValue($vid);
    $url = GetIcon($ico, 'icon01');
    // Text holen
    $vid = CreateVariableByName($_IPS['SELF'], 'Zusammenfassung', 3);
    $txt = GetValue($vid);
    // Temperatur
    $vid = CreateVariableByName($_IPS['SELF'], 'Temperatur', 2);
    if ($HTML['temp'] >= __IPS_MIN_ID) {
        $vid = $HTML['temp'];
    }
    $tmp = GetValue($vid);
    $tmp = intval(round($tmp, 0)) + 0;
    // Sun
    $snr = $HTML['sunrise'];
    $sns = $HTML['sunset'];
    // Niederschlagswahrscheinlichkeit (<i class="fa-light fa-umbrella"></i>&nbsp;)
    $fall = '<span class="txt fall">{{fall}} Regen</span>';
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlagswahrscheinlichkeit', 2);
    if ($HTML['chance'] >= __IPS_MIN_ID) {
        $vid = $HTML['chance'];
    }
    $value = GetValueFormatted($vid);
    $fall = str_replace('{{fall}}', $value, $fall);
    // Wind (<i class="fa-light fa-wind"></i>&nbsp;)
    $wind = '<span class="txt wind">{{wind}}</span>';
    $vid = CreateVariableByName($_IPS['SELF'], 'Windgeschwindigkeit', 2);
    if ($HTML['wind'] >= __IPS_MIN_ID) {
        $vid = $HTML['wind'];
    }
    $value = GetValueFormatted($vid);
    $vid = CreateVariableByName($_IPS['SELF'], 'Windrichtung', 2);
    if ($HTML['direction'] >= __IPS_MIN_ID) {
        $vid = $HTML['direction'];
    }
    $value = $value . ' ' . GetValueFormatted($vid);
    $wind = str_replace('{{wind}}', $value, $wind);
    // Luftfeuchtigkeit (<i class="fa-light fa-raindrops"></i>&nbsp;)
    $humi = '<span class="txt humi">{{humi}} Luftfeuchte</span>';
    $vid = CreateVariableByName($_IPS['SELF'], 'Luftfeuchtigkeit', 2);
    if ($HTML['humidity'] >= __IPS_MIN_ID) {
        $vid = $HTML['humidity'];
    }
    $value = GetValueFormatted($vid);
    $humi = str_replace('{{humi}}', $value, $humi);
    // Niederschlag/h (<i class="fa-light fa-raindrops"></i>&nbsp;)
    $rain = '<span class="txt rain">{{rain}}/Tag</span>';
    $vid = CreateVariableByName($_IPS['SELF'], 'Niederschlag/h', 2);
    if ($HTML['rain'] >= __IPS_MIN_ID) {
        $vid = $HTML['rain'];
    }
    $value = GetValueFormatted($vid);
    $rain = str_replace('{{rain}}', $value, $rain);
    // HTML WebFront
    if ($HTML['webfront']) {
        $htmlWF = __TILE_VISU_SCRIPT;
        $htmlWF .= '<style type="text/css">';
        $htmlWF .= '.wdiv { height:220px; display:flex;}';
        $htmlWF .= '.wbox { display:inline-block; position:relative; border:1px solid rgba(255, 255, 255, 0.1); height:220px; margin: 0px 10px 0px 0px; color: rgb(255, 255, 255); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%);}';
        $htmlWF .= '.wday { position:absolute; top:10px; left:10px; font-size:20px; font-weight:bold; color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wdeb { position:absolute; top:0px; right:5px; font-size:72px; font-weight:bold; overflow:hidden;}';
        $htmlWF .= '.wdes { position:absolute; top:35px; left:10px; right: 100px; font-size:12px;  color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wicb { position:absolute; bottom:0px; overflow:hidden;}';
        $htmlWF .= '.wics { position:absolute; width:115px; top:45px; text-align:center; overflow:hidden;}';
        $htmlWF .= '.wdec { position:absolute; width:115px; bottom:35px; text-align:center; font-size:48px; font-weight:bold; overflow:hidden;}';
        $htmlWF .= '.wtec { position:absolute; width:110px; bottom:5px; text-align:center; text-overflow: ellipsis; font-size:10px;  color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wder { position:absolute; bottom:42px; right:5px; text-align:right; font-size:16px; color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wsgl { position:absolute; bottom:5px; left:10px; text-align:left; font-size:12px; color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wssr { position:absolute; bottom:5px; right:10px; text-align:right; font-size:12px; color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.warw { position:absolute; width:25px; left:10px; bottom:70px; text-align:center; font-size:48px; font-weight:bold; overflow:hidden; background-repeat:no-repeat; background-image: url(data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTYuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgd2lkdGg9IjMycHgiIGhlaWdodD0iMzJweCIgdmlld0JveD0iMCAwIDQ2LjAyIDQ2LjAyIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA0Ni4wMiA0Ni4wMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0xNC43NTcsNDYuMDJjLTEuNDEyLDAtMi44MjUtMC41MjEtMy45MjktMS41NjljLTIuMjgyLTIuMTctMi4zNzMtNS43OC0wLjIwNC04LjA2M2wxMi43NTgtMTMuNDE4TDEwLjYzNyw5LjY0NSAgICBDOC40Niw3LjM3LDguNTQsMy43NiwxMC44MTYsMS41ODJjMi4yNzctMi4xNzgsNS44ODYtMi4wOTcsOC4wNjMsMC4xNzlsMTYuNTA1LDE3LjI1M2MyLjEwNCwyLjIsMi4xMDgsNS42NjUsMC4wMTMsNy44NzIgICAgTDE4Ljg5Myw0NC4yNDdDMTcuNzcsNDUuNDI0LDE2LjI2Nyw0Ni4wMiwxNC43NTcsNDYuMDJ6IiBmaWxsPSIjRkZGRkZGIi8+Cgk8L2c+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPC9zdmc+Cg==)}';
        $htmlWF .= '.wtew { position:absolute; width:50px; bottom:5px; text-align:center; text-overflow: ellipsis; font-size:12px;  color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '</style>';
        // Aktueller Daten
        $htmlWF .= '<div class="wdiv">';
        $htmlWF .= '<div class="wbox" style="width:225px;">';
        $htmlWF .= '<div class="wday">Aktuell</div>';
        $htmlWF .= '<div class="wdeb">' . $tmp . '°</div>';
        $htmlWF .= '<div class="wdes">' . $txt . '</div>';
        $htmlWF .= '<div class="wicb"><img style="width:220px; height:140px;" src="' . $url . '" /></div>';
        $htmlWF .= '<div class="wsgl">&uarr;&nbsp;' . $snr . '</div>';
        $htmlWF .= '<div class="wssr">' . $sns . '&nbsp;&darr;</div>';
        $htmlWF .= '</div>';
        // Forcast (next 3 Days)
        for ($i = 1; $i < 4; $i++) {
            $day = $days[$i]->time;
            $sum = strtr($days[$i]->summary, $TRANS);
            $thi = $days[$i]->temperatureHigh;
            $tlo = $days[$i]->temperatureLow;
            $wdy = date('D', intval($day));
            $wdy = strtr($wdy, $TRANS);
            $ico = GetIcon($days[$i]->icon, 'icon03');
            $htmlWF .= '<div class="wbox" style="width:115px;">';
            $htmlWF .= '<div class="wday">' . $wdy . '</div>';
            $htmlWF .= '<div class="wics"><img style="width:125px; height:74px;" src="' . $ico . '" /></div>';
            $htmlWF .= '<div class="wdec">' . (intval(round($thi, 0)) + 0) . '°</div>';
            $htmlWF .= '<div class="wder">' . (intval(round($tlo, 0)) + 0) . '°</div>';
            $htmlWF .= '<div class="wtec">' . $txt . '</div>';
            $htmlWF .= '</div>';
        }
        // Box ready
        $htmlWF .= '</div>';
        // Days speichern
        $vid = CreateVariableByName($_IPS['SELF'], '3-Tage-Wetter', 3);
        SetValue($vid, $htmlWF);
    }
    // HTML Tile Visu
    if ($HTML['tilevisu']) {
        $htmlTV = __TILE_VISU_SCRIPT;
        $htmlTV .= '<style type="text/css">';
        $htmlTV .= '.cardS {display:block;}';
        $htmlTV .= '.cardM {display:none;}';
        $htmlTV .= '.cardL {display:none;}';
        $htmlTV .= '#grid {width:100%; height:100%; display:grid; justify-items:center;}';
        $htmlTV .= '#grid > div {justify-content:center; align-items:center; display:flex; width:100%;}';
        $htmlTV .= '.wdes {position:absolute; top:0px; right:40vw; font-size:7vw; width:25%; text-align: end; overflow:hidden;}';
        $htmlTV .= '.wdeg {position:absolute; top:0px; left:0px; font-size:25vw; line-height:1em; overflow:hidden;}';
        $htmlTV .= '.wico {width:100%; height:80vw; position:absolute; bottom:0px; background-image:url(' . $url . '); background-size:contain; background-repeat:no-repeat; background-position-x:center; background-position-y:bottom;}';
        $htmlTV .= '.wsgl {position:absolute; bottom:0px; left:0px; font-size:6vw; opacity: 75%;}';
        $htmlTV .= '.wssr {position:absolute; bottom:0px; right:0px; font-size:6vw; opacity: 75%;}';
        $htmlTV .= '.wfgd {position: absolute; bottom: 0; width: 100%; padding-top: 5px; display: grid; border-top: solid 2px #11A0F3; grid-template-rows: auto; grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr 1fr; margin: 0 auto; justify-content: center; text-align: center;}';
        $htmlTV .= '.wfgd > .day {font-size: 6vh;}';
        $htmlTV .= '.wfgd > .img {width: 48px; height: 48px; margin: auto; display: block; }';
        $htmlTV .= '.wfgd > .txt {color: white; background: #11A0F3; border-radius: 5px; margin: 0 4px; padding: 3px 0 0 0; font-size: small;}';
        $htmlTV .= '.wifo {position: absolute; top: 0; right: 0; display: grid; grid-template-rows: 1fr 1fr 1fr 1fr ; grid-template-columns: auto; margin: 0 auto; justify-content: center;}';
        $htmlTV .= '.wifo > .txt {font-size: 6vh; opacity: 75%;}';
        $htmlTV .= '.hidden {display:none;}';
        $htmlTV .= '@media (aspect-ratio >1.5) {';
        $htmlTV .= '  .cardS {display:none;}';
        $htmlTV .= '  .cardM {display:block;}';
        $htmlTV .= '  .cardL {display:none;}';
        $htmlTV .= '  .wdes {font-size:3vw;}';
        $htmlTV .= '  .wdeg {font-size:10vw;}';
        $htmlTV .= '  .wsgl, .wssr { font-size: 2.5vw; bottom: 57vh;}';
        $htmlTV .= '  .wssr {right: 40vw;}';
        $htmlTV .= '  .wico {top: 0px; height: 45vh; width: 60vw}';
        $htmlTV .= '}';
        $htmlTV .= '@media screen and (min-width:768px){';
        $htmlTV .= '  .cardS {display:block;}';
        $htmlTV .= '  .cardM {display:none;}';
        $htmlTV .= '  .cardL {display:none;}';
        $htmlTV .= '}';
        $htmlTV .= '</style>';
        // Aktueller Daten
        $htmlTV .= '<body>';
        $htmlTV .= '<!-- Small Cards -->';
        $htmlTV .= '<div class="cardS">';
        $htmlTV .= '    <div class="wbox">';
        $htmlTV .= '        <div class="wdes">' . $txt . '</div>';
        $htmlTV .= '        <div class="wdeg">' . $tmp . '°</div>';
        $htmlTV .= '        <div class="wico"></div>';
        $htmlTV .= '        <div class="wsgl">☀️&nbsp;' . $snr . '</div>';
        $htmlTV .= '        <div class="wssr">' . $sns . '&nbsp;🌓</div>';
        $htmlTV .= '    </div>';
        $htmlTV .= '</div>';
        $htmlTV .= '<!-- Medium Cards -->';
        $htmlTV .= '<div class="cardM">';
        $htmlTV .= '    <div class="wbox">';
        $htmlTV .= '        <div class="wdes">' . $txt . '</div>';
        $htmlTV .= '        <div class="wico"></div>';
        $htmlTV .= '        <div class="wdeg">' . $tmp . '°</div>';
        $htmlTV .= '        <div class="wsgl">☀️&nbsp;' . $snr . '</div>';
        $htmlTV .= '        <div class="wssr">' . $sns . '&nbsp;🌓</div>';
        $htmlTV .= '        <div class="wifo">';
        $htmlTV .= $fall;
        $htmlTV .= $humi;
        $htmlTV .= $wind;
        $htmlTV .= $rain;
        $htmlTV .= '        </div>';
        $htmlTV .= '        <div class="wfgd">';
        // 7 days forecast
        for ($i = 1; $i < 8; $i++) {
            $day = $days[$i]->temperatureHighTime;
            $wd = date('D', intval($day));
            $wd = strtr($wd, $TRANS);
            $htmlTV .= '        <span class="day">' . strtoupper($wd) . '</span>';
        }
        // Icons
        for ($i = 1; $i < 8; $i++) {
            $ico = $days[$i]->icon;
            $htmlTV .= '        <img class="img" src="' . GetIcon($ico, 'icon07') . '" />';
        }
        // Temp
        for ($i = 1; $i < 8; $i++) {
            $th = $days[$i]->temperatureHigh;
            $htmlTV .= '        <span class="txt">' . (intval(round($th, 0)) + 0) . '°</span>';
        }
        $htmlTV .= '        </div>';
        $htmlTV .= '    </div>';
        $htmlTV .= '</div>';
        $htmlTV .= '<!-- Large Cards -->';
        $htmlTV .= '<div class="cardL">';
        $htmlTV .= '</div>';
        $htmlTV .= '</body>';
        // Day speichern
        $vid = CreateVariableByName($_IPS['SELF'], 'Aktuelles-Wetter', 3);
        SetValue($vid, $htmlTV);
    }
}

// Extrahiere die Stundenwerte und baue HTML Box zusammen
function SetHourlyWeather($hourly)
{
    global $TRANS, $HTML;
    // HTML WebFront
    if ($HTML['webfront']) {
        // General CSS
        $htmlWF = '';
        $htmlWF .= '<style type="text/css">';
        $htmlWF .= '.wdiv { height:180px;}';
        $htmlWF .= '.wbox { display:inline-block; position:relative; border:1px solid rgba(255, 255, 255, 0.1); height:180px; margin: 0px 10px 0px 0px; color: rgb(255, 255, 255); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%);}';
        $htmlWF .= '.wday { position:absolute; top:10px; left:10px; font-size:18px; font-weight:bold; color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wics { position:absolute; width:109px; top:35px; text-align:center; overflow:hidden;}';
        $htmlWF .= '.wdec { position:absolute; width:100px; bottom:35px; text-align:center; font-size:36px; font-weight:bold; overflow:hidden;}';
        $htmlWF .= '.wtec { position:absolute; width:109px; bottom:5px; text-align:center; text-overflow: ellipsis; font-size:10px;  color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wder { position:absolute; bottom:42px; right:5px; text-align:right; font-size:14px; color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '</style>';
        $htmlWF .= '<div class="wdiv">';
        // nächsten 24h reichen!
        for ($i = 0; $i < 24; $i++) {
            $time = $hourly[$i]->time;
            $text = strtr($hourly[$i]->summary, $TRANS);
            $temp = $hourly[$i]->temperature;
            $rain = $hourly[$i]->precipProbability;
            $hour = date('H:i', intval($time));
            $icon = GetIcon($hourly[$i]->icon, 'icon24');
            if ((($i + 1) % 8) == 0) {
                $htmlWF .= '<div class="wbox" style="width:109px; margin-right:0px; margin-bottom:5px;">';
            }
            else {
                $htmlWF .= '<div class="wbox" style="width:109px; float:left; margin-bottom:5px; ">';
            }
            $htmlWF .= '<div class="wday">' . $hour . '</div>';
            $htmlWF .= '<div class="wics"><img style="width:75px;" src="' . $icon . '"></img></div>';
            $htmlWF .= '<div class="wdec">' . (intval(round($temp, 0)) + 0) . '°</div>';
            $htmlWF .= '<div class="wder">' . number_format($rain * 100) . '%</div>';
            $htmlWF .= '<div class="wtec">' . $text . '</div>';
            $htmlWF .= '</div>';
        }
        $htmlWF .= '</div>';
        // Hours speichern
        $vid = CreateVariableByName($_IPS['SELF'], '24-Stunden-Wetter', 3);
        SetValue($vid, $htmlWF);
    }
}

// Gibt für den übergebenen (engl.) Type die deutsche Niederschlagsart zurück.
function GetPrecipitation($type = null)
{
    if ($type == 'rain') return 'Regen';
    if ($type == 'snow') return 'Schnee';
    if ($type == 'sleet') return 'Schneeregen';
    return 'keiner';
}

// Erstellt aus dem Icon-String eine Image-URL
function GetIcon($ico, $type)
{
    global $ICONS, $HTML;

    // Ist es Tag oder Nacht?
    $now = date('H:i', time());
    $snr = $HTML['sunrise'];
    $sns = $HTML['sunset'];
    $day = (($now > $snr) && ($now < $sns)) ? true : false;
    // Tag oder Nacht Icon
    $time = 'night';
    if ($day) $time = 'day';
    // Basis Name ermitteln
    $icon = explode('-', $ico);
    // Woher Bilder nehmen?
    if ($HTML[$type]) {
        // Url ermitteln
        $found = false;
        foreach ($ICONS[$time] as $name => $url) {
            if ($icon[0] == $name) {
                $found = true;
                return $url;
            }
        }
        if ($found == false) {
            IPS_LogMessage('WEATHER', 'Forecast Icon: ' . $ico);
        }
        return $ICONS['unknown']['icon'];
    }
    else {
        return $HTML['ibase'] . $ico . $HTML['iext'];
    }
}

// Erstellt aus dem Icon-String eine Font Symbol
function GetSign($ico)
{
    global $ICONS;
    // Basis Name ermitteln
    $icon = explode('-', $ico);
    // Zeiichen ermitteln
    $found = false;
    foreach ($ICONS['sign'] as $name => $sign) {
        if ($icon[0] == $name) {
            $found = true;
            return $sign;
        }
    }
    if ($found == false) {
        IPS_LogMessage('WEATHER', 'Forecast Sign: ' . $ico);
    }
    // weather-sunny
    return 'E599';
}

################################################################################