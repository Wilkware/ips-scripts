<?php

declare(strict_types=1);

################################################################################
# Script:   Amount.SolCast.ips.php
# Version:  1.3.20230515
# Author:   Heiko Wilknitz (@Pitti)
#           Idee von STELE99 (2022)
#
# Forecast von PV Analage(n) berechnen via solcast.com
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
# 22.02.2023 - Initalversion (v1.0)
# 12.03.2023 - BuildTable,CalcTotal & ArchiveValue hinzugefügt (v1.1)
# 17.03.2023 - Fix für Archive Control (v1.2)
# 15.05.2023 - Keine Zwischenwerte mehr im Archiv
#
# ------------------------------ Konfiguration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = false;
#
# PV Anlagen definiert durch
#   token:       API Key
#   rid:         Ressource ID der Anlage in Solcast
#   graph:       Wenn true wird via quickchart.io ein Graph erstellt
#
# Erste Anlage (meine ist auf der Garage, bitte den Namen auch anpassen)
$PVA['Garage'] = [
    'token'     => __WWX['SCC_TOKEN'], // (string) erstezen durch => '<API-KEY>'
    'rid'       => __WWX['SCC_RID'],   // (string) erstezen durch => '<GUID>'
    'graph'     => true,
];
#
# Weitere Anlage
# $PVA['Haus'] = [
#    'token'     => '<API-KEY>',
#    'rid'       => <GUID>,
#    'graph'      => true,
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
    $midnight = mktime(0, 4, 16);
    CreateEventByName($_IPS['SELF'], 'UpdateMidnight', $midnight);
    // Event aller 6 Stunde von  03:00 bis 22:00 Uhr
    $from = mktime(2, 54, 16);
    $to = mktime(22, 0, 0);
    CreateEventByNameFromTo($_IPS['SELF'], 'UpdateDaily', 3, 1, $from, $to);
    foreach ($PVA as $name => $plant) {
        // Kategorie erzeugen
        $cid = CreateCategoryByName($_IPS['SELF'], $name);
        $vid = CreateVariableByName($cid, 'Daten', 3, 0);
        SetValueString($vid, '[]');
        $vid = CreateVariableByName($cid, 'Tabellarischer Verlauf', 3, 1, 'Database', '~HTMLBox');
        $vid = CreateVariableByName($cid, 'Graphischer Verlauf', 3, 2, 'Graph', '~HTMLBox');
        $vid = CreateVariableByName($cid, 'PV heute (normal)', 2, 10, '', '~Electricity');
        // Archivierung aktivieren (Typ: Zähler)
        RegisterArchive($vid, false);
        $vid = CreateVariableByName($cid, 'PV heute (besser)', 2, 11, '', '~Electricity');
        // Archivierung aktivieren (Typ: Zähler)
        RegisterArchive($vid, false);
        $vid = CreateVariableByName($cid, 'PV heute (schlechter)', 2, 12, '', '~Electricity');
        // Archivierung aktivieren (Typ: Zähler)
        RegisterArchive($vid, false);
        $vid = CreateVariableByName($cid, 'PV morgen (normal)', 2, 20, '', '~Electricity');
        $vid = CreateVariableByName($cid, 'PV morgen (besser)', 2, 21, '', '~Electricity');
        $vid = CreateVariableByName($cid, 'PV morgen (schlechter)', 2, 22, '', '~Electricity');
    }
}
// WEBFRONT
elseif ($_IPS['SENDER'] == 'WebFront') {
    // Benutzer hat etwas geändert!
}
// VARIABLENAENDERUNG
elseif ($_IPS['SENDER'] == 'Variable') {
    // ToDO?
}
// TIMER EVENT
elseif ($_IPS['SENDER'] == 'TimerEvent') {
    $event = IPS_GetName($_IPS['EVENT']);
    // pro Anlage werden die Daten auswerten
    foreach ($PVA as $name => $plant) {
        UpdateData($name, $plant, ($event == 'UpdateMidnight'));
    }
}

#----------------------------------- Functions ---------------------------------

// Für die Anlagen den Forecast ermitteln und tabellarisch und graphisch aufbereiten
function UpdateData($name, $plant, $reset = false)
{
    // Kategorie erzeugen oder lesen
    $cid = CreateCategoryByName($_IPS['SELF'], $name);
    // Daten holen
    $data = RequestData($plant);
    // Daten normalisieren
    $fore = ForecastData($data);
    // Gespeicherte Daten einlesen
    $did = CreateVariableByName($cid, 'Daten', 3);
    $data = json_decode(GetValue($did), true);
    // Variablen
    $hnid = CreateVariableByName($cid, 'PV heute (normal)', 2);
    $hbid = CreateVariableByName($cid, 'PV heute (besser)', 2);
    $hsid = CreateVariableByName($cid, 'PV heute (schlechter)', 2);
    $mnid = CreateVariableByName($cid, 'PV morgen (normal)', 2);
    $mbid = CreateVariableByName($cid, 'PV morgen (besser)', 2);
    $msid = CreateVariableByName($cid, 'PV morgen (schlechter)', 2);
    // Daten zurücksetzen oder zusammenführen
    if ($reset) {
        $data = $fore;
        // Heute Variablen
        SetValueFloat($hnid, 0);
        SetValueFloat($hbid, 0);
        SetValueFloat($hsid, 0);
        IPS_Sleep(1000);
        // Morgen Variablen
        SetValueFloat($mnid, 0);
        SetValueFloat($mbid, 0);
        SetValueFloat($msid, 0);
        IPS_Sleep(1000);
    } else {
        foreach ($fore as $day => $hours) {
            foreach ($hours as $hour => $values) {
                $data[$day][$hour] = $values;
            }
        }
    }
    // Data wieder speichern
    $str = json_encode($data);
    SetValueString($did, $str);
    // Summen ermitteln für Heute (Archive) und Morgen
    $values = CalcTotal($data);
    // Heute
    ArchiveValue($hnid, $values[0]['norm']);
    ArchiveValue($hbid, $values[0]['more']);
    ArchiveValue($hsid, $values[0]['poor']);
    // Morgen
    SetValueFloat($mnid, $values[1]['norm']);
    SetValueFloat($mbid, $values[1]['more']);
    SetValueFloat($msid, $values[1]['poor']);
    // HTML Table bauen
    $html = BuildTable($data);
    $vid = CreateVariableByName($cid, 'Tabellarischer Verlauf', 3);
    SetValueString($vid, $html);
    // SVG Chart plotten
    $svg = DrawChart($data);
    $svg = str_replace(['1024pt', '325pt'], '100%', $svg);
    $vid = CreateVariableByName($cid, 'Graphischer Verlauf', 3);
    SetValueString($vid, $svg);
}

// Von SolCast gelieferten Daten auf Tages- und stundenbasis Normalisieren
function ForecastData($data)
{
    $fd = [];
    foreach ($data['forecasts'] as $o) {
        $hour = date('H', strtotime($o['period_end']));
        $day = date('z', strtotime($o['period_end'])) - date('z');
        if (isset($fd[$day][$hour])) {
            // echo "Add " . $day . ' - ' . $hour . PHP_EOL;
            $fd[$day][$hour]['norm'] = ($fd[$day][$hour]['norm'] + $o['pv_estimate']) / 2;
            $fd[$day][$hour]['poor'] = ($fd[$day][$hour]['poor'] + $o['pv_estimate10']) / 2;
            $fd[$day][$hour]['more'] = ($fd[$day][$hour]['more'] + $o['pv_estimate90']) / 2;
        } else {
            // echo "New " . $day . ' - ' . $hour . PHP_EOL;
            $fd[$day][$hour] = [
                'norm'  => $o['pv_estimate'],
                'poor'  => $o['pv_estimate10'],
                'more'  => $o['pv_estimate90'],
            ];
        }
    }
    return $fd;
}

// Daten gezielt ins Archive schreiben (Zähler -> keine negativen Werte)
function ArchiveValue($vid, $value)
{
    $lv = GetValueFloat($vid);
    if (($lv > 0) && ($lv != $value)) {
        //Den letzten Wert, der in der Datenbank gespeichert wurde, holen
        $aid = IPS_GetInstanceListByModuleID(ExtractGuid('Archive Control'))[0];
        $last = AC_GetLoggedValues($aid, $vid, 0, 0, 1)[0];
        AC_DeleteVariableData($aid, $vid, $last['TimeStamp'], 0);
        IPS_Sleep(1000);
        SetValueFloat($vid, $value);
        AC_ReAggregateVariable($aid, $vid);
        IPS_Sleep(1000);
    } else {
        SetValueFloat($vid, $value);
    }
}

// Daten bei SolCast.com anfragen
function RequestData($plant, $forecast = true)
{
    // Which data
    $url = 'https://api.solcast.com.au/rooftop_sites/' . $plant['rid'];
    if ($forecast) {
        $url .= '/forecasts?format=json';
    } else {
        $url .= '/estimated_actuals?format=json';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $plant['token'],
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        throw new ErrorException(curl_error($ch));
    }
    curl_close($ch);
    // $response = $SO_JSON;
    $data = json_decode($response, true);
    // print_r($data);
    return $data;
}

// Tages-Summen bilden und zurückliefern
function CalcTotal($data)
{
    $total = [];
    foreach ($data as $day => $hours) {
        $poor = 0;
        $norm = 0;
        $more = 0;
        foreach ($hours as $hour => $values) {
            $poor += $values['poor'];
            $norm += $values['norm'];
            $more += $values['more'];
        }
        $total[$day]['poor'] = $poor;
        $total[$day]['norm'] = $norm;
        $total[$day]['more'] = $more;
    }
    return $total;
}

// HTML-Tabelle pro Tag bauen
function BuildTable($data)
{
    // cols
    $cols = 0;
    foreach ($data as $day => $hours) {
        $count = 0;
        foreach ($hours as $hour => $values) {
            if (($values['poor'] != 0) || ($values['norm'] != 0) || ($values['more'] != 0)) {
                $count++;
            }
        }
        $cols = max($cols, $count);
    }
    // html
    $html = '';
    $html .= '<table class="wwx">';
    foreach ($data as $day => $hours) {
        $count = 0;
        $th = '<thead class="orange"><th><center>' . date('d.m.Y', strtotime('today+' . $day . 'day')) . '</center></th>';
        $tr1 = '<tr><td>kW/h (bewölkt)</td>';
        $tr2 = '<tr><td>kW/h (normal)</td>';
        $tr3 = '<tr><td>kW/h (sonnig)</td>';
        foreach ($hours as $hour => $values) {
            if (($values['poor'] != 0) || ($values['norm'] != 0) || ($values['more'] != 0)) {
                $th .= '<th>' . $hour . ':00</th>';
                $tr1 .= '<td>' . round(floatval($values['poor']), 4) . '</td>';
                $tr2 .= '<td>' . round(floatval($values['norm']), 4) . '</td>';
                $tr3 .= '<td>' . round(floatval($values['more']), 4) . '</td>';
                $count++;
            }
        }
        for ($i = $count; $i < $cols; $i++) {
            $th .= '<th>-</th>';
            $tr1 .= '<td>-</td>';
            $tr2 .= '<td>-</td>';
            $tr3 .= '<td>-</td>';
        }
        $html .= $th . '</thead>';
        $html .= $tr1 . '</tr>';
        $html .= $tr2 . '</tr>';
        $html .= $tr3 . '</tr>';
    }
    $html .= '</table>';
    return $html;
}

// Daten als Chart plotten
function DrawChart($data)
{
    $axis_x = [];
    $axis_yn = [];
    $axis_yp = [];
    $axis_ym = [];

    foreach ($data as $day => $hours) {
        foreach ($hours as $hour => $value) {
            if (($value['norm'] != 0) && ($value['poor'] != 0) && ($value['more'] != 0)) {
                $axis_x[] = '\'' . $hour . ':00\'';
                $axis_yn[] = intval($value['norm'] * 1000);
                $axis_yp[] = intval($value['poor'] * 1000);
                $axis_ym[] = intval($value['more'] * 1000);
            }
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
            label: 'Sonniger',
            backgroundColor: 'rgba(255, 125, 0, 0.1)',
            borderColor: 'rgb(255, 125, 0)',
            borderWidth: 1,
            lineTension: 0.4,
            pointRadius: 1,
            fill: true,
            data: [" . implode(',', $axis_ym) . "]
        },
        {
            label: 'Normal',
            backgroundColor: 'rgba(255, 255, 0, 0.1)',
            borderColor: 'rgb(255, 255, 0)',
            borderWidth: 1,
            lineTension: 0.4,
            pointRadius: 1,
            fill: true,
            data: [" . implode(',', $axis_yn) . "]
        },
        {
            label: 'Bewölkter',
            backgroundColor: 'rgba(0, 255, 255, 0.1)',
            borderColor: 'rgb(0, 255, 255)',
            borderWidth: 1,
            lineTension: 0.4,
            pointRadius: 1,
            fill: true,
            data: [" . implode(',', $axis_yp) . "]
        }
        ]
    },
    options: {
        legend: {
            labels: {
                fontColor: 'rgb(255, 255, 255)'
            }
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
            labelString: 'Leistung (Watt)'
            }
        }]
        }
    }
    }");
    return $chart->toBinary();
}

################################################################################