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

opendxp.registerNS("opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.operator.time");
opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.operator.time = Class.create(opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.abstractOperator, {

    type: 'time',

    getMenuGroup: function() {
        return this.menuGroups.dataTypes;
    },

    getIconClass: function() {
        return "opendxp_icon_time";
    },

    getFormItems: function() {
        return [
            {
                xtype: 'textfield',
                fieldLabel: t('plugin_pimcore_datahub_data_importer_configpanel_transformation_pipeline_format'),
                value: this.data.settings ? this.data.settings.format : 'H:i',
                listeners: {
                    change: this.inputChangePreviewUpdate.bind(this)
                },
                name: 'settings.format'
            }
        ];
    }

});
