<?php

require 'vendor/autoload.php';

use Keboola\Temp\Temp;

echo "Initializing\n";
$dataDir = getenv('KBC_DATADIR') === false ? '/data' : getenv('KBC_DATADIR');
$configFile = $dataDir . DIRECTORY_SEPARATOR . 'config.json';
$config = json_decode(file_get_contents($configFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("Failed to read config file: ". json_last_error_msg());
}
$repository = $config['image_parameters']['repository'];
$key = $config['image_parameters']['#git_key'];
$pass = $config['image_parameters']['#pass'];
file_put_contents('/root/pass', $pass);
exec('echo ' . escapeshellarg($key) . ' > /root/.ssh/git_key');
exec('chmod 0400 /root/.ssh/git_key');

$temp = new Temp();
$temp->initRunFolder();
#exec('eval $(ssh-agent -s)');
#exec('ssh-add ~/.ssh/id_rsa');
echo 'git clone ' . escapeshellarg($repository) . ' ' . $temp->getTmpFolder() . "\n";
exec('git clone ' . escapeshellarg($repository) . ' ' . $temp->getTmpFolder());

echo "Generating JSON files\n";
foreach ($config['storage']['input']['tables'] as $table) {
    $source = $dataDir . DIRECTORY_SEPARATOR . 'in' . DIRECTORY_SEPARATOR . 'tables'
        . DIRECTORY_SEPARATOR . $table['destination'];
    $destination = $temp->getTmpFolder() . DIRECTORY_SEPARATOR . $table['destination'];
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
    $data['destination'] = $destination;
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($destination, $json);
    echo "Saved $destination JSON file\n";
}

echo "Pushing\n";
exec('git add .');
$message = "Update: " . (new DateTime())->format('Y-M-D H:i');
exec('git commit -a -m ' . escapeshellarg($message));
exec('git push');
echo "All done\n";
