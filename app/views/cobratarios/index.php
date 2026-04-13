<?php $cobratarios = $cobratarios ?? []; ?>
<section id="cobratarios" class="content-section">
    <div class="section-header">
        <h2>Catálogo de Cobratarios</h2>
        <a href="nuevo-cobratario">
            <button class="btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nuevo Cobratario
            </button>
        </a>
    </div>

    <div class="search-bar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" placeholder="Buscar cobratario...">
    </div>

    <div class="cards-grid">
        <?php foreach ($cobratarios as $cobratario): ?>
            <div class="person-card">
                <div class="person-avatar">
                    <span><?= htmlspecialchars(strtoupper(substr($cobratario['nombre'], 0, 2))) ?></span>
                </div>
                <div class="person-info">
                    <h3><?= htmlspecialchars($cobratario['nombre']) ?></h3>
                    <p class="person-email"><?= htmlspecialchars($cobratario['email'] ?? 'Sin correo') ?></p>
                    <p class="person-phone"><?= htmlspecialchars($cobratario['telefono'] ?? 'Sin telefono') ?></p>
                </div>
                <div class="person-stats">
                    <div class="stat">
                        <span class="stat-number"><?= (int)($cobratario['clientes_asignados'] ?? 0) ?></span>
                        <span class="stat-text">Clientes</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">$<?= number_format((float)($cobratario['total_cobrado'] ?? 0), 2, '.', ',') ?></span>
                        <span class="stat-text">Cobrado hoy</span>
                    </div>
                </div>
                <div class="person-actions">
                    <button class="btn-secondary">Ver Detalles</button>
                    <button class="btn-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="1"></circle>
                            <circle cx="19" cy="12" r="1"></circle>
                            <circle cx="5" cy="12" r="1"></circle>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>