<?php

declare(strict_types=1);

################################################################################
# Script:   System.Dashboard.ips.php
# Version:  5.0.20230808
# Author:   Heiko Wilknitz (@Pitti)
#           Original von Horst (12.11.2010)
#           Angepasst für RasPi lueralba (31.3.2015)
#
# Meldungsanzeige im WebFront!
# Dieses Skript dient zur Verwaltung einer Meldungsliste im WebFront.
# Meldungen können hinzugefügt und entfernt werden. Es ist auch möglich,
# Meldungen zu einem bestimmten Zeitpunkt automatisch löschen zu lassen,
# sowie das Löschen von Meldungen durch Klick im WebFront zu aktivieren.
# Mit der Version 2.0 ist es möglich den Button zum Wechseln der Seite
# im Webfront zu benutzen (Typ 4).
#
# ------------------------------ Installation ----------------------------------
#
# Dieses Skript richtet automatisch alle nötigen Objekte bei manueller
# Ausführung ein. Eine weitere manuelle Ausführung setzt alle benötigten Objekte
# wieder auf den Ausgangszustand.
#
# - Neues Skript erstellen
# - Diesen PHP-Code hineinkopieren
# - Skript Abspeichern
# - Webfront ID eintragen (Abschnitt 'Konfiguration')
# - Skript Ausführen
#
# Meldung durch ein anderes Skript hinzufügen lassen:
# ---------------------------------------------------
#
# $number = IPS_RunScriptWaitEx(ObjektID, ['action' => 'add', 'text' => 'Test', 'expires' => time() + 60, 'removable' => true]);
# Die Rückgabe des Aufrufes ist die Identifikationsnummer der neuen Nachricht,
# bei Misserfolg wird der Wert 0 zurückgegeben.
#
# Parameter:
# - 'text': Meldungstext
# - 'expires' (optional): Zeitpunkt des automatischen Löschens der Meldung
#          als Unix-Timestamp. Ist der Wert kleiner als die aktuelle Timestamp,
#          wird nicht automatisch gelöscht.
# - 'removable' (optional): Meldung wird bei Klick auf Button gelöscht.
# - 'type' (optional): Art der Meldung ... 0 => Normal(grün),
#          1 => Fehler(rot), 2 => Warnung(gelb), 3 => Todo(blau), 4 => Goto(orange)
# - 'image' (optional): Name des WebFront-Icons (ipsIcon<name>), welches
#          für Meldung verwendet werden soll, Standard ist "Talk"
#          Doku:  https://www.symcon.de/service/dokumentation/komponenten/icons/
#          z.B. Clock, Gear, Alert, etc....
# - 'page' (optional): Nur in Verbindung mit Type 4 - Seitenname
#          HINWEIS: funktioniert nur ohne Parameter 'removable'!!
#
# Meldung durch ein anderes Skript löschen lassen:
# ------------------------------------------------
#
# $success = IPS_RunScriptWaitEx(ObjektID, ['action' => 'remove', 'number' => 123]);
# Bei erfolgreichem Löschen wird der Wert 1 zurückgegeben, bei Misserfolg der Wert 0.
#
# Parameter:
# - 'number': Identifikationsnummer der zu löschenden Meldung
#
# Meldung eines bestimmten Types löschen:
# ------------------------------------------------
#
# $success = IPS_RunScriptWaitEx(ObjektID, ['action' => 'removeType', 'type' => x]);
# Bei erfolgreichem Löschen wird der Wert 1 zurückgegeben, bei Misserfolg der Wert 0.
#
# Parameter:
# - 'type': Meldungstyp der gelöscht werden soll (x = 0|1|2|3|4)
#
# Alle vorhandenen Meldungen durch ein anderes Skript löschen lassen:
# -------------------------------------------------------------------
#
# $success = IPS_RunScriptWaitEx(ObjektID, ['action' => 'removeAll']);
# Bei erfolgreichem Löschen wird der Wert 1 zurückgegeben, bei Misserfolg der Wert 0.
#
# Eine Meldungen hinzufügen, welche bei Klick auf den Button die Seite wechselt:
# ------------------------------------------------------------------------------
#
# $id = IPS_RunScriptWaitEx(ObjektID , ['action' => 'add', 'text' => $text, 'type' => 4, 'image' => 'Telephone', 'page' => 'catAnrufe']);
# Der Parameter 'page' definiert zu welcher Seite im Webfront gewechselt werden soll.
# Der Name der Seite muss einer existierenden 'Element ID' im konfigurierten Webfront
# entsprechen (z.b. item2435). HINWEIS: Derzeit nicht für die Tile Visu verfügbar.
#
# ------------------------------ Changelog -------------------------------------
#
# 08.02.2017 - Initalversion (v1.0)
# 17.02.2018 - Neuer Typ 4 zum Wechseln der Seite bei Klick auf Button
#              Umstellung auf Webhook als Ersatz für extra Remove-Script (v2.0)
# 24.02.2018 - über 'fifo' kann man die Reihenfolge der Meldungsausgabe steuern
#            - der Zeitstemmpel wann die Meldung erzeugt wurde wird beim Hover
#              über das Icon angezeigt
#              Doku verbessert (v2.1)
# 26.02.2018 - Flag für Reihenfolge der Meldungsauflistung hinzugefügt (v2.2)
# 04.03.2018 - Hinterlegung einer URL auf den Button (eperimental) (v2.3)
# 21.02.2019 - 3 neue Flags füre die Manupilation der Darstellung hinzugefügt
#              'nomsg' für keine Meldungen, 'noico' für keine Icons und
#              'bfort' für Button vor Text (in Kombi mit NO-ICON) (v3.0)
# 08.04.2019 - Umbau zur Vereinigung der Meldungsverwaltung & Toolbar (v4.0)
# 11.01.2020 - Jede Meldung bekommt jetzt einen Timestamp bei Erzeugung (v4.1)
#              Aktivitäten(Notifications) addieren sich jetzt
# 04.03.2020 - Bugfix & Optimierungen; RemoveType neu implementiert (v4.2)
# 06.11.2020 - Notify um Sender erweitert um +/- zu ermöglichen (v4.3)
# 20.06.2020 - Flag für extra Switch Page Button (v4.4)
# 08.08.2023 - Tabelle für neue Kachel-Visu (v5.0)
#
# ----------------------------- Konfigruration ---------------------------------
#
# WebFront Configuration
$wfc = 52523;
# First In First Out - erste Meldung wird zuerst dargestellt, sonst
# letzte Meldung zuerst (LIFO).
$fifo = false;
# Flag, ob angezeigt werden soll das keine Meldung existiert.
$nomsg = false;
# Flag, ob Icons angezeigt werden soll.
$noico = false;
# Flag, ob Button vor Text angezeigt werden soll;
# nur in Kombi mit NO ICON Flag verwendbar
$bfort = false;
# Flag, ob Button für Switch Page angezeigt werden soll;
# nur in Kombi mit $bfort = false
$bpage = true;
# Profile Message Typen
$types = [
    [-1, 'Alle', '', 0x808080],
    [0, 'Info', '', 0x00FF00],
    [1, 'Fehler', '', 0xFF0000],
    [2, 'Warnung', '', 0xFFFF00],
    [3, 'Todo', '', 0x0000FF],
    [4, 'Goto', '', 0xFF8000],
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
    Install();
}
// WEBFRONT
elseif($_IPS['SENDER'] == 'WebFront') {
    SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
    if($_IPS['VALUE'] == -1) {
        $result = RemoveAllMessages();
    }
    else {
        $result = RemoveTypes($_IPS['VALUE']);
    }
    if($result != 1) {
        echo 'Fehler beim Löschen aufgetreten!';
    }
}
// SCRIPTAUSFUEHRUNG
elseif($_IPS['SENDER'] == 'RunScript') {
    $result = 0;
    switch ($_IPS['action']) {
        case 'notify':
            $name = isset($_IPS['name']) ? $_IPS['name'] : '';
            $ident = isset($_IPS['ident']) ? $_IPS['ident'] : -1;
            $count = isset($_IPS['count']) ? $_IPS['count'] : 0;
            $image = isset($_IPS['image']) ? $_IPS['image'] : 'Talk';
            $page = isset($_IPS['page']) ? $_IPS['page'] : '';
            if (!(is_string($page))) {
                $page = '';
            }
            if (!($image != '')) {
                $image = 'Talk';
            }
            if (is_string($name) && $name != '') {
                $result = ShowNotification($name, $ident, $count, $image, $page);
            }
            break;
        case 'add':
            $expires = isset($_IPS['expires']) ? $_IPS['expires'] : 0;
            $removable = isset($_IPS['removable']) ? $_IPS['removable'] : false;
            $text = isset($_IPS['text']) ? $_IPS['text'] : 'leer';
            $type = isset($_IPS['type']) ? $_IPS['type'] : 0;
            $image = isset($_IPS['image']) ? $_IPS['image'] : 'Talk';
            $page = isset($_IPS['page']) ? $_IPS['page'] : '';
            $timestamp = time();
            if (!($expires > time())) {
                $expires = 0;
            }
            if (!($removable === true)) {
                $removable = false;
            }
            if (!($type > 0)) {
                $type = 0;
            }
            if (!(is_string($page))) {
                $page = '';
            }
            if (!($image != '')) {
                $image = 'Talk';
            }
            if (is_string($text) && $text != '') {
                $result = AddMessage($text, $expires, $removable, $type, $image, $timestamp, $page);
            }
            break;
        case 'remove':
            $number = isset($_IPS['number']) ? $_IPS['number'] : -1;
            if ($number > 0) {
                $result = RemoveMessage($number);
            }
            break;
        case 'removeAll':
            $result = RemoveAllMessages();
            break;
        case 'removeType':
            $type = isset($_IPS['type']) ? $_IPS['type'] : -1;
            if ($type >= 0) {
                $result = RemoveTypes($type);
            }
            break;
    }
    echo $result;
}
// TIMER EVENT
elseif($_IPS['SENDER'] == 'TimerEvent') {
    $number = explode('#', IPS_GetName($_IPS['EVENT']));
    $number = $number[1];
    RemoveMessage($number);
}
// AUFRUF WEBHOOK
elseif($_IPS['SENDER'] == 'WebHook') {
    $result = 0;
    switch ($_GET['action']) {
        case 'remove':
            $number = isset($_GET['number']) ? $_GET['number'] : -1;
            if ($number > 0) {
                $result = RemoveMessage($number);
            }
            break;
        case 'switch':
            $page = isset($_GET['page']) ? $_GET['page'] : '';
            $wfc = isset($_GET['webfront']) ? $_GET['webfront'] : $wfc;
            if (is_string($page) && $page != '') {
                $split = explode(',', $page);
                $result = SwitchPage($wfc, $split[0]);
                if(isset($split[1]) && ($split[1] != '')) {
                    OpenPopup($wfc, $split[1]);
                }
            }
            break;
    }
    echo $result;
}

# ------------------------------ Funktionen ------------------------------------

// Alle Meldungen(Daten) löschen und Letzte Meldungsnummer auf 0 setzen
function RemoveAllMessages()
{
    $did = CreateVariableByName($_IPS['SELF'], 'Daten', 3);
    $lid = CreateVariableByName($_IPS['SELF'], 'Meldungsnummer', 1);
    $ids = IPS_GetChildrenIDs($_IPS['SELF']);
    if (IPS_SemaphoreEnter($_IPS['SELF'] . 'DataUpdate', 2000)) {
        foreach ($ids as $id) {
            if (IPS_EventExists($id) && substr(IPS_GetName($id), 0, 16) == 'Remove Message #') {
                IPS_DeleteEvent($id);
            }
        }
        SetValueString($did, json_encode([]));
        SetValueInteger($lid, 0);
        IPS_SemaphoreLeave($_IPS['SELF'] . 'DataUpdate');
        RenderMessages([]);
        RenderCard([]);
    }
    else {
        ThrowException('Could not remove all messages: Semaphore timeout!');
    }
    return 1;
}

// Alle Meldungen eines bestimmten Meldungstyp löschen.
function RemoveTypes($type)
{
    $did = CreateVariableByName($_IPS['SELF'], 'Daten', 3);
    $result = 0;
    if (IPS_SemaphoreEnter($_IPS['SELF'] . 'DataUpdate', 2000)) {
        $data = json_decode(GetValueString($did), true);
        foreach($data as $id => $val) {
            if($val['type'] == $type) {
                unset($data[$id]);
                $eid = @IPS_GetEventIDByName('Remove Message #' . $id, $_IPS['SELF']);
                if ($eid !== false) {
                    IPS_DeleteEvent($eid);
                }
            }
        }
        $result = 1;
        SetValueString($did, json_encode($data));
        IPS_SemaphoreLeave($_IPS['SELF'] . 'DataUpdate');
        RenderMessages($data);
        RenderCard($data);
    }
    else {
        ThrowException('Could not remove message type : ' . $type . '. Semaphore timeout!');
    }
    return $result;
}

// Meldung mit der Meldungsnummer(number) löschen.
function RemoveMessage($number)
{
    $did = CreateVariableByName($_IPS['SELF'], 'Daten', 3);
    $result = 0;
    if (IPS_SemaphoreEnter($_IPS['SELF'] . 'DataUpdate', 2000)) {
        $data = json_decode(GetValueString($did), true);
        if (isset($data[$number])) {
            unset($data[$number]);
            $eid = @IPS_GetEventIDByName('Remove Message #' . $number, $_IPS['SELF']);
            if ($eid !== false) {
                IPS_DeleteEvent($eid);
            }
            SetValueString($did, json_encode($data));
            $result = 1;
        }
        else {
            ThrowException('Could not remove message #' . $number . ': Unknown message number!');
        }
        IPS_SemaphoreLeave($_IPS['SELF'] . 'DataUpdate');
        RenderMessages($data);
        RenderCard($data);
    }
    else {
        ThrowException('Could not remove message #' . $number . ': Semaphore timeout!');
    }
    return $result;
}

// Neue Meldung hinzufügen
function AddMessage($text, $expires, $removable, $type, $image, $timestamp, $page)
{
    $did = CreateVariableByName($_IPS['SELF'], 'Daten', 3);
    $lid = CreateVariableByName($_IPS['SELF'], 'Meldungsnummer', 1);
    $number = 0;
    if (IPS_SemaphoreEnter($_IPS['SELF'] . 'DataUpdate', 2000)) {
        $data = json_decode(GetValueString($did), true);
        if (!is_array($data)) {
            $data = [];
        }
        $number = GetValueInteger($lid) + 1;
        $data[$number] = ['timestamp' => time(), 'text' => $text, 'expires' => $expires, 'removable' => $removable, 'type' => $type, 'image' => $image, 'timestamp' => $timestamp, 'page' => $page];
        if ($expires > time()) {
            $eid = IPS_CreateEvent(1);
            IPS_SetParent($eid, $_IPS['SELF']);
            IPS_SetName($eid, 'Remove Message #' . $number);
            IPS_SetEventCyclic($eid, 1, 0, 0, 0, 0, 0);
            IPS_SetEventCyclicDateFrom($eid, (int) date('j', $expires), (int) date('n', $expires), (int) date('Y', $expires));
            IPS_SetEventCyclicDateTo($eid, 0, 0, 0);
            IPS_SetEventCyclicTimeFrom($eid, (int) date('H', $expires), (int) date('i', $expires), (int) date('s', $expires));
            IPS_SetEventCyclicTimeTo($eid, 0, 0, 0);
            IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
            IPS_SetEventActive($eid, true);
        }
        SetValueString($did, json_encode($data));
        SetValueInteger($lid, $number);
        IPS_SemaphoreLeave($_IPS['SELF'] . 'DataUpdate');
        RenderMessages($data);
        RenderCard($data);
    }
    else {
        ThrowException('Could not add message: Semaphore timeout!');
    }
    return $number;
}

// Neue Benachrichtigung erhalten
function ShowNotification($name, $ident, $count, $image, $page)
{
    $ret = true;
    $did = CreateVariableByName($_IPS['SELF'], 'Nachrichten', 3);
    if (IPS_SemaphoreEnter($_IPS['SELF'] . 'NotifyUpdate', 2000)) {
        $json = json_decode(GetValueString($did), true);
        // safty check
        if (!is_array($json)) {
            $json = [];
        }
        if($ident != -1) {
            if(isset($json[$name])) {
                $data = $json[$name]['count'];
            }
            $data[$ident] = $count;
        }
        else {
            $data = [];
        }
        $json[$name] = ['count' => $data, 'image' => $image, 'page' => $page];
        SetValueString($did, json_encode($json));
        IPS_SemaphoreLeave($_IPS['SELF'] . 'NotifyUpdate');
        RenderNotifications($json);
    }
    else {
        ThrowException('Could not add notification: Semaphore timeout!');
        $ret = false;
    }
    return $ret;
}

// Umschalten zu einer bestimmten Seite im WebFront
function SwitchPage($wfc, $page)
{
    $result = WFC_SwitchPage($wfc, $page);
    return $result;
}

// Popup öffnen für Link-Anzeige (experimental)
function OpenPopup($wfc, $url)
{
    $result = WFC_SendNotification($wfc, 'Weiterleitung', $url, 'Internet', 5);
    return $result;
}

// Meldungen als HTML zusammenbauen.
function RenderMessages($data)
{
    global $fifo, $nomsg, $noico, $bfort, $bpage;
    $mid = CreateVariableByName($_IPS['SELF'], 'Meldungen', 3);
    $cnt = count($data);
    // Etwas CSS und HTML
    $style = '';
    $style = $style . '<style type="text/css">';
    if($cnt == 0 && $nomsg) {
        $style = $style . 'table.msg { width:100%;}';
    }
    else {
        $style = $style . 'table.msg { width:100%; border-collapse: collapse; border-spacing: 0px;}';
    }
    if($noico) {
        if($bfort) {
            $style = $style . '.msg td.btn { width: 49px; text-align:center; padding: 2px;  border-left: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
            $style = $style . '.msg td.txt { padding: 5px; border-right: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
        }
        else {
            $style = $style . '.msg td.txt { padding: 5px; border-left: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
            $style = $style . '.msg td.btn { width: 49px; text-align:center; padding: 2px; border-right: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
        }
    }
    else {
        $style = $style . '.msg td.ico { width: 36px; padding: 1px 0px 0px 0px; border-left: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
        $style = $style . '.msg td.txt { padding: 2px;  border-top: 1px solid rgba(255, 255, 255, 0.1); }';
        $style = $style . '.msg td.btn { width: 49px; text-align:center; padding: 2px 3px 2px 2px;  border-right: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
    }
    $style = $style . '.msg tr:last-child { border-bottom: 1px solid rgba(255, 255, 255, 0.2); }';
    $style = $style . '.blue {padding: 5px; color: rgb(255, 255, 255); background-color: rgb(0, 0, 255); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
    $style = $style . '.red {padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 0, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
    $style = $style . '.green {padding: 5px; color: rgb(255, 255, 255); background-color: rgb(0, 255, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
    $style = $style . '.yellow {padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 255, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
    $style = $style . '.orange {padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 160, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
    $style = $style . '</style>';
    $content = $style;
    $content = $content . '<table class="msg">';

    if ($cnt == 0) {
        // Keine Meldung, dann sagen wir das auch ;-)
        if(!$nomsg) {
            $content = $content . '<tr>';
            $class = 'ico';
            // Icon?
            if(!$noico) {
                $content = $content . '<td class="ico"><img src="img/icons/Ok.svg"></img></td>';
                $class = 'txt';
            }
            // Button vor Text
            if($noico && $bfort) {
                $content = $content . '<td class="btn"><div class="green" onclick="alert("Nachricht kann nicht bestätigt werden.");">OK</div></td>';
                $content = $content . '<td class="txt">Keine Meldungen vorhanden!</td>';
            }
            // Button nach Text
            else {
                $content = $content . '<td class="' . $class . '">Keine Meldungen vorhanden!</td>';
                $content = $content . '<td class="btn"><div class="green" onclick="alert("Nachricht kann nicht bestätigt werden.");">OK</div></td>';
            }
            $content = $content . '</tr>';
        }
        // Keine Meldung, keine Ausgabe
        else {
            $content = $content . '<tr><td></td></tr>';
        }
    }
    else {
        // fifo or lifo
        if(!$fifo) {
            $data = array_reverse($data, true);
        }
        foreach ($data as $number => $message) {
            if ($message['type']) {
                switch ($message['type']) {
                    case 4:
                        $type = 'orange';
                        break;
                    case 3:
                        $type = 'blue';
                        break;
                    case 2:
                        $type = 'yellow';
                        break;
                    case 1:
                        $type = 'red';
                        break;
                    default:
                        $type = 'green';
                        break;
                }
            }
            else {
                $type = 'green';
            }
            if ($message['image']) {
                $title = ' ';
                if (isset($message['timestamp'])) {
                    $title .= 'title="' . date('d.m.Y H:i', $message['timestamp']) . '" ';
                }
                $image = '<img src="img/icons/' . $message['image'] . '.svg"' . $title . '></img>';
            }
            else {
                $image = '<img src="img/icons/Ok.svg"></img>';
            }
            $content .= '<tr>';
            // Icon?
            if(!$noico) {
                $content = $content . '<td class="ico">' . $image . '</td>';
            }
            // Button vor Text
            if($noico && $bfort) {
                if ($message['removable']) {
                    $content = $content . '<td class="fst"><div class="' . $type . '" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/msg?ts=\' + (new Date()).getTime() + \'&action=remove&number=' . $number . '\' });">OK</div></td>';
                }
                elseif ($message['page']) {
                    $content = $content . '<td class="btn"><div class="' . $type . '" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/msg?ts=\' + (new Date()).getTime() + \'&action=switch&page=' . $message['page'] . '\' });">OK</div></td>';
                }
                else {
                    $content = $content . '<td class="btn"><div class="' . $type . '" onclick="alert(\'Nachricht kann nicht bestätigt werden.\');">OK</div></td>';
                }
                $content = $content . '<td class="txt">' . $message['text'] . '</td>';
            }
            // Button nach Text
            else {
                if ($message['page']) {
                    $content = $content . '<td class="txt"><div onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/msg?ts=\' + (new Date()).getTime() + \'&action=switch&page=' . $message['page'] . '\' });">' . $message['text'] . '</div></td>';
                } else {
                    $content = $content . '<td class="txt">' . $message['text'] . '</td>';
                }
                if ($message['removable']) {
                    $content = $content . '<td class="btn"><div class="' . $type . '" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/msg?ts=\' + (new Date()).getTime() + \'&action=remove&number=' . $number . '\' });">OK</div></td>';
                }
                else {
                    $content = $content . '<td class="btn"><div class="' . $type . '" onclick="alert(\'Nachricht kann nicht bestätigt werden.\');">OK</div></td>';
                }
            }
            $content .= '</tr>';
        }
    }
    $content = $content . '</table>';
    SetValueString($mid, $content);
}

// Meldungen als HTML Card zusammenbauen.
function RenderCard($data)
{
    global $fifo, $nomsg, $noico, $bfort, $bpage;
    $mid = CreateVariableByName($_IPS['SELF'], 'Texttafel', 3);
    $cnt = count($data);
    // Etwas CSS und HTML
    $style = '<meta name="viewport" content="width=device-width, initial-scale=1">';
    $style .= '<style type="text/css">';
    $style .= 'body {margin: 0px;}';
    $style .= '::-webkit-scrollbar { width: 8px; }';
    $style .= '::-webkit-scrollbar-track { background: transparent; }';
    $style .= '::-webkit-scrollbar-thumb { background: transparent; border-radius: 20px; }';
    $style .= '::-webkit-scrollbar-thumb:hover { background: #555; }';
    $style .= '.card { display:block; }';
    $style .= 'table.wwx { border-collapse: collapse; width: 100% }';
    $style .= '.wwx th, .wwx td { vertical-align: middle; text-align: left; padding: 5px; }';
    $style .= '.wwx tr { border-bottom: 2px solid #4A4B4D; }';
    $style .= '.wwx tr:nth-of-type(1) { border-top: 2px solid #4A4B4D; }';
    $style .= '.icon {width: 24px; height: 24px; }';
    $style .= 'span { font-size: 0.9em; }';
    $style .= '.blue {background-color: #11A0F3; }';
    $style .= '.green {background-color: #58A906; }';
    $style .= '.yellow {background-color: #FFC107; }';
    $style .= '.red {background-color: #F35A2C; }';
    $style .= '.orange {background-color: #FF9800; }';
    $style .= '.button { cursor: pointer; border-radius: 5px; min-width: 2.5em; text-align: center; }';
    $style .= '</style>';
    // Sart Content
    $content = $style;
    $content .= '<div class="card">';
    $content .= '<table class="wwx">';

    if ($cnt == 0) {
        // Keine Meldung, dann sagen wir das auch ;-)
        if(!$nomsg) {
            $content = $content . '<tr>';
            // Icon?
            if(!$noico) {
                $content = $content . '<td><img class="icon" src="/img/icons/Ok.svg"></img></td>';
            }
            // Button vor Text
            if($noico && $bfort) {
                $content = $content . '<td><div class="button green" onclick="alert(\'Nachricht kann nicht bestätigt werden.\');">OK</div></td>';
                $content = $content . '<td><span>Keine Meldungen vorhanden!</span></td>';
            }
            // Button nach Text
            else {
                $content = $content . '<td><span>Keine Meldungen vorhanden!</span></td>';
                $content = $content . '<td><div class="button green" onclick="alert(\'Nachricht kann nicht bestätigt werden.\');">OK</div></td>';
            }
            $content = $content . '</tr>';
        }
        // Keine Meldung, keine Ausgabe
        else {
            $content = '';
        }
    }
    else {
        // fifo or lifo
        if(!$fifo) {
            $data = array_reverse($data, true);
        }
        foreach ($data as $number => $message) {
            if ($message['type']) {
                switch ($message['type']) {
                    case 4:
                        $type = 'orange';
                        break;
                    case 3:
                        $type = 'blue';
                        break;
                    case 2:
                        $type = 'yellow';
                        break;
                    case 1:
                        $type = 'red';
                        break;
                    default:
                        $type = 'green';
                        break;
                }
            }
            else {
                $type = 'green';
            }
            if ($message['image']) {
                $title = ' ';
                if (isset($message['timestamp'])) {
                    $title .= 'title="' . date('d.m.Y H:i', $message['timestamp']) . '" ';
                }
                $image = '<img class="icon" src="/img/icons/' . $message['image'] . '.svg"' . $title . '></img>';
            }
            else {
                $image = '<img class="icon" src="/img/icons/Ok.svg"></img>';
            }
            $content .= '<tr>';
            // Icon?
            if(!$noico) {
                $content = $content . '<td>' . $image . '</td>';
            }
            // Button vor Text
            if($noico && $bfort) {
                if ($message['removable']) {
                    $content = $content . '<td><div class="button ' . $type . '" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/msg?ts=\' + (new Date()).getTime() + \'&action=remove&number=' . $number . '\' });">OK</div></td>';
                }
                else {
                    $content = $content . '<td><div class="button ' . $type . '" onclick="alert(\'Nachricht kann nicht bestätigt werden.\');">OK</div></td>';
                }
                $content = $content . '<td><span>' . $message['text'] . '</span></td>';
            }
            // Button nach Text
            else {
                $content = $content . '<td><span>' . $message['text'] . '</span></td>';
                if ($message['removable']) {
                    $content = $content . '<td><div class="button ' . $type . '" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/msg?ts=\' + (new Date()).getTime() + \'&action=remove&number=' . $number . '\' });">OK</div></td>';
                }
                else {
                    $content = $content . '<td><div class="button ' . $type . '" onclick="alert(\'Nachricht kann nicht bestätigt werden.\');">OK</div></td>';
                }
            }
            $content .= '</tr>';
        }
    }
    $content .= '</table>';
    $content .= '</div>';
    SetValueString($mid, $content);
}

// Nachrichten als HTML zusammenbauen.
function RenderNotifications($data)
{
    $aid = CreateVariableByName($_IPS['SELF'], 'Aktivitäten', 3);
    $cnt = count($data);
    $msg = 'onclick="hook(\'<PAGE>\');"';
    // HTML & Style
    $html = '';
    $html = $html . '<style type="text/css">';
    $html = $html . '.ibox {overflow:hidden; display: flex;}';
    $html = $html . '.ib {cursor: pointer; width:63px}';
    $html = $html . '.button {cursor: pointer; display:inline-block; width:91px; text-align: center; border:1px solid rgba(255, 255, 255, 0.1); margin-right:10px; font-size:small;}';
    $html = $html . '.trans {padding: 5px; color: rgb(255, 255, 255); background-color: #0d1724; background-image: linear-gradient(to bottom,rgba(255,255,255,0.18) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%);}';
    $html = $html . '.badge {position:relative;}';
    $html = $html . '.badge[data-badge]:after {content:attr(data-badge); position:absolute; top:5px; right:5px; font-size:1em; background-color: red; background-image: linear-gradient(to bottom, #FF6969 0%,#ff0000 100%); color:white; width:20px;height:20px;text-align:center;line-height:20px; border:2px solid white;border-radius:50%; box-shadow:0 0 1px #333; text-shadow:0 -1px 0 rgba(0,0,0,.6);}';
    $html = $html . '.badge[data-badge="0"]:after{content:none;}';
    $html = $html . '.badge[data-badge=""]:after{content:none;}';
    $html = $html . '</style>';
    $html = $html . '<script type="text/javascript">';
    $html = $html . 'let webfronts = [[1, 52523], [0.8, 37741], [1.25, 39749]];';
    $html = $html . 'let bs = document.querySelector("body");';
    $html = $html . 'var zoom = getComputedStyle(bs).zoom;';
    $html = $html . 'var wf = webfronts[0][0];';
    $html = $html . 'for (i = 0; i < webfronts.length; i++) { if (webfronts[i][0] == zoom) wf = webfronts[i][1]; }';
    $html = $html . 'window.xhrGet = function xhrGet(o) { var HTTP = new XMLHttpRequest(); HTTP.open(\'GET\', o.url, true); HTTP.send(); };';
    $html = $html . 'function hook(p) { window.xhrGet({url: \'hook/msg?action=switch&page=\' + p + \'&webfront=\' + wf});}';
    $html = $html . '</script>';
    $html = $html . '<div class="ibox">';
    foreach ($data as $key => $value) {
        $click = str_replace('<PAGE>', $value['page'], $msg);
        $style = '';
        if($key === array_key_last($data)) {
            $style = 'style="margin-right: 0px;"';
        }
        $cnt = 0;
        foreach($value['count'] as $ident => $count) {
            $cnt += $count;
        }
        $html = $html . '<div class="trans button badge" data-badge="' . $cnt . '" ' . $click . $style . '><img class="ib" src="img/icons/' . $value['image'] . '.svg" /><span>' . $key . '</span></div>';
    }
    $html = $html . '</div>';
    SetValue($aid, $html);
}

// Installationsroutine zum Erzeugen aller notwendigen Variablen.
function Install()
{
    global $types;
    // Profil erzeugen
    $vpn = 'Message.Type';
    CreateProfileInteger($vpn, 'Talk', '', '', 0, 0, 0, 0, $types);
    // Variablen erzeugen
    $pos = 1;
    $vid = CreateVariableByName($_IPS['SELF'], 'Daten', 3, '', $pos++);
    IPS_SetHidden($vid, true);
    $vid = CreateVariableByName($_IPS['SELF'], 'Nachrichten', 3, '', $pos++);
    IPS_SetHidden($vid, true);
    SetValueString($vid, json_encode([]));
    $vid = CreateVariableByName($_IPS['SELF'], 'Aktivitäten', 3, '~HTMLBox', $pos++);
    $vid = CreateVariableByName($_IPS['SELF'], 'Meldungen', 3, '~HTMLBox', $pos++);
    $vid = CreateVariableByName($_IPS['SELF'], 'Meldungsnummer', 1, '', $pos++);
    SetValueInteger($vid, 0);
    $vid = CreateVariableByName($_IPS['SELF'], 'Meldungstyp', 1, $vpn, $pos++, $_IPS['SELF']);
    SetValueInteger($vid, -1);
    $vid = CreateVariableByName($_IPS['SELF'], 'Texttafel', 3, '~TextBox', $pos++);
    $ids = IPS_GetChildrenIDs($_IPS['SELF']);
    foreach ($ids as $id) {
        if (IPS_EventExists($id) && substr(IPS_GetName($id), 0, 16) == 'Remove Message #') {
            IPS_DeleteEvent($id);
        }
    }
    RenderCard([]);
    RenderMessages([]);
    RenderNotifications([]);
}

// Fehlerbehandlung
function ThrowException($message)
{
    IPS_LogMessage(IPS_GetName($_IPS['SELF']), 'MSG:' . $message);
}

################################################################################