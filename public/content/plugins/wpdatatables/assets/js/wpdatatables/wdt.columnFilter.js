(function ($) {
    $.fn.columnFilter = function (options) {

        // Array of column Indexes for custom searches (All range filters)
        var customSearchIndexes = [];

        // Default properties
        var properties = {
            sPlaceHolder: "foot",
            sRangeSeparator: "~",
            aoColumns: null,
            sRangeFormat: "From {from} to {to}"
        };

        $.extend(properties, options);

        var oTable = this, columnIndex, sColumnLabel, th, tr, aoFilterCells;
        //Array of the functions that will override sSearch_ parameters
        var afnSearch_ = [];

        return this.each(function () {

            // If "Render advanced filter" is "In the header"
            if (properties.sPlaceHolder === 'head:before') {
                tr = $("tr:first", oTable.fnSettings().nTHead).detach();
                tr.appendTo($(oTable.fnSettings().nTHead));
                aoFilterCells = oTable.fnSettings().aoHeader[0];
            } else {
                aoFilterCells = oTable.fnSettings().aoFooter[0];
            }

            // Go through all table filter cells
            $(aoFilterCells).each(function (index) {

                columnIndex = index;

                var aoColumn = {
                    type: "text",
                    bRegex: false,
                    bSmart: true,
                    iMaxLength: -1,
                    iFilterLength: 0
                };

                sColumnLabel = $($(this)[0].cell).text();

                if (properties.aoColumns !== null) {
                    if (properties.aoColumns.length < columnIndex || properties.aoColumns[columnIndex] === null)
                        return;
                    aoColumn = properties.aoColumns[columnIndex];
                }

                if (typeof aoColumn.sSelector === 'undefined') {
                    th = $($(this)[0].cell);
                } else {
                    th = $(aoColumn.sSelector);
                }

                th.addClass('column-' + aoColumn.origHeader.toString().toLowerCase().replace(/\ /g, '-'));

                if (typeof aoColumn.sRangeFormat !== 'undefined')
                    sRangeFormat = aoColumn.sRangeFormat;
                else
                    sRangeFormat = properties.sRangeFormat;

                if (aoColumn !== null) {
                    switch (aoColumn.type) {
                        case 'null':
                            break;
                        case 'number':
                            wdtCreateInput(oTable, aoColumn, columnIndex, sColumnLabel, th);
                            break;
                        case 'number-range':
                            wdtCreateNumberRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes);
                            break;
                        case 'date-range':
                            wdtCreateDateRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes);
                            break;
                        case 'datetime-range':
                            wdtCreateDateTimeRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes);
                            break;
                        case 'time-range':
                            wdtCreateTimeRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes);
                            break;
                        case 'select':
                            wdtCreateSelectbox(oTable, aoColumn, columnIndex, sColumnLabel, th);
                            break;
                        case 'multiselect':
                            wdtCreateMultiSelectbox(oTable, aoColumn, columnIndex, sColumnLabel, th);
                            break;
                        case 'checkbox':
                            wdtCreateCheckbox(oTable, aoColumn, columnIndex, sColumnLabel, th);
                            break;
                        case 'text':
                        default:
                            wdtCreateInput(oTable, aoColumn, columnIndex, sColumnLabel, th);
                            break;
                    }
                }
            });

            for (var j = 0; j < customSearchIndexes.length; j++) {
                var fnSearch_ = function () {
                    var id = oTable.attr("id");
                    if ((typeof $("#" + id + "_range_from_" + customSearchIndexes[j]).val() === 'undefined')
                        || (typeof $("#" + id + "_range_to_" + customSearchIndexes[j]).val() === 'undefined')) {
                        return properties.sRangeSeparator;
                    }
                    return $("#" + id + "_range_from_" + customSearchIndexes[j]).val() + properties.sRangeSeparator + $("#" + id + "_range_to_" + customSearchIndexes[j]).val();
                };
                afnSearch_.push(fnSearch_);
            }

            if (oTable.fnSettings().oFeatures.bServerSide) {

                if (typeof oTable.fnSettings().ajax.data !== 'undefined') {
                    var currentDataMethod = oTable.fnSettings().ajax.data;
                }

                oTable.fnSettings().ajax = {
                    url: oTable.fnSettings().ajax.url,
                    type: 'POST',
                    data: function (d) {
                        if (typeof currentDataMethod !== 'undefined') {
                            currentDataMethod(d);
                        }
                        for (j = 0; j < customSearchIndexes.length; j++) {
                            var index = customSearchIndexes[j];
                            d.columns[index].search.value = afnSearch_[j]();
                        }
                        d.sRangeSeparator = properties.sRangeSeparator;
                    }
                };

            }

            wdtClearFilters();

        });

    };

})(jQuery);

var sRangeFormat = wpdatatables_frontend_strings.from + " {from} " + wpdatatables_frontend_strings.to + " {to}";
var fnOnFiltered = function () {
};

/**
 * Creates "Text" and "Number" filter
 * @param oTable
 * @param aoColumn
 * @param columnIndex
 * @param sColumnLabel
 * @param th
 */
function wdtCreateInput(oTable, aoColumn, columnIndex, sColumnLabel, th) {
    var bIsNumber = aoColumn.type === 'number';
    var serverSide = oTable.fnSettings().oFeatures.bServerSide;
    var sCSSClass = aoColumn.type === 'number' ? 'number_filter' : 'text_filter';

    sColumnLabel = sColumnLabel.replace(/(^\s*)|(\s*$)/g, "");

    var placeholder = aoColumn.filterLabel ? aoColumn.filterLabel : sColumnLabel;

    var input = jQuery('<input type="' + aoColumn.type + '" class="form-control wdt-filter-control ' + sCSSClass + '" placeholder="' + placeholder + '" />');

    th.html(input);

    if (bIsNumber)
        th.wrapInner('<span class="filter_column wdt-filter-number" data-filter_type="number" data-index="' + columnIndex + '"/>');
    else
        th.wrapInner('<span class="filter_column wdt-filter-text" data-filter_type="text" data-index="' + columnIndex + '"/>');

    input.keyup(function (e) {
        inputSearch(this.value, e.keyCode);
    });

    function inputSearch(value, keyCode) {
        if (typeof keyCode !== 'undefined' && jQuery.inArray(keyCode, [16, 37, 38, 39, 40]) !== -1) {
            return;
        }

        var tableId = oTable.attr('id');
        var search = '';

        if (aoColumn.exactFiltering) {
            search = serverSide ? value : "^" + value + "$";
            oTable.api().column(columnIndex).search(value ? search : '', true, false);
        } else {
            search = bIsNumber && !serverSide ? '^' + value : value;
            oTable.api().column(columnIndex).search(search, bIsNumber, false);
        }

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    }

    if (aoColumn.defaultValue) {
        var defaultValue = jQuery.isArray(aoColumn.defaultValue) ?
            aoColumn.defaultValue[0] : aoColumn.defaultValue;
        jQuery(input).val(defaultValue);
        oTable.fnFilter(defaultValue, columnIndex);
    }

}

/**
 * Creates "Number range" filter
 * @param oTable
 * @param aoColumn
 * @param columnIndex
 * @param sColumnLabel
 * @param th
 * @param customSearchIndexes
 */
function wdtCreateNumberRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes) {
    var tableId = oTable.attr('id');
    var fromDefaultValue = '', toDefaultValue = '', defaultValue = aoColumn.defaultValue;
    var tableDescription = jQuery.parseJSON(jQuery('#' + oTable.data('described-by')).val());
    var numberFormat = (typeof tableDescription.numberFormat !== 'undefined') ? parseInt(tableDescription.numberFormat) : 1;
    var replaceFormat = numberFormat === 1 ? /\./g : /,/g;

    if (defaultValue !== '') {
        fromDefaultValue = defaultValue[0];
        toDefaultValue = defaultValue[1];
    }

    th.html('');

    var sFromId = oTable.attr("id") + '_range_from_' + columnIndex;
    var from = jQuery('<input type="number" class="form-control wdt-filter-control number-range-filter" id="' + sFromId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.from + '" />');
    th.append(from);

    var sToId = oTable.attr("id") + '_range_to_' + columnIndex;
    var to = jQuery('<input type="number" class="form-control wdt-filter-control number-range-filter" id="' + sToId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.to + '" />');
    th.append(to);

    th.wrapInner('<span class="filter_column wdt-filter-number-range" data-filter_type="number range" data-index="' + columnIndex + '"/>');
    customSearchIndexes.push(columnIndex);

    oTable.dataTableExt.afnFiltering.push(
        function (oSettings, aData, iDataIndex) {
            if (oTable.attr("id") !== oSettings.sTableId)
                return true;

            // Try to handle missing nodes more gracefully
            if (document.getElementById(sFromId) == null)
                return true;

            var iMin = document.getElementById(sFromId).value.replace(replaceFormat, '');
            var iMax = document.getElementById(sToId).value.replace(replaceFormat, '');
            var iValue = aData[columnIndex] == "-" ? '0' : aData[columnIndex].replace(replaceFormat, '');

            if (numberFormat === 1) {
                iMin = iMin.replace(/,/g, '.');
                iMax = iMax.replace(/,/g, '.');
                iValue = iValue.replace(/,/g, '.');
            }

            if (iMin !== '') {
                iMin = iMin * 1;
            }

            if (iMax !== '') {
                iMax = iMax * 1;
            }

            iValue = iValue * 1;

            return (iMin === "" && iMax === "") ||
                (iMin === "" && iValue <= iMax) ||
                (iMin <= iValue && "" === iMax) ||
                (iMin <= iValue && iValue <= iMax);


        }
    );

    jQuery('#' + sFromId + ', #' + sToId, th).keyup(function () {
        numberRangeSearch();
    });

    if (fromDefaultValue) {
        jQuery(from).val(fromDefaultValue);
        jQuery(document).ready(function () {
            jQuery(from).keyup();
        });
    }

    if (toDefaultValue) {
        jQuery(to).val(toDefaultValue);
        jQuery(document).ready(function () {
            jQuery(to).keyup();
        });
    }

    function numberRangeSearch() {
        var iMin = document.getElementById(sFromId).value * 1;
        var iMax = document.getElementById(sToId).value * 1;
        if (iMin != 0 && iMax != 0 && iMin > iMax)
            return;

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    }

}

/**
 * Creates "Date range" filter
 * @param oTable
 * @param aoColumn
 * @param columnIndex
 * @param sColumnLabel
 * @param th
 * @param customSearchIndexes
 */
function wdtCreateDateRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes) {
    var tableId = oTable.attr('id');
    var fromDefaultValue = '', toDefaultValue = '', defaultValue = aoColumn.defaultValue;
    var dateFormat = getMomentWdtDateFormat();

    if (defaultValue !== '') {
        fromDefaultValue = defaultValue[0];
        toDefaultValue = defaultValue[1];
    }

    th.html('');
    var sFromId = oTable.attr("id") + '_range_from_' + columnIndex;
    var from = jQuery('<input type="text" class="form-control wdt-filter-control date-range-filter wdt-datepicker" id="' + sFromId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.from + '" />');

    var sToId = oTable.attr("id") + '_range_to_' + columnIndex;
    var to = jQuery('<input type="text" class="form-control wdt-filter-control date-range-filter wdt-datepicker" id="' + sToId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.to + '" />');

    th.append(from).append(to);;

    th.wrapInner('<span class="filter_column wdt-filter-date-range" data-filter_type="date range" data-index="' + columnIndex + '"/>');
    customSearchIndexes.push(columnIndex);

    oTable.dataTableExt.afnFiltering.push(
        function (oSettings, aData, iDataIndex) {
            if (oTable.attr("id") != oSettings.sTableId)
                return true;

            var dStartDate = moment(from.val(), dateFormat).toDate();
            var dEndDate = moment(to.val(), dateFormat).toDate();

            if (isNaN(dStartDate.getTime()) && isNaN(dEndDate.getTime())) {
                return true;
            }

            var dCellDate = null;

            try {
                if (aData[columnIndex] === null || aData[columnIndex] === "")
                    return false;
                dCellDate = moment(aData[columnIndex], dateFormat).toDate();
            } catch (ex) {
                return false;

            }

            if (isNaN(dCellDate.getTime()))
                return false;

            return (isNaN(dStartDate.getTime()) && dCellDate <= dEndDate) ||
                (dStartDate <= dCellDate && isNaN(dEndDate.getTime())) ||
                (dStartDate <= dCellDate && dCellDate <= dEndDate);

        }
    );

    jQuery('#' + sFromId + ', #' + sToId, th).on('dp.change', function () {

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    });

    if (fromDefaultValue) {
        jQuery(from).val(fromDefaultValue);
        jQuery(document).ready(function () {
            jQuery(from).change();
        });
    }

    if (toDefaultValue) {
        jQuery(to).val(toDefaultValue);
        jQuery(document).ready(function () {
            jQuery(to).change();
        });
    }
}

/**
 * Creates "DateTime range" filter
 * @param oTable
 * @param aoColumn
 * @param columnIndex
 * @param sColumnLabel
 * @param th
 * @param customSearchIndexes
 */
function wdtCreateDateTimeRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes) {
    var tableId = oTable.attr('id');
    var fromDefaultValue = '', toDefaultValue = '', defaultValue = aoColumn.defaultValue;
    var dateFormat = getMomentWdtDateFormat();
    var timeFormat = getMomentWdtTimeFormat();

    if (defaultValue !== '') {
        fromDefaultValue = defaultValue[0];
        toDefaultValue = defaultValue[1];
    }

    th.html('');

    var sFromId = oTable.attr("id") + '_range_from_' + columnIndex;
    var fromHTML = '<input type="text" class="form-control wdt-filter-control date-time-range-filter wdt-datetimepicker" id="' + sFromId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.from + '" />';
    var from = jQuery(fromHTML);

    var sToId = oTable.attr("id") + '_range_to_' + columnIndex;
    var toHTML = '<input type="text" class="form-control wdt-filter-control date-time-range-filter wdt-datetimepicker" id="' + sToId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.to + '" />';
    var to = jQuery(toHTML);

    th.append(from).append(to);


    th.wrapInner('<span class="filter_column wdt-filter-date-time-range" data-filter_type="datetime range" data-index="' + columnIndex + '"/>');
    customSearchIndexes.push(columnIndex);

    oTable.dataTableExt.afnFiltering.push(
        function (oSettings, aData, iDataIndex) {
            if (oTable.attr("id") != oSettings.sTableId)
                return true;

            var dStartDate = moment(from.val(), dateFormat + ' ' + timeFormat).toDate();
            var dEndDate = moment(to.val(), dateFormat + ' ' + timeFormat).toDate();

            if (isNaN(dStartDate.getTime()) && isNaN(dEndDate.getTime())) {
                return true;
            }

            var dCellDate = null;

            try {
                if (aData[columnIndex] === null || aData[columnIndex] === '')
                    return false;
                dCellDate = moment(aData[columnIndex], dateFormat + ' ' + timeFormat).toDate();
            } catch (ex) {
                return false;
            }

            if (isNaN(dCellDate.getTime()))
                return false;

            return (isNaN(dStartDate.getTime()) && dCellDate <= dEndDate) ||
                (dStartDate <= dCellDate && isNaN(dEndDate.getTime())) ||
                (dStartDate <= dCellDate && dCellDate <= dEndDate);
        }
    );

    jQuery('#' + sFromId + ', #' + sToId, th).on('dp.change', function () {

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    });

    if (fromDefaultValue) {
        jQuery(from).val(fromDefaultValue);
        jQuery(document).ready(function () {
            jQuery(from).change();
        });
    }

    if (toDefaultValue) {
        jQuery(to).val(toDefaultValue);
        jQuery(document).ready(function () {
            jQuery(to).change();
        });
    }
}

/**
 * Creates "Time range" filter
 * @param oTable
 * @param aoColumn
 * @param columnIndex
 * @param sColumnLabel
 * @param th
 * @param customSearchIndexes
 */
function wdtCreateTimeRangeInput(oTable, aoColumn, columnIndex, sColumnLabel, th, customSearchIndexes) {
    var tableId = oTable.attr('id');
    var fromDefaultValue = '', toDefaultValue = '', defaultValue = aoColumn.defaultValue;
    var timeFormat = getMomentWdtTimeFormat();

    if (defaultValue !== '') {
        fromDefaultValue = defaultValue[0];
        toDefaultValue = defaultValue[1];
    }

    th.html('');

    var sFromId = oTable.attr("id") + '_range_from_' + columnIndex;
    var fromHTML = '<input type="text" class="form-control wdt-filter-control time-range-filter wdt-timepicker" id="' + sFromId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.from + '" />';
    var from = jQuery(fromHTML);

    var sToId = oTable.attr("id") + '_range_to_' + columnIndex;
    var toHTML = '<input type="text" class="form-control wdt-filter-control time-range-filter wdt-timepicker" id="' + sToId + '" rel="' + columnIndex + '" placeholder="' + wpdatatables_frontend_strings.to + '" />';
    var to = jQuery(toHTML);

    th.append(from).append(to);

    th.wrapInner('<span class="filter_column filter_date_range" data-filter_type="time range" data-index="' + columnIndex + '"/>');
    customSearchIndexes.push(columnIndex);

    oTable.dataTableExt.afnFiltering.push(
        function (oSettings, aData, iDataIndex) {
            if (oTable.attr("id") != oSettings.sTableId)
                return true;

            var dStartTime = moment(from.val(), timeFormat).toDate();
            var dEndTime = moment(to.val(), timeFormat).toDate();

            if (isNaN(dStartTime.getTime()) && isNaN(dEndTime.getTime())) {
                return true;
            }

            var dCellTime = null;

            try {
                if (aData[columnIndex] === null || aData[columnIndex] === '')
                    return false;
                dCellTime = moment(aData[columnIndex], timeFormat).toDate();
            } catch (ex) {
                return false;
            }

            if (isNaN(dCellTime.getTime()))
                return false;

            return (isNaN(dStartTime.getTime()) && dCellTime <= dEndTime) ||
                (dStartTime <= dCellTime && isNaN(dEndTime.getTime())) ||
                (dStartTime <= dCellTime && dCellTime <= dEndTime);
        }
    );

    jQuery('#' + sFromId + ', #' + sToId, th).on('dp.change', function () {

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    });

    if (fromDefaultValue) {
        jQuery(from).val(fromDefaultValue);
        jQuery(document).ready(function () {
            jQuery(from).change();
        });
    }

    if (toDefaultValue) {
        jQuery(to).val(toDefaultValue);
        jQuery(document).ready(function () {
            jQuery(to).change();
        });
    }
}

/**
 * Creates "Selectbox" and "Multiselectbox" filters
 * @param oTable
 * @param aoColumn
 * @param columnIndex
 * @param sColumnLabel
 * @param th
 */
function wdtCreateSelectbox(oTable, aoColumn, columnIndex, sColumnLabel, th) {
    var tableId = oTable.attr('id'), serverSide = oTable.fnSettings().oFeatures.bServerSide, selected;
    var dateFormat = getMomentWdtDateFormat();
    var timeFormat = getMomentWdtTimeFormat();

    if (aoColumn.values === null)
        aoColumn.values = getColumnDistinctValues(tableId, columnIndex, false);

    if (aoColumn.possibleValuesAddEmpty === true && !serverSide) {
        aoColumn.values.unshift('possibleValuesAddEmpty');
    }

    if (aoColumn.defaultValue !== '') {
        if (jQuery.isArray(aoColumn.defaultValue)) {
            aoColumn.defaultValue = aoColumn.defaultValue[0];
        }
    }

    var selectTitle = aoColumn.filterLabel ? aoColumn.filterLabel : '';

    var select = '<select class="wdt-select-filter wdt-filter-control selectpicker" title="' + selectTitle + '" data-index="' + columnIndex + '"><option value="">' + ' ' + '</option>';

    var iLen = aoColumn.values.length;

    for (var j = 0; j < iLen; j++) {
        if (typeof (aoColumn.values[j]) !== 'object') {
            selected = '';

            if ((aoColumn.defaultValue !== '') && (encodeURI(aoColumn.values[j]) == encodeURI(aoColumn.defaultValue))) {
                selected = 'selected="selected" ';
            }

            var optionLabel = aoColumn.values[j];
            if (aoColumn.values[j] === 'possibleValuesAddEmpty') {
                optionLabel = ' ';
            }

            // TODO Solve formatting dates, times and datetimes in the separate method for all filters
            if (serverSide) {
                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'date') {
                    optionLabel = moment(optionLabel, moment.ISO_8601).format(dateFormat);
                }

                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'time') {
                    optionLabel = moment(optionLabel, 'hh:mm:ss').format(timeFormat.replace('h', 'hh'));
                }

                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'datetime') {
                    optionLabel = moment(optionLabel, moment.ISO_8601).format(dateFormat + ' ' + timeFormat.replace('h', 'hh'));
                }

            }

            select += '<option ' + selected + ' value="' + encodeURI(aoColumn.values[j]) + '">' + optionLabel + '</option>';
        } else {
            selected = '';

            if ((aoColumn.defaultValue !== '') && (aoColumn.values[j].value == aoColumn.defaultValue)) {
                selected = 'selected="selected" ';
            }
            select += '<option ' + selected + 'value="' + encodeURI(aoColumn.values[j].value) + '">' + aoColumn.values[j].label + '</option>';
        }
    }

    select = jQuery(select + '</select>');
    th.html(select);
    th.wrapInner('<span class="filter_column filter_select" data-filter_type="selectbox" data-index="' + columnIndex + '"/>');

    select.on('change.selectChange', function () {
        selectboxSearch.call(jQuery(this));
    });

    if (aoColumn.defaultValue) {
        oTable.fnFilter(aoColumn.defaultValue, columnIndex);
    }

    jQuery('.selectpicker[data-index=' + columnIndex + ']').selectpicker('refresh');

    function selectboxSearch() {
        var search = '';

        if (jQuery(this).val() === 'possibleValuesAddEmpty' && !serverSide) {
            oTable.api().column(columnIndex).search('^$', true, false);
        } else {
            if (aoColumn.exactFiltering) {
                search = serverSide ? decodeURIComponent(jQuery(this).val()) : '^' + decodeURIComponent(jQuery(this).val()) + '$';
                oTable.api().column(columnIndex).search(jQuery(this).val() ? search : '', true, false);
            } else {
                oTable.api().column(columnIndex).search(decodeURIComponent(jQuery(this).val()), true, false);
            }
        }

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    }
}

/**
 * Creates "Multiselectbox" filter
 * @param oTable
 * @param aoColumn
 * @param columnIndex
 * @param sColumnLabel
 * @param th
 */
function wdtCreateMultiSelectbox(oTable, aoColumn, columnIndex, sColumnLabel, th) {
    var tableId = oTable.attr('id'), serverSide = oTable.fnSettings().oFeatures.bServerSide, selected;
    var dateFormat = getMomentWdtDateFormat();
    var timeFormat = getMomentWdtTimeFormat();

    if (!jQuery.isArray(aoColumn.defaultValue)) {
        aoColumn.defaultValue = [aoColumn.defaultValue];
    }

    if (aoColumn.values === null)
        aoColumn.values = getColumnDistinctValues(tableId, columnIndex, false);

    var selectTitle = aoColumn.filterLabel ? aoColumn.filterLabel : '';
    var select = '<select class="wdt-multiselect-filter wdt-filter-control selectpicker" title="' + selectTitle + '" data-index="' + columnIndex + '" multiple>';
    var iLen = aoColumn.values.length;

    for (var j = 0; j < iLen; j++) {

        selected = jQuery.inArray(aoColumn.values[j].toString(), aoColumn.defaultValue) !== -1 ? selected = 'selected="selected" ' : '';

        if (typeof (aoColumn.values[j]) !== 'object') {
            var optionLabel = aoColumn.values[j];
            // TODO Solve formatting dates, times and datetimes in the separate method for all filters
            if (serverSide) {
                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'date') {
                    optionLabel = moment(optionLabel, moment.ISO_8601).format(dateFormat);
                }

                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'time') {
                    optionLabel = moment(optionLabel, 'hh:mm:ss').format(timeFormat.replace('h', 'hh'));
                }

                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'datetime') {
                    optionLabel = moment(optionLabel, moment.ISO_8601).format(dateFormat + ' ' + timeFormat.replace('h', 'hh'));
                }

            }
            if (aoColumn.values[j] === 'possibleValuesAddEmpty') {
                optionLabel = ' ';
            }
            select += '<option ' + selected + ' value="' + encodeURI(aoColumn.values[j]) + '">' + optionLabel + '</option>';
        } else {
            select += '<option ' + selected + 'value="' + encodeURI(aoColumn.values[j].value) + '">' + aoColumn.values[j].label + '</option>';
        }
    }

    select = jQuery(select + '</select>');
    th.html(select);
    th.wrapInner('<span class="filter_column filter_select" data-filter_type="multiselectbox" data-index="' + columnIndex + '" />');

    select.change(function () {
        multiSelectboxSearch.call(jQuery(this));
    });

    if (aoColumn.defaultValue[0]) {
        var search = '';
        for (var i = 0; i < aoColumn.defaultValue.length; i++) {
            search += buildSearchStringForMultiFilters(i, aoColumn.defaultValue[i], aoColumn.defaultValue.length, aoColumn.exactFiltering);
        }
        oTable.fnFilter(search, columnIndex, true, false);
        fnOnFiltered();
    }

    jQuery('.selectpicker[data-index=' + columnIndex + ']').selectpicker('refresh');

    function multiSelectboxSearch() {
        var search = '', selectedOptions;
        selectedOptions = jQuery(this).selectpicker('val');

        if (selectedOptions !== null) {
            var selectedOptionsLength = selectedOptions.length;
        }

        jQuery.each(selectedOptions, function (index, value) {
            search += buildSearchStringForMultiFilters(index, value, selectedOptionsLength, aoColumn.exactFiltering);
        });

        oTable.api().column(columnIndex).search(search, true, false);

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    }

}

/**
 * Creates "Checkbox" filter
 * @param oTable
 * @param aoColumn - filter settings that will be applied on the column
 * @param columnIndex - column index
 * @param sColumnLabel
 * @param th
 */
function wdtCreateCheckbox(oTable, aoColumn, columnIndex, sColumnLabel, th) {
    var tableId = oTable.attr('id'), serverSide = oTable.fnSettings().oFeatures.bServerSide;
    var dateFormat = getMomentWdtDateFormat();
    var timeFormat = getMomentWdtTimeFormat();

    if (!jQuery.isArray(aoColumn.defaultValue)) {
        aoColumn.defaultValue = [aoColumn.defaultValue];
    }

    if (aoColumn.values === null)
        aoColumn.values = getColumnDistinctValues(tableId, columnIndex, false);

    var r = '', j, iLen = aoColumn.values.length, dialogRender = true;

    if (typeof aoColumn.sSelector !== 'undefined') {
        dialogRender = aoColumn.checkboxesInModal;
    }

    var labelBtn = aoColumn.filterLabel ? aoColumn.filterLabel : sColumnLabel;
    var checkboxesDivId = oTable.attr('id') + '-checkbox-' + columnIndex;

    if (dialogRender) {
        var buttonId = "checkbox-button-" + checkboxesDivId;
        r += '<button id="' + buttonId + '" class="wdt-checkbox-filter btn" > ' + labelBtn + '</button>'; // Filter button which opens the dialog
    }

    r += '<div id="' + checkboxesDivId + '">';

    for (j = 0; j < iLen; j++) {
        if (aoColumn.values[j] !== null) {
            var value = typeof aoColumn.values[j] !== 'object' ? aoColumn.values[j] : aoColumn.values[j].value;
            var label = typeof aoColumn.values[j] !== 'object' ? aoColumn.values[j] : aoColumn.values[j].label;
            var checked = jQuery.inArray(value.toString(), aoColumn.defaultValue) !== -1 ? 'checked="checked" ' : '';

            // TODO Solve formatting dates, times and datetimes in the separate method for all filters
            if (serverSide) {

                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'date') {
                    label = moment(label, moment.ISO_8601).format(dateFormat);
                }

                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'time') {
                    label = moment(label, 'hh:mm:ss').format(timeFormat);
                }

                if (oTable.fnSettings().aoColumns[columnIndex].wdtType === 'datetime') {
                    label = moment(label, moment.ISO_8601).format(dateFormat + ' ' + timeFormat.replace('h', 'hh'));
                }

            }

            r += '<div class="wdt_checkbox_option checkbox">' +
                '<label>' +
                '<input type="checkbox" class="wdt-checkbox-filter wdt-filter-control" value="' + _.escape(value) + '" ' + checked + '>' +
                '<i class="input-helper"></i>' + label +
                '</label>' +
                '</div>';
        }
    }

    jQuery(th).off('change.checkboxChange').on('change.checkboxChange', '#' + checkboxesDivId + ' input.wdt-checkbox-filter', function () {
        checkboxSearch.call(jQuery(this), columnIndex, checkboxesDivId);
    });

    th.html(r);
    th.wrapInner('<span class="filter_column filter_checkbox" data-filter_type="checkbox" data-index="' + columnIndex + '" />');

    if (aoColumn.defaultValue[0]) {
        var search = '';
        for (var i = 0; i < aoColumn.defaultValue.length; i++) {
            search += buildSearchStringForMultiFilters(i, aoColumn.defaultValue[i], aoColumn.defaultValue.length, aoColumn.exactFiltering);
        }
        oTable.fnFilter(search, columnIndex, true, false);
        fnOnFiltered();
    }

    if (dialogRender) {
        var dlg = jQuery('#' + checkboxesDivId).wrap('<div class="wdt-checkbox-modal-wrap ' + checkboxesDivId + '" />').hide();
        var $modal = jQuery('#wdt-frontend-modal');

        $modal.on('click', 'button.close', function (e) {
            $modal.fadeOut(300, function(){
                jQuery(this).find('.modal-body').html('')
            });
        });

        $modal.on('keydown', function (e) {
            if ( e.keyCode === 27 ) {
                $modal.fadeOut(300, function () {
                    jQuery(this).find('.modal-body').html('')
                });
            }
        });

        jQuery('#' + buttonId).on('click', function (e) {
            e.preventDefault();
            jQuery('#wdt-frontend-modal .modal-title').html(labelBtn);
            jQuery('#wdt-frontend-modal .modal-body').append(dlg.show());
            jQuery('#wdt-frontend-modal .modal-footer').html('<button class="btn btn-danger btn-icon-text waves-effect" id="wdt-checkbox-filter-reset" href="#">Reset</button><button class="btn btn-success btn-icon-text waves-effect" id="wdt-checkbox-filter-close" href="#"><i class="zmdi zmdi-check"></i>OK</button>');

            jQuery('input.wdt-checkbox-filter').off('change').on('change', function () {
                checkboxSearch.call(jQuery(this), columnIndex, checkboxesDivId);
            });

            if (typeof wpDataTables[tableId].onRenderCheckboxFilterModal !== 'undefined') {
                for (var i in wpDataTables[tableId].onRenderCheckboxFilterModal) {
                    wpDataTables[tableId].onRenderCheckboxFilterModal[i]($modal, columnIndex);
                }
            }
            $modal.attr('data-current-checkbox-dialog',dlg.attr('id'));
            $modal.modal('show');
        });

        $modal.on('shown.bs.modal', function(){
            jQuery(this).off('click', '#wdt-checkbox-filter-close').on('click', '#wdt-checkbox-filter-close', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $modal.modal('hide');
                if (jQuery('#' + $modal.attr('data-current-checkbox-dialog')).length){
                    jQuery('.wdt-checkbox-modal-wrap.' + $modal.attr('data-current-checkbox-dialog')).html(jQuery('#' + $modal.attr('data-current-checkbox-dialog'))).hide();
                }
                $modal.find('.modal-body').html('');
            });

            jQuery(this).off('click', '#wdt-checkbox-filter-reset').on('click.resetCheckboxFilter', '#wdt-checkbox-filter-reset', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (jQuery('#' + $modal.attr('data-current-checkbox-dialog')).length) {
                    jQuery('#' + $modal.attr('data-current-checkbox-dialog')).find(jQuery('input.wdt-checkbox-filter:checkbox:checked')).each(function () {
                        jQuery(this).prop('checked', false).change();
                    });
                }
                oTable.fnFilter('', $modal.attr('data-current-checkbox-dialog').substr($modal.attr('data-current-checkbox-dialog').length - 1), true, false);

                fnOnFiltered();
            });
        });
    }

    function checkboxSearch(columnIndex, checkboxesDivId) {
        var search = '', checkedInputs;
        checkedInputs = jQuery(this).closest('#' + checkboxesDivId).find('input:checkbox:checked');
        var checkedInputsLength = checkedInputs.length;

        jQuery.each(checkedInputs, function (index) {
            search += buildSearchStringForMultiFilters(index, jQuery(this).val(), checkedInputsLength, aoColumn.exactFiltering);
        });

        oTable.api().column(columnIndex).search(search, true, false);

        if (typeof wpDataTables[tableId].drawTable === 'undefined' || wpDataTables[tableId].drawTable === true) {
            oTable.api().draw();
        }

        fnOnFiltered();
    }

}


/**
 * Function that retrieves column distinct data for non-server-side wpDataTables
 * @param tableId - ID of the table (table_1, table_2...)
 * @param columnIndex - Index of the column
 * @param applySearch - Return values only from the filtered rows
 */
function getColumnDistinctValues(tableId, columnIndex, applySearch) {
    applySearch = applySearch ? 'applied' : 'none';

    return wpDataTables[tableId]
        .api()
        .column(columnIndex, {search: applySearch})
        .data()
        .unique()
        .toArray()
        .filter(Boolean)
        .sort();
}

/**
 * Build search string and filter the table for "Multiselectbox" and "Checkbox" filters
 * @param index
 * @param value
 * @param valuesLength
 * @param exactFiltering
 */
function buildSearchStringForMultiFilters(index, value, valuesLength, exactFiltering) {
    var search = '', or = '|';

    if ((index === 0 && valuesLength === 1) || (index !== 0 && index === valuesLength - 1)) {
        or = '';
    }

    search = search.replace(/^\s+|\s+$/g, '');

    if (exactFiltering) {
        search = search + '^' + value.replace(/\+/g, '\\+') + '$' + or;
    } else {
        search = search + value.replace(/\+/g, '\\+') + or;
    }

    return decodeURIComponent(search);
}

/**
 * Function that attach event on clear filters button
 */
function wdtClearFilters() {
    jQuery('.wdt-clear-filters-button, .wdt-clear-filters-widget-button').click(function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        e.preventDefault();

        var button = jQuery(e.target);
        if (button.is('.wdt-clear-filters-widget-button')) {
            jQuery('.filter_column input:not([type="checkbox"])').val('');
            jQuery('.filter_column select').selectpicker('val', '');
            jQuery('.filter_column input:checkbox').removeAttr('checked');

            for (var i in wpDataTables) {
                wpDataTables[i].api().columns().search('').draw();
            }

            jQuery('.filter_column select').find('.filter_column').eq(0).change();
        } else {
            var wpDataTableSelecter = jQuery(this).closest('.wpDataTables');

            wpDataTableSelecter.find('.filter_column input:not([type="checkbox"])').val('');
            wpDataTableSelecter.find('.filter_column select').selectpicker('val', '');
            wpDataTableSelecter.find('.filter_column input:checkbox').removeAttr('checked');

            var tableId = '';
            if (jQuery(this).parent().is('#wdt-clear-filters-button-block')) {
                tableId = jQuery(this).data('table_id');
            } else {
                tableId = jQuery(this).closest('.wpDataTablesWrapper').find('table.wpDataTable').prop('id');
            }

            wpDataTables[tableId].api().columns().search('').draw();

            wpDataTableSelecter.find('.wdt-filter-control').eq(0).change();
        }
    });
}
