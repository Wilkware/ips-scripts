<?php
################################################################################
# Script:   Online.StockExchange.ips.php
# Version:  2.1.20180319
# Author:   Heiko Wilknitz (@Pitti)
#           Erweiterung von Smudo (Umstellung auf Tagesschau) 
#
# Börsenticker:
#   Google Search:  https://www.google.de/search?q=<WKN>
#       WKN = Wertpapierkennnummer
#           ETR:xxx => Xetra
#           FRA:xxx => Frankfurt
#       Beispiel:   https://www.google.com/search?q=ETR:BMW
#
# Installation:
#   - WKN-Array unter Konfiguration mit den entsprechenden Werten befüllen
#   - Script in der Konsole ausführen
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
    // Google Search
    //  "ETR:ADN1"      => "adesso SE",
    "FRA:ADN1"      => "adesso SE",
    //  "FRA:DBK"      => "Deutsche Bank",
];
#
################################################################################

// INSTALLATION
if ($_IPS['SENDER'] == "Execute") {
    // ID des ArchiveHandler ermitteln 
    $instances = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}'); 
    $id_archive_handler = $instances[0]; 
    // pro WKN eine Variable
    foreach($wkn as $ident => $name) {
        $pos = 0;
        $vid = CreateVariableByName($_IPS['SELF'], $name, 2 /*Float*/, $pos++, 'Graph' , 'Stock.ADN1');
        IPS_SetInfo($vid, $ident);
        AC_SetLoggingStatus($id_archive_handler, $vid, true);
    }
}
// TIMEREVENT
if($_IPS['SENDER'] == "TimerEvent") {
    // alle untergeordneten Objekt einsammeln
    $ids = IPS_GetChildrenIDs($_IPS['SELF']);
    //echo print_r($ids);
    foreach ($ids as $id) {
        // for each Wertkennzeichen daten holen
        $array = IPS_GetObject($id);
        // Float-Variable?
        if ($array['ObjectType'] == 2) {
            $url = 'https://www.google.de/search?q='.$array['ObjectInfo'];
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
            curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'); 
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            $page = curl_exec($curl);
            curl_close($curl);
            $stock = GetMark($page, '<span style="font-size:157%"><b>*</b>'); 
            //var_dump($page);
            $dec = ToFloat($stock);
            //var_dump($dec);
            if(!is_float((float)$dec)) {
                IPS_LogMessage("BOERSE", $dec);
                return;
            }
            if($dec > 0) {
                SetValue($id, $dec);
            }
            else {
                IPS_LogMessage("BÖRSE", $dec);
            }
        }
    }
}

// Diese Funktion trennt die relevanten Bereiche aus dem Ausschnitt heraus 
// $string ist dabei der zu durchsuchende Gesamtstring, 
// in $Mark sind durch "*" getrennt der Beginn des zu suchenden Strings  
// und das Ende des zu suchende Abschnittes. 
// Beispiel für den Text "<div>*</div></li>" 
function GetMark($string, $mark) { 
	$find = explode("*",$mark); 
	$lens  = strlen($find[0]);
	$lene  = strlen($find[1]); 
	$start = strpos($string, $find[0]); 
	$stop  = strpos($string, $find[1], $start+$lens);
	$inner = substr($string, $start+$lens, $stop-$start-$lens);
	return $inner; 
} 

// Umwandlung einer Zahlenrepräsentations als String in eine echte Gleitkommazahl
function ToFloat($str) {
  if(strstr($str, ",")) { 
    $str = str_replace(".", "", $str); // replace dots (thousand seps) with blancs 
    $str = str_replace(",", ".", $str); // replace ',' with '.' 
  } 
  return floatval($str); // take some last chances with floatval 
}

################################################################################
?>