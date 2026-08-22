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

opendxp.registerNS('opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.operator.reduceArrayKeyValuePairs');
opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.operator.reduceArrayKeyValuePairs = Class.create(opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.abstractOperator, {

    type: 'reduceArrayKeyValuePairs',

    getMenuGroup: function() {
        return this.menuGroups.dataManipulation;
    },
});
