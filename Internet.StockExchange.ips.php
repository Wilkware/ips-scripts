<?
################################################################################
# Scriptbezeichnung: Internet.StockExchange.ips.php
# Version:	2.0.20171205
# Author:	Heiko Wilknitz (@Pitti)
#
# Börsenticker:
# 	JSON - Google	
# 		https://finance.google.com/finance
# 		ETR:xxx => Xetra
# 		FRA:xxx => Frankfurt
# 		https://finance.google.com/finance?q=ETR:ADN1
# 		https://finance.google.com/finance?q=FRA:ADN1
#
#
# Installation:
#	- WKN-Array unter Konfiguration mit den entsprechenden Werten befüllen
#	- Script in der Konsole ausführen
#
# ------------------------------ Konfiguration ---------------------------------
#
$wkn = array(
	"FRA:ADN1"		=> "adesso AG"	//google
);
#
# ----------------------------------- ID´s -------------------------------------
#
#
################################################################################


if ($_IPS['SENDER'] == "Execute") {
	// ID des ArchiveHandler ermitteln 
	$instances = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}'); 
	$id_archive_handler = $instances[0]; 
	// pro WKN eine Variable
	foreach($wkn as $ident => $name) {
		$vid = CreateVariableByName($_IPS['SELF'], $name, 2 /*Float*/);
		IPS_SetInfo($vid, $ident);
		IPS_SetIcon($vid, "Graph");
		AC_SetLoggingStatus($id_archive_handler, $vid, true);
	}
}


if($_IPS['SENDER'] == "TimerEvent") {
	// alle untergeordneten Objekt einsammeln
	$ids = IPS_GetChildrenIDs($_IPS['SELF']);
	// echo print_r($ids);
	foreach ($ids as $id) {
		// for each Wertkennzeichen daten holen
		$array = IPS_GetObject($id);
		// Float-Variable?
		if ($array['ObjectType'] == 2) {
			// Google
			$url = 'https://finance.google.com/finance?q='.$array['ObjectInfo'].'&output=json';
			$req = file_get_contents($url, false, NULL, 4);	// Lesen der Daten.
			//var_dump($req);
			$dec = json_decode($req, true);
			//var_dump($dec);
			if($dec[0]["l"] > 0) {
				SetValue($id, $dec[0]["l"]);
			}
		}		
	}
}	

# ------------------------------ Funktionen ------------------------------------

function CreateVariableByName($id, $name, $type) 
{ 
   $vid = @IPS_GetVariableIDByName($name, $id); 
   if($vid===false) { 
      $vid = IPS_CreateVariable($type); 
      IPS_SetParent($vid, $id); 
      IPS_SetName($vid, $name); 
   } 
   return $vid; 
} 

function CreateEventByName($id, $name, $type) 
{ 
   $eid = @IPS_GetEventIDByName($name, $id); 
   if($eid===false) { 
      $eid = IPS_CreateEvent($type); 
      IPS_SetParent($eid, $id); 
      IPS_SetName($eid, $name); 
   } 
   return $eid; 
}

################################################################################
?>