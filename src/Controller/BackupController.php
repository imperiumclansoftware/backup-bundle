<?php
namespace ICS\BackupBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class BackupController extends AbstractController
{

    /**
    * @Route("/",name="ics-backup-homepage")
    */
    public function index()
    {

        return $this->render('@Backup\index.html.twig',[]);
    }

}