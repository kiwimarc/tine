/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Employee Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeEditDialog
 */

Tine.HumanResources.CostCenterGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    /*
     * config
     */
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    defaultSortInfo: {field: 'start_date', direction: 'DESC'},
    autoExpandColumn: 'cost_center_id',
    quickaddMandatory: 'cost_center_id',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    enableHdMenu: false,
    recordClass: Tine.HumanResources.Model.CostCenter,
    validate: true,
    /*
     * public
     */
    app: null,
    
    /**
     * the calling editDialog
     * Tine.HumanResources.EmployeeEditDialog
     */
    editDialog: null,
    
    /**
     * initializes the component
     */
    initComponent: function() {
        this.title = this.app.i18n.ngettext('CostCenter', 'CostCenters', 2),
        Tine.HumanResources.CostCenterGridPanel.superclass.initComponent.call(this);
        this.store.sortInfo = this.defaultSortInfo;
        this.on('afteredit', this.onAfterEdit, this);
        this.editDialog.on('load', this.loadRecord, this);
        this.store.sort();
        
        // sync record on these events
        this.store.on('update', this.syncStoreToRecord.createDelegate(this));
        this.store.on('add', this.syncStoreToRecord.createDelegate(this));
        this.store.on('remove', this.syncStoreToRecord.createDelegate(this));
    },
    
    /**
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     */
    syncStoreToRecord: function(store, record, operation) {
        if (this.editDialog.record) {
            var items = [];
            store.each(function(item) {
                items.push(item.data);
            });
            this.editDialog.record.set('costcenters', items);
        }
    },
    
    /**
     * loads the existing CostCenters into the store
     */
    loadRecord: function() {
        var c = this.editDialog.record.get('costcenters');
        if (Ext.isArray(c)) {
            Ext.each(c, function(ar) {
                this.store.addSorted(new this.recordClass(ar));
            }, this);
        }
    },
    
    /**
     * new entry event -> add new record to store
     * @see Tine.widgets.grid.QuickaddGridPanel
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        recordData.employee_id = this.editDialog.record.get('id');
        var relatedRecord = this.costcenterQuickadd.store.getById(this.costcenterQuickadd.getValue());
        recordData.cost_center_id = relatedRecord.data;
        recordData.start_date = recordData.start_date || new Date();
        this.store.addSorted(new this.recordClass(recordData));
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        this.costCenterEditor = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'CostCenter', { allowBlank: true});
        this.startdateEditor = new Ext.ux.form.ClearableDateField();
        
        this.costcenterQuickadd = Tine.widgets.form.RecordPickerManager.get('Tinebase', 'CostCenter', {allowBlank: true});
        
        var columns = [
            {id: 'cost_center_id', dataIndex: 'cost_center_id', type: Tine.Tinebase.Model.CostCenter, header: this.app.i18n._('Cost Center'),
                quickaddField: this.costcenterQuickadd,
                editor: this.costCenterEditor, scope: this,
                renderer: Tine.widgets.grid.RendererManager.get('HumanResources', 'CostCenter', 'cost_center_id', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL)
            }, {id: 'start_date',renderer: Tine.Tinebase.common.dateRenderer, editor: this.startdateEditor, quickaddField: new Ext.ux.form.ClearableDateField(), dataIndex: 'start_date', header: this.app.i18n._('Startdate'),  scope: this, width: 120}
        ];
        
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width: 160,
                editable: true
            }, 
            columns: columns
       });
    },
    
    /**
     * is called on after edit to set related records
     * @param {} o
     */
    onAfterEdit: function(o) {
        if (o.field == 'start_date') {
            o.record.set('start_date', this.startdateEditor.getValue());
        } else if (o.field == 'cost_center_id') {
            var relatedRecord = this.costCenterEditor.store.getById(this.costCenterEditor.getValue());
            o.record.set('cost_center_id', relatedRecord.data);
        }
    }
});

