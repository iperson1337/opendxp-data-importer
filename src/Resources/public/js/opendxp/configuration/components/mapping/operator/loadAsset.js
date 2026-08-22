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

opendxp.registerNS("opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.operator.loadAsset");
opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.operator.loadAsset = Class.create(opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.abstractOperator, {

    type: 'loadAsset',

    getMenuGroup: function() {
        return this.menuGroups.loadImport;
    },

    getIconClass: function() {
        return "opendxp_icon_asset opendxp_icon_overlay_add";
    },

    getFormItems: function() {
        return [
            {
                xtype: 'combo',
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_transformation_pipeline_asset_load_strategy'),
                value: this.data.settings ? this.data.settings.loadStrategy : 'path',
                listeners: {
                    change: this.inputChangePreviewUpdate.bind(this)
                },
                name: 'settings.loadStrategy',
                store: [
                    ['path', t('plugin_opendxp_datahub_data_importer_configpanel_find_strategy_path')],
                    ['id', t('plugin_opendxp_datahub_data_importer_configpanel_find_strategy_id')],
                ]
            }
        ];
    }

});
