<?php
require_once 'sessionCheck.php';

$query = "SELECT * FROM users";

if ($role == 'ADMIN') {
    $stmt = $conn->prepare($query . " WHERE id IN (SELECT user_id FROM user_roles WHERE role_id = 2)");
} elseif ($role == 'MANAGER') {
    $stmt = $conn->prepare($query . " WHERE manager_id = ?");
    $stmt->bind_param("i", $user_id);
} else {
    header('Location: profile.php');
    exit;
}
$stmt->execute();
$result = $stmt->get_result();
$title = $currentPath = "Manager Report";

$styles = '
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<style>
    .table-container {
        background-color: #f8f9fa;
        border-radius: 5px;
        margin: 10px 0;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: #333;
    }
    .card-header {
        font-weight: bold;
    }
    .btn {
        margin: 2px;
    }
</style>';

require 'static/header.php';
?>

<body>
<div id="wrapper">
    <?php
    require 'static/navbar.php';
    ?>

    <div id="page-wrapper" class="gray-bg">
        <?php require './static/other.php'; ?>
        <div class="container mt-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <?php
                    if ($role == 'ADMIN')
                        echo 'List of all managers';
                    elseif ($role == 'MANAGER')
                        echo 'List of my users';
                    ?>
                </div>
                <div class="card-body">
                    <table id="userTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Last Name</th>
                            <?php if ($role == 'ADMIN') {
                                echo '
                                <th>Number of users</th>                                    
                                <th>Age Report</th>
                                ';
                            } else {
                                echo '
                                <th>Age Group</th>
                                ';
                            } ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            echo '
                                <tr>
                                    <td>' . htmlspecialchars($row['username']) . '</td>
                                    <td>' . htmlspecialchars($row['name']) . '</td>
                                    <td>' . htmlspecialchars($row['last_name']) . '</td>';

                            if ($role == 'ADMIN') {
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE manager_id = ?");
                                $stmt->bind_param("i", $row['id']);
                                $stmt->execute();
                                $stmt->bind_result($count);
                                $stmt->fetch();
                                $stmt->close();
                                $row['id'] = encryptMessage($row['id']);

                                echo '<td>' . $count . '</td>
                                      <td>
                                            <button type="button" value="' . $row['id'] . '" class="btn btn-success show-btn" data-toggle="modal" data-target="#AgeRaport">Show Report</button>
                                        </td>
                                    </tr>';
                            } else {
                                $stmt = $conn->prepare("SELECT 
                                                        CASE
                                                            WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                                                            WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                                                            WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                                                            ELSE '46+'
                                                        END AS age_group
                                                    FROM users
                                                    WHERE id = ?
                                                   ");
                                $stmt->bind_param("i", $row['id']);
                                $stmt->execute();
                                $stmt->bind_result($age_group);
                                $stmt->fetch();
                                $stmt->close();
                                echo '<td>' . htmlspecialchars($age_group) . '</td>
                                    </tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="AgeRaport" tabindex="-1" role="dialog" aria-labelledby="ageReportLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ageReportLabel" style="font-weight: bold">Age Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Age Group</th>
                            <th>Number of Users</th>
                        </tr>
                        </thead>
                        <tbody id="age-report-body">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php require_once './static/logout.php' ?>
</div>

<?php require 'static/scripts.html'; ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function () {
        $('#userTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: true,
            pageLength: 10,
            responsive: true,
            columnDefs: [
                {
                    orderable: false,
                    targets: -1
                }
            ],
            dom: '<"top d-flex justify-content-between"<"d-none"l><"d-flex justify-content-end"f>>rt<"bottom d-flex justify-content-between"ip><"clear">',
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        $('.show-btn').on('click', function () {
            const manager_id = $(this).val();
            $.ajax({
                url: './api/v1/admin/get-age-report.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    manager_id: manager_id,
                    action: 'get-age-report',
                    role: '<?php echo $_SESSION['role']; ?>',
                },
                success: function (response) {
                    if (response.success) {
                        const tbody = $('#age-report-body');
                        tbody.empty();
                        response.data.forEach(row => {
                            tbody.append(`
                                <tr>
                                    <td>${row.age_group}</td>
                                    <td>${row.user_count}</td>
                                </tr>
                            `);
                        });
                        $('#AgeRaport').modal('show');
                    } else {
                        toastr.error('Failed to fetch age report.');
                    }
                },
                error: function () {
                    toastr.error('Error fetching age report.');
                }
            });
        });
    });
</script>
<script src="scripts/general.js"></script>
</body>
</html>