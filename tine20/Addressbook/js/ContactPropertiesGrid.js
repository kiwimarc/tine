/*
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 *
 * @param config {Object}
 *  config.fields {Collection} of recordClass fields
 *  config.recordClass {Tine.Tinebase.data.Record}
 * @returns {Ext.grid.PropertyGrid}
 */
export default (config) => {
    const app = Tine.Tinebase.appMgr.get(config.recordClass.getMeta('appName'));

    const fieldManager = _.bind(
        Tine.widgets.form.FieldManager.get,
        Tine.widgets.form.FieldManager,
        app.appName,
        config.recordClass,
        _,
        Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID
    );

    config.propertyNames = {};
    config.customEditors = {};
    config.customRenderers = {};
    config.fields.forEach((field, idx) => {
        const editor = fieldManager(field.fieldName, {
            selectOnFocus:true,
            expandOnFocus:true,
            cls: `x-grid-editor-${field.fieldName}`
        });
        const name = `${_.padStart( String(idx), 3, '0')}_${field.fieldName}`;
        config.propertyNames[name] = editor.fieldLabel;
        config.customEditors[name] = new Ext.grid.GridEditor(Ext.create(editor));
        config.customRenderers[name] = Tine.widgets.grid.RendererManager.get(app.appName, config.recordClass, field.fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
    });

    // config.isValid = function() {
    //     let valid = true;
    //     const values = this.getSource();
    //     _.forEach(config.customEditors, (gridEditor, name) => {
    //         const field = gridEditor.field;
    //         field.setValue(values[name]);
    //         if (!field.validate()) {
    //             valid = false;
    //             // mark row invalid somehow!
    //             return false;
    //         }
    //     });
    //     return valid;
    // };
    //
    // /* needed for isFormField cycle */
    // config.isFormField = true;
    // config.markInvalid = Ext.form.Field.prototype.markInvalid;
    // config.clearInvalid = Ext.form.Field.prototype.clearInvalid;
    // config.getMessageHandler = Ext.form.Field.prototype.getMessageHandler;
    // config.getName = Ext.form.Field.prototype.getName;
    // config.getValue = Ext.emptyFn;
    // config.setValue = Ext.emptyFn;
    // config.validate = function() { return this.isValid(); };

    const propertyGrid = new Ext.grid.PropertyGrid(Object.assign({
        border: false,
        hideHeaders: true,
        storeConfig: {
            isEditableValue: () => {
                return true
            },
        }
    }, config));

    propertyGrid.afterIsRendered().then(() => {
        const editDialog = propertyGrid.findParentBy(function (c) {
            return c instanceof Tine.widgets.dialog.EditDialog
        });
        editDialog.on('load', onRecordLoad);
        editDialog.on('recordUpdate', onRecordUpdate);

        // NOTE: in case we are rendered after record was load
        onRecordLoad(editDialog, editDialog.record);
    });

    const onRecordLoad = (editDialog, record) => {
        propertyGrid.setSource(config.fields.reduce((source, field, idx) => {
            const name = `${_.padStart( String(idx), 3, '0')}_${field.fieldName}`;
            source[name] = record.get(field.fieldName);
            return source;
        }, {}));

    };

    const onRecordUpdate = (editDialog, record) => {
        _.forEach(propertyGrid.getSource(), (value, name) => {
            const fieldName = name.replace(/^\d{3}_/, '');
            record.set(fieldName, value);
        });
    };

    return propertyGrid;
}