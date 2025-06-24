<?php
require_once './sessionCheck.php';
global $conn;
$title = $currentPath = "Hyrje/Dalje";
$styles = '
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<style>
    .child-table-container {
        background-color: #f8f9fa;
        border-radius: 5px;
        margin: 10px 0;
    }
    .year-table, .month-table {
        background-color: white;
    }
    .details-control {
        cursor: pointer;
    }
    .month-data-row {
        background-color: #e9ecef;
    }
    tr.shown {
        background-color: #e9f5ff;
    }
</style>';
require_once 'static/header.php';
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
                <div class="card-header bg-primary text-white">User Work Data</div>
                <div class="card-body">
                    <table id="userTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>Vitet ne Pune</th>
                            <th>Ditet ne Pune</th>
                            <th>Oret Gjithesej</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php require_once './static/logout.php' ?>
</div>
<?php require 'static/scripts.html'; ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="scripts/hyrje-dalje.js"></script>
<script src="scripts/general.js"></script>
</body>
</html>