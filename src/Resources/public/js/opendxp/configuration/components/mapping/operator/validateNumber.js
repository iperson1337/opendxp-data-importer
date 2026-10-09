/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 */

opendxp.registerNS("opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.operator.validateNumber");
opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.operator.validateNumber = Class.create(opendxp.plugin.opendxpDataImporterBundle.configuration.components.mapping.abstractOperator, {

    type: 'validateNumber',

    getMenuGroup: function() {
        return this.menuGroups.dataManipulation;
    },

    getIconClass: function() {
        return "opendxp_icon_data_group_numeric";
    },

    getFormItems: function() {
        const settings = this.data.settings || {};

        return [
            {
                xtype: 'textfield',
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_transformation_pipeline_validate_number_label'),
                value: settings.label || '',
                name: 'settings.label'
            },
            {
                xtype: 'checkbox',
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_transformation_pipeline_validate_number_required'),
                // Новый оператор — обязательное значение: так его и добавляют в конвейер
                value: settings.hasOwnProperty('required') ? settings.required : true,
                inputValue: 'on',
                uncheckedValue: 'off',
                listeners: {
                    change: this.inputChangePreviewUpdate.bind(this)
                },
                name: 'settings.required'
            },
            {
                xtype: 'checkbox',
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_transformation_pipeline_validate_number_integer_only'),
                value: settings.integerOnly || false,
                inputValue: 'on',
                listeners: {
                    change: this.inputChangePreviewUpdate.bind(this)
                },
                name: 'settings.integerOnly'
            },
            {
                xtype: 'textfield',
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_transformation_pipeline_validate_number_greater_than'),
                value: settings.greaterThan || '',
                listeners: {
                    change: this.inputChangePreviewUpdate.bind(this)
                },
                name: 'settings.greaterThan'
            }
        ];
    }

});
