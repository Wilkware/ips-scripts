# IP Symcon Skript Bibliothek

[![Version](https://img.shields.io/badge/Symcon-Scripts-red.svg?style=flat-square)](https://www.symcon.de/de/service/dokumentation/komponenten/dienst/php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Library%20Version-2.5.20250507-orange.svg?style=flat-square)](https://github.com/Wilkware/ips-scripts)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/ips-scripts/style.yml?branch=main&label=CheckStyle&style=flat-square)](https://github.com/Wilkware/ips-scripts/actions)

Dies ist eine Sammlung von allgemeinen oder aufgabenspezifischen Skripten, welche man für ein IP-Symcon System verwenden kann.

## Inhaltverzeichnis

1. [Voraussetzungen](#user-content-1-voraussetzungen)
2. [Anwendung](#user-content-2-anwendung)
3. [Versionshistorie](#user-content-3-versionshistorie)

### 1. Voraussetzungen

* IP-Symcon ab Version 6.4
* Visual Studio Editor mit installierter [FTP-Sync Erweiterung](https://marketplace.visualstudio.com/items?itemName=faulty.ftp-sync-improved)

### 2. Anwendung

Der Hintergrund und die Verwendung ist in meinem Blogartikel '[Versionierung von Skripte](https://wilkware.de/2022/03/versionierung-skripte/)' näher beschrieben.

### 3. Versionshistorie

v2.5.20250507

* _NEU_: Reiseinfo Script (bahn.de) hinzugefügt

v2.4.20241222

* _NEU_: Börsenticker Script 
* _NEU_: Update Local Script um neue TileVisu Farben und Icon-Mapping
* _NEU_: Update SolarEdge Script wegen Auswahl des (Auswertungs-)Zeitraums
* _FIX_: Versionsnummern in allen Scripts korrekt nachgezogen

v2.3.20240909

* _NEU_: Update Wetterscript (bessere Unterstützung für Fehler, TileVisu und openHASP)

v2.2.20240801

* _NEU_: Icondarstellung im Dashboard für v7.x überarbeitet
* _DEL_: Media Guide Script gelöscht
* _FIX_: CheckStyle korriegiert

v2.1.20240304

* _NEU_: Kleinere Fixes primär für neue Tile Visu

v2.0.20231117

* _NEU_: PHP Style Check Action eingeführt
* _NEU_: Unterstützung der Themes für Tile Visu
* _NEU_: Erweiterung für PirateWeather Skript um Vorhersage und Klimadaten
* _FIX_: Icondarstellung im Dashboard Skript funktioniert jetzt korrekt

v1.9.20231107

* _FIX_: Veraltete System-Calls im Meldungsskript

v1.8.20231106

* _NEU_: PirateWeather Skript (Wetterdaten vom Nachfolger von Dark Sky)
* _FIX_: Style Checks für SolarEdge jetzt korrekt

v1.7.20231020

* _NEU_: Dashboard Skript (Meldungsanzeige im WebFront / Tile Visu)

v1.6.20231019

* _NEU_: Medien Skript

v1.5.20231005

* _NEU_: SolarEdge Monitoring Skript
* _NEU_: Globale Get-Funktionen hinzugefügt
* _FIX_: Test auf Bestehen in Create-Funktionen korrigiert bzw. vereinheitlicht

v1.4.20230315

* _NEU_: Locals Skript für personalisierte Daten hinzugefügt
* _NEU_: Solarprognose Skripte für solarprognose.de und solcast.com
* _NEU_: Einige neue globale Funktionen hinzugefügt
* _FIX_: CreateProfileInteger in Functions korrigiert

v1.3.20230223

* _NEU_: QuickChart Skript (System.QuickChart) hinzugefügt
* _NEU_: VSC Workspace Datei hinzugefügt
* _NEU_: PHP CS Fixer (style) hinzugefügt

v1.2.20230125

* _NEU_: Sonnengang Skript (Weather.Sunrun) hinzugefügt

v1.1.20230121

* _NEU_: Globale Konstante für Bibliothek eingeführt
* _NEU_: Multimedia-Guide Skript hinzugefügt

v1.0.20230115

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
