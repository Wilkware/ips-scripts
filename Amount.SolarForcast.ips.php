<?php

declare(strict_types=1);

################################################################################
# Script:   Amount.SolarForcast.ips.php
# Version:  2.2.20230326
# Author:   Heiko Wilknitz (@Pitti)
#
# Script zur Abholung und Aufbereitung der prognostizierten Solorproduktion
# von solarprognose.de.
#
# API
#   https://www.solarprognose.de/web/solarprediction/api/v1
#       ?access-token=ACCESS-TOKEN
#       &project=Hier Ihre Projekt-Website oder Ihre Kontakt-E-Mail
#       &item=ITEM
#       &id=ID
#       &type=hourly|daily
#       &_format=json|xml
#       &algorithm=mosmix|own-v1|clearsky
#       &day=DAY
#       &start_epoch_time=START_EPOCH_TIME&end_epoch_time=END_EPOCH_TIME
#       &start_day=START_DAY&end_day=END_DAY
#       &snomminixml=true # für snom VoIP Telefone
#
# ------------------------------ Installation ----------------------------------
#
# Dieses Skript richtet automatisch alle nötigen Objekte bei manueller
# Ausführung ein. Eine weitere manuelle Ausführung setzt alle benötigten Objekte
# wieder auf den Ausgangszustand.
#
# - Neues Skript erstellen
# - Diesen PHP-Code hineinkopieren
# - Abschnitt 'Konfiguration' mit eigenen Werten anpassen (__WWX austauschen)
# - Skript Abspeichern
# - Skript Ausführen
# - Visualisierung per Link auf entstandene Variablen erstellen
#
# ------------------------------ Changelog -------------------------------------
#
# 08.02.2023 - Initalversion (v1.0)
# 23.02.2023 - API Doc, Type fixes (v1.1)
# 15.03.2023 - Umstellung bzw. Erweiterung für mehrere Anlagen
#              und All-In-One Script (v2.0)
# 17.03.2023 - Fix für Archive Control (v2.1)
# 26.03.2023 - Fix Style & Logging (v2.2)
# 04.03.2024 - Fix für Archivdaten (v2.3)
#
# ------------------------------ Konfiguration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = false;
#
# Globale Variable __WWX als Array via define() in __autoload definiert!!!
#
# Erste Anlage
$PVA['Garage'] = [
    'token'     => __WWX['SPD_TOKEN'],  // (string) erstezen durch => '<api-token>'
    'project'   => __WWX['SPD_MAIL'],   // (string) erstezen durch => '<mail-adresse>'
    'id'        => __WWX['SPD_ID'],     // (int) erstezen durch => <anlagen-id>
    'item'      => 'location',
    'format'    => 'json',
    'type'      => 'hourly', // 'daily';
    'start'     => 0,
    'end'       => 1,
];
#
# Weitere Anlage
# $PVA['Haus'] = [
#    'token'     => __WWX['SPD_TOKEN'],
#    'project'   => __WWX['SPD_MAIL'],
#    'id'        => __WWX['SPD_ID2'],
#    'item'      => 'location',
#    'format'    => 'json',
#    'type'      => 'daily';
#    'start'     => 0,
#    'end'       => 1,
# ];
#
################################################################################
#
# Requires include of the global function script via autoload (__autoload.php)
# or direct in the script (uncomment next line)!
# require_once(IPS_GetKernelDir()."scripts".DIRECTORY_SEPARATOR.'System.Functions.ips.php');
# You can download it from here https://github.com/wilkware/ips-scripts
#
defined('WWX_FUNCTIONS') || die('Global function library not available!');

require_once IPS_GetKernelDir() . 'scripts' . DIRECTORY_SEPARATOR . 'System.QuickChart.ips.php';

// INSTALLATION
if ($_IPS['SENDER'] == 'Execute') {
    // Event 1 mal am Tag
    $midnight = mktime(0, 0, 15);
    CreateEventByName($_IPS['SELF'], 'UpdateDaily', $midnight);
    // Event aller 1 Atunde von  05:00 bis 18:00 Uhr - TODO: Timer im Sommer vielleicht anpassen
    $from = mktime(5, 0, 15);
    $to = mktime(18, 0, 15);
    CreateEventByNameFromTo($_IPS['SELF'], 'UpdateHourly', 3, 1, $from, $to);
    // Variablen pro Anlage anlegen
    foreach ($PVA as $name => $plant) {
        // Kategorie erzeugen
        $cid = CreateCategoryByName($_IPS['SELF'], $name);
        $vid = CreateVariableByName($cid, 'Daten', 3);
        $vid = CreateVariableByName($cid, 'Tabellarischer Verlauf', 3, 10, 'Database', '~HTMLBox');
        $vid = CreateVariableByName($cid, 'Graphischer Verlauf', 3, 11, 'Graph', '~HTMLBox');
        $vid = CreateVariableByName($cid, 'Prognose Heute', 2, 20, '', '~Electricity');
        // Archivierung aktivieren (Typ: Zähler)
        RegisterArchive($vid, false);
        $vid = CreateVariableByName($cid, 'Prognose Morgen', 2, 21, '', '~Electricity');
        $vid = CreateVariableByName($cid, 'Aktuelle Stunde', 2, 22, '', '~Electricity');
    }
}
// WEBFRONT
elseif ($_IPS['SENDER'] == 'WebFront') {
    // Benutzer hat etwas geändert!
}
// VARIABLENAENDERUNG
elseif ($_IPS['SENDER'] == 'Variable') {
    // Varaible hat sich geändert!
}
// TIMER EVENT
elseif ($_IPS['SENDER'] == 'TimerEvent') {
    $event = IPS_GetName($_IPS['EVENT']);
    if ($event == 'UpdateDaily') {
        foreach ($PVA as $name => $plant) {
            // Kategorie erzeugen oder lesen
            $cid = CreateCategoryByName($_IPS['SELF'], $name);
            // Mitternacht - Reset auf 0
            $vid = CreateVariableByName($cid, 'Prognose Heute', 2);
            SetValueFloat($vid, 0);
            $vid = CreateVariableByName($cid, 'Prognose Morgen', 2);
            SetValueFloat($vid, 0);
            $vid = CreateVariableByName($cid, 'Aktuelle Stunde', 2);
            SetValueFloat($vid, 0);
        }
    } elseif ($event == 'UpdateHourly') {
        foreach ($PVA as $name => $plant) {
            // Kategorie erzeugen oder lesen
            $cid = CreateCategoryByName($_IPS['SELF'], $name);
            // von 08:00 bis 18:00 - TODO: Timer im Sommer vielleicht anpassen
            $data = UpdateForecast($plant);
            // Save
            $vid = CreateVariableByName($cid, 'Daten', 3);
            $json = json_encode($data);
            SetValueString($vid, $json);
            // aktuellen Werte abgleichen wenn notwendig
            $vid = CreateVariableByName($cid, 'Prognose Heute', 2);
            if ($data['Heute'] > 0) {
                $lv = GetValueFloat($vid);
                if ($lv < $data['Heute']) {
                    SetValueFloat($vid, $data['Heute']);
                } elseif ($lv == $data['Heute']) {
                    // Do nothing
                } else {
                    //Den letzten Wert, der in der Datenbank gespeichert wurde, holen
                    $aid = IPS_GetInstanceListByModuleID(ExtractGuid('Archive Control'))[0];
                    $last = AC_GetLoggedValues($aid, $vid, 0, 0, 1)[0];
                    AC_DeleteVariableData($aid, $vid, $last['TimeStamp'], 0);
                    SetValueFloat($vid, $data['Heute']);
                    IPS_Sleep(1000);
                    AC_ReAggregateVariable($aid, $vid);
                }
            }
            unset($data['Heute']);
            $vid = CreateVariableByName($cid, 'Prognose Morgen', 2);
            SetValueFloat($vid, $data['Morgen']);
            unset($data['Morgen']);
            $vid = CreateVariableByName($cid, 'Aktuelle Stunde', 2);
            SetValueFloat($vid, $data['Stunde']);
            unset($data['Stunde']);
            // SVG Chart
            $svg = DrawChart($data);
            $svg = str_replace(['1024pt', '325pt'], '100%', $svg);
            $vid = CreateVariableByName($cid, 'Graphischer Verlauf', 3);
            SetValueString($vid, $svg);
            // HTML Table
            $html = BuildHtml($data);
            $vid = CreateVariableByName($cid, 'Tabellarischer Verlauf', 3);
            SetValueString($vid, $html);
        }
    }
}

#----------------------------------- Functions ---------------------------------

function UpdateForecast($plant)
{
    $url = 'https://www.solarprognose.de/web/solarprediction/api/v1?access-token=' .
        $plant['token'] . '&project=' .
        $plant['project'] . '&item=' .
        $plant['item'] . '&id=' .
        $plant['id'] . '&type=' .
        $plant['type'] . '&_format=' .
        $plant['format'] . '&algorithm=own-v1&start_day=' .
        $plant['start'] . '&end_day=' .
        $plant['end'];
    //EchoDebug('URL: ', $url);
    $json = file_get_contents($url);
    //EchoDebug('Daten :', $json);
    $result = [];
    if ($json !== false) {
        $data = json_decode($json, true);
        // Heute, Morgen und aktuelle Stunde
        $ad = date('d.m.Y');
        $at = date('H') . ':00';
        $result['Heute'] = 0;
        $result['Morgen'] = 0;
        $result['Stunde'] = 0;
        // Stundenwerte
        foreach ($data['data'] as $key => $values) {
            // Tag
            $dd = date('d.m.Y', $key);
            // Uhrzeit
            $dt = date('H:i', $key);
            // Werte hochzählen
            if ($ad == $dd) { // Heute
                $result['Heute'] += $values[0];
            } else { // Morgen
                $result['Morgen'] += $values[0];
            }
            if (($ad == $dd) && ($at == $dt)) {
                $result['Stunde'] = $values[0];
            }
            // Pro Tag ein Feld
            if (!isset($result[$ad])) {
                $result[$ad] = [];
            }
            // Stundenwerte und akkumulierten Werte
            $result[$dd][$dt] = $values;
        }
    }
    return $result;
}

function BuildHtml($data)
{
    // Rows
    $rows = array_keys($data);
    // cols
    $cols = max(count($data[$rows[0]]), count($data[$rows[1]]));
    // html
    $html = '';
    $html .= '<table class="wwx">';
    foreach ($data as $day => $hours) {
        $count = 0;
        $th = '<thead class="orange"><th><center>' . $day . '</center></th>';
        $tr1 = '<tr><td>kW/h</td>';
        $tr2 = '<tr><td>&sum; kWh</td>';
        foreach ($hours as $hour => $values) {
            $th .= '<th>' . $hour . '</th>';
            $tr1 .= '<td>' . $values[0] . '</td>';
            $tr2 .= '<td>' . $values[1] . '</td>';
            $count++;
        }
        for ($i = $count; $i < $cols; $i++) {
            $th .= '<th>-</th>';
            $tr1 .= '<td>-</td>';
            $tr2 .= '<td>-</td>';
        }
        $html .= $th . '</thead>';
        $html .= $tr1 . '</tr>';
        $html .= $tr2 . '</tr>';
    }
    $html .= '</table>';
    return $html;
}

function DrawChart($data)
{
    $axis_x = [];
    $axis_y = [];
    // prepeare dataset
    foreach ($data as $day => $hours) {
        foreach ($hours as $hour => $values) {
            $axis_x[] = '\'' . $hour . '\'';
            $axis_y[] = intval($values[0] * 1000);
        }
    }
    // new chart object
    $chart = new QuickChart(['width' => 1024, 'height' => 325, 'format' => 'svg']);
    // chart config
    $chart->setConfig("{
    type: 'line',
    data: {
        labels: [" . implode(',', $axis_x) . "],
        datasets: [
        {
            backgroundColor: 'rgba(255, 125, 0, 0.1)',
            borderColor: 'rgb(255, 125, 0)',
            borderWidth: 1,
            lineTension: 0.4,
            pointRadius: 1,
            fill: true,
            data: [" . implode(',', $axis_y) . "]
        }]
    },
    options: {
        legend: {
            labels: {
                fontColor: 'rgb(255, 255, 255)'
            },
            display: false
        },
        scales: {
        xAxes: [{
            ticks: {
                fontColor: '#fff',
            },
            gridLines: {
                color: 'rgba(211, 211, 211, 0.2)',
                borderDash: [5,5]
            },
            scaleLabel: {
            fontColor: '#fff',
            display: false,
            labelString: 'Uhrzeit'
            }
        }],
        yAxes: [{
            ticks: {
                fontColor: '#fff'
            },
            gridLines: {
                color: 'rgba(211, 211, 211, 0.2)',
                borderDash: [5,5]
            },
            scaleLabel: {
            fontColor: '#fff',
            display: true,
            labelString: 'Leistung (kW)'
            }
        }]
        }
    }
    }");
    return $chart->toBinary();
}

################################################################################