<?php
$impresoras = $impresoras ?? [];
$disponibles = $disponibles ?? [];
?>

<section id="impresoras" class="content-section">
    <div class="section-header">
        <h2>Catálogo de Impresoras</h2>
    </div>

    <div class="form-card" style="margin-bottom: 20px;">
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <h3>Impresoras Registradas</h3>
            </div>

            <?php if (empty($impresoras)): ?>
                <div class="welcome-card" style="padding: 18px;">
                    <p style="margin:0;">No hay impresoras registradas aún.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($impresoras as $impresora): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$impresora['nombre']) ?></td>
                                    <td>
                                        <?php if ((int)$impresora['activa'] === 1): ?>
                                            <span class="badge active">Activa</span>
                                        <?php else: ?>
                                            <span class="badge inactive">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ((int)$impresora['activa'] !== 1): ?>
                                                <form action="/proyecto-residencia/public/impresoras/activar" method="POST">
                                                    <input type="hidden" name="idimpresora" value="<?= (int)$impresora['idimpresora'] ?>">
                                                    <button type="submit" class="btn-secondary">Activar</button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="/proyecto-residencia/public/impresoras/eliminar" method="POST" onsubmit="return confirm('¿Eliminar impresora del catálogo?');">
                                                <input type="hidden" name="idimpresora" value="<?= (int)$impresora['idimpresora'] ?>">
                                                <button type="submit" class="btn-secondary" style="color: var(--accent-red);">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-card">
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <h3>Impresoras Disponibles en el Sistema</h3>
            </div>

            <?php if (empty($disponibles)): ?>
                <p style="color: var(--text-secondary); margin: 0 0 10px 0;">No se detectaron impresoras automáticamente. Puedes agregarla manualmente.</p>
            <?php else: ?>
                <div class="cards-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); margin-bottom: 14px;">
                    <?php foreach ($disponibles as $nombreDisponible): ?>
                        <div class="person-card" style="padding: 12px;">
                            <p style="margin: 0 0 10px 0; color: var(--text-primary); font-weight: 600; word-break: break-word;"><?= htmlspecialchars((string)$nombreDisponible) ?></p>
                            <form action="/proyecto-residencia/public/impresoras/guardar" method="POST">
                                <input type="hidden" name="nombre" value="<?= htmlspecialchars((string)$nombreDisponible) ?>">
                                <button type="submit" class="btn-primary">Agregar al catálogo</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="/proyecto-residencia/public/impresoras/guardar" method="POST" class="form-grid" style="grid-template-columns: 1fr auto; align-items: end;">
                <div class="form-field">
                    <label for="nombre_impresora_manual">Agregar impresora manualmente</label>
                    <input id="nombre_impresora_manual" type="text" name="nombre" placeholder="Ej: POS-58" required>
                </div>
                <button type="submit" class="btn-primary">Agregar</button>
            </form>
        </div>
    </div>
</section>