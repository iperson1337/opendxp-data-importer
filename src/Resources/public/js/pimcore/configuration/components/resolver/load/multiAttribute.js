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

opendxp.registerNS('opendxp.plugin.pimcoreDataImporterBundle.configuration.components.resolver.load.multiAttribute');
opendxp.plugin.pimcoreDataImporterBundle.configuration.components.resolver.load.multiAttribute = Class.create(opendxp.plugin.pimcoreDataImporterBundle.configuration.components.abstractOptionType, {

    type: 'multiAttribute',
    dataApplied: false,
    transformationPipelineItems: [],

    /**
     * Creates a transformation pipeline container with menu items for adding operators
     * @param {Array} data - Array of transformation pipeline items
     * @param {Object} container - The parent container
     * @param {Function} updateCallback - Callback function to be called when pipeline is updated
     * @returns {Object} - The transformation pipeline container
     */
    buildTransformationPipeline: function (data, container, updateCallback) {
        data = Array.isArray(data) ? data : [];
        var transformationPipelineContainer = Ext.create('Ext.Panel', {});

        // Add toolbar with menu
        transformationPipelineContainer.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    text: t('add'),
                    iconCls: 'opendxp_icon_add',
                    menu: this.buildTransformationMenu(transformationPipelineContainer, container, updateCallback)
                }
            ]
        });

        // Add existing items to the pipeline
        data.forEach(item => {
            this.addTransformationPipelineItem(item.type, item, transformationPipelineContainer, container, updateCallback);
        });

        // Recalculate transformation result type after adding all items
        if (data.length > 0) {
            this.recalculateTransformationResultType(container);
        }

        return transformationPipelineContainer;
    },

    /**
     * Builds the menu for adding transformation operators
     * @param {Object} transformationPipelineContainer - The pipeline container
     * @param {Object} container - The parent container
     * @param {Function} updateCallback - Callback function to be called when pipeline is updated
     * @returns {Object} - The menu
     */
    buildTransformationMenu: function(transformationPipelineContainer, container, updateCallback) {
        let addMenu = new Ext.menu.Menu();
        let subMenus = {};
        let menuItemsWithoutGroup = [];
        const itemTypes = this.getSortedOperatorTypes();

        for (let i = 0; i < itemTypes.length; i++) {
            const operator = opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.operator[itemTypes[i]];
            if (!operator || !operator.prototype) {
                console.warn(`Missing operator or prototype for type: ${itemTypes[i]}`);
                continue;
            }

            const menuGroup = operator.prototype.getMenuGroup?.();
            const iconClass = operator.prototype.getIconClass?.();

            const menuItem = {
                iconCls: iconClass || '',
                handler: () => {
                    this.addTransformationPipelineItem(itemTypes[i], {}, transformationPipelineContainer, container, updateCallback);
                    this.recalculateTransformationResultType(container);
                },
                text: t('plugin_pimcore_datahub_data_importer_configpanel_transformation_pipeline_' + itemTypes[i])
            };

            if (menuGroup) {
                if (!subMenus[menuGroup.text]) {
                    subMenus[menuGroup.text] = [];
                    subMenus[menuGroup.text]['icon'] = menuGroup.icon;
                }
                subMenus[menuGroup.text].push(menuItem);
            } else {
                menuItemsWithoutGroup.push(new Ext.menu.Item(menuItem));
            }
        }

        // Add grouped menu items
        Object.entries(subMenus).forEach(([menuText, items]) => {
            addMenu.add(new Ext.menu.Item({
                text: menuText,
                iconCls: items.icon || '',
                menu: items,
                hideOnClick: false
            }));
        });

        // Add ungrouped menu items
        menuItemsWithoutGroup.forEach(item => addMenu.add(item));

        return addMenu;
    },

    /**
     * Gets sorted operator types
     * @returns {Array} - Sorted array of operator types
     */
    getSortedOperatorTypes: function() {
        const itemTypes = Object.keys(opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.operator);

        return itemTypes.sort((item1, item2) => {
            const str1 = t('plugin_pimcore_datahub_data_importer_configpanel_transformation_pipeline_' + item1);
            const str2 = t('plugin_pimcore_datahub_data_importer_configpanel_transformation_pipeline_' + item2);
            return str1.localeCompare(str2);
        });
    },

    /**
     * Adds a transformation pipeline item to the pipeline container
     * @param {string} type - The type of the transformation operator
     * @param {Object} data - The data for the transformation operator
     * @param {Object} pipelineContainer - The pipeline container
     * @param {Object} attributeContainer - The attribute container
     * @param {Function} updateCallback - Callback function to be called when pipeline is updated
     */
    addTransformationPipelineItem: function (type, data, pipelineContainer, attributeContainer, updateCallback) {
        try {
            const OperatorClass = opendxp.plugin.pimcoreDataImporterBundle.configuration.components.mapping.operator[type];
            if (!OperatorClass) {
                console.error(`Unknown transformation operator type: ${type}`);
                return;
            }

            const item = new OperatorClass(
                data,
                pipelineContainer,
                this.createOperatorChangeCallback(pipelineContainer, attributeContainer, updateCallback),
                this.createOperatorRemoveCallback(pipelineContainer, attributeContainer, updateCallback)
            );

            pipelineContainer.add(item.buildTransformationPipelineItem());
        } catch (e) {
            console.error(`Error adding transformation pipeline item of type: ${type}`, e);
        }
    },

    /**
     * Creates a callback function for when an operator is changed
     * @param {Object} pipelineContainer - The pipeline container
     * @param {Object} attributeContainer - The attribute container
     * @param {Function} updateCallback - Callback function to be called when pipeline is updated
     * @returns {Function} - The callback function
     */
    createOperatorChangeCallback: function(pipelineContainer, attributeContainer, updateCallback) {
        return () => {
            const pipelineData = this.collectPipelineData(pipelineContainer);
            this.updateHiddenField(attributeContainer, pipelineData);
            this.recalculateTransformationResultType(attributeContainer);
            if (updateCallback) updateCallback();
        };
    },

    /**
     * Creates a callback function for when an operator is removed
     * @param {Object} pipelineContainer - The pipeline container
     * @param {Object} attributeContainer - The attribute container
     * @param {Function} updateCallback - Callback function to be called when pipeline is updated
     * @returns {Function} - The callback function
     */
    createOperatorRemoveCallback: function(pipelineContainer, attributeContainer, updateCallback) {
        return () => {
            const pipelineData = this.collectPipelineData(pipelineContainer);
            this.updateHiddenField(attributeContainer, pipelineData);
            this.recalculateTransformationResultType(attributeContainer);
            if (updateCallback) updateCallback();
        };
    },

    /**
     * Collects data from all pipeline items
     * @param {Object} pipelineContainer - The pipeline container
     * @returns {Array} - Array of pipeline item data
     */
    collectPipelineData: function(pipelineContainer) {
        const pipelineData = [];
        pipelineContainer.items.each(pipelineItem => {
            if (pipelineItem.operatorImplementation) {
                pipelineData.push(pipelineItem.operatorImplementation.getValues());
            }
        });
        return pipelineData;
    },

    /**
     * Updates the hidden field with pipeline data
     * @param {Object} attributeContainer - The attribute container
     * @param {Array} pipelineData - The pipeline data
     */
    updateHiddenField: function(attributeContainer, pipelineData) {
        const hiddenField = attributeContainer.down('[name=transformationPipeline]');
        if (hiddenField) {
            hiddenField.setValue(pipelineData);
        }
    },

    /**
     * Recalculates the transformation result type based on the current configuration
     * @param {Object} attributePanel - The attribute panel
     */
    recalculateTransformationResultType: function (attributePanel) {
        const currentConfig = this.buildTransformationConfig(attributePanel);
        const configName = this.getConfigName();

        this.requestTransformationResultType(attributePanel, currentConfig, configName);
    },

    /**
     * Builds the transformation configuration object for the AJAX request
     * @param {Object} attributePanel - The attribute panel
     * @returns {Object} - The configuration object
     */
    buildTransformationConfig: function(attributePanel) {
        const attributeName = attributePanel.down('[name=attributeName]').getValue();
        const dataSourceIndex = attributePanel.down('[name=dataSourceIndex]').getValue();
        const transformationPipeline = this.getTransformationPipelineFromPanel(attributePanel);

        return {
            attributeName: attributeName,
            dataSourceIndex: dataSourceIndex,
            transformationPipeline: Array.isArray(transformationPipeline) ? transformationPipeline : []
        };
    },

    /**
     * Gets the transformation pipeline data from the panel
     * @param {Object} attributePanel - The attribute panel
     * @returns {Array} - The transformation pipeline data
     */
    getTransformationPipelineFromPanel: function(attributePanel) {
        let transformationPipeline = [];
        const transformationPipelineFieldset = attributePanel.down('#transformationPipelineFieldset');

        if (transformationPipelineFieldset && transformationPipelineFieldset.items && transformationPipelineFieldset.items.items.length > 0) {
            const pipelineContainer = transformationPipelineFieldset.items.items[0];
            if (pipelineContainer && pipelineContainer.items) {
                pipelineContainer.items.each(function(pipelineItem) {
                    if (pipelineItem.operatorImplementation) {
                        transformationPipeline.push(pipelineItem.operatorImplementation.getValues());
                    }
                });
            }
        }

        return transformationPipeline;
    },

    /**
     * Gets the configuration name
     * @returns {string} - The configuration name
     */
    getConfigName: function() {
        return this.configItemRootContainer && this.configItemRootContainer.configName
            ? this.configItemRootContainer.configName
            : '';
    },

    /**
     * Sends an AJAX request to calculate the transformation result type
     * @param {Object} attributePanel - The attribute panel
     * @param {Object} currentConfig - The current configuration
     * @param {string} configName - The configuration name
     */
    requestTransformationResultType: function(attributePanel, currentConfig, configName) {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_dataimporter_configdataobject_calculatetransformationresulttype'),
            method: 'POST',
            params: {
                config_name: configName,
                current_config: Ext.encode(currentConfig),
                system_read: 1
            },
            success: function (response) {
                this.handleTransformationResultTypeResponse(attributePanel, response);
            }.bind(this)
        });
    },

    /**
     * Handles the response from the transformation result type calculation
     * @param {Object} attributePanel - The attribute panel
     * @param {Object} response - The AJAX response
     */
    handleTransformationResultTypeResponse: function(attributePanel, response) {
        let data = Ext.decode(response.responseText);

        this.updateTransformationResultTypeUI(attributePanel, data);
        this.updateAttributeStoreIfNeeded(attributePanel, data);
    },

    /**
     * Updates the transformation result type UI elements
     * @param {Object} attributePanel - The attribute panel
     * @param {string} resultType - The transformation result type
     */
    updateTransformationResultTypeUI: function(attributePanel, resultType) {
        console.log(resultType)
        // Update the hidden field
        const transformationResultTypeField = attributePanel.down('[name=transformationResultType]');
        if (transformationResultTypeField) {
            transformationResultTypeField.setValue(resultType);
        }

        // Update the label
        const transformationResultTypeLabel = attributePanel.down('#transformationResultTypeLabel');
        if (transformationResultTypeLabel) {
            transformationResultTypeLabel.setHtml(resultType);
        }
    },

    /**
     * Updates the attribute store whenever the transformation result type changes
     * @param {Object} attributePanel - The attribute panel
     * @param {string} newResultType - The new transformation result type
     */
    updateAttributeStoreIfNeeded: function(attributePanel, newResultType) {
        const transformationResultTypeField = attributePanel.down('[name=transformationResultType]');
        const currentResultType = transformationResultTypeField ? transformationResultTypeField.getValue() : 'default';

        if (attributePanel.attributeStore) {
            const attributeNameField = attributePanel.down('[name=attributeName]');
            const dataSourceIndexField = attributePanel.down('[name=dataSourceIndex]');
            const attributeName = attributeNameField.getValue();
            const dataSourceIndex = dataSourceIndexField.getValue();

            // Reinitialize the attribute store with a callback that restores the values
            this.initAttributeStore(attributePanel, attributePanel.attributeStore, function(store) {
                attributeNameField.setValue(attributeName);
                dataSourceIndexField.setValue(dataSourceIndex);
            });
        }
    },

    /**
     * Initializes the attribute store with data from the server
     * @param {Object} panel - The panel containing the attribute store
     * @param {Object} attributeStore - The attribute store to initialize
     * @param {Function} callback - Callback function to be called after initialization
     */
    initAttributeStore: function (panel, attributeStore, callback) {
        const classId = this.getClassId();
        const transformationResultType = this.getTransformationResultType(panel);

        let targetFieldCache = this.getOrCreateTargetFieldCache();

        if (this.isCacheAvailable(targetFieldCache, classId, transformationResultType)) {
            this.handleCachedAttributes(targetFieldCache, classId, transformationResultType, panel, attributeStore, callback);
        } else {
            this.loadAttributesFromServer(targetFieldCache, classId, transformationResultType, attributeStore, callback);
        }
    },

    /**
     * Gets the class ID from the root container
     * @returns {string} - The class ID
     */
    getClassId: function() {
        return this.configItemRootContainer.currentDataValues.dataObjectClassId;
    },

    /**
     * Gets the transformation result type from the panel
     * @param {Object} panel - The panel
     * @returns {string} - The transformation result type
     */
    getTransformationResultType: function(panel) {
        const transformationResultTypeField = panel.down('[name=transformationResultType]');
        return transformationResultTypeField ? transformationResultTypeField.getValue() : 'default';
    },

    /**
     * Gets or creates the target field cache
     * @returns {Object} - The target field cache
     */
    getOrCreateTargetFieldCache: function() {
        return this.configItemRootContainer.targetFieldCache || {};
    },

    /**
     * Checks if the cache is available for the given class ID and transformation result type
     * @param {Object} cache - The cache object
     * @param {string} classId - The class ID
     * @param {string} transformationResultType - The transformation result type
     * @returns {boolean} - True if cache is available, false otherwise
     */
    isCacheAvailable: function(cache, classId, transformationResultType) {
        return cache[classId] && cache[classId][transformationResultType];
    },

    /**
     * Handles cached attributes
     * @param {Object} cache - The cache object
     * @param {string} classId - The class ID
     * @param {string} transformationResultType - The transformation result type
     * @param {Object} panel - The panel
     * @param {Object} attributeStore - The attribute store
     * @param {Function} callback - Callback function
     */
    handleCachedAttributes: function(cache, classId, transformationResultType, panel, attributeStore, callback) {
        if (cache[classId][transformationResultType].loading) {
            // If still loading, retry after a delay
            setTimeout(this.initAttributeStore.bind(this, panel, attributeStore, callback), 400);
        } else {
            // Use cached data
            attributeStore.loadData(cache[classId][transformationResultType].data);
            if (callback) {
                callback(attributeStore);
            }
        }
    },

    /**
     * Loads attributes from the server
     * @param {Object} cache - The cache object
     * @param {string} classId - The class ID
     * @param {string} transformationResultType - The transformation result type
     * @param {Object} attributeStore - The attribute store
     * @param {Function} callback - Callback function
     */
    loadAttributesFromServer: function(cache, classId, transformationResultType, attributeStore, callback) {
        // Initialize cache entry
        cache = cache || {};
        cache[classId] = cache[classId] || {};
        cache[classId][transformationResultType] = {
            loading: true,
            data: null
        };
        this.configItemRootContainer.targetFieldCache = cache;

        // Request data from server
        Ext.Ajax.request({
            url: Routing.generate('opendxp_dataimporter_configdataobject_loaddataobjectattributes'),
            method: 'GET',
            params: {
                'class_id': classId,
                'transformation_result_type': transformationResultType,
                'system_read': 1
            },
            success: function (response) {
                this.handleAttributesResponse(response, cache, classId, transformationResultType, attributeStore, callback);
            }.bind(this)
        });
    },

    /**
     * Handles the response from the server with attributes data
     * @param {Object} response - The AJAX response
     * @param {Object} cache - The cache object
     * @param {string} classId - The class ID
     * @param {string} transformationResultType - The transformation result type
     * @param {Object} attributeStore - The attribute store
     * @param {Function} callback - Callback function
     */
    handleAttributesResponse: function(response, cache, classId, transformationResultType, attributeStore, callback) {
        let data = Ext.decode(response.responseText);

        // Update cache
        cache[classId][transformationResultType].loading = false;
        cache[classId][transformationResultType].data = data.attributes;

        // Update store and call callback
        attributeStore.loadData(cache[classId][transformationResultType].data);
        if (callback) {
            callback(attributeStore);
        }
    },

    /**
     * Builds the settings form for the multiAttribute component
     * @returns {Object} - The settings form
     */
    buildSettingsForm: function() {
        if(!this.form) {
            // Create form components
            const attributeMappingField = this.createAttributeMappingField();
            const attributeMappingContainer = this.createAttributeMappingContainer();
            const updateAttributeMappingField = this.createUpdateAttributeMappingFieldFunction(attributeMappingContainer, attributeMappingField);
            const addButton = this.createAddButton(attributeMappingContainer, updateAttributeMappingField);

            // Add existing attribute mappings
            this.addExistingAttributeMappings(attributeMappingContainer, updateAttributeMappingField);

            // Set up class change listener
            this.setupClassChangeListener(attributeMappingContainer);

            // Create the form
            this.form = this.createForm(attributeMappingField, addButton, attributeMappingContainer);
        }

        return this.form;
    },

    /**
     * Creates an array field to store attribute mapping data
     * @returns {Object} - The attribute mapping field
     */
    createAttributeMappingField: function() {
        return Ext.create('DataHub.DataImporter.ArrayField', {
            name: this.dataNamePrefix + 'attributeMapping',
            value: this.data.attributeMapping || [],
            hidden: true
        });
    },

    /**
     * Creates a container for attribute mapping items
     * @returns {Object} - The attribute mapping container
     */
    createAttributeMappingContainer: function() {
        return Ext.create('Ext.panel.Panel', {
            layout: 'anchor',
            border: false,
            style: {
                marginBottom: '10px'
            },
            items: []
        });
    },

    /**
     * Creates a function to update the attribute mapping field with current values
     * @param {Object} attributeMappingContainer - The attribute mapping container
     * @param {Object} attributeMappingField - The attribute mapping field
     * @returns {Function} - The update function
     */
    createUpdateAttributeMappingFieldFunction: function(attributeMappingContainer, attributeMappingField) {
        return function() {
            const attributeMapping = [];
            attributeMappingContainer.items.each(function(item) {
                const attributeName = item.down('[name=attributeName]').getValue();
                const dataSourceIndex = item.down('[name=dataSourceIndex]').getValue();
                const transformationPipeline = this.getTransformationPipelineFromPanel(item);
                const transformationResultType = item.down('[name=transformationResultType]').getValue();

                attributeMapping.push({
                    attributeName: attributeName,
                    dataSourceIndex: dataSourceIndex,
                    transformationPipeline: transformationPipeline.length > 0 ? transformationPipeline : [],
                    transformationResultType: transformationResultType
                });
            }, this);

            attributeMappingField.setValue(attributeMapping);
        }.bind(this);
    },

    /**
     * Creates a button to add new attribute mapping
     * @param {Object} attributeMappingContainer - The attribute mapping container
     * @param {Function} updateAttributeMappingField - The update function
     * @returns {Object} - The add button
     */
    createAddButton: function(attributeMappingContainer, updateAttributeMappingField) {
        return Ext.create('Ext.button.Button', {
            text: t('add'),
            iconCls: 'opendxp_icon_add',
            handler: function() {
                this.addAttributeMappingPanel(attributeMappingContainer, null, updateAttributeMappingField);
                updateAttributeMappingField();
            }.bind(this)
        });
    },

    /**
     * Adds existing attribute mappings to the container
     * @param {Object} attributeMappingContainer - The attribute mapping container
     * @param {Function} updateAttributeMappingField - The update function
     */
    addExistingAttributeMappings: function(attributeMappingContainer, updateAttributeMappingField) {
        if (this.data.attributeMapping && this.data.attributeMapping.length > 0) {
            this.data.attributeMapping.forEach(function(mapping) {
                this.addAttributeMappingPanel(attributeMappingContainer, mapping, updateAttributeMappingField);
            }, this);
        }
    },

    /**
     * Sets up a listener for class changes
     * @param {Object} attributeMappingContainer - The attribute mapping container
     */
    setupClassChangeListener: function(attributeMappingContainer) {
        this.configItemRootContainer.on(opendxp.plugin.pimcoreDataImporterBundle.configuration.events.classChanged,
            function(combo, newValue, oldValue) {
                // Update attribute store for each panel when class changes
                attributeMappingContainer.items.each(function(panel) {
                    if (panel.attributeStore) {
                        this.updatePanelAttributeStore(panel);
                    }
                }, this);
            }.bind(this)
        );
    },

    /**
     * Updates the attribute store for a panel
     * @param {Object} panel - The panel
     */
    updatePanelAttributeStore: function(panel) {
        // Get the current values from the panel
        const attributeNameField = panel.down('[name=attributeName]');
        const dataSourceIndexField = panel.down('[name=dataSourceIndex]');
        const attributeName = attributeNameField.getValue();
        const dataSourceIndex = dataSourceIndexField.getValue();

        // Reinitialize the attribute store with a callback that restores the values
        this.initAttributeStore(panel, panel.attributeStore, function(store) {
            attributeNameField.setValue(attributeName);
            dataSourceIndexField.setValue(dataSourceIndex);
        });
    },

    /**
     * Creates the form
     * @param {Object} attributeMappingField - The attribute mapping field
     * @param {Object} addButton - The add button
     * @param {Object} attributeMappingContainer - The attribute mapping container
     * @returns {Object} - The form
     */
    createForm: function(attributeMappingField, addButton, attributeMappingContainer) {
        return Ext.create('DataHub.DataImporter.StructuredValueForm', {
            defaults: {
                labelWidth: 200,
                width: 800,
                allowBlank: false,
                msgTarget: 'under',
            },
            border: false,
            collapsed: false,
            collapsible: false,
            titleCollapse: false,
            hideCollapseTool: true,
            headerOverCls: 'data_hub_cursor_pointer',
            cls: 'data_hub_mapping_panel',
            collapsedCls: 'data_hub_collapsed',
            items: [
                {
                    xtype: 'fieldset',
                    title: t('plugin_pimcore_datahub_data_importer_configpanel_attribute_mapping_details'),
                    collapsible: false,
                    collapsed: false,
                    items: [
                        attributeMappingField,
                        {
                            xtype: 'container',
                            layout: 'hbox',
                            margin: '0 0 10 0',
                            items: [addButton]
                        },
                        attributeMappingContainer
                    ]
                },
                {
                    xtype: 'checkbox',
                    fieldLabel: t('plugin_pimcore_datahub_data_importer_configpanel_include_unpublished'),
                    name: this.dataNamePrefix + 'includeUnpublished',
                    value: this.data.hasOwnProperty('includeUnpublished') ? this.data.includeUnpublished : false,
                    inputValue: true
                }
            ],
            listeners: {
                beforesubmit: this.createBeforeSubmitHandler()
            }
        });
    },

    /**
     * Creates a handler for the beforesubmit event
     * @returns {Function} - The handler function
     */
    createBeforeSubmitHandler: function() {
        return function() {
            // Filter out attributeName, dataSourceIndex, and transformationResultType from the attributeMapping array
            const attributeMappingField = this.form.down('[name=' + this.dataNamePrefix + 'attributeMapping]');
            if (attributeMappingField) {
                const attributeMapping = attributeMappingField.getValue();
                if (Array.isArray(attributeMapping)) {
                    const filteredMapping = attributeMapping.map(function(item) {
                        // Create a deep copy of the transformationPipeline
                        const pipeline = item.transformationPipeline || [];
                        const serializedPipeline = JSON.parse(JSON.stringify(pipeline));
                        return {
                            transformationPipeline: serializedPipeline,
                            transformationResultType: item.transformationResultType
                        };
                    });
                    attributeMappingField.setValue(filteredMapping);
                }
            }
        }.bind(this);
    },

    /**
     * Adds an attribute mapping panel to the container
     * @param {Object} attributeMappingContainer - The attribute mapping container
     * @param {Object} attributeData - The attribute data
     * @param {Function} updateCallback - The update callback function
     * @returns {Object} - The panel
     */
    addAttributeMappingPanel: function(attributeMappingContainer, attributeData, updateCallback) {
        // Create a local attribute store for this panel
        const attributeStore = Ext.create('Ext.data.JsonStore', {
            fields: ['key', 'title', 'localized']
        });

        const panel = this.createAttributeMappingPanel(attributeStore, attributeData, updateCallback);
        attributeMappingContainer.add(panel);

        // Initialize the attribute store
        this.initAttributeStore(panel, attributeStore, function(store) {
            this.initializeAttributeMappingPanel(panel, attributeData, updateCallback);
        }.bind(this));

        return panel;
    },

    /**
     * Creates an attribute mapping panel
     * @param {Object} attributeStore - The attribute store
     * @param {Object} attributeData - The attribute data
     * @param {Function} updateCallback - The update callback function
     * @returns {Object} - The panel
     */
    createAttributeMappingPanel: function(attributeStore, attributeData, updateCallback) {
        return Ext.create('Ext.panel.Panel', {
            layout: 'anchor',
            border: true,
            bodyPadding: 10,
            collapsible: true,
            collapsed: false,
            titleCollapse: true,
            title: attributeData && attributeData.attributeName ? attributeData.attributeName : t('plugin_pimcore_datahub_data_importer_configpanel_attribute_mapping_item'),
            cls: 'data_hub_mapping_panel',
            headerOverCls: 'data_hub_cursor_pointer',
            collapsedCls: 'data_hub_collapsed',
            hideCollapseTool: true,
            attributeStore: attributeStore, // Store reference to the attribute store in the panel
            tools: [{
                type: 'close',
                handler: function(owner, tool, event) {
                    const ownerContainer = event.container.component.ownerCt;
                    ownerContainer.remove(event.container.component, true);
                    if (updateCallback) updateCallback();
                }
            }],
            items: this.createAttributeMappingPanelItems(attributeStore, attributeData, updateCallback)
        });
    },

    /**
     * Creates the items for an attribute mapping panel
     * @param {Object} attributeStore - The attribute store
     * @param {Object} attributeData - The attribute data
     * @param {Function} updateCallback - The update callback function
     * @returns {Array} - The panel items
     */
    createAttributeMappingPanelItems: function(attributeStore, attributeData, updateCallback) {
        return [
            {
                xtype: 'combo',
                fieldLabel: t('plugin_pimcore_datahub_data_importer_configpanel_attribute_name'),
                name: 'attributeName',
                value: '', // Will be set after store is loaded
                displayField: 'title',
                valueField: 'key',
                forceSelection: true,
                queryMode: 'local',
                store: attributeStore,
                allowBlank: false,
                anchor: '100%',
                listeners: {
                    change: function(field, newValue) {
                        if (updateCallback) updateCallback();
                        this.updatePanelTitle(field, newValue);
                    }.bind(this)
                }
            },
            {
                xtype: 'combo',
                fieldLabel: t('plugin_pimcore_datahub_data_importer_configpanel_data_source_index'),
                name: 'dataSourceIndex',
                value: '', // Will be set after store is loaded
                store: this.configItemRootContainer.columnHeaderStore,
                displayField: 'label',
                valueField: 'dataIndex',
                forceSelection: false,
                queryMode: 'local',
                triggerOnClick: false,
                allowBlank: false,
                anchor: '100%',
                listeners: {
                    change: function(field, newValue) {
                        if (updateCallback) updateCallback();
                        // Recalculate transformation result type
                        const panel = field.up('panel');
                        if (panel) {
                            this.recalculateTransformationResultType(panel);
                        }
                    }.bind(this)
                }
            },
            {
                xtype: 'hidden',
                name: 'transformationPipeline',
                value: attributeData && attributeData.transformationPipeline && Array.isArray(attributeData.transformationPipeline) ? attributeData.transformationPipeline : []
            },
            {
                xtype: 'fieldset',
                title: t('plugin_pimcore_datahub_data_importer_configpanel_transformation_pipeline'),
                collapsible: true,
                collapsed: true,
                itemId: 'transformationPipelineFieldset'
            },
            {
                xtype: 'hidden',
                name: 'transformationResultType',
                value: attributeData ? attributeData.transformationResultType : 'default'
            },
            {
                xtype: 'fieldset',
                title: t('plugin_pimcore_datahub_data_importer_configpanel_transformation_result'),
                items: [{
                    xtype: 'fieldcontainer',
                    fieldLabel: t('plugin_pimcore_datahub_data_importer_configpanel_transformation_result_type'),
                    layout: 'hbox',
                    items: [
                        Ext.create('Ext.form.Label', {
                            html: attributeData ? attributeData.transformationResultType : 'default',
                            itemId: 'transformationResultTypeLabel'
                        })
                    ]
                }]
            }
        ];
    },

    /**
     * Updates the panel title with the attribute name
     * @param {Object} field - The field
     * @param {string} newValue - The new value
     */
    updatePanelTitle: function(field, newValue) {
        const panel = field.up('panel');
        if (panel) {
            // Get the display value for the title
            let displayValue = newValue;
            const record = panel.attributeStore.findRecord('key', newValue);
            if (record) {
                displayValue = record.get('title');
            }
            panel.setTitle(displayValue || t('plugin_pimcore_datahub_data_importer_configpanel_attribute_mapping_item'));
        }
    },

    /**
     * Initializes an attribute mapping panel
     * @param {Object} panel - The panel
     * @param {Object} attributeData - The attribute data
     * @param {Function} updateCallback - The update callback function
     */
    initializeAttributeMappingPanel: function(panel, attributeData, updateCallback) {
        // Set the combo box values after the store is loaded
        const attributeNameField = panel.down('[name=attributeName]');
        const dataSourceIndexField = panel.down('[name=dataSourceIndex]');

        if (attributeData) {
            if (attributeData.attributeName) {
                attributeNameField.setValue(attributeData.attributeName);
            }
            if (attributeData.dataSourceIndex) {
                dataSourceIndexField.setValue(attributeData.dataSourceIndex);
            }
        }

        // Add transformation pipeline to the fieldset
        this.addTransformationPipelineToPanel(panel, attributeData, updateCallback);

        // If we have a transformationPipeline but no transformationResultType, calculate it
        if (attributeData && attributeData.transformationPipeline && attributeData.transformationPipeline.length > 0 && !attributeData.transformationResultType) {
            this.recalculateTransformationResultType(panel);
        }
    },

    /**
     * Adds a transformation pipeline to a panel
     * @param {Object} panel - The panel
     * @param {Object} attributeData - The attribute data
     * @param {Function} updateCallback - The update callback function
     */
    addTransformationPipelineToPanel: function(panel, attributeData, updateCallback) {
        const transformationPipelineFieldset = panel.down('#transformationPipelineFieldset');
        if (transformationPipelineFieldset) {
            const transformationPipeline = this.buildTransformationPipeline(
                attributeData && attributeData.transformationPipeline && Array.isArray(attributeData.transformationPipeline) ? attributeData.transformationPipeline : [],
                panel,
                updateCallback
            );
            transformationPipelineFieldset.add(transformationPipeline);
        }
    }
});
