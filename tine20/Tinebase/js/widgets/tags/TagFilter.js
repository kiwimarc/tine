/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagFilter
 * @extends     Tine.widgets.grid.PickerFilter
 */
Tine.widgets.tags.TagFilter = Ext.extend(Tine.widgets.grid.PickerFilter, {
    
    field: 'tag',
    defaultOperator: 'equals',
    operators: ['equals', 'not', 'in', 'notin', 'contains'],
    
    /**
     * @private
     */
    initComponent: function() {
        this.picker = Tine.widgets.tags.TagCombo;
        this.recordClass = Tine.Tinebase.Model.Tag;
        
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.label = i18n._('Tag');
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.tag'] = Tine.widgets.tags.TagFilter;

