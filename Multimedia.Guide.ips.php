<?php

declare(strict_types=1);

################################################################################
# Script:   Multimedia.Guide.ips.php
# Version:  1.1.20230121
# Author:   Heiko Wilknitz (@Pitti)
#
# TV Guide via EPG
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
# 10.01.2023 - Initalversion (v1.0)
# 21.01.2023 - Variablen & Timer Setup hinzugefügt
#
# ------------------------------ Konfiguration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = false;
#
# Time-Zone-Offset
$TZO = 3600;
# Zeitbereich auf x Minuten runden
$MIN = 15;
# Sekunden sind im xmlTV immer Null
$FMT = 'YmdHi00';
# EPG source files
$EPG = [
    'horizon.tv',
    'hd-plus.de',
];
# Source file extension
$EXT = '.xml';
# Bash Script, welches täglich die xmltv-Dateien abholt
$BSH = '../guide.sh';
# Channel Configuration File
$CHN = 'webfront/user/guide/channels.json';
# Verzeichnis, wo die xmltv Dateien liegen
$XML = 'webfront/user/guide/xml/';
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
    $vid = CreateVariableByName($_IPS['SELF'], 'EPG', 3, 0, 'Database', '~HTMLBox');
    // xmltv Datenupdate (bash script execution)
    $midnight = mktime(5, 25, 0);
    $eid = CreateEventByName($_IPS['SELF'], 'XMLUpdate', $midnight);
    // Script Timer aller 15 min
    $eid = CreateTimerByName($_IPS['SELF'], 'EPGUpdate', $MIN, true);
}
// WEBFRONT
elseif ($_IPS['SENDER'] == 'WebFront') {
    // Benutzer hat etwas geändert!
}
// TIMER EVENT
elseif ($_IPS['SENDER'] == 'TimerEvent') {
    if (IPS_GetName($_IPS['EVENT']) == 'EPGUpdate') {
        $vid = CreateVariableByName($_IPS['SELF'], 'EPG', 3);
        UpdateGuide($vid);
    } else { // Update XML Timer
        ExecuteCommand($BSH);
    }
}
// AUFRUF WEBHOOK
elseif ($_IPS['SENDER'] == 'WebHook') {
    // ToDo
}

# ---------------------------- Functions ---------------------------------------

function UpdateGuide($vid)
{
    global $MIN, $FMT, $CHN, $EPG, $TZO, $XML, $EXT;
    // Timeslots
    $dtn = round(time() / ($MIN * 60)) * ($MIN * 60);
    $dts = $dtn - (1 * 3600); //  -1 hour
    $dte = $dtn + (2 * 3600); //  +2 hours
    // e.g. 20230110200000
    $tts = date($FMT, $dts);
    $tte = date($FMT, $dte);
    // Channel settings
    $dir = IPS_GetKernelDir();
    $prg = json_decode(file_get_contents($dir . $CHN), true);
    // Extract programm data
    foreach ($EPG as $src) {
        $xml = simplexml_load_file($dir . $XML . $src . $EXT);
        foreach ($xml->programme as $item) {
            $ps = date($FMT, strtotime(substr((string) $item['start'], 0, 14)) + $TZO);
            $pe = date($FMT, strtotime(substr((string) $item['stop'], 0, 14)) + $TZO);
            $ch = $item['channel']->__toString();
            if (isset($prg[$ch]) && $prg[$ch]['source'] == $src) {
                if (($ps >= $tts && $ps < $tte) || ($pe > $tts && $pe <= $tte) || ($ps < $tts && $pe > $tte)) {
                    $prg[$ch]['times'][] = [$ps, $pe, $item->title->__toString()];
                }
            }
        }
    }
    // Update Table
    SetValueString($vid, BuildHtml($dtn, $dts, $dte, $prg));
}

// Render Sendeterminliste
function BuildHtml($dtn, $dts, $dte, $prg)
{
    // Stylesheet
    $style = '';
    $style = $style . '<style type="text/css">';
    $style = $style . '#epg_table {table-layout: fixed;}';
    $style = $style . 'table th, table td {border: 1px solid rgba(255, 255, 255, 0.3); }';
    $style = $style . 'th img {max-height: 25px; max-width: 50px;}';
    $style = $style . 'span i {font-size: 10px; font-style: normal;}';
    $style = $style . '.thc {width: 50px;}';
    $style = $style . '.thr {height: 50px; text-align: center!important;}';
    $style = $style . '.dot {position: relative;}';
    $style = $style . '.dot:before {content: \'x\'; visibility: hidden;}';
    $style = $style . '.dot span {position: absolute;top: 8px; left: 5px; right: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}';
    $style = $style . '.now {background: rgba(255,255,255, 0.1);}';
    $style = $style . '</style>';
    // HTML zusammenbauen
    $html = $style;
    $html .= '<table id="epg_table" class="wwx">';
    $html .= '<thead class="blue">';
    $html .= '<tr>';
    $html .= '<th scope="col" class="thc">&nbsp;</th>';
    // Zeiten (Spaltenkopf - 1min = 1col)
    for ($half = $dts; $half < $dte; $half = $half + 1800) {
        $html .= '<th colspan="30">' . date('H:i', $half) . '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    // Zeiten pro Kanal
    foreach ($prg as $chn => $values) {
        // max cols per row
        $first = false;
        $cols = ($dtn - $dts) / 60;
        $html .= '<tr>';
        $html .= '<th scope="row" class="thr"><img src="' . $values['logo'] . '" title="' . $values['name'] . '"></th>';
        if (isset($values['times'])) {
            foreach ($values['times'] as $time) {
                $class = 'dot';
                $ts = strtotime($time[0]);
                $te = strtotime($time[1]);
                $tt = date('H:i', $ts) . ' - ' . date('H:i', $te);
                $tst = $tt . '&#10;' . $time[2];
                $tsn = '<i>' . $tt . '</i><br />' . $time[2];
                if (($ts <= $dtn) && ($dtn < $te)) {
                    $class = $class . ' now';
                }

                if ($ts <= $dts) { // start overlaped
                    if ($te > $dte) {
                        $te = $dte; // end also overlaped
                    }
                    $span = ($te - $dts) / 60;
                    $html .= '<td class="' . $class . '" colspan="' . $span . '"><span title="' . $tst . '">' . $tsn . '</span></td>';
                    $cols = $cols - $span;
                    $first = true;
                } elseif ($te > $dte) {   // end overlaped
                    if (!$first) {
                        $span = ($ts - $dts) / 60;
                        $html .= '<td class="' . $class . '" colspan="' . $span . '"><span> </span></td>';
                        $cols = $cols - $span;
                        $first = true;
                    }
                    $span = ($dte - $ts) / 60;
                    $html .= '<td class="' . $class . '" colspan="' . $span . '"><span title="' . $tst . '">' . $tsn . '</span></td>';
                    $cols = $cols - $span;
                } else { // in the middle
                    if (!$first) {
                        $span = ($ts - $dts) / 60;
                        $html .= '<td class="' . $class . '" colspan="' . $span . '"><span> </span></td>';
                        $cols = $cols - $span;
                        $first = true;
                    }
                    $span = ($te - $ts) / 60;
                    $html .= '<td class="' . $class . '" colspan="' . $span . '"><span title="' . $tst . '">' . $tsn . '</span></td>';
                    $cols = $cols - $span;
                }
            }
        }
        // No Times or open cols
        if ($cols > 0) {
            $html .= '<td class="dot" colspan="' . $cols . '"><span> </span></td>';
        }

        $html .= '</tr>';
    }
    /*    <tr>
            <th scope="row" class="tac">
                <img src="/user/guide/img/zdf.svg" title="ZDF">
            </th>
            <td class="' . $class . '" colspan="1"><span>Tagesschau um Acht&#10;Test</span></td>
            <td class="' . $class . '" colspan="20"><span>Tatort: Tot einer Unbekannten</span></td>
            <td class="' . $class . '" colspan="6"><span>Tagesthemen</span></td>
            <td class="dot now" colspan="21"><span>Purpurnen Flüsse</span></td>
        </tr>
     */
    $html .= '</tbody>';
    $html .= '</table>';
    // HTML
    return $html;
}

// Systemkommando aufrufen
function ExecuteCommand($cmd)
{
    $ret = exec("$cmd", $out, $rc);
    if ($ret === false) {
        IPS_LogMessage('GUIDE', 'Fehler beim Ausführen des XML Update Aufrufs!');
    }
}

################################################################################
