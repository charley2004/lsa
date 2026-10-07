/* Results table → mobile cards helper.
   1) Copies each <th> text into data-label on its column's <td>s (CSS shows it on mobile).
   2) Restores ARIA table roles, because display:block/grid on a <table> strips
      table semantics in some browsers (screen readers lose "row 3 of 12").
   Include once, after the table: <script src="lsa-results-table.js" defer></script> */
document.querySelectorAll('.results-table-modern').forEach((table) => {
    const headers = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());

    table.setAttribute('role', 'table');
    table.querySelectorAll('thead, tbody').forEach((g) => g.setAttribute('role', 'rowgroup'));
    table.querySelectorAll('tr').forEach((tr) => tr.setAttribute('role', 'row'));
    table.querySelectorAll('th').forEach((th) => th.setAttribute('role', 'columnheader'));

    table.querySelectorAll('tbody tr').forEach((row) => {
        [...row.cells].forEach((cell, i) => {
            cell.setAttribute('role', 'cell');
            if (!cell.dataset.label) cell.dataset.label = headers[i] || '';
        });
    });
});