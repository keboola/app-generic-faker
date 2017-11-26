<?php

require 'vendor/autoload.php';

function exception_error_handler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

// initialize logger
$logger = new Monolog\Logger("logger");
$stream = fopen('php://stderr', 'r');
$logger->pushHandler(new \Monolog\Handler\StreamHandler($stream));
$logger->info("Initializing");

// run application
try {
    $generator = new \Keboola\GenericFaker\Generator($logger);
    $generator->run();
} catch (\InvalidArgumentException $e) {
    $logger->error($e->getMessage(), ['exception' => $e]);
    exit(1);
} catch (\Exception $e) {
    $logger->error('Application error has occurred.', ['exception' => $e]);
    exit(2);
}
