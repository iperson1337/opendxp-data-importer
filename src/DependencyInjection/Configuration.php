<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataImporterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('opendxp_data_importer');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('messenger_queue_processing')
                ->addDefaultsIfNotSet()
                ->info('Configure import queue processing via symfony messenger')
                ->children()
                    ->booleanNode('activated')
                        ->defaultFalse()
                        ->info('Activate dispatching messages after import was prepared. Will start import as soon as messages are processed via symfony messenger.')
                    ->end()
                    ->integerNode('worker_count_lifetime')
                        ->defaultValue(60 * 30) //30 minutes
                        ->info('Lifetime of tmp store entry for current worker count entry. After lifetime, the value will be cleared. Default to 30 minutes.')
                    ->end()
                    ->integerNode('worker_count_parallel')
                        ->defaultValue(3)
                        ->info('Count of maximum parallel worker messages for parallel imports.')
                    ->end()
                    ->integerNode('worker_item_count')
                        ->defaultValue(200)
                        ->info('Count of items imported per worker message.')
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
