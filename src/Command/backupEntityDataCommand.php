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

class backupEntityDataCommand extends Command
{
    private $io;
    private $backup;

    protected static $defaultName = 'backup:entity:data';

    public function __construct(BackupService $backup)
    {
        parent::__construct();
        $this->backup = $backup;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command export an entity in a file with data')
            ->addArgument('bundle',InputArgument::OPTIONAL,'Bundle of entity')
            ->addArgument('entity',InputArgument::OPTIONAL,'Entity name')
            ->addOption('ignoreproperty','i',InputOption::VALUE_NONE,'Ignore properties of entity')
            ->addOption('format','f',InputOption::VALUE_OPTIONAL,'Format of export [json,xml,yaml,csv]',BackupFormat::JSON);
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input,$output);
        
        $bundle=null;
        $entity=null;

        // Argument control
        if($input->getArgument('bundle'))
        {
            $bundle = $this->backup->getBundleByName($input->getArgument('bundle'));

            if($input->getArgument('entity') && $bundle!=null)
            {
                $entity = $this->backup->getEntityByName($input->getArgument('entity'));
            }
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

        if($entity == null)
        {
            $choicesEntities=[];
            foreach($this->backup->getEntityList($bundle) as $entity)
            {
                $choicesEntities[] = $entity->getName();
            }
            $entity = $this->backup->getEntityByName($this->io->choice('Which entity do you want to backup ?',$choicesEntities));
        }

        $ignoredProperties=[];
        if($input->getOption('ignoreproperty'))
        {
            $properties=[];
            foreach($entity->reflFields as $key => $ref)
            {
                $properties[]=$key;
            }
        
            $question = new ChoiceQuestion(
                'Which property do you want to ignore ? (Multiple : separate by comma)',
                $properties
            );
            $question->setMultiselect(true);

            $ignoredProperties = $this->io->askQuestion($question);
        }

        // Proceed to backup
        $this->io->title('Backup an entity : '.$entity->getName()); 
        
        try
        {
            $filepath=$this->backup->backupEntity($bundle,$entity,$input->getOption('format'),$ignoredProperties);
            $this->io->success('Entity '.$entity->getName()."\n is backup in file\n".$filepath);
            
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