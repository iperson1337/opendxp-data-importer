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

use OpenDxp\Bundle\DataImporterBundle\DataSource\Interpreter\InterpreterFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class InterpreterConfigurationFactoryPass implements CompilerPassInterface
{
    const interpreter_tag = 'opendxp.datahub.data_importer.interpreter';

    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::interpreter_tag);
        $interpreters = [];
        if (sizeof($taggedServices)) {
            foreach ($taggedServices as $id => $tags) {
                foreach ($tags as $attributes) {
                    $interpreters[$attributes['type']] = new Reference($id);
                }
            }
        }

        $serviceLocator = $container->getDefinition(InterpreterFactory::class);
        $serviceLocator->setArgument('$interpreterBluePrints', $interpreters);
    }
}
