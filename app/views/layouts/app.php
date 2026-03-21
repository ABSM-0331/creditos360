<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>

<body>
    <?php
    $flashSuccess = $_SESSION['success'] ?? null;
    $flashError = $_SESSION['error'] ?? null;
    unset($_SESSION['success'], $_SESSION['error']);
    ?>

    <div class="app-container">
        <?php $currentPath = $_SERVER['REQUEST_URI']; ?>
        <?php require  __DIR__ . '/sidebar.php' ?>
        <main class="main-content">
            <?php require __DIR__ . '/header.php' ?>

            <!-- Content Sections -->
            <div class="content-wrapper">
                <?php require $view; ?>
            </div>
        </main>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($flashSuccess): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: <?= json_encode($flashSuccess) ?>,
                confirmButtonText: 'Aceptar'
            });
        </script>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: <?= json_encode($flashError) ?>,
                confirmButtonText: 'Aceptar'
            });
        </script>
    <?php endif; ?>
    <script>
        if (document.referrer.includes('')) {
            window.history.replaceState(null, null, window.location.href);
        }
        window.history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function() {
            window.history.pushState(null, null, window.location.href);
        });
    </script>
</body>

</html>