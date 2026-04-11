<section id="dashboardAvanceCobranza" class="content-section active">
    <?php
    $filtrosAvance = $filtrosAvance ?? [
        'fecha_inicio' => date('Y-m-d'),
        'fecha_fin' => date('Y-m-d'),
        'ids_cobratario' => [],
    ];
    $avanceCobratarios = $avanceCobratarios ?? [
        'resumen' => [],
        'detalle' => [],
        'totales' => [
            'cobros_realizados' => 0,
            'monto_total' => 0,
            'moratorio_total' => 0,
            'cobratarios_con_movimiento' => 0,
        ],
    ];
    $cobratarios = $cobratarios ?? [];

    $idsSeleccionados = array_map('intval', $filtrosAvance['ids_cobratario'] ?? []);
    $queryPdf = [
        'fecha_inicio' => $filtrosAvance['fecha_inicio'] ?? date('Y-m-d'),
        'fecha_fin' => $filtrosAvance['fecha_fin'] ?? date('Y-m-d'),
    ];
    foreach ($idsSeleccionados as $idSel) {
        $queryPdf['cobratarios'][] = $idSel;
    }
    $urlPdf = '/proyecto-residencia/public/dashboard/avance-cobratarios-pdf?' . http_build_query($queryPdf);

    if (!function_exists('moneyDashboardAdmin')) {
        function moneyDashboardAdmin($value): string
        {
            return '$' . number_format((float)$value, 2, '.', ',');
        }
    }
    ?>

    <div class="section-header">
        <h2>Avance de Cobranza</h2>
    </div>

    <section id="avanceCobratariosModulo" class="form-card avance-cobros-module">
        <div class="form-section avance-cobros-header">
            <div class="avance-cobros-title-row">
                <div>
                    <h2>Avance por Cobratarios</h2>
                    <p>Consulta rendimiento diario o por rango de fechas, por uno, varios o todos los cobratarios.</p>
                </div>
                <a href="<?= htmlspecialchars($urlPdf) ?>" class="btn-secondary" target="_blank" rel="noopener">
                    Exportar PDF
                </a>
            </div>

            <form id="avanceCobranzaForm" method="GET" action="/proyecto-residencia/public/dashboard/avance-cobranza" class="form-grid avance-cobros-filters">
                <div class="form-field">
                    <label for="fecha_inicio">Fecha inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars((string)($filtrosAvance['fecha_inicio'] ?? '')) ?>">
                </div>
                <div class="form-field">
                    <label for="fecha_fin">Fecha fin</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars((string)($filtrosAvance['fecha_fin'] ?? '')) ?>">
                </div>
                <div class="form-field span-2">
                    <label>Cobratarios</label>
                    <div class="avance-cobros-picker">
                        <?php foreach ($cobratarios as $cob): ?>
                            <?php $idc = (int)($cob['idcobratario'] ?? 0); ?>
                            <label class="avance-cobros-chip">
                                <input
                                    type="checkbox"
                                    name="cobratarios[]"
                                    value="<?= $idc ?>"
                                    <?= in_array($idc, $idsSeleccionados, true) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars((string)($cob['nombre'] ?? 'Cobratario')) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <small class="avance-cobros-hint">Si no marcas ninguno, se muestran todos.</small>
                </div>
            </form>

            <div class="avance-cobros-actions-bar">
                <a href="/proyecto-residencia/public/dashboard/avance-cobranza" class="btn-secondary">Limpiar</a>
                <button type="submit" form="avanceCobranzaForm" class="btn-primary">Aplicar Filtros</button>
            </div>
        </div>

        <div class="form-section avance-cobros-body">
            <div class="avance-cobros-kpis">
                <article class="avance-cobros-kpi-card">
                    <span class="avance-cobros-kpi-value"><?= (int)($avanceCobratarios['totales']['cobros_realizados'] ?? 0) ?></span>
                    <span class="avance-cobros-kpi-label">Cobros realizados</span>
                </article>
                <article class="avance-cobros-kpi-card">
                    <span class="avance-cobros-kpi-value"><?= moneyDashboardAdmin($avanceCobratarios['totales']['monto_total'] ?? 0) ?></span>
                    <span class="avance-cobros-kpi-label">Monto cobrado</span>
                </article>
                <article class="avance-cobros-kpi-card">
                    <span class="avance-cobros-kpi-value"><?= moneyDashboardAdmin($avanceCobratarios['totales']['moratorio_total'] ?? 0) ?></span>
                    <span class="avance-cobros-kpi-label">Moratorio cobrado</span>
                </article>
                <article class="avance-cobros-kpi-card">
                    <span class="avance-cobros-kpi-value"><?= (int)($avanceCobratarios['totales']['cobratarios_con_movimiento'] ?? 0) ?></span>
                    <span class="avance-cobros-kpi-label">Cobratarios con movimiento</span>
                </article>
            </div>

            <div class="avance-cobros-block">
                <h3>Resumen por Cobratario</h3>
                <div class="avance-cobros-table-wrap">
                    <table class="data-table avance-cobros-table resumen-table">
                        <thead>
                            <tr>
                                <th>Cobratario</th>
                                <th class="is-right">Cobros realizados</th>
                                <th class="is-right">Monto total</th>
                                <th class="is-right">Moratorio total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($avanceCobratarios['resumen'])): ?>
                                <tr>
                                    <td colspan="4" class="is-empty">No hay cobros para el rango seleccionado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($avanceCobratarios['resumen'] as $fila): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($fila['cobratario'] ?? '-')) ?></td>
                                        <td class="is-right"><?= (int)($fila['cobros_realizados'] ?? 0) ?></td>
                                        <td class="is-right"><?= moneyDashboardAdmin($fila['monto_total'] ?? 0) ?></td>
                                        <td class="is-right"><?= moneyDashboardAdmin($fila['moratorio_total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="avance-cobros-block">
                <h3>Detalle de Cobros Realizados</h3>
                <div class="avance-cobros-table-wrap">
                    <table class="data-table avance-cobros-table detalle-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cobratario</th>
                                <th>Cliente</th>
                                <th>Credito</th>
                                <th>Letra</th>
                                <th>Metodo</th>
                                <th class="is-right">Moratorio</th>
                                <th class="is-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($avanceCobratarios['detalle'])): ?>
                                <tr>
                                    <td colspan="8" class="is-empty">Sin detalle de cobros para este filtro.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($avanceCobratarios['detalle'] as $fila): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($fila['fecha_pago'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($fila['cobratario'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($fila['cliente'] ?? '-')) ?></td>
                                        <td>#<?= (int)($fila['idcredito'] ?? 0) ?></td>
                                        <td>#<?= (int)($fila['numero_pago'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars((string)($fila['metodo_pago'] ?? '-')) ?></td>
                                        <td class="is-right"><?= moneyDashboardAdmin($fila['interes_moratorio'] ?? 0) ?></td>
                                        <td class="is-right"><?= moneyDashboardAdmin($fila['monto_pagado'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</section>