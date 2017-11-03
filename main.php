<?php

require 'vendor/autoload.php';

use Keboola\StorageApi\Client;

$dataDir = getenv('KBC_DATADIR') ?? '/data';
$configFile = $dataDir . DIRECTORY_SEPARATOR . 'config.json';
$config = json_decode(file_get_contents($configFile), true);
if (json_last_error() != JSON_ERROR_NONE) {
    throw new Exception("Failed to read config file: ". json_last_error_msg());
}

$token = $config['image_parameters']['#token'];
$url = $config['image_parameters']['#url'];
$bucket = $config['image_parameters']['bucket'];
$dataSet = $config['parameters']['dataset'];
$page = $config['parameters']['page'];

$client = new Client(['token' => $token, 'url' => $url]);
$exporter = new \Keboola\StorageApi\TableExporter($client);
$tmp = new \Keboola\Temp\Temp();
$destination = $tmp->getTmpFolder() . $dataSet;
$exporter->exportTable($bucket . '.' . $dataSet, $destination, ['whereColumn' => 'page', 'whereValues' => [$page]]);
$csv = new \Keboola\Csv\CsvFile($destination);
$headers = $csv->getHeader();
$data = [];
// paage by se dal udelat pres where values
foreach ($csv as $rowIndex => $row) {
    if ($rowIndex == 0) {
        continue;
    }
    $item = [];
    foreach ($headers as $index => $column) {
        $item[$column] = $row[$index];
    }
    $data['items'][] = $item;
}
$data['page'] = $page;
$response = json_encode($data, JSON_PRETTY_PRINT);
echo $response;
