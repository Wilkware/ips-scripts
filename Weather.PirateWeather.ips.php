<?php

declare(strict_types=1);

################################################################################
# Scriptbezeichnung: Weather.PirateWeather.ips.php
# Version: 3.0.20231106
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
# 06.11.2023 - Anpassung für Nutzung via Pitti's Skript-Bibliothek
#
# ----------------------------- Konfigruration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = false;
#
# Globale Variable __WWX als Array via define() in __autoload definiert!!!
$API = __WWX['PWN_TOKEN'];   // # API-Token (Key)
#
# Location Control ID (wenn 0 => müssen $LAT & $LON korrekt gesetzt werden)
$LCID = 0;
# Latitude/Längengrad
$LAT = 52.5208;
# Longitude/Breitengrad
$LON = 13.4094;
#
# Ortsinfos setzen
if ($LCID != 0) {
    $location = json_decode(IPS_GetProperty($LCID, 'Location'), true);
    $LAT = $location['latitude'];
    $LON = $location['longitude'];
}
#
# Settings für HTML-Boxen;
$HTML = [
    'temp'      => 0,       // ID einer eigenen Temperaturvariable, z.B. von Wetterstation
    'sunrise'   => date_sunrise(time(), SUNFUNCS_RET_STRING, $LAT, $LON, 90, 1),  // Uhrzeit 'hh:mm' für Sonnenaufgang, oder via GetValue() vom Location Modul Variable => date('H:i', Getvalue(12345));
    'sunset'    => date_sunset(time(), SUNFUNCS_RET_STRING, $LAT, $LON, 90, 1),   // Uhrzeit 'hh:mm' für Sonnenuntergang, oder via GetValue() vom Location Modul Variable => date('H:i', Getvalue(12345));
    'webfront'  => true,    // true = Support für WebFront ('3-Tage-Wetter' & '24-Stunden--Wetter'), IPS <= v6.4
    'tilevisu'  => true,    // true = Support für Tile Visu ('Aktuelles-Wetter'). IPS >= v7.0
    'icons'     => false,   // true = Nutzung eigener Icons (siehe Array $ICONS) oder nachfolgende URL hinterlegen
    'ibase'     => 'https://basmilius.github.io/weather-icons/production/fill/all/', // URL Base für Online-Icons
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
elseif($_IPS['SENDER'] == 'TimerEvent') {
    //  Daten abholen
    $url = "https://api.pirateweather.net/forecast/$API/$LAT,$LON?exclude=minutely&lang=de&units=ca";
    if($DEBUG) EchoDebug('WEATHER', $url);
    $json = @file_get_contents($url);
    if($DEBUG) EchoDebug('WEATHER', $json);
    // Handle the error
    if ($json === false) {
        $error = error_get_last();
        IPS_LogMessage('WEATHER', print_r($error, true));
        exit();
    }
    $data = json_decode($json);
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
    $vid = CreateVariableByName($_IPS['SELF'], 'Uhrzeit', 1);
    SetValue($vid, $current->time);
    $vid = CreateVariableByName($_IPS['SELF'], 'Zusammenfassung', 3);
    SetValue($vid, $current->summary);
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
    $url = GetIcon($ico);
    // Text holen
    $vid = CreateVariableByName($_IPS['SELF'], 'Zusammenfassung', 3);
    $txt = strtr(GetValue($vid), $TRANS);
    // Temperatur
    $vid = CreateVariableByName($_IPS['SELF'], 'Temperatur', 2);
    if ($HTML['temp'] != 0) {
        $vid = $HTML['temp'];
    }
    $tmp = GetValue($vid);
    // Sun
    $snr = $HTML['sunrise'];
    $sns = $HTML['sunset'];
    // HTML WebFront
    if ($HTML['webfront']) {
        $htmlWF = '';
        $htmlWF .= '<style type="text/css">';
        $htmlWF .= '.wdiv { height:220px; display:flex;}';
        $htmlWF .= '.wbox { display:inline-block; position:relative; border:1px solid rgba(255, 255, 255, 0.1); height:220px; margin: 0px 10px 0px 0px; color: rgb(255, 255, 255); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%);}';
        $htmlWF .= '.wday { position:absolute; top:10px; left:10px; font-size:20px; font-weight:bold; color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wdeb { position:absolute; top:0px; right:5px; font-size:72px; font-weight:bold; overflow:hidden;}';
        $htmlWF .= '.wdes { position:absolute; top:35px; left:10px; right: 100px; font-size:12px;  color:rgba(255, 255, 255, 0.5); overflow:hidden;}';
        $htmlWF .= '.wicb { position:absolute; bottom:0px; overflow:hidden;}';
        $htmlWF .= '.wics { position:absolute; width:115px; top:25px; text-align:center; overflow:hidden;}';
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
        $htmlWF .= '<div class="wdeb">' . (round($tmp, 0) + 0) . '°</div>';
        $htmlWF .= '<div class="wdes">' . $txt . '</div>';
        $htmlWF .= '<div class="wicb"><img style="width:175px;" src="' . $url . '" /></div>';
        $htmlWF .= '<div class="wsgl">&uarr;&nbsp;' . $snr . '</div>';
        $htmlWF .= '<div class="wssr">' . $sns . '&nbsp;&darr;</div>';
        $htmlWF .= '</div>';
        // Forcast (next 3 Days)
        for($i = 1; $i < 4; $i++) {
            $day = $days[$i]->time;
            $sum = strtr($days[$i]->summary, $TRANS);
            $thi = $days[$i]->temperatureHigh;
            $tlo = $days[$i]->temperatureLow;
            $wdy = date('D', intval($day));
            $wdy = strtr($wdy, $TRANS);
            $ico = GetIcon($days[$i]->icon);
            $htmlWF .= '<div class="wbox" style="width:115px;">';
            $htmlWF .= '<div class="wday">' . $wdy . '</div>';
            $htmlWF .= '<div class="wics"><img src="' . $ico . '" /></div>';
            $htmlWF .= '<div class="wdec">' . (round($thi, 0) + 0) . '°</div>';
            $htmlWF .= '<div class="wder">' . (round($tlo, 0) + 0) . '°</div>';
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
        $htmlTV = '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $htmlTV .= '<style type="text/css">';
        $htmlTV .= 'body{margin:0px;}';
        $htmlTV .= '::-webkit-scrollbar{width:8px; }';
        $htmlTV .= '::-webkit-scrollbar-track{background:transparent; }';
        $htmlTV .= '::-webkit-scrollbar-thumb{background:transparent; border-radius:20px; }';
        $htmlTV .= '::-webkit-scrollbar-thumb:hover{background:#555; }';
        $htmlTV .= '.cardS{display:block; }';
        $htmlTV .= '.cardM{display:none; }';
        $htmlTV .= '.cardL{display:none; }';
        $htmlTV .= '#grid{width:100%; height:100%; display:grid; justify-items:center; }';
        $htmlTV .= '#grid > div{justify-content:center; align-items:center; display:flex; width:100%; }';
        $htmlTV .= '.wdes{position:absolute; top:0px; left:0px; font-size:7vw; width:50%; overflow:hidden; }';
        $htmlTV .= '.wdeg{position:absolute; top:0px; right:0px; font-size:25vw; line-height:1em; overflow:hidden; }';
        $htmlTV .= '.wico{width:100%; height:80vw; position:absolute; bottom:0px; background-image:url(' . $url . '); background-size:contain; background-repeat:no-repeat; background-position-x:center; background-position-y:bottom; }';
        $htmlTV .= '.wsgl{position:absolute; bottom:0px; left:0px; font-size:6vw; color:rgba(255,255,255,0.5); }';
        $htmlTV .= '.wssr{position:absolute; bottom:0px; right:0px; font-size:6vw; color:rgba(255,255,255,0.5); }';
        $htmlTV .= '.hidden{display:none; }';
        $htmlTV .= '@media (aspect-ratio >1.5) {';
        $htmlTV .= '  .cardS{display:none; }';
        $htmlTV .= '  .cardM{display:block; }';
        $htmlTV .= '  .cardL{display:none; }';
        $htmlTV .= '  .wdes{font-size:4vw; width:25%;}';
        $htmlTV .= '  .wdeg{font-size:12vw; }';
        $htmlTV .= '  .wico{width:50%; height:80vw;}';
        $htmlTV .= '}';
        $htmlTV .= '@media screen and (min-width:768px){';
        $htmlTV .= '  .cardS{display:block; }';
        $htmlTV .= '  .cardM{display:none; }';
        $htmlTV .= '  .cardL{display:none; }';
        $htmlTV .= '}';
        $htmlTV .= '</style>';
        // Aktueller Daten
        $htmlTV .= '<!-- Small Cards -->';
        $htmlTV .= '<div class="cardS">';
        $htmlTV .= '    <div class="wbox">';
        $htmlTV .= '        <div class="wdes">' . $txt . '</div>';
        $htmlTV .= '        <div class="wdeg">' . (round($tmp, 0) + 0) . '°</div>';
        $htmlTV .= '        <div class="wico"></div>';
        $htmlTV .= '        <div class="wsgl">☀️&nbsp;' . $snr . '</div>';
        $htmlTV .= '        <div class="wssr">' . $sns . '&nbsp;🌓</div>';
        $htmlTV .= '    </div>';
        $htmlTV .= '</div>';
        $htmlTV .= '<!-- Medium Cards -->';
        $htmlTV .= '<div class="cardM">';
        $htmlTV .= '        <div class="wbox">';
        $htmlTV .= '        <div class="wdes">' . $txt . '</div>';
        $htmlTV .= '        <div class="wdeg">' . (round($tmp, 0) + 0) . '°</div>';
        $htmlTV .= '        <div class="wico"></div>';
        $htmlTV .= '        <div class="wsgl">☀️&nbsp;' . $snr . '</div>';
        $htmlTV .= '        <div class="wssr">' . $sns . '&nbsp;🌓</div>';
        $htmlTV .= '    </div>';
        $htmlTV .= '</div>';
        $htmlTV .= '<!-- Large Cards -->';
        $htmlTV .= '<div class="cardL">';
        $htmlTV .= '</div>';
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
        for($i = 0; $i < 24; $i++) {
            $time = $hourly[$i]->time;
            $text = strtr($hourly[$i]->summary, $TRANS);
            $temp = $hourly[$i]->temperature;
            $rain = $hourly[$i]->precipProbability;
            $hour = date('H:i', intval($time));
            $icon = GetIcon($hourly[$i]->icon);
            if ((($i + 1) % 8) == 0) {
                $htmlWF .= '<div class="wbox" style="width:109px; margin-right:0px; margin-bottom:5px;">';
            }
            else {
                $htmlWF .= '<div class="wbox" style="width:109px; float:left; margin-bottom:5px; ">';
            }
            $htmlWF .= '<div class="wday">' . $hour . '</div>';
            $htmlWF .= '<div class="wics"><img style="width:75px;" src="' . $icon . '"></img></div>';
            $htmlWF .= '<div class="wdec">' . (round($temp, 0) + 0) . '°</div>';
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
function GetIcon($ico)
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
    if($HTML['icons']) {
        // Url ermitteln
        $found = false;
        foreach ($ICONS[$time] as $name => $url) {
            if ($icon[0] == $name) {
                $found = true;
                return $url;
            }
        }
        if($found == false) {
            IPS_LogMessage('WEATHER', 'Forecast Icon: ' . $ico);
        }
        return $ICONS['unknown']['icon'];
    }
    else {
        return $HTML['ibase'] . $ico . $HTML['iext'];
    }
}

################################################################################