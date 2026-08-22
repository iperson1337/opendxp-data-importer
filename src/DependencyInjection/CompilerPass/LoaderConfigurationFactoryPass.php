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

namespace OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass;

use OpenDxp\Bundle\DataImporterBundle\DataSource\Loader\DataLoaderFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoaderConfigurationFactoryPass implements CompilerPassInterface
{
    const loader_tag = 'opendxp.datahub.data_importer.loader';

    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::loader_tag);
        $loader = [];
        if (sizeof($taggedServices)) {
            foreach ($taggedServices as $id => $tags) {
                foreach ($tags as $attributes) {
                    $loader[$attributes['type']] = new Reference($id);
                }
            }
        }

        $serviceLocator = $container->getDefinition(DataLoaderFactory::class);
        $serviceLocator->setArgument('$dataLoaderBluePrints', $loader);
    }
}
