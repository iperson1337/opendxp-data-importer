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

use OpenDxp\Bundle\DataImporterBundle\Mapping\MappingConfigurationFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MappingConfigurationFactoryPass implements CompilerPassInterface
{
    const operator_tag = 'opendxp.datahub.data_importer.operator';

    const data_target_tag = 'opendxp.datahub.data_importer.data_target';

    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::operator_tag);
        $operators = [];
        if (sizeof($taggedServices)) {
            foreach ($taggedServices as $id => $tags) {
                foreach ($tags as $attributes) {
                    $operators[$attributes['type']] = new Reference($id);
                }
            }
        }

        $taggedServices = $container->findTaggedServiceIds(self::data_target_tag);
        $dataTargets = [];
        if (sizeof($taggedServices)) {
            foreach ($taggedServices as $id => $tags) {
                foreach ($tags as $attributes) {
                    $dataTargets[$attributes['type']] = new Reference($id);
                }
            }
        }

        $serviceLocator = $container->getDefinition(MappingConfigurationFactory::class);
        $serviceLocator->setArgument('$operatorBluePrints', $operators);
        $serviceLocator->setArgument('$dataTargetBluePrints', $dataTargets);
    }
}
