$(document).ready(function () {

    const tableConfig = {
        styles: {
            container: "child-table-container",
            containerClasses: "pl-5 pt-3 pb-3",
            tableClasses: "table table-sm table-bordered",
            tableWidth: "90%"
        },
        columns: [
            { title: "Ditet qe ka Punuar", data: "DitetQeKaPunuar" },
            { title: "Oret qe ka Punuar", data: "OretQeKaPunuar" },
            { title: "Oret qe ka Punuar ne orar", data: "OreQeKaPunuarNeOrarPune" },
            { title: "Oret qe ka Punur jashte orarit", data: "OreQePunuarJashteOrarit" },
            { title: "Oret qe nuk ka punuar ne orar pune", data: "OretQeNukKaPunuarNeOrarPune" }
        ]
    };

    const table = $('#userTable').DataTable({
        serverSide: true,
        ajax: {
            url: "./api/v1/admin/get-hyrje-dalje.php",
            dataSrc: 'data'
        },
        columns: [{
            data: "Emri",
            className: 'details-control',
            render: function (data, type, row) {
                if (type === 'display') {
                    return '<span class="expand-username" style="cursor: pointer;">' +
                        '<i class="fa fa-plus-circle text-primary mr-2"></i>' + data +
                        '</span>';
                }
                return data;
            }
        }, {
            data: "VitetEPunes"
        }, {
            data: "DitetQeKaPunuar"
        }, {
            data: "OreTePunuar"
        }],
        columnDefs: [{
            searchable: true, targets: 0
        }, {
            searchable: false, targets: '_all'
        }],
        order: [[0, 'desc'], [1, 'desc'], [2, 'desc'], [3, 'desc']],
        ordering: true,
        responsive: true,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"top d-flex justify-content-between"' +
            '<"d-flex"l>' +
            '<"d-flex justify-content-end"f>' +
            '>' +
            'rt' +
            '<"bottom d-flex justify-content-between"ip>' +
            '<"clear">',
        pageLength: 10
    });

    function createTableStructure(type, identifier) {
        const tableId = type.toLowerCase() + '-' + identifier.replace(/\s+/g, '-');

        let tableStructure =
            '<div class="' + tableConfig.styles.container + ' ' + tableConfig.styles.containerClasses + '">' +
            '<table class="' + tableConfig.styles.tableClasses + ' ' + type.toLowerCase() + '-table" style="width: ' + tableConfig.styles.tableWidth + '">' +
            '<thead>' +
            '<tr>' +
            '<th>' + type + '</th>';

        tableConfig.columns.forEach(column => {
            tableStructure += '<th>' + column.title + '</th>';
        });

        tableStructure +=
            '</tr>' +
            '</thead>' +
            '<tbody id="' + tableId + '">' +
            '<tr>' +
            '<td colspan="' + (tableConfig.columns.length + 1) + '" class="text-center">Loading...</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>' +
            '</div>';

        return tableStructure;
    }

    const openRows = new Map();

    $('#userTable tbody').on('click', '.expand-username', function () {
        const tr = $(this).closest('tr');
        const row = table.row(tr);
        const username = row.data().Emri;

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            $(this).html('<i class="fa fa-plus-circle text-primary mr-2"></i>' + username);
            openRows.delete(username);
        } else {
            row.child(createTableStructure('Viti', username)).show();
            tr.addClass('shown');
            $(this).html('<i class="fa fa-minus-circle text-danger mr-2"></i>' + username);
            openRows.set(username, true);
            fetchYearData(username);
        }
    });

    function fetchYearData(username) {
        $.ajax({
            url: './api/v1/admin/get-years-data.php',
            type: 'GET',
            data: {
                username: username
            },
            dataType: 'json',
            success: function (response) {
                updateYearData(username, response.data);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching year data:", error);
                $('#viti-' + username.replace(/\s+/g, '-')).html(
                    '<tr><td colspan="' + (tableConfig.columns.length + 1) + '" class="text-center text-danger">Error loading data</td></tr>'
                );
            }
        });
    }

    function updateYearData(username, yearsData) {
        let yearsHtml = '';

        if (yearsData && yearsData.length > 0) {
            yearsData.forEach(function (yearData) {
                yearsHtml +=
                    '<tr class="year-row" data-username="' + username + '" data-year="' + yearData.Viti + '">' +
                    '<td class="details-control">' +
                    '<span class="expand-year" style="cursor: pointer;">' +
                    '<i class="fa fa-plus-circle text-primary mr-2"></i>' + yearData.Viti +
                    '</span>' +
                    '</td>' +
                    '<td>' + yearData.DitetQeKaPunuar + '</td>' +
                    '<td>' + yearData.OretQeKaPunuar + '</td>' +
                    '<td>' + yearData.OreQeKaPunuarNeOrarPune + '</td>' +
                    '<td>' + yearData.OreQePunuarJashteOrarit + '</td>' +
                    '<td>' + yearData.OretQeNukKaPunuarNeOrarPune + '</td>' +
                    '</tr>';
            });
        } else {
            yearsHtml = '<tr><td colspan="' + (tableConfig.columns.length + 1) + '" class="text-center">No data available</td></tr>';
        }

        $('#viti-' + username.replace(/\s+/g, '-')).html(yearsHtml);

        $('.expand-year').on('click', function () {
            const yearRow = $(this).closest('tr');
            const username = yearRow.data('username');
            const year = yearRow.data('year');
            const yearText = $(this).text().trim();

            if (yearRow.next().hasClass('month-data-row')) {
                yearRow.next().remove();
                $(this).html('<i class="fa fa-plus-circle text-primary mr-2"></i>' + year);
            } else {
                $(this).html('<i class="fa fa-minus-circle text-danger mr-2"></i>' + year);

                const monthTableHTML = createTableStructure('Muaji', username + '-' + year);
                const monthRow = $('<tr class="month-data-row"><td colspan="' + (tableConfig.columns.length + 1) + '">' + monthTableHTML + '</td></tr>');

                yearRow.after(monthRow);
                fetchMonthData(username, year);
            }
        });
    }

    function fetchMonthData(username, year) {
        $.ajax({
            url: './api/v1/admin/get-months-data.php',
            type: 'GET',
            data: {
                username: username,
                year: year
            },
            dataType: 'json',
            success: function (response) {
                updateMonthData(username, year, response.data);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching month data:", error);
                $('.month-data-row .child-table-container tbody').html(
                    '<tr><td colspan="' + (tableConfig.columns.length + 1) + '" class="text-center text-danger">Error loading data</td></tr>'
                );
            }
        });
    }

    function updateMonthData(username, year, monthsData) {
        let monthsHtml = '';

        if (monthsData && monthsData.length > 0) {
            monthsData.forEach(function (monthData) {
                monthsHtml +=
                    '<tr>' +
                    '<td>' + monthData.Muaji + '</td>' +
                    '<td>' + monthData.DitetQeKaPunuar + '</td>' +
                    '<td>' + monthData.OretQeKaPunuar + '</td>' +
                    '<td>' + monthData.OreQeKaPunuarNeOrarPune + '</td>' +
                    '<td>' + monthData.OreQePunuarJashteOrarit + '</td>' +
                    '<td>' + monthData.OretQeNukKaPunuarNeOrarPune + '</td>' +
                    '</tr>';
            });
        } else {
            monthsHtml = '<tr><td colspan="' + (tableConfig.columns.length + 1) + '" class="text-center">No data available</td></tr>';
        }

        $('#muaji-' + username.replace(/\s+/g, '-') + '-' + year).html(monthsHtml);
    }
});