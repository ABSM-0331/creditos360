<section id="nuevo-cobratario" class="content-section">
    <div class="section-header">
        <h2>Registrar Nuevo Cobratario</h2>
        <a href="cobratarios">
            <button class="btn-secondary" id="btnVolverCobratarios">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al listado
            </button>
        </a>
    </div>

    <form id="formNuevoCobratario" class="form-card" action="crear-cobratario" method="post" enctype="multipart/form-data">
        <!-- Datos Personales -->
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <h3>Datos Personales</h3>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="cob_ap_paterno">Apellido Paterno <span class="required">*</span></label>
                    <input type="text" id="cob_ap_paterno" name="ap_paterno" placeholder="Ej: Gonzalez"
                        maxlength="50" required>
                </div>
                <div class="form-field">
                    <label for="cob_ap_materno">Apellido Materno</label>
                    <input type="text" id="cob_ap_materno" name="ap_materno" placeholder="Ej: Lopez"
                        maxlength="50">
                </div>
                <div class="form-field span-2">
                    <label for="cob_nombres">Nombre(s) <span class="required">*</span></label>
                    <input type="text" id="cob_nombres" name="nombres" placeholder="Ej: Juan Carlos"
                        maxlength="80" required>
                </div>
                <div class="form-field">
                    <label for="cob_sexo">Sexo</label>
                    <select id="cob_sexo" name="sexo">
                        <option value="" disabled selected>Seleccionar</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="cob_fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="cob_fecha_nacimiento" class="fecha_nacimiento" name="fecha_nacimiento">
                </div>
                <div class="form-field">
                    <label for="cob_edad">Edad</label>
                    <input type="number" id="cob_edad" class="edad" name="edad" placeholder="0" min="0" max="150"
                        readonly>
                </div>
                <div class="form-field">
                    <label for="cob_curp">CURP</label>
                    <input type="text" id="cob_curp" name="curp" placeholder="Ej: GOGJ850315HDFRRL09"
                        maxlength="18" style="text-transform: uppercase;">
                </div>
                <div class="form-field">
                    <label for="cob_clave_elector">Clave de Elector</label>
                    <input type="text" id="cob_clave_elector" name="clave_elector"
                        placeholder="Clave INE" maxlength="20">
                </div>
                <div class="form-field">
                    <label for="cob_email">Correo Electronico</label>
                    <input type="email" id="cob_email" name="email" placeholder="correo@ejemplo.com"
                        maxlength="100">
                </div>
                <div class="form-field">
                    <label for="cob_telefono">Telefono <span class="required">*</span></label>
                    <input type="tel" id="cob_telefono" name="telefono" placeholder="Ej: 555 123 4567"
                        maxlength="20" required>
                </div>
            </div>
        </div>

        <!-- Foto -->
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <h3>Foto del Cobratario</h3>
            </div>

            <div class="foto-upload-area">
                <div class="foto-preview" id="cobFotoPreview">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Sin foto</span>
                </div>
                <div class="foto-actions">
                    <label for="cob_foto_ruta" class="btn-secondary" style="cursor: pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Subir Foto
                    </label>
                    <input type="file" id="cob_foto_ruta" name="foto_ruta" accept="image/*"
                        style="display: none;">
                    <span class="foto-hint">JPG, PNG. Max 2MB</span>
                </div>
            </div>
        </div>

        <!-- Domicilio -->
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <h3>Domicilio</h3>
            </div>

            <div class="form-grid">
                <div class="form-field span-2">
                    <label for="cob_dom_calle">Calle</label>
                    <input type="text" id="cob_dom_calle" name="dom_calle"
                        placeholder="Nombre de la calle" maxlength="100">
                </div>
                <div class="form-field">
                    <label for="cob_dom_numero">Numero</label>
                    <input type="text" id="cob_dom_numero" name="dom_numero" placeholder="Ej: 123"
                        maxlength="10">
                </div>
                <div class="form-field">
                    <label for="cob_dom_colonia">Colonia</label>
                    <input type="text" id="cob_dom_colonia" name="dom_colonia"
                        placeholder="Nombre de la colonia" maxlength="100">
                </div>
                <div class="form-field">
                    <label for="cob_dom_cruz1">Entre Calle 1</label>
                    <input type="text" id="cob_dom_cruz1" name="dom_cruz1" placeholder="Primer cruce"
                        maxlength="100">
                </div>
                <div class="form-field">
                    <label for="cob_dom_cruz2">Entre Calle 2</label>
                    <input type="text" id="cob_dom_cruz2" name="dom_cruz2" placeholder="Segundo cruce"
                        maxlength="100">
                </div>
                <div class="form-field">
                    <label for="cob_dom_cp">Codigo Postal</label>
                    <input type="text" id="cob_dom_cp" name="dom_cp" placeholder="Ej: 44100"
                        maxlength="10">
                </div>
                <div class="form-field">
                    <label for="cob_idestado">Estado</label>
                    <select id="cob_idestado" name="idestado">
                        <option value="" disabled selected>Seleccionar estado</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="cob_idmunicipio">Municipio</label>
                    <select id="cob_idmunicipio" name="idmunicipio">
                        <option value="" disabled selected>Seleccionar municipio</option>
                    </select>
                </div>
                <div class="form-field span-full">
                    <label for="cob_dom_referencia">Referencia del Domicilio</label>
                    <textarea id="cob_dom_referencia" name="dom_referencia"
                        placeholder="Ej: Casa color azul, frente al parque..." maxlength="255"
                        rows="3"></textarea>
                </div>
            </div>
        </div>

        <!-- Botones de accion -->
        <div class="form-actions">
            <button type="button" class="btn-secondary btn-lg" id="btnCancelarCob">Cancelar</button>
            <button type="submit" class="btn-primary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Guardar Cobratario
            </button>
        </div>
    </form>
</section>