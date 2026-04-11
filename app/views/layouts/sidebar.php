<?php
$empresaSidebar = [
    'nombre_empresa' => 'GestionPro',
    'logo_ruta' => null,
];

if (class_exists('EmpresaService')) {
    try {
        $empresaSidebar = array_merge($empresaSidebar, (new EmpresaService())->obtenerDatos());
    } catch (Throwable $e) {
        // Conservar valores por defecto cuando falle la consulta.
    }
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <?php if (!empty($empresaSidebar['logo_ruta'])): ?>
                <img src="/<?= htmlspecialchars($empresaSidebar['logo_ruta']) ?>" alt="Logo empresa" class="sidebar-logo-image">
            <?php else: ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($empresaSidebar['nombre_empresa'] ?: 'GestionPro') ?></span>
        </div>
    </div>

    <?php $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null; ?>
    <?php
    $dashboardHref = '/proyecto-residencia/public/dashboard';
    $isAvanceCobranza = str_contains($currentPath, '/dashboard/avance-cobranza');
    $isDashboardAdmin = str_contains($currentPath, '/dashboard')
        && !$isAvanceCobranza
        && !str_contains($currentPath, '/dashboard-cliente')
        && !str_contains($currentPath, '/dashboard-cobratario');
    $isDashboardCliente = str_contains($currentPath, '/dashboard-cliente');
    $isDashboardCobratario = str_contains($currentPath, '/dashboard-cobratario');
    if ($rol === 2) {
        $dashboardHref = '/proyecto-residencia/public/dashboard-cliente';
    } elseif ($rol === 3) {
        $dashboardHref = '/proyecto-residencia/public/dashboard-cobratario#resumenCobratario';
    }
    ?>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?= $dashboardHref ?>" class="nav-link <?= ($rol === 1 && $isDashboardAdmin) || ($rol === 2 && $isDashboardCliente) ? 'active' : '' ?>" <?= $rol === 3 ? 'data-cobratario-nav="resumen"' : '' ?>>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if ($rol === 3): ?>
                <li>
                    <a href="/proyecto-residencia/public/dashboard-cobratario#tablaCreditosCobratario" class="nav-link" data-cobratario-nav="creditos">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="16" x2="9" y2="16.01"></line>
                            <line x1="13" y1="16" x2="13" y2="16.01"></line>
                        </svg>
                        <span>Mis Créditos</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($rol === 1): ?>
                <li>
                    <a href="/proyecto-residencia/public/dashboard/avance-cobranza" class="nav-link <?= $isAvanceCobranza ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        <span>Avance Cobranza</span>
                    </a>
                </li>
                <li>
                    <a href="/proyecto-residencia/public/dashboard-cobratario#tablaCreditosCobratario" class="nav-link <?= $isDashboardCobratario ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="16" x2="9" y2="16.01"></line>
                            <line x1="13" y1="16" x2="13" y2="16.01"></line>
                        </svg>
                        <span>Cobranza</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($rol !== 2 && $rol !== 3): ?>
                <?php $catalogosActive = str_contains($currentPath, '/clientes') || str_contains($currentPath, '/cobratarios') || str_contains($currentPath, '/usuarios'); ?>
                <li>
                    <details class="nav-details" <?= $catalogosActive ? 'open' : '' ?>>
                        <summary class="nav-link <?= $catalogosActive ? 'active' : '' ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            <span>Catálogos</span>
                            <svg class="nav-chevron" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 640 640" fill="currentColor" stroke-width="2" fill="none">
                                <path d="M297.4 470.6C309.9 483.1 330.2 483.1 342.7 470.6L534.7 278.6C547.2 266.1 547.2 245.8 534.7 233.3C522.2 220.8 501.9 220.8 489.4 233.3L320 402.7L150.6 233.4C138.1 220.9 117.8 220.9 105.3 233.4C92.8 245.9 92.8 266.2 105.3 278.7L297.3 470.7z" />
                            </svg>
                        </summary>
                        <ul style="margin-left: 18px;">
                            <li>
                                <a href="/proyecto-residencia/public/clientes" class="nav-link <?= str_contains($currentPath, '/clientes') ? 'active' : '' ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    <span>Clientes</span>
                                </a>
                            </li>
                            <li>
                                <a href="/proyecto-residencia/public/cobratarios" class="nav-link <?= str_contains($currentPath, '/cobratarios') ? 'active' : '' ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <line x1="19" y1="8" x2="19" y2="14"></line>
                                        <line x1="22" y1="11" x2="16" y2="11"></line>
                                    </svg>
                                    <span>Cobratarios</span>
                                </a>
                            </li>
                            <li>
                                <a href="/proyecto-residencia/public/usuarios" class="nav-link <?= str_contains($currentPath, '/usuarios') ? 'active' : '' ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    <span>Usuarios</span>
                                </a>
                            </li>
                        </ul>
                    </details>
                </li>
                <li>
                    <a href="/proyecto-residencia/public/creditos" class="nav-link <?= str_contains($currentPath, '/creditos') ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="16" x2="9" y2="16.01"></line>
                            <line x1="13" y1="16" x2="13" y2="16.01"></line>
                        </svg>
                        <span>Créditos</span>
                    </a>
                </li>
                <li>
                    <a href="/proyecto-residencia/public/empresa" class="nav-link <?= str_contains($currentPath, '/empresa') ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <path d="M7 7h10v10H7z"></path>
                        </svg>
                        <span>Empresa</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($rol !== 2): ?>
                <li>
                    <a href="/proyecto-residencia/public/impresoras" class="nav-link <?= str_contains($currentPath, '/impresoras') ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Impresoras</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="/proyecto-residencia/public/logout" class="logout-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>

<?php if ($rol === 3 && $isDashboardCobratario): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resumenLink = document.querySelector('[data-cobratario-nav="resumen"]');
            const creditosLink = document.querySelector('[data-cobratario-nav="creditos"]');

            if (!resumenLink || !creditosLink) {
                return;
            }

            function setActiveByHash() {
                const isCreditos = window.location.hash === '#tablaCreditosCobratario';
                resumenLink.classList.toggle('active', !isCreditos);
                creditosLink.classList.toggle('active', isCreditos);
            }

            setActiveByHash();
            window.addEventListener('hashchange', setActiveByHash);
        });
    </script>
<?php endif; ?>