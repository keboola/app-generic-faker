<?php

require 'vendor/autoload.php';

use Keboola\Temp\Temp;

echo "\nInitializing\n";
$dataDir = getenv('KBC_DATADIR') === false ? '/data' : getenv('KBC_DATADIR');
$configFile = $dataDir . DIRECTORY_SEPARATOR . 'config.json';
$config = json_decode(file_get_contents($configFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("Failed to read config file: ". json_last_error_msg());
}
$repository = $config['image_parameters']['repository'];
$key = $config['image_parameters']['#git_key'];
passthru('echo ' . escapeshellarg($key) . ' > /root/.ssh/git_key');
passthru('chmod 0400 /root/.ssh/git_key');
passthru('eval $(ssh-agent -s)');

$temp = new Temp();
$temp->initRunFolder();
passthru('git clone ' . escapeshellarg($repository) . ' ' . $temp->getTmpFolder());
mkdir($temp->getTmpFolder());

echo "\nGenerating JSON files\n";

foreach ($config['storage']['input']['tables'] as $table) {
    $source = $dataDir . DIRECTORY_SEPARATOR . 'in' . DIRECTORY_SEPARATOR . 'tables'
        . DIRECTORY_SEPARATOR . $table['destination'];
    $destinationDir = $temp->getTmpFolder() . DIRECTORY_SEPARATOR . $table['destination'];
    mkdir($destinationDir);
    $destinationFile = $destinationDir . DIRECTORY_SEPARATOR . $table['destination'];
    $csv = new \Keboola\Csv\CsvFile($source);
    $headers = $csv->getHeader();
    $data = [];
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
    $data['source'] = $source;
    $data['destination'] = $table['destination'];
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($destinationFile . '.request', 'GET /' . $table['destination']);
    file_put_contents($destinationFile . '.response', $json);
    file_put_contents($destinationFile . '.requestHeaders', 'Authorization: Basic Sm9obkRvZTpzZWNyZXQ=');
    echo "\nSaved $destinationFile JSON files.\n";
}

echo "\nPushing\n";
chdir($temp->getTmpFolder());
passthru('git add .');
$message = "Update: " . (new DateTime())->format('Y-M-D H:i');
passthru('git commit -a -m ' . escapeshellarg($message));
passthru('git push');
echo "\nAll done\n";
