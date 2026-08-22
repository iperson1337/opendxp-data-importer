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

opendxp.registerNS("opendxp.plugin.datahub.adapter.dataImporterDataObject");
opendxp.plugin.datahub.adapter.dataImporterDataObject = Class.create(opendxp.plugin.datahub.adapter.graphql, {

    createConfigPanel: function(data) {
        let fieldPanel = new opendxp.plugin.opendxpDataImporterBundle.configuration.configItemDataObject(data, this);
    },

    openConfiguration: function (id) {
        var existingPanel = Ext.getCmp("plugin_opendxp_datahub_configpanel_panel_" + id);
        if (existingPanel) {
            this.configPanel.editPanel.setActiveTab(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: Routing.generate('opendxp_dataimporter_configdataobject_get'),
            params: {
                name: id
            },
            success: function (response) {
                let data = Ext.decode(response.responseText);
                this.createConfigPanel(data);
                opendxp.layout.refresh();
            }.bind(this)
        });
    }
});
