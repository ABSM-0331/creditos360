<?php
$creditos = $creditos ?? [];
$resumenCobratario = $resumenCobratario ?? [
    'totalCreditosAsignados' => 0,
    'creditosActivos' => 0,
    'clientesAsignados' => 0,
    'totalCobrado' => 0,
    'pendienteCobroHoy' => 0,
];
?>

<section class="content-section">
    <div class="section-header">
        <h2>Dashboard del Cobratario</h2>
    </div>

    <div id="resumenCobratario" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px; scroll-margin-top: 90px;">
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <p style="margin: 0 0 6px 0; color: var(--text-secondary); font-size: 12px;">Total cobrado hoy</p>
            <p style="margin: 0; color: var(--text-primary); font-size: 24px; font-weight: 700;">$<?= number_format((float)$resumenCobratario['totalCobrado'], 2, '.', ',') ?></p>
        </div>
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <p style="margin: 0 0 6px 0; color: var(--text-secondary); font-size: 12px;">Clientes asignados</p>
            <p style="margin: 0; color: var(--text-primary); font-size: 24px; font-weight: 700;"><?= (int)$resumenCobratario['clientesAsignados'] ?></p>
        </div>
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <p style="margin: 0 0 6px 0; color: var(--text-secondary); font-size: 12px;">Créditos asignados</p>
            <p style="margin: 0; color: var(--text-primary); font-size: 24px; font-weight: 700;"><?= (int)$resumenCobratario['totalCreditosAsignados'] ?></p>
        </div>
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <p style="margin: 0 0 6px 0; color: var(--text-secondary); font-size: 12px;">Créditos activos</p>
            <p style="margin: 0; color: var(--text-primary); font-size: 24px; font-weight: 700;"><?= (int)$resumenCobratario['creditosActivos'] ?></p>
        </div>
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <p style="margin: 0 0 6px 0; color: var(--text-secondary); font-size: 12px;">Pendiente por cobrar hoy</p>
            <p style="margin: 0; color: var(--text-primary); font-size: 24px; font-weight: 700;">$<?= number_format((float)$resumenCobratario['pendienteCobroHoy'], 2, '.', ',') ?></p>
        </div>
    </div>

    <!-- Tabla de créditos asignados -->
    <div id="tablaCreditosCobratario">
        <div style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <h3 style="margin: 0 0 15px 0; color: var(--text-primary); font-size: 16px;">Mis Créditos Asignados</h3>
            <?php if (empty($creditos)): ?>
                <div style="text-align: center; padding: 30px; color: var(--text-muted); background: var(--bg-tertiary); border-radius: 8px;">
                    <p>📭 No tienes créditos asignados aún.</p>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 14px;">
                    <input
                        type="text"
                        id="buscadorCreditosCobratario"
                        placeholder="Buscar crédito por ID, cliente, tipo, estado o monto..."
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary); font-size: 14px; box-sizing: border-box;">
                </div>
                <div id="sinResultadosCreditosCobratario" style="display: none; text-align: center; padding: 18px; margin-bottom: 10px; color: var(--text-muted); background: var(--bg-tertiary); border-radius: 8px;">
                    No se encontraron créditos con ese criterio de búsqueda.
                </div>
                <div style="overflow-x: auto; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead style="background: var(--bg-tertiary); position: sticky; top: 0;">
                            <tr>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">ID</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Cliente</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Monto</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Tipo</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Pagos</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Saldo Pendiente</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Estado</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Fecha Inicio</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCreditosCobratario">
                            <?php foreach ($creditos as $credito): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary); font-weight: 500;">#<?= htmlspecialchars($credito['idcredito']) ?></td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary);"><?= htmlspecialchars($credito['cliente']) ?></td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary); font-weight: 500;">$<?= number_format($credito['monto'], 2, '.', ',') ?></td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary);">
                                        <span style="display: inline-block; padding: 4px 8px; background: var(--bg-tertiary); border-radius: 4px; font-size: 12px; text-transform: capitalize;">
                                            <?= htmlspecialchars($credito['tipo']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary);"><?= htmlspecialchars($credito['cantidad_pagos']) ?></td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary); font-weight: 500;">$<?= number_format($credito['saldo_pendiente'], 2, '.', ',') ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; 
                                            <?php
                                            if ($credito['estado'] === 'activo') {
                                                echo 'background: rgba(34, 197, 94, 0.1); color: rgb(34, 197, 94);';
                                            } elseif ($credito['estado'] === 'completado') {
                                                echo 'background: rgba(59, 130, 246, 0.1); color: rgb(59, 130, 246);';
                                            } else {
                                                echo 'background: rgba(239, 68, 68, 0.1); color: rgb(239, 68, 68);';
                                            }
                                            ?>
                                        ">
                                            <?= htmlspecialchars(strtoupper($credito['estado'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-secondary); font-size: 12px;">
                                        <?= date('d/m/Y', strtotime($credito['fecha_inicio'])) ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <button type="button" onclick="verDetalleCredito(<?= $credito['idcredito'] ?>)" style="padding: 6px 12px; background: var(--accent-blue); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: background 0.2s; margin-right: 6px;" onmouseover="this.style.background='rgb(37, 149, 218)'" onmouseout="this.style.background='var(--accent-blue)'">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            Ver Detalle
                                        </button>
                                        <button type="button" onclick="cobrarCreditoAsignado(<?= $credito['idcredito'] ?>)" style="padding: 6px 12px; background: rgb(34, 197, 94); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                            Cobrar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detalle del crédito (escondido por defecto) -->
    <div id="detalleCredito" style="display: none;">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2>Detalle del Crédito <span id="creditoNumero" style="color: var(--accent-blue);"></span></h2>
            <button type="button" onclick="cerrarDetalleCredito()" style="padding: 10px 20px; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--radius); cursor: pointer; font-weight: 500; font-size: 14px;">
                Volver
            </button>
        </div>

        <div class="form-card">
            <!-- Información del crédito -->
            <div class="form-section">
                <div class="form-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <h3>Información del Crédito</h3>
                </div>
                <div id="infoCredito" style="margin-top: 15px;">
                    <!-- Se llenará con JavaScript -->
                </div>
            </div>

            <!-- Resumen de pagos -->
            <div class="form-section" id="resumenPagos" style="margin-top: 20px;">
                <div class="form-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    <h3>Resumen de Montos</h3>
                </div>
                <div id="statsCredito" style="margin-top: 15px;">
                    <!-- Se llenará con JavaScript -->
                </div>
            </div>

            <!-- Tabla de pagos -->
            <div class="form-section" id="tablaPagosSection" style="margin-top: 20px;">
                <div class="form-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10H3"></path>
                        <path d="M21 6H3"></path>
                        <path d="M21 14H3"></path>
                        <path d="M21 18H3"></path>
                    </svg>
                    <h3>Cronograma de Pagos</h3>
                </div>
                <div id="tablaPagos" style="margin-top: 15px;">
                    <!-- Se llenará con JavaScript -->
                </div>
            </div>
        </div>
    </div>
</section>

<div id="modalCobroPago" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1200; align-items: center; justify-content: center;">
    <div style="width: 100%; max-width: 460px; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px;">
        <h3 style="margin: 0 0 12px 0; color: var(--text-primary);">Registrar cobro</h3>
        <p id="infoPagoCobro" style="margin: 0 0 14px 0; color: var(--text-secondary); font-size: 13px;"></p>
        <div id="detallePagosCobro" style="display: none; margin: 0 0 14px 0; padding: 10px 12px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); font-size: 12px; max-height: 120px; overflow-y: auto;"></div>
        <div id="alertaCobroAnticipado" style="display: none; margin: 0 0 14px 0; padding: 10px 12px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 8px; color: rgb(180, 83, 9); font-size: 12px;"></div>

        <div style="display: grid; gap: 10px;">
            <div>
                <label style="display:block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px;">Monto a cobrar</label>
                <input id="montoCobro" type="number" step="0.01" readonly style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary);">
            </div>
            <div id="grupoAbonoCapital" style="display: none;">
                <label style="display:block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px;">Abono a capital (opcional)</label>
                <input id="abonoCapitalCobro" type="number" step="0.01" min="0" value="0" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary);">
                <p id="ayudaAbonoCapital" style="margin: 6px 0 0 0; color: var(--text-muted); font-size: 11px;"></p>
            </div>
            <div>
                <label style="display:block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px;">Efectivo recibido</label>
                <input id="montoRecibidoCobro" type="number" step="0.01" min="0" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary);">
            </div>
            <div>
                <label style="display:block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px;">Forma de pago</label>
                <select id="metodoPagoCobro" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary);">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta_debito">Tarjeta de débito</option>
                    <option value="tarjeta_credito">Tarjeta de crédito</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px;">Cambio</label>
                <input id="cambioCobro" type="text" readonly value="$0.00" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); font-weight: 700;">
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap: 10px; margin-top: 14px;">
            <button type="button" onclick="cerrarModalCobro()" class="btn-secondary">Cancelar</button>
            <button type="button" id="btnConfirmarCobro" onclick="confirmarCobroPago()" class="btn-primary">Confirmar cobro</button>
        </div>
    </div>
</div>

<script>
    let cobroActual = {
        idCredito: null,
        pagosSeleccionados: [],
        tipoCredito: '',
        montoBaseCobro: 0,
        abonoCapital: 0,
        montoCobro: 0,
        tieneAnticipados: false,
    };
    let pagosCreditoActual = [];

    function navegarSeccionCobratarioPorHash() {
        const hash = window.location.hash;
        const esCreditos = hash === '#tablaCreditosCobratario';

        const resumen = document.getElementById('resumenCobratario');
        const tabla = document.getElementById('tablaCreditosCobratario');
        const detalle = document.getElementById('detalleCredito');

        if (resumen) {
            resumen.style.display = esCreditos ? 'none' : 'grid';
        }

        if (tabla) {
            tabla.style.display = esCreditos ? 'block' : 'none';
        }

        if (detalle) {
            detalle.style.display = 'none';
        }

        const target = esCreditos ?
            document.getElementById('tablaCreditosCobratario') :
            document.getElementById('resumenCobratario');

        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    function inicializarBuscadorCreditosCobratario() {
        const buscador = document.getElementById('buscadorCreditosCobratario');
        const tbody = document.getElementById('tbodyCreditosCobratario');
        const sinResultados = document.getElementById('sinResultadosCreditosCobratario');

        if (!buscador || !tbody) {
            return;
        }

        buscador.addEventListener('input', function() {
            const termino = this.value.toLowerCase().trim();
            const filas = tbody.querySelectorAll('tr');
            let visibles = 0;

            filas.forEach((fila) => {
                const textoFila = fila.textContent.toLowerCase();
                const coincide = termino === '' || textoFila.includes(termino);
                fila.style.display = coincide ? '' : 'none';

                if (coincide) {
                    visibles += 1;
                }
            });

            if (sinResultados) {
                sinResultados.style.display = visibles === 0 ? 'block' : 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        navegarSeccionCobratarioPorHash();
        inicializarBuscadorCreditosCobratario();
    });
    window.addEventListener('hashchange', navegarSeccionCobratarioPorHash);

    // Funciones para ver detalle del crédito
    function verDetalleCredito(idCredito) {
        cobroActual.idCredito = idCredito;
        // Ocultar tabla de créditos
        document.getElementById('resumenCobratario').style.display = 'none';
        document.getElementById('tablaCreditosCobratario').style.display = 'none';
        document.getElementById('detalleCredito').style.display = 'block';

        // Mostrar mensaje de carga
        document.getElementById('creditoNumero').textContent = `#${idCredito}`;
        document.getElementById('infoCredito').innerHTML = '<div style="text-align: center; padding: 20px;"><p>Cargando...</p></div>';
        document.getElementById('statsCredito').innerHTML = '';
        document.getElementById('tablaPagos').innerHTML = '';

        // Hacer la petición al servidor
        fetch(`/proyecto-residencia/public/creditos/obtener?id=${idCredito}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar el crédito');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    mostrarDetalleCredito(data.credito, data.pagos);
                } else {
                    throw new Error(data.mensaje || 'Error al cargar el crédito');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                cerrarDetalleCredito();
            });

        // Scroll a la parte superior
        document.querySelector('.section-header').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function mostrarDetalleCredito(credito, pagos) {
        pagosCreditoActual = Array.isArray(pagos) ? pagos : [];
        cobroActual.idCredito = credito.idcredito;
        cobroActual.pagosSeleccionados = [];

        // Mostrar información del crédito
        const estadoColor = {
            'activo': 'rgb(34, 197, 94)',
            'completado': 'rgb(59, 130, 246)',
            'cancelado': 'rgb(239, 68, 68)'
        };

        const infoHTML = `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Cliente</p>
                    <p style="color: var(--text-primary); font-weight: 600; margin: 0;">${credito.cliente}</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Cobrador</p>
                    <p style="color: var(--text-primary); font-weight: 600; margin: 0;">${credito.cobratario}</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Tipo</p>
                    <p style="color: var(--text-primary); font-weight: 600; margin: 0; text-transform: capitalize;">${credito.tipo}</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Estado</p>
                    <p style="color: ${estadoColor[credito.estado]}; font-weight: 600; margin: 0; text-transform: uppercase;">${credito.estado}</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Fecha Inicio</p>
                    <p style="color: var(--text-primary); font-weight: 600; margin: 0;">${formatearFecha(credito.fecha_inicio)}</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Fecha Final</p>
                    <p style="color: var(--text-primary); font-weight: 600; margin: 0;">${formatearFecha(credito.fecha_fin)}</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Interés</p>
                    <p style="color: var(--text-primary); font-weight: 600; margin: 0;">${credito.interes}%</p>
                </div>
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 8px;">
                    <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 5px 0;">Moratorio</p>
                    <p style="color: var(--text-primary); font-weight: 600; margin: 0;">$${Number(credito.moratorio).toFixed(2)}</p>
                </div>
            </div>
        `;
        document.getElementById('infoCredito').innerHTML = infoHTML;

        // Calcular totales
        const montoOriginal = parseFloat(credito.monto);
        const totalPagar = parseFloat(credito.total_pagos);
        const saldoPendiente = parseFloat(credito.saldo_pendiente);
        const pagado = totalPagar - saldoPendiente;
        const interes = totalPagar - montoOriginal;

        // Determinar el label del pago según el tipo
        const tipoTexto = String(credito.tipo || '')
            .toLowerCase()
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, (letra) => letra.toUpperCase());
        const labelPago = `Pago ${tipoTexto.toLowerCase()}`;

        const statsHTML = `
            <div class="credito-stats-grid">
                <div class="stat-box">
                    <p class="stat-label">Capital</p>
                    <p class="stat-value">$${formatearMoneda(montoOriginal)}</p>
                </div>
                <div class="stat-box">
                    <p class="stat-label">Interés</p>
                    <p class="stat-value">$${formatearMoneda(interes)}</p>
                </div>
                <div class="stat-box">
                    <p class="stat-label">Total a Pagar</p>
                    <p class="stat-value">$${formatearMoneda(totalPagar)}</p>
                </div>
                <div class="stat-box">
                    <p class="stat-label">${labelPago}</p>
                    <p class="stat-value">$${formatearMoneda(totalPagar / credito.cantidad_pagos)}</p>
                </div>
                <div class="stat-box">
                    <p class="stat-label">Pagado</p>
                    <p class="stat-value" style="color: rgb(34, 197, 94);">$${formatearMoneda(pagado)}</p>
                </div>
                <div class="stat-box">
                    <p class="stat-label">Saldo Pendiente</p>
                    <p class="stat-value" style="color: rgb(239, 68, 68);">$${formatearMoneda(saldoPendiente)}</p>
                </div>
            </div>
        `;
        document.getElementById('statsCredito').innerHTML = statsHTML;

        const pagosSeleccionables = obtenerPagosSeleccionables(pagos);
        const puedeSeleccionarTodos = pagosSeleccionables.length > 0;
        const estiloBotonSeleccion = puedeSeleccionarTodos ?
            'padding: 8px 14px; background: rgb(34, 197, 94); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;' :
            'padding: 8px 14px; background: var(--bg-tertiary); color: var(--text-muted); border: none; border-radius: 6px; cursor: not-allowed; font-size: 12px; font-weight: 600;';

        let tablaHTML = `
            <div id="accionesCobroSeleccionado" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; padding: 12px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px;">
                <div>
                    <p id="resumenSeleccionCobro" style="margin: 0; color: var(--text-secondary); font-size: 13px;">Selecciona una o varias letras pendientes para cobrarlas.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-primary); font-size: 13px;">
                        <input id="seleccionarTodosPagos" type="checkbox" onchange="alternarSeleccionTodos(this.checked)" ${puedeSeleccionarTodos ? '' : 'disabled'}>
                        Seleccionar todas
                    </label>
                    <button type="button" id="btnCobrarSeleccionadas" onclick="abrirModalCobroSeleccionado()" style="${estiloBotonSeleccion}" disabled>
                        Cobrar seleccionadas
                    </button>
                </div>
            </div>
            <div class="credito-table-container" style="overflow-x: auto; max-height: 500px; border-radius: 8px;">
                <table class="data-table" style="width: 100%; font-size: 12px;">
                    <thead style="position: sticky; top: 0; background: var(--bg-secondary);">
                        <tr>
                            <th style="padding: 10px 5px;">Sel.</th>
                            <th style="padding: 10px 5px;">#</th>
                            <th style="padding: 10px 5px;">Fecha</th>
                            <th style="padding: 10px 5px;">Saldo Inicial</th>
                            <th style="padding: 10px 5px;">Capital</th>
                            <th style="padding: 10px 5px;">Interés</th>
                            <th style="padding: 10px 5px;">Pago</th>
                            <th style="padding: 10px 5px;">Saldo Final</th>
                            <th style="padding: 10px 5px;">Estado</th>
                            <th style="padding: 10px 5px;">Acción</th>
                            <th style="padding: 10px 5px;">Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        pagos.forEach((pago, index) => {
            const estadoPago = pago.estado || 'pendiente';
            let estadoBadge = '';

            switch (estadoPago) {
                case 'pagado':
                    estadoBadge = '<span style="display: inline-block; padding: 4px 8px; background: rgba(34, 197, 94, 0.1); color: rgb(34, 197, 94); border-radius: 4px; font-size: 11px; font-weight: 600;">PAGADO</span>';
                    break;
                case 'pendiente':
                    estadoBadge = '<span style="display: inline-block; padding: 4px 8px; background: rgba(234, 179, 8, 0.1); color: rgb(234, 179, 8); border-radius: 4px; font-size: 11px; font-weight: 600;">PENDIENTE</span>';
                    break;
                case 'vencido':
                    estadoBadge = '<span style="display: inline-block; padding: 4px 8px; background: rgba(239, 68, 68, 0.1); color: rgb(239, 68, 68); border-radius: 4px; font-size: 11px; font-weight: 600;">VENCIDO</span>';
                    break;
                case 'atrasado':
                    estadoBadge = '<span style="display: inline-block; padding: 4px 8px; background: rgba(249, 115, 22, 0.1); color: rgb(249, 115, 22); border-radius: 4px; font-size: 11px; font-weight: 600;">ATRASADO</span>';
                    break;
            }

            // Calcular saldo inicial (saldo vivo + capital programado)
            const saldoInicial = parseFloat(pago.saldo_vivo) + parseFloat(pago.capital_programado);
            const montoCobroActual = obtenerMontoCobroPago(pago);
            const puedeCobrar = esPagoSeleccionable(pago);
            const esAnticipado = esPagoAnticipado(pago);
            const estiloFila = obtenerEstiloFilaPago(pago);
            const accionCobro = puedeCobrar ?
                `<button type="button" onclick="abrirModalCobroIndividual(${credito.idcredito}, ${pago.idpago})" style="padding: 6px 10px; background: var(--accent-blue); color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;">Cobrar</button>` :
                '<span style="color: var(--text-muted); font-size: 11px;">-</span>';
            const botónTicket = estadoPago === 'pagado' ?
                `<button type="button" onclick="recuperarTicketPago(${credito.idcredito}, ${pago.idpago})" style="padding: 6px 10px; background: rgb(34, 197, 94); color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Ver ticket
                </button>` :
                '<span style="color: var(--text-muted); font-size: 11px;">-</span>';
            const checkboxCobro = puedeCobrar ?
                `<input type="checkbox" class="checkbox-pago-cobro" data-idpago="${pago.idpago}" onchange="toggleSeleccionPago(${credito.idcredito}, ${pago.idpago}, this.checked)">` :
                '<span style="color: var(--text-muted); font-size: 11px;">-</span>';
            const etiquetaAnticipado = esAnticipado ?
                '<div style="margin-top: 4px; color: rgb(245, 158, 11); font-size: 10px; font-weight: 700;">ANTICIPADO</div>' :
                '';
            const etiquetaMoratorio = Number(pago.recargo_moratorio || 0) > 0 ?
                `<div style="margin-top: 4px; color: rgb(239, 68, 68); font-size: 10px; font-weight: 700;">+ MORATORIO $${formatearMoneda(pago.recargo_moratorio)}</div>` :
                '';

            tablaHTML += `
                <tr style="border-bottom: 1px solid var(--border-color); ${estiloFila}">
                    <td style="padding: 8px 5px; text-align: center;">${checkboxCobro}</td>
                    <td style="padding: 8px 5px;">${pago.numero_pago}</td>
                    <td style="padding: 8px 5px;">${formatearFecha(pago.fecha_programada)}${etiquetaAnticipado}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(saldoInicial)}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(pago.capital_programado)}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(pago.interes_programado)}</td>
                    <td style="padding: 8px 5px; font-weight: 600;">$${formatearMoneda(montoCobroActual)}${etiquetaMoratorio}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(pago.saldo_vivo)}</td>
                    <td style="padding: 8px 5px; text-align: center;">${estadoBadge}</td>
                    <td style="padding: 8px 5px; text-align: center;">${accionCobro}</td>
                    <td style="padding: 8px 5px; text-align: center;">${botónTicket}</td>
                </tr>
            `;
        });

        tablaHTML += `
                    </tbody>
                </table>
            </div>
        `;
        document.getElementById('tablaPagos').innerHTML = tablaHTML;
        actualizarEstadoSeleccionPagos();
    }

    function abrirModalCobro(idCredito, pagosSeleccionados) {
        const pagos = Array.isArray(pagosSeleccionados) ? pagosSeleccionados : [];
        const totalBaseCobro = pagos.reduce((acumulado, pago) => acumulado + obtenerMontoCobroPago(pago), 0);
        const pagosAnticipados = pagos.filter((pago) => esPagoAnticipado(pago));
        const esMensual = (pagos[0]?.tipo || '').toLowerCase() === 'mensual';
        const saldoVivo = Number(pagos[0]?.saldo_vivo || 0);
        const interesMensual = Number(pagos[0]?.interes_programado || 0);
        const detallePagos = pagos
            .map((pago) => {
                const recargo = Number(pago.recargo_moratorio || 0);
                const textoMoratorio = recargo > 0 ? ` (incluye moratorio $${formatearMoneda(recargo)})` : '';
                return `Letra #${pago.numero_pago} · ${formatearFecha(pago.fecha_programada)} · $${formatearMoneda(obtenerMontoCobroPago(pago))}${textoMoratorio}`;
            })
            .join('<br>');

        cobroActual.idCredito = idCredito;
        cobroActual.pagosSeleccionados = pagos.map((pago) => Number(pago.idpago));
        cobroActual.tipoCredito = (pagos[0]?.tipo || '').toLowerCase();
        cobroActual.montoBaseCobro = Number(totalBaseCobro || 0);
        cobroActual.abonoCapital = 0;
        cobroActual.montoCobro = Number(totalBaseCobro || 0);
        cobroActual.tieneAnticipados = pagosAnticipados.length > 0;

        document.getElementById('infoPagoCobro').textContent = pagos.length === 1 ?
            `Crédito #${idCredito} · Letra #${pagos[0].numero_pago}` :
            `Crédito #${idCredito} · ${pagos.length} letras seleccionadas`;
        document.getElementById('detallePagosCobro').style.display = pagos.length > 0 ? 'block' : 'none';
        document.getElementById('detallePagosCobro').innerHTML = detallePagos;
        document.getElementById('alertaCobroAnticipado').style.display = cobroActual.tieneAnticipados ? 'block' : 'none';
        document.getElementById('alertaCobroAnticipado').textContent = cobroActual.tieneAnticipados ?
            `Se cobrarán ${pagosAnticipados.length} letra(s) antes de su fecha programada. Al continuar se te pedirá confirmación.` :
            '';
        document.getElementById('grupoAbonoCapital').style.display = esMensual ? 'block' : 'none';
        document.getElementById('abonoCapitalCobro').value = '0';
        document.getElementById('ayudaAbonoCapital').textContent = esMensual ?
            `Pago minimo por interes: $${formatearMoneda(interesMensual)}. Capital actual: $${formatearMoneda(saldoVivo)}.` :
            '';
        document.getElementById('montoCobro').value = cobroActual.montoCobro.toFixed(2);
        document.getElementById('montoRecibidoCobro').value = '';
        document.getElementById('metodoPagoCobro').value = 'efectivo';
        document.getElementById('cambioCobro').value = '$0.00';
        document.getElementById('btnConfirmarCobro').disabled = true;
        document.getElementById('modalCobroPago').style.display = 'flex';
    }

    function obtenerFechaHoySinHora() {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        return hoy;
    }

    function obtenerMontoCobroPago(pago) {
        const montoActual = Number(pago.monto_cobro_actual || 0);
        if (montoActual > 0) {
            return montoActual;
        }

        const recargoMoratorio = Number(pago.recargo_moratorio || 0);
        return Number(pago.monto_programado || 0) + recargoMoratorio;
    }

    function esPagoDelDia(pago) {
        const estado = (pago.estado || '').toLowerCase();
        if (estado !== 'pendiente') {
            return false;
        }

        const fecha = new Date(`${pago.fecha_programada}T00:00:00`);
        const hoy = obtenerFechaHoySinHora();
        return fecha.getTime() === hoy.getTime();
    }

    function obtenerEstiloFilaPago(pago) {
        const estado = (pago.estado || '').toLowerCase();

        if (estado === 'pagado') {
            return 'background: rgba(34, 197, 94, 0.12);';
        }

        if (estado === 'vencido' || estado === 'atrasado') {
            return 'background: rgba(239, 68, 68, 0.12);';
        }

        if (esPagoDelDia(pago)) {
            return 'background: rgba(59, 130, 246, 0.14);';
        }

        return '';
    }

    function esPagoSeleccionable(pago) {
        const estado = (pago.estado || '').toLowerCase();
        return ['pendiente', 'vencido', 'atrasado'].includes(estado);
    }

    function esPagoAnticipado(pago) {
        if (!esPagoSeleccionable(pago)) {
            return false;
        }

        const fecha = new Date(`${pago.fecha_programada}T00:00:00`);
        return fecha > obtenerFechaHoySinHora();
    }

    function obtenerPagosSeleccionables(pagos) {
        return (pagos || []).filter((pago) => esPagoSeleccionable(pago));
    }

    function obtenerPagoCorrespondiente(pagos) {
        const hoy = obtenerFechaHoySinHora();

        return pagos.find((p) => {
            const estado = (p.estado || '').toLowerCase();
            if (!['pendiente', 'vencido', 'atrasado'].includes(estado)) {
                return false;
            }
            const fecha = new Date(`${p.fecha_programada}T00:00:00`);
            return fecha <= hoy;
        });
    }

    function obtenerPagoSugerido(pagos) {
        const pagoCorrespondiente = obtenerPagoCorrespondiente(pagos);
        if (pagoCorrespondiente) {
            return pagoCorrespondiente;
        }

        return obtenerPagosSeleccionables(pagos)[0] || null;
    }

    function obtenerPagoPorId(idPago) {
        return pagosCreditoActual.find((pago) => Number(pago.idpago) === Number(idPago)) || null;
    }

    function obtenerPagosSeleccionados() {
        return cobroActual.pagosSeleccionados
            .map((idPago) => obtenerPagoPorId(idPago))
            .filter(Boolean);
    }

    function toggleSeleccionPago(idCredito, idPago, seleccionado) {
        if (Number(cobroActual.idCredito) !== Number(idCredito)) {
            cobroActual.idCredito = idCredito;
            cobroActual.pagosSeleccionados = [];
        }

        const idNormalizado = Number(idPago);
        if (seleccionado) {
            if (!cobroActual.pagosSeleccionados.includes(idNormalizado)) {
                cobroActual.pagosSeleccionados.push(idNormalizado);
            }
        } else {
            cobroActual.pagosSeleccionados = cobroActual.pagosSeleccionados.filter((id) => Number(id) !== idNormalizado);
        }

        actualizarEstadoSeleccionPagos();
    }

    function alternarSeleccionTodos(seleccionarTodo) {
        const ids = seleccionarTodo ?
            obtenerPagosSeleccionables(pagosCreditoActual).map((pago) => Number(pago.idpago)) : [];

        cobroActual.pagosSeleccionados = ids;

        document.querySelectorAll('.checkbox-pago-cobro').forEach((checkbox) => {
            checkbox.checked = seleccionarTodo;
        });

        actualizarEstadoSeleccionPagos();
    }

    function actualizarEstadoSeleccionPagos() {
        const resumen = document.getElementById('resumenSeleccionCobro');
        const btnCobrar = document.getElementById('btnCobrarSeleccionadas');
        const seleccionarTodos = document.getElementById('seleccionarTodosPagos');
        const seleccionables = obtenerPagosSeleccionables(pagosCreditoActual);
        const pagosSeleccionados = obtenerPagosSeleccionados();
        const total = pagosSeleccionados.reduce((acumulado, pago) => acumulado + obtenerMontoCobroPago(pago), 0);
        const anticipados = pagosSeleccionados.filter((pago) => esPagoAnticipado(pago)).length;

        if (resumen) {
            if (seleccionables.length === 0) {
                resumen.textContent = 'Este crédito no tiene letras pendientes disponibles para cobro.';
            } else if (pagosSeleccionados.length === 0) {
                resumen.textContent = 'Selecciona una o varias letras pendientes para cobrarlas.';
            } else {
                const textoAnticipado = anticipados > 0 ? ` · ${anticipados} anticipada(s)` : '';
                resumen.textContent = `${pagosSeleccionados.length} letra(s) seleccionada(s) · Total $${formatearMoneda(total)}${textoAnticipado}`;
            }
        }

        if (btnCobrar) {
            btnCobrar.disabled = pagosSeleccionados.length === 0;
            btnCobrar.style.background = pagosSeleccionados.length === 0 ? 'var(--bg-tertiary)' : 'rgb(34, 197, 94)';
            btnCobrar.style.color = pagosSeleccionados.length === 0 ? 'var(--text-muted)' : '#fff';
            btnCobrar.style.cursor = pagosSeleccionados.length === 0 ? 'not-allowed' : 'pointer';
        }

        if (seleccionarTodos) {
            seleccionarTodos.checked = seleccionables.length > 0 && pagosSeleccionados.length === seleccionables.length;
            seleccionarTodos.indeterminate = pagosSeleccionados.length > 0 && pagosSeleccionados.length < seleccionables.length;
        }
    }

    function abrirModalCobroIndividual(idCredito, idPago) {
        const pago = obtenerPagoPorId(idPago);
        if (!pago) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró la letra seleccionada.',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        abrirModalCobro(idCredito, [pago]);
    }

    function abrirModalCobroSeleccionado() {
        const pagosSeleccionados = obtenerPagosSeleccionados();
        if (pagosSeleccionados.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin selección',
                text: 'Selecciona al menos una letra para registrar el cobro.',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        abrirModalCobro(cobroActual.idCredito, pagosSeleccionados);
    }

    function cobrarCreditoAsignado(idCredito) {
        fetch(`/proyecto-residencia/public/creditos/obtener?id=${idCredito}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error('No se pudo cargar el crédito');
                }
                return response.json();
            })
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.mensaje || 'No se pudo cargar el crédito');
                }

                const pago = obtenerPagoSugerido(data.pagos || []);
                if (!pago) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin pago por cobrar',
                        text: 'Este crédito no tiene letras pendientes por cobrar.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }

                window.location.hash = '#tablaCreditosCobratario';
                abrirModalCobro(idCredito, [pago]);
            })
            .catch((error) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    confirmButtonText: 'Aceptar'
                });
            });
    }

    function cerrarModalCobro() {
        document.getElementById('modalCobroPago').style.display = 'none';
    }

    function actualizarCambioCobro() {
        const abonoCapital = Number(document.getElementById('abonoCapitalCobro')?.value || 0);
        cobroActual.abonoCapital = Math.max(0, abonoCapital);
        cobroActual.montoCobro = cobroActual.montoBaseCobro + (cobroActual.tipoCredito === 'mensual' ? cobroActual.abonoCapital : 0);

        const recibido = Number(document.getElementById('montoRecibidoCobro').value || 0);
        const cambio = recibido - cobroActual.montoCobro;
        const cambioInput = document.getElementById('cambioCobro');
        const btnConfirmar = document.getElementById('btnConfirmarCobro');

        document.getElementById('montoCobro').value = cobroActual.montoCobro.toFixed(2);
        cambioInput.value = `$${formatearMoneda(cambio)}`;
        cambioInput.style.color = cambio < 0 ? 'rgb(239, 68, 68)' : 'rgb(34, 197, 94)';
        btnConfirmar.disabled = recibido < cobroActual.montoCobro;
    }

    function confirmarCobroPago() {
        const montoRecibido = Number(document.getElementById('montoRecibidoCobro').value || 0);
        const metodoPago = document.getElementById('metodoPagoCobro').value;

        if (!metodoPago) {
            Swal.fire({
                icon: 'warning',
                title: 'Forma de pago requerida',
                text: 'Selecciona la forma de pago del cliente antes de continuar.',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        if (montoRecibido < cobroActual.montoCobro) {
            Swal.fire({
                icon: 'warning',
                title: 'Monto insuficiente',
                text: 'El efectivo recibido debe cubrir al menos el monto del pago.',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        const enviarCobro = (confirmarAnticipado) => {
            const formData = new FormData();
            formData.append('idcredito', cobroActual.idCredito);
            formData.append('pagos', JSON.stringify(cobroActual.pagosSeleccionados));
            formData.append('monto_recibido', montoRecibido.toFixed(2));
            formData.append('abono_capital', (cobroActual.tipoCredito === 'mensual' ? cobroActual.abonoCapital : 0).toFixed(2));
            formData.append('confirmar_anticipado', confirmarAnticipado ? '1' : '0');
            formData.append('metodo_pago', metodoPago);

            fetch('/proyecto-residencia/public/creditos/cobrar', {
                    method: 'POST',
                    body: formData,
                })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.mensaje || 'No se pudo registrar el cobro');
                    }

                    const cambio = Number(data.cambio || 0);
                    const idPago = data.idpago;
                    const idCredito = data.idcredito;
                    const pagosCobrados = Array.isArray(data.pagos_cobrados) ? data.pagos_cobrados : [];
                    const recibos = Array.isArray(data.recibos) ? data.recibos : [];
                    const historialIds = Array.isArray(data.historial_ids) ? data.historial_ids : [];
                    const idCreditoActual = cobroActual.idCredito;
                    const ticketGenerado = Boolean(data.ticket_generado);
                    const ticketUrl = data.ticket_url || '';
                    const ticketError = data.ticket_error || '';
                    const ticketTexto = ticketGenerado ?
                        (ticketUrl ? `<br><small><a href="${ticketUrl}" target="_blank" rel="noopener">Ver ticket del cobro</a></small>` : '<br><small>Ticket disponible para consulta.</small>') :
                        (ticketError ? `<br><small>Ticket no disponible: ${ticketError}</small>` : '');

                    cerrarModalCobro();
                    Swal.fire({
                        icon: 'success',
                        title: pagosCobrados.length > 1 ? 'Cobros registrados' : 'Cobro registrado',
                        html: `${pagosCobrados.length > 1 ? `${pagosCobrados.length} letras cobradas.` : 'Pago registrado.'} Cambio: <strong>$${formatearMoneda(cambio)}</strong><br><small>Se abrirá un recibo con el desglose completo.</small>${ticketTexto}`,
                        confirmButtonText: 'Ver recibo'
                    }).then(() => {
                        if (historialIds.length > 0) {
                            const urlRecibo = `/proyecto-residencia/public/creditos/recibo?historial=${historialIds.join(',')}&idcredito=${idCreditoActual}`;
                            window.open(urlRecibo, 'recibo_cobro', 'width=900,height=1000,scrollbars=yes');
                        } else if (recibos.length > 0 && recibos[0].idhistorial) {
                            const urlRecibo = `/proyecto-residencia/public/creditos/recibo?historial=${recibos[0].idhistorial}&idcredito=${idCreditoActual}`;
                            window.open(urlRecibo, 'recibo_cobro', 'width=900,height=1000,scrollbars=yes');
                        } else if (idPago && idCredito) {
                            const urlRecibo = `/proyecto-residencia/public/creditos/recibo?idpago=${idPago}&idcredito=${idCredito}`;
                            window.open(urlRecibo, 'recibo_cobro', 'width=900,height=1000,scrollbars=yes');
                        }

                        verDetalleCredito(idCreditoActual);
                    });
                })
                .catch((error) => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message,
                        confirmButtonText: 'Aceptar'
                    });
                });
        };

        if (cobroActual.tieneAnticipados) {
            cerrarModalCobro();
            Swal.fire({
                icon: 'warning',
                title: 'Confirmar cobro anticipado',
                text: 'Seleccionaste letras con fecha futura. ¿Deseas cobrarlas de todas formas?',
                showCancelButton: true,
                confirmButtonText: 'Sí, cobrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarCobro(true);
                    return;
                }

                document.getElementById('modalCobroPago').style.display = 'flex';
            });
            return;
        }

        enviarCobro(false);
    }

    function cerrarDetalleCredito() {
        window.location.hash = '#tablaCreditosCobratario';
        document.getElementById('detalleCredito').style.display = 'none';
        document.getElementById('tablaCreditosCobratario').style.display = 'block';
        document.getElementById('resumenCobratario').style.display = 'none';
        document.querySelector('.section-header').scrollIntoView({
            behavior: 'smooth'
        });
    }

    // Funciones auxiliares
    function formatearFecha(fecha) {
        if (!fecha) return '-';
        const date = new Date(fecha + 'T00:00:00');
        return date.toLocaleDateString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    function formatearMoneda(numero) {
        return Number(numero).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputRecibido = document.getElementById('montoRecibidoCobro');
        if (inputRecibido) {
            inputRecibido.addEventListener('input', actualizarCambioCobro);
        }

        const inputAbonoCapital = document.getElementById('abonoCapitalCobro');
        if (inputAbonoCapital) {
            inputAbonoCapital.addEventListener('input', actualizarCambioCobro);
        }

        const modal = document.getElementById('modalCobroPago');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    cerrarModalCobro();
                }
            });
        }
    });

    /**
     * Recuperar/regenerar ticket de un pago ya registrado
     */
    function recuperarTicketPago(idCredito, idPago) {
        const ticketUrl = `/proyecto-residencia/public/creditos/ver-ticket?idpago=${idPago}&idcredito=${idCredito}`;
        window.open(ticketUrl, 'ticket_cobro', 'width=560,height=980,scrollbars=yes');
    }
</script>