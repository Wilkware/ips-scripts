<?php
################################################################################
# Script:   System.Functions.ips.php
# Version:  2.0.20220801
# Author:   Heiko Wilknitz (@Pitti)
#
# Basisfunktionen für einfache Scripterstellung!
#
# ------------------------------ Changelog -------------------------------------
#
# 09.05.2019 - Initalversion (v1.0)
# 01.08.2022 - CreateIdent hinzugefügt
#
################################################################################

// Erzeugt aus dem übergebenen Namen ein IPS konformen IDENT
// Ausserdem erlauben wir nur kleingeschriebene Idents.
function CreateIdent($name)
{
    $umlaute = Array("/ä/","/ö/","/ü/","/Ä/","/Ö/","/Ü/","/ß/");
    $replace = Array("ae","oe","ue","Ae","Oe","Ue","ss");
    $ident = preg_replace($umlaute, $replace, $name);
    // idents immer klein?!?
    //$ident = strtolower($ident);
    return preg_replace("/[^a-z0-9_]+/i", "", $ident);
}

// Erzeugt eine Kategorie unterhalb {id} mit dem Namen {name}
// Existiert die Kategorie schon wird diese zurückgeliefert.
// Position: 0 gleich Standard, sonst neue Sortierreihenfolgenummer {pos}
// Icon: Zuweisung eines Icons mit dem Namen {icon}
function CreateCategoryByName($id, $name, $pos=0, $icon = '')
{
    $cid = @IPS_GetCategoryIDByName($name, $id);
    if(!$cid) {
        $cid = IPS_CreateCategory();
        IPS_SetName($cid, $name);
        IPS_SetParent($cid, $id);
        IPS_SetPosition($cid, $pos);
        IPS_SetIcon($cid, $icon);
    }
    return $cid;
}

// Erzeugt ein Dummy Modul unterhalb {id} mit dem Namen {name}
// Existiert das Modul schon wird diese zurückgeliefert.
// {pos}    : 0 gleich Standardposition, sonst neue Sortierreihenfolgenummer 
// [icon}   : Zuweisung eines Icons mit dem Namen {icon}
function CreateDummyByName($id, $name, $pos=0, $icon = '')
{
    return CreateDummyByIdent($id, $name, $name, $pos, $icon);
}

// Erzeugt ein Dummy Modul unterhalb {id} mit dem Namen {name} und Ident {ident}
// Existiert das Modul schon wird diese zurückgeliefert.
// {pos}    : 0 gleich Standardposition, sonst neue Sortierreihenfolgenummer 
// [icon}   : Zuweisung eines Icons mit dem Namen {icon}
function CreateDummyByIdent($id, $ident, $name, $pos=0, $icon = '')
{
    $ident = CreateIdent($ident);
    $did = @IPS_GetObjectIDByIdent($ident,$id);
    if(!$did) {
        $did = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
        IPS_SetName($did, $name);
        IPS_SetIdent($did, $ident);
        IPS_SetParent($did, $id);
        IPS_SetPosition($did, $pos);
        IPS_SetIcon($did, $icon);
    }
    return $did;
}

// Erzeugt ein Popup Modul unterhalb {id} mit dem Namen {name}
// Existiert das Modul schon wird diese zurückgeliefert.
// {pos}    : 0 gleich Standardposition, sonst neue Sortierreihenfolgenummer 
// [icon}   : Zuweisung eines Icons mit dem Namen {icon}
function CreatePopupByName($id, $name, $pos=0, $icon = '')
{
    return CreatePopupByName($id, $name, $name, $pos, $icon);
}

// Erzeugt ein Popup Modul unterhalb {id} mit dem Namen {name} und Ident {ident}
// Existiert das Modul schon wird diese zurückgeliefert.
// {pos}    : 0 gleich Standardposition, sonst neue Sortierreihenfolgenummer 
// [icon}   : Zuweisung eines Icons mit dem Namen {icon}
function CreatePopupByIdent($id, $ident, $name, $pos=0, $icon = '')
{
    $ident = CreateIdent($ident);
    $pid = @IPS_GetObjectIDByIdent($ident,$id);
    if(!$pid) {
        $pid = IPS_CreateInstance("{5EA439B8-FB5C-4B81-AA35-1D14F4EA9821}");
        IPS_SetName($pid, $name);
        IPS_SetIdent($pid, $ident);
        IPS_SetParent($pid, $id);
        IPS_SetPosition($pid, $pos);
        IPS_SetIcon($pid, $icon);
    }
    return $pid;
}

// Erzeugt eine Variable unterhalb {id} mit dem Namen {name} vom Typ {type}
// Existiert die Variable schon wird diese zurückgeliefert.
// {type}   : 0 = Boolean, 1 = Integer, 2 = Float, 3 = String
// {pos}    : 0 gleich Standardposition, sonst neue Sortierreihenfolgenummer 
// [icon}   : Zuweisung eines Icons mit dem Namen {icon}
// {profil} : leer für kein Profil, sonst Profilnamen 
// {action} : Script-ID für Auslösung via Webfront 
function CreateVariableByName($id, $name, $type, $pos = 0, $icon = '', $profile = '', $action = null) 
{
    $vid = @IPS_GetVariableIDByName($name, $id); 
    if($vid===false) {
        $vid = IPS_CreateVariable($type); 
        IPS_SetParent($vid, $id); 
        IPS_SetName($vid, $name); 
        IPS_SetPosition($vid, $pos); 
        IPS_SetIcon($vid, $icon);
        if($profile !== '') { 
            IPS_SetVariableCustomProfile($vid, $profile); 
        }
        if($action != null) {
            IPS_SetVariableCustomAction($vid, $action);
        }
    }
    return $vid; 
}

// Erzeugt eine Variable unterhalb {id} mit dem Namen {name} vom Typ {type}
// Existiert die Variable schon wird diese zurückgeliefert.
// {type}   : 0 = Boolean, 1 = Integer, 2 = Float, 3 = String
// {pos}    : 0 gleich Standardposition, sonst neue Sortierreihenfolgenummer 
// [icon}   : Zuweisung eines Icons mit dem Namen {icon}
// {profil} : leer für kein Profil, sonst Profilnamen 
// {action} : Script-ID für Auslösung via Webfront 
function CreateVariableByIdent($id, $ident, $name, $type, $pos = 0, $icon = '', $profile = '', $action = null) 
{
    $ident = CreateIdent($ident);
    $vid = @IPS_GetObjectIDByIdent($ident, $id); 
    if($vid===false) {
        $vid = IPS_CreateVariable($type); 
        IPS_SetParent($vid, $id); 
        IPS_SetName($vid, $name); 
        IPS_SetIdent($vid, $ident);
        IPS_SetPosition($vid, $pos); 
        IPS_SetIcon($vid, $icon);
        if($profile !== '') { 
            IPS_SetVariableCustomProfile($vid, $profile); 
        }
        if($action != null) {
            IPS_SetVariableCustomAction($vid, $action);
        }
    }
    return $vid; 
}

// Erzeugt einen Timer unterhalb {id} mit dem Namen {name} um Zeit {time}
// Existiert das  Event schon wird diese zurückgeliefert.
// $time = mktime(hour, minute, second);
function CreateEventByName($id, $name, $time = 0)
{
    $eid = @IPS_GetEventIDByName($name, $id);  
    if(($eid===false) && ($time > 0)) {
        // Eventtyp = Zyklisch (1)
        $eid = IPS_CreateEvent(1);
        IPS_SetParent($eid, $id);
        IPS_SetName($eid, $name);
        IPS_SetPosition($eid, -2);
        IPS_SetHidden($eid, true);
    }
    if($time > 0) {
        // Datumstyp = Tägliche (2), Datumsintervall = keins (1), Datumstage = keine (0), Datumstagesintervall = keine (0), Zeittyp = Einmalig (0), Zeitintervall = keine (0)
        IPS_SetEventCyclic($eid, 2, 1, 0, 0, 0, 0);
        IPS_SetEventCyclicDateFrom($eid, (int)date('j',$time), (int)date('n', $time),  (int)date('Y', $time));
        IPS_SetEventCyclicDateTo($eid, 0, 0, 0);
        IPS_SetEventCyclicTimeFrom($eid, (int)date("H", $time), (int)date("i", $time), (int)date("s", $time));
        IPS_SetEventCyclicTimeTo($eid, 0, 0, 0);
        IPS_SetEventActive($eid, true);
        if (function_exists('IPS_SetEventAction')) {
            IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
        }
    }
    return $eid;
}

// Erzeugt einen Timer unterhalb {id} mit dem Namen {name} um die Zeit {time}
// Existiert das  Event schon wird diese zurückgeliefert.
// Tägliche Ausführung: $time in Minuten
// Einmalige Ausführung: $time mit mktime(hour, minute, second) übergeben.
function CreateTimerByName($id, $name, $time = 0, $repeat = true)
{
    //IPS_LogMessage('TIMER', strftime("%Y-%m-%d", $time));
    $eid = @IPS_GetEventIDByName($name, $id);
    if(($eid===false) && ($time > 0)) {
        // Eventtyp = Zyklisch (1)
        $eid = IPS_CreateEvent(1);
        IPS_SetParent($eid, $id);
        IPS_SetName($eid, $name);
        IPS_SetPosition($eid, -1);
        IPS_SetHidden($eid, true);
        if($repeat == true) {
            // 0 = Tägliche Ausführung
            IPS_SetEventCyclic($eid, 0, 1, 0, 0, 2, $time);
            $now = time();
            IPS_SetEventCyclicDateFrom($eid, (int)date('j',$now), (int)date('n', $now),  (int)date('Y', $now));
        } else {
            // Einmalige Ausführung
            IPS_SetEventCyclic($eid, 1, 0, 0, 0, 0, 0);
            IPS_SetEventCyclicDateFrom($eid, (int)date('j',$time), (int)date('n', $time),  (int)date('Y', $time));
            IPS_SetEventCyclicDateTo($eid, (int)date('j',$time), (int)date('n', $time),  (int)date('Y', $time));
            IPS_SetEventCyclicTimeFrom($eid, (int)date("H", $time), (int)date("i", $time), (int)date("s", $time));
            IPS_SetEventCyclicTimeTo($eid, (int)date("H", $time), (int)date("i", $time), (int)date("s", $time));
        }
        IPS_SetEventActive($eid, true);
        if (function_exists('IPS_SetEventAction')) {
            IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
        }
    }
    return $eid;
}

// Eine Funktion um ein Script im Script-Verzeichnis zu erzeugen 
function CreateScriptByName($id, $name)
{ 
    $sid = @IPS_GetScriptIDByName($name, $id); 
    if ($sid == 0){ 
        $sid = IPS_CreateScript(0); 
        IPS_SetName($sid, $name); 
        IPS_SetParent($sid, $id); 
    } 
    return $sid; 
} 

// Erzeugt ein WebHook
function RegisterHook($webhook, $id)
{
    $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
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

// Erzeugt ein Variablenprofil vom Typ {type} mit Namen {name} 
function CreateProfile($name, $type)
{
    if(!IPS_VariableProfileExists($name)) {
        IPS_CreateVariableProfile($name, $type);
    }
    else {
    $profile = IPS_GetVariableProfile($name);
        if($profile['ProfileType'] != $type)
            throw new Exception("Variable profile type does not match for profile ".$name);
    }
}

// Erzeugt ein Boolean-Variablenprofil (Boolean-Typ = 0)
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

// Erzeugt ein Integer-Variablenprofil (Integer-Typ = 1)
function CreateProfileInteger($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $step, $digits, $asso = null)
{
    CreateProfile($name, 1);
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);
    IPS_SetVariableProfileDigits($name, $digits);
/*
    if (($asso !== null) && (count($asso) !== 0)) {
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

// Erzeugt ein Float-Variablenprofil (Float-Typ = 2)
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
    if(($asso !== null) && (count($asso) !== 0)){
        foreach($asso as $ass) {
            IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
        }
    }
}

// Erzeugt ein String-Variablenprofil (String-Typ = 3)
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

// Debug Ausgabe von Variablen, Felder & Objecten auf die Konsole
function EchoDebug($msg, $data, $pre = null) {
    // Safty Check for use!
    if(!isset($GLOBALS["DEBUG"])) {
        echo "!!! For Debug Output define global variable 'DEBUG' (true|false) !!!";
        return;
    }
    // only if Debug enabled
    if($GLOBALS["DEBUG"]) {
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                EchoDebug($msg, $value, $key);
            }
        } elseif (is_array($data)) {
            if($pre!==null) {
                echo $msg . ': ' . $pre . '(array) =>' . PHP_EOL;
            }
            foreach ($data as $key => $value) {
                EchoDebug($msg, $value, $key);
            }
        } elseif (is_bool($data)) {
            echo $msg . ': ' .  ($pre!==null ? $pre . ' => ' : '') . ($data ? 'TRUE' : 'FALSE') . PHP_EOL;
        } else {
            echo $msg . ': ' . ($pre!==null ? $pre . ' => ' : '') . $data . PHP_EOL;
        }
    }
}

################################################################################
?>