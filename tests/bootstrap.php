<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Ensure the test database exists with the schema
if (($_SERVER['APP_ENV'] ?? 'dev') === 'test') {
    try {
        $kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) ($_SERVER['APP_DEBUG'] ?? false));
        $kernel->boot();

        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);

        $application->run(
            new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'doctrine:database:create',
                '--if-not-exists' => true,
            ]),
            new \Symfony\Component\Console\Output\NullOutput()
        );

        $application->run(
            new \Symfony\Component\Console\Input\ArrayInput([
                'command' => 'doctrine:migrations:migrate',
                '--no-interaction' => true,
            ]),
            new \Symfony\Component\Console\Output\NullOutput()
        );

        $kernel->shutdown();
    } catch (\Throwable $e) {
        echo 'Warning: Could not set up test database: ' . $e->getMessage() . PHP_EOL;
    }
}
