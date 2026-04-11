<?php
class DashboardController
{
    private CreditosRepository $creditosRepo;
    private UsuarioRepository $usuarioRepo;

    public function __construct()
    {
        $this->creditosRepo = new CreditosRepository();
        $this->usuarioRepo = new UsuarioRepository();
    }

    public function index(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 1) {
            if ($rol === 2) {
                header('location: /proyecto-residencia/public/dashboard-cliente');
            } elseif ($rol === 3) {
                header('location: /proyecto-residencia/public/dashboard-cobratario');
            } else {
                header('location: /proyecto-residencia/public/login');
            }
            exit;
        }

        // Obtener estadísticas para el dashboard
        $estadisticas = $this->creditosRepo->obtenerEstadisticasDashboard();

        $view = __DIR__ . '/../views/dashboard/index.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function avanceCobranza(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 1) {
            header('location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $filtrosAvance = $this->resolverFiltrosAvanceCobratarios();
        $cobratarios = $this->usuarioRepo->obtenerCobratarios();
        $avanceCobratarios = $this->creditosRepo->obtenerAvanceCobrosCobratarios(
            $filtrosAvance['fecha_inicio'],
            $filtrosAvance['fecha_fin'],
            $filtrosAvance['ids_cobratario']
        );

        $view = __DIR__ . '/../views/dashboard/avance-cobranza.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function exportarAvanceCobratariosPdf(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 1) {
            header('location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $filtrosAvance = $this->resolverFiltrosAvanceCobratarios();
        $cobratarios = $this->usuarioRepo->obtenerCobratarios();
        $avanceCobratarios = $this->creditosRepo->obtenerAvanceCobrosCobratarios(
            $filtrosAvance['fecha_inicio'],
            $filtrosAvance['fecha_fin'],
            $filtrosAvance['ids_cobratario']
        );

        $html = $this->construirHtmlPdfAvanceCobratarios($filtrosAvance, $cobratarios, $avanceCobratarios);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'avance-cobranza-' . $filtrosAvance['fecha_inicio'] . '-a-' . $filtrosAvance['fecha_fin'] . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    public function cliente(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 2) {
            header('location: /proyecto-residencia/public/dashboard');
            exit;
        }

        $idCliente = (int)($_SESSION['idpersona'] ?? 0);
        $creditosCliente = [];
        $resumenCliente = [
            'creditos_activos' => 0,
            'saldo_total' => 0.0,
            'pagos_pendientes' => 0,
            'pagos_vencidos' => 0,
            'monto_proximo_pago' => 0.0,
        ];

        if ($idCliente > 0) {
            $todos = $this->creditosRepo->obtenerCreditosCliente($idCliente);
            foreach ($todos as $credito) {
                $estado = strtolower((string)($credito['estado'] ?? ''));
                $saldo = (float)($credito['saldo_pendiente'] ?? 0);
                if ($estado !== 'activo' || $saldo <= 0) {
                    continue;
                }

                $pagos = $this->creditosRepo->obtenerPagosCredito((int)$credito['idcredito']);
                $pendientes = array_values(array_filter($pagos, static function (array $pago): bool {
                    return strtolower((string)($pago['estado'] ?? '')) !== 'pagado';
                }));

                $proximoPago = $pendientes[0] ?? null;
                $cantidadVencidos = 0;
                foreach ($pendientes as $pagoPendiente) {
                    $estadoPago = strtolower((string)($pagoPendiente['estado'] ?? ''));
                    if ($estadoPago === 'vencido' || $estadoPago === 'atrasado') {
                        $cantidadVencidos++;
                    }
                }

                $montoProximo = $proximoPago ? (float)($proximoPago['monto_cobro_actual'] ?? $proximoPago['monto_programado'] ?? 0) : 0.0;
                $moratorioActual = $proximoPago ? (float)($proximoPago['recargo_moratorio'] ?? 0) : 0.0;

                $credito['tipo_label'] = $this->formatearTipo((string)($credito['tipo'] ?? ''));
                $credito['proximo_pago'] = $proximoPago;
                $credito['pagos_pendientes_detalle'] = $pendientes;
                $credito['cantidad_pendientes'] = count($pendientes);
                $credito['cantidad_vencidos'] = $cantidadVencidos;
                $credito['monto_proximo_pago'] = round($montoProximo, 2);
                $credito['moratorio_actual'] = round($moratorioActual, 2);

                $creditosCliente[] = $credito;

                $resumenCliente['creditos_activos']++;
                $resumenCliente['saldo_total'] += $saldo;
                $resumenCliente['pagos_pendientes'] += count($pendientes);
                $resumenCliente['pagos_vencidos'] += $cantidadVencidos;
                $resumenCliente['monto_proximo_pago'] += $montoProximo;
            }
        }

        $resumenCliente['saldo_total'] = round((float)$resumenCliente['saldo_total'], 2);
        $resumenCliente['monto_proximo_pago'] = round((float)$resumenCliente['monto_proximo_pago'], 2);

        $view = __DIR__ . '/../views/dashboard/cliente.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function cobratario(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('location: /proyecto-residencia/public/login');
            exit;
        }

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
        if ($rol !== 1 && $rol !== 3) {
            header('location: /proyecto-residencia/public/dashboard');
            exit;
        }

        // Obtener el ID del cobratario desde la sesión (idpersona)
        $idCobratario = $_SESSION['idpersona'] ?? null;

        // Obtener créditos para cobranza
        $creditos = [];
        if ($rol === 1) {
            $creditos = $this->creditosRepo->obtenerTodos();
        } elseif ($idCobratario) {
            $creditos = $this->creditosRepo->obtenerCreditosCobratario($idCobratario);
        }

        $totalCreditosAsignados = count($creditos);
        $creditosActivos = 0;
        $clientesUnicos = [];
        $totalCobrado = 0.0;
        $saldoPendienteTotal = 0.0;

        if ($rol === 1) {
            foreach ($creditos as $credito) {
                $totalPagar = (float)($credito['total_pagos'] ?? 0);
                $saldoPendiente = (float)($credito['saldo_pendiente'] ?? 0);
                $totalCobrado += max(0, $totalPagar - $saldoPendiente);
            }
        } elseif ($idCobratario) {
            $totalCobrado = $this->creditosRepo->obtenerTotalCobradoCobratario($idCobratario);
        }

        foreach ($creditos as $credito) {
            if (($credito['estado'] ?? '') === 'activo') {
                $creditosActivos++;
            }

            if (isset($credito['idcliente'])) {
                $clientesUnicos[(string)$credito['idcliente']] = true;
            }

            $saldoPendiente = (float)($credito['saldo_pendiente'] ?? 0);
            $saldoPendienteTotal += $saldoPendiente;
        }

        $resumenCobratario = [
            'totalCreditosAsignados' => $totalCreditosAsignados,
            'creditosActivos' => $creditosActivos,
            'clientesAsignados' => count($clientesUnicos),
            'totalCobrado' => $totalCobrado,
            'saldoPendienteTotal' => $saldoPendienteTotal,
        ];

        $view = __DIR__ . '/../views/dashboard/cobratario.php';
        require __DIR__ . '/../views/layouts/app.php';
    }

    private function formatearTipo(string $tipo): string
    {
        $normalizado = str_replace(['_', '-'], ' ', strtolower(trim($tipo)));
        return ucwords($normalizado);
    }

    private function resolverFiltrosAvanceCobratarios(): array
    {
        $hoy = date('Y-m-d');
        $fechaInicioIngresada = $_GET['fecha_inicio'] ?? null;
        $fechaFinIngresada = $_GET['fecha_fin'] ?? null;
        $fechaInicio = (string)($fechaInicioIngresada ?? $hoy);
        $fechaFin = (string)($fechaFinIngresada ?? $hoy);

        if ($fechaInicioIngresada === null && $fechaFinIngresada === null) {
            if ($this->creditosRepo->contarCobrosEnRango($hoy, $hoy) === 0) {
                $rangoHistorial = $this->creditosRepo->obtenerRangoFechasHistorialPagos();
                $ultimaFecha = (string)($rangoHistorial['fecha_fin'] ?? $hoy);
                if ($ultimaFecha !== '') {
                    $fechaInicio = $ultimaFecha;
                    $fechaFin = $ultimaFecha;
                }
            }
        }

        if (!$this->esFechaValida($fechaInicio)) {
            $fechaInicio = $hoy;
        }
        if (!$this->esFechaValida($fechaFin)) {
            $fechaFin = $hoy;
        }
        if ($fechaInicio > $fechaFin) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        $idsCobratario = [];
        $rawCobratarios = $_GET['cobratarios'] ?? [];
        if (!is_array($rawCobratarios)) {
            $rawCobratarios = [$rawCobratarios];
        }

        foreach ($rawCobratarios as $id) {
            $valor = (int)$id;
            if ($valor > 0) {
                $idsCobratario[] = $valor;
            }
        }

        $idsCobratario = array_values(array_unique($idsCobratario));

        return [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'ids_cobratario' => $idsCobratario,
        ];
    }

    private function esFechaValida(string $fecha): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    private function construirHtmlPdfAvanceCobratarios(array $filtros, array $cobratarios, array $avance): string
    {
        $resumen = $avance['resumen'] ?? [];
        $detalle = $avance['detalle'] ?? [];
        $totales = $avance['totales'] ?? [];

        $filtroTexto = 'Todos';
        if (!empty($filtros['ids_cobratario'])) {
            $nombres = [];
            $ids = array_map('intval', $filtros['ids_cobratario']);
            foreach ($cobratarios as $cob) {
                $id = (int)($cob['idcobratario'] ?? 0);
                if (in_array($id, $ids, true)) {
                    $nombres[] = trim((string)($cob['nombre'] ?? ''));
                }
            }
            $filtroTexto = !empty($nombres) ? implode(', ', $nombres) : 'Selección manual';
        }

        $html = '<html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
            h1 { margin: 0 0 6px 0; font-size: 20px; }
            h2 { margin: 18px 0 8px 0; font-size: 14px; }
            p { margin: 4px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            th, td { border: 1px solid #d0d7de; padding: 6px; text-align: left; }
            th { background: #f3f4f6; }
            .right { text-align: right; }
            .muted { color: #555; }
            .kpis { margin-top: 10px; }
            .kpis td { width: 25%; }
        </style></head><body>';

        $html .= '<h1>Reporte de Avance de Cobranza</h1>';
        $html .= '<p class="muted">Rango: <strong>' . htmlspecialchars($filtros['fecha_inicio']) . '</strong> a <strong>' . htmlspecialchars($filtros['fecha_fin']) . '</strong></p>';
        $html .= '<p class="muted">Cobratarios: <strong>' . htmlspecialchars($filtroTexto) . '</strong></p>';
        $html .= '<p class="muted">Generado: ' . date('Y-m-d H:i:s') . '</p>';

        $html .= '<table class="kpis"><tr>';
        $html .= '<td><strong>Cobros realizados</strong><br>' . (int)($totales['cobros_realizados'] ?? 0) . '</td>';
        $html .= '<td><strong>Monto cobrado</strong><br>$' . number_format((float)($totales['monto_total'] ?? 0), 2, '.', ',') . '</td>';
        $html .= '<td><strong>Moratorio cobrado</strong><br>$' . number_format((float)($totales['moratorio_total'] ?? 0), 2, '.', ',') . '</td>';
        $html .= '<td><strong>Cobratarios con movimiento</strong><br>' . (int)($totales['cobratarios_con_movimiento'] ?? 0) . '</td>';
        $html .= '</tr></table>';

        $html .= '<h2>Resumen por Cobratario</h2>';
        $html .= '<table><thead><tr><th>Cobratario</th><th class="right">Cobros</th><th class="right">Monto</th><th class="right">Moratorio</th></tr></thead><tbody>';
        if (empty($resumen)) {
            $html .= '<tr><td colspan="4">Sin cobros en el rango seleccionado.</td></tr>';
        } else {
            foreach ($resumen as $fila) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string)($fila['cobratario'] ?? '-')) . '</td>';
                $html .= '<td class="right">' . (int)($fila['cobros_realizados'] ?? 0) . '</td>';
                $html .= '<td class="right">$' . number_format((float)($fila['monto_total'] ?? 0), 2, '.', ',') . '</td>';
                $html .= '<td class="right">$' . number_format((float)($fila['moratorio_total'] ?? 0), 2, '.', ',') . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<h2>Detalle de Cobros</h2>';
        $html .= '<table><thead><tr><th>Fecha</th><th>Cobratario</th><th>Cliente</th><th>Crédito</th><th>Letra</th><th>Método</th><th class="right">Moratorio</th><th class="right">Monto</th></tr></thead><tbody>';
        if (empty($detalle)) {
            $html .= '<tr><td colspan="8">Sin detalle para mostrar.</td></tr>';
        } else {
            foreach ($detalle as $fila) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string)($fila['fecha_pago'] ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string)($fila['cobratario'] ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars((string)($fila['cliente'] ?? '-')) . '</td>';
                $html .= '<td>#' . (int)($fila['idcredito'] ?? 0) . '</td>';
                $html .= '<td>#' . (int)($fila['numero_pago'] ?? 0) . '</td>';
                $html .= '<td>' . htmlspecialchars((string)($fila['metodo_pago'] ?? '-')) . '</td>';
                $html .= '<td class="right">$' . number_format((float)($fila['interes_moratorio'] ?? 0), 2, '.', ',') . '</td>';
                $html .= '<td class="right">$' . number_format((float)($fila['monto_pagado'] ?? 0), 2, '.', ',') . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '</body></html>';
        return $html;
    }
}
