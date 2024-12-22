<?php

declare(strict_types=1);

################################################################################
# Script:   SolarEdge.Monitoring.ips.php
# Version:  1.2.20241119
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
# 19.11.2024 - Auswahl des (Auswertungs-)Zeitraums (nur für Chart) (v1.2)
#
# ---------------------------- Konfiguration -----------------------------------
#
#
# Global Debug Output Flag
$DEBUG = false;
#
$API_KEY = __WWX['SEM_TOKEN'];
$API_SITE = __WWX['SEM_SID'];
$API_BASE = 'https://monitoringapi.solaredge.com';
#
$API_UNIT = [
    [0, 'Tag',   '', -1, 'DAY'],
    [1, 'Woche', '', -1, 'WEEK'],
    [2, 'Monat', '', -1, 'MONTH'],
    [3, 'Jahr',  '', -1, 'YEAR'],
];
#
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
    $vid = CreateVariableByIdent($cid, 'Performance', 'Leistung und Energieertrag', 3, 6, '', '~HTMLBox');
    IPS_SetHidden($vid, true); // wieder über direkten Link in Visu sichtbar machen :)
    $vpn = 'SEM.Period';
    CreateProfileInteger($vpn, 'Calendar', '', '', 0, 0, 0, $API_UNIT);
    $vid = CreateVariableByIdent($cid, 'Period', 'Zeitraum', 1, 5, '', $vpn, $_IPS['SELF']);
    // -------------------- Webhook for Site Energy ----------------------------
    RegisterHook('sem', $_IPS['SELF']);
}
// WEBFRONT
elseif ($_IPS['SENDER'] == 'WebFront') {
    // ToDO?
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
        UpdateSiteEnergy($API_BASE, $API_KEY, $API_SITE, $API_UNIT, true);
    }
}
// WEBHOOK
elseif ($_IPS['SENDER'] == 'WebHook') {
    $period = isset($_GET['p'])?$_GET['p']: 3;
    $cid = GetDummyByIdent($_IPS['SELF'], 'SiteEnergy');
    $vid = CreateVariableByIdent($cid, 'Period', 'Zeitraum', 1);
    SetValue($vid, $period);
    UpdateSiteEnergy($API_BASE, $API_KEY, $API_SITE, $API_UNIT, false);
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
function UpdateSiteEnergy($api_base, $api_key, $site_id, $time_unit, $timer)
{
    // Datendummy holen
    $cid = GetDummyByIdent($_IPS['SELF'], 'SiteEnergy');
    // Zeitraum holen
    $vid = GetObjectByIdent($cid, 'Period');
    // fix 2 = MONTH, or via webhook
    $period = $timer ? 2 : GetValue($vid);
    // Zeiten setzen
    $now = time();
    $year = intval(date('Y', $now));
    $month = intval(date('m', $now));
    $day = intval(date('d', $now));
    $time = "01.01.$year - $day.$month.$year";
    // Zeitraum für URL-Parameter zusammenbauen
    switch ($period) {
        case 0:
            $date_start = "$year-$month-$day%2000:00:00";
            $date_end = "$year-$month-$day%2023:59:59";
            $time = "$day.$month.$year";
            break;
        case 1:
            $date_end = "$year-$month-$day%2023:59:59";
            $time = " - $day.$month.$year";
            $week_start = strtotime('monday this week');
            $year = intval(date('Y', $week_start));
            $month = intval(date('m', $week_start));
            $day = intval(date('d', $week_start));
            $date_start = "$year-$month-$day%2000:00:00";
            $time = "$day.$month.$year" . $time;
            break;
        case 2:
            $date_start = "$year-$month-01%2000:00:00";
            $date_end = "$year-$month-$day%2023:59:59";
            $time = "$day.$month.$year" . $time;
            $time = "01.$month.$year - $day.$month.$year";
            break;
        default:
            $date_start = "$year-01-01%2000:00:00";
            $date_end = "$year-$month-$day%2023:59:59";
            break;
    }
    $url = "$api_base/site/$site_id/energyDetails?timeUnit=" . $time_unit[$period][4] . "&endTime=$date_end&startTime=$date_start&api_key=$api_key";
    EchoDebug(__FUNCTION__, $url);
    // Daten holen
    $response = @Sys_GetURLContent($url);
    if ($response === false) {
        IPS_LogMessage('SolarEdge', 'Fehler bei SiteEnergy aufgetreten!');
        return;
    }
    // Werte decodieren
    $data = json_decode($response, true);
    // Werte schreiben
    $performance = [];
    foreach ($data['energyDetails']['meters'] as $meter) {
        $value = $meter['values'][0]['value'] / 1000.;
        $performance[$meter['type']] = $value;
        if($timer) {
            $vid = GetObjectByIdent($cid, $meter['type']);
            SetValue($vid, $value);
        }
    }
    $vid = GetVariableByIdent($cid, 'Performance');
    $html = RenderHtml($performance, $time, $period);
    SetValue($vid, $html);
}

/*
 * Render the performance chart
 */
function RenderHtml($data, $time, $period)
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
    #html content
    $html = __TILE_VISU_SCRIPT;
    $html .= '
<style>
    .sem-box-chart{width:100%;display:flex;position:relative;font-size:14px;}
    .sem-box-text{width:100%;display:flex;position:relative;padding-top:5px;padding-bottom:15px;font-size:12px;}
    .sem-box-overlay{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:white;z-index:10;}
    .sem-selfconsumption_percent{width:' . $selfconsumption_percent . '%;background:#28cdab;padding-top:10px;padding-left:10px;padding-bottom:10px;color:white;border-radius:5px 0px 0px 5px;}
    .sem-feedin-percent{width:' . $feedin_percent . '%;background:#15c603;padding-top:10px;padding-bottom:10px;padding-right:10px;direction:rtl;color:white;border-radius:0px 5px 5px 0px;}
    .sem-selfproduction-percent{width:' . $selfproduction_percent . '%;background:#28cdab;padding-top:10px;padding-left:10px;padding-bottom:10px;color:white;border-radius:5px 0px 0px 5px;}
    .sem-purchaesd-percent{width:' . $purchaesd_percent . '%;background:#d00000;padding-top:10px;padding-right:10px;padding-bottom:10px;white-space:nowrap;direction:rtl;color:white;border-radius:0px 5px 5px 0px;}
    .sem-text-right{width:50%;text-align:right;}
    .sem-text-left{width:50%;}
    .sem-box-button {display: flex; font-size: 14px;}
    .sem-button {flex: 1; margin: 5px; display: flex; flex-direction: row; align-items: center; justify-content: center; border-radius: 5px; cursor: pointer; border-width: 0px; color: var(--content-color); background-color: rgba(153, 155, 154, 0.2); box-sizing: border-box; white-space: nowrap; height: 40px; }
    .sem-button-left {margin-left: 0;}
    .sem-button-right {margin-right: 0;}
    .sem-button-select {background-color: var(--accent-color); color: #ffffff;}
</style>
<body>
<div class="sem-box-text">
    <div class="sem-text-left">&#128197; ' . $time . '</div>
    <div class="sem-text-right">&#9200; ' . date('d.m.Y H:i', time()) . ' Uhr</div>
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
    <div class="sem-text-left">Selbstversorgung: <font style="color: #28cdab; font-weight: bold;">' . $selfproduction_kwh . '&nbsp;kWh</font></div>
    <div class="sem-text-right">Zukauf: <font style="color: #FF0000; font-weight: bold;">' . $purchaesd_kwh . '&nbsp;kWh&nbsp;</font></div>
</div>
<div class="sem-box-button">
    <button class="sem-button sem-button-left' . ($period==0?' sem-button-select" ':'" ') . 'onclick="window.xhrGet=function xhrGet(o) {var xhr = new XMLHttpRequest();xhr.open(\'GET\',o.url,true); xhr.send();};window.xhrGet({ url: \'/hook/sem?p=0\' });">Tag</button>
    <button class="sem-button' . ($period==1?' sem-button-select" ':'" ') . 'onclick="window.xhrGet=function xhrGet(o) {var xhr = new XMLHttpRequest();xhr.open(\'GET\',o.url,true); xhr.send();};window.xhrGet({ url: \'/hook/sem?p=1\' });">Woche</button>
    <button class="sem-button' . ($period==2?' sem-button-select" ':'" ') . 'onclick="window.xhrGet=function xhrGet(o) {var xhr = new XMLHttpRequest();xhr.open(\'GET\',o.url,true); xhr.send();};window.xhrGet({ url: \'/hook/sem?p=2\' });">Monat</button>
    <button class="sem-button sem-button-right' . ($period==3?' sem-button-select" ':'" ') . 'onclick="window.xhrGet=function xhrGet(o) {var xhr = new XMLHttpRequest();xhr.open(\'GET\',o.url,true); xhr.send();};window.xhrGet({ url: \'/hook/sem?p=3\' });">Jahr</button>
</div>
</body>';

    return $html;
}