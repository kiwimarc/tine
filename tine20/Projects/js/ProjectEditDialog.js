/**
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Projects');

/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.ProjectEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Project Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Projects.ProjectEditDialog
 */
Tine.Projects.ProjectEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'ProjectEditWindow_',
    appName: 'Projects',
    modelName: 'Project',
    evalGrants: true,
    showContainerSelector: true,
    hideRelationsPanel: false,
    displayNotes: true,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {
    },
    
    /**
     * executed after record got updated from proxy
     * @private
     */
    onAfterRecordLoad: function() {
        Tine.Projects.ProjectEditDialog.superclass.onAfterRecordLoad.call(this);
        this.contactLinkPanel.onRecordLoad(this.record);
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Projects.ProjectEditDialog.superclass.onRecordUpdate.call(this);
        var _ = window.lodash,
            relations = this.record.get('relations');

        relations = relations.filter(function (element) {
            if (element.type == 'COWORKER' || element.type == 'RESPONSIBLE') {
                return null;
            } else return element;
        });

        relations = _.concat(relations, this.contactLinkPanel.getData());
        this.record.set('relations', relations);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        // TODO replace with field manager version (see tasks)
        this.contactLinkPanel = new Tine.widgets.grid.LinkGridPanel({
            app: this.app,
            editDialog: this,
            searchRecordClass: Tine.Addressbook.Model.Contact,
            newRecordClass: Tine.Addressbook.Model.Contact,
            title: this.app.i18n._('Attendee'),
            typeColumnHeader: this.app.i18n._('Role'),
            searchComboConfig: {
                relationDefaults: {
                    type: this.app.getRegistry().get('config')['projectAttendeeRole'].definition['default'],
                    own_model: 'Projects_Model_Project',
                    related_model: 'Addressbook_Model_Contact',
                    related_degree: 'sibling',
                    related_backend: 'Sql'
                }
            },
            relationTypesKeyfieldName: 'projectAttendeeRole'
        });

        const tasksApp = Tine.Tinebase.appMgr.get('Tasks');
        this.tasksLinkPanel = Tine.widgets.form.FieldManager.get(
            this.appName,
            this.modelName,
            'tasks',
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
            {
                allowCreateNew: true,
                // TODO allow to auto-create this (but we need to switch Tasks frontend to MC / appstarter first)
                // columns: [
                //     'summary',
                //     'due',
                //     'priority',
                //     'percent',
                //     'status',
                // ]
                columns: [
                    {
                        id: 'summary',
                        header: tasksApp.i18n._("Summary"),
                        width: 130,
                        dataIndex: 'summary',
                        quickaddField: new Ext.form.TextField({
                            emptyText: tasksApp.i18n._('Add a task...')
                        })
                    }, {
                        id: 'due',
                        header: tasksApp.i18n._("Due Date"),
                        width: 100,
                        dataIndex: 'due',
                        renderer: Tine.Tinebase.common.dateRenderer,
                        editor: new Ext.ux.form.ClearableDateField({
                            //format : 'd.m.Y'
                        }),
                        quickaddField: new Ext.ux.form.ClearableDateField({
                            //value: new Date(),
                            //format : "d.m.Y"
                        })
                    }, {
                        id: 'priority',
                        header: tasksApp.i18n._("Priority"),
                        width: 70,
                        dataIndex: 'priority',
                        renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskPriority'),
                        editor: {
                            xtype: 'widget-keyfieldcombo',
                            app: 'Tasks',
                            keyFieldName: 'taskPriority'
                        },
                        quickaddField: new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Tasks',
                            keyFieldName: 'taskPriority'
                        })
                    }, {
                        id: 'percent',
                        header: tasksApp.i18n._("Percent"),
                        width: 70,
                        dataIndex: 'percent',
                        renderer: Ext.ux.PercentRenderer,
                        editor: new Ext.ux.PercentCombo({
                            autoExpand: true,
                            blurOnSelect: true
                        }),
                        quickaddField: new Ext.ux.PercentCombo({
                            autoExpand: true
                        })
                    }, {
                        id: 'status',
                        header: tasksApp.i18n._("Status"),
                        width: 100,
                        dataIndex: 'status',
                        renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskStatus'),
                        editor: {
                            xtype: 'widget-keyfieldcombo',
                            app: 'Tasks',
                            keyFieldName: 'taskStatus'
                        },
                        quickaddField: new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Tasks',
                            keyFieldName: 'taskStatus',
                            value: 'NEEDS-ACTION'
                        })
                    }
                ]
            }
        )

        return {
            xtype: 'tabpanel',
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._('Project'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Project'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                columnWidth: .333
                            },
                            items: [[{
                                    columnWidth: 1,
                                    fieldLabel: this.app.i18n._('Title'),
                                    name: 'title',
                                    allowBlank: false
                                }], [{
                                    columnWidth: .5,
                                    fieldLabel: this.app.i18n._('Number'),
                                    name: 'number'
                                }, new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    columnWidth: .5,
                                    app: 'Projects',
                                    keyFieldName: 'projectStatus',
                                    fieldLabel: this.app.i18n._('Status'),
                                    name: 'status'
                                })],
                                [
                                    {
                                        columnWidth: .5,
                                        fieldLabel: this.app.i18n._('Start'),
                                        xtype: 'extuxclearabledatefield',
                                        name: 'start'
                                    },
                                    {
                                        columnWidth: .5,
                                        fieldLabel: this.app.i18n._('End'),
                                        xtype: 'extuxclearabledatefield',
                                        name: 'end'
                                    }
                                ],
                                [
                                    new Tine.Tinebase.widgets.keyfield.ComboBox({
                                        columnWidth: .5,
                                        fieldLabel: this.app.i18n._('Scope'),
                                        app: 'Projects',
                                        keyFieldName: 'projectScope',
                                        name: 'scope'
                                    }),
                                    new Tine.Tinebase.widgets.keyfield.ComboBox({
                                        columnWidth: .5,
                                        fieldLabel: this.app.i18n._('Type'),
                                        app: 'Projects',
                                        keyFieldName: 'projectType',
                                        name: 'type'
                                    }) 
                                ]
                            ]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        deferredRender: false,
                        activeTab: 0,
                        border: false,
                        height: 250,
                        form: true,
                        items: [
                            this.contactLinkPanel
                        ]
                    }, {
                        xtype: 'tabpanel',
                        deferredRender: false,
                        activeTab: 0,
                        border: false,
                        height: 200,
                        form: true,
                        items: [
                            this.tasksLinkPanel
                        ]
                    }]
                }, {
                    // activities and tags
                    layout: 'ux.multiaccordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.tags.TagPanel({
                            app: 'Projects',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Ext.Panel({
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype: 'textarea',
                                name: 'description',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100% 100%',
                                emptyText: this.app.i18n._('Enter description'),
                                requiredGrant: 'editGrant'
                            }]
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * Projects Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Projects.ProjectEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 800,
        name: Tine.Projects.ProjectEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Projects.ProjectEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
