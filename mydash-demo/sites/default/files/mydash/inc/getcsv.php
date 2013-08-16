<?php

$data=urldecode($_REQUEST['csv_text']);
$filename=$_REQUEST['filename'] . ".csv";

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$data = str_replace('CRCR', "\r\n", $data);

echo $data;

?>