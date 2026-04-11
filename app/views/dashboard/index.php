<!-- Dashboard Section -->
<section id="dashboard" class="content-section active">
    <?php
    $datos = [
        'totalClientes' => 124,
        'totalCobratarios' => 18,
        'cobroHoy' => 45230,
        'cobrosPendientesHoy' => 32
    ];

    // Usar estadísticas si están disponibles
    if (isset($estadisticas) && is_array($estadisticas)) {
        $datos = $estadisticas;
    }
    ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= (int)$datos['totalClientes'] ?></span>
                <span class="stat-label">Total Clientes</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <line x1="19" y1="8" x2="19" y2="14"></line>
                    <line x1="22" y1="11" x2="16" y2="11"></line>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= (int)$datos['totalCobratarios'] ?></span>
                <span class="stat-label">Total Cobratarios</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value">$<?= number_format($datos['cobroHoy'], 2, '.', ',') ?></span>
                <span class="stat-label">Cobrado Hoy</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= (int)$datos['cobrosPendientesHoy'] ?></span>
                <span class="stat-label">Cobros Pendientes Hoy</span>
            </div>
        </div>
    </div>

    <div class="welcome-card">
        <h2>Bienvenido al Sistema de Gestión</h2>
        <p>Selecciona una opción del menú lateral para comenzar a administrar tus clientes y cobratarios.</p>
    </div>
</section>