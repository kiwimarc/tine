/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Projects');

/**
 * Project grid panel
 * 
 * @namespace   Tine.Projects
 * @class       Tine.Projects.ProjectGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Project Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Projects.ProjectGridPanel
 */
Tine.Projects.ProjectGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Projects.Model.Project} recordClass
     */
    recordClass: Tine.Projects.Model.Project,
    
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: true,
    
    /**
     * optional additional filterToolbar configs
     * @cfg {Object} ftbConfig
     */
    ftbConfig: null,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'creation_time', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'title'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Projects.recordBackend;
        
        this.gridConfig.cm = this.getColumnModel();
        
        Tine.Projects.ProjectGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    initActions: function() {
        if (Tine.Tinebase.appMgr.get('SimpleFAQ')) {
            this.actions_searchFAQ = new Ext.Action({
                requiredGrant: 'readGrant',
                text: this.app.i18n._('Search FAQ'),
                disabled: false,
                handler: Tine.Tinebase.appMgr.get('SimpleFAQ').findQuestion,
                iconCls: '.x-btn-large SimpleFAQIconCls',
                scope: this
            });
            this.actionUpdater.addActions(this.actions_searchFAQ);
        }
        this.supr().initActions.call(this);
    },
    
     /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        if (Tine.Tinebase.appMgr.get('SimpleFAQ')) {
            return [
                Ext.apply(new Ext.Button(this.actions_searchFAQ), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                })
            ];
        }
        return [];
    },
    

    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function(){
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
            {   id: 'tags', header: this.app.i18n._('Tags'), width: 40,  dataIndex: 'tags', sortable: false, renderer: Tine.Tinebase.common.tagsRenderer },                
            {
                id: 'number',
                header: this.app.i18n._("Number"),
                width: 100,
                sortable: true,
                dataIndex: 'number',
                hidden: true
            }, {
                id: 'title',
                header: this.app.i18n._("Title"),
                width: 350,
                sortable: true,
                dataIndex: 'title'
            }, {
                id: 'status',
                header: this.app.i18n._("Status"),
                width: 150,
                sortable: true,
                dataIndex: 'status',
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Projects', 'projectStatus')
            }].concat(this.getModlogColumns())
        });
    },
    
    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    }
});
