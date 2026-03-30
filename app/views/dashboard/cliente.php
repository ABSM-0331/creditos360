<?php
$creditosCliente = $creditosCliente ?? [];
$resumenCliente = $resumenCliente ?? [
    'creditos_activos' => 0,
    'saldo_total' => 0.0,
    'pagos_pendientes' => 0,
    'pagos_vencidos' => 0,
    'monto_proximo_pago' => 0.0,
];

if (!function_exists('moneyCliente')) {
    function moneyCliente($valor): string
    {
        return '$' . number_format((float)$valor, 2, '.', ',');
    }
}

if (!function_exists('fechaCliente')) {
    function fechaCliente(?string $fecha): string
    {
        if (!$fecha) {
            return '-';
        }

        try {
            return (new DateTime($fecha))->format('d/m/Y');
        } catch (Throwable $e) {
            return '-';
        }
    }
}

if (!function_exists('diasTextoCliente')) {
    function diasTextoCliente(?string $fecha): string
    {
        if (!$fecha) {
            return 'Sin fecha definida';
        }

        try {
            $hoy = new DateTime('today');
            $programada = new DateTime($fecha);
            $programada->setTime(0, 0, 0);

            $dias = (int)$hoy->diff($programada)->days;

            if ($programada > $hoy) {
                return 'Te toca pagar en ' . $dias . ' día(s)';
            }
            if ($programada < $hoy) {
                return 'Atraso de ' . $dias . ' día(s)';
            }

            return 'Te toca pagar hoy';
        } catch (Throwable $e) {
            return 'Sin fecha definida';
        }
    }
}
?>

<section class="content-section" id="dashboardCliente">
    <div class="section-header">
        <h2>Mis Créditos Activos</h2>
    </div>

    <div class="stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= (int)$resumenCliente['creditos_activos'] ?></span>
                <span class="stat-label">Créditos Activos</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="6" x2="12" y2="12"></line>
                    <line x1="12" y1="12" x2="16" y2="14"></line>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= (int)$resumenCliente['pagos_pendientes'] ?></span>
                <span class="stat-label">Pagos Pendientes</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 1v22"></path>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" style="font-size: 1.2rem;"><?= moneyCliente($resumenCliente['monto_proximo_pago']) ?></span>
                <span class="stat-label">Total Próximo Pago</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"></path>
                    <path d="M12 4h9"></path>
                    <path d="M4 9h16"></path>
                    <path d="M4 15h16"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value" style="font-size: 1.2rem;"><?= moneyCliente($resumenCliente['saldo_total']) ?></span>
                <span class="stat-label">Saldo Total Pendiente</span>
            </div>
        </div>
    </div>

    <?php if (empty($creditosCliente)): ?>
        <div class="welcome-card">
            <h2>No tienes créditos activos</h2>
            <p>Cuando tengas un crédito activo, aquí podrás ver cuánto te toca pagar, en qué fecha y si se aplicó moratorio por atraso.</p>
        </div>
    <?php else: ?>
        <div class="cliente-creditos-lista">
            <?php foreach ($creditosCliente as $credito): ?>
                <?php
                $proximo = $credito['proximo_pago'] ?? null;
                $pendientes = $credito['pagos_pendientes_detalle'] ?? [];
                $moratorioActual = (float)($credito['moratorio_actual'] ?? 0);
                $moratorioBase = (float)($credito['moratorio'] ?? 0);
                $mensajeDias = diasTextoCliente($proximo['fecha_programada'] ?? null);
                $esAtrasado = stripos($mensajeDias, 'Atraso') !== false;
                ?>
                <details class="cliente-credito-card">
                    <summary class="cliente-credito-summary">
                        <div class="cliente-credito-summary-main">
                            <h3>Crédito #<?= (int)$credito['idcredito'] ?> - <?= htmlspecialchars((string)$credito['tipo_label']) ?></h3>
                            <p><?= htmlspecialchars($mensajeDias) ?></p>
                        </div>
                        <div class="cliente-credito-summary-metrics">
                            <div>
                                <span>Saldo</span>
                                <strong><?= moneyCliente($credito['saldo_pendiente'] ?? 0) ?></strong>
                            </div>
                            <div>
                                <span>Próximo pago</span>
                                <strong><?= moneyCliente($credito['monto_proximo_pago'] ?? 0) ?></strong>
                            </div>
                            <span class="badge active">Activo</span>
                        </div>
                    </summary>

                    <div class="cliente-credito-detalle">
                        <div class="credito-stats-grid" style="margin-bottom: 14px;">
                            <div class="stat-box">
                                <p class="stat-label">Saldo Pendiente</p>
                                <p class="stat-value"><?= moneyCliente($credito['saldo_pendiente'] ?? 0) ?></p>
                            </div>
                            <div class="stat-box">
                                <p class="stat-label">Próxima Fecha</p>
                                <p class="stat-value"><?= fechaCliente($proximo['fecha_programada'] ?? null) ?></p>
                            </div>
                            <div class="stat-box">
                                <p class="stat-label">Monto Próximo Pago</p>
                                <p class="stat-value"><?= moneyCliente($credito['monto_proximo_pago'] ?? 0) ?></p>
                            </div>
                            <div class="stat-box">
                                <p class="stat-label">Moratorio por atraso</p>
                                <p class="stat-value" style="color: var(--accent-red);"><?= moneyCliente($moratorioBase) ?></p>
                            </div>
                        </div>

                        <div style="padding: 12px; border-radius: 8px; border: 1px solid <?= $esAtrasado ? 'rgba(239, 68, 68, 0.35)' : 'var(--border-color)' ?>; background: <?= $esAtrasado ? 'rgba(239, 68, 68, 0.08)' : 'var(--bg-tertiary)' ?>; margin-bottom: 12px;">
                            <p style="margin: 0 0 6px 0; color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($mensajeDias) ?></p>
                            <p style="margin: 0; color: var(--text-secondary); font-size: 13px;">
                                Si no pagas a tiempo, se aplicará moratorio de <strong><?= moneyCliente($moratorioBase) ?></strong> por periodo vencido.
                                <?php if ($moratorioActual > 0): ?>
                                    Moratorio acumulado para tu próxima letra hoy: <strong style="color: var(--accent-red);"><?= moneyCliente($moratorioActual) ?></strong>.
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="credito-table-container" style="max-height: 360px;">
                            <table class="data-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Monto Base</th>
                                        <th>Moratorio</th>
                                        <th>Total a pagar</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($pendientes, 0, 12) as $pago): ?>
                                        <?php
                                        $estadoPago = strtolower((string)($pago['estado'] ?? 'pendiente'));
                                        $esVencido = ($estadoPago === 'vencido' || $estadoPago === 'atrasado');
                                        ?>
                                        <tr>
                                            <td><?= (int)($pago['numero_pago'] ?? 0) ?></td>
                                            <td><?= fechaCliente($pago['fecha_programada'] ?? null) ?></td>
                                            <td><?= moneyCliente($pago['monto_programado'] ?? 0) ?></td>
                                            <td style="color: <?= ((float)($pago['recargo_moratorio'] ?? 0) > 0 ? 'var(--accent-red)' : 'var(--text-secondary)') ?>; font-weight: 600;">
                                                <?= moneyCliente($pago['recargo_moratorio'] ?? 0) ?>
                                            </td>
                                            <td style="font-weight: 700;"><?= moneyCliente($pago['monto_cobro_actual'] ?? $pago['monto_programado'] ?? 0) ?></td>
                                            <td>
                                                <span class="badge <?= $esVencido ? 'pending' : 'inactive' ?>">
                                                    <?= strtoupper(htmlspecialchars((string)($pago['estado'] ?? 'pendiente'))) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>