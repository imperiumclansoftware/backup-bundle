<?php

namespace ICS\BackupBundle\Service;

use ZipArchive;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use ICS\BackupBundle\Entity\EntityBackup;
use ICS\BackupBundle\Entity\BackupFormat;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class BackupService
{
    private $backupPath;
    private $config;
    private $doctrine;
    private $bundleFullList;
    private $entityFullList;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $doctrine)
    {
        $this->backupPath = $kernel->getProjectDir() . '/backup';
        //$this->config=$kernel->getParameter('backup');
        $this->doctrine = $doctrine;

        $this->bundleFullList = $kernel->getBundles();
        $this->entityFullList = $this->doctrine->getMetadataFactory()->getAllMetadata();
    }

    public function getBundleByName(string $bundleName): ?Bundle
    {
        foreach ($this->bundleFullList as $bundle) {
            if ($bundle->getName() == $bundleName) {
                return $bundle;
            }
        }

        return null;
    }

    public function getBundlesList(bool $onlyBundleWithEntity = false)
    {
        $bundles = [];

        if ($onlyBundleWithEntity) {
            foreach ($this->bundleFullList as $bundle) {
                if (count($this->getEntityList($bundle)) > 0) {
                    $bundles[] = $bundle;
                }
            }
        } else {
            $bundles = $this->bundleFullList;
        }

        array_multisort($bundles);

        return $bundles;
    }

    public function getEntityByName(string $entityName): ?ClassMetadata
    {
        foreach ($this->entityFullList as $entity) {
            if ($entity->getName() == $entityName) {
                return $entity;
            }
        }

        return null;
    }

    public function getEntityList(Bundle $bundle)
    {
        $bundleEntities = [];

        foreach ($this->entityFullList as $entity) {
            $bundleName = explode('\\', $entity->getName())[1];

            if ($bundleName == $bundle->getName()) {
                $bundleEntities[] = $entity;
            }
        }
        array_multisort($bundleEntities);
        return $bundleEntities;
    }

    public function BackupEntity(Bundle $bundle, ClassMetadata $entity, string $format = BackupFormat::JSON, array $ignoredProperty = [])
    {
        $normalizer = new ObjectNormalizer();

        switch ($format) {
            case BackupFormat::XML:
                $encoder = new XmlEncoder();
                $format = BackupFormat::XML;
                break;
            case BackupFormat::CSV:
                $encoder = new CsvEncoder();
                $format = BackupFormat::CSV;
                break;
            case BackupFormat::YAML:
                $encoder = new YamlEncoder();
                $format = BackupFormat::YAML;
                break;
            default:
                $encoder = new JsonEncoder();
                $format = BackupFormat::JSON;
                break;
        }

        $serializer = new Serializer([$normalizer], [$encoder]);

        $entityData = $this->doctrine->getRepository($entity->getName())->findAll();

        $backupData = new EntityBackup($bundle->getName(), $entity->getName(), $entityData);

        $backupFinal = $serializer->serialize($backupData, $format, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => $ignoredProperty,
            AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
            ObjectNormalizer::DEEP_OBJECT_TO_POPULATE => 1

        ]);
        $entityArray = explode('\\', $entity->getName());
        $entityName = $entityArray[count($entityArray) - 1];
        $filename = $backupData->getDate()->format('YmdHis') . '_' . $entityName;
        $path = '/ByEntity/' . $bundle->getName();

        return $this->saveFile($backupFinal, $path, $filename, $format);
    }

    public function BackupBundle(Bundle $bundle, string $format = BackupFormat::JSON)
    {
        $normalizer = new ObjectNormalizer();

        switch ($format) {
            case BackupFormat::XML:
                $encoder = new XmlEncoder();
                $format = BackupFormat::XML;
                break;
            case BackupFormat::CSV:
                $encoder = new CsvEncoder();
                $format = BackupFormat::CSV;
                break;
            case BackupFormat::YAML:
                $encoder = new YamlEncoder();
                $format = BackupFormat::YAML;
                break;
            default:
                $encoder = new JsonEncoder();
                $format = BackupFormat::JSON;
                break;
        }

        $serializer = new Serializer([$normalizer], [$encoder]);
        $path = '/ByBundle/' . $bundle->getName();

        $entityList = $this->getEntityList($bundle);

        foreach ($entityList as $entity) {

            $entityData = $this->doctrine->getRepository($entity->getName())->findAll();

            $backupData = new EntityBackup($bundle->getName(), $entity->getName(), $entityData);

            $backupFinal = $serializer->serialize($backupData, $format, [
                AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                    return $object->getId();
                },
                ObjectNormalizer::DEEP_OBJECT_TO_POPULATE => 1

            ]);
            $entityArray = explode('\\', $entity->getName());
            $entityName = $entityArray[count($entityArray) - 1];
            $filename = $backupData->getDate()->format('YmdHis') . '_' . $entityName;

            $this->saveFile($backupFinal, $path, $filename, $format);
        }

        $dateZipFile = new DateTime();
        $zipPath = $this->backupPath . '/ByBundle/' . $dateZipFile->format('YmdHis') . '_' . $bundle->getName() . '.zip';

        $this->zipDir($this->backupPath . $path, $zipPath);

        $this->removePath($this->backupPath . $path);
        return $zipPath;
    }

    public function saveFile(string $formatedData, string $backupPath, string $filename, string $format)
    {
        switch ($format) {
            case BackupFormat::XML:
                $extension = '.xml';
                break;
            case BackupFormat::CSV:
                $extension = '.csv';
                break;
            case BackupFormat::YAML:
                $extension = '.yaml';
                break;
            default:
                $extension = '.json';
                break;
        }

        $filename = $filename . $extension;
        $finalPath = $this->backupPath . $backupPath;
        if (!file_exists($finalPath)) {
            mkdir($finalPath, 0755, true);
        }

        file_put_contents($finalPath . '/' . $filename, $formatedData);

        return $finalPath . '/' . $filename;
    }

    public static function zipDir($sourcePath, $outZipPath)
    {
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];

        $z = new ZipArchive();
        $z->open($outZipPath, ZIPARCHIVE::CREATE);
        $z->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
        $z->close();
    }

    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    private function removePath($path)
    {
        if(is_dir($path))
        {
            $dir=opendir($path);
            while($file=readdir($dir))
            {
                if($file!='.' && $file!='..')
                {
                    $this->removePath($path.'/'.$file);
                }
            }

            rmdir($path);
        }
        else
        {
            unlink($path);
        }
    }
}
