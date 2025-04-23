<?php
/**
 * QRIS Static to Dynamic Converter
 * Modified for web application use
 */

function convertStaticToDynamicQRIS($qrisStatic, $amount) {
    // Remove the last 4 characters (CRC)
    $qris = substr($qrisStatic, 0, -4);
    
    // Replace static identifier with dynamic
    $step1 = str_replace("010211", "010212", $qris);
    
    // Split at country code
    $step2 = explode("5802ID", $step1);
    
    // Format amount with proper length prefix
    $uang = "54" . sprintf("%02d", strlen($amount)) . $amount;
    $uang .= "5802ID";
    
    // Combine parts
    $fix = trim($step2[0]) . $uang . trim($step2[1]);
    
    // Add CRC16 checksum
    $fix .= convertCRC16($fix);
    
    return $fix;
}

function convertCRC16($str) {
    $crc = 0xFFFF;
    $strlen = strlen($str);
    
    for($c = 0; $c < $strlen; $c++) {
        $crc ^= ord(substr($str, $c, 1)) << 8;
        for($i = 0; $i < 8; $i++) {
            if($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc = $crc << 1;
            }
        }
    }
    
    $hex = $crc & 0xFFFF;
    $hex = strtoupper(dechex($hex));
    
    if (strlen($hex) == 3) $hex = "0" . $hex;
    
    return $hex;
}