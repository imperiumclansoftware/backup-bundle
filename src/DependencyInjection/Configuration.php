<?php
namespace ICS\BackupBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {

        $treeBuilder = new TreeBuilder('backup');
        // $treeBuilder->getRootNode()->children()
        //    ->enumNode('type')->values(['json','xml'])->defaultValue('json')->end()
        // ;

        return $treeBuilder;
    }

}
