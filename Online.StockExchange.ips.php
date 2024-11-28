<?php

declare(strict_types=1);

################################################################################
# Script:   Online.StockExchange.ips.php
# Version:  3.0.20241128
# Author:   Heiko Wilknitz (@Pitti)
#           Erweiterung von Smudo (Umstellung auf Tagesschau)
#
# Börsenticker:
#   Tageschaukurse: https://www.tagesschau.de/wirtschaft/boersenkurse/<WKN>/
#       dax-index-846900 => DAX
#   Beispiel: https://www.tagesschau.de/wirtschaft/boersenkurse/dax-index-846900/
#
# Installation:
#   - WKN-Array unter Konfiguration mit den entsprechenden Werten befüllen
#   - WKN-Values unter Konfiguration mit den entsprechenden Namen befüllen
#   - Script in der Konsole ausführen
#   - Timer z.B. für Mo-Fr von 9:00 - 18:00 Uhr (aller 15 min) anlegen
#
# ------------------------------ Changelog -------------------------------------
#
#   05.12.2017 - Umstellung auf Curl (v2.0)
#   19.03.2018 - Umstellung von Google Finance auf Google Search (v2.1)
#   28.11.2024 - Umstellung auf tagesschau.de (v3.0)
#
# ------------------------------ Konfiguration ---------------------------------
#
# Wertpapierkennnummer
$wkn = [
    // Tageschau Börsenkurse
    "dax-index-846900"	=> "DAX",
];
#
# Aktuell mögliche Werte:
#   Eröffnung, Tages-Hoch, Tages-Tief,
#   52 Wochen-Hoch, 52 Wochen-Tief,
#   Allzeit-Hoch, Allzeit-Tief,
#   Schluss Vortag, Geld, Brief
$wkn_values = ['Eröffnung', 'Tages-Hoch', 'Tages-Tief'];
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
    // ID des ArchiveHandler ermitteln
    $aid = 
    $aid = IPS_GetInstanceListByModuleID(ExtractGuid('Archive Control'))[0];
    // pro WKN eine Variable
    foreach ($wkn as $ident => $name) {
        $pos = 0;
        $vid = CreateVariableByName($_IPS['SELF'], $name, 2 /*Float*/, $pos, 'money-bill-trend-up', '~Euro');
        IPS_SetInfo($vid, $ident);
        AC_SetLoggingStatus($aid, $vid, true);
        // Unterhalb Variablen für die Tageswerte anlegen
        foreach($wkn_values as $name) {
            CreateVariableByName($vid, $name, 2, $pos++, 'chart-mixed-up-circle-dollar', '~Euro');
        }
        CreateVariableByName($vid, 'Tagesveränderung', 3, $pos, 'chart-mixed-up-circle-dollar');
    }
}
// TIMEREVENT
if ($_IPS['SENDER'] == 'TimerEvent') {
    // alle untergeordneten Objekt einsammeln
    $ids = IPS_GetChildrenIDs($_IPS['SELF']);
    //echo print_r($ids);
    foreach ($ids as $id) {
        // for each Wertkennzeichen daten holen
        $array = IPS_GetObject($id);
        // Float-Variable?
        if ($array['ObjectType'] == 2) {
            $url = 'https://www.tagesschau.de/wirtschaft/boersenkurse/' . $array['ObjectInfo'] . '/';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            $page = curl_exec($curl);
            curl_close($curl);
            //var_dump($page);
            $stock = GetMark($page, '<table class="cnttable zebra to le">*</tbody>');
            $search = '//td[text()="Kurs"]/following-sibling::td[@class="ri"]';
            $value = Dom2Value($stock, $search);
            $result = ToFloat($value);
            //var_dump($result);
            if ($result > 0) {
                SetValueFloat($id, $result);
            }
            else {
                IPS_LogMessage('BÖRSE', $result);
            }
            // zu jedem WKZ die zusätzlichen Daten holen
            foreach ($wkn_values as $name) {
                $search = '//td[text()="' . $name . '"]/following-sibling::td[@class="ri"]';
                $value = Dom2Value($stock, $search);
                $result = ToFloat($value);
                if ($result == 'N/A') {
                    @IPS_DeleteVariable((@IPS_GetObjectIDByName($name, $id)));
                } else {
                    SetValueFloat(@IPS_GetObjectIDByName($name, $id), $result);
                }
            }
            $stock = GetMark($page, '<span class="change">*</span>');
            $search = '//span[contains(@class, "icon_pos") or contains(@class, "icon_neg") or contains(@class, "icon_neutral")]';
            $value = Dom2Value($stock, $search);
            SetValueString(@IPS_GetObjectIDByName('Tagesveränderung', $id), $value);
        }
    }
}

// Diese Funktion trennt die relevanten Bereiche aus dem Ausschnitt heraus
// $string ist dabei der zu durchsuchende Gesamtstring,
// in $Mark sind durch "*" getrennt der Beginn des zu suchenden Strings
// und das Ende des zu suchende Abschnittes.
// Beispiel für den Text "<div>*</div></li>"
function GetMark($string, $mark)
{
    $find = explode('*', $mark);
    $lens = strlen($find[0]);
    $lene = strlen($find[1]);
    $start = strpos($string, $find[0]);
    $stop = strpos($string, $find[1], $start + $lens);
    $inner = substr($string, $start + $lens, $stop - $start - $lens);
    return $inner;
}

// Umwandlung einer Zahlenrepräsentations als String in eine echte Gleitkommazahl
function ToFloat($str)
{
    if (strstr($str, ',')) {
        $str = str_replace('.', '', $str); // replace dots (thousand seps) with blancs
        $str = str_replace(',', '.', $str); // replace ',' with '.'
    }
    return floatval($str); // take some last chances with floatval
}

// Extrahiert Wert aus DOM für übergebenen Suchkriterium
function Dom2Value($data, $search)
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $data);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $elements = $xpath->query($search);
    // Überprüfe, ob das Element gefunden wurde und speichere den Inhalt in einer Variablen
    if ($elements->length > 0) {
        $value = $elements->item(0)->nodeValue;
    } else {
        $value = 'N/A';
    }
    return $value;
}

################################################################################