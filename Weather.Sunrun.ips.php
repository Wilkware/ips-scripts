<?php

declare(strict_types=1);

################################################################################
# Script:  Weather.Sunrun.ips.php
# Version: 2.6.20241221
# Author:  Heiko Wilknitz (@Pitti)
#
# Berechung des aktuellen Sonnenstandes und stellt ihn graphisch dar.
#
# Die Funktion CalcSunPos basiert auf der Arbeit von Adrian(@aglutz)
# https://community.symcon.de/t/sonnenstand-berechnen-azimut-elevation/
#
# ------------------------------ Installation ----------------------------------
#
# Dieses Skript richtet automatisch alle nötigen Objekte bei manueller
# Ausführung ein. Eine weitere manuelle Ausführung setzt alle benötigten Objekte
# wieder auf den Ausgangszustand.
#
# - Neues Skript erstellen
# - Diesen PHP-Code hineinkopieren
# - Abschnitt 'Konfiguration' den eigenen Gegebenheiten anpassen
# - Skript Abspeichern
# - Skript Ausführen
# - Visualisierung per Link auf entstandene Variablen erstellen
#
# ------------------------------ Changelog -------------------------------------
#
# 27.22.2020 - Initialversion (v1.0)
# 25.01.2023 - Umbau auf eigene Lösung (v2.0)
# 25.01.2023 - Zeichenresourcen & Location dynamisiert (v2.1)
# 30.01.2023 - Elevation Option hinzugefügt (v2.2)
# 06.02.2023 - Option für Sonnenuntergang hinzugefügt (v2.3)
# 23.02.2023 - Location-Angaben werden jetzt als float angegeben (v2.4)
# 04.03.2024 - Kleine Anpassungen für Tile Visu (v2.5)
# 21.12.2024 - LocationControlID ausgelagert, Meta-Tag liefert jetzt IPS (v2.6)
#
# ------------------------------ Konfiguration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = false;
#
# Location Control ID (wenn 0 => müssen $LAT & $LON korrekt gesetzt werden)
$LCID = __WWX['IID_LC'];
# Latitude
$LAT = 52.5208;
# Longitude
$LON = 13.4094;
#
# HTML Zeichenoptionen
$DRAW = [
    'bg'            => '/user/sunrun/bg.png', // Hintergrundbild (Karte)
    'chart'         => '/user/sunrun/chart.svg', // Chart- bzw Diagrambild
    'size'          => 600, // Größe (Quadrad) des Containers (px)
    'line'          => 4, // Linienstärke
    'elevation'     => true, // Zeichne Elevation
    'dashed'        => true, // Zeichne gestrichelte Line nach Sonnenuntergang
    'dashedline'    => '[5,15]', // Muster gestrichelte Line
    'dashedstroke'  => '#F7f2E0', // Farbe gestrichelte Line
    'sunrise'       => '#FC9C54', // Linienfarbe Sonnenaufgang
    'sunset'        => '#FD5E53', // Linienfarbe Sonnenuntergang
    'sunpos'        => '#FFE373', // Linienfarbe Sonnengang (akt. Position)
    'sunradius'     => 16, //  Sonnenradius (px)
    'sunline'       => 2, //  Sonnenkreis Linienstärke (px)
    'sunrisefill'   => '#F1C40F', // Farbe aufgegangene Sonne (Innen)
    'sunrisestroke' => '#FC9C54', // Farbe aufgegangene Sonne (Kreis)
    'sunsetfill'    => '#57544B', // Farbe untergegangene Sonne (Innen)
    'sunsetstroke'  => '#F7f2E0', // Farbe untergegangene Sonne (Kreis)
];
#
# Update Interval
$UPDATE = 15;
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
    $vid = CreateVariableByName($_IPS['SELF'], 'Position', 3, 0, 'Sun', '~HTMLBox');
    $eid = CreateTimerByName($_IPS['SELF'], 'Update Timer', $UPDATE, true);
}
// TIMER EVENT
if ($_IPS['SENDER'] == 'TimerEvent') {
    // Set Postion Infos
    $latitude = $LAT;
    $longitude = $LON;
    if ($LCID >= __IPS_MIN_ID) {
        $location = json_decode(IPS_GetProperty($LCID, 'Location'), true);
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
    }
    // Calculate Sun Position Infos
    $info = DateSunInfo($latitude, $longitude);
    //var_dump($info);
    // Build HTML
    $html = BuildHtml($info, $DRAW);
    // Update Variable for WF reoload
    $vid = CreateVariableByName($_IPS['SELF'], 'Position', 3);
    SetValue($vid, $html);
}

#----------------------------------- Functions ---------------------------------

// calculate sun postion based on UTC!!!
function CalcSunPos($year, $month, $day, $hours, $minutes, $seconds, $latitude, $longitude)
{
    $pi = 3.14159265358979323846;
    $dpi = (2 * $pi);
    $rad = ($pi / 180);
    $earthMeanRadius = 6371.01;	// in km
    $astronomicalUnit = 149597890;	// in km

    // Calculate time of the day in UT decimal hours
    $decimalHours = floatval($hours) + (floatval($minutes) + floatval($seconds) / 60.0) / 60.0;

    // Calculate current Julian Day
    $iY = 2000 - $year;
    $iA = (14 - ($month)) / 12;
    $iM = ($month) + 12 * $iA - 3;
    $liAux3 = (153 * $iM + 2) / 5;
    $liAux4 = 365 * ($iY - $iA);
    $liAux5 = ($iY - $iA) / 4;
    $elapsedJulianDays = floatval(($day + $liAux3 + $liAux4 + $liAux5 + 59) + -0.5 + $decimalHours / 24.0);

    // Calculate ecliptic coordinates (ecliptic longitude and obliquity of the
    // ecliptic in radians but without limiting the angle to be less than 2*Pi
    // (i.e., the result may be greater than 2*Pi)
    $omega = 2.1429 - 0.0010394594 * $elapsedJulianDays;
    $meanLongitude = 4.8950630 + 0.017202791698 * $elapsedJulianDays; // Radians
    $meanAnomaly = 6.2400600 + 0.0172019699 * $elapsedJulianDays;
    $eclipticLongitude = $meanLongitude + 0.03341607 * sin($meanAnomaly) + 0.00034894 * sin(2 * $meanAnomaly) - 0.0001134 - 0.0000203 * sin($omega);
    $eclipticObliquity = 0.4090928 - 6.2140e-9 * $elapsedJulianDays + 0.0000396 * cos($omega);

    // Calculate celestial coordinates ( right ascension and declination ) in radians
    // but without limiting the angle to be less than 2*Pi (i.e., the result may be
    // greater than 2*Pi)
    $sinEclipticLongitude = sin($eclipticLongitude);
    $y1 = cos($eclipticObliquity) * $sinEclipticLongitude;
    $x1 = cos($eclipticLongitude);
    $rightAscension = atan2($y1, $x1);
    if ($rightAscension < 0.0) {
        $rightAscension = $rightAscension + $dpi;
    }
    $declination = asin(sin($eclipticObliquity) * $sinEclipticLongitude);

    // Calculate local coordinates ( azimuth and zenith angle ) in degrees
    $greenwichMeanSiderealTime = 6.6974243242 + 0.0657098283 * $elapsedJulianDays + $decimalHours;
    $localMeanSiderealTime = ($greenwichMeanSiderealTime * 15 + $longitude) * $rad;
    $dourAngle = $localMeanSiderealTime - $rightAscension;
    $latitudeInRadians = $latitude * $rad;
    $cosLatitude = cos($latitudeInRadians);
    $sinLatitude = sin($latitudeInRadians);
    $cosHourAngle = cos($dourAngle);
    $zenithAngle = (acos($cosLatitude * $cosHourAngle * cos($declination) + sin($declination) * $sinLatitude));
    $y = -sin($dourAngle);
    $x = tan($declination) * $cosLatitude - $sinLatitude * $cosHourAngle;
    $azimuth = atan2($y, $x);
    if ($azimuth < 0.0) {
        $azimuth = $azimuth + $dpi;
    }
    $azimuth = $azimuth / $rad;

    // Parallax Correction
    $parallax = ($earthMeanRadius / $astronomicalUnit) * sin($zenithAngle);
    $zenithAngle = ($zenithAngle + $parallax) / $rad;
    $elevation = 90 - $zenithAngle;

    // Result
    $pos = [
        'azimut'    => $azimuth,
        'elevation' => $elevation,
    ];
    return $pos;
}

function DateSunInfo($lat, $lon)
{
    // UTC Time
    $utc = gmdate('Y-m-d H:i:s');

    // sunset & sunrise for the day
    $date = date_parse($utc);
    $time = strtotime($utc);
    $sun = date_sun_info($time, $lat, $lon);

    // sunpos
    $info['sunpos'] = CalcSunPos($date['year'], $date['month'], $date['day'], $date['hour'], $date['minute'], $date['second'], $lat, $lon);

    // sunrise
    $rise = gmdate('Y-m-d H:i:s', $sun['sunrise']);
    $date = date_parse($rise);
    $info['sunrise'] = CalcSunPos($date['year'], $date['month'], $date['day'], $date['hour'], $date['minute'], $date['second'], $lat, $lon);

    // sunset
    $set = gmdate('Y-m-d H:i:s', $sun['sunset']);
    $date = date_parse($set);
    $info['sunset'] = CalcSunPos($date['year'], $date['month'], $date['day'], $date['hour'], $date['minute'], $date['second'], $lat, $lon);

    // result
    return $info;
}

function BuildHtml($pos, $draw)
{
    // Positionen vereinfachen und JS taublich machen
    $a_sr = number_format($pos['sunrise']['azimut']);
    $e_sr = number_format($pos['sunrise']['elevation']);
    $a_ss = number_format($pos['sunset']['azimut']);
    $e_ss = number_format($pos['sunset']['elevation']);
    $a_sp = number_format($pos['sunpos']['azimut']);
    $e_sp = number_format($pos['sunpos']['elevation']);
    // HTML
    $html = '';
    $html .= '<!DOCTYPE html>';
    $html .= '<html lang="de">';
    $html .= '<head>';
    //$html .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
    $html .= '<style>';
    $html .= 'body { margin: 0; overflow: hidden; }';
    $html .= '#sunmap {background: url("' . $draw['bg'] . '") no-repeat; background-size: cover; width: ' . $draw['size'] . 'px; height: ' . $draw['size'] . 'px; margin: auto;}';
    $html .= 'canvas {background: url("' . $draw['chart'] . '") no-repeat center; background-size: 100% 100%;}';
    $html .= '</style>';
    $html .= '</head>';
    $html .= '<body>';
    $html .= '<div id="sunmap"><canvas id="sunpos" width="' . $draw['size'] . '" height="' . $draw['size'] . '">Your browser does not support the HTML canvas tag.</canvas></div>';
    $html .= '<script>';
    $html .= 'var a_sr = ' . number_format($pos['sunrise']['azimut']) . ';';
    $html .= 'var e_sr = ' . number_format($pos['sunrise']['elevation']) . ';';
    $html .= 'var a_ss = ' . number_format($pos['sunset']['azimut']) . ';';
    $html .= 'var e_ss = ' . number_format($pos['sunset']['elevation']) . ';';
    $html .= 'var a_sp = ' . number_format($pos['sunpos']['azimut']) . ';';
    $html .= 'var e_sp = ' . number_format($pos['sunpos']['elevation']) . ';';
    $html .= 'var c = document.getElementById("sunpos");';
    $html .= 'var ctx = c.getContext("2d");';
    $html .= 'var x1 = c.width/2;';
    $html .= 'var y1 = c.height/2;';
    $html .= 'var ln = c.height/2.6;';
    if ($draw['elevation']) { // elevation
        $html .= 'if(e_sp < 0) e_sp = 0;';
        $html .= 'var le = ln-((ln*e_sp)/90);';
    } else {
        $html .= 'var le = ln;';
    }
    $html .= 'ctx.lineWidth = ' . $draw['line'] . ';';
    $html .= 'ctx.lineCap = "round";';
    // sunrise
    $html .= 'x2 = x1 + (Math.cos((Math.PI * (a_sr-90)) / 180.0) * ln);';
    $html .= 'y2 = y1 + (Math.sin((Math.PI * (a_sr-90)) / 180.0) * ln);';
    $html .= 'ctx.beginPath();';
    $html .= 'ctx.moveTo(x1, y1);';
    $html .= 'ctx.lineTo(x2, y2);';
    $html .= 'ctx.strokeStyle = "' . $draw['sunrise'] . '";';
    $html .= 'ctx.stroke();';
    // sunset
    $html .= 'x2 = x1 + (Math.cos((Math.PI * (a_ss-90)) / 180.0) * ln);';
    $html .= 'y2 = y1 + (Math.sin((Math.PI * (a_ss-90)) / 180.0) * ln);';
    $html .= 'ctx.beginPath();';
    $html .= 'ctx.moveTo(x1, y1);';
    $html .= 'ctx.lineTo(x2, y2);';
    $html .= 'ctx.strokeStyle = "' . $draw['sunset'] . '";';
    $html .= 'ctx.stroke();';
    // sunpos
    $html .= 'x2 = x1 + (Math.cos((Math.PI * (a_sp-90)) / 180.0) * le);';
    $html .= 'y2 = y1 + (Math.sin((Math.PI * (a_sp-90)) / 180.0) * le);';
    // Draw Line if sun is visible
    if (($a_sp <= $a_ss) && ($a_sp >= $a_sr)) {
        $html .= 'ctx.beginPath();';
        $html .= 'ctx.moveTo(x1, y1);';
        $html .= 'ctx.lineTo(x2, y2);';
        $html .= 'ctx.strokeStyle = "' . $draw['sunpos'] . '";';
        $html .= 'ctx.stroke();';
    } elseif ($draw['dashed']) {
        $html .= 'ctx.setLineDash(' . $draw['dashedline'] . ');';
        $html .= 'ctx.beginPath();';
        $html .= 'ctx.moveTo(x1, y1);';
        $html .= 'ctx.lineTo(x2, y2);';
        $html .= 'ctx.strokeStyle = "' . $draw['dashedstroke'] . '";';
        $html .= 'ctx.stroke();';
    }
    // sun
    $html .= 'ctx.setLineDash([]);';
    $html .= 'ctx.beginPath();';
    $html .= 'ctx.lineWidth = ' . $draw['sunline'] . ';';
    if (($a_sp <= $a_ss) && ($a_sp >= $a_sr)) {
        $html .= 'ctx.fillStyle = "' . $draw['sunrisefill'] . '";';
        $html .= 'ctx.strokeStyle = "' . $draw['sunrisestroke'] . '";';
    } else {
        $html .= 'ctx.fillStyle = "' . $draw['sunsetfill'] . '";';
        $html .= 'ctx.strokeStyle = "' . $draw['sunsetstroke'] . '";';
    }
    $html .= 'ctx.arc(x2, y2, ' . $draw['sunradius'] . ', 0, 2 * Math.PI);';
    $html .= 'ctx.fill();';
    $html .= 'ctx.stroke();';
    $html .= '</script></body>';
    $html .= '</html>';

    return $html;
}

################################################################################
