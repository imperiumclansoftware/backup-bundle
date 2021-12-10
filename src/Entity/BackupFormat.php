<?php
namespace ICS\BackupBundle\Entity;

abstract class BackupFormat
{
    const JSON = 'json';
    const CSV = 'csv';
    const YAML = 'yaml';
    const XML = 'xml';
}