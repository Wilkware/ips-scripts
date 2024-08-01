<?php

declare(strict_types=1);

################################################################################
# Script:   SolarEdge.Monitoring.ips.php
# Version:  1.0.20231002
# Author:   Heiko Wilknitz (@Pitti)
#
# PHP Wrapper for SolarEdge API Calls
# https://monitoring.solaredge.com/
#
# ------------------------------ API Documentation -----------------------------
#
# https://www.solaredge.com/sites/default/files/se_monitoring_api.pdf
#
# ------------------------------ Changelog -------------------------------------
#
# 02.10.2023 - Initalversion (v1.0)
# 04.03.2024 - Kleine Fixes und Anpassungen für Tile Visu (v1.1)
#
# ---------------------------- Konfiguration -----------------------------------
#
# Global Debug Output Flag
$DEBUG = false;

$API_KEY = __WWX['SEM_TOKEN'];
$API_SITE = __WWX['SEM_SID'];
$API_BASE = 'https://monitoringapi.solaredge.com';
$API_UNIT = 'MONTH';
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
    // Event aller 1 Stunde von 06:00 bis 20:00 Uhr
    $from = mktime(6, 0, 0);
    $to = mktime(20, 0, 0);
    CreateEventByNameFromTo($_IPS['SELF'], 'UpdateSiteEnergy', 3, 1, $from, $to);
    // Event aller 1 Stunde von 06:00 bis 20:00 Uhr
    $from = mktime(6, 0, 0);
    $to = mktime(20, 0, 0);
    CreateEventByNameFromTo($_IPS['SELF'], 'UpdateOverview', 2, 5, $from, $to);
    // -------------------- Kategorie Site Overview ----------------------------
    $cid = CreateDummyByIdent($_IPS['SELF'], 'SiteOverview', 'Übersicht', 1, 'EnergyProduction');
    $vid = CreateVariableByIdent($cid, 'Actual', 'Aktuell', 2, 0, '', '~Electricity');
    $vid = CreateVariableByIdent($cid, 'Today', 'Heute', 2, 1, '', '~Electricity');
    RegisterArchive($vid, false); // Archivierung aktivieren (Typ: Zähler)
    $vid = CreateVariableByIdent($cid, 'Month', 'Monat', 2, 2, '', '~Electricity');
    $vid = CreateVariableByIdent($cid, 'Year', 'Jahr', 2, 3, '', '~Electricity');
    $vid = CreateVariableByIdent($cid, 'Total', 'Gesamt', 2, 4, '', '~Electricity');
    RegisterArchive($vid, false); // Archivierung aktivieren (Typ: Zähler)
    // -------------------- Kategorie Site Energy ----------------------------
    $cid = CreateDummyByIdent($_IPS['SELF'], 'SiteEnergy', 'Energiedaten', 2, 'EnergySolar');
    $vid = CreateVariableByIdent($cid, 'Production', 'Produktion', 2, 0, '', '~Electricity');
    RegisterArchive($vid, false); // Archivierung aktivieren (Typ: Zähler)
    $vid = CreateVariableByIdent($cid, 'Consumption', 'Verbrauch', 2, 1, '', '~Electricity');
    RegisterArchive($vid, false); // Archivierung aktivieren (Typ: Zähler)
    $vid = CreateVariableByIdent($cid, 'Purchased', 'Zukauf', 2, 2, '', '~Electricity');
    RegisterArchive($vid, false); // Archivierung aktivieren (Typ: Zähler)
    $vid = CreateVariableByIdent($cid, 'SelfConsumption', 'Eigenverbrauch', 2, 3, '', '~Electricity');
    RegisterArchive($vid, false); // Archivierung aktivieren (Typ: Zähler)
    $vid = CreateVariableByIdent($cid, 'FeedIn', 'Einspeisung', 2, 4, '', '~Electricity');
    RegisterArchive($vid, false); // Archivierung aktivieren (Typ: Zähler)
    $vid = CreateVariableByIdent($cid, 'Performance', 'Leistung und Energieertrag', 3, 5, '', '~HTMLBox');
    IPS_SetHidden($vid, true); // wieder über direkten Link in Visu sichtbar machen :)
    //UpdateSiteEnergy($API_BASE, $API_KEY, $API_SITE, $API_UNIT);
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
    if ($event == 'UpdateOverview') {
        UpdateOverview($API_BASE, $API_KEY, $API_SITE);
    }
    if ($event == 'UpdateSiteEnergy') {
        UpdateSiteEnergy($API_BASE, $API_KEY, $API_SITE, $API_UNIT);
    }
}

#----------------------------------- Functions ---------------------------------

/*
 * Gets the actual site overview
 */
function UpdateOverview($api_base, $api_key, $site_id)
{
    $url = "$api_base/site/$site_id/overview?api_key=$api_key";
    EchoDebug(__FUNCTION__, $url);
    // Daten holen
    $response = @Sys_GetURLContent($url);
    if ($response === false) {
        IPS_LogMessage('SolarEdge', 'Fehler bei Overview aufgetreten!');
        return;
    }
    // Werte decodieren
    $data = json_decode($response, true);
    // Dummy Modul ID holen
    $cid = GetDummyByIdent($_IPS['SELF'], 'SiteOverview');
    // Generated current
    $value = $data['overview']['currentPower']['power'] / 1000.;
    $vid = GetVariableByIdent($cid, 'Actual');
    SetValue($vid, $value);
    // Generated day
    $value = $data['overview']['lastDayData']['energy'] / 1000.;
    $vid = GetVariableByIdent($cid, 'Today');
    SetValue($vid, $value);
    // Generated month
    $value = $data['overview']['lastMonthData']['energy'] / 1000.;
    $vid = GetVariableByIdent($cid, 'Month');
    SetValue($vid, $value);
    // Generated year
    $value = $data['overview']['lastYearData']['energy'] / 1000.;
    $vid = GetVariableByIdent($cid, 'Year');
    SetValue($vid, $value);
    // Generated all
    $value = $data['overview']['lifeTimeData']['energy'] / 1000.;
    $vid = GetVariableByIdent($cid, 'Total');
    SetValue($vid, $value);
}

/*
 * Gets the actual site energy
 */
function UpdateSiteEnergy($api_base, $api_key, $site_id, $time_unit)
{
    $now = time();
    $year = intval(date('Y', $now));
    $month = intval(date('m', $now));
    $day = intval(date('d', $now));
    $date_start = "$year-$month-01%2000:00:00";
    $date_end = "$year-$month-$day%2023:59:59";

    $url = "$api_base/site/$site_id/energyDetails?timeUnit=$time_unit&endTime=$date_end&startTime=$date_start&api_key=$api_key";
    EchoDebug(__FUNCTION__, $url);
    // Daten holen
    $response = @Sys_GetURLContent($url);
    if ($response === false) {
        IPS_LogMessage('SolarEdge', 'Fehler bei SiteEnergy aufgetreten!');
        return;
    }
    // Werte decodieren
    $data = json_decode($response, true);
    // Dummy Modul ID holen
    $cid = GetDummyByIdent($_IPS['SELF'], 'SiteEnergy');
    // Werte schreiben
    $performance = [];
    foreach ($data['energyDetails']['meters'] as $meter) {
        $vid = GetObjectByIdent($cid, $meter['type']);
        $value = $meter['values'][0]['value'] / 1000.;
        SetValue($vid, $value);
        $performance[$meter['type']] = $value;
    }
    $vid = GetVariableByIdent($cid, 'Performance');
    $html = RenderHtml($performance);
    SetValue($vid, $html);
}

/*
 * Render the performance chart
 */
function RenderHtml($data)
{
    # kWh values
    $production_kwh = round($data['Production'], 2);
    $selfconsumption_kwh = round($data['SelfConsumption'], 2);
    $feedin_kwh = round($data['FeedIn'], 2);
    $consumption_kwh = round($data['Consumption'], 2);
    $purchaesd_kwh = round($data['Purchased'], 2);
    $selfproduction_kwh = $selfconsumption_kwh;
    # Percent values
    $selfconsumption_percent = 100.;
    if ($production_kwh > 0) {
        $selfconsumption_percent = round($selfconsumption_kwh * 100. / $production_kwh, 0);
    }
    $feedin_percent = 100. - $selfconsumption_percent;
    $selfproduction_percent = 100.;
    if ($consumption_kwh > 0) {
        $selfproduction_percent = round($selfproduction_kwh * 100. / $consumption_kwh, 0);
    }
    $purchaesd_percent = 100. - $selfproduction_percent;
    # Timespent
    $now = time();
    $year = intval(date('Y', $now));
    $month = intval(date('m', $now));
    $day = intval(date('t', $now));
    $time_spent = "01.$month.$year - $day.$month.$year";
    #html content
    $html = '
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body {margin: 0px;}
    ::-webkit-scrollbar {width: 8px;}
    ::-webkit-scrollbar-track {background: transparent;}
    ::-webkit-scrollbar-thumb {background: transparent; border-radius: 20px;}
    ::-webkit-scrollbar-thumb:hover {background: #555;}
    .sem-box-chart{width:100%;display:flex;position:relative;font-size:14px;}
    .sem-box-text{width:100%;display:flex;position:relative;padding-top:5px;padding-bottom:15px;font-size:12px;}
    .sem-box-overlay{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:white;z-index:10;}
    .sem-selfconsumption_percent{width:' . $selfconsumption_percent . '%;background:#28cdab;padding-top:10px;padding-left:10px;padding-bottom:10px;color:white;border-radius:5px 0px 0px 5px;}
    .sem-feedin-percent{width:' . $feedin_percent . '%;background:#15c603;padding-top:10px;padding-bottom:10px;padding-right:10px;direction:rtl;color:white;border-radius:0px 5px 5px 0px;}
    .sem-selfproduction-percent{width:' . $selfproduction_percent . '%;background:#28cdab;padding-top:10px;padding-left:10px;padding-bottom:10px;color:white;border-radius:5px 0px 0px 5px;}
    .sem-purchaesd-percent{width:' . $purchaesd_percent . '%;background:#d00000;padding-top:10px;padding-right:10px;padding-bottom:10px;white-space:nowrap;direction:rtl;color:white;border-radius:0px 5px 5px 0px;}
    .sem-text-right{width:50%;text-align:right;}
    .sem-text-left{width:50%;}
</style>
<body>
<div class="sem-box-text">
    <div class="sem-text-left">&#128197; ' . $time_spent . '</div>
    <div class="sem-text-right">&#9200; ' . date('d.m.Y H:i', $now) . ' Uhr</div>
</div>
<div class="sem-box-chart">
    <div class="sem-box-overlay">Produktion: ' . $production_kwh . ' kWh</div>
    <div class="sem-selfconsumption_percent">' . $selfconsumption_percent . '%</div>
    <div class="sem-feedin-percent">' . $feedin_percent . '%</div>
</div>
<div class="sem-box-text">
    <div class="sem-text-left">Eigenverbrauch: <font style="color: #28cdab; font-weight: bold;">' . $selfconsumption_kwh . '&nbsp;kWh</font></div>
    <div class="sem-text-right">Einspeisung: <font style="color: #15c603; font-weight: bold;">' . $feedin_kwh . '&nbsp;kWh</font></div>
</div>
<div class="sem-box-chart">
    <div class="sem-box-overlay">Verbrauch: ' . $consumption_kwh . ' kWh</div>
    <div class="sem-selfproduction-percent">' . $selfproduction_percent . '%</div>
    <div class="sem-purchaesd-percent">' . $purchaesd_percent . '%</div>
</div>
<div class="sem-box-text">
    <div class="sem-text-left">Eigenproduktion: <font style="color: #28cdab; font-weight: bold;">' . $selfproduction_kwh . '&nbsp;kWh</font></div>
    <div class="sem-text-right">Zukauf: <font style="color: #FF0000; font-weight: bold;">' . $purchaesd_kwh . '&nbsp;kWh&nbsp;</font></div>
</div>
</body>';

    return $html;
}