<?php
require_once 'sessionCheck.php';
if ($role != 'ADMIN' && $role != 'MANAGER') {
    header('Location: profile.php');
}
$query = "SELECT * FROM users";

if ($role == 'ADMIN') {
    $stmt = $conn->prepare($query);
} elseif ($role == 'MANAGER') {
    $stmt = $conn->prepare($query . " WHERE manager_id = ?");
    $stmt->bind_param("i", $user_id);
} else {
    header('Location: profile.php');
}
$stmt->execute();
$result = $stmt->get_result();
$title = $currentPath = "All Users";

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
    .action-buttons {
        text-align: center;
        white-space: nowrap;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span>
                        <?php
                        if ($role == 'ADMIN' || $role == 'MANAGER')
                            echo 'List of all users';
                        ?>
                    </span>
                    <?php if ($role == 'ADMIN') {
                        echo '<button type="button" class="btn btn-light btn-sm add-btn" data-toggle="modal" data-target="#AddUser">
                                <i class="fa fa-plus"></i> Add User
                              </button>';
                    } ?>
                </div>
                <div class="card-body">
                    <table id="userTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Last Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <?php if ($role == 'ADMIN')
                                echo '<th class="action-buttons">Actions</th>'
                            ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            $row['id'] = encryptMessage($row['id']);
                            echo '
                                <tr>
                                    <td>' . htmlspecialchars($row['username']) . '</td>
                                    <td>' . htmlspecialchars($row['name']) . '</td>
                                    <td>' . htmlspecialchars($row['last_name']) . '</td>
                                    <td>' . htmlspecialchars($row['address']) . '</td>
                                    <td>' . htmlspecialchars($row['phone']) . '</td>
                                    <td>' . htmlspecialchars($row['email']) . '</td>
                                    ';
                            if ($role == 'ADMIN') {
                                echo '  <td class="action-buttons">
                                            <button type="button" value="' . $row['id'] . '" class="btn btn-success btn-sm update-btn" data-toggle="modal" data-target="#UpdateUser">
                                                <i class="fa fa-edit"></i> Update
                                            </button>
                                            <button type="button" value="' . $row['id'] . '" class="btn btn-danger btn-sm delete-btn" data-toggle="modal" data-target="#DeleteUser">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>';
                            } else {
                                echo '</tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="UpdateUser" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel" style="font-weight: bold">Update user</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="ibox ">
                        <form id="updateForm" method="POST">
                            <input type="hidden" id="update-user-id">
                            <div class="form-group">
                                <input id="update-username" name="username" type="text" class="form-control"
                                       placeholder="Username">
                            </div>
                            <div class="form-group">
                                <input id="update-first-name" name="firstName" type="text" class="form-control"
                                       placeholder="First Name">
                            </div>
                            <div class="form-group">
                                <input id="update-last-name" name="lastName" type="text" class="form-control"
                                       placeholder="Last Name">
                            </div>
                            <div class="form-group">
                                <input id="update-birthday" name="birthday" type="text" class="form-control"
                                       data-provide="datepicker"
                                       placeholder="Date of Birth">
                            </div>
                            <div class="form-group">
                                <input id="update-phone" name="phone" type="text" class="form-control"
                                       placeholder="Phone Number">
                            </div>
                            <div class="form-group">
                                <input id="update-email" name="email" type="email" class="form-control"
                                       placeholder="Email">
                            </div>
                            <div class="form-group">
                                <input id="update-address" name="address" type="text" class="form-control"
                                       placeholder="Address">
                            </div>
                            <div class="form-group">
                                <p><strong>Current Manager:</strong> <span id="manager"></span></p>
                                <label for="update-manager">Manager</label>
                                <select id="update-manager" name="manager_id" class="form-control">
                                    <option selected value="">None</option>
                                    <?php
                                    $managersQuery = "SELECT id ,username FROM users WHERE id in ( SELECT user_id FROM user_roles WHERE role_id = 2)";
                                    $managersStmt = $conn->prepare($managersQuery);
                                    $managersStmt->execute();
                                    $managersResult = $managersStmt->get_result();

                                    while ($manager = $managersResult->fetch_assoc()) {
                                        $manager['id'] = encryptMessage($manager['id']);
                                        echo '<option value="' . htmlspecialchars($manager['id']) . '">' . htmlspecialchars($manager['username']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <p><strong>Current Role:</strong> <span id="role"></span></p>
                                <label for="update-role">Role</label>
                                <select id="update-role" name="role_id" class="form-control">
                                    <option value="" selected>None</option>
                                    <?php
                                    $rolesQuery = "SELECT id ,name FROM roles ";
                                    $rolesStmt = $conn->prepare($rolesQuery);
                                    $rolesStmt->execute();
                                    $rolesResult = $rolesStmt->get_result();

                                    while ($roleToAssign = $rolesResult->fetch_assoc()) {
                                        $roleToAssign['id'] = encryptMessage($roleToAssign['id']);
                                        echo '<option value="' . htmlspecialchars($roleToAssign['id']) . '">' . htmlspecialchars(ucfirst(strtolower($roleToAssign['name']))) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="checkbox i-checks">
                                    <label>
                                        <input id="update-termsChecked" type="checkbox" name="termsChecked"> <i></i>
                                        Agree to
                                        the terms and policy
                                    </label>
                                </div>
                                <p id="update-errorTermsChecked"></p>
                            </div>

                            <button id="update-button" type="submit" class="btn btn-primary block full-width m-b">
                                Update
                            </button>
                        </form>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="DeleteUser" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>⚠️ Are you sure you want to delete this user? This action cannot be undone.</p>
                    <input type="hidden" id="delete-user-id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button id="confirm-delete-btn" type="button" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="AddUser" tabindex="-1" role="dialog" aria-labelledby="addModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel" style="font-weight: bold">Add User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="ibox ">
                        <form id="addForm" method="POST">
                            <div class="form-group">
                                <input id="add-username" name="username" type="text" class="form-control"
                                       placeholder="Username">
                            </div>
                            <div class="form-group">
                                <input id="add-first-name" name="firstName" type="text" class="form-control"
                                       placeholder="First Name">
                            </div>
                            <div class="form-group">
                                <input id="add-last-name" name="lastName" type="text" class="form-control"
                                       placeholder="Last Name">
                            </div>
                            <div class="form-group">
                                <input id="add-birthday" name="birthday" type="text" class="form-control"
                                       data-provide="datepicker"
                                       placeholder="Date of Birth">
                            </div>
                            <div class="form-group">
                                <input id="add-phone" name="phone" type="text" class="form-control"
                                       placeholder="Phone Number">
                            </div>
                            <div class="form-group">
                                <input id="add-email" name="email" type="email" class="form-control"
                                       placeholder="Email">
                            </div>
                            <div class="form-group">
                                <input id="add-address" name="address" type="text" class="form-control"
                                       placeholder="Address">
                            </div>
                            <div class="form-group">
                                <label for="add-manager">Manager</label>
                                <select id="add-manager" name="manager_id" class="form-control">
                                    <option disabled value="none" selected>Select a manager</option>
                                    <?php
                                    $managersQuery = "SELECT id ,username FROM users WHERE id in ( SELECT user_id FROM user_roles WHERE role_id = 2)";
                                    $managersStmt = $conn->prepare($managersQuery);
                                    $managersStmt->execute();
                                    $managersResult = $managersStmt->get_result();

                                    while ($manager = $managersResult->fetch_assoc()) {
                                        $manager['id'] = encryptMessage($manager['id']);
                                        echo '<option value="' . htmlspecialchars($manager['id']) . '">' . htmlspecialchars($manager['username']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="add-role">Role</label>
                                <select id="add-role" name="role_id" class="form-control">
                                    <option disabled value="none" selected>Select a role</option>
                                    <?php
                                    $rolesQuery = "SELECT id ,name FROM roles ";
                                    $rolesStmt = $conn->prepare($rolesQuery);
                                    $rolesStmt->execute();
                                    $rolesResult = $rolesStmt->get_result();

                                    while ($roleToAssign = $rolesResult->fetch_assoc()) {
                                        $roleToAssign['id'] = encryptMessage($roleToAssign['id']);
                                        echo '<option value="' . htmlspecialchars($roleToAssign['id']) . '">' . htmlspecialchars(ucfirst(strtolower($roleToAssign['name']))) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input id="add-password" name="password" type="password" class="form-control"
                                       placeholder="Password"
                                >
                            </div>
                            <div class="form-group">
                                <input id="add-confirm-password" name="confirmPassword" type="password"
                                       class="form-control"
                                       placeholder="Confirm Password">
                            </div>
                            <div class="form-group">
                                <div class="checkbox i-checks">
                                    <label>
                                        <input id="add-termsChecked" type="checkbox" name="termsChecked"> <i></i> Agree
                                        to
                                        the terms and policy
                                    </label>
                                </div>
                                <p id="add-errorTermsChecked"></p>
                            </div>

                            <button id="add-button" type="submit" class="btn btn-primary block full-width m-b">
                                Add
                            </button>
                        </form>

                    </div>
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

        $(function () {
            $('#add-birthday').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true
            });

            $('#update-birthday').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true
            });
        });

        $('.update-btn').on('click', function () {
            const userId = $(this).val();
            $('#update-user-id').val(userId);
            $.ajax({
                url: './api/v1/admin/get-user.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    user_id: userId,
                    action: 'get-user',
                    role: "<?= htmlspecialchars($_SESSION['role'])?>"
                },
                success: function (response) {
                    if (response.success) {
                        $('#update-username').val(response.data.username);
                        $('#update-first-name').val(response.data.name);
                        $('#update-last-name').val(response.data.last_name);
                        $('#update-birthday').val(response.data.birthday);
                        $('#update-phone').val(response.data.phone);
                        $('#update-email').val(response.data.email);
                        $('#update-address').val(response.data.address);
                        $('#update-termsChecked').prop('checked', response.data.termsChecked === "1");
                        $('#role').text(response.data.role_name);
                        if (response.data.manager_username == null) {
                            $('#manager').text('No manager assigned');
                        } else {
                            $('#manager').text(response.data.manager_username);
                        }
                        $('#UpdateUser').modal('show');
                    } else {
                        toastr.error('Failed to fetch user data.');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('Error fetching user: ' + error);
                }
            });
        });

        $('.delete-btn').on('click', function () {
            const userId = $(this).val();
            $('#delete-user-id').val(userId);
            $('#DeleteUser').modal('show');
        });

        $('#confirm-delete-btn').on('click', function () {
            const userId = $('#delete-user-id').val();
            $.ajax({
                url: './api/v1/admin/delete-user.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    user_id: userId,
                    action: 'delete-user',
                    role: "<?= htmlspecialchars($_SESSION['role'])?>"
                },
                success: function (response) {
                    if (response.success) {
                        toastr.options.onHidden = function () {
                            location.reload();
                        };
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                    $('#DeleteUser').modal('hide');
                },
                error: function (xhr, status, error) {
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : "An unexpected error occurred. Please try again.";
                    toastr.error(errorMessage);
                }
            });
        });

        <?php
        echo 'const PHP_SESSION_ROLE = "' . htmlspecialchars($_SESSION['role']) . '";';
        require './scripts/admin.js'
        ?>
        $("#add-manager").select2({
            dropdownParent: $("#AddUser .modal-content"),
            width: '100%'
        });

        $("#add-role").select2({
            dropdownParent: $("#AddUser .modal-content"),
            width: '100%'
        });

        $("#update-manager").select2({
            dropdownParent: $("#UpdateUser .modal-content"),
            width: '100%'
        });

        $("#update-role").select2({
            dropdownParent: $("#UpdateUser .modal-content"),
            width: '100%'
        });

        $('.i-checks').iCheck({
            checkboxClass: 'icheckbox_square-green',
            radioClass: 'iradio_square-green',
        });

    });
</script>
<script src="scripts/general.js"></script>
</body>
</html>