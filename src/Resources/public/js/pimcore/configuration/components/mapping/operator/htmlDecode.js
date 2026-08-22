/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.opendxp.org)
 *  @license    http://www.opendxp.org/license     GPLv3 and PCL
 */

opendxp.registerNS("opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.operator.htmlDecode");
opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.operator.htmlDecode = Class.create(opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.abstractOperator, {

    type: 'htmlDecode',

    getMenuGroup: function() {
        return this.menuGroups.dataManipulation;
    },
    getIconClass: function() {
        return "opendxp_icon_html";
    },
});