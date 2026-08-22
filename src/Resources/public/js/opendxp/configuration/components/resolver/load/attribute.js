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

opendxp.registerNS('opendxp.plugin.opendxpDataImporterBundle.configuration.components.resolver.load.attribute');
opendxp.plugin.opendxpDataImporterBundle.configuration.components.resolver.load.attribute = Class.create(opendxp.plugin.opendxpDataImporterBundle.configuration.components.abstractOptionType, {

    type: 'attribute',
    dataApplied: false,

    buildSettingsForm: function() {

        if(!this.form) {
            const languageSelection = Ext.create('Ext.form.ComboBox', {
                store: opendxp.settings.websiteLanguages,
                forceSelection: true,
                fieldLabel: t('language'),
                name: this.dataNamePrefix + 'language',
                value: this.data.language,
                allowBlank: true,
                hidden: true
            });

            const attributeSelection = Ext.create('Ext.form.ComboBox', {
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_attribute_name'),
                name: this.dataNamePrefix + 'attributeName',
                value: this.data.attributeName,
                displayField: 'title',
                valueField: 'key',
                forceSelection: true,
                queryMode: 'local'
            });

            const attributeStore = Ext.create('Ext.data.JsonStore', {
                fields: ['key', 'name', 'localized'],
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    extraParams: {
                        class_id: this.configItemRootContainer.currentDataValues.dataObjectClassId
                    },
                    url: Routing.generate('opendxp_dataimporter_configdataobject_loaddataobjectattributes'),
                    reader: {
                        type: 'json',
                        rootProperty: 'attributes'
                    }
                },

                listeners: {
                    dataChanged: function(store) {
                        if(!this.dataApplied) {
                            attributeSelection.setValue(this.data.attributeName);
                            this.form.isValid();
                            this.dataApplied = true;
                            this.setLanguageVisibility(attributeStore, attributeSelection, languageSelection);
                        }
                    }.bind(this)
                }
            });


            attributeSelection.setStore(attributeStore);
            attributeSelection.on('change', this.setLanguageVisibility.bind(this, attributeStore, attributeSelection, languageSelection));

            this.configItemRootContainer.on(opendxp.plugin.opendxpDataImporterBundle.configuration.events.classChanged,
                function(combo, newValue, oldValue) {
                    attributeStore.proxy.setExtraParam('class_id', newValue);
                    attributeStore.load();
                }
            );

            this.form = Ext.create('DataHub.DataImporter.StructuredValueForm', {
                defaults: {
                    labelWidth: 200,
                    width: 600,
                    allowBlank: false,
                    msgTarget: 'under',
                },
                border: false,
                items: [
                    {
                        xtype: 'combo',
                        fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_data_source_index'),
                        name: this.dataNamePrefix + 'dataSourceIndex',
                        value: this.data.dataSourceIndex,
                        store: this.configItemRootContainer.columnHeaderStore,
                        displayField: 'label',
                        valueField: 'dataIndex',
                        forceSelection: false,
                        queryMode: 'local',
                        triggerOnClick: false
                    },
                    attributeSelection,
                    languageSelection,
                    {
                        xtype: 'checkbox',
                        fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_include_unpublished'),
                        name: this.dataNamePrefix + 'includeUnpublished',
                        value: this.data.hasOwnProperty('includeUnpublished') ? this.data.includeUnpublished : false,
                        inputValue: true
                    }
                ]
            });
        }

        return this.form;
    },

    setLanguageVisibility: function(attributeStore, attributeSelection, languageSelection) {
        const record = attributeStore.findRecord('key', attributeSelection.getValue());
        if(record) {
            languageSelection.setHidden(!record.data.localized);
            if(!record.data.localized) {
                languageSelection.setValue(null);
            }
        }
    }


});
