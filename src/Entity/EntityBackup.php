<?php
namespace ICS\BackupBundle\Entity;

use DateTime;

class EntityBackup
{
    private $bundle;

    private $entity;

    private $data;

    private $date;

    public function __construct(string $bundle,string $entity,$data)
    {
        $this->bundle = $bundle;
        $this->entity = $entity;
        $this->data = $data;
        $this->date = new DateTime();
    }

    /**
     * Get the value of bundle
     */ 
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Set the value of bundle
     *
     * @return  self
     */ 
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get the value of entity
     */ 
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the value of entity
     *
     * @return  self
     */ 
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get the value of date
     */ 
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of date
     *
     * @return  self
     */ 
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of data
     */ 
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the value of data
     *
     * @return  self
     */ 
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}