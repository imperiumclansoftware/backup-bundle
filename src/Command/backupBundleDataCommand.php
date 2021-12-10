<?php
namespace ICS\BackupBundle\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use ICS\BackupBundle\Service\BackupService;
use ICS\BackupBundle\Entity\BackupFormat;
use Exception;

class backupBundleDataCommand extends Command
{
    private $io;
    private $backup;

    protected static $defaultName = 'backup:bundle:data';

    public function __construct(BackupService $backup)
    {
        parent::__construct();
        $this->backup = $backup;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command export a bundle in a zip file with data')
            ->addArgument('bundle',InputArgument::OPTIONAL,'Bundle of entity')
            ->addOption('format','f',InputOption::VALUE_OPTIONAL,'Format of export [json,xml,yaml,csv]',BackupFormat::JSON);
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input,$output);
        
        $bundle=null;

        // Argument control
        if($input->getArgument('bundle'))
        {
            $bundle = $this->backup->getBundleByName($input->getArgument('bundle'));
        }

        // Question if necessary
        if($bundle==null)
        {
            $choicesBundles=[];
            foreach($this->backup->getBundlesList(true) as $backup)
            {
                $choicesBundles[]=$backup->getName();
            }

            $bundle = $this->backup->getBundleByName($this->io->choice('Which bundle do you want to backup ?',$choicesBundles));
        }

        // Proceed to backup
        $this->io->title('Backup bundle : '.$bundle->getName()); 
        
        try
        {
            $filepath=$this->backup->backupBundle($bundle,$input->getOption('format'));
            $this->io->success('Bundle '.$bundle->getName()."\n is backup in file\n".$filepath);
            
            return Command::SUCCESS;
        }
        catch(Exception $ex)
        {
            $this->io->error($ex->getMessage());
            return Command::FAILURE;
        }
        
        // return Command::INVALID
    }    
}