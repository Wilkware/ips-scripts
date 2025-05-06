<?php

declare(strict_types=1);

################################################################################
# Script:   Online.Travel.ips.php
# Version:  2.0.20231117
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
#
# ------------------------------ Konfiguration ---------------------------------
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
//    [$min - 3, 'Jetzt',        '', 0x0000FF],
    [$min - 2, '--',           '', -1],
    [$min - 1, '-',            '', -1],
    [$min,  '%d ' . $suffix,  '', 0x00FF00],
    [$max + 1, '+',            '', -1],
    [$max + 2, '++',           '', -1],
];
#
# Ankunft & Abfahrt Profil
$arrdep = [
    [0, 'Abfahrt',	'', 0x00FF00],
    [1, 'Ankunft',	'', 0xFFFF00],
    [2, 'Beides',	'', 0x0000FF],
];
#
# Stationen Profil
# ACHTUNG: Hier die eigenen Stationen eintragen bzw. Ändern und Erweitern!!
#   int => Stations ID
#   string => Anzeigename
#   hex => Farbcode
$stations = [
    [620887,  'Forstern',       '', 0xFF8000],
    [8001825, 'Erding',         '', 0xFF8000],
    [8003879, 'Markt Schwaben', '', 0xFF8000],
    [624904,  'Messestadt Ost', '', 0xFF8000],
];
#
# Suchen
$search = [
    [0, '>', '', -1],
    [1, 'Suchen', '', 0x008000],
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
    // Fahrplan (TileVisu)
    $vid = CreateVariableByName($_IPS['SELF'], 'Fahrplan (TileVisu)', 3, $pos++, 'Database', '~HTMLBox');
    IPS_SetPosition($vid, $pos++);
    // Fahrplan (Webfront)
    $vid = CreateVariableByName($_IPS['SELF'], 'Fahrplan (Webfront)', 3, $pos++, 'Database', '~HTMLBox');
    IPS_SetPosition($vid, $pos++);
}
// AKTION VIA WEBFRONT
elseif($_IPS['SENDER'] == 'WebFront') {
    $name = IPS_GetName($_IPS['VARIABLE']);
    switch ($name) {
        case 'Startzeit':
            switch($_IPS['VALUE']) {
                case $min - 2:
                    $_IPS['VALUE'] = GetValue($_IPS['VARIABLE']) - $jump;
                    if($_IPS['VALUE'] <= 0) {
                        $_IPS['VALUE'] = 0;
                        SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
                    }
                    break;
                case $min - 1:
                    $_IPS['VALUE'] = GetValue($_IPS['VARIABLE']) - $step;
                    if($_IPS['VALUE'] < 0) {
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
            $vid = CreateVariableByName($_IPS['SELF'], 'Fahrplan (Webfront)', 3);
            $html = RenderWebfront($table);
            SetValue($vid, $html);
            $vid = CreateVariableByName($_IPS['SELF'], 'Fahrplan (TileVisu)', 3);
            $html = RenderTileVisu($table);
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

    // Hier werden Verkehrsmittel ausgeschlossen
    /*
    $bahn->TypeBUS(false);
    $bahn->TypeTRAM(false);
    $bahn->TypeICE(false);
    $bahn->TypeIC(false);
    $bahn->TypeRE(false);
    $bahn->TypeSBAHN(false);
    $bahn->TypeUBAHN(false);
    $bahn->TypeFAEHRE(false);
     */

     // Abfahrt
    $departures = [];
    if ($mode == 0 || $mode == 2) {
        // Parameter 1 ist der Bahnhof oder die Haltestelle
        // (es muss kein Bahnhof sein, Bushaltestelle gehen auch)
        // Parameter 2 ist die Art der Tafel: "Abfahrt" oder "Ankunft"
        $bahn = new Bahn($station, 'abfahrt');
        // Hier werden Datum und Zeit gesetzt.
        // Werden die nicht gesetzt wird die/das aktuelle Zeit/Datum genommen
        $bahn->datum($dateTime->format('d.m.Y'));
        $bahn->zeit($dateTime->format('H:i'));
        // Jetzt das Ergebniss holen!
        $status = $bahn->fetch();
        if($status) {
            // Array mit den Informationen ausgeben:
            $departures = GetRowInfos($bahn, $time, $rows);
        }
    }
    // Ankunft
    $arrivals = [];
    if ($mode == 1 || $mode == 2) {
        // Parameter 1 ist der Bahnhof oder die Haltestelle
        // (es muss kein Bahnhof sein, Bushaltestelle gehen auch)
        // Parameter 2 ist die Art der Tafel: "Abfahrt" oder "Ankunft"
        $bahn = new Bahn($station, 'ankunft');
        // Hier werden Datum und Zeit gesetzt.
        // Werden die nicht gesetzt wird die/das aktuelle Zeit/Datum genommen
        $bahn->datum($dateTime->format('d.m.Y'));
        $bahn->zeit($dateTime->format('H:i'));
        // Jetzt das Ergebniss holen!
        $status = $bahn->fetch();
        if($status) {
            // Array mit den Informationen ausgeben:
            $arrivals = GetRowInfos($bahn, $time, $rows);
        }
    }
    // HTML zusammenbauen
    $table['Abfahrt'] = $departures;
    $table['Ankunft'] = $arrivals;
    return $table;
}

// $bahn - ergebnis der class.bahn.php
// $away - eine wegezeit in minuten zum bahnhof. oder =0
// $rows - maximale Anzahl von Zeilen pro Tabelle
function GetRowInfos($bahn, $away, $rows)
{
    $pos = 0;
    $lines = [];
    $journeys = count($bahn->timetable);
    for($i = 0; $i < $journeys; $i++) {
        $caller = $bahn->timetable[$i]['type'];
        $image = '<img src=/user/bahn/' . strtolower($caller) . '_24x24.gif>';
        $colour = 0; // 1 = yellow or 2 = red, 0 = white ist der normalfall
        $train = $bahn->timetable[$i]['train'];
        $time = $bahn->timetable[$i]['time'];
        // differenz zur aktuellen zeit ausrechnen.
        $timestampField = strtotime($bahn->timetable[$i]['time']);
        $timestampNow = time();
        $diff = $timestampField - $timestampNow;
        if ($diff > 0) {
            $difference = date('H:i', $diff - 1 * 60 * 60);
            if ($diff > $away * 60) {
                $colour = 0;
            }
            else {
                $colour = 2;
            }
        }
        else {
            // nicht mehr zu schaffen da zeit abgelaufen
            $difference = '--:--';
            if ($away != 0) {
                $color = 2;
            }
        }
        $direction = $bahn->timetable[$i]['route_ziel'];
        $track = isset($bahn->timetable[$i]['platform']) ? $bahn->timetable[$i]['platform'] : '';
        $info = $bahn->timetable[$i]['ris'];
        $lines[] = [$image, $train, $time, $colour, $difference, $direction, $track, $info];
        $pos++;
        // genug Zeilen?
        if($pos >= $rows)
            break;
    }
    return $lines;
}

// anzeige aufbereiten in eine html box
// $table - Ankunft und/oder Abfahrt infos
function RenderWebfront($table)
{
    $color = ['white', 'yellow', 'red'];
    // Anzeige aufbereiten
    $html = '';
    $html = $html . '<style type="text/css">';
    $html = $html . 'table  {border-collapse: collapse; font-size: 14px; width: 100%; }';
    $html = $html . 'td.fst {vertical-align: middle; text-align: center; width: 30px; padding: 5px; border-left: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
    $html = $html . 'td.mid {vertical-align: middle; text-align: left; padding: 5px; border-top: 1px solid rgba(255, 255, 255, 0.1); }';
    $html = $html . 'td.lst {vertical-align: middle; text-align: left; width: 350px; padding: 5px; border-right: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
    $html = $html . 'tr:last-child {border-bottom: 1px solid rgba(255, 255, 255, 0.2); }';
    $html = $html . 'tr:nth-child(even) {  background-color: rgba(0, 0, 0, 0.2); }';
    $html = $html . '.th { color: rgb(255, 255, 255); background-color: rgb(160, 160, 0); font-weight:bold; background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
    $html = $html . '</style>';

    foreach($table as $type => $lines) {
        if(!empty($lines)) {
            $html .= '<table>';
            $html .= '<tr><td class="fst th" style="width:30px;"></td><td class="mid th" style="width:80px;">Zug</td><td class="mid th" style="width:50px;">' . $type . '</td><td class="mid th" style="width:50px;">Diff.</td><td class="mid th" style="width:200px;">Richtung</td><td class="mid th" style="width:40px;">Gleis</td><td class="lst th">Aktuelles</td></tr>';
            foreach($lines as $line) {
                $html .= '<tr>';
                $html .= '<td class="fst">' . $line[0] . '</td>';
                $html .= '<td class="mid">' . $line[1] . '</td>';
                $html .= '<td class="mid">' . $line[2] . '</td>';
                $html .= '<td class="mid"><font color="' . $color[$line[3]] . '">' . $line[4] . '</font></td>';
                $html .= '<td class="mid">' . $line[5] . '</td>';
                $html .= '<td class="mid">' . $line[6] . '</td>';
                $html .= '<td class="lst">' . $line[7] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
            $html .= '<br />';
        }
    }
    // fertiges HTML zurueck
    return $html;
}

// anzeige aufbereiten in eine html box
// $table - Ankunft und/oder Abfahrt infos
// $light - Heller oder dunkler Theme
function RenderTileVisu($table)
{
    $color = ['#999A9C', '#FFC107', '#F35A2C'];
    $html = __TILE_VISU_SCRIPT;
    $html .= '<style>';
    $html .= '.cardL { display:block; }';
    $html .= '</style>';
    $html .= '<!-- Large Cards -->';
    $html .= '<div class="cardL">';

    foreach($table as $type => $lines) {
        if(!empty($lines)) {
            $html .= '    <table class="wwx">';
            $html .= '        <thead class="olive">';
            $html .= '            <tr><th></th><th>Zug</th><th>' . $type . '</th><th>Diff.</th><th>Richtung</th><th>Gleis</th><th>Aktuelles</th></tr>';
            $html .= '        </thead>';
            foreach($lines as $line) {
                $html .= '      <tr>';
                $html .= '          <td>' . $line[0] . '</td>';
                $html .= '          <td>' . $line[1] . '</td>';
                $html .= '          <td>' . $line[2] . '</td>';
                $html .= '          <td><font color="' . $color[$line[3]] . '">' . $line[4] . '</font></td>';
                $html .= '          <td>' . $line[5] . '</td>';
                $html .= '          <td>' . $line[6] . '</td>';
                $html .= '          <td>' . $line[7] . '</td>';
                $html .= '      </tr>';
            }
            $html .= '    </table>';
            $html .= '<br />';
        }
        $html .= '</div>';
    }
    // fertiges HTML zurueck
    return $html;
}

# --------------------------------- KLASSE BAHN --------------------------------
#
#	Author: Frederik Granna (sysrun)
#	Version 0.1
#
# ------------------------------------------------------------------------------
class Bahn
{
    public $_BASEURL = 'https://reiseauskunft.bahn.de/bin/bhftafel.exe/dn?maxJourneys=20';
    public $_PARAMS = [];
    public $_FETCHMETHOD;
    public $timetable = [];
    public $bahnhof = false;

    public function __construct($bahnhof = null, $type = 'abfahrt')
    {
       $type = strtolower($type);
       if(!$bahnhof)
          $bahnhof = '008003280';
        $this->_init($bahnhof);
        $this->fetchMethodCURL(true);
        $this->boardType($type);
    }

    public function TypeBUS($state = true)
    {
        $this->_PARAMS['GUIREQProduct_5'] = ($state) ? 'on' : false;
    }

    public function TypeICE($state = true)
    {
        $this->_PARAMS['GUIREQProduct_0'] = ($state) ? 'on' : false;
    }

    public function TypeIC($state = true)
    {
        $this->_PARAMS['GUIREQProduct_1'] = ($state) ? 'on' : false;
    }
    public function TypeRE($state = true)
    {
        $this->_PARAMS['GUIREQProduct_3'] = ($state) ? 'on' : false;
    } // NV genannt

    public function TypeSBAHN($state = true)
    {
        $this->_PARAMS['GUIREQProduct_4'] = ($state) ? 'on' : false;
    }
    public function TypeFAEHRE($state = true)
    {
        $this->_PARAMS['GUIREQProduct_6'] = ($state) ? 'on' : false;
    }   // UBAHN

    public function TypeTRAM($state = true)
    {
        $this->_PARAMS['GUIREQProduct_8'] = ($state) ? 'on' : false;
    }   // STrassenbahn

    public function TypeUBAHN($state = true)
    {
        $this->_PARAMS['GUIREQProduct_7'] = ($state) ? 'on' : false;
    }   // UBAHN

    public function boardType($type)
    {
        if($type == 'ankunft')
            $this->_PARAMS['boardType'] = 'arr';
        else
            $this->_PARAMS['boardType'] = 'dep';
    }

    public function datum($datum)
    {
        $this->_PARAMS['date'] = $datum;
    }

    public function zeit($zeit)
    {
        $this->_PARAMS['time'] = $zeit;
    }

    public function fetch($html = null)
    {
       if($html) {
          return $this->_parse($html);
       }elseif($this->_FETCHMETHOD == 'CURL') {
            return $this->_queryCurl();
        }
    }

    public function _queryCurl()
    {
        $this->buildQueryURL();
        $result = $this->_call();
        return $this->_parse($result);
    }

    public function buildQueryURL()
    {
       $fields_string = '';
        foreach($this->_PARAMS as $key=>$value) {
           if($value) {
                $fields_string .= $key . '=' . urlencode(strval($value)) . '&';
           }
        }
        rtrim($fields_string, '&');

        $this->_URL = $this->_BASEURL . $fields_string;
        return $this->_URL;
    }

    public function _parse($data)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($data);

        $select = $dom->getElementById('rplc0');
        if($select->tagName == 'select') {
            $options = $select->getElementsByTagName('option');
            foreach($options as $op) {
                echo utf8_decode($op->getAttribute('value') . '-' . $op->nodeValue) . 'n';
            }
            return false;
        }else {
           $this->bahnhof = utf8_decode($select->getAttribute('value'));
            $this->_process_dom($dom);
            return true;
        }
    }

    public function _process_dom($dom)
    {
        $test = $dom->getElementById('sqResult')->getElementsByTagName('tr');
        $data = [];
        foreach($test as $k=>$t) {
            $tds = $t->getElementsByTagName('td');
            foreach($tds as $td) {
               $dtype = $td->getAttribute('class');
                switch($dtype) {
                    case 'train':
                        if($a = $td->getElementsByTagName('a')->item(0)) {
                             $data[$k]['train'] = str_replace(' ', '', $a->nodeValue);
                            if($img = $a->getElementsByTagName('img')->item(0)) {
                                if (preg_match('%/([a-z_]*)_24%', $img->getAttribute('src'), $regs)) {
                                   switch($regs[1]) {
                                      case 'EC':
                                         $data[$k]['type'] = 'IC';
                                      break;
                                        default:
                                            $data[$k]['type'] = strtoupper($regs[1]);
                                        break;
                                    }
                                }
                            }
                        }
                    break;
                    case 'route':
                       if($span = @$td->getElementsByTagName('span')->item(0)) {
                          $data[$k]['route_ziel'] = $span->nodeValue;
                        }
                        preg_match_all('/(.*)s*([0-9:]{5})/', $td->nodeValue, $result, PREG_PATTERN_ORDER);
                        $tmp = [];
                        foreach($result[1] as $rk=>$rv) {
                            $tmp[$result[2][$rk]] = utf8_decode(trim(html_entity_decode(str_replace('n', '', $rv))));
                        }
                        $data[$k]['route'] = $tmp;
                    break;
                    case 'time':
                    case 'platform':
                    case 'ris':
                       $data[$k][$dtype] = $td->nodeValue;
                    break;
                }
            }
        }
        foreach($data as $d) {
            if(array_key_exists('train', $d)) {
               foreach($d as $dk=>$dv)
                  if(!is_array($dv))
                      $d[$dk] = ltrim(str_replace("\n", '', utf8_decode(trim(html_entity_decode($dv)))), '-');
                $d['route_start'] = $this->bahnhof;
                $this->timetable[] = $d;
             }
        }
    }

    public function fetchMethodCURL($state)
    {
        if($state) {
            $this->_FETCHMETHOD = 'CURL';
        }else {
            $this->_FETCHMETHOD = 'OTHER';
        }
    }

    public function _call()
    {
        $this->_CH = curl_init();
        curl_setopt($this->_CH, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_CH, CURLOPT_URL, $this->_URL);
        $result = curl_exec($this->_CH);
        curl_close($this->_CH);
        return $result;
    }

    public function _init($bahnhof)
    {
        $this->_PARAMS = [
            'country'                 => 'DEU',
            'rt'                      => 1,
            'GUIREQProduct_0'         => 'on',	// ICE
            'GUIREQProduct_1'         => 'on',	// Intercity- und Eurocityzüge
            'GUIREQProduct_2'         => 'on',	// Interregio- und Schnellzüge
            'GUIREQProduct_3'         => 'on',	// Nahverkehr, sonstige Züge
            'GUIREQProduct_4'         => 'on',	// S-Bahn
            'GUIREQProduct_5'         => 'on',	// BUS
            'GUIREQProduct_6'         => 'on',	// Schiffe
            'GUIREQProduct_7'         => 'on',	// U-Bahn
            'GUIREQProduct_8'         => 'on',	// Strassenbahn
            'REQ0JourneyStopsSID'     => '',
            'REQTrain_name'           => '',
            'REQTrain_name_filterSelf'=> '1',
            'advancedProductMode'     => '',
            'boardType'               => 'dep',			// dep oder arr
            'date'                    => date('d.m.Y'),
            'input'                   => $bahnhof,
            'start'                   => 'Suchen',
            'time'                    => date('H:i'),
        ];
    }
}
# ------------------------------------------------------------------------------