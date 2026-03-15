<?php
$personas = $personas ?? [];
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>

<section id="nuevo-usuario" class="content-section">
    <div class="section-header">
        <h2>Crear Nuevo Usuario</h2>
        <a href="/proyecto-residencia/public/usuarios">
            <button class="btn-secondary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver
            </button>
        </a>
    </div>

    <?php if ($error): ?>
        <div class="form-card" style="margin-bottom: 16px; border-left: 4px solid var(--accent-red);">
            <p style="margin: 0; color: var(--accent-red); font-weight: 600;">
                <?= htmlspecialchars($error) ?>
            </p>
        </div>
    <?php endif; ?>

    <form class="form-card" action="/proyecto-residencia/public/crear-usuario" method="POST">
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <h3>Datos de la Persona</h3>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="idpersona">Persona <span class="required">*</span></label>
                    <select id="idpersona" name="idpersona" required class="form-select">
                        <option value="">Seleccionar persona...</option>
                        <?php foreach ($personas as $persona): ?>
                            <option value="<?= $persona['idpersona'] ?>">
                                <?= htmlspecialchars($persona['nombre']) ?> - <?= htmlspecialchars($persona['rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h3>Credenciales de Acceso</h3>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="username">Usuario <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required placeholder="Nombre de usuario">
                </div>
                <div class="form-field">
                    <label for="contrasena">Contraseña <span class="required">*</span></label>
                    <input type="password" id="contrasena" name="contrasena" required placeholder="Contraseña">
                </div>
                <div class="form-field">
                    <label for="confirmar_contrasena">Confirmar Contraseña <span class="required">*</span></label>
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required placeholder="Confirmar contraseña">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Crear Usuario
            </button>
            <a href="/proyecto-residencia/public/usuarios">
                <button type="button" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="19" cy="12" r="1"></circle>
                        <circle cx="5" cy="12" r="1"></circle>
                    </svg>
                    Cancelar
                </button>
            </a>
        </div>
    </form>
</section>