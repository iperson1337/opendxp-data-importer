<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass;

use OpenDxp\Bundle\DataImporterBundle\Resolver\Factory\MultiAttributeConfigurationFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MultiAttributeConfigurationFactoryPass implements CompilerPassInterface
{
    const string operator_tag = 'opendxp.datahub.data_importer.operator';

    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds(self::operator_tag);
        $operators = [];
        if (count($taggedServices)) {
            foreach ($taggedServices as $id => $tags) {
                foreach ($tags as $attributes) {
                    $operators[$attributes['type']] = new Reference($id);
                }
            }
        }

        $serviceLocator = $container->getDefinition(MultiAttributeConfigurationFactory::class);
        $serviceLocator->setArgument('$operatorBluePrints', $operators);
    }
}
