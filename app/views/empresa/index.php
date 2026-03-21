<?php
$empresa = $empresa ?? [];
$logo = $empresa['logo_ruta'] ?? null;
?>

<section id="empresa" class="content-section">
    <div class="section-header">
        <h2>Datos de la Empresa</h2>
    </div>

    <form class="form-card" action="/proyecto-residencia/public/empresa/guardar" method="POST" enctype="multipart/form-data">
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <path d="M7 7h10v10H7z" opacity="0.35"></path>
                </svg>
                <h3>Informacion General</h3>
            </div>

            <div class="form-grid">
                <div class="form-field span-2">
                    <label for="nombre_empresa">Nombre de la empresa <span class="required">*</span></label>
                    <input type="text" id="nombre_empresa" name="nombre_empresa" maxlength="150" required value="<?= htmlspecialchars($empresa['nombre_empresa'] ?? '') ?>" placeholder="Ej: Creditos 360 S.A. de C.V.">
                </div>

                <div class="form-field span-2">
                    <label for="direccion">Direccion</label>
                    <textarea id="direccion" name="direccion" maxlength="255" rows="3" placeholder="Calle, numero, colonia, municipio, estado"><?= htmlspecialchars($empresa['direccion'] ?? '') ?></textarea>
                </div>

                <div class="form-field">
                    <label for="correo">Correo de la empresa</label>
                    <input type="email" id="correo" name="correo" maxlength="120" value="<?= htmlspecialchars($empresa['correo'] ?? '') ?>" placeholder="contacto@empresa.com">
                </div>

                <div class="form-field">
                    <label for="telefono">Telefono</label>
                    <input type="text" id="telefono" name="telefono" maxlength="20" value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>" placeholder="Ej: 5551234567">
                </div>

                <div class="form-field">
                    <label for="representante_legal">Representante legal</label>
                    <input type="text" id="representante_legal" name="representante_legal" maxlength="150" value="<?= htmlspecialchars($empresa['representante_legal'] ?? '') ?>" placeholder="Nombre completo del representante">
                </div>

                <div class="form-field">
                    <label for="rfc">RFC</label>
                    <input type="text" id="rfc" name="rfc" maxlength="13" style="text-transform: uppercase;" value="<?= htmlspecialchars($empresa['rfc'] ?? '') ?>" placeholder="XAXX010101000">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <h3>Logo de la Empresa</h3>
            </div>

            <div class="foto-upload-area">
                <div class="foto-preview" id="empresaLogoPreview">
                    <?php if (!empty($logo)): ?>
                        <img src="/<?= htmlspecialchars($logo) ?>" alt="Logo actual">
                    <?php else: ?>
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span>Sin logo</span>
                    <?php endif; ?>
                </div>

                <div class="foto-actions">
                    <label for="empresa_logo" class="btn-secondary" style="cursor: pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Subir Logo
                    </label>
                    <input type="file" id="empresa_logo" name="empresa_logo" accept="image/*" style="display: none;">
                    <span class="foto-hint">JPG, PNG, WEBP o SVG. Max 2MB</span>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Guardar Datos de la Empresa
            </button>
        </div>
    </form>
</section>