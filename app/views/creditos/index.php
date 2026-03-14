<?php
$clienteSeleccionado = $_POST['idpersona'] ?? null;
$clienteSeleccionadoNombre = $_POST['cliente_nombre'] ?? null;
$tipo = $_POST['tipo'] ?? 'diario';
$clientes = $clientes ?? [];
$creditos = $creditos ?? [];
$cobratarios = $cobratarios ?? [];
$configuraciones = $configuraciones ?? [];

// Obtener configuración según el tipo
$config = $configuraciones[$tipo] ?? [];

$monto       = $_POST['monto'] ?? 10000;
$pagos       = $_POST['pagos'] ?? ($config['pagos'] ?? 35);
$interes     = $_POST['interes'] ?? ($config['interes'] ?? 22.5);
$moratorio   = $_POST['moratorio'] ?? ($config['moratorio'] ?? 35);
$fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');

$simular = isset($_POST['simular']);
?>

<section id="creditos" class="content-section">
    <div class="section-header">
        <h2>Gestión de Créditos</h2>
        <button type="button" class="btn-primary" onclick="abrirSimulador(true)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Simular nuevo crédito
        </button>
    </div>

    <!-- Tabla de créditos activos -->
    <div id="tablaCreditos" style="margin-bottom: 30px;">
        <div style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <h3 style="margin: 0 0 15px 0; color: var(--text-primary); font-size: 16px;">Créditos Activos</h3>
            <?php if (empty($creditos)): ?>
                <div style="text-align: center; padding: 30px; color: var(--text-muted); background: var(--bg-tertiary); border-radius: 8px;">
                    <p>📭 No hay créditos registrados aún.</p>
                    <p style="font-size: 13px; margin: 10px 0 0 0;">Haz clic en "Simular Crédito" para crear uno nuevo.</p>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 14px;">
                    <input
                        type="text"
                        id="buscadorCreditosAdmin"
                        placeholder="Buscar crédito por ID, cliente, cobrador, tipo, estado o monto..."
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary); font-size: 14px; box-sizing: border-box;">
                </div>
                <div id="sinResultadosCreditosAdmin" style="display: none; text-align: center; padding: 18px; margin-bottom: 10px; color: var(--text-muted); background: var(--bg-tertiary); border-radius: 8px;">
                    No se encontraron créditos con ese criterio de búsqueda.
                </div>
                <div style="overflow-x: auto; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead style="background: var(--bg-tertiary); position: sticky; top: 0;">
                            <tr>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">ID</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Cliente</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Cobrador</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Monto</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Tipo</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Pagos</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Saldo Pendiente</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Estado</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Fecha</th>
                                <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCreditosAdmin">
                            <?php foreach ($creditos as $credito): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary); font-weight: 500;">#<?= htmlspecialchars($credito['idcredito']) ?></td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary);"><?= htmlspecialchars($credito['cliente']) ?></td>
                                    <td style="padding: 12px; text-align: center; color: var(--text-primary);"><?= htmlspecialchars($credito['cobratario']) ?></td>
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
                                        <?= date('d/m/Y', strtotime($credito['fecha_creacion'])) ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <button type="button" onclick="verDetalleCredito(<?= $credito['idcredito'] ?>)" style="padding: 6px 12px; background: var(--accent-blue); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='rgb(37, 149, 218)'" onmouseout="this.style.background='var(--accent-blue)'">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            Ver Detalle
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

    <!-- Simulador de créditos (escondido por defecto) -->
    <div id="simuladorCreditos" style="display: none;">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2>Simulador de Créditos</h2>
            <button type="button" onclick="cerrarSimulador()" style="padding: 10px 20px; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--radius); cursor: pointer; font-weight: 500; font-size: 14px;">
                Volver
            </button>
        </div>

        <form class="form-card" method="POST" action="/proyecto-residencia/public/creditos">
            <div class="form-section">
                <div class="form-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <h3>Seleccionar Cliente</h3>
                </div>
                <div class="form-grid" style="grid-template-columns: 1fr;">
                    <div class="form-field">
                        <label for="searchCliente">Cliente <span class="required">*</span></label>
                        <div class="search-cliente-wrapper" style="position: relative;">
                            <input
                                type="text"
                                id="searchCliente"
                                placeholder="Escribe el nombre o email del cliente..."
                                autocomplete="off"
                                style="width: 100%;">
                            <input type="hidden" id="idpersona" name="idpersona">
                            <div id="clientesDropdown" class="clientes-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: var(--radius); max-height: 250px; overflow-y: auto; z-index: 100; margin-top: 4px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <h3>Parámetros del Crédito</h3>
                </div>
                <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="form-field">
                        <label for="monto">Monto <span class="required">*</span></label>
                        <input type="number" id="monto" name="monto" step="0.01" value="<?= $monto ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="tipo">Tipo <span class="required">*</span></label>
                        <select id="tipo" name="tipo" required class="form-select" onchange="actualizarConfig()">
                            <option value="diario" <?= $tipo == 'diario' ? 'selected' : '' ?>>Diario</option>
                            <option value="semanal" <?= $tipo == 'semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="mensual" <?= $tipo == 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="pagos">Cantidad de Pagos <span class="required">*</span></label>
                        <input type="number" id="pagos" name="pagos" value="<?= $pagos ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="interes">Interés % <span class="required">*</span></label>
                        <input type="number" id="interes" name="interes" step="0.01" value="<?= $interes ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="moratorio">Moratorio <span class="required">*</span></label>
                        <input type="number" id="moratorio" name="moratorio" value="<?= $moratorio ?>" required>
                    </div>
                    <div id="divFechaInicio" class="form-field">
                        <label for="fecha_inicio">Fecha Inicio <span class="required">*</span></label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $fechaInicio ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="simular" class="btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                        <path d="M4.22 4.22l4.24 4.24m6.08 0l4.24-4.24"></path>
                        <path d="M1 12h6m6 0h6"></path>
                        <path d="M4.22 19.78l4.24-4.24m6.08 0l4.24 4.24"></path>
                    </svg>
                    Simular Crédito
                </button>
            </div>
        </form>

        <div id="resultadoSimulacion" style="margin-top: 40px;<?= $simular ? '' : ' display: none;' ?>">
            <?php if ($simular && $clienteSeleccionado): ?>
                <?php
                switch ($tipo) {
                    case 'diario':
                        $labelPago = 'Pago diario';
                        break;
                    case 'semanal':
                        $labelPago = 'Pago semanal';
                        break;
                    case 'mensual':
                        $labelPago = 'Pago mensual';
                        break;
                    default:
                        $labelPago = 'Pago';
                }

                if ($config['modo'] == 'fijo') {

                    $montoInteres = round($monto * ($interes / 100), 2);
                    $totalPagar  = round($monto + $montoInteres, 2);

                    $capitalBase = round($monto / $pagos, 2);
                    $interesBase = round($montoInteres / $pagos, 2);

                    $saldo = $monto;
                    $fecha = new DateTime($fechaInicio);
                ?>
                    <div class="credito-stats-grid">
                        <div class="stat-box">
                            <p class="stat-label">Capital</p>
                            <p class="stat-value"><?= number_format($monto, 2, '.', ',') ?></p>
                        </div>
                        <div class="stat-box">
                            <p class="stat-label">Interés</p>
                            <p class="stat-value"><?= number_format($montoInteres, 2, '.', ',') ?></p>
                        </div>
                        <div class="stat-box">
                            <p class="stat-label">Total a Pagar</p>
                            <p class="stat-value"><?= number_format($totalPagar, 2, '.', ',') ?></p>
                        </div>
                        <div class="stat-box">
                            <p class="stat-label"><?= $labelPago ?></p>
                            <p class="stat-value"><?= number_format($totalPagar / $pagos, 2, '.', ',') ?></p>
                        </div>
                    </div>

                    <div class="credito-table-container" style="overflow-x: auto; max-height: 500px; border-radius: 8px;">
                        <table class="data-table" style="width: 100%; font-size: 12px;">
                            <thead style="position: sticky; top: 0; background: var(--bg-secondary);">
                                <tr>
                                    <th style="padding: 10px 5px;">#</th>
                                    <th style="padding: 10px 5px;">Fecha</th>
                                    <th style="padding: 10px 5px;">Saldo Inicial</th>
                                    <th style="padding: 10px 5px;">Capital</th>
                                    <th style="padding: 10px 5px;">Interés</th>
                                    <th style="padding: 10px 5px;">Pago</th>
                                    <th style="padding: 10px 5px;">Saldo Final</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = 1; $i <= $pagos; $i++) {
                                    $saldoInicial = $saldo;

                                    if ($i == $pagos) {
                                        $capital = $saldo;
                                        $interesPago = $montoInteres - ($interesBase * ($pagos - 1));
                                    } else {
                                        $capital = $capitalBase;
                                        $interesPago = $interesBase;
                                    }

                                    $pago = round($capital + $interesPago, 2);
                                    $saldo -= $capital;
                                ?>
                                    <tr>
                                        <td style="padding: 8px 5px;"><?= $i ?></td>
                                        <td style="padding: 8px 5px;"><?= $fecha->format('d/m/Y') ?></td>
                                        <td style="padding: 8px 5px;">$<?= number_format($saldoInicial, 2, '.', ',') ?></td>
                                        <td style="padding: 8px 5px;">$<?= number_format($capital, 2, '.', ',') ?></td>
                                        <td style="padding: 8px 5px;">$<?= number_format($interesPago, 2, '.', ',') ?></td>
                                        <td style="padding: 8px 5px; font-weight: 600;">$<?= number_format($pago, 2, '.', ',') ?></td>
                                        <td style="padding: 8px 5px;">$<?= number_format(max(0, $saldo), 2, '.', ',') ?></td>
                                    </tr>
                                <?php
                                    $fecha->add(new DateInterval($config['intervalo']));
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="credito-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarSimulador()">Cancelar</button>
                        <button type="button" class="btn-secondary" onclick="imprimirSimulacion()">Imprimir Simulación</button>
                        <button type="button" class="btn-primary" onclick="guardarCredito()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Guardar Crédito
                        </button>
                    </div>

                    <form id="guardarCreditoForm" method="POST" action="/proyecto-residencia/public/creditos/guardar" style="display: none;">
                        <input type="hidden" name="idpersona" value="<?= htmlspecialchars($clienteSeleccionado) ?>">
                        <input type="hidden" name="idcobratario" value="">
                        <input type="hidden" name="cliente_nombre" value="<?= htmlspecialchars($clienteSeleccionadoNombre) ?>">
                        <input type="hidden" name="monto" value="<?= htmlspecialchars($monto) ?>">
                        <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                        <input type="hidden" name="pagos" value="<?= htmlspecialchars($pagos) ?>">
                        <input type="hidden" name="interes" value="<?= htmlspecialchars($interes) ?>">
                        <input type="hidden" name="moratorio" value="<?= htmlspecialchars($moratorio) ?>">
                        <input type="hidden" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>">
                    </form>
                <?php
                } else {
                    $interesMes = round($monto * ($interes / 100), 2);
                ?>
                    <div class="credito-stats-grid">
                        <div class="stat-box">
                            <p class="stat-label">Saldo</p>
                            <p class="stat-value">$<?= number_format($monto, 2, '.', ',') ?></p>
                        </div>
                        <div class="stat-box">
                            <p class="stat-label">Interés Mensual</p>
                            <p class="stat-value">$<?= number_format($interesMes, 2, '.', ',') ?></p>
                        </div>
                        <div class="stat-box">
                            <p class="stat-label">Pago Mínimo</p>
                            <p class="stat-value">$<?= number_format($interesMes, 2, '.', ',') ?></p>
                        </div>
                        <div class="stat-box">
                            <p class="stat-label">Modo</p>
                            <p class="stat-value">Flexible</p>
                        </div>
                    </div>

                    <div class="credito-info-box">
                        <p>💡 En crédito mensual el cliente puede pagar solo interés o abonar a capital. El cálculo se hace nuevamente después de cada pago.</p>
                    </div>

                    <div class="credito-actions">
                        <button type="button" class="btn-secondary" onclick="cerrarSimulador()">Cancelar</button>
                        <button type="button" class="btn-secondary" onclick="imprimirSimulacion()">Imprimir Simulación</button>
                        <button type="button" class="btn-primary" onclick="guardarCredito()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Guardar Crédito
                        </button>
                    </div>

                    <form id="guardarCreditoForm" method="POST" action="/proyecto-residencia/public/creditos/guardar" style="display: none;">
                        <input type="hidden" name="idpersona" value="<?= htmlspecialchars($clienteSeleccionado) ?>">
                        <input type="hidden" name="idcobratario" value="">
                        <input type="hidden" name="cliente_nombre" value="<?= htmlspecialchars($clienteSeleccionadoNombre) ?>">
                        <input type="hidden" name="monto" value="<?= htmlspecialchars($monto) ?>">
                        <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                        <input type="hidden" name="pagos" value="<?= htmlspecialchars($pagos) ?>">
                        <input type="hidden" name="interes" value="<?= htmlspecialchars($interes) ?>">
                        <input type="hidden" name="moratorio" value="<?= htmlspecialchars($moratorio) ?>">
                        <input type="hidden" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>">
                    </form>
                <?php
                }
                ?>
            <?php elseif ($simular && !$clienteSeleccionado): ?>
                <div style='background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid var(--accent-red);'>
                    <p style='margin: 0; color: var(--accent-red); font-weight: 600; font-size: 13px;'>
                        ⚠️ Debes seleccionar un cliente para simular un crédito
                    </p>
                </div>
            <?php endif; ?>
        </div>
        </form>
    </div>
</section>

<!-- Modal para seleccionar cobratario -->
<div id="modalCobratario" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: var(--bg-primary); padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-primary);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Asignar Cobratario
        </h3>
        <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 14px;">Selecciona el cobratario encargado de cobrar este crédito.</p>

        <div style="margin-bottom: 20px; position: relative;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-primary); font-size: 14px;">Cobratario <span style="color: var(--accent-red);">*</span></label>
            <input
                type="text"
                id="searchCobratarioModal"
                placeholder="Escribe el nombre del cobratario..."
                autocomplete="off"
                style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-secondary); color: var(--text-primary); font-size: 14px; box-sizing: border-box;">
            <div id="cobratariosDropdownModal" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: var(--radius); max-height: 250px; overflow-y: auto; z-index: 1001; margin-top: 4px;">
            </div>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalCobratario()" style="padding: 10px 20px; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary); border-radius: var(--radius); cursor: pointer; font-size: 14px; font-weight: 500;">
                Cancelar
            </button>
            <button type="button" onclick="confirmarCobratario()" style="padding: 10px 20px; background: var(--accent-primary); color: white; border: none; border-radius: var(--radius); cursor: pointer; font-size: 14px; font-weight: 500;">
                Confirmar
            </button>
        </div>
    </div>
</div>

<script>
    // Datos del servidor
    const configuracionesData = <?= json_encode($configuraciones) ?>;
    const clientesData = <?= json_encode($clientes) ?>;
    const cobratariosData = <?= json_encode($cobratarios) ?>;

    // Ejecutar configuración inicial
    document.addEventListener('DOMContentLoaded', function() {
        // Solo actualizar config si no hay valores POST (no es una simulación)
        if (!<?= isset($_POST['simular']) ? 'true' : 'false' ?>) {
            actualizarConfig();
        }
        <?php if ($simular): ?>
            abrirSimulador(false);
        <?php endif; ?>

        inicializarBuscadorCreditosAdmin();
    });

    function inicializarBuscadorCreditosAdmin() {
        const buscador = document.getElementById('buscadorCreditosAdmin');
        const tbody = document.getElementById('tbodyCreditosAdmin');
        const sinResultados = document.getElementById('sinResultadosCreditosAdmin');

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

    function imprimirSimulacion() {
        const resultado = document.getElementById('resultadoSimulacion');
        const cliente = document.getElementById('searchCliente')?.value || 'Cliente no especificado';
        const tipo = document.getElementById('tipo')?.value || '-';
        const monto = document.getElementById('monto')?.value || '0';
        const pagos = document.getElementById('pagos')?.value || '0';
        const interes = document.getElementById('interes')?.value || '0';
        const fechaInicio = document.getElementById('fecha_inicio')?.value || '-';

        if (!resultado || resultado.style.display === 'none' || resultado.innerHTML.trim() === '') {
            Swal.fire({
                title: 'Sin simulación',
                text: 'Primero realiza una simulación para poder imprimirla.',
                icon: 'info',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        const contenido = resultado.cloneNode(true);
        contenido.querySelectorAll('.credito-actions, #guardarCreditoForm').forEach((nodo) => nodo.remove());
        contenido.querySelectorAll('.credito-table-container').forEach((nodo) => {
            nodo.style.maxHeight = 'none';
            nodo.style.overflow = 'visible';
        });
        contenido.querySelectorAll('thead').forEach((nodo) => {
            nodo.style.position = 'static';
            nodo.style.top = 'auto';
        });

        const ventana = window.open('', 'impresion_simulacion', 'width=1000,height=900,scrollbars=yes');
        if (!ventana) {
            Swal.fire({
                title: 'No se pudo abrir la impresión',
                text: 'Revisa que tu navegador permita ventanas emergentes.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        ventana.document.write(`
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Simulación de Crédito</title>
                <style>
                    body { font-family: Arial, sans-serif; color: #111827; padding: 24px; }
                    h1 { margin: 0 0 8px; font-size: 22px; }
                    .sub { margin: 0 0 18px; color: #4b5563; font-size: 13px; }
                    .datos { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 8px 20px; margin-bottom: 16px; }
                    .dato { font-size: 13px; }
                    .dato b { color: #111827; }
                    .credito-stats-grid { display: grid; grid-template-columns: repeat(4, minmax(120px, 1fr)); gap: 10px; margin: 14px 0 18px; }
                    .stat-box { border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; }
                    .stat-label { margin: 0 0 4px; font-size: 11px; color: #6b7280; text-transform: uppercase; }
                    .stat-value { margin: 0; font-size: 15px; font-weight: 700; }
                    table { width: 100%; border-collapse: collapse; font-size: 12px; }
                    th, td { border: 1px solid #e5e7eb; padding: 7px; text-align: center; }
                    th { background: #f3f4f6; font-weight: 700; }
                    .credito-table-container { max-height: none !important; overflow: visible !important; }
                    tr { page-break-inside: avoid; }
                    .credito-info-box { padding: 10px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; font-size: 12px; }
                    @media print { body { padding: 0; } }
                </style>
            </head>
            <body>
                <h1>Simulación de Crédito</h1>
                <p class="sub">Documento informativo para explicar la estructura del crédito y sus pagos.</p>

                <div class="datos">
                    <div class="dato"><b>Cliente:</b> ${cliente}</div>
                    <div class="dato"><b>Tipo:</b> ${tipo}</div>
                    <div class="dato"><b>Monto:</b> $${Number(monto).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    <div class="dato"><b>Pagos:</b> ${pagos}</div>
                    <div class="dato"><b>Interés:</b> ${interes}%</div>
                    <div class="dato"><b>Fecha inicio:</b> ${fechaInicio}</div>
                </div>

                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 10px 0 16px;">

                ${contenido.innerHTML}
            </body>
            </html>
        `);

        ventana.document.close();
        ventana.focus();
        ventana.print();
    }

    function actualizarConfig() {
        const tipo = document.getElementById('tipo').value;
        if (configuracionesData[tipo]) {
            const pagosInput = document.getElementById('pagos');
            const interesInput = document.getElementById('interes');
            const moratorioInput = document.getElementById('moratorio');

            // Al cambiar el tipo, se cargan sus valores por defecto.
            pagosInput.value = configuracionesData[tipo].pagos;
            interesInput.value = configuracionesData[tipo].interes;
            moratorioInput.value = configuracionesData[tipo].moratorio;
        }

        // Mostrar/ocultar campo de fecha según el tipo
        const divFecha = document.getElementById('divFechaInicio');
        if (divFecha) {
            divFecha.style.display = tipo === 'mensual' ? 'none' : 'block';
        }
    }

    // Búsqueda de clientes
    const searchInput = document.getElementById('searchCliente');
    const dropdown = document.getElementById('clientesDropdown');
    const idpersonaInput = document.getElementById('idpersona');

    // Recuperar cliente seleccionado si existe después del POST
    <?php if ($clienteSeleccionado && $clienteSeleccionadoNombre): ?>
        searchInput.value = '<?= htmlspecialchars($clienteSeleccionadoNombre) ?>';
        idpersonaInput.value = '<?= htmlspecialchars($clienteSeleccionado) ?>';
    <?php endif; ?>

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        if (searchTerm.length === 0) {
            dropdown.style.display = 'none';
            idpersonaInput.value = '';
            return;
        }

        const resultados = clientesData.filter(cliente => {
            const nombre = cliente.nombre.toLowerCase();
            const email = cliente.email.toLowerCase();
            return nombre.includes(searchTerm) || email.includes(searchTerm);
        });

        if (resultados.length === 0) {
            dropdown.innerHTML = '<div style="padding: 10px; color: var(--text-muted); text-align: center;">No se encontraron clientes</div>';
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = resultados.map(cliente => `
            <div class="cliente-item" data-id="${cliente.idcliente}" style="padding: 10px 12px; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s ease;" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='transparent'">
                <div style="font-weight: 500; color: var(--text-primary);">${cliente.nombre}</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">${cliente.email}</div>
            </div>
        `).join('');

        dropdown.style.display = 'block';

        // Agregar eventos click a los items
        document.querySelectorAll('.cliente-item').forEach(item => {
            item.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const cliente = clientesData.find(c => c.idcliente == id);
                const clienteNombre = `${cliente.nombre} - ${cliente.email}`;
                searchInput.value = clienteNombre;
                idpersonaInput.value = id;
                dropdown.style.display = 'none';

                // Guardar el nombre del cliente en un campo oculto para recuperarlo después del POST
                let clienteNombreInput = document.querySelector('input[name="cliente_nombre"]');
                if (!clienteNombreInput) {
                    clienteNombreInput = document.createElement('input');
                    clienteNombreInput.type = 'hidden';
                    clienteNombreInput.name = 'cliente_nombre';
                    document.querySelector('form').appendChild(clienteNombreInput);
                }
                clienteNombreInput.value = clienteNombre;
            });
        });
    });

    // Cerrar dropdown cuando se hace click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-cliente-wrapper')) {
            dropdown.style.display = 'none';
        }
    });

    // Variables para el modal de cobratario
    let cobratarioSeleccionado = null;
    let cobratarioSeleccionadoNombre = null;

    // Función para abrir el modal de cobratario
    function abrirModalCobratario() {
        cobratarioSeleccionado = null;
        cobratarioSeleccionadoNombre = null;
        document.getElementById('searchCobratarioModal').value = '';
        document.getElementById('cobratariosDropdownModal').style.display = 'none';
        document.getElementById('modalCobratario').style.display = 'flex';
    }

    // Función para cerrar el modal
    function cerrarModalCobratario() {
        document.getElementById('modalCobratario').style.display = 'none';
        cobratarioSeleccionado = null;
        cobratarioSeleccionadoNombre = null;
    }

    // Búsqueda de cobratarios en el modal
    document.getElementById('searchCobratarioModal').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const dropdown = document.getElementById('cobratariosDropdownModal');

        if (searchTerm.length === 0) {
            dropdown.style.display = 'none';
            cobratarioSeleccionado = null;
            cobratarioSeleccionadoNombre = null;
            return;
        }

        const resultados = cobratariosData.filter(cobratario => {
            const nombre = cobratario.nombre.toLowerCase();
            const email = cobratario.email.toLowerCase();
            return nombre.includes(searchTerm) || email.includes(searchTerm);
        });

        if (resultados.length === 0) {
            dropdown.innerHTML = '<div style="padding: 10px; color: var(--text-muted); text-align: center;">No se encontraron cobratarios</div>';
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = resultados.map(cobratario => `
            <div class="cobratario-item" data-id="${cobratario.idcobratario}" style="padding: 10px 12px; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s ease;" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='transparent'">
                <div style="font-weight: 500; color: var(--text-primary);">${cobratario.nombre}</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">${cobratario.email}</div>
            </div>
        `).join('');

        dropdown.style.display = 'block';

        // Agregar eventos click a los items
        document.querySelectorAll('.cobratario-item').forEach(item => {
            item.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const cobratario = cobratariosData.find(c => c.idcobratario == id);
                cobratarioSeleccionado = id;
                cobratarioSeleccionadoNombre = `${cobratario.nombre} - ${cobratario.email}`;
                document.getElementById('searchCobratarioModal').value = cobratarioSeleccionadoNombre;
                dropdown.style.display = 'none';
            });
        });
    });

    // Cerrar dropdown del modal si se hace click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#searchCobratarioModal') && !e.target.closest('#cobratariosDropdownModal')) {
            document.getElementById('cobratariosDropdownModal').style.display = 'none';
        }
    });

    // Función para confirmar la selección del cobratario
    function confirmarCobratario() {
        if (!cobratarioSeleccionado) {
            alert('Por favor, selecciona un cobratario');
            return;
        }

        // Actualizar el campo oculto con el id del cobratario
        const form = document.getElementById('guardarCreditoForm');
        form.querySelector('input[name="idcobratario"]').value = cobratarioSeleccionado;

        // Cerrar el modal
        cerrarModalCobratario();

        // Enviar el formulario
        enviarFormularioCredito();
    }

    // Función para guardar el crédito (abre modal)
    function guardarCredito() {
        const idpersona = document.getElementById('idpersona')?.value || idpersonaInput.value;
        const searchCliente = document.getElementById('searchCliente').value;

        if (!idpersona) {
            alert('Por favor, selecciona un cliente');
            return;
        }

        // Actualizar valores del formulario oculto antes de abrir modal
        const form = document.getElementById('guardarCreditoForm');
        form.querySelector('input[name="idpersona"]').value = idpersona;
        form.querySelector('input[name="monto"]').value = document.getElementById('monto').value;
        form.querySelector('input[name="tipo"]').value = document.getElementById('tipo').value;
        form.querySelector('input[name="pagos"]').value = document.getElementById('pagos').value;
        form.querySelector('input[name="interes"]').value = document.getElementById('interes').value;
        form.querySelector('input[name="moratorio"]').value = document.getElementById('moratorio').value;
        form.querySelector('input[name="fecha_inicio"]').value = document.getElementById('fecha_inicio').value;
        form.querySelector('input[name="cliente_nombre"]').value = searchCliente;

        // Abrir modal para seleccionar cobratario
        abrirModalCobratario();
    }

    // Función para enviar el formulario
    function enviarFormularioCredito(confirmadoCreditoActivo = false) {
        // Usar fetch para enviar y mostrar respuesta sin recargar
        const form = document.getElementById('guardarCreditoForm');
        let confirmarInput = form.querySelector('input[name="confirmar_credito_activo"]');
        if (!confirmarInput) {
            confirmarInput = document.createElement('input');
            confirmarInput.type = 'hidden';
            confirmarInput.name = 'confirmar_credito_activo';
            form.appendChild(confirmarInput);
        }
        confirmarInput.value = confirmadoCreditoActivo ? '1' : '0';

        const formData = new FormData(form);
        fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.mensaje,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        limpiarFormulario();
                    });
                } else if (data.requiere_confirmacion && !confirmadoCreditoActivo) {
                    const saldoPendiente = Number(data?.credito_activo?.saldo_pendiente || 0).toFixed(2);
                    const idCreditoActivo = data?.credito_activo?.idcredito || 'N/A';

                    Swal.fire({
                        title: 'Cliente con crédito activo',
                        text: `${data.mensaje} (Crédito #${idCreditoActivo}, saldo pendiente: $${saldoPendiente})`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, otorgar otro crédito',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            enviarFormularioCredito(true);
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.mensaje,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Error al guardar el crédito: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    }

    // Funciones para abrir/cerrar simulador
    function abrirSimulador(limpiar = false) {
        document.getElementById('tablaCreditos').style.display = 'none';
        document.getElementById('simuladorCreditos').style.display = 'block';
        if (limpiar) {
            limpiarTodo();
            ocultarResultado();
        }
        document.querySelector('.section-header').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function cerrarSimulador() {
        document.getElementById('simuladorCreditos').style.display = 'none';
        document.getElementById('tablaCreditos').style.display = 'block';
        limpiarTodo();
        ocultarResultado();
        // Recargar la página para reiniciar valores
        window.location.href = '/proyecto-residencia/public/creditos';
    }

    function limpiarTodo() {
        const form = document.querySelector('#simuladorCreditos form');
        if (form) {
            form.reset();
        }
        document.getElementById('searchCliente').value = '';
        document.getElementById('idpersona').value = '';
        if (searchInput) searchInput.value = '';
        if (idpersonaInput) idpersonaInput.value = '';
        cobratarioSeleccionado = null;
        cobratarioSeleccionadoNombre = null;
        if (dropdown) dropdown.style.display = 'none';
    }

    function ocultarResultado() {
        const resultado = document.getElementById('resultadoSimulacion');
        if (!resultado) {
            return;
        }
        resultado.innerHTML = '';
        resultado.style.display = 'none';
    }

    function limpiarFormulario() {
        limpiarTodo();
        // Redirigir a la página de créditos después de guardar para reiniciar valores
        window.location.href = '/proyecto-residencia/public/creditos';
    }

    // Funciones para ver detalle del crédito
    function verDetalleCredito(idCredito) {
        // Ocultar tabla de créditos y simulador
        document.getElementById('tablaCreditos').style.display = 'none';
        document.getElementById('simuladorCreditos').style.display = 'none';
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
        let labelPago = 'Pago';
        switch (credito.tipo.toLowerCase()) {
            case 'diario':
                labelPago = 'Pago diario';
                break;
            case 'semanal':
                labelPago = 'Pago semanal';
                break;
            case 'mensual':
                labelPago = 'Pago mensual';
                break;
        }

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

        // Mostrar tabla de pagos
        let tablaHTML = `
            <div class="credito-table-container" style="overflow-x: auto; max-height: 500px; border-radius: 8px;">
                <table class="data-table" style="width: 100%; font-size: 12px;">
                    <thead style="position: sticky; top: 0; background: var(--bg-secondary);">
                        <tr>
                            <th style="padding: 10px 5px;">#</th>
                            <th style="padding: 10px 5px;">Fecha</th>
                            <th style="padding: 10px 5px;">Saldo Inicial</th>
                            <th style="padding: 10px 5px;">Capital</th>
                            <th style="padding: 10px 5px;">Interés</th>
                            <th style="padding: 10px 5px;">Pago</th>
                            <th style="padding: 10px 5px;">Saldo Final</th>
                            <th style="padding: 10px 5px;">Estado</th>
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
            const estiloFila = obtenerEstiloFilaPagoAdmin(pago);

            tablaHTML += `
                <tr style="border-bottom: 1px solid var(--border-color); ${estiloFila}">
                    <td style="padding: 8px 5px;">${pago.numero_pago}</td>
                    <td style="padding: 8px 5px;">${formatearFecha(pago.fecha_programada)}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(saldoInicial)}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(pago.capital_programado)}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(pago.interes_programado)}</td>
                    <td style="padding: 8px 5px; font-weight: 600;">$${formatearMoneda(pago.monto_programado)}</td>
                    <td style="padding: 8px 5px;">$${formatearMoneda(pago.saldo_vivo)}</td>
                    <td style="padding: 8px 5px; text-align: center;">${estadoBadge}</td>
                </tr>
            `;
        });

        tablaHTML += `
                    </tbody>
                </table>
            </div>
        `;
        document.getElementById('tablaPagos').innerHTML = tablaHTML;
    }

    function obtenerFechaHoySinHoraAdmin() {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        return hoy;
    }

    function esPagoDelDiaAdmin(pago) {
        const estado = (pago.estado || '').toLowerCase();
        if (estado !== 'pendiente') {
            return false;
        }

        const fecha = new Date(`${pago.fecha_programada}T00:00:00`);
        return fecha.getTime() === obtenerFechaHoySinHoraAdmin().getTime();
    }

    function obtenerEstiloFilaPagoAdmin(pago) {
        const estado = (pago.estado || '').toLowerCase();

        if (estado === 'pagado') {
            return 'background: rgba(34, 197, 94, 0.12);';
        }

        if (estado === 'vencido' || estado === 'atrasado') {
            return 'background: rgba(239, 68, 68, 0.12);';
        }

        if (esPagoDelDiaAdmin(pago)) {
            return 'background: rgba(59, 130, 246, 0.14);';
        }

        return '';
    }

    function cerrarDetalleCredito() {
        document.getElementById('detalleCredito').style.display = 'none';
        document.getElementById('tablaCreditos').style.display = 'block';
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
</script>
<style>
    #creditos {
        overflow-x: hidden;
    }

    #creditos .form-card,
    #creditos .form-section,
    #creditos .form-grid,
    #creditos .form-field {
        min-width: 0;
    }

    #creditos .form-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }

    #creditos input,
    #creditos select {
        width: 100%;
        min-width: 0;
    }

    .search-cliente-wrapper {
        position: relative;
    }

    .search-cliente-wrapper input {
        padding: 0.625rem 0.875rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        color: var(--text-primary);
        font-size: 0.875rem;
        transition: var(--transition);
    }

    .search-cliente-wrapper input:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1);
        outline: none;
    }

    .clientes-dropdown {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        animation: slideDown 0.2s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>