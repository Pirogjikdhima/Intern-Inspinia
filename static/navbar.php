<?php include_once("css/navbar.html")?>
<nav class="navbar-default navbar-static-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav metismenu" id="side-menu">
            <li class="nav-header">
                <div class="dropdown profile-element">
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <span class="block m-t-xs font-bold"><?php echo htmlspecialchars($username) ?></span>
                        <span class="text-muted text-xs block"><?php echo htmlspecialchars(ucfirst(strtolower($role))) ?>
                            <b class="caret"></b>
                        </span>
                    </a>
                    <ul class="dropdown-menu animated fadeInRight m-t-xs">
                        <li>
                            <a class="dropdown-item" href="./profile.php">
                                <i class="fa fa-user"></i> Profile
                            </a>
                        </li>
                        <li>
                            <a href="./password.php" class="dropdown-item">
                                <i class="fa fa-key"></i> Change Password
                            </a>
                        </li>
                        <li class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item logout-btn" href="#">
                                <i class="fa fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="logo-element">
                    IN+
                </div>
            </li>

            <?php if ($role == "ADMIN" || $role == "MANAGER") { ?>
                <li>
                    <a href="#">
                        <i class="fa fa-users"></i>
                        <span class="nav-label">User Management</span>
                        <span class="fa arrow"></span>
                    </a>
                    <ul class="nav nav-second-level collapse">
                        <li><a href="./report.php">All Users Report</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#">
                        <i class="fa fa-clipboard-list"></i>
                        <span class="nav-label">Manager Reports</span>
                        <span class="fa arrow"></span>
                    </a>
                    <ul class="nav nav-second-level collapse">
                        <li><a href="./manager-report.php">Manager Report</a></li>
                    </ul>
                </li>
            <?php } ?>

            <?php if ($role == "ADMIN") { ?>
                <li>
                    <a href="#">
                        <i class="fa fa-clock"></i>
                        <span class="nav-label">Work Tracking</span>
                        <span class="fa arrow"></span>
                    </a>
                    <ul class="nav nav-second-level collapse">
                        <li><a href="./hyrje-dalje.php">Work Hours Report</a></li>
                    </ul>
                </li>
            <?php } ?>



            <li>
                <a href="#">
                    <i class="fa fa-cogs"></i>
                    <span class="nav-label">Settings</span>
                    <span class="fa arrow"></span>
                </a>
                <ul class="nav nav-second-level collapse">
                    <li><a href="./profile.php">My Profile</a></li>
                    <li><a href="./password.php">Change Password</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<script>
    $(document).ready(function() {
        const currentPath = window.location.pathname;
        const fileName = currentPath.split('/').pop();

        $('#side-menu li').removeClass('active');
        $('#side-menu li a').removeClass('active');

        $('#side-menu a').each(function() {
            const href = $(this).attr('href');
            if (href && href.includes(fileName)) {
                $(this).addClass('active');
                $(this).closest('li').addClass('active');

                if ($(this).closest('.nav-second-level').length) {
                    $(this).closest('.nav-second-level').prev('a').attr('aria-expanded', 'true');
                    $(this).closest('li').parent().addClass('in');
                    $(this).closest('li').parent().prev().addClass('active');
                }
            }
        });

        $('#side-menu').metisMenu();
    });
</script>