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

opendxp.registerNS("opendxp.plugin.pimcoreDataImporterBundle.configuration.components.interpreter.json");
opendxp.plugin.pimcoreDataImporterBundle.configuration.components.interpreter.json = Class.create(opendxp.plugin.pimcoreDataImporterBundle.configuration.components.abstractOptionType, {

    type: 'json',

    buildSettingsForm: function() {

        if(!this.form) {
            this.form = Ext.create('DataHub.DataImporter.StructuredValueForm', {
                defaults: {
                    labelWidth: 200,
                    width: 600
                },
                border: false,
                items: [
                ]
            });
        }

        return this.form;
    }

});