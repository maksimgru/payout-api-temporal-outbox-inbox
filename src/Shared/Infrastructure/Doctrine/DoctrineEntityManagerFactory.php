<?php

namespace Shared\Infrastructure\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class DoctrineEntityManagerFactory
{
    public function create(): EntityManagerInterface
    {
        $paths = [
            base_path('src/Modules/Payouts/Infrastructure/Persistence/Doctrine/Orm'),
            base_path('src/Modules/Users/Infrastructure/Persistence/Doctrine/Orm'),
            base_path('src/Modules/Audit/Infrastructure/Persistence/Doctrine/Orm'),
            base_path('src/Shared/Infrastructure/Doctrine/Orm'),
        ];

        $cache = config('app.debug')
            ? new ArrayAdapter()
            : new FilesystemAdapter(directory: storage_path('framework/cache/doctrine'));

        $configuration = ORMSetup::createAttributeMetadataConfiguration(
            paths: $paths,
            isDevMode: (bool) config('app.debug'),
            proxyDir: storage_path('framework/cache/doctrine/proxies'),
            cache: $cache,
        );

        $configuration->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => (string) config('database.connections.mysql.host'),
            'port' => (int) config('database.connections.mysql.port'),
            'dbname' => (string) config('database.connections.mysql.database'),
            'user' => (string) config('database.connections.mysql.username'),
            'password' => (string) config('database.connections.mysql.password'),
            'charset' => 'utf8mb4',
        ], $configuration);

        return new EntityManager($connection, $configuration);
    }
}
