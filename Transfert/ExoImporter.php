<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UJM\ExoBundle\Transfert;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Claroline\CoreBundle\Library\Transfert\Importer;
use Claroline\CoreBundle\Library\Transfert\RichTextInterface;
use Symfony\Component\Config\Definition\Processor;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Entity\Workspace\Workspace;

/**
 * @DI\Service("claroline.importer.exo_importer")
 * @DI\Tag("claroline.importer")
 */
class ExoImporter extends Importer implements ConfigurationInterface
{
    private $container;
    private $om;

    /**
     * @DI\InjectParams({
     *      "om"        = @DI\Inject("claroline.persistence.object_manager"),
     *      "container" = @DI\Inject("service_container")
     * })
     */
    public function __construct($om, $container)
    {
        $this->container = $container;
        $this->om = $om;
    }

    public function  getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('data');
        $this->addExoDescription($rootNode);

        return $treeBuilder;
    }

    public function getName()
    {
        return 'ujm_exercise';
    }

    public function addExoDescription($rootNode)
    {
        $rootPath = $this->getRootPath();

        $rootNode
            ->children()
                ->arrayNode('file')
                    ->children()
                        ->scalarNode('path')->isRequired()->end()
                        ->scalarNode('version')->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    public function validate(array $data)
    {
        $processor = new Processor();
        $result = $processor->processConfiguration($this, $data);
    }

    public function import(array $data)
    {
        //this is the root of the unzipped archive
        $rootPath = $this->getRootPath();

        $qtiRepos = $this->container->get('ujm.qti_repository');
        $qtiRepos->createDirQTI();

        if ($exercises = opendir($rootPath.'/qti')) {
            while (($exercise = readdir($exercises)) !== false) {
                if ($exercise != '.' && $exercise != '..') {
                    $questions = opendir($rootPath.'/qti/'.$exercise);
                    while (($question = readdir($questions)) !== false) {
                        if ($question != '.' && $question != '..') {
                            $files = opendir($rootPath.'/qti/'.$exercise.'/'.$question);
                            while (($file = readdir($files)) !== false) {
                                if ($file != '.' && $file != '..') {
                                    copy($rootPath.'/qti/'.$exercise.'/'.$question.'/'.$file, $qtiRepos->getUserDir().$file);
                                }
                            }
                        }
                        $qtiRepos->scanFiles();
                    }
               }
           }
        }

        //die();
    }

    public function export(Workspace $workspace, array &$files, $object)
    {
        return array();
    }

    public static function fileNotExists($v, $rootpath)
    {
        $ds = DIRECTORY_SEPARATOR;

        return !file_exists($rootpath . $ds . $v);;
    }
}
