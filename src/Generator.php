<?php

namespace Keboola\GenericFaker;

use DateTime;
use Keboola\Csv\CsvFile;
use Keboola\Temp\Temp;
use Psr\Log\LoggerInterface;

class Generator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Temp
     */
    private $temp;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->temp = new Temp();
        $this->temp->initRunFolder();
    }

    private function getDataDir() : string
    {
        return getenv('KBC_DATADIR') === false ? '/data' : getenv('KBC_DATADIR');
    }

    private function processConfigFile() : array
    {
        $configFile = $this->getDataDir() . DIRECTORY_SEPARATOR . 'config.json';
        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to read config file: ". json_last_error_msg());
        }
        if (empty($config['image_parameters']['repository'])) {
            throw new \InvalidArgumentException("Repository not set in image_parameters.");
        }
        if (empty($config['image_parameters']['#git_key'])) {
            throw new \InvalidArgumentException("Git Key not set in image_parameters.");
        }
        if (empty($config['storage']['input']['tables']) || !is_array($config['storage']['input']['tables'])) {
            throw new \InvalidArgumentException("No tables on input.");
        }
        $config['parameters']['username'] = $config['parameters']['username'] ?? 'JohnDoe';
        $config['parameters']['#password'] = $config['parameters']['#password'] ?? 'secret';
        if (empty($config['parameters']['pageSize']) || empty(intval($config['parameters']['pageSize']))) {
            $config['parameters']['pageSize'] = 1000;
        }
        $this->logger->info("Config file ok.");
        return $config;
    }

    private function generateJSONFiles(array $tables, string $userName, string $password, int $pageSize)
    {
        $sourceDir = $source = $this->getDataDir() . DIRECTORY_SEPARATOR . 'in' . DIRECTORY_SEPARATOR . 'tables' . DIRECTORY_SEPARATOR;
        $destinationDir = $this->temp->getTmpFolder() . DIRECTORY_SEPARATOR;
        $this->logger->info("Generating JSONs.");
        foreach ($tables as $table) {
            $sourceFile = $sourceDir . $table['destination'];
            if (!file_exists($destinationDir . $table['destination'])) {
                mkdir($destinationDir . $table['destination']);
            }
            $destinationFile = $destinationDir . $table['destination'] . DIRECTORY_SEPARATOR . $table['destination'];
            $csv = new CsvFile($sourceFile);
            $headers = $csv->getHeader();
            $data = [];
            $data['source'] = $table['source'];
            $data['destination'] = $table['destination'];
            $data['lastPage'] = false;
            $page = 1;
            foreach ($csv as $rowIndex => $row) {
                if ($rowIndex == 0) {
                    continue;
                }
                $item = [];
                foreach ($headers as $index => $column) {
                    $item[$column] = $row[$index];
                }
                $data['items'][] = $item;
                if (count($data['items']) >= $pageSize) {
                    $data['pageNumber'] = $page;
                    $this->saveData($data, $destinationFile, $userName, $password, $table['destination']);
                    $data['items'] = [];
                    $page++;
                }
            }
            $data['pageNumber'] = $page;
            $data['lastPage'] = true;
            $this->saveData($data, $destinationFile, $userName, $password, $table['destination']);
        }
    }

    private function saveData(array $data, string $destinationFile, string $userName, string $password, string $address)
    {
        $baseName = $destinationFile . '-' . $data['pageNumber'];
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $targetUrl = str_replace('.', '/', $address);
        file_put_contents($baseName . '.request', 'GET /' . $targetUrl . '?page=' . $data['pageNumber']);
        file_put_contents($baseName . '.response', $json);
        $auth = base64_encode($userName . ':' . $password);
        file_put_contents($baseName . '.requestHeaders', 'Authorization: Basic ' . $auth);
        $this->logger->info("Saved $baseName request, response and requestHeaders files.");
    }

    private function pullData(string $repository, string $key)
    {
        chdir($this->temp->getTmpFolder());
        passthru('echo ' . escapeshellarg($key) . ' > /root/.ssh/git_key');
        passthru('chmod 0400 /root/.ssh/git_key');
        passthru('eval $(ssh-agent -s)');
        passthru('git clone ' . escapeshellarg($repository) . ' .');
    }

    private function pushData()
    {
        $this->logger->debug("Pushing");
        chdir($this->temp->getTmpFolder());
        passthru('git add .');
        $message = "Update: " . (new DateTime())->format('Y-M-D H:i');
        passthru('git commit -a -m ' . escapeshellarg($message));
        passthru('git push');
    }

    public function run()
    {
        $config = $this->processConfigFile();
        $this->pullData($config['image_parameters']['repository'], $config['image_parameters']['#git_key']);
        $this->generateJSONFiles(
            $config['storage']['input']['tables'],
            $config['parameters']['username'],
            $config['parameters']['#password'],
            $config['parameters']['pageSize']
        );
        $this->pushData();
        $this->logger->debug("All done");
    }
}
