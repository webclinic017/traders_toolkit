<?php
//set_include_path( get_include_path().PATH_SEPARATOR.__DIR__ );
//echo get_include_path(); exit();
//require '../../autoloader.php';

echo "script is executing\m";

$csv = new \SgCsv\CsvMappedReader('demo.csv');

$headings = $csv->getFieldNames();
echo "File Headings: ".implode($headings, ", ")."\n";

foreach ($csv as $line) {
    var_dump($line);
}