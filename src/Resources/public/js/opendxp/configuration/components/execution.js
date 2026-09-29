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

opendxp.registerNS('opendxp.plugin.opendxpDataImporterBundle.configuration.components.execution');
opendxp.plugin.opendxpDataImporterBundle.configuration.components.execution = Class.create({

    configName: '',
    data: {},
    configItemRootContainer: null,
    currentLoaderType: null,
    currentDirtyState: false,
    updateHandle: null,

    initialize: function(configName, data, configItemRootContainer, loaderType) {
        this.configName = configName;
        this.data = data;
        this.configItemRootContainer = configItemRootContainer;
        this.currentLoaderType = loaderType;
    },

    buildPanel: function() {

        if(!this.form) {

            this.buttonFieldContainer = Ext.create('Ext.form.FieldContainer', {
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_execution_manual_execution'),
                items: [
                    {
                        xtype: 'button',
                        width: 165,
                        text: t('plugin_opendxp_datahub_data_importer_configpanel_execution_start'),
                        handler: this.startImport.bind(this)
                    }
                ],
            });

            // Файл для импорта из ресурса — без захода в «Ресурсы»: скачать шаблон,
            // загрузить заполненный файл в тот же ассет и сразу запустить импорт
            this.assetFileContainer = Ext.create('Ext.form.FieldContainer', {
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_execution_import_file'),
                layout: 'hbox',
                items: [
                    {
                        xtype: 'button',
                        iconCls: 'opendxp_icon_download',
                        text: t('plugin_opendxp_datahub_data_importer_configpanel_execution_download_template'),
                        margin: '0 10 0 0',
                        handler: this.downloadTemplate.bind(this)
                    },
                    this.buildUploadForm()
                ]
            });

            this.scheduleTypes = Ext.create('Ext.form.FieldContainer', {
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_execution_schedule_type'),
                items: [{
                    xtype: 'radiogroup',
                    vertical: 'false',
                    columns: 2,
                    width: 400,
                    items: [{
                        boxLabel: t('plugin_opendxp_datahub_data_importer_configpanel_execution_schedule_type_cron_label'),
                        name: 'scheduleType',
                        checked: !this.data || this.data.scheduleType !== 'job',
                        inputValue: 'recurring',
                        listeners: {
                            change:  (obj, value) => {
                                if (value) {
                                    this.cronDefinitionContainer.down('textfield').setValue(this.data.cronDefinition);
                                    this.cronDefinitionContainer.setVisible(true);
                                    this.scheduledAtContainer.setVisible(false);
                                    this.scheduledAtContainer.down('datefield').reset();
                                }
                            },
                            scope: this
                        }

                    }, {
                        boxLabel: t('plugin_opendxp_datahub_data_importer_configpanel_execution_schedule_type_job_label'),
                        name: 'scheduleType',
                        checked: this.data?.scheduleType === 'job',
                        inputValue: 'job',
                        listeners: {
                            change: (obj, value) => {
                                if (value) {
                                    this.scheduledAtContainer.down('datefield').setValue(this.data.scheduledAt);
                                    this.scheduledAtContainer.setVisible(true);
                                    this.cronDefinitionContainer.setVisible(false);
                                    this.cronDefinitionContainer.down('textfield').reset();
                                }
                            },
                            scope: this
                        }
                    }]
                }]
            });

            this.scheduledAtContainer = Ext.create('Ext.form.FieldContainer', {
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_execution_datetime'),
                layout: 'hbox',
                hidden: this.data?.scheduleType !== 'job',
                style: 'margin-bottom: 18px;',
                items: [
                    {
                        xtype: 'datefield',
                        name: 'scheduledAt',
                        width: 300,
                        format: 'd-m-Y H:i',
                        value: this.data ? this.data.scheduledAt : '',
                        activeErrorsTpl: t('plugin_opendxp_datahub_data_importer_configpanel_execution_status_error'),
                        formatText: t('plugin_opendxp_datahub_data_importer_configpanel_execution_date_format'),
                        msgTarget: 'under'
                    }
                ]
            });

            this.cronDefinitionContainer = Ext.create('Ext.form.FieldContainer', {
                fieldLabel: t('plugin_opendxp_datahub_data_importer_configpanel_execution_cron'),
                layout: 'hbox',
                hidden: this.data?.scheduleType === 'job',
                items: [
                    {
                        xtype: 'textfield',
                        name: 'cronDefinition',
                        width: 300,
                        value: this.data.cronDefinition,
                        listeners: {
                            blur: function(field) {
                                if(this.cronTimeout) {
                                    clearTimeout(this.cronTimeout);
                                }
                                this.validateCron(field);
                            }.bind(this),
                            change: function(field) {
                                if(this.cronTimeout) {
                                    clearTimeout(this.cronTimeout);
                                }
                                this.cronTimeout = setTimeout(function(field) {
                                    this.validateCron(field);
                                }.bind(this, field), 500);
                            }.bind(this)
                        },
                        msgTarget: 'under',
                    },
                    {
                        xtype: 'displayfield',
                        style: 'padding-left: 10px',
                        value: '<a target="_blank" href="https://crontab.guru/">' + t('plugin_opendxp_datahub_data_importer_configpanel_execution_cron_generator') + '</a>'
                    }
                ]
            });

            this.progressLabel = Ext.create('Ext.form.Label', {
                style: 'margin-bottom: 5px; display: block'
            });
            this.progressBar = Ext.create('Ext.ProgressBar', {
                hidden: true
            });

            // Итог последнего запуска — сколько строк записано, отклонено, не найдено
            this.summaryLabel = Ext.create('Ext.Component', {
                hidden: true,
                style: 'margin-top: 10px'
            });
            this.rejectedButton = Ext.create('Ext.button.Button', {
                hidden: true,
                iconCls: 'opendxp_icon_warning',
                margin: '0 10 0 0',
                handler: this.openImportLog.bind(this, 'rejected')
            });
            this.notFoundButton = Ext.create('Ext.button.Button', {
                hidden: true,
                iconCls: 'opendxp_icon_search',
                handler: this.openImportLog.bind(this, 'notFound')
            });
            this.summaryButtons = Ext.create('Ext.Container', {
                layout: 'hbox',
                margin: '8 0 0 0',
                items: [this.rejectedButton, this.notFoundButton]
            });
            this.cancelButtonContainer = Ext.create('Ext.Panel', {
                layout: 'hbox',
                hidden: true,
                bodyStyle: 'padding-top: 10px',
                border: false,
                items: [
                    {
                        xtype: 'component',
                        flex: 1
                    },
                    {
                        xtype: 'button',
                        iconCls: 'opendxp_icon_cancel',
                        text: t('plugin_opendxp_datahub_data_importer_configpanel_execution_cancel'),
                        handler: function() {
                            Ext.Ajax.request({
                                url: Routing.generate('opendxp_dataimporter_configdataobject_cancelexecution'),
                                method: 'PUT',
                                params: {
                                    config_name: this.configName,
                                },
                                success: function (response) {

                                    opendxp.helpers.showNotification(t('success'), t('plugin_opendxp_datahub_data_importer_configpanel_execution_cancel_successful'), 'success');
                                    this.updateProgress();

                                }.bind(this)
                            });
                        }.bind(this)
                    }
                ]
            });

            this.updateProgress();

            this.form = Ext.create('DataHub.DataImporter.StructuredValueForm', {
                bodyStyle: 'padding:10px;',
                title: t('plugin_opendxp_datahub_data_importer_configpanel_execution'),
                items: [
                    {
                        xtype: 'fieldset',
                        title: t('plugin_opendxp_datahub_data_importer_configpanel_execution_settings'),
                        defaults: {
                            labelWidth: 130
                        },
                        items: [
                            this.scheduleTypes,
                            this.cronDefinitionContainer,
                            this.scheduledAtContainer,
                            this.buttonFieldContainer,
                            this.assetFileContainer
                        ]
                    },{
                        xtype: 'fieldset',
                        title: t('plugin_opendxp_datahub_data_importer_configpanel_execution_status'),
                        items: [
                            this.progressLabel,
                            this.progressBar,
                            this.cancelButtonContainer,
                            this.summaryLabel,
                            this.summaryButtons

                        ]
                    }
                ]
            });

            this.updateDisabledState();

            this.configItemRootContainer.on(opendxp.plugin.opendxpDataImporterBundle.configuration.events.loaderTypeChanged, function(newType) {
                this.currentLoaderType = newType;
                this.updateDisabledState();
            }.bind(this));

            this.configItemRootContainer.on(opendxp.plugin.opendxpDataImporterBundle.configuration.events.configDirtyChanged, function(dirty) {
                this.currentDirtyState = dirty;
                this.updateDisabledState();
            }.bind(this));

            this.form.on('destroy', function() {
                clearTimeout(this.updateHandle);
            }.bind(this));

        }

        return this.form;
    },

    updateDisabledState: function() {
        this.cronDefinitionContainer.setDisabled(this.currentLoaderType === 'push');
        this.buttonFieldContainer.setDisabled(this.currentLoaderType === 'push' || this.currentDirtyState);
        // Путь файла берётся из сохранённого конфига — при несохранённых правках он может отличаться
        this.assetFileContainer.setHidden(this.currentLoaderType !== 'asset');
        this.assetFileContainer.setDisabled(this.currentDirtyState);
    },

    updateSummary: function(isRunning) {
        // Пока импорт не идёт и сводка уже показана, повторно не спрашиваем
        if (!isRunning && this.summaryLoaded && !this.lastRunning) {
            return;
        }
        this.lastRunning = isRunning;

        Ext.Ajax.request({
            url: Routing.generate('opendxp_dataimporter_configdataobject_importrunsummary'),
            method: 'GET',
            params: {
                config_name: this.configName
            },
            success: function(response) {
                const data = Ext.decode(response.responseText);
                const summary = data && data.summary;
                this.summaryLoaded = true;

                if (!summary) {
                    this.summaryLabel.hide();
                    this.rejectedButton.hide();
                    this.notFoundButton.hide();
                    return;
                }

                const started = Ext.Date.format(new Date(summary.startedAt * 1000), 'd.m.Y H:i');
                let html = '<b>' + t('plugin_opendxp_datahub_data_importer_summary_last_run') + ' ' + started + (isRunning ? ' (' + t('plugin_opendxp_datahub_data_importer_summary_running') + ')' : '') + ':</b> '
                    + '<span style="color:#1e8449">' + t('plugin_opendxp_datahub_data_importer_summary_imported') + ' ' + summary.imported + '</span>, '
                    + '<span style="color:' + (summary.rejected ? '#c0392b' : 'inherit') + '">' + t('plugin_opendxp_datahub_data_importer_summary_rejected') + ' ' + summary.rejected + '</span>, '
                    + '<span style="color:' + (summary.notFound ? '#d68910' : 'inherit') + '">' + t('plugin_opendxp_datahub_data_importer_summary_not_found') + ' ' + summary.notFound + '</span>';
                if (summary.warnings) {
                    html += ', ' + t('plugin_opendxp_datahub_data_importer_summary_warnings') + ' ' + summary.warnings;
                }

                this.summaryLabel.setHtml(html);
                this.summaryLabel.show();

                this.rejectedButton.setText(t('plugin_opendxp_datahub_data_importer_log_rejected') + ' (' + summary.rejected + ')');
                this.rejectedButton.setHidden(!summary.rejected);
                this.notFoundButton.setText(t('plugin_opendxp_datahub_data_importer_log_not_found') + ' (' + summary.notFound + ')');
                this.notFoundButton.setHidden(!summary.notFound);
            }.bind(this)
        });
    },

    openImportLog: function(kind) {
        new opendxp.plugin.opendxpDataImporterBundle.configuration.components.importLog(this.configName).openWindow(kind);
    },

    downloadTemplate: function() {
        const url = Routing.generate('opendxp_dataimporter_configdataobject_downloadimporttemplate', {
            config_name: this.configName
        });

        // Сначала запрос, потом сохранение: ошибку (нет доступа, нет шаблона) надо показать,
        // а не открыть пустую страницу
        fetch(url, {credentials: 'same-origin'}).then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    const message = response.status === 403 ? t('access_denied') : (text && text.length < 300 ? text : t('error_general'));
                    opendxp.helpers.showNotification(t('error'), message, 'error');
                });
            }

            const disposition = response.headers.get('Content-Disposition') || '';
            const match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
            const filename = match ? decodeURIComponent(match[1]) : this.configName + '.xlsx';

            return response.blob().then(function(blob) {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(function() { URL.revokeObjectURL(link.href); }, 1000);
            });
        }.bind(this)).catch(function() {
            opendxp.helpers.showNotification(t('error'), t('error_general'), 'error');
        });
    },

    buildUploadForm: function() {
        this.uploadForm = Ext.create('Ext.form.Panel', {
            border: false,
            width: 260,
            style: 'background: transparent',
            bodyStyle: 'background: transparent; padding: 0',
            items: [{
                xtype: 'fileuploadfield',
                name: 'Filedata',
                buttonOnly: true,
                hideLabel: true,
                // Поле выбора файла в ExtJS всегда readOnly, а тема OpenDXP гасит readOnly-поля
                // до opacity 0.7 — кнопка выглядела бы неактивной
                style: 'opacity: 1',
                buttonText: t('plugin_opendxp_datahub_data_importer_configpanel_execution_upload_and_start'),
                buttonConfig: {
                    iconCls: 'opendxp_icon_upload',
                    width: 250
                },
                listeners: {
                    change: this.uploadAndStart.bind(this)
                }
            }]
        });

        return this.uploadForm;
    },

    uploadAndStart: function(field) {
        if (!field.getValue()) {
            return;
        }

        this.uploadForm.getForm().submit({
            url: Routing.generate('opendxp_dataimporter_configdataobject_uploadtoassetandstart', {
                config_name: this.configName
            }),
            params: {
                csrfToken: opendxp.settings['csrfToken']
            },
            waitMsg: t('please_wait'),
            success: function() {
                opendxp.helpers.showNotification(t('success'), t('plugin_opendxp_datahub_data_importer_configpanel_execution_upload_and_start_successful'), 'success');
                this.uploadForm.getForm().reset();
                this.lastRunning = true;
                this.updateProgress();
            }.bind(this),
            failure: function(form, action) {
                let message = t('plugin_opendxp_datahub_data_importer_configpanel_execution_start_error');
                if (action.result && action.result.message) {
                    message = action.result.message;
                }
                opendxp.helpers.showNotification(t('error'), message, 'error');
                this.uploadForm.getForm().reset();
                this.updateProgress();
            }.bind(this)
        });
    },

    startImport: function(button) {

        button.setText(t('plugin_opendxp_datahub_data_importer_configpanel_execution_start_loading'));
        button.setDisabled(true);

        Ext.Ajax.request({
            url: Routing.generate('opendxp_dataimporter_configdataobject_startbatchimport'),
            method: 'PUT',
            params: {
                config_name: this.configName,
            },
            success: function (response) {
                let data = Ext.decode(response.responseText);

                if (data && data.success) {
                    opendxp.helpers.showNotification(t('success'), t('plugin_opendxp_datahub_data_importer_configpanel_execution_start_successful'), 'success');
                } else {
                    opendxp.helpers.showNotification(t("error"), t('plugin_opendxp_datahub_data_importer_configpanel_execution_start_error'), 'error');
                }
                button.setDisabled(false);
                button.setText(t('plugin_opendxp_datahub_data_importer_configpanel_execution_start'));
                this.updateDisabledState();
                this.lastRunning = true;
                this.updateProgress();
            }.bind(this)
        });
    },

    validateCron: function(field) {

        if(field.getValue().length === 0) {
            field.setValidation(true);
        } else {
            Ext.Ajax.request({
                url: Routing.generate('opendxp_dataimporter_configdataobject_iscronexpressionvalid'),
                method: 'GET',
                params: {
                    cron_expression: field.getValue()
                },
                success: function (response) {
                    let data = Ext.decode(response.responseText);
                    if(data.success) {
                        field.setValidation(true);
                    } else {
                        field.setValidation(data.message);
                    }
                    field.isValid();
                }.bind(this)
            });
        }

    },

    updateProgress: function() {
        clearTimeout(this.updateHandle);
        Ext.Ajax.request({
            url: Routing.generate('opendxp_dataimporter_configdataobject_checkimportprogress'),
            method: 'GET',
            params: {
                config_name: this.configName,
            },
            success: function (response) {
                let data = Ext.decode(response.responseText);

                if(data.isRunning) {
                    this.progressBar.show();
                    this.cancelButtonContainer.show();
                    this.progressBar.updateProgress(data.progress, data.processedItems + '/' + data.totalItems + ' ' + t('plugin_opendxp_datahub_data_importer_configpanel_execution_processed'));
                    this.progressLabel.setHtml(t('plugin_opendxp_datahub_data_importer_configpanel_execution_current_progress'));
                } else {
                    this.progressBar.hide();
                    this.cancelButtonContainer.hide();
                    this.progressLabel.setHtml('<b>' + t('plugin_opendxp_datahub_data_importer_configpanel_execution_not_running') + '</b>');
                }

                this.updateSummary(data.isRunning);
                this.updateHandle = setTimeout(this.updateProgress.bind(this), 5000);

            }.bind(this)
        });
    }

});
