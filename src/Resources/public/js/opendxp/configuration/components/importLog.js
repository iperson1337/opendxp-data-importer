/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

/**
 * Журнал одного импорта для тех, у кого нет права `application_logging`: читает только записи
 * этого импорта через /import-log (доступ — как на чтение конфига). Используется во вкладке
 * «Логи» и в окне «Отклонённые» / «Не найдены» вкладки «Выполнение».
 */
opendxp.registerNS('opendxp.plugin.opendxpDataImporterBundle.configuration.components.importLog');
opendxp.plugin.opendxpDataImporterBundle.configuration.components.importLog = Class.create({

    configName: '',

    initialize: function(configName) {
        this.configName = configName;
    },

    getTabPanel: function() {
        return this.buildGrid('all', true, {
            title: t('plugin_opendxp_datahub_data_importer_configpanel_logs')
        });
    },

    openWindow: function(kind) {
        const titles = {
            rejected: t('plugin_opendxp_datahub_data_importer_log_rejected'),
            notFound: t('plugin_opendxp_datahub_data_importer_log_not_found')
        };

        Ext.create('Ext.window.Window', {
            title: (titles[kind] || t('plugin_opendxp_datahub_data_importer_configpanel_logs')) + ' — ' + this.configName,
            width: Math.min(1100, window.innerWidth - 100),
            height: Math.min(600, window.innerHeight - 100),
            layout: 'fit',
            modal: true,
            items: [this.buildGrid(kind, true, {})]
        }).show();
    },

    buildGrid: function(kind, onlyLastRun, panelConfig) {
        const store = Ext.create('Ext.data.Store', {
            pageSize: 50,
            remoteSort: false,
            fields: ['id', 'timestamp', 'priority', 'message', 'relatedobject', 'relatedobjecttype'],
            proxy: {
                type: 'ajax',
                url: Routing.generate('opendxp_dataimporter_configdataobject_importlog'),
                extraParams: {
                    config_name: this.configName,
                    kind: kind,
                    onlyLastRun: onlyLastRun ? 1 : 0
                },
                reader: {
                    type: 'json',
                    rootProperty: 'data',
                    totalProperty: 'total'
                }
            },
            autoLoad: true
        });

        const setParam = function(name, value) {
            store.getProxy().setExtraParam(name, value);
            store.loadPage(1);
        };

        const kindCombo = Ext.create('Ext.form.ComboBox', {
            fieldLabel: t('plugin_opendxp_datahub_data_importer_log_show'),
            labelWidth: 70,
            width: 280,
            editable: false,
            value: kind,
            store: [
                ['all', t('plugin_opendxp_datahub_data_importer_log_all')],
                ['rejected', t('plugin_opendxp_datahub_data_importer_log_rejected')],
                ['notFound', t('plugin_opendxp_datahub_data_importer_log_not_found')],
                ['imported', t('plugin_opendxp_datahub_data_importer_log_imported')]
            ],
            listeners: {
                select: function(combo) {
                    setParam('kind', combo.getValue());
                }
            }
        });

        const lastRunCheckbox = Ext.create('Ext.form.field.Checkbox', {
            boxLabel: t('plugin_opendxp_datahub_data_importer_log_only_last_run'),
            checked: onlyLastRun,
            margin: '0 0 0 15',
            listeners: {
                change: function(field, value) {
                    setParam('onlyLastRun', value ? 1 : 0);
                }
            }
        });

        return Ext.create('Ext.grid.Panel', Ext.apply({
            store: store,
            border: false,
            viewConfig: {
                enableTextSelection: true
            },
            tbar: [kindCombo, lastRunCheckbox, '->', {
                iconCls: 'opendxp_icon_reload',
                handler: function() {
                    store.reload();
                }
            }],
            bbar: Ext.create('Ext.PagingToolbar', {
                store: store,
                displayInfo: true
            }),
            columns: [
                {
                    text: t('plugin_opendxp_datahub_data_importer_log_time'),
                    dataIndex: 'timestamp',
                    width: 150,
                    renderer: function(value) {
                        return Ext.Date.format(new Date(value * 1000), 'd.m.Y H:i:s');
                    }
                },
                {
                    text: t('plugin_opendxp_datahub_data_importer_log_result'),
                    dataIndex: 'priority',
                    width: 120,
                    renderer: this.renderResult.bind(this)
                },
                {
                    text: t('plugin_opendxp_datahub_data_importer_log_message'),
                    dataIndex: 'message',
                    flex: 1,
                    cellWrap: true,
                    renderer: this.renderMessage.bind(this)
                },
                {
                    text: 'ID',
                    dataIndex: 'relatedobject',
                    width: 80,
                    renderer: function(value) {
                        return value ? '<a href="#">' + value + '</a>' : '';
                    }
                }
            ],
            listeners: {
                cellclick: function(view, td, cellIndex, record) {
                    const id = record.get('relatedobject');
                    if (cellIndex === 3 && id) {
                        opendxp.helpers.openElement(id, record.get('relatedobjecttype') || 'object');
                    }
                }
            }
        }, panelConfig));
    },

    renderResult: function(priority, meta, record) {
        const message = record.get('message') || '';
        if (['error', 'critical', 'alert', 'emergency'].indexOf(priority) !== -1) {
            return '<span style="color:#c0392b;font-weight:bold">' + t('plugin_opendxp_datahub_data_importer_log_kind_rejected') + '</span>';
        }
        if (priority === 'warning' && message.indexOf('No match by') === 0) {
            return '<span style="color:#d68910">' + t('plugin_opendxp_datahub_data_importer_log_kind_not_found') + '</span>';
        }
        if (/^Element \d+ imported successfully\.$/.test(message)) {
            return '<span style="color:#1e8449">' + t('plugin_opendxp_datahub_data_importer_log_kind_imported') + '</span>';
        }

        return Ext.util.Format.htmlEncode(priority);
    },

    /**
     * Технические тексты ImportProcessingService — в понятные: строка файла отдельно от причины.
     */
    renderMessage: function(message) {
        const encode = Ext.util.Format.htmlEncode;
        let match;

        if ((match = message.match(/^Error processing element: ([\s\S]*?) → ([\s\S]*)$/))) {
            return '<b>' + encode(match[2]) + '</b><br><span style="color:#777">'
                + t('plugin_opendxp_datahub_data_importer_log_row') + ': ' + encode(match[1]) + '</span>';
        }
        if ((match = message.match(/^No match by \S+ with 'Do not create' location strategy: ([\s\S]*)$/))) {
            return t('plugin_opendxp_datahub_data_importer_log_not_found_message')
                + '<br><span style="color:#777">' + t('plugin_opendxp_datahub_data_importer_log_row') + ': ' + encode(match[1]) + '</span>';
        }
        if ((match = message.match(/^Element (\d+) imported successfully\.$/))) {
            return t('plugin_opendxp_datahub_data_importer_log_imported_message') + ' ' + match[1];
        }
        if (message === 'Loading source data from configured source...') {
            return '<i>' + t('plugin_opendxp_datahub_data_importer_log_run_started') + '</i>';
        }

        return encode(message);
    }
});
