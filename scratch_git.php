<?php
$f1 = file_get_contents('c:/xampp/htdocs/oxo/db/oxo_db.sql');
$f2 = file_get_contents('c:/Users/sojin/Downloads/oxo_db.sql');

preg_match_all('/CREATE TABLE [`"]?(\w+)[`"]?/', $f1, $m1);
preg_match_all('/CREATE TABLE [`"]?(\w+)[`"]?/', $f2, $m2);

$t1 = array_unique($m1[1]);
sort($t1);
$t2 = array_unique($m2[1]);
sort($t2);

echo "--- TABLES IN db/oxo_db.sql (" . count($t1) . ") ---\n";
print_r($t1);

echo "\n--- TABLES IN Downloads/oxo_db.sql (" . count($t2) . ") ---\n";
print_r($t2);
