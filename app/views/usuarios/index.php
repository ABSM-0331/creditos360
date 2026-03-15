<?php
$usuarios = $usuarios ?? [];
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<section id="usuarios" class="content-section">
    <div class="section-header">
        <h2>Gestión de Usuarios</h2>
        <a href="nuevo-usuario">
            <button class="btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Crear Usuario
            </button>
        </a>
    </div>

    <?php if ($success): ?>
        <div class="form-card" style="margin-bottom: 16px; border-left: 4px solid var(--accent-green);">
            <p style="margin: 0; color: var(--accent-green); font-weight: 600;">
                <?= htmlspecialchars($success) ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="form-card" style="margin-bottom: 16px; border-left: 4px solid var(--accent-red);">
            <p style="margin: 0; color: var(--accent-red); font-weight: 600;">
                <?= htmlspecialchars($error) ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="search-bar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" placeholder="Buscar usuario...">
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 10px; opacity: 0.5;">
                                <path d="M14 16H9m-4.5-4h15"></path>
                                <path d="M12 2c-6.627 0-12 3.134-12 7v2c0 3.866 5.373 7 12 7s12-3.134 12-7v-2c0-3.866-5.373-7-12-7z"></path>
                                <path d="M12 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                            <p>No hay usuarios registrados</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($usuario['username']) ?></strong></td>
                            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td><?= htmlspecialchars($usuario['telefono'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($usuario['rol'] === 'Cliente'): ?>
                                    <span class="badge badge-info">Cliente</span>
                                <?php elseif ($usuario['rol'] === 'Cobratario'): ?>
                                    <span class="badge badge-warning">Cobratario</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="editar-usuario?id=<?= $usuario['idusuario'] ?>">
                                        <button class="btn-icon" title="Editar">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>