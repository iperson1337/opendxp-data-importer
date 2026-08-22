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

opendxp.registerNS('opendxp.plugin.pimcoreDataImporterBundle.configuration.components.resolver.publish.noChangePublishNew');
opendxp.plugin.pimcoreDataImporterBundle.configuration.components.resolver.publish.noChangePublishNew = Class.create(opendxp.plugin.pimcoreDataImporterBundle.configuration.components.abstractOptionType, {

    type: 'noChangePublishNew',

    buildSettingsForm: function() {

        return null;

    }

});