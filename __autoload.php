<?php

declare(strict_types=1);

################################################################################
# Script:   __autoload.php
# Version:  1.0.20210609
# Author:   Heiko Wilknitz (@Pitti)
#
# Um Funktionen, Konstanten usw. global über alle Skripte hinweg zur Verfügung
# zu stellen müssen diese in der Datei "__autoload.php" definiert werden.
# Diese muss sich im "IP-Symcon/scripts"-Ordner befinden.
#
# Innerhalb der "__autoload.php" können dann weitere Dateien eingelesen werden.
#
# ------------------------------ Changelog -------------------------------------
#
# 09.06.2021 - Initalversion (v1.0)
#
################################################################################

require_once IPS_GetKernelDir() . '/scripts/System.Functions.ips.php';
