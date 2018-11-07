/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * File picker dialog
 *
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.FilePickerDialog
 * @extends     Tine.Tinebase.dialog.Dialog
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Filemanager.FilePickerDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    layout: 'fit',

    /**
     * Dialog window
     */
    window: null,

    /**
     * The validated and chosen node
     */
    nodes: null,

    /**
     * Allow to select one or more node
     */
    singleSelect: true,

    /**
     * A constraint allows to alter the selection behaviour of the picker, for example only allow to select files.
     *
     * By default, file and folder are allowed to be selected, the concrete implementation needs to define it's purpose
     *
     * Valids constraints:
     *  - file
     *  - folder
     *  - null (take all)
     */
    constraint: null,

    /**
     * @cfg {Array} requiredGrants
     * grants which are required to select nodes
     */
    requiredGrants: ['readGrant'],

    windowNamePrefix: 'FilePickerDialog_',

    /**
     * Constructor.
     */
    initComponent: function () {
        this.addEvents(
            /**
             * If the dialog will close and an valid node was selected
             * @param node
             */
            'selected'
        );

        this.items = [{
            layout: 'fit',
            items: [
                this.getFilePicker()
            ]
        }];

        this.on('apply', function() {
            this.fireEvent('selected', this.nodes);
        }, this);

        Tine.Filemanager.FilePickerDialog.superclass.initComponent.call(this);
    },

    getEventData: function () {
        return this.nodes;
    },

    /**
     * Create a new filepicker and register listener
     * @returns {*}
     */
    getFilePicker: function () {
        var picker = new Tine.Filemanager.FilePicker({
            requiredGrants: this.requiredGrants,
            constraint: this.constraint,
            singleSelect: this.singleSelect
        });

        picker.on('nodeSelected', this.onNodesSelected.createDelegate(this));
        picker.on('invalidNodeSelected', this.onInvalidNodesSelected.createDelegate(this));

        return picker;
    },

    /**
     * If a node was selected
     * @param nodes
     */
    onNodesSelected: function (nodes) {
        this.nodes = nodes;
        this.buttonApply.setDisabled(false);
    },

    afterRender: function () {
        Tine.Filemanager.FilePickerDialog.superclass.afterRender.apply(this, arguments);
        this.buttonApply.setDisabled(true);
    },
    
    /**
     * If an invalid node was selected
     */
    onInvalidNodesSelected: function () {
        this.buttonApply.setDisabled(true);
    },

    /**
     * Creates a new pop up dialog/window (acc. configuration)
     *
     * @returns {null}
     */
    openWindow: function () {
        this.window = Tine.WindowFactory.getWindow({
            title: this.windowTitle,
            closeAction: 'close',
            modal: true,
            width: 550,
            height: 500,
            layout: 'fit',
            plain: true,
            items: [
                this
            ]
        });

        return this.window;
    }
});
