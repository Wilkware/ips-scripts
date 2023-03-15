<?php

declare(strict_types=1);

################################################################################
# Script:   <category>.<name>.ips.php
# Version:  1.0.yyyymmdd
# Author:   Heiko Wilknitz (@Pitti)
#
# Template für alle Scripts in IPS => Kurzbeschreibung was das Skript tut!
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
# dd.mm.yyyy - Initalversion (v1.0)
#
# ------------------------------ Konfiguration ---------------------------------
#
# Global Debug Output Flag
$DEBUG = true;
#
# Profiles
$profile = [
    [1, '►', '', 0xff8000],
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
    // CreateVariableByName($id, $name, $type, $pos = 0, $icon = '', $profile = '', $action = null)
    // $vid = CreateVariableByName($_IPS['SELF'], "Variable 1", 3, 0, '', '~HTMLBox');
    // $vpn = 'PROFIL.name';
    // CreateProfileInteger($vpn, 'Speedo', '', '', 1, 3, 0, $profile);
    // z.B. Mitternachtsupdate
    // $midnight = mktime(0, 5, 0);
    // $eid = CreateEventByName($_IPS['SELF'], "Midnight Update", $midnight);
    // Timer setzen
    // CreateTimerByName($_IPS['SELF'], "Reminder Notification", 180);
    // Script Timer
    // IPS_SetScriptTimer($_IPS['SELF'], 10);
    // CreateCategoryByName($_IPS['SELF'], 'Test');
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
    $eid = 0; // CreateEventByName || CreateTimerByName
    if ($eid == $_IPS['EVENT']) {
        // Spezial Event/Timer?
    } else {
        // ScriptTimer
    }
}
// AUFRUF WEBHOOK
elseif ($_IPS['SENDER'] == 'WebHook') {
    // ToDo
}

#----------------------------------- Functions ---------------------------------

// Declare script specific functions here!

################################################################################
