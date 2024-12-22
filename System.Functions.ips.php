<?php

declare(strict_types=1);

################################################################################
# Script:   System.Functions.ips.php
# Version:  4.1.20240304
# Author:   Heiko Wilknitz (@Pitti)
#
# Basisfunktionen für einfache Scripterstellung!
#
# ------------------------------ Changelog -------------------------------------
#
# 09.05.2019 - Initalversion (v1.0)
# 01.08.2022 - CreateIdent hinzugefügt (v2.0)
# 21.01.2023 - Definition der WWX-Konstante (v2.1)
# 15.03.2023 - RegisterArchive, UnregisterArchive, UnregisterProfil,
#              ExtractGuid, CreateEventByNameFromTo
#              CreateIdent um Option erweitert
#              CreateProfilInteger korrigiert (ACHTUNG)
#              Dokumentation umgestellt (v3.0)
# 05.10.2023 - GetObjectByIdent und GetObjectByName hinzugefügt
#              GetCategoryByName, GetEventByName, GetScriptByName
#              GetDummyByName, GetDummyByIdent
#              GetPopupByName, GetPopupByIdent,
#              GetVariableByName und GetVariableByName hinzugefügt  (v4.0)
# 04.03.2024 - Kleine Anpassungen für Events (v4.1)
#
################################################################################

// Globale Definition der Bibliothek
define('WWX_FUNCTIONS', true);

/**
 * Liefert die GUID für den übergebene Modul bzw. Aktion
 *
 * @param string $name Name des IPS Moduls oder Automation
 *
 * @return string GUID, e.g. '{43192F0B-135B-4CE7-A0A7-1475603F3060}'
 */
function ExtractGuid($name)
{
    // Modules
    $guids = IPS_GetModuleList();
    $result = false;
    foreach ($guids as $guid) {
        $module = IPS_GetModule($guid);
        if ($module['ModuleName'] == $name) {
            return $guid;
        }
    }
    // Actions/Automations
    $actions = json_decode(IPS_GetActions(), true);
    $matching = [];
    foreach ($actions as $action) {
        if ($action['caption'] == $name && count($action['form']) == 0) {
            $matching[] = $action;
        }
    }
    if (count($matching) == 1) {
        return $matching[0]['id'];
    }
    throw new Exception('GUID does not exist for ' . $name);
}

/**
 * Erzeugt aus dem übergebenen Namen ein IPS konformen IDENT
 *
 * @param string $name Name für den Variablen-Ident
 * @param bool $lower Wenn true, dann nur nur kleingeschriebene Idents.
 *
 * @return string Ident
 */
function CreateIdent($name, $lower = false)
{
    $umlaute = ['/ä/', '/ö/', '/ü/', '/Ä/', '/Ö/', '/Ü/', '/ß/'];
    $replace = ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'];
    $ident = preg_replace($umlaute, $replace, $name);
    // idents immer klein?!?
    if ($lower) {
        $ident = strtolower($ident);
    }
    return preg_replace('/[^a-z0-9_]+/i', '', $ident);
}

/**
 * Liefert die ID eines Objektes unterhalb {id} mit dem Ident {ident}.
 *
 * @param int $id ID unter welchem das Objekt gesucht werden soll.
 * @param string $ident Ident des zu suchenden Objektes
 * @param bool $internal Nutzung der internen Ident-Funktion
 *
 * @return int|false ID des gefundenen Objektes, andernfalls false.
 */
function GetObjectByIdent($id, $ident, $internal = true)
{
    if ($internal) {
        $ident = CreateIdent($ident);
    }
    return @IPS_GetObjectIDByIdent($ident, $id);
}

/**
 * Liefert die ID eines Objektes unterhalb {id} mit dem Namen {name}.
 *
 * @param int $id ID unter welchem das Objekt gesucht werden soll.
 * @param string $name Name des zu suchenden Objektes
 *
 * @return int|false ID des gefundenen Objektes, andernfalls false.
 */
function GetObjectByName($id, $name)
{
    return @IPS_GetObjectIDByName($name, $id);
}

/**
 * Erzeugt eine Kategorie unterhalb {id} mit dem Namen {name}
 * Existiert die Kategorie schon wird diese zurückgeliefert.
 *
 * @param int $id ID unter welchem die Kategorie erzeugt werden soll.
 * @param string $name Name der zu erzeugenden Kategorie
 * @param int $pos 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
 * @param string $icon Zuweisung eines Icons mit dem übergebenen Namen
 *
 * @return int ID der bestehenden oder neu angelegten Kategorie
 */
function CreateCategoryByName($id, $name, $pos = 0, $icon = '')
{
    $cid = GetCategoryByName($id, $name);
    if ($cid === false) {
        $cid = IPS_CreateCategory();
        IPS_SetName($cid, $name);
        IPS_SetParent($cid, $id);
        IPS_SetPosition($cid, $pos);
        IPS_SetIcon($cid, $icon);
    }
    return $cid;
}

/**
 * Liefert die ID eine Kategorie unterhalb {id} mit dem Namen {name}.
 *
 * @param int $id ID unter welchem die Kategorie erzeugt werden soll.
 * @param string $name Name der zu erzeugenden Kategorie
 *
 * @return int|false ID der gefundenen Kategorie, andernfalls false.
 */
function GetCategoryByName($id, $name)
{
    return @IPS_GetCategoryIDByName($name, $id);
}

/**
 * Erzeugt ein Dummy Modul unterhalb {id} mit dem Namen {name}
 * Existiert das Modul schon wird diese zurückgeliefert.
 *
 * @param int $id ID unter welchem das Dummy Modulserzeugt werden soll.
 * @param string $name Name des zu erzeugenden Dummy Moduls
 * @param int $pos 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
 * @param string $icon Zuweisung eines Icons mit dem übergebenen Namen
 *
 * @return int ID des bestehenden oder neu angelegten Dummy Moduls
 */
function CreateDummyByName($id, $name, $pos = 0, $icon = '')
{
    return CreateDummyByIdent($id, $name, $name, $pos, $icon);
}

/**
 * Liefert die ID eines Dummy Moduls unterhalb {id} mit dem Namen {name}.
 *
 * @param int $id ID unter welchem das Dummy Moduls erzeugt wurde.
 * @param string $name Name des zu suchenden Dummy Moduls
 *
 * @return int|false ID des gefundenen Dummy Moduls, andernfalls false.
 */
function GetDummyByName($id, $name)
{
    return GetObjectByName($id, $name);
}

/**
 * Erzeugt ein Dummy Modul unterhalb {id} mit dem Namen {name} und Ident {ident}
 * Existiert das Modul schon wird diese zurückgeliefert.
 *
 * @param int $id ID unter welchem das Dummy Moduls erzeugt werden soll.
 * @param string $ident Ident des zu erzeugenden Dummy Moduls
 * @param string $name Name des zu erzeugenden Dummy Moduls
 * @param int $pos 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
 * @param string $icon Zuweisung eines Icons mit dem übergebenen Namen
 *
 * @return int ID des bestehenden oder neu angelegten Dummy Moduls
 */
function CreateDummyByIdent($id, $ident, $name, $pos = 0, $icon = '')
{
    $ident = CreateIdent($ident);
    $did = GetObjectByIdent($id, $ident);
    if ($did === false) {
        $did = IPS_CreateInstance(ExtractGuid('Dummy Module'));
        IPS_SetName($did, $name);
        IPS_SetIdent($did, $ident);
        IPS_SetParent($did, $id);
        IPS_SetPosition($did, $pos);
        IPS_SetIcon($did, $icon);
    }
    return $did;
}

/**
 * Liefert die ID eines Dummy Moduls unterhalb {id} mit dem Ident {ident}.
 *
 * @param int $id ID unter welchem das Dummy Moduls erzeugt wurde.
 * @param string $ident Ident des zu suchenden Dummy Moduls
 *
 * @return int|false ID des gefundenen Dummy Moduls, andernfalls false.
 */
function GetDummyByIdent($id, $ident)
{
    return GetObjectByIdent($id, $ident);
}

/**
 * Erzeugt ein Popup Modul unterhalb {id} mit dem Namen {name}
 * Existiert das Modul schon wird diese zurückgeliefert.
 *
 * @param int $id ID unter welchem das Popup Moduls erzeugt werden soll.
 * @param string $name Name des zu erzeugenden Popup Moduls
 * @param int $pos 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
 * @param string $icon Zuweisung eines Icons mit dem übergebenen Namen
 *
 * @return int ID des bestehenden oder neu angelegten Popup Moduls
 */
function CreatePopupByName($id, $name, $pos = 0, $icon = '')
{
    return CreatePopupByName($id, $name, $name, $pos, $icon);
}

/**
 * Liefert die ID eines Popup Moduls unterhalb {id} mit dem Namen {name}.
 *
 * @param int $id ID unter welchem das Popup Modul erzeugt wurde.
 * @param string $name Name des zu suchenden Popup Moduls
 *
 * @return int|false ID des gefundenen Popup Moduls, andernfalls false.
 */
function GetPopupByName($id, $name)
{
    return GetObjectByName($id, $name);
}

/**
 * Erzeugt ein Popup Modul unterhalb {id} mit dem Namen {name} und Ident {ident}
 * Existiert das Modul schon wird diese zurückgeliefert.
 *
 * @param int $id ID unter welchem das Popup Moduls erzeugt werden soll.
 * @param string $ident Ident des zu erzeugenden Popup Moduls
 * @param string $name Name des zu erzeugenden Popup Moduls
 * @param int $pos 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
 * @param string $icon Zuweisung eines Icons mit dem übergebenen Namen
 *
 * @return int ID des bestehenden oder neu angelegten Popup Moduls
 */
function CreatePopupByIdent($id, $ident, $name, $pos = 0, $icon = '')
{
    $ident = CreateIdent($ident);
    $pid = GetObjectByIdent($id, $ident);
    if ($pid === false) {
        $pid = IPS_CreateInstance(ExtractGuid('Popup Module'));
        IPS_SetName($pid, $name);
        IPS_SetIdent($pid, $ident);
        IPS_SetParent($pid, $id);
        IPS_SetPosition($pid, $pos);
        IPS_SetIcon($pid, $icon);
    }
    return $pid;
}

/**
 * Liefert die ID eines Popup Moduls unterhalb {id} mit dem Ident {ident}.
 *
 * @param int $id ID unter welchem das Popup Modul erzeugt wurde.
 * @param string $ident Ident des zu suchenden Popup Moduls
 *
 * @return int|false ID des gefundenen Popup Moduls, andernfalls false.
 */
function GetPopupByIdent($id, $ident)
{
    return GetObjectByIdent($id, $ident);
}

/**
 * Erzeugt eine Variable unterhalb {id} mit dem Namen {name} vom Typ {type}
 * Existiert die Variable schon wird diese zurückgeliefert.
 *
 * @param int $id ID unter welchem die Variable erzeugt werden soll.
 * @param string $name Name der zu erzeugenden Variable
 * @param int $type Typ der Variable (0 = Boolean, 1 = Integer, 2 = Float, 3 = String)
 * @param int $pos 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
 * @param string $icon Zuweisung eines Icons mit dem übergebenen Namen
 * @param string $profil Leer für kein Profil, sonst Profilnamen
 * @param int $action ID der benutzerdefinierten Aktion (Auslösung via Webfront)
 *
 * @return int ID der bestehenden oder neu angelegten Variable
 */
function CreateVariableByName($id, $name, $type, $pos = 0, $icon = '', $profile = '', $action = null)
{
    $vid = GetVariableByName($id, $name);
    if ($vid === false) {
        $vid = IPS_CreateVariable($type);
        IPS_SetParent($vid, $id);
        IPS_SetName($vid, $name);
        IPS_SetPosition($vid, $pos);
        IPS_SetIcon($vid, $icon);
        if ($profile !== '') {
            IPS_SetVariableCustomProfile($vid, $profile);
        }
        if ($action != null) {
            IPS_SetVariableCustomAction($vid, $action);
        }
    }
    return $vid;
}

/**
 * Liefert die ID einer Variable unterhalb {id} mit dem Namen {name}.
 *
 * @param int $id ID unter welchem die Variable erzeugt wurde.
 * @param string $name Name der zu suchenden Variable
 *
 * @return int|false ID der gefundenen Variable, andernfalls false.
 */
function GetVariableByName($id, $name)
{
    return @IPS_GetVariableIDByName($name, $id);
}

/**
 * Erzeugt eine Variable unterhalb {id} mit dem Namen {name} vom Typ {type}
 * Existiert die Variable schon wird diese zurückgeliefert.
 *
 * @param int $id ID unter welchem die Variable erzeugt werden soll.
 * @param string $ident Ident der zu erzeugenden Variable
 * @param string $name Name der zu erzeugenden Variable
 * @param int $type Typ der Variable (0 = Boolean, 1 = Integer, 2 = Float, 3 = String)
 * @param int $pos 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
 * @param string $icon Zuweisung eines Icons mit dem übergebenen Namen
 * @param string $profil Leer für kein Profil, sonst Profilnamen
 * @param int $action ID der benutzerdefinierten Aktion (Auslösung via Webfront)
 *
 * @return int ID der bestehenden oder neu angelegten Variable
 */
function CreateVariableByIdent($id, $ident, $name, $type, $pos = 0, $icon = '', $profile = '', $action = null)
{
    $ident = CreateIdent($ident);
    $vid = GetObjectByIdent($id, $ident);
    if ($vid === false) {
        $vid = IPS_CreateVariable($type);
        IPS_SetParent($vid, $id);
        IPS_SetName($vid, $name);
        IPS_SetIdent($vid, $ident);
        IPS_SetPosition($vid, $pos);
        IPS_SetIcon($vid, $icon);
        if ($profile !== '') {
            IPS_SetVariableCustomProfile($vid, $profile);
        }
        if ($action != null) {
            IPS_SetVariableCustomAction($vid, $action);
        }
    }
    return $vid;
}

/**
 * Liefert die ID einer Variable unterhalb {id} mit dem Ident {ident}.
 *
 * @param int $id ID unter welchem die Variable erzeugt wurde.
 * @param string $ident Ident der zu suchenden Variable
 *
 * @return int|false ID der gefundenen Variable, andernfalls false.
 */
function GetVariableByIdent($id, $ident)
{
    return GetObjectByIdent($id, $ident);
}

/**
 * Erzeugt ein Event unterhalb {id} mit dem Namen {name} um Zeit {time}
 * Existiert das Event schon wird diese zurückgeliefert.
 * Hinweis: $time = mktime(hour, minute, second);
 *
 * @param int $id ID unter welchem das Event erzeugt werden soll.
 * @param string $name Name des zu erzeugenden Events
 * @param int $time Uhrzeit an welchen das Event eintreten soll.
 *
 * @return int ID des bestehenden oder neu erzeugten Events
 */
function CreateEventByName($id, $name, $time = 0)
{
    $eid = GetEventByName($id, $name);
    if (($eid === false) && ($time > 0)) {
        // Eventtyp = Zyklisch (1)
        $eid = IPS_CreateEvent(1);
        IPS_SetParent($eid, $id);
        IPS_SetName($eid, $name);
        IPS_SetPosition($eid, -2);
        IPS_SetHidden($eid, true);
    }
    if ($time > 0) {
        // Datumstyp = Tägliche (2), Datumsintervall = keins (1), Datumstage = keine (0), Datumstagesintervall = keine (0), Zeittyp = Einmalig (0), Zeitintervall = keine (0)
        IPS_SetEventCyclic($eid, 2, 1, 0, 0, 0, 0);
        IPS_SetEventCyclicDateFrom($eid, (int) date('j', $time), (int) date('n', $time), (int) date('Y', $time));
        IPS_SetEventCyclicDateTo($eid, 0, 0, 0);
        IPS_SetEventCyclicTimeFrom($eid, (int) date('H', $time), (int) date('i', $time), (int) date('s', $time));
        IPS_SetEventCyclicTimeTo($eid, 0, 0, 0);
        IPS_SetEventActive($eid, true);
        if (function_exists('IPS_SetEventAction')) {
            IPS_SetEventAction($eid, ExtractGuid('Run Automation'), []);
        }
    }
    return $eid;
}

/**
 * Erzeugt ein Event unterhalb {id} mit dem Namen {name} in der Zeit {from} bis {to}
 * Existiert das  Event schon wird diese zurückgeliefert.
 * Hinweis: $from/$to = mktime(hour, minute, second);
 *
 * @param int $id ID unter welchem das Event erzeugt werden soll.
 * @param string $name Name des zu erzeugenden Events
 * @param int $type Zeittyp (1 Sekündlich,2 Minütlichm, 3 Stündlich)
 * @param int $interval Zeitinterval (1 Alle X Sekunden, 2 Alle X Minuten, 3 Alle X Stunden)
 * @param int $from Uhrzeit an welchen das Event starten soll.
 * @param int $time Uhrzeit an welchen das Event stoppen soll.
 *
 * @return int ID des bestehenden oder neu erzeugten Events
 */
function CreateEventByNameFromTo($id, $name, $type, $interval, $from, $to)
{
    $eid = GetEventByName($id, $name);
    if (($eid === false) && ($type > 0) && ($interval > 0)) {
        // Eventtyp = Zyklisch (1)
        $eid = IPS_CreateEvent(1);
        IPS_SetParent($eid, $id);
        IPS_SetName($eid, $name);
        IPS_SetPosition($eid, -2);
        IPS_SetHidden($eid, true);
    }
    if ($from > 0 && $to > 0) {
        // Datumstyp = Tägliche (2), Datumsintervall = keins (1), Datumstage = keine (0), Datumstagesintervall = keine (0), Zeittyp = Einmalig (0), Zeitintervall = keine (0)
        IPS_SetEventCyclic($eid, 2, 1, 0, 0, $type, $interval);
        IPS_SetEventCyclicTimeFrom($eid, (int) date('H', $from), (int) date('i', $from), (int) date('s', $from));
        IPS_SetEventCyclicTimeTo($eid, (int) date('H', $to), (int) date('i', $to), (int) date('s', $to));
        IPS_SetEventActive($eid, true);
        if (function_exists('IPS_SetEventAction')) {
            IPS_SetEventAction($eid, ExtractGuid('Run Automation'), []);
        }
    }
    return $eid;
}

/**
 * Erzeugt einen Timer unterhalb {id} mit dem Namen {name} um die Zeit {time}
 * Existiert der Timer schon wird diese zurückgeliefert.
 * Tägliche Ausführung: $time in Minuten
 * Einmalige Ausführung: $time mit mktime(hour, minute, second) übergeben.
 *
 * @param int $id ID unter welchem das Timers erzeugt werden soll.
 * @param string $name Name des zu erzeugenden Timers
 * @param int $time Uhrzeit bzw. Zeitpunkt des Timers.
 *
 * @return int ID des bestehenden oder neu erzeugten Timers
 */
function CreateTimerByName($id, $name, $time = 0, $repeat = true)
{
    $eid = GetEventByName($id, $name);
    if (($eid === false) && ($time > 0)) {
        // Eventtyp = Zyklisch (1)
        $eid = IPS_CreateEvent(1);
        IPS_SetParent($eid, $id);
        IPS_SetName($eid, $name);
        IPS_SetPosition($eid, -1);
        IPS_SetHidden($eid, true);
    }
    if (($eid !== false) && ($time > 0)) {
        if ($repeat == true) {
            // 0 = Tägliche Ausführung
            IPS_SetEventCyclic($eid, 0, 1, 0, 0, 2, $time);
            $now = time();
            IPS_SetEventCyclicDateFrom($eid, (int) date('j', $now), (int) date('n', $now), (int) date('Y', $now));
        } else {
            // Einmalige Ausführung
            IPS_SetEventCyclic($eid, 1, 0, 0, 0, 0, 0);
            IPS_SetEventCyclicDateFrom($eid, (int) date('j', $time), (int) date('n', $time), (int) date('Y', $time));
            IPS_SetEventCyclicDateTo($eid, (int) date('j', $time), (int) date('n', $time), (int) date('Y', $time));
            IPS_SetEventCyclicTimeFrom($eid, (int) date('H', $time), (int) date('i', $time), (int) date('s', $time));
            IPS_SetEventCyclicTimeTo($eid, (int) date('H', $time), (int) date('i', $time), (int) date('s', $time));
        }
        IPS_SetEventActive($eid, true);
        if (function_exists('IPS_SetEventAction')) {
            IPS_SetEventAction($eid, ExtractGuid('Run Automation'), []);
        }
    }
    if (($eid !== false) && ($time == -1)) {
        IPS_SetEventActive($eid, false);
    }
    return $eid;
}

/**
 * Liefert die ID eines Events bzw. Timers unterhalb {id} mit dem Namen {name}.
 *
 * @param int $id ID unter welchem das Event erzeugt wurde.
 * @param string $name Name des zu suchenden Events oder Timers.
 *
 * @return int|false ID des gefundenen Events, andernfalls false.
 */
function GetEventByName($id, $name)
{
    return @IPS_GetEventIDByName($name, $id);
}

/**
 * Eine Funktion um ein Skript im Script-Verzeichnis zu erzeugen
 *
 * @param int $id ID unter welchem das Skript erzeugt werden soll.
 * @param string $name Name des zu erzeugenden Scripts
 *
 * @return int ID des bestehenden oder neu erzeugten Scripts
 */
function CreateScriptByName($id, $name)
{
    $sid = GetScriptByName($id, $name);
    if ($sid === false) {
        $sid = IPS_CreateScript(0);
        IPS_SetName($sid, $name);
        IPS_SetParent($sid, $id);
    }
    return $sid;
}

/**
 * Liefert die ID eines Skriptes unterhalb {id} mit dem Namen {name}.
 *
 * @param int $id ID unter welchem das Skript erzeugt wurde.
 * @param string $name Name des zu suchenden Skriptes.
 *
 * @return int|false ID des gefundenen Skriptes, andernfalls false.
 */
function GetScriptByName($id, $name)
{
    return @IPS_GetScriptIDByName($name, $id);
}

/**
 * Erzeugt ein WebHook mit Path {webhook} und Target {id}
 *
 * @param string $webhook Path des Ebhooks
 * @param int $id Ziel ID (Target) des Webhooks
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function RegisterHook($webhook, $id)
{
    $ids = IPS_GetInstanceListByModuleID(ExtractGuid('WebHook Control'));
    if (count($ids) > 0) {
        $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
        $found = false;
        foreach ($hooks as $index => $hook) {
            if ($hook['Hook'] == $webhook) {
                if ($hook['TargetID'] == $id) {
                    return;
                }
                $hooks[$index]['TargetID'] = $id;
                $found = true;
            }
        }
        if (!$found) {
            $hooks[] = ['Hook' => $webhook, 'TargetID' => $id];
        }
        IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
        IPS_ApplyChanges($ids[0]);
    }
}

/**
 * Unregister a web hook.
 *
 * @param string $hook path of the web hook.
 */
function UnregisterHook($hook)
{
    $ids = IPS_GetInstanceListByModuleID(ExtractGuid('WebHook Control'));
    if (count($ids) > 0) {
        $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
        $found = false;
        foreach ($hooks as $key => $value) {
            if ($value['Hook'] == $hook) {
                $found = true;
                $this->SendDebug('UnregisterHook', $hook . $this->InstanceID);
                break;
            }
        }
        // Unregister
        if ($found == true) {
            array_splice($hooks, $key, 1);
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }
}

/**
 * Aktiviert das Logging einer Variable im Archiv.
 *
 * @param int $id ID der zu loggenden Variable
 * @param bool $default True für standard Aggregationstyp, sonst Zähler
 * @param bool $zero true wenn Nullwerte ignoriert werden sollen.
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function RegisterArchive($id, $default = true, $zero = false)
{
    $ids = IPS_GetInstanceListByModuleID(ExtractGuid('Archive Control'));
    if (count($ids) > 0) {
        AC_SetLoggingStatus($ids[0], $id, true);
        AC_SetAggregationType($ids[0], $id, ($default ? 0 : 1));
        if ($zero) {
            AC_SetCounterIgnoreZeros($ids[0], $id, true);
        }
    }
}

/**
 * Deaktiviert das Logging einer Variable im Archiv.
 *
 * @param int $id ID der nicht mehr zu loggenden Variable
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function UnregisterArchive($id)
{
    $ids = IPS_GetInstanceListByModuleID(ExtractGuid('Archive Control'));
    if (count($ids) > 0) {
        AC_SetLoggingStatus($ids[0], $id, false);
    }
}

/**
 * Erzeugt ein Variablenprofil vom Typ {type} mit Namen {name}
 *
 * @param string $name Name des Profiles
 * @param int $type Typ des Profils (0 = Boolean, 1 = Integer, 2 = Float, 3 = String)
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function CreateProfile($name, $type)
{
    if (!IPS_VariableProfileExists($name)) {
        IPS_CreateVariableProfile($name, $type);
    } else {
        $profile = IPS_GetVariableProfile($name);
        if ($profile['ProfileType'] != $type) {
            throw new Exception('Variable profile type does not match for profile ' . $name);
        }
    }
}

/**
 * Erzeugt ein Boolean-Variablenprofil (Boolean-Typ = 0)
 *
 * @param string $name Name des Profiles
 * @param string $icon Assoziertes Standard-Icon
 * @param string $prefix Prefix Text
 * @param string $suffix Suffix Text
 * @param array $asso Array mit Profilassoziationen
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function CreateProfileBoolean($name, $icon, $prefix, $suffix, $asso)
{
    CreateProfile($name, 0);
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);

    if (($asso !== null) && (count($asso) !== 0)) {
        foreach ($asso as $ass) {
            IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
        }
    }
}

/**
 * Erzeugt ein Integer-Variablenprofil (Integer-Typ = 1)
 *
 * @param string $name Name des Profiles
 * @param string $icon Assoziertes Standard-Icon
 * @param string $prefix Prefix Text
 * @param string $suffix Suffix Text
 * @param int $minvalue Minimalwert welchen die Variable annehmen kann
 * @param int $maxvalue Maximalwert welchen die Variable annehmen kann
 * @param int $step Schrittweite in welchen sich der Wert erhöhen oder verringern kann
 * @param array $asso Array mit Profilassoziationen
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function CreateProfileInteger($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $step, $asso = null)
{
    CreateProfile($name, 1);
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);
    IPS_SetVariableProfileValues($name, $minvalue, $maxvalue, $step);

    if (($asso !== null) && (count($asso) !== 0)) {
        foreach ($asso as $ass) {
            IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
        }
    }
}

/**
 * Erzeugt ein Float-Variablenprofil (Float-Typ = 2)
 *
 * @param string $name Name des Profiles
 * @param string $icon Assoziertes Standard-Icon
 * @param string $prefix Prefix Text
 * @param string $suffix Suffix Text
 * @param int $minvalue Minimalwert welchen die Variable annehmen kann
 * @param int $maxvalue Maximalwert welchen die Variable annehmen kann
 * @param int $step Schrittweite in welchen sich der Wert erhöhen oder verringern kann
 * @param int $digits Anzahl Nachkommastellen die angezeigt werden sollen
 * @param array $asso Array mit Profilassoziationen
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function CreateProfileFloat($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $step, $digits, $asso = null)
{
    CreateProfile($name, 2);
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);
    IPS_SetVariableProfileDigits($name, $digits);
    /*
        if(($asso == null) && (count($asso) == 0)){
            $minvalue = 0;
            $maxvalue = 0;
        }
     */
    IPS_SetVariableProfileValues($name, $minvalue, $maxvalue, $step);
    if (($asso !== null) && (count($asso) !== 0)) {
        foreach ($asso as $ass) {
            IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
        }
    }
}

/**
 * Erzeugt ein String-Variablenprofil (String-Typ = 3)
 *
 * @param string $name Name des Profiles
 * @param string $icon Assoziertes Standard-Icon
 * @param string $prefix Prefix Text
 * @param string $suffix Suffix Text
 * @param array $asso Array mit Profilassoziationen
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function CreateProfileString($name, $icon, $prefix, $suffix, $asso)
{
    CreateProfile($name, 3);
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);

    if (($asso !== null) && (count($asso) !== 0)) {
        foreach ($asso as $ass) {
            IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
        }
    }
}

/**
 * Löscht ein Variablenprofile, sofern es nicht noch verwendet wird.
 *
 * @param string $name Name des Profiles
 *
 * @return void true im Erfolgsfall, anderenfalls false.
 */
function UnregisterProfile($name)
{
    if (!IPS_VariableProfileExists($name)) {
        return false;
    }
    foreach (IPS_GetVariableList() as $vid) {
        if (IPS_GetVariable($vid)['VariableCustomProfile'] == $name) {
            return false;
        }
        if (IPS_GetVariable($vid)['VariableProfile'] == $name) {
            return false;
        }
    }
    return IPS_DeleteVariableProfile($name);
}

/**
 * Debug Ausgabe von Variablen, Feldern & Objecten auf die Konsole
 *
 * @param string $msg Texthinweis zum Datenobjekt
 * @param mixed $data Datenobjekt welches auszugeben ist
 * @param string|null $pre Prefix, welcher den Datentyp näher beschreibt
 *
 * @return void Funktion liefert keinen Rückgabewert.
 */
function EchoDebug($msg, $data, $pre = null)
{
    // Safty Check for use!
    if (!isset($GLOBALS['DEBUG'])) {
        echo "!!! For Debug Output define global variable 'DEBUG' (true|false) !!!";
        return;
    }
    // only if Debug enabled
    if ($GLOBALS['DEBUG']) {
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                EchoDebug($msg, $value, $key);
            }
        } elseif (is_array($data)) {
            if ($pre !== null) {
                echo $msg . ': ' . $pre . '(array) =>' . PHP_EOL;
            }
            foreach ($data as $key => $value) {
                EchoDebug($msg, $value, $key);
            }
        } elseif (is_bool($data)) {
            echo $msg . ': ' . ($pre !== null ? $pre . ' => ' : '') . ($data ? 'TRUE' : 'FALSE') . PHP_EOL;
        } else {
            echo $msg . ': ' . ($pre !== null ? $pre . ' => ' : '') . $data . PHP_EOL;
        }
    }
}

################################################################################