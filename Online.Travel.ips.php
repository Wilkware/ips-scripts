<?php

declare(strict_types=1);

################################################################################
# Script:   Online.Travel.ips.php
# Version:  3.0.20250506
# Author:   Heiko Wilknitz (@Pitti)
#           Original von sysrun (16.05.2010)
#
# Abfahrtstafeln von bahn.de
# ===========================
#
# Dieses Skript liest die An-und Abfahrtszeiten der Deutschen Bahn aus.
#
# Installation:
# -------------
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
#
# ---------------------------- Versionshistorie --------------------------------
#
# 16.03.2018 v1.2: Init
# 24.02.2020 v1.3: Fix Mode 2 (Umbruch)
# 17.11.2023 v2.0: Umbau für Tile Visu
# 06.05.2025 v3.0: Umbau auf Web API der bahn.de
#
# ------------------------------ Konfiguration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = false;
#
# Anzahl Zeilen in Fahrplantabelle (maximal 20 möglich)
$rows = 6;
#
# Schrittweite bzw. Offset der Startzeit konfigurierbar machen
# von Jetzt bis max. 12 Stunden in der Zukunft, sollte reichen
# Nur im Notfall anpassen ;-)
$min = 0;           // Jetzt
$max = 720;         // 12 Stunden
$step = 5;          // kleine Schrittweite
$jump = 60;         // große Schrittweite
$suffix = 'min';    // min = Minuten
#
# Startzeit Offset Profil
$time = [
    [$min - 2, '--',           '', -1],
    [$min - 1, '-',            '', -1],
    [$min,  '%d ' . $suffix,  '',  -1],
    [$max + 1, '+',            '', -1],
    [$max + 2, '++',           '', -1],
];
#
# Ankunft & Abfahrt Profil
$arrdep = [
    [0, 'Abfahrt',	'', -1],
    [1, 'Ankunft',	'', -1],
    [2, 'Beides',	'', -1],
];
#
# Stationen Profil
# ACHTUNG: Hier die eigenen Stationen eintragen bzw. Ändern und Erweitern!!
#   int => Stations ID (IBNR Onlinesuche https://www.michaeldittrich.de/ibnr/online.php)
#   string => Anzeigename
#   hex => Farbcode
$stations = [
    [620887,  'Forstern',       '', -1],
    [8001825, 'Erding',         '', -1],
    [8003879, 'Markt Schwaben', '', -1],
    [624904,  'Messestadt Ost', '', -1],
];
#
# Suchen
$search = [
    [0, '>', '', -1],
    [1, 'Suchen', '', -1],
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
    $pos = 0;
    // Startzeit
    $vpn = 'Travel.Time';
    CreateProfileInteger($vpn, 'Clock', '', '', $min, $max, 0, $time);
    $vid = CreateVariableByName($_IPS['SELF'], 'Startzeit', 1, $pos++, '', $vpn, $_IPS['SELF']);
    SetValue($vid, $min - 3);
    // Wegzeit bzw. Laufweg
    $vpn = 'Travel.AwayTime';
    CreateProfileInteger($vpn, 'Hourglass', '', ' min', 0, 60, 5);
    $vid = CreateVariableByName($_IPS['SELF'], 'Laufweg', 1, $pos++, '', $vpn, $_IPS['SELF']);
    SetValue($vid, 0);
    // Abfahrt und Ankunft
    $vpn = 'Travel.ArrivalsDeparture';
    CreateProfileInteger($vpn, 'Distance', '', '', 0, 0, 0, $arrdep);
    $vid = CreateVariableByName($_IPS['SELF'], 'Abfahrt und Ankunft', 1, $pos++, '', $vpn, $_IPS['SELF']);
    SetValue($vid, 0);
    // Stationen
    $vpn = 'Travel.Stations';
    CreateProfileInteger($vpn, 'Flag', '', '', 0, 0, 0, $stations);
    $vid = CreateVariableByName($_IPS['SELF'], 'Bahnhof/Haltestelle', 1, $pos++, '', $vpn, $_IPS['SELF']);
    SetValue($vid, $stations[0][0]);
    // Suchen
    $vpn = 'Travel.Search';
    CreateProfileInteger($vpn, 'Script', '', '', 0, 0, 0, $search);
    $vid = CreateVariableByName($_IPS['SELF'], 'Reise', 1, $pos++, '', $vpn, $_IPS['SELF']);
    SetValue($vid, 0);
    // Fahrplan
    $vid = CreateVariableByName($_IPS['SELF'], 'Fahrplan', 3, $pos++, 'Database', '~HTMLBox');
    IPS_SetPosition($vid, $pos++);
}
// AKTION VIA WEBFRONT
elseif ($_IPS['SENDER'] == 'WebFront') {
    $name = IPS_GetName($_IPS['VARIABLE']);
    switch ($name) {
        case 'Startzeit':
            switch ($_IPS['VALUE']) {
                case $min - 2:
                    $_IPS['VALUE'] = GetValue($_IPS['VARIABLE']) - $jump;
                    if ($_IPS['VALUE'] <= 0) {
                        $_IPS['VALUE'] = 0;
                        SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
                    }
                    break;
                case $min - 1:
                    $_IPS['VALUE'] = GetValue($_IPS['VARIABLE']) - $step;
                    if ($_IPS['VALUE'] < 0) {
                        $_IPS['VALUE'] = 0;
                        SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
                    }
                    break;
                case $min:
                    SetValue($_IPS['VARIABLE'], 0);
                    break;
                case $max + 1:
                    $_IPS['VALUE'] = min($max, max(0, GetValue($_IPS['VARIABLE'])) + $step);
                    break;
                case $max + 2:
                    $_IPS['VALUE'] = min($max, max(0, GetValue($_IPS['VARIABLE'])) + $jump);
                    break;
                default:
                    return;
                    break;
            }
            break;
        case 'Reise':
            SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
            // Daten holen
            $vid = CreateVariableByName($_IPS['SELF'], 'Startzeit', 1);
            $start = GetValue($vid);
            $vid = CreateVariableByName($_IPS['SELF'], 'Laufweg', 1);
            $time = GetValue($vid);
            $vid = CreateVariableByName($_IPS['SELF'], 'Abfahrt und Ankunft', 1);
            $mode = GetValue($vid);
            $vid = CreateVariableByName($_IPS['SELF'], 'Bahnhof/Haltestelle', 1);
            $station = GetValue($vid); //Formatted
            // Fahrplan rendern
            $table = GenerateTimetable($start, $time, $mode, $station, $rows);
            $vid = CreateVariableByName($_IPS['SELF'], 'Fahrplan', 3);
            $html = RenderVisu($table);
            SetValue($vid, $html);
            // Update Button zurückstellen
            $_IPS['VALUE'] = 0;
            break;
    }
    // Speichern
    SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
}

# -------------------------------- FUNKTIONEN ----------------------------------

// Daten von bahne.de holen
function GenerateTimetable($start, $time, $mode, $station, $rows)
{
    // Startzeit berechnen
    $dateTime = new DateTime();
    if ($start > 0) {
        $dateTime->modify('+' . $start . ' minutes');
    }

    // Abfahrt
    $departures = [];
    if ($mode == 0 || $mode == 2) {
        // Init Web API
        $bahn = new Bahn($station, 'abfahrt');
        // Jetzt das Ergebniss holen!
        $data = $bahn->Fetch($dateTime);
        if ($data !== false) {
            // Array mit den Informationen ausgeben:
            $departures = GetRowInfos($data, $time, $rows);
        }
    }
    // Ankunft
    $arrivals = [];
    if ($mode == 1 || $mode == 2) {
        // Init Web API
        $bahn = new Bahn($station, 'ankunft');
        // Jetzt das Ergebniss holen!
        $data = $bahn->Fetch($dateTime);
        if ($data !== false) {
            // Array mit den Informationen ausgeben:
            $arrivals = GetRowInfos($data, $time, $rows);
        }
    }
    // HTML zusammenbauen
    $table['Abfahrt'] = $departures;
    $table['Ankunft'] = $arrivals;
    return $table;
}

// $data - Ergebnis der Datenabfrage
// $away - eine Wegezeit in Minuten zum Bahnhof, oder 0
// $rows - maximale Anzahl von Zeilen pro Tabelle
function GetRowInfos($data, $away, $rows)
{
    $pos = 0;
    $lines = [];
    // Haben wir Einträge?
    if (isset($data['entries'])) {
        $timetable = $data['entries'];
        $journeys = count($timetable);
        for ($i = 0; $i < $journeys; $i++) {
            $caller = $timetable[$i]['verkehrmittel'];
            $image = Bahn::TYPE_ARR[$caller['produktGattung']][1];
            $train = $caller['name'];
            // Differenz zur aktuellen Zeit ausrechnen.
            $cold = 0; // 1 = yellow, 2 = red, 3 = green, 0 = white ist der Normalfall
            $tsNow = time();
            $tsField = strtotime($timetable[$i]['zeit']);
            $diff = $tsField - $tsNow;
            if ($diff > 0) {
                $difference = date('H:i', $diff - 1 * 60 * 60);
                if ($diff > $away * 60) {
                    $cold = 0;
                }
                else {
                    $cold = 2;
                }
            }
            else {
                // nicht mehr zu schaffen da zeit abgelaufen
                $difference = '--:--';
                if ($away != 0) {
                    $cold = 2;
                }
            }
            $time = date('H:i', $tsField);
            // Verspätung
            $cole = 3; // 2 = red, 3 = green ist der Normalfall
            $tsEst = $tsField; // nicht immer gesetzt
            if (isset($timetable[$i]['ezZeit'])) {
                $tsEst = strtotime($timetable[$i]['ezZeit']);
            }
            $diff = $tsEst - $tsField;
            if ($diff > 300) {
                $cole = 2;
            }
            $est = date('H:i', $tsEst);
            $direction = $timetable[$i]['terminus'];
            $track = isset($timetable[$i]['gleis']) ? $timetable[$i]['gleis'] : '';
            $info = '';
            if (isset($timetable[$i]['ueber'])) {
                $info = implode(', ', $timetable[$i]['ueber']);
            }
            //$bahn->timetable[$i]['ris'];
            $lines[] = [$image, $time, $est, $cole, $train, $direction, $difference, $cold, $info, $track];
            $pos++;
            // genug Zeilen?
            if ($pos >= $rows)
                break;
        }
    }
    return $lines;
}

// Anzeige aufbereiten in eine HtmlBox
// $table - Ankunft- und/oder Abfahrtszeiten
function RenderVisu($table)
{
    $color = ['#999A9C', '#FFC107', '#F35A2C', '#58A906'];
    $html = __TILE_VISU_SCRIPT;

    foreach ($table as $type => $lines) {
        $dir = ($type == 'Abfahrt') ? 'Nach' : 'Von';
        $nxt = ($type == 'Abfahrt') ? 'Nächste Halte' : 'Vorherige Halte';
        if (!empty($lines)) {
            $html .= '    <table class="wwx tr8">';
            $html .= '        <thead class="theme">';
            $html .= '            <tr><th></th><th>Zeit</th><th></th><th>Verkehr</th><th>' . $dir . '</th><th>Diff.</th><th>' . $nxt . '</th><th>Gleis</th></tr>';
            $html .= '        </thead>';
            foreach ($lines as $line) {
                $html .= '      <tr>';
                $html .= '          <td>' . $line[0] . '</td>';
                $html .= '          <td>' . $line[1] . '</td>';
                $html .= '          <td><font color="' . $color[$line[3]] . '">' . $line[2] . '</font></td>';
                $html .= '          <td>' . $line[4] . '</td>';
                $html .= '          <td>' . $line[5] . '</td>';
                $html .= '          <td><font color="' . $color[$line[7]] . '">' . $line[6] . '</font></td>';
                $html .= '          <td style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . $line[8] . '</td>';
                $html .= '          <td>' . $line[9] . '</td>';
                $html .= '      </tr>';
            }
            $html .= '    </table>';
        }
    }
    // fertiges HTML zurueck
    return $html;
}

# --------------------------------- KLASSE BAHN --------------------------------
class Bahn
{
    public const BASE_URL = 'https://www.bahn.de/web/api/reiseloesung/';
    public const DATE_FMT = 'Y-m-d';
    public const TIME_FMT = 'H:i:s';
    public const VIAS_PAR = '&mitVias=true&maxVias=8';
    public const TYPE_ARR = [
        // Hochgeschwindigkeitszüge
        'ICE' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#282D37" rx="4"/><path fill="#FFF" d="M11.101 26.83c.991 1.106 2.167 2.075 3.986 3.27l.57.36c.38.213.778.344 1.343.423A42 42 0 0 0 20 31q1.452.003 2.905-.104c.484-.061.848-.156 1.175-.303l.702-.4c2.037-1.331 3.057-2.18 4.113-3.358a89 89 0 0 1-.548 2.032c-.205.676-.652 1.376-1.182 2.18a4.34 4.34 0 0 1-2.196 1.708 1 1 0 0 1-.853 1.238L16 34a1 1 0 0 1-.966-1.26 4.4 4.4 0 0 1-2.02-1.474 11 11 0 0 1-1.264-2.145q-.155-.357-.649-2.292zM21.486 6c3.086 0 4.872 1.074 6.252 2.9a10.73 10.73 0 0 1 2.155 5.826l.016.373.082 3.266q.06 1.744-.182 3.916a35 35 0 0 1-1.203 1.71c-1.337 1.819-2.388 2.897-4.805 4.446-.394.252-.794.562-2.106.562h-3.42c-1.275 0-1.544-.222-2.076-.562-2.515-1.608-3.55-2.687-4.748-4.394q-1.2-1.707-1.262-1.77-.19-1.995-.19-3.202.003-1.207.093-3.972c.056-2.24.815-4.407 2.17-6.199 1.331-1.76 3.116-2.9 6.317-2.896zm-4.892 16.04a1 1 0 0 0-1.89.61l.97 3.009a1 1 0 0 0 1.89-.61l-.97-3.008zm6.7.027-.932 2.87a1 1 0 0 0 1.86.722l.933-2.87a1 1 0 0 0-1.861-.722M20 9q-2.589 0-4.272.665c-1.716.677-2.864 2.448-2.48 4.445.21 1.1.42 1.861.54 2.264a2.9 2.9 0 0 0 2.151 2.002l1.445.226c1.56.238 3.146.261 4.711.072l1.779-.263a2.9 2.9 0 0 0 2.344-2.038c.118-.391.271-.774.54-2.27.337-1.88-.72-3.675-2.29-4.355C23.091 9.15 21.598 9 20 9"/></g></svg>'],
        // Intercity- und Eurocityzüge
        'EC_IC' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#646973" rx="4"/><path fill="#FFF" d="M10.698 29.562q4.118 1.18 5.295 1.308c1.177.128 1.825.128 2.916.128h2.182c1.382 0 1.542 0 2.735-.102q1.194-.103 5.476-1.333a7.73 7.73 0 0 1-5.078 4.188 1.25 1.25 0 0 0-1.4-.988Q21.152 32.999 20 33q-1.154 0-2.823-.237c-.64-.092-1.224.299-1.401.988-2.075-.41-4.122-2.14-5.078-4.19zM22.5 6c3.975 0 7.365 3.34 7.496 7.264q.004 13.461-.031 13.872c-1.806.833-4.716 1.675-6.707 1.807-1.244.057-1.68.055-3.258.057-1.58.002-2.33.002-3.158-.05-2.196-.166-5.338-1.098-6.807-1.815Q9.965 26.883 10 13.5c0-3.946 3.311-7.364 7.262-7.496zm-7.296 18.505a1.125 1.125 0 0 0-.277 2.22l2.243.28a1.125 1.125 0 0 0 .278-2.22zm7.536.28a1.125 1.125 0 0 0 .277 2.22l2.243-.28a1.125 1.125 0 0 0-.277-2.22zM16.823 8.05a4 4 0 0 0-3.635 4.303l.676 8.443a2 2 0 0 0 1.841 1.835l2.696.206a21 21 0 0 0 3.198 0l2.696-.206a2 2 0 0 0 1.841-1.835l.676-8.443a4 4 0 0 0-3.635-4.303c-.368-.033-5.986-.033-6.354 0"/></g></svg>'],
        // Interregio- und Schnellzüge
        'IR' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#878C96" rx="4"/><path fill="#FFF" d="M22.61 6c3.994 0 7.257 3.108 7.386 7L30 28a6 6 0 0 1-5.75 5.995c-.052-.841-.744-1.295-1.428-1.197a19.8 19.8 0 0 1-5.645 0c-.684-.098-1.337.355-1.426 1.197a6 6 0 0 1-5.747-5.77L10 13.24c0-3.924 3.183-7.11 7.146-7.236zM15 27.882c-.685 0-1.125.538-1.125 1.118s.454 1.125 1.125 1.118l2 .007c.666 0 1.125-.545 1.125-1.125s-.444-1.125-1.125-1.125zm8 0A1.12 1.12 0 0 0 21.875 29c0 .58.467 1.118 1.125 1.125h2A1.13 1.13 0 0 0 26.125 29c0-.58-.43-1.118-1.125-1.125zM23.346 9h-6.69C15.19 9 14 10.208 14 11.696l.59 10.93C14.588 23.936 15.616 25 16.885 25h6.188c1.264 0 2.29-1.058 2.295-2.364L26 11.71C26.006 10.215 24.816 9 23.346 9"/></g></svg>'],
        // Straßenbahn
        'TRAM' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#A9455D" rx="4"/><path fill="#FFF" d="M22.528 6.15c.369 0 .733.072 1.073.21l.78.38a.85.85 0 0 1-.663 1.562l-.676-.33a1.2 1.2 0 0 0-.38-.114L20.85 7.85v2.65l2.786.014c.918.036 1.666.172 2.318.52s1.163.86 1.512 1.512c.348.652.484 1.4.52 2.318.014 1.252 0 11.583 0 13.772s-.172 2.666-.52 3.318a3.64 3.64 0 0 1-1.512 1.512c-.652.348-1.458.535-2.81.532-1.351-.002-4.86.003-6.288 0-1.43-.002-2.158-.184-2.81-.532a3.64 3.64 0 0 1-1.512-1.512c-.348-.652-.484-1.4-.52-2.318-.037-.917-.037-13.855 0-14.772.036-.918.172-1.666.52-2.318a3.64 3.64 0 0 1 1.512-1.512c.652-.348 1.4-.484 2.318-.52l2.786-.014V7.85h-1.678a1.2 1.2 0 0 0-.392.069l-.7.341a.85.85 0 0 1-.853-1.467l.67-.342c.33-.165.803-.301 1.275-.301zm-7.675 22.7c-.649 0-1.15.557-1.15 1.15s.478 1.143 1.15 1.15h1.997c.696 0 1.15-.557 1.15-1.15a1.14 1.14 0 0 0-1.15-1.15zm8.297.007C22.456 28.85 22 29.407 22 30s.48 1.15 1.144 1.15h2.006c.668 0 1.15-.557 1.15-1.15s-.471-1.15-1.15-1.15zM26.3 16.2H13.7v9.034c0 .488.47.942.93.998a77 77 0 0 0 10.74 0 1 1 0 0 0 .924-.887zM17 12.65a.84.84 0 0 0-.85.85c0 .433.348.85.85.85h6a.85.85 0 0 0 0-1.7z"/></g></svg>'],
        // Busse
        'BUS' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#814997" rx="4"/><path fill="#FFF" d="M13.585 7.152h12.83c1.176.016 1.802.152 2.451.5.637.34 1.142.845 1.483 1.482.347.65.483 1.275.5 2.451l.001 16.492c0 .738-.077 1.139-.3 1.556q-.211.393-.549.67L30 32a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-1.15H14V32a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1l-.001-1.696a2.2 2.2 0 0 1-.548-.67c-.201-.377-.284-.739-.299-1.345V11.585c.016-1.176.152-1.802.5-2.451a3.58 3.58 0 0 1 1.482-1.483c.65-.347 1.275-.483 2.451-.5zM12.35 25.75a1.5 1.5 0 1 0 .001 3.001 1.5 1.5 0 0 0-.001-3.001m15.3 0a1.5 1.5 0 1 0 .001 3.001 1.5 1.5 0 0 0-.001-3.001M29.15 12h-18.3v11.043c3.15.493 6.382.707 9.15.707q4.575 0 9.15-.707zM33 13.165c.433 0 .85.351.843.85l.007 3c0 .47-.38.85-.85.85a.844.844 0 0 1-.844-.85l-.006-3c0-.47.38-.85.85-.85m-26 0c.433 0 .85.338.85.85v3a.85.85 0 0 1-1.7 0v-3c0-.47.38-.85.85-.85m8-5.015a.855.855 0 0 0-.85.85c0 .433.342.85.85.85l10.012-.007c.48.007.838-.41.838-.843s-.34-.85-.838-.843z"/></g></svg>'],
        // S-Bahnen
        'SBAHN' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#408335" rx="4"/><path fill="#FFF" d="M26.317 16.01c-1.65-1.84-4.133-3.458-7.046-3.458-1.648 0-2.59.909-2.59 1.973 0 4.477 10.77 1.33 10.77 8.688 0 2.971-2.784 6.43-7.472 6.43-2.613 0-5.633-1.22-7.28-2.75v-3.857c1.562 2.328 4.41 4.123 7.28 4.123 1.756 0 3.168-1.04 3.168-2.15 0-4.189-10.598-1.595-10.598-8.822 0-3.79 3.575-5.83 6.787-5.83 2.677 0 5.075.91 6.98 2.44v3.213z"/></g></svg>'],
        // U-Bahn
        'UBAHN' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#1455C0" rx="4"/><path fill="#FFF" d="M23.399 10.286V22.2c0 2.176-1.483 3.628-3.4 3.628-1.918 0-3.399-1.452-3.399-3.628V10.286h-4.856v12.358c0 5.173 3.872 7.07 8.255 7.07 4.382 0 8.257-1.893 8.257-7.07V10.286z"/></g></svg>'],
        // Schiffe
        'SCHIFF' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#309FD1" rx="4"/><path fill="#FFF" d="M30.41 28.464q.658.685.895.879c.56.46 1.221.807 1.693.807a.85.85 0 1 1 0 1.7q-1.563 0-3.248-1.613-1.727 1.613-3.248 1.613-1.52 0-3.252-1.614-1.716 1.617-3.254 1.614t-3.246-1.613q-1.704 1.614-3.25 1.613-1.546 0-3.25-1.614Q8.533 31.85 7 31.85a.85.85 0 0 1 0-1.7c.46 0 1.073-.295 1.696-.807q.237-.195.894-.878a.85.85 0 0 1 1.32 0q.657.683.894.878c.561.46 1.196.807 1.696.807s1.134-.346 1.695-.807q.238-.195.895-.879a.85.85 0 0 1 1.32 0q.657.685.895.879c.56.46 1.191.808 1.693.807s1.074-.296 1.697-.807q.237-.195.895-.88a.85.85 0 0 1 1.242-.083q.735.768.974.963c.558.457 1.2.808 1.696.807.496 0 1.07-.296 1.693-.807q.238-.195.895-.88a.85.85 0 0 1 1.32 0zM24 9.15a.85.85 0 0 1 .789.533l.626 2.467-12.621.007a.85.85 0 0 0 0 1.686l13.047.007.828 3.3H32c.482 0 .87.4.85.881-.138 3.219-1.633 5.697-2.338 6.703s-.802 1.096-1.205 1.547c-.819.914-1.72 1.57-2.862 1.57q-1.549 0-3.231-2.021-1.661 2.023-3.247 2.02-1.587-.003-3.239-2.02-1.61 2.02-3.227 2.02-1.62 0-3.258-2.019Q8.56 27.881 7 27.881a.86.86 0 0 1-.85-.85V17.35l17.457-.007a.85.85 0 0 0 0-1.686L6.15 15.65V10c0-.47.38-.85.85-.85zM20.25 20a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5m5 0a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5m-10 0a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5m-5 0a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5"/></g></svg>'],
        // Nahverkehr, sonstige Züge
        'REGIONAL' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#878C96" rx="4"/><path fill="#FFF" d="M22.61 6c3.994 0 7.257 3.108 7.386 7L30 28a6 6 0 0 1-5.75 5.995c-.052-.841-.744-1.295-1.428-1.197a19.8 19.8 0 0 1-5.645 0c-.684-.098-1.337.355-1.426 1.197a6 6 0 0 1-5.747-5.77L10 13.24c0-3.924 3.183-7.11 7.146-7.236zM15 27.882c-.685 0-1.125.538-1.125 1.118s.454 1.125 1.125 1.118l2 .007c.666 0 1.125-.545 1.125-1.125s-.444-1.125-1.125-1.125zm8 0A1.12 1.12 0 0 0 21.875 29c0 .58.467 1.118 1.125 1.125h2A1.13 1.13 0 0 0 26.125 29c0-.58-.43-1.118-1.125-1.125zM23.346 9h-6.69C15.19 9 14 10.208 14 11.696l.59 10.93C14.588 23.936 15.616 25 16.885 25h6.188c1.264 0 2.29-1.058 2.295-2.364L26 11.71C26.006 10.215 24.816 9 23.346 9"/></g></svg>'],
        // Anrufpflichtige Verkehre
        'ANRUFPFLICHTIG' => [true, '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><g fill="none" fill-rule="evenodd"><rect width="40" height="40" fill="#FFD800" rx="4"/><path fill="#282D37" d="M26.438 10.15a2.85 2.85 0 0 1 2.717 1.988L30.626 18h2.391a.94.94 0 0 1 .96.85l.007 1.116c0 .512-.373 1.034-.967 1.033h-1.19q.052.474-.065 1.753l-.342 3.558c-.112 1.148-.668 1.99-1.62 2.364v2.044c0 .446 0 1.282-1.282 1.282h-1.036c-1.283 0-1.282-.836-1.282-1.282v-1.644a70 70 0 0 1-12.4 0v1.644c0 .446 0 1.282-1.282 1.282h-1.036c-1.282 0-1.282-.836-1.282-1.282l-.001-2.013c-.897-.325-1.463-1.114-1.622-2.19l-.358-3.663q-.13-1.42-.077-1.853H6.967C6.414 21 6 20.519 6 19.967v-1c0-.553.414-.966.967-.966h2.406l1.424-5.691a2.86 2.86 0 0 1 2.765-2.159zm-9.555 12.857a1 1 0 0 0 0 1.986h6.234a1 1 0 0 0 0-1.986zm-6-1a1 1 0 0 0 0 1.986h2.234a1 1 0 0 0 0-1.986zm16 0a1 1 0 0 0 0 1.986h2.234a1 1 0 0 0 0-1.986zM13.562 11.85a1.15 1.15 0 0 0-1.08.756L11.124 18h17.748l-1.319-5.279a1.155 1.155 0 0 0-1.116-.871zm9.422-4.7c.47 0 .85.38.85.85 0 .433-.36.85-.834.85h-6.016A.85.85 0 1 1 17 7.15z"/></g></svg>'],
    ];

    private $station = null;
    private $direction = null;

    public function __construct($bahnhof = null, $richtung = 'abfahrt')
    {
        $this->station = $bahnhof;
        $dir = strtolower($richtung);
        if ($dir == 'abfahrt') $this->direction = 'abfahrten';
        if ($dir == 'ankunft') $this->direction = 'ankuenfte';
    }

    public function Fetch($dt)
    {
        // Safty check
        if (empty($this->station)) {
            return false;
        }
        // Build URL
        $url = self::BASE_URL . $this->direction;
        // Date
        $url .= '?datum=' . $dt->format(self::DATE_FMT);
        // Time
        $url .= '&zeit=' . $dt->format(self::TIME_FMT);
        // Station
        $url .= '&ortExtId=' . $this->station;
        // Vias?
        $url .= self::VIAS_PAR;
        // Mode of transport
        foreach (self::TYPE_ARR as $key => $value) {
            if ($value[0]) {
                $url .= '&verkehrsmittel[]=' . $key;
            }
        }
        EchoDebug('URL: ', $url);
        // Try to get Infos
        $ret = @Sys_GetURLContentEx($url, ['Timeout'=> 50000]);
        if ($ret === false) {
            return false;
        }
        return json_decode($ret, true);
    }
}
# ------------------------------------------------------------------------------