/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.namespace('Tine.Felamimail');

require('./MessageFileButton');

/**
 * Message grid panel
 * 
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Message Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.GridPanel
 */
Tine.Felamimail.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Felamimail.Model.Message} recordClass
     */
    recordClass: Tine.Felamimail.Model.Message,
    
    /**
     * message detail panel
     * 
     * @type Tine.Felamimail.GridDetailsPanel
     * @property detailsPanel
     */
    detailsPanel: null,
    
    /**
     * transaction id of current delete message request
     * @type Number
     */
    deleteTransactionId: null,
    
    /**
     * this is true if messages are moved/deleted
     * 
     * @type Boolean
     */
    movingOrDeleting: false,
    
    manualRefresh: false,
    
    /**
     * @private model cfg
     */
    evalGrants: false,
    filterSelectionDelete: true,
    // autoRefresh is done via onUpdateFolderStore
    autoRefreshInterval: false,

    // needed for refresh after file messages
    listenMessageBus: true,

    /**
     * @private grid cfg
     */
    defaultSortInfo: {field: 'sent', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'subject',
        // drag n dropfrom
        enableDragDrop: true,
        ddGroup: 'mailToTreeDDGroup'
    },
    // we don't want to update the preview panel on context menu
    updateDetailsPanelOnCtxMenu: false,

    /**
     * needed to apply second grid state for send folders
     */
    sendFolderGridStateId: null,

    sentFolderSelected: false,

    /**
     * Return CSS class to apply to rows depending upon flags
     * - checks Flagged, Deleted and Seen
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var className = '';
        
        if (record.hasFlag('\\Flagged')) {
            className += ' flag_flagged';
        }
        if (record.hasFlag('\\Deleted')) {
            className += ' flag_deleted';
        }
        if (! record.hasFlag('\\Seen')) {
            className += ' flag_unread';
        }
        if (record.get('is_spam_suspicions')) {
            className += ' is_spam_suspicions';
        }
        
        return className;
    },
    
    /**
     * init message grid
     * @private
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18nEmptyText = this.app.i18n._('No Messages found.');
        
        this.recordProxy = Tine.Felamimail.messageBackend;
        
        this.gridConfig.columns = this.getColumns();
        this.initDetailsPanel();
        
        this.pagingConfig = {
            doRefresh: this.doRefresh.createDelegate(this)
        };

        if (!this.readOnly) {
            this.plugins.push({
                ptype: 'ux.browseplugin',
                multiple: true,
                scope: this,
                enableFileDialog: false,
                handler: this.onFilesSelect.createDelegate(this)
            });
        }
        
        Tine.Felamimail.GridPanel.superclass.initComponent.call(this);
        this.grid.getSelectionModel().on('rowselect', this.onRowSelection, this);
        this.app.getFolderStore().on('update', this.onUpdateFolderStore, this);
        this.contextMenu.on('beforeshow', this.onDisplaySpamActions, this);
        this.initPagingToolbar();

        this.sendFolderGridStateId = this.gridConfig.stateId + '-SendFolder';
    },
    
    /**
     * add quota bar to paging toolbar
     */
    initPagingToolbar: function() {
        Ext.QuickTips.init();
        
        this.quotaBar = new Ext.Component({
            style: {
                marginTop: '3px',
                width: '100px',
                height: '16px'
            }
        });

        this.pagingToolbar.insert(12, new Ext.Toolbar.Separator());
        this.pagingToolbar.insert(12, this.quotaBar);
    },
    
    /**
     * cleanup on destruction
     */
    onDestroy: function() {
        this.app.getFolderStore().un('update', this.onUpdateFolderStore, this);
    },
    
    /**
     * folder store gets updated -> refresh grid if new messages arrived or messages have been removed
     * 
     * @param {Tine.Felamimail.FolderStore} store
     * @param {Tine.Felamimail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolderStore: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT && record.isModified('cache_totalcount')) {
            var tree = this.app.getMainScreen().getTreePanel(),
                selectedNodes = (tree) ? tree.getSelectionModel().getSelectedNodes() : [];
            
            // only refresh if 1 or no messages are selected
            if (this.getGrid().getSelectionModel().getCount() <= 1) {
                var refresh = false;
                for (var i = 0; i < selectedNodes.length; i++) {
                    if (selectedNodes[i].id == record.id) {
                        refresh = true;
                        break;
                    }
                }
                
                // check if folder is in filter or allinboxes are selected and updated folder is an inbox
                if (! refresh) {
                    var filters = this.filterToolbar.getValue();
                    filters = filters.filters ? filter.filters : filters;
                    
                    for (var i = 0; i < filters.length; i++) {
                        if (filters[i].field == 'path' && filters[i].operator == 'in') {
                            if (filters[i].value.indexOf(record.get('path')) !== -1 || (filters[i].value.indexOf('/allinboxes') !== -1 && record.isInbox())) {
                                refresh = true;
                                break;
                            }
                        }
                    }
                }
                
                if (refresh && this.noDeleteRequestInProgress()) {
                    Tine.log.debug('Refresh grid because of folder update.');
                    this.loadGridData({
                        removeStrategy: 'keepBuffered',
                        autoRefresh: true
                    });
                }
            }
        }
    },
    
    /**
     * skip initial till we know the INBOX id
     */
    initialLoad: function() {
        var account = this.app.getActiveAccount(),
            accountId = account ? account.id : null,
            inbox = accountId ? this.app.getFolderStore().queryBy(function(record) {
                return record.get('account_id') === accountId && record.get('localname').match(/^inbox$/i);
            }, this).first() : null;
            
        if (! inbox) {
            this.initialLoad.defer(100, this, arguments);
            return;
        }
        
        return Tine.Felamimail.GridPanel.superclass.initialLoad.apply(this, arguments);
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        
        this.action_spam = new Ext.Action({
            requiredGrant: 'editGrant',
            actionType: 'edit',
            text: this.app.i18n._('It is SPAM'),
            handler:this.processSpamStrategy.bind(this, 'spam'),
            iconCls: 'felamimail-action-spam',
            allowMultiple: true,
            hidden: !this.app.featureEnabled('featureSpamSuspicionStrategy')

        });

        this.action_ham = new Ext.Action({
            requiredGrant: 'editGrant',
            actionType: 'edit',
            text: this.app.i18n._('It is not SPAM'),
            handler: this.processSpamStrategy.bind(this, 'ham'),
            iconCls: 'felamimail-action-ham',
            allowMultiple: true,
            hidden: !this.app.featureEnabled('featureSpamSuspicionStrategy')
        });
        
        this.action_write = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Compose'),
            handler: this.onMessageCompose.createDelegate(this),
            // TODO reactivate when account becomes available as sometimes this stays deactivated
            disabled: ! this.app.getActiveAccount(),
            iconCls: this.app.appName + 'IconCls'
        });

        this.action_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply'),
            handler: this.onMessageReplyTo.createDelegate(this, [false]),
            iconCls: 'action_email_reply',
            disabled: true
        });

        this.action_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All'),
            handler: this.onMessageReplyTo.createDelegate(this, [true]),
            iconCls: 'action_email_replyAll',
            disabled: true
        });

        this.action_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward'),
            handler: this.onMessageForward.createDelegate(this),
            iconCls: 'action_email_forward',
            disabled: true
        });

        this.action_flag = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Toggle highlighting'),
            handler: this.onToggleFlag.createDelegate(this, ['\\Flagged'], true),
            iconCls: 'action_email_flag',
            allowMultiple: true,
            disabled: true
        });
        
        this.action_markUnread = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Mark read/unread'),
            handler: this.onToggleFlag.createDelegate(this, ['\\Seen'], true),
            iconCls: 'action_mark_read',
            allowMultiple: true,
            disabled: true
        });
        
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            text: this.app.i18n._('Delete'),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });

        this.action_moveRecord = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: true,
            text: this.app.i18n._('Move'),
            disabled: true,
            actionType: 'edit',
            handler: this.onMoveRecords,
            scope: this,
            iconCls: 'action_move'
        });

        this.action_copyRecord = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: true,
            text: this.app.i18n._('Copy'),
            disabled: true,
            actionType: 'edit',
            handler: this.onCopyRecords,
            scope: this,
            iconCls: 'action_editcopy'
        });

        this.action_fileRecord = new Tine.Felamimail.MessageFileButton({});
        
        this.action_importRecords = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Import Messages'),
            handler: this.onFilesSelect,
            disabled: true,
            scope: this,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                enableFileDrop: false,
                disable: true
            }],
            iconCls: 'action_import',
            actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
                action.setDisabled(! this.getCurrentFolderFromTree());
            }.createDelegate(this)
        });

        this.action_addAccount = new Ext.Action({
            text: this.app.i18n._('Add Account'),
            handler: this.onAddAccount,
            iconCls: 'action_add',
            scope: this,
            disabled: ! Tine.Tinebase.common.hasRight('add_accounts', 'Felamimail')
        });
        this.action_printPreview = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Preview'),
            handler: this.onPrintPreview.createDelegate(this, []),
            disabled:true,
            hidden: Ext.supportsPopupWindows,
            iconCls:'action_printPreview',
            scope:this
        });
        this.action_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Message'),
            handler: this.onPrint.createDelegate(this, []),
            disabled:true,
            iconCls:'action_print',
            scope:this,
            menu:{
                items:[
                    this.action_printPreview
                ]
            }
        });
        this.actionUpdater.addActions([
            this.action_deleteRecord,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_fileRecord,
            this.action_importRecords,
            this.action_flag,
            this.action_markUnread,
            this.action_addAccount,
            this.action_print,
            this.action_printPreview,
            this.action_copyRecord,
            this.action_moveRecord,
            this.action_spam,
            this.action_ham
        ]);
        
        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items: [
                this.action_reply,
                this.action_replyAll,
                this.action_forward,
                this.action_flag,
                this.action_markUnread,
                this.action_copyRecord,
                this.action_moveRecord,
                this.action_deleteRecord,
                this.action_fileRecord,
                this.action_spam,
                this.action_ham
            ],
        });
    },

    /**
     * upload new file and add to store
     *
     * @param {ux.BrowsePlugin} fileSelector
     * @param {} e
     */
    onFilesSelect: async function (fileSelector, event) {
        const folder = this.getCurrentFolderFromTree();
        if (! folder) {
            return Ext.Msg.alert(this.app.i18n._('No target folder selected'), this.app.i18n._('You need to select a target folder for the messages.'));
        }
        
        const exts = _.uniq(_.map(fileSelector.files, file => {return file.name.split('.').pop()}));
        if (exts.length !== 1 || exts[0] !== 'eml') {
            return Ext.Msg.alert(this.app.i18n._('Wrong File Type'), this.app.i18n._('Files of type eml allowed only.'));
        }
        
        this.importMask = new Ext.LoadMask(this.grid.getEl(), { msg: i18n._('Importing Messages...') });
        this.importMask.show();
        
        const pms = _.map(fileSelector.files, (file) => {
            return new Ext.ux.file.Upload({file: file}).upload().get('promise').then((upload) => {
                const tempFile = upload.fileRecord.get('tempFile');
                return Tine.Felamimail.importMessage(folder.id, tempFile.id);
            });             
        });
        
        await Promise.allSettled(pms);
        this.doRefresh();
        this.importMask.hide();
    },
    
    /**
     * initializes the filterPanel, overwrites the superclass method
     */
    initFilterPanel: function() {
        this.filterToolbar = this.getFilterToolbar();
        this.filterToolbar.criteriaIgnores = [
            {field: 'query',     operator: 'contains',     value: ''},
            {field: 'id' },
            {field: 'path' }
        ];
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
    },
    
    /**
     * the details panel (shows message content)
     * 
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Felamimail.GridDetailsPanel({
            gridpanel: this,
            grid: this,
            app: this.app,
            i18n: this.app.i18n
        });
    },
    
    /**
     * get action toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    layout: 'toolbar',
                    buttonAlign: 'left',
                    columns: 6,
                    items: [
                        Ext.apply(new Ext.SplitButton(this.action_write), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top',
                            arrowAlign:'right',
                            menu: new Ext.menu.Menu({
                                items: [],
                                plugins: [{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tine.widgets.grid.GridPanel.addButton'
                                },{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tinebase-MainContextMenu'
                                }]
                            })
                        }),
                        Ext.apply(new Ext.Button(this.action_deleteRecord), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_reply), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_replyAll), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_forward), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        {
                            xtype: 'buttongroup',
                            buttonAlign: 'left',
                            columns: 3,
                            frame: false,
                            items: [
                                this.action_print,
                                this.action_markUnread,
                                this.action_addAccount,
                                this.action_fileRecord,
                                this.action_flag,
                                this.action_importRecords
                            ]
                        }
                    ]
                }, this.getActionToolbarItems()]
            });

            this.actionToolbar.on('resize', this.onActionToolbarResize, this, {buffer: 250});
            this.actionToolbar.on('show', this.onActionToolbarResize, this);

            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }
        }
        
        return this.actionToolbar;
    },
    
    /**
     * returns cm
     * 
     * @private
     */
    getColumns: function(){
        return [{
            id: 'id',
            header: this.app.i18n._("Id"),
            width: 100,
            sortable: true,
            dataIndex: 'id',
            hidden: true
        }, {
            id: 'content_type',
            header: this.app.i18n._("Attachments"),
            width: 12,
            sortable: true,
            dataIndex: 'has_attachment',
            renderer: this.attachmentRenderer
        }, {
            id: 'flags',
            header: this.app.i18n._("Flags"),
            width: 24,
            sortable: true,
            dataIndex: 'flags',
            align: 'center',
            renderer: this.flagRenderer
        },{
            id: 'subject',
            header: this.app.i18n._("Subject"),
            width: 300,
            sortable: true,
            dataIndex: 'subject'
        },{
            id: 'from_email',
            header: this.app.i18n._("From (Email)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_email'
        },{
            id: 'from_name',
            header: this.app.i18n._("From (Name)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_name'
        },{
            id: 'sender',
            header: this.app.i18n._("Sender"),
            width: 100,
            sortable: true,
            dataIndex: 'sender',
            hidden: true
        },{
            id: 'to',
            header: this.app.i18n._("To"),
            width: 150,
            sortable: true,
            dataIndex: 'to',
            hidden: true
        },{
            id: 'sent',
            header: this.app.i18n._("Sent"),
            width: 100,
            sortable: true,
            dataIndex: 'sent',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'received',
            header: this.app.i18n._("Received"),
            width: 100,
            sortable: true,
            dataIndex: 'received',
            hidden: true,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'folder_id',
            header: this.app.i18n._("Folder"),
            width: 100,
            sortable: true,
            dataIndex: 'folder_id',
            hidden: true,
            renderer: this.accountAndFolderRenderer.createDelegate(this)
        },{
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 80,
            sortable: true,
            dataIndex: 'size',
            hidden: true,
            renderer: Ext.util.Format.fileSize
        }];
    },
    
    /**
     * attachment column renderer
     * 
     * @param {String} value
     * @return {String}
     * @private
     */
    attachmentRenderer: function(value, metadata, record) {
        var result = '';
        
        if (value == 1) {
            result = '<img class="FelamimailFlagIcon" src="images/oxygen/16x16/actions/attach.png">';
        }
        
        return result;
    },
    
    /**
     * get flag icon
     * 
     * @param {String} flags
     * @return {String}
     * @private
     * 
     * TODO  use spacer if first flag(s) is/are not set?
     */
    flagRenderer: function(value, metadata, record) {
        var icons = [],
            result = '',
            i18n = Tine.Tinebase.appMgr.get('Felamimail').i18n;
            
        if (record.hasFlag('\\Answered')) {
            icons.push({src: 'images/icon-set/icon_email_answer.svg', qtip: i18n._('Answered')});
        }   
        if (record.hasFlag('Passed')) {
            icons.push({src: 'images/icon-set/icon_email_forward.svg', qtip: i18n._('Forwarded')});
        }
        
        if (record.get('is_spam_suspicions')) {
            icons.push({src: 'images/icon-set/icon_spam.svg', qtip: i18n._('This message might be SPAM')});
        }

        if (record.hasFlag('Tine20')) {
            const icon = record.getTine20Icon();
            icons.push({src: icon, qtip: i18n._('Tine20')});
        }

        if (record.get('content_type') === 'multipart/encrypted') {
            icons.push({src: 'images/icon-set/icon_lock.svg', qtip: i18n._('Encrypted Message')});
        }

        Ext.each(icons, function(icon) {
            result += '<img class="FelamimailFlagIcon" src="' + icon.src + '" ext:qtip="' + Ext.util.Format.htmlEncode(icon.qtip) + '">';
        }, this);

        let fileLocations = record.get('fileLocations');
        if (_.isArray(fileLocations) && fileLocations.length) {
            const fileLocationText = Tine.Felamimail.MessageFileButton.getFileLocationText(fileLocations, '<br>');

            result +=  fileLocationText ? ('<img class="FelamimailFlagIcon MessageFileIcon" src="images/icon-set/icon_download.svg" ' +
                'ext:qtitle="' + Ext.util.Format.htmlEncode(i18n._('Filed as:')) + '"' +
                'ext:qtip="' + Ext.util.Format.htmlEncode(fileLocationText) + '"' +
            '>') : '';
        }

        return result;
    },

    /**
     * returns account and folder globalname
     * 
     * @param {String} folderId
     * @param {Object} metadata
     * @param {Folder|Account} record
     * @return {String}
     */
    accountAndFolderRenderer: function(folderId, metadata, record) {
        var folderStore = this.app.getFolderStore(),
            account = this.app.getAccountStore().getById(record.get('account_id')),
            result = (account) ? account.get('name') : record.get('account_id'),
            folder = folderStore.getById(folderId);
        
        if (! folder) {
            folder = folderStore.getById(record.id);
            if (! folder) {
                // only account
                return (result) ? result : record.get('name');
            }
        }

        result += '/';
        if (folder) {
            result += folder.get('globalname');
        }

        return result;
    },

    /**
     * executed when user clicks refresh btn
     */
    doRefresh: function() {
        var folder = this.getCurrentFolderFromTree(),
            refresh = this.pagingToolbar.refresh;
            
        // refresh is explicit
        this.editBuffer = [];
        this.manualRefresh = true;
        
        if (folder) {
            refresh.disable();
            Tine.log.info('User forced mail check for folder "' + folder.get('localname') + '"');
            this.app.checkMails(folder, function() {
                refresh.enable();
                this.manualRefresh = false;
            });
        } else {
            this.filterToolbar.onFilterChange();
        }
    },
    
    /**
     * get currently selected folder from tree
     * @return {Tine.Felamimail.Model.Folder}
     */
    getCurrentFolderFromTree: function() {
        var tree = this.app.getMainScreen().getTreePanel(),
            node = tree ? tree.getSelectionModel().getSelectedNode() : null,
            folder = node ? this.app.getFolderStore().getById(node.id) : null;
        
        return folder;
    },
    
    /**
     * delete messages handler:
     * delete messages from trash/drafts folder, move messages to trash from other folders
     * 
     * @return {Boolean}
     */
    onDeleteRecords: function() {
        const account = this.app.getActiveAccount();
        const trashId = (account) ? account.getTrashFolderId() : null;
        const trash = trashId ? this.app.getFolderStore().getById(trashId) : null;
        const draftsFolderId = account.getSpecialFolderId('drafts_folder');
        const draftsFolder = draftsFolderId ? this.app.getFolderStore().getById(draftsFolderId) : null;

        let moveMessages;

        if (draftsFolder && draftsFolder.isCurrentSelection()) {
            moveMessages = false;
        } else {
            const trashConfigured = account.get('trash_folder');
            moveMessages = trash && ! trash.isCurrentSelection() || ! trash && trashConfigured;
        }

        return moveMessages
                ? this.moveSelectedMessages(trash, true, false)
                : this.deleteSelectedMessages();
    },

    /**
     * move messages handler
     *
     * @return {void}
     */
    onMoveRecords: function() {
        var selectPanel = Tine.Felamimail.FolderSelectPanel.openWindow({
            allAccounts: true,
            listeners: {
                scope: this,
                folderselect: function(node) {
                    var folder = new Tine.Felamimail.Model.Folder(node.attributes, node.attributes.id);
                    this.moveSelectedMessages(folder, false, false);
                    selectPanel.close();
                }
            }
        });
    },

    /**
     * copy messages handler
     *
     * @return {void}
     */
    onCopyRecords: function() {
        var selectPanel = Tine.Felamimail.FolderSelectPanel.openWindow({
            allAccounts: true,
            listeners: {
                scope: this,
                folderselect: function(node) {
                    var folder = new Tine.Felamimail.Model.Folder(node.attributes, node.attributes.id);
                    this.moveSelectedMessages(folder, false, true);
                    selectPanel.close();
                }
            }
        });
    },

    /**
     * file selected messages to Filemanager
     */
    onFileRecords: function() {
        var filePicker = new Tine.Filemanager.FilePickerDialog({
            windowTitle: this.app.i18n._('Select Message File Location'),
            singleSelect: true,
            requiredGrants: ['addGrant'],
            constraint: 'folder'
        });

        filePicker.on('selected', function (node) {
            this.fileRecords('Filemanager', node[0].path);
        }, this);

        filePicker.openWindow();
    },

    /**
     * file messages
     *
     * @param appName
     * @param path
     */
    fileRecords: function(appName, path) {
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter();

        this.fileMessagesLoadMask = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Filing Messages')});
        this.fileMessagesLoadMask.show();
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.fileMessages',
                filterData: filter,
                targetApp: appName,
                targetPath: path
            },
            timeout: 3600000, // 1 hour
            scope: this,
            success: function(result, request){
                this.afterFileRecords(result, request);
            },
            failure: function(response, request) {
                var responseText = Ext.util.JSON.decode(response.responseText),
                    exception = responseText.data;
                this.afterFileRecords(response, request, exception);
            }
        });
    },

    /**
     * show feedback when message filing has been (un)successful
     *
     * TODO reload grid when request returns?
     */
    afterFileRecords: function(result, request, error) {
        Tine.log.info('Tine.Felamimail.GridPanel::afterFileRecords');
        Tine.log.debug(result);

        this.fileMessagesLoadMask.hide();

        if (error) {
            Ext.Msg.show({
                title: this.app.i18n._('Error Filing Message'),
                msg: error.message ? error.message : this.app.i18n._('Could not file message.'),
                icon: Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
        }
    },

    /**
     * permanently delete selected messages
     */
    deleteSelectedMessages: function() {
        this.moveOrDeleteMessages(null);
    },
    
    /**
     * move selected messages to given folder
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     * @param {Boolean} toTrash
     * @param {Boolean} keepOriginalMessages
     */
    moveSelectedMessages: function(folder, toTrash, keepOriginalMessages) {
        if (folder && folder.isCurrentSelection()) {
            // nothing to do ;-)
            return;
        }
        
        this.moveOrDeleteMessages(folder, toTrash, keepOriginalMessages);
    },
    
    /**
     * move (folder !== null) or delete selected messages 
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     * @param {Boolean} toTrash
     * @param {Boolean} keepOriginalMessages
     */
    moveOrDeleteMessages: function(folder, toTrash, keepOriginalMessages) {
        
        // this is needed to prevent grid reloads while messages are moved or deleted
        this.movingOrDeleting = true;
        
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgsIds = [];
        
        if (sm.isFilterSelect) {
            var msgs = this.getStore(),
                nextRecord = null;
        } else {
            var msgs = sm.getSelectionsCollection(),
                nextRecord = this.getNextMessage(msgs);
        }
        
        var increaseUnreadCountInTargetFolder = 0;
        msgs.each(function(msg) {
            var isSeen = msg.hasFlag('\\Seen'),
                currFolder = this.app.getFolderStore().getById(msg.get('folder_id')),
                diff = isSeen ? 0 : 1;
            
            if (currFolder) {
                currFolder.set('cache_unreadcount', currFolder.get('cache_unreadcount') - diff);
                currFolder.set('cache_totalcount', currFolder.get('cache_totalcount') - 1);
                currFolder.set('cache_status', 'pending');
                currFolder.commit();
            }
            increaseUnreadCountInTargetFolder += diff;
           
            msgsIds.push(msg.id);
            if (! keepOriginalMessages) {
                this.getStore().remove(msg);
            }
        },  this);
        
        if (folder) {
            // update unread count of target folder (only when moving)
            folder.set('cache_unreadcount', folder.get('cache_unreadcount') + increaseUnreadCountInTargetFolder);
            folder.set('cache_status', 'pending');
            folder.commit();
        }

        if (! keepOriginalMessages) {
            this.deleteQueue = this.deleteQueue.concat(msgsIds);
        }
        if (nextRecord !== null) {
            sm.selectRecords([nextRecord]);
        }
        
        this.app.checkMailsDelayedTask.delay(1000);
        
        var callbackFn = this.onAfterDelete.createDelegate(this, [msgsIds]);
        
        if (folder !== null || toTrash) {
            // move
            var targetFolderId = (toTrash) ? '_trash_' : folder.id;
            this.deleteTransactionId = Tine.Felamimail.messageBackend.moveMessages(filter, targetFolderId, keepOriginalMessages, {
                callback: callbackFn
            });
        } else {
            // delete
            this.deleteTransactionId = Tine.Felamimail.messageBackend.addFlags(filter, '\\Deleted', {
                callback: callbackFn
            });
        }
    },

    /**
     * get next message in grid
     * 
     * @param msgs
     * @return Tine.Felamimail.Model.Message
     */
    getNextMessage: function(msgs) {
        var nextRecord = null;
        
        if (msgs.getCount() >= 1 && this.getStore().getCount() > 1) {
            if (msgs.getCount() === 1) {
                // select next message (or previous if it was the last or BACKSPACE)
                var lastIdx = this.getStore().indexOf(msgs.last()),
                    direction = Ext.EventObject.getKey() == Ext.EventObject.BACKSPACE ? -1 : +1;

                nextRecord = this.getStore().getAt(lastIdx + 1 * direction);
                if (! nextRecord) {
                    nextRecord = this.getStore().getAt(lastIdx + (-1) * direction);
                }
            }
            
            if (msgs.getCount() > 1) {
                const sm = this.getGrid().getSelectionModel();
                msgs.each(function (msg) {
                    let idx = this.getStore().indexOfId(msg.id);
                    nextRecord = this.getStore().getAt(idx + 1);
                    
                    // return the first unselected row as nextRecord
                    if (!sm.isSelected(idx + 1)) {
                        return false;
                    }
                    
                    //select the previous row before the first selected one, if user select till the last row
                    if (idx === (this.getStore().getCount() - 1)) {
                        idx = this.getStore().indexOfId(msgs.items[0].id);
                        nextRecord = this.getStore().getAt(idx - 1) || null;
                        return false;
                    }
                }, this)
            }
        }
        
        return nextRecord;
    },
    
    /**
     * executed after a msg compose
     * 
     * @param {String} composedMsg
     * @param {String} action
     * @param {Array}  [affectedMsgs]  messages affected 
     * @param {String} [mode]
     */
    onAfterCompose: function(composedMsg, action, affectedMsgs, mode) {
        Tine.log.debug('Tine.Felamimail.GridPanel::onAfterCompose / arguments:');
        Tine.log.debug(arguments);

        // mark send folders cache status incomplete
        composedMsg = Ext.isString(composedMsg) ? new this.recordClass(Ext.decode(composedMsg)) : composedMsg;
        
        // NOTE: if affected messages is decoded, we need to fetch the originals out of our store
        if (Ext.isString(affectedMsgs)) {
            var msgs = [],
                store = this.getStore();
            Ext.each(Ext.decode(affectedMsgs), function(msgData) {
                var msg = store.getById(msgData.id);
                if (msg) {
                    msgs.push(msg);
                }
            }, this);
            affectedMsgs = msgs;
        }
        
        var composerAccount = this.app.getAccountStore().getById(composedMsg.get('account_id')),
            sendFolderId = composerAccount ? composerAccount.getSendFolderId() : null,
            sendFolder = sendFolderId ? this.app.getFolderStore().getById(sendFolderId) : null;
            
        if (sendFolder) {
            sendFolder.set('cache_status', 'incomplete');
        }
        
        if (Ext.isArray(affectedMsgs)) {
            Ext.each(affectedMsgs, function(msg) {
                if (['reply', 'forward'].indexOf(action) !== -1) {
                    msg.addFlag(action === 'reply' ? '\\Answered' : 'Passed');
                } else if (action === 'senddraft') {
                    this.deleteTransactionId = Tine.Felamimail.messageBackend.addFlags(msg.id, '\\Deleted', {
                        callback: this.onAfterDelete.createDelegate(this, [[msg.id]])
                    });
                }
            }, this);
        } 
    },
    
    /**
     * executed after msg delete
     * 
     * @param {Array} [ids]
     */
    onAfterDelete: function(ids) {
        this.deleteQueue = this.deleteQueue.diff(ids);
        this.editBuffer = this.editBuffer.diff(ids);
        
        this.movingOrDeleting = false;
        
        Tine.log.debug('Tine.Felamimail.GridPanel::onAfterDelete() -> Loading grid data after delete.');
        this.loadGridData({
            removeStrategy: 'keepBuffered',
            autoRefresh: true
        });
    },
    
    /**
     * check if delete/move action is running atm
     * 
     * @return {Boolean}
     */
    noDeleteRequestInProgress: function() {
        return (
            ! this.movingOrDeleting && 
            (! this.deleteTransactionId || ! Tine.Felamimail.messageBackend.isLoading(this.deleteTransactionId))
        );
    },
    
    /**
     * compose new message handler
     */
    onMessageCompose: function() {
        var activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();
        
        var win = Tine.Felamimail.MessageEditDialog.openWindow({
            accountId: activeAccount ? activeAccount.id : null,
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['compose', []], 1)
            }
        });
    },
    
    /**
     * forward message(s) handler
     */
    onMessageForward: function() {
        var sm = this.getGrid().getSelectionModel(),
            msgs = sm.getSelections(),
            msgsData = [];
            
        Ext.each(msgs, function(msg) {msgsData.push(msg.data)}, this);
        
        if (sm.getCount() > 0) {
            var win = Tine.Felamimail.MessageEditDialog.openWindow({
                forwardMsgs : Ext.encode(msgsData),
                listeners: {
                    'update': this.onAfterCompose.createDelegate(this, ['forward', msgs], 1)
                }
            });
        }
    },
    
    /**
     * reply message handler
     * 
     * @param {bool} toAll
     */
    onMessageReplyTo: function(toAll) {
        var sm = this.getGrid().getSelectionModel(),
            msg = sm.getSelected();
            
        Tine.Felamimail.MessageEditDialog.openWindow({
            replyTo : Ext.encode(msg.data),
            replyToAll: toAll,
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['reply', [msg]], 1)
            }
        });
    },
    
    /**
     * called when a row gets selected
     * 
     * @param {SelectionModel} sm
     * @param {Number} rowIndex
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Number} retryCount
     * 
     * TODO find a better way to check if body is fetched, this does not work correctly if a message is removed
     *       and the next one is selected automatically
     */
    onRowSelection: function(sm, rowIndex, record, retryCount) {
        if (sm.getCount() == 1 && (! retryCount || retryCount < 5) && ! record.bodyIsFetched()) {
            Tine.log.debug('Tine.Felamimail.GridPanel::onRowSelection() -> Deferring onRowSelection');
            retryCount = (retryCount) ? retryCount++ : 1;
            return this.onRowSelection.defer(250, this, [sm, rowIndex, record, retryCount+1]);
        }
        
        if (sm.getCount() == 1 && sm.isIdSelected(record.id) && !record.hasFlag('\\Seen')) {
            Tine.log.debug('Tine.Felamimail.GridPanel::onRowSelection() -> Selected unread message');
            Tine.log.debug(record);

            if (Tine.Felamimail.registry.get('preferences').get('markEmailRead') === 1) {
                record.addFlag('\\Seen');
                record.mtime = new Date().getTime();
                Tine.Felamimail.messageBackend.addFlags(record.id, '\\Seen');
                this.app.getMainScreen().getTreePanel().decrementCurrentUnreadCount();
            }
            
            if (record.get('headers')['disposition-notification-to']) {
                Ext.Msg.confirm(
                    this.app.i18n._('Send Reading Confirmation'),
                    this.app.i18n._('Do you want to send a reading confirmation message?'), 
                    function(btn) {
                        if (btn == 'yes'){
                            Tine.Felamimail.sendReadingConfirmation(record.id);
                        }
                    }, 
                    this
                );
            }
        }
    },

    /**
     * open first file location when file icon is clicked
     */
    onRowClick: function(grid, row, e) {
        if (e.getTarget('.MessageFileIcon')) {
            let record = this.getStore().getAt(row);
            let fileLocation = record.get('fileLocations')[0];
            Tine.Felamimail.MessageFileButton.locationClickHandler(fileLocation.model, fileLocation.record_id);

            e.stopEvent();
        }

        Tine.Felamimail.GridPanel.superclass.onRowClick.apply(this, arguments);
    },

    /**
     * row doubleclick handler
     * 
     * - opens message edit dialog (if draft/template)
     * - opens message display dialog (everything else)
     * 
     * @param {Tine.Felamimail.GridPanel} grid
     * @param {Row} row
     * @param {Event} e
     */
    onRowDblClick: function(grid, row, e) {
        
        var record = this.grid.getSelectionModel().getSelected(),
            folder = this.app.getFolderStore().getById(record.get('folder_id')),
            account = this.app.getAccountStore().getById(folder.get('account_id')),
            action = (folder.get('globalname') == account.get('drafts_folder')) ? 'senddraft' :
                      folder.get('globalname') == account.get('templates_folder') ? 'sendtemplate' : null,
            win;
        
        // check folder to determine if mail should be opened in compose dlg
        if (action !== null) {
            win = Tine.Felamimail.MessageEditDialog.openWindow({
                draftOrTemplate: Ext.encode(record.data),
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose.createDelegate(this, [action, [record]], 1)
                }
            });
        } else {
            win = Tine.Felamimail.MessageDisplayDialog.openWindow({
                record: Ext.encode(record.data),
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose.createDelegate(this, ['compose', []], 1),
                    'remove': this.onRemoveInDisplayDialog
                }
            });
        }
    },
    
    /**
     * message got removed in display dialog
     * 
     * @param {} msgData
     */
    onRemoveInDisplayDialog: function (msgData) {
        var msg = this.getStore().getById(Ext.decode(msgData).id),
            folderId = msg ? msg.get('folder_id') : null,
            folder = folderId ? this.app.getFolderStore().getById(folderId) : null,
            accountId = folder ? folder.get('account_id') : null,
            account = accountId ? this.app.getAccountStore().getById(accountId) : null;
            
        this.getStore().remove(msg);
        this.onAfterDelete(null);
    },    
    
    /**
     * called when the store gets updated
     * 
     * NOTE: we only allow updateing flags BUT the actual updating is done 
     *       directly from the UI fn's to support IMAP optimised bulk actions
     */
    onStoreUpdate: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT && record.isModified('flags')) {
            record.commit()
        }
    },
    
    /**
     * key down handler
     * 
     * @param {Event} e
     */
    onKeyDown: function(e) {
        // no keys for quickadds etc.
        if (e.getTarget('input') || e.getTarget('textarea')) return;

        switch (e.getKey()) {
            case e.N:
            case e.M:
                this.onMessageCompose();
                e.preventDefault();
                break;
            case e.R:
                this.onMessageReplyTo();
                e.preventDefault();
                break;
            case e.L:
                this.onMessageForward();
                e.preventDefault();
                break;
        }

        // TODO add keys to "help" message box of generic grid onKeyDown()

        Tine.Felamimail.GridPanel.superclass.onKeyDown.call(this, e);
    },
    
    /**
     * toggle flagged status of mail(s)
     * - Flagged/Seen
     * 
     * @param {Button} button
     * @param {Event} event
     * @param {String} flag
     */
    onToggleFlag: function(btn, e, flag) {
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgs = sm.isFilterSelect ? this.getStore() : sm.getSelectionsCollection(),
            flagCount = 0;
            
        // switch all msgs to one state -> toogle most of them
        msgs.each(function(msg) {
            flagCount += msg.hasFlag(flag) ? 1 : 0;
        });
        var action = flagCount >= Math.round(msgs.getCount()/2) ? 'clear' : 'add';
        
        Tine.log.info('Tine.Felamimail.GridPanel::onToggleFlag - Toggle flag for ' + msgs.getCount() + ' message(s): ' + flag);
        
        // mark messages in UI and add to edit buffer
        msgs.each(function(msg) {
            // update unreadcount
            if (flag === '\\Seen') {
                var isSeen = msg.hasFlag('\\Seen'),
                    folder = this.app.getFolderStore().getById(msg.get('folder_id')),
                    diff = (action === 'clear' && isSeen) ? 1 :
                           (action === 'add' && ! isSeen) ? -1 : 0;
                
                if (folder) {
                    folder.set('cache_unreadcount', folder.get('cache_unreadcount') + diff);
                    if (sm.isFilterSelect && sm.getCount() > 50 && folder.get('cache_status') !== 'pending') {
                        Tine.log.debug('Tine.Felamimail.GridPanel::onToggleFlag - Set cache status to pending for folder ' + folder.get('globalname'));
                        folder.set('cache_status', 'pending');
                    }
                    folder.commit();
                }
            }
            
            msg[action + 'Flag'](flag);
            
            this.addToEditBuffer(msg);
        }, this);
        
        if (sm.isFilterSelect && sm.getCount() > 50) {
            Tine.log.debug('Tine.Felamimail.GridPanel::moveOrDeleteMessages - Update message cache for "pending" folders');
            this.app.checkMailsDelayedTask.delay(1000);
        }
        
        // do request
        Tine.Felamimail.messageBackend[action+ 'Flags'](filter, flag);
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        this.supr().onStoreBeforeload.apply(this, arguments);

        // make sure, our handler is called just before the request is sent
        this.store.on('beforeload', _.bind(this.onStoreBeforeLoadFolderChange, this), this, {single: true});

        if (! Ext.isEmpty(this.deleteQueue)) {
            options.params.filter.push({field: 'id', operator: 'notin', value: this.deleteQueue});
        }
    },

    onStoreBeforeLoadFolderChange: function (store, options) {

        const isSendFolderPath = this.isSendFolderPathInFilterParams(options.params);

        if (isSendFolderPath && ! this.sentFolderSelected) {
            this.changeFilterInParams(options.params, 'query', 'to');
            this.changeGridState(this.sendFolderGridStateId);
            this.sentFolderSelected = true;

        } else if (! isSendFolderPath && this.sentFolderSelected) {
            this.changeFilterInParams(options.params, 'to', 'query');
            this.changeGridState(this.gridConfig.stateId);
            this.sentFolderSelected = false;
        }
    },

    isSendFolderPathInFilterParams: function (params) {
        const pathFilter = _.find(_.get(params, 'filter[0].filters[0].filters', {}), {
            field: 'path'
        });
        const operator = _.get(pathFilter, 'operator', '');
        const value = operator.match(/equals|in/) ? _.get(pathFilter, 'value', null) : null;
        const pathFilterValue =  value && _.isArray(value) ? value[0] : value;

        const pathParts = _.isString(pathFilterValue) ? pathFilterValue.split('/') : null;
        if (! pathParts || pathParts.length < 3) {
            return false;
        }
        const composerAccount = this.app.getAccountStore().getById(pathParts[1]);

        const sendFolderIds = composerAccount ? [
            composerAccount.getSendFolderId(),
            composerAccount.getSpecialFolderId('templates_folder'),
            composerAccount.getSpecialFolderId('drafts_folder')
        ]: null;

        return (-1 !== sendFolderIds.indexOf(pathParts[2]));
    },

    /**
     * @param params
     * @param from
     * @param to
     *
     * TODO just change the field??
     */
    changeFilterInParams: function (params, from, to) {
        const queryFilter = params.filter[0] && params.filter[0].filters
            ? _.remove(params.filter[0].filters[0].filters, {
                field: from
            })
            : [];
        if (queryFilter.length > 0) {
            params.filter[0].filters[0].filters =
                params.filter[0].filters[0].filters.concat({
                    field: to,
                    operator: 'contains',
                    value: queryFilter[0].value
                });
        }
    },

    /**
     * // if send, draft, template folders are selected, do the following:
     * // - hide from email + name columns from grid
     * // - show to column in grid
     * // - save this state
     * // - if grid state is changed by user, do not change columns by user
     *
     * // if switched from send, draft, template to "normal" folder
     * // - switch to default state
     *
     * TODO make it work with current state / set/apply from existing state
     *
     * @param stateId
     */
    changeGridState: function (stateId) {

        this.stateId = stateId;

        // if (! Ext.state.Manager.get(stateId)) {

        const cm = this.grid.getColumnModel();
        const from_email_index = cm.getIndexById('from_email');
        const from_name_index = cm.getIndexById('from_name');
        const to_index = cm.getIndexById('to');

        if (stateId === this.sendFolderGridStateId) {
            // - hide from email + name columns from grid
            // - show to column in grid

            cm.setHidden(from_email_index, true);
            cm.setHidden(from_name_index, true);
            cm.setHidden(to_index, false);
        } else {
            cm.setHidden(from_email_index, false);
            cm.setHidden(from_name_index, false);
            cm.setHidden(to_index, true);
        }

        // this.saveState();

        // } else {
        //     // TODO make this work
        //     const state = this.getState();
        //     this.applyState(state);
        // }
    },

    /**
     *  called after a new set of Records has been loaded
     *  
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        this.supr().onStoreLoad.apply(this, arguments);
        
        Tine.log.debug('Tine.Felamimail.GridPanel::onStoreLoad(): store loaded new records.');

        let folder = this.getCurrentFolderFromTree();
        if (folder && records.length < folder.get('cache_totalcount')) {
            Tine.log.debug('Tine.Felamimail.GridPanel::onStoreLoad() - Count mismatch: got ' + records.length + ' records for folder ' + folder.get('globalname'));
            Tine.log.debug(folder);
            folder.set('cache_status', 'pending');
            folder.commit();
            this.app.checkMailsDelayedTask.delay(1000);
        }
        
        this.updateQuotaBar();
    },
    
    /**
     * update quotaBar / only do it if we have a path filter with a single account id
     * 
     * @param {Record} accountInbox
     */
    updateQuotaBar: function(accountInbox) {
        var accountId = this.extractAccountIdFromFilter();
        
        if (accountId === null) {
            Tine.log.debug('No or multiple account ids in filter. Resetting quota bar.');
            this.quotaBar.hide();
            return;
        }
            
        if (! accountInbox) {
            var accountInbox = this.app.getFolderStore().queryBy(function(folder) {
                return folder.isInbox() && (folder.get('account_id') == accountId);
            }, this).first();
        }
        if (accountInbox && parseInt(accountInbox.get('quota_limit'), 10) && accountId == accountInbox.get('account_id')) {
            Tine.log.debug('Showing quota info.');
            
            var limit = parseInt(accountInbox.get('quota_limit'), 10) / 1024,
                usage = parseInt(accountInbox.get('quota_usage'), 10) * 1024;
            
            this.quotaBar.show();
            this.quotaBar.update(Tine.widgets.grid.QuotaRenderer(usage, limit, /*use SoftQuota*/ false));
        } else {
            Tine.log.debug('No account inbox found or no quota info found.');
            this.quotaBar.hide();
        }
    },
    
    /**
     * get account id from filter (only returns the id if a single account id was found)
     * 
     * @param {Array} filter
     * @return {String}
     */
    extractAccountIdFromFilter: function(filter) {
        if (! filter) {
            filter = this.filterToolbar.getValue();
        }
        
        // use first OR panel in case of filterPanel
        Ext.each(filter, function(filterData) {
            if (filterData.condition && filterData.condition == 'OR') {
                filter = filterData.filters[0].filters;
                return false;
            }
        }, this);
        
        // condition from filterPanel
        while (filter.filters || (Ext.isArray(filter) && filter.length > 0 && filter[0].filters)) {
            filter = (filter.filters) ? filter.filters : filter[0].filters;
        }
        
        var accountId = null, 
            filterAccountId = null,
            accountIdMatch = null;

        for (var i = 0; i < filter.length; i++) {
            if (filter[i].field == 'path' && filter[i].operator == 'in') {
                for (var j = 0; j < filter[i].value.length; j++) {
                    accountIdMatch = filter[i].value[j].match(/^\/([a-z0-9]*)/i);
                    if (accountIdMatch) {
                        filterAccountId = accountIdMatch[1];
                        if (accountId && accountId != filterAccountId) {
                            // multiple different account ids found!
                            return null;
                        } else {
                            accountId = filterAccountId;
                        }
                    }
                }
            }
        }
        
        return accountId;
    },
    
    /**
     * add new account button
     * 
     * @param {Button} button
     * @param {Event} event
     */
    onAddAccount: function(button, event) {
        // it is only allowed to create user (external) accounts here
        var newAccount = new Tine.Felamimail.Model.Account({
            type: 'user'
        });
        // this is a little bit clunky but seems to be required to prevent record loading in AccountEditDialog
        newAccount.id = null;

        // make sure accountStore is initialised
        this.app.getAccountStore();

        var popupWindow = Tine.Felamimail.AccountEditDialog.openWindow({
            record: newAccount
        });
    },
    
    /**
     * print handler
     * 
     * @todo move this to Ext.ux.Printer as iframe driver
     * @param {Tine.Felamimail.GridDetailsPanel} details panel [optional]
     */
    onPrint: function(detailsPanel) {
        var id = Ext.id(),
            doc = document,
            frame = doc.createElement('iframe');
            
        Ext.fly(frame).set({
            id: id,
            name: id,
            style: {
                position: 'absolute',
                width: '210mm',
                height: '297mm',
                top: '-10000px', 
                left: '-10000px'
            }
        });
        
        doc.body.appendChild(frame);

        Ext.fly(frame).set({
           src : Ext.SSL_SECURE_URL
        });

        var doc = frame.contentWindow.document || frame.contentDocument || WINDOW.frames[id].document,
            content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);
            
        doc.open();
        doc.write(content);
        doc.close();
        
        frame.contentWindow.focus();
        frame.contentWindow.print();
    },
    
    /**
     * get detail panel content
     * 
     * @param {Tine.Felamimail.GridDetailsPanel} details panel
     * @return {String}
     */
    getDetailsPanelContentForPrinting: function(detailsPanel) {
        // TODO somehow we have two <div class="preview-panel-felamimail"> -> we need to fix that and get the first element found
        var detailsPanels = detailsPanel.getEl().query('.preview-panel-felamimail');
        var detailsPanelContent = (detailsPanels.length > 1) ? detailsPanels[1].innerHTML : detailsPanels[0].innerHTML;
        
        var buffer = '<html><head>';
        buffer += '<title>' + this.app.i18n._('Print Preview') + '</title>';
        buffer += '</head><body>';
        buffer += detailsPanelContent;
        buffer += '</body></html>';
        
        return buffer;
    },
    
    /**
     * print preview handler
     * 
     * @param {Tine.Felamimail.GridDetailsPanel} details panel [optional]
     */
    onPrintPreview: function(detailsPanel) {
        var content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);
        
        var win = window.open('about:blank',this.app.i18n._('Print Preview'),'width=500,height=500,scrollbars=yes,toolbar=yes,status=yes,menubar=yes');
        win.document.open()
        win.document.write(content);
        win.document.close();
        win.focus();
    },
    
    /**
     * format headers
     * 
     * @param {Object} headers
     * @param {Bool} ellipsis
     * @param {Bool} onlyImportant
     * @return {String}
     */
    formatHeaders: function(headers, ellipsis, onlyImportant, plain) {
        var result = '';
        for (header in headers) {
            if (headers.hasOwnProperty(header) && 
                    (! onlyImportant || header == 'from' || header == 'to' || header == 'cc' || header == 'subject' || header == 'date')) 
            {
                result += (plain ? (header + ': ') : ('<b>' + header + ':</b> '))
                    + Ext.util.Format.htmlEncode(
                        (ellipsis) 
                            ? Ext.util.Format.ellipsis(headers[header], 40)
                            : headers[header]
                    ) + (plain ? '\n' : '<br/>');
            }
        }
        return result;
    },

    /**
     * process spam strategy and refresh grid panel
     *
     * @param option
     */
    processSpamStrategy: async function (option) {
        const sm = this.getGrid().getSelectionModel();
        const msgs = sm.isFilterSelect ? this.getStore() : sm.getSelectionsCollection();
        const nextRecord = sm.isFilterSelect ? null : this.getNextMessage(msgs);
        
        try {
            const promises = [];
            
            msgs.each(function (msg) {
                if ('spam' === option) {
                    this.getStore().remove(msg);
                    this.deleteQueue.push(msg.id);
                }
                
                if ('ham' === option) {
                    let subject = msg.get('subject').replace(/^SPAM\? \(.+\) \*\*\* /, '');
                    msg.set('subject', subject);
                    msg.set('is_spam_suspicions', false);
                    msg.commit();
                    this.editBuffer.push(msg);
                }
                
                promises.push(Tine.Felamimail.processSpam(msg, option));
            }, this);
            
            if (nextRecord && 'spam' === option) {
                sm.selectRecords([nextRecord]);
            }
            
            this.doRefresh();
            await Promise.allSettled(promises);
        } catch (e) {
            this.doRefresh();
        }
    },

    /**
     * - hide spam actions if the current folder is trash/junk folder
     * - disable spam actions if the message has no spam flag
     */
    onDisplaySpamActions: function () {
        if (!this.app.featureEnabled('featureSpamSuspicionStrategy')) {
            return ;
        }

        const sm = this.getGrid().getSelectionModel();
        const msgs =  sm.getCount() > 0 ? sm.getSelectionsCollection() : null;
        const folder = this.getCurrentFolderFromTree();
        const account = folder ? this.app.getAccountStore().getById(folder.get('account_id')) : null;

        this.action_spam.show();
        this.action_ham.show();

        if (folder && account && (
            folder.get('globalname') === account.get('trash_folder')
            || folder.get('localname').match(/junk/i)
        )) {
            this.action_spam.hide();
            this.action_ham.hide();
        }

        if (msgs) {
            msgs.each(function (msg) {
                if (!msg.get('is_spam_suspicions')) {
                    this.action_ham.disable();
                }
            }, this);
        }
    }
});
