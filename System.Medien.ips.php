<?php

declare(strict_types=1);

################################################################################
# Script:   System.Medien.ips.php
# Version:  1.0.20231019
# Author:   Heiko Wilknitz (@Pitti)
#
# Skript zum Anzeigen von Medienobjekten in IPS!
#
# ------------------------------ Changelog -------------------------------------
#
# 19.10.2023 - Initalversion (v1.0)
#
################################################################################

# # ID des Standard-Medienordners (siehe Locals)
$media = __WWX['IDM_ROOT'];

// AUFRUF WEBHOOK
if($_IPS['SENDER'] == 'WebHook') {
    $root = isset($_GET['root']) ? $_GET['root'] : $media;
    $name = isset($_GET['name']) ? $_GET['name'] : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'image/png'; // default: immage/png | oder audio/mpeg ...

    if($name != '') {
        $mid = IPS_GetMediaIDByName($name, $root);
        if ($mid === false) {
            header('HTTP/1.0 404 Not Found');
            echo 'Medienobjekt mit Namen ="' . $name . '" konnte nicht in ' . $root . ' gefunden!';
        }
        else {
            header('Content-Type: ' . $type);
            echo base64_decode(IPS_GetMediaContent($mid));
        }
    }
    else {
        header('HTTP/1.0 404 Not Found');
        echo 'Kein Medienobjekt angefordert!';
    }
}