<?php
// $Id$

// Define $input to be an array containing all of the output from some
// ping command

$input[] = "";
$input[] = "Ping wird ausgefhrt fr 209.92.3.80 mit 32 Bytes Daten: ";
$input[] = "";
$input[] = "Zeitberschreitung der Anforderung. ";
$input[] = "Zeitberschreitung der Anforderung. ";
$input[] = "Zeitberschreitung der Anforderung. ";
$input[] = "";
$input[] = "Ping-Statistik fr 209.92.3.80: ";
$input[] = "Pakete: Gesendet = 3, Empfangen = 0, Verloren = 3 (100% Verlust), ";

$expect['bytesperreq'] = 40; // 32 bytes of data, plus 8 bytes of ICMP header
$expect['received'] = 0;
$expect['transmitted'] = 3;
$expect['loss'] = 100; // percent lost!
$expect['bytestotal'] = 120;
$expect['targetip'] = '209.92.3.80';

?>