/**
 * Bulk import/export/delete controls injected onto the tool_usertours
 * configure.php tour list page.
 *
 * @module     local_bulktourmanager/bulkactions
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {prefetchStrings} from 'core/prefetch';
import {getString} from 'core/str';
import {confirm as confirmModal} from 'core/notification';

const SELECTORS = {
    // tool_usertours\local\table\tour_list renders no id on the <table>, only
    // this class list (see its constructor's set_attribute('class', ...)).
    TABLE: 'table.admintable.generaltable',
    DELETE_LINK: '[data-action="delete"][data-id]',
    ROW_CHECKBOX: 'input[data-bulktourmanager-checkbox]',
    SELECT_ALL_CHECKBOX: 'input[data-bulktourmanager-selectall]',
    TOUR_ACTIONS_LIST: '.tour-actions ul',
};

/**
 * Read the tour id off a row's existing core-rendered delete link.
 *
 * @param {HTMLTableRowElement} row
 * @return {String|null}
 */
const getRowTourId = row => {
    const deleteLink = row.querySelector(SELECTORS.DELETE_LINK);
    return deleteLink ? deleteLink.dataset.id : null;
};

/**
 * Add a checkbox column to the tour list table: one "select all" checkbox in
 * the header, and one per-row checkbox keyed by tour id.
 *
 * @param {HTMLTableElement} table
 * @return {HTMLElement} The toolbar, so the caller can wire up its buttons.
 */
const addCheckboxColumn = table => {
    const headerRow = table.tHead ? table.tHead.rows[0] : null;
    if (headerRow) {
        const th = document.createElement('th');
        const selectAll = document.createElement('input');
        selectAll.type = 'checkbox';
        selectAll.dataset.bulktourmanagerSelectall = '1';
        th.appendChild(selectAll);
        headerRow.insertBefore(th, headerRow.firstChild);
    }

    Array.from(table.tBodies[0]?.rows || []).forEach(row => {
        const tourId = getRowTourId(row);
        if (!tourId) {
            return;
        }

        const td = document.createElement('td');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.dataset.bulktourmanagerCheckbox = tourId;
        td.appendChild(checkbox);
        row.insertBefore(td, row.firstChild);
    });
};

/**
 * Build and submit a hidden POST form carrying the selected tour ids.
 *
 * @param {String} action The URL to submit to.
 * @param {String[]} ids The selected tour ids.
 */
const submitBulkForm = (action, ids) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.style.display = 'none';

    const addHidden = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    };

    addHidden('sesskey', M.cfg.sesskey);
    ids.forEach(id => addHidden('ids[]', id));

    document.body.appendChild(form);
    form.submit();
};

/**
 * Add the "Bulk import" entry to the existing tour-actions icon list.
 *
 * @param {HTMLElement} actionsList
 */
const addBulkImportLink = actionsList => {
    const li = document.createElement('li');
    const link = document.createElement('a');
    link.href = M.cfg.wwwroot + '/local/bulktourmanager/import.php';
    link.className = 'text-body';

    getString('bulkimport', 'local_bulktourmanager').then(label => {
        link.textContent = label;
        return label;
    }).catch(() => {
        link.textContent = 'Bulk import (zip)';
    });

    li.appendChild(link);
    actionsList.appendChild(li);
};

/**
 * Build the selection toolbar (Export selected / Delete selected), disabled
 * until at least one row checkbox is checked.
 *
 * @param {HTMLTableElement} table
 * @return {Promise<HTMLElement>}
 */
const buildToolbar = table => {
    return Promise.all([
        getString('exportselected', 'local_bulktourmanager'),
        getString('deleteselected', 'local_bulktourmanager'),
    ]).then(([exportLabel, deleteLabel]) => {
        const toolbar = document.createElement('div');
        toolbar.className = 'local-bulktourmanager-toolbar mb-3';

        const exportButton = document.createElement('button');
        exportButton.type = 'button';
        exportButton.className = 'btn btn-secondary mr-2';
        exportButton.textContent = exportLabel;
        exportButton.disabled = true;
        exportButton.dataset.bulktourmanagerAction = 'export';

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn btn-outline-danger';
        deleteButton.textContent = deleteLabel;
        deleteButton.disabled = true;
        deleteButton.dataset.bulktourmanagerAction = 'delete';

        toolbar.appendChild(exportButton);
        toolbar.appendChild(deleteButton);

        table.parentNode.insertBefore(toolbar, table);

        return toolbar;
    });
};

/**
 * Set up the bulk tour manager controls for the tour list page.
 */
export const init = () => {
    prefetchStrings('local_bulktourmanager', [
        'bulkimport',
        'exportselected',
        'deleteselected',
        'confirmbulkdeletetitle',
        'confirmbulkdeletequestion',
    ]);
    prefetchStrings('core', ['yes', 'no']);

    const actionsList = document.querySelector(SELECTORS.TOUR_ACTIONS_LIST);
    if (actionsList) {
        addBulkImportLink(actionsList);
    }

    // With zero tours, core's flexible_table prints a "Nothing to display"
    // notice instead of a <table> element at all - nothing to add checkboxes
    // or a selection toolbar to in that case, but the import link above still
    // applies regardless.
    const table = document.querySelector(SELECTORS.TABLE);
    if (!table) {
        return;
    }

    addCheckboxColumn(table);

    buildToolbar(table).then(toolbar => {
        const getSelectedIds = () => Array.from(
            table.querySelectorAll(SELECTORS.ROW_CHECKBOX + ':checked')
        ).map(checkbox => checkbox.dataset.bulktourmanagerCheckbox);

        const updateToolbarState = () => {
            const anySelected = getSelectedIds().length > 0;
            toolbar.querySelectorAll('button').forEach(button => {
                button.disabled = !anySelected;
            });
        };

        table.addEventListener('change', e => {
            if (e.target.matches(SELECTORS.SELECT_ALL_CHECKBOX)) {
                table.querySelectorAll(SELECTORS.ROW_CHECKBOX).forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });
            }
            updateToolbarState();
        });

        toolbar.addEventListener('click', e => {
            const button = e.target.closest('[data-bulktourmanager-action]');
            if (!button) {
                return;
            }

            const ids = getSelectedIds();
            if (!ids.length) {
                return;
            }

            const action = button.dataset.bulktourmanagerAction;
            if (action === 'export') {
                submitBulkForm(M.cfg.wwwroot + '/local/bulktourmanager/export.php', ids);
            } else if (action === 'delete') {
                confirmModal(
                    getString('confirmbulkdeletetitle', 'local_bulktourmanager'),
                    getString('confirmbulkdeletequestion', 'local_bulktourmanager', ids.length),
                    getString('yes', 'core'),
                    getString('no', 'core'),
                    () => submitBulkForm(M.cfg.wwwroot + '/local/bulktourmanager/delete.php', ids)
                );
            }
        });

        return toolbar;
    }).catch(() => {
        // If string fetching failed the toolbar was never inserted; nothing to clean up.
    });
};
