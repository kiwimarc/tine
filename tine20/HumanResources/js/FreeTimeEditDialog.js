/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.FreeTimeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.FreeTimeEditDialog
 */
Tine.HumanResources.FreeTimeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * the datepicker holds a calendar to select the dates of vacation or sickness
     * 
     * @type {Tine.HumanResources.DatePicker}
     */
    datePicker: null,
    
    /**
     * the account picker holds the account the (vacation-)days are taken from
     * @type {Tine.Tinebase.widgets.form.RecordPickerComboBox}
     */
    accountPicker: null,
    
    
    // private
    recordFromJson: true,
    
    /**
     * inits the component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources');

        this.feastAndFreeDaysCache = {};
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onAfterRecordLoad: async function() {
        await Promise.all([
            this.isRendered(),
            this.record.resolveForeignRecords()
        ]);

        const statusPicker = this.getStatusPicker();
        if (statusPicker && this.record.get('created_by')) {
            statusPicker.setValue(this.record.get('status'));
        }
        
        this.datePicker.setValue(Date.parseDate(_.get(this.record, 'data.firstday_date'), Date.patterns.ISO8601Long) || new Date());
        this.datePicker.setSelected(_.map(_.get(this.record, 'data.freedays', []), (day) => {
            return Date.parseDate(day.date, Date.patterns.ISO8601Long);
        }));
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.onAfterRecordLoad.call(this);
    },

    checkStates: function() {
        Tine.HumanResources.FreeTimeEditDialog.superclass.checkStates.apply(this, arguments);

        // record vs. recordData!
        var type = this.typePicker.selectedRecord || this.record.get('type') || {};
        var employee = this.employeePicker.selectedRecord || this.record.get('employee_id');
        var employeeName = _.get(employee, 'data.n_fn', _.get(employee, 'n_fn', this.app.i18n._('Employee')));
        var typeString = _.get(type, 'data.name', _.get(type, 'name', type)) || 'Free Time';
        var isNewRecord = !this.record.get('creation_time');

        if (!isNewRecord) {
            // this.accountPicker.hide();
            this.window.setTitle(String.format(this.app.i18n._('Edit {0} for {1}'), this.app.i18n._hidden(typeString), employeeName));
        } else {
            this.window.setTitle(String.format(this.app.i18n._('Add {0} for {1}'),  this.app.i18n._hidden(typeString), employeeName));
        }

        this.employeePicker.setDisabled(!isNewRecord || this.fixedFields.indexOfKey('employee_id') >= 0);
        this.typePicker.setDisabled(!isNewRecord || this.fixedFields.indexOfKey('type') >= 0);
        
        this.sicknessStatusPicker[type.id === 'sickness' ? 'show' : 'hide']();
        this.vacationStatusPicker[type.id === 'vacation' ? 'show' : 'hide']();
        this.accountPicker[type.id === 'vacation' ? 'show' : 'hide']();
        this.accountPicker.setDisabled(!employee);
        this.remainingDaysField[type.id === 'vacation' ? 'show' : 'hide']();
    },
    
    /**
     * just break if at least one day is selected, otherwise close the window
     * 
     * @param {Boolean} closeWindow
     * @return {Boolean}
     */
    onApplyChanges: function(closeWindow) {
        // if no day is selected, show message and break saving
        if (! this.datePicker.getSelected().length) {
            var msg = this.app.i18n._('You have to select at least one day to save this freetime entry.');
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: this.app.i18n._('No day selected'), 
                msg: msg
            });
            
            return false;
        } else {
            Tine.HumanResources.FreeTimeEditDialog.superclass.onApplyChanges.call(this, closeWindow);
        }
    },
    
    /**
     * executed when record gets updated from form
     * @private
     */
    onRecordUpdate: function() {
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordUpdate.call(this);

        const statusPicker = this.getStatusPicker();
        this.record.set('status', statusPicker ? statusPicker.getValue() : null);
        
        this.record.set('account_id', _.get(this.accountPicker, 'selectedRecord.data'));
    },
    
    /**
     * creates the date picker
     */
    initDatePicker: function() {
        this.datePicker = new Ext.DatePicker({
            allowMultiSelection: true,
            listeners: {
                scope: this,
                periodchange: this.onPeriodChange,
                beforeselect: this.onBeforeDateSelect,
                select: this.onDateSelect,
            },
            plugins: [new Ext.ux.DatePickerWeekPlugin({
                weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK')
            })]
        });
    },

    onPeriodChange: async function(datePicker, period) {
        // datePicker must always have a value
        datePicker.setValue(period.from.add(Date.DAY, 15));
        if (! _.get(this, 'accountPicker.selectedRecord.data.id')) return;
        
        const years = _.uniq([period.from.format('Y'), period.until.format('Y')]);
        
        const pms = _.reduce(years, (pms, year) => {
            return pms.concat(_.get(this.feastAndFreeDaysCache, year) ? [] :
                this.getFeastAndFreeDays(year));
        }, []);

        // wait until cache is filled
        if (pms.length) this.datePicker.showLoadMask();
        await Promise.all(pms);
        
        // const days = [];
        const disabledDates = [];
        const dateClss = {};
        _.each(_.range(0, 42), (i) => {
            const day = period.from.add(Date.DAY, i);
            const date = day.format(datePicker.format);
            const isDisabled = Tine.HumanResources.Model.FreeTime.isExcludeDay(this.feastAndFreeDaysCache, day);
            const freeTimes = Tine.HumanResources.Model.FreeTime.getFreeTimes(this.feastAndFreeDaysCache, day);
            const isCurrent = _.find(_.get(this.record, 'data.freedays', []), {date: day.format(Date.patterns.ISO8601Long)});
            if (!isCurrent && (isDisabled || freeTimes.length)) {
                disabledDates.push(date);
            }
        });
    

        datePicker.setDisabledDates(disabledDates);
        // @TODO setStyle once we have colors
        // @TODO qTips for details?

        this.datePicker.hideLoadMask();
    },

    onBeforeDateSelect: function(datePicker, dateValue) {
        return !!this.form.findField('remaining_vacation_days').getValue();
    },
    
    onDateSelect: function(datePicker, dateValue) {
        const freeDays = this.datePicker.getSelected();
        this.record.set('freedays', _.map(freeDays, (day) => {
            return {date: day.format(Date.patterns.ISO8601Long)}
        }));
        this.record.set('firstday_date', freeDays[0]);
        this.record.set('lastday_date', freeDays[freeDays.length-1]);
        this.record.set('days_count', freeDays.length);

        this.checkStates();
    },
    
    /**
     * initializes the account picker
     */
    initAccountPicker: function() {
        let me = this;
        this.accountPicker = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'Account', {
            name: 'account_id',
            fieldLabel: this.app.i18n._('Personal account'),
            columnWidth: 1,
            hideMode: 'offsets',

            checkState: async function() {
                let employee = _.get(me, 'employeePicker.selectedRecord.data', me.record.get('employee_id'));
                let account = _.get(me, 'accountPicker.selectedRecord.data', me.record.get('account_id'));

                if (!employee) {
                    if (me.accountPicker) {
                        me.accountPicker.clearValue();
                    }
                    this.setDisabled(true);
                    return;
                }
                const employeeFilter = [{
                    field: 'employee_id', operator: 'AND', value: [
                        {field: ':id', operator: 'equals', value: _.get(employee, 'data', employee)}
                    ]
                }];
                this.additionalFilters = employeeFilter;
                
                // NOTE: account and year is somehow synonym. Unfortunately in some scenarios we only
                //       have the account_id and don't know the year which we need to load the rest of the data
                if (!_.get(account, 'id') || _.get(employee, 'id', employee) !== _.get(account, 'employee_id.id', _.get(account, 'employee_id'))) {
                    await me.showLoadMask();
                    
                    const filter = [_.isString(account) && account.length ?
                        {field: 'id', operator: 'equals', value: account} : // by id
                        {field: 'year', operator: 'equals', value: new Date().format('Y')} // by startYear
                    ].concat(employeeFilter);
                    
                    // fetch account
                    const accountsData = await Tine.HumanResources.searchAccounts(filter, []);
                    
                    if (_.get(accountsData, 'results.length') === 1) {
                        const accountData = _.get(accountsData, 'results.0');
                        me.accountPicker.setValue(accountData);
                        await me.onAccountSelect(accountData);
                    }
                    
                    return me.hideLoadMask();
                } else if (! Object.keys(me.feastAndFreeDaysCache).length) {
                    // initial load
                    me.onAccountSelect(account);
                }
            }
        });

        this.accountPicker.on('select', async function (combo, record, index) {
            if (record) {
                this.onAccountSelect(_.get(record, 'data'));
            }
        }, this);
    },
    
    onAccountSelect: async function (accountData) {
        this.feastAndFreeDaysCache = {};
        let year = _.get(accountData, 'year');
        await this.getFeastAndFreeDays(year);
        await this.datePicker.getPeriod().then(async (period) => {
            await this.onPeriodChange(this.datePicker, period);
        });
        this.checkStates();
    },
    
    getFeastAndFreeDays: async function(year) {
        let me = this;
        let employee = _.get(me, 'employeePicker.selectedRecord.data', me.record.get('employee_id'));
        let employeeId = _.get(employee, 'id', employee);
        let accountId = _.get(me, 'accountPicker.selectedRecord.data.id');
        var isNewRecord = !this.record.get('creation_time');
        let freeTimeId = isNewRecord ? null : me.record.get('id');
        
        return me.showLoadMask()
            .then(() => {
                const response = _.get(this.feastAndFreeDaysCache, year);
                if (response) {
                    return {results: response};
                } else {
                    return Tine.HumanResources.getFeastAndFreeDays(employeeId, year, freeTimeId, accountId);
                }
            })
            .then((response) => {
                const feastAndFreeDays = response.results;
                Tine.HumanResources.Model.FreeTime.prepareFeastAndFreeDays(feastAndFreeDays);
                
                this.feastAndFreeDaysCache[year] = feastAndFreeDays;
                return response.results ;
            })
            .finally(() => {
                return me.hideLoadMask()
            });
    },
    
    initStatusPickers: function() {
        var statusPickerDefaults = {
            fieldLabel: this.app.i18n._('Status'),
            xtype: 'widget-keyfieldcombo',
            app: 'HumanResources'
        };

        this.sicknessStatusPicker = new Tine.Tinebase.widgets.keyfield.ComboBox(
            Ext.apply({
                keyFieldName: 'sicknessStatus',
                name: 'sicknessStatus'
            }, statusPickerDefaults)
        );
        this.vacationStatusPicker = new Tine.Tinebase.widgets.keyfield.ComboBox(
            Ext.apply({
                keyFieldName: 'vacationStatus',
                name: 'vacationStatus'
            }, statusPickerDefaults)
        );
    },

    getStatusPicker: function() {
        const pickerName = _.toLower(_.get(this.record, 'data.type.id', _.get(this.record, 'data.type'))) + 'StatusPicker';
        return _.isFunction(_.get(this, pickerName + '.setValue')) ? this[pickerName] : null;
    },
    
    initEmployeePicker: function() {
        this.employeePicker = Tine.widgets.form.FieldManager.get('HumanResources', 'FreeTime', 'employee_id', Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
    },

    initTypePicker: function() {
        this.typePicker = Tine.widgets.form.FieldManager.get('HumanResources', 'FreeTime', 'type', Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
    },

    getFormItems: function() {
        this.initDatePicker();
        this.initAccountPicker();
        this.initStatusPickers();
        this.initEmployeePicker();
        this.initTypePicker();

        this.remainingDaysField = new Ext.form.NumberField({
            fieldLabel: this.app.i18n._('Remaining'),
            columnWidth: 1/3,
            name: 'remaining_vacation_days',
            readOnly: true,
            allowBlank: true,
            
            checkState: () => {
                const year = _.get(this, 'accountPicker.selectedRecord.data.year');
                const feastAndFreeDays = _.get(this.feastAndFreeDaysCache, year);
                let remaining = _.get(feastAndFreeDays, 'remainingVacation', 0);
                
                const originalDays = this.record.get('creation_time') ? +_.get(this.record, 'modified.days_count', 0) : 0;
                const currentDays = this.record.get('days_count');
                remaining = remaining + originalDays - currentDays;

                this.form.findField('remaining_vacation_days').setValue(year && feastAndFreeDays ? remaining : '');
            }
        });
        
        return {
            xtype: 'tabpanel',
            plain:true,
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._('Freetime'),
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
                        autoHeight: true,
                        title: this.app.i18n._('Days'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: 1
                            },
                            items: [
                                [this.employeePicker],
                                [this.typePicker],
                                [this.sicknessStatusPicker],
                                [this.vacationStatusPicker],
                                [this.accountPicker],
                                [this.remainingDaysField],
                                [{
                                    xtype: 'panel',
                                    cls: 'HumanResources x-form-item',
                                    style: {
                                        'float': 'right',
                                        margin: '0 5px 10px 0'
                                    },
                                    items: [{html: '<label style="display:block; margin-bottom: 5px">' + this.app.i18n._('Select Days') + '</label>'}, this.datePicker]
                                }]
                            ]
                        }]
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
            }]
        };
    }
});

Tine.HumanResources.FreeTimeEditDialog.openWindow  = function(config) {
    const recordData = _.get(config, 'record.data', _.get(config, 'record'));
    config.record = JSON.stringify(recordData);
    
    return Tine.WindowFactory.getWindow({
        width: 490,
        height: 550,
        name: 'FreeTimeEditWindow_' + _.get(recordData, 'id', 0),
        contentPanelConstructor: 'Tine.HumanResources.FreeTimeEditDialog',
        contentPanelConstructorConfig: config
    });
};
