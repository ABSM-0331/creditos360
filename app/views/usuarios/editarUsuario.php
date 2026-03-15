<?php $usuario = $usuario ?? []; ?>

<section id="editar-usuario" class="content-section">
    <div class="section-header">
        <h2>Editar Usuario</h2>
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

    <form class="form-card" action="/proyecto-residencia/public/actualizar-usuario" method="POST">
        <input type="hidden" name="idusuario" value="<?= $usuario['idusuario'] ?>">

        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <h3>Información de la Persona</h3>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Nombre</label>
                    <input type="text" value="<?= htmlspecialchars($usuario['nombre']) ?>" disabled>
                </div>
                <div class="form-field">
                    <label>Rol</label>
                    <input type="text" value="<?= htmlspecialchars($usuario['rol']) ?>" disabled>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h3>Datos de Acceso</h3>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="username">Usuario <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"></path>
                    <path d="M12.5 7H11v6l5.25 3.15"></path>
                </svg>
                <h3>Cambiar Contraseña (Opcional)</h3>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="contrasena">Nueva Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Dejar en blanco para no cambiar">
                </div>
                <div class="form-field">
                    <label for="confirmar_contrasena">Confirmar Contraseña</label>
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" placeholder="Dejar en blanco para no cambiar">
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
                Guardar Cambios
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