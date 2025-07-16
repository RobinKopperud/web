<nav class="navbar navbar-expand-lg navbar-light bg-gray shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-pink" href="index.php">Borettslag Parkering</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Hjem</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="parking.php">Parkeringsplasser</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Min side</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-pink" href="logout.php">Logg ut</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Logg inn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-pink" href="register.php">Registrer</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>