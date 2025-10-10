<?php

namespace App\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()
            ->get("doctrine")
            ->getManager();
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if ($metadata) {
            $tool->createSchema($metadata);
        }
    }
}
