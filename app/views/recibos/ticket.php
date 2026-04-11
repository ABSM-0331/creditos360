<?php
$modoInteractivo = $modoInteractivo ?? true;
$empresa = $empresa ?? [];
$cobro = $cobro ?? [];
$impresorasDisponibles = $impresorasDisponibles ?? [];
$impresoraPredeterminada = $impresoraPredeterminada ?? '';
$payloadImpresion = $payloadImpresion ?? [];
$clienteEmail = $clienteEmail ?? '';
$historialCsv = $historialCsv ?? '';

$nombreEmpresa = (string)($empresa['nombre_empresa'] ?? 'GestionPro');
$representante = (string)($empresa['representante_legal'] ?? '');
$rfc = (string)($empresa['rfc'] ?? '');
$direccion = (string)($empresa['direccion'] ?? '');
$telefono = (string)($empresa['telefono'] ?? '');
$correo = (string)($empresa['correo'] ?? '');
$logoRuta = trim((string)($empresa['logo_ruta'] ?? ''));
$logoUrl = $logoRuta !== '' ? '/' . ltrim($logoRuta, '/') : '';

$numeroRecibo = (string)($cobro['numero_recibo'] ?? 'N/A');
$fecha = (string)($cobro['fecha_formateada'] ?? $cobro['fecha'] ?? date('Y-m-d'));
$cliente = (string)($cobro['cliente']['nombre'] ?? 'N/A');
$cobrador = (string)($cobro['cobrador'] ?? 'N/A');

$pagos = is_array($cobro['pagos'] ?? null) ? $cobro['pagos'] : [];
$cantidadPagos = max(1, (int)($cobro['resumen']['cantidad_pagos_cobrados'] ?? count($pagos)));
$montoPagado = (float)($cobro['resumen']['total_cobrado'] ?? 0);
$capital = (float)($cobro['resumen']['total_programado'] ?? 0);
$interes = (float)($cobro['resumen']['total_moratorio'] ?? 0);
$saldoRestante = (float)($cobro['credito']['saldo_pendiente_momento'] ?? ($cobro['credito']['saldo_pendiente'] ?? 0));

if (!function_exists('ticket_view_esc')) {
    function ticket_view_esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ticket_view_money')) {
    function ticket_view_money(float $monto): string
    {
        return number_format($monto, 2, '.', ',');
    }
}

$detallePagos = '';
foreach ($pagos as $pago) {
    $numeroPago = (int)($pago['numero_pago'] ?? 0);
    $fechaPagoProgramada = (string)($pago['fecha_programada_formateada'] ?? $pago['fecha_programada'] ?? '');
    $montoPagadoItem = (float)($pago['monto_pagado'] ?? 0);
    $moratorioPago = (float)($pago['recargo_moratorio'] ?? 0);
    $valorLetra = (float)($pago['monto_programado'] ?? max(0, $montoPagadoItem - $moratorioPago));
    $totalConMoratorio = $valorLetra + $moratorioPago;

    $detallePagos .= '<div class="row row-pago"><span>Letra #' . $numeroPago . ' (' . ticket_view_esc($fechaPagoProgramada) . ')</span><span class="value">$' . ticket_view_money($valorLetra) . '</span></div>';

    if ($moratorioPago > 0) {
        $detallePagos .= '<div class="row row-pago row-pago-detalle"><span>Moratorio</span><span class="value">$' . ticket_view_money($moratorioPago) . '</span></div>';
        $detallePagos .= '<div class="row row-pago row-pago-total"><span>Total letra #' . $numeroPago . '</span><span class="value">$' . ticket_view_money($totalConMoratorio) . '</span></div>';
    }
}

$opcionesImpresora = '';
foreach ($impresorasDisponibles as $impresora) {
    $nombreImpresora = trim((string)($impresora['nombre'] ?? ''));
    if ($nombreImpresora === '') {
        continue;
    }

    $activa = (int)($impresora['activa'] ?? 0) === 1;
    $etiqueta = $nombreImpresora . ($activa ? ' (activa)' : '');
    $selected = $nombreImpresora === $impresoraPredeterminada ? ' selected' : '';
    $opcionesImpresora .= '<option value="' . ticket_view_esc($nombreImpresora) . '"' . $selected . '>' . ticket_view_esc($etiqueta) . '</option>';
}

$selectorImpresoras = '';
if ($modoInteractivo) {
    $selectorImpresoras = '<div class="printer-selector"><label for="printerSelect">Impresora destino</label><select id="printerSelect"' . (empty($impresorasDisponibles) ? ' disabled' : '') . '>' . ($opcionesImpresora !== '' ? $opcionesImpresora : '<option value="" selected>No hay impresoras registradas</option>') . '</select><small>' . (empty($impresorasDisponibles) ? 'Registra al menos una impresora para poder imprimir.' : 'Puedes elegir cualquiera de las impresoras registradas para tu usuario.') . '</small></div>';
}

$htmlLogo = $logoUrl !== '' ? '<img class="logo" src="https://crediox.com.mx/proyecto-residencia/public' . ticket_view_esc($logoUrl) . '" alt="Logo">' : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Cobro</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 18px;
            background: #ececec;
            font-family: "IBM Plex Mono", "Courier New", monospace;
            color: #111827;
        }

        .modal {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 14px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .modal-title {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
        }

        .modal-close {
            border: none;
            background: transparent;
            font-size: 30px;
            line-height: 1;
            cursor: pointer;
            color: #6b7280;
        }

        .ticket {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .center {
            text-align: center;
        }

        .logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin: 0 auto 6px auto;
            display: block;
        }

        h1 {
            margin: 0 0 4px 0;
            font-size: 26px;
            letter-spacing: 0.5px;
            font-weight: 800;
        }

        p {
            margin: 2px 0;
            font-size: 12px;
        }

        .sep {
            border-top: 1px dashed #9ca3af;
            margin: 10px 0;
        }

        .subtitle {
            margin: 4px 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1.2px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin: 6px 0;
            font-size: 12px;
        }

        .row span:first-child {
            color: #374151;
        }

        .row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .row-pago {
            font-size: 11px;
            color: #374151;
        }

        .row-pago-detalle {
            padding-left: 12px;
            color: #6b7280;
        }

        .row-pago-total {
            padding-left: 12px;
            font-weight: 700;
            color: #111827;
        }

        .row .value {
            font-weight: 700;
        }

        .footer {
            text-align: center;
            margin-top: 8px;
            font-size: 13px;
            color: #4b5563;
        }

        .actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .printer-selector {
            margin-top: 14px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .printer-selector label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .printer-selector select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            font-size: 14px;
            color: #111827;
        }

        .printer-selector small {
            display: block;
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.4;
        }

        .btn {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            padding: 10px 16px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn svg {
            vertical-align: middle;
            margin-right: 6px;
        }

        .section-card {
            margin: 0;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .modal {
                border: none;
                border-radius: 0;
                padding: 0;
                max-width: 100%;
            }

            .modal-header {
                display: none;
            }

            .ticket {
                border: none;
                border-radius: 0;
            }

            .actions {
                display: none;
            }

            .printer-selector {
                display: none;
            }
        }
    </style>
</head>

<body>
    <section class="modal">
        <header class="modal-header">
            <h2 class="modal-title">Recibo de Pago</h2>
            <button class="modal-close" onclick="window.close()">×</button>
        </header>

        <article class="ticket">
            <div class="center">
                <?= $htmlLogo ?>
                <h1><?= ticket_view_esc($nombreEmpresa) ?></h1>
                <?php if ($representante !== ''): ?><p>Rep: <?= ticket_view_esc($representante) ?></p><?php endif; ?>
                <?php if ($rfc !== ''): ?><p>RFC: <?= ticket_view_esc($rfc) ?></p><?php endif; ?>
                <?php if ($direccion !== ''): ?><p><?= ticket_view_esc($direccion) ?></p><?php endif; ?>
                <?php if ($telefono !== ''): ?><p>Tel: <?= ticket_view_esc($telefono) ?></p><?php endif; ?>
                <?php if ($correo !== ''): ?><p><?= ticket_view_esc($correo) ?></p><?php endif; ?>
            </div>

            <div class="sep"></div>
            <div class="center subtitle">RECIBO DE PAGO</div>
            <div class="sep"></div>

            <div class="section-card section-card-meta">
                <div class="row"><span>Recibo:</span><span class="value"><?= ticket_view_esc($numeroRecibo) ?></span></div>
                <div class="row"><span>Fecha:</span><span><?= ticket_view_esc($fecha) ?></span></div>
                <div class="row"><span>Cliente:</span><span><?= ticket_view_esc($cliente) ?></span></div>
                <div class="row"><span>Cobratario:</span><span><?= ticket_view_esc($cobrador) ?></span></div>
            </div>

            <div class="sep"></div>

            <div class="section-card section-card-pagos">
                <div class="row"><span>Letras cobradas:</span><span class="value"><?= (int)$cantidadPagos ?></span></div>
                <?= $detallePagos ?>
            </div>

            <div class="sep"></div>

            <div class="section-card section-card-resumen">
                <div class="row"><span>Monto pagado:</span><span class="value">$<?= ticket_view_money($montoPagado) ?></span></div>
                <div class="row"><span>Capital:</span><span>$<?= ticket_view_money($capital) ?></span></div>
                <div class="row"><span>Interes moratorio:</span><span>$<?= ticket_view_money($interes) ?></span></div>
            </div>

            <div class="sep"></div>

            <div class="section-card section-card-saldo">
                <div class="row"><span>Saldo restante:</span><span class="value">$<?= ticket_view_money($saldoRestante) ?></span></div>
            </div>

            <div class="sep"></div>
            <div class="footer">Gracias por su pago</div>
            <?= $selectorImpresoras ?>

            <?php if ($modoInteractivo): ?>
                <div class="actions">
                    <button class="btn btn-print" onclick="imprimir()" <?= empty($impresorasDisponibles) ? ' disabled' : '' ?>>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <?= empty($impresorasDisponibles) ? 'Sin impresoras' : 'Imprimir' ?>
                    </button>
                    <button class="btn" onclick="enviarTicketCorreo()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2L11 13"></path>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Enviar
                    </button>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <script>
        const PRINT_BASE = "http://127.0.0.1:9666";
        const PRINT_AGENT = PRINT_BASE + "/print";
        const PRINT_STATUS = PRINT_BASE + "/status";
        const PRINT_TOKEN = "secreto-123";
        const ticketPayload = <?= json_encode($payloadImpresion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const printerOptions = <?= json_encode(array_values($impresorasDisponibles), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const printerDefault = <?= json_encode($impresoraPredeterminada, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const selectImpresora = document.getElementById("printerSelect");

        if (selectImpresora && printerDefault) {
            selectImpresora.value = printerDefault;
        }

        async function fetchWithTimeout(resource, options = {}) {
            const {
                timeout = 30000, ...opts
            } = options;
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), timeout);
            try {
                return await fetch(resource, {
                    ...opts,
                    signal: controller.signal
                });
            } finally {
                clearTimeout(timer);
            }
        }

        async function detectarAgente() {
            try {
                const response = await fetchWithTimeout(PRINT_STATUS, {
                    method: "GET",
                    timeout: 2000
                });
                if (!response.ok) {
                    return "dotnet";
                }
                const data = await response.json().catch(() => ({}));
                if (data && typeof data === "object" && "btConnected" in data && "mac" in data) {
                    return "b4a";
                }
                return "dotnet";
            } catch (error) {
                return "dotnet";
            }
        }

        window.imprimir = async function() {
            if (!printerOptions.length) {
                window.alert("No hay impresoras registradas para este usuario.");
                return;
            }

            const nombreImpresora = selectImpresora ? (selectImpresora.value || "").trim() : "";
            if (!nombreImpresora) {
                window.alert("Selecciona una impresora para continuar.");
                return;
            }

            const btn = document.querySelector(".btn-print");
            const textoOriginal = btn ? btn.innerHTML : "";

            if (btn) {
                btn.disabled = true;
                btn.textContent = "Imprimiendo...";
            }

            try {
                const tipoAgente = await detectarAgente();
                let response;

                if (tipoAgente === "b4a") {
                    response = await fetchWithTimeout(PRINT_AGENT, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-PRINT-TOKEN": PRINT_TOKEN
                        },
                        body: JSON.stringify({
                            url: window.location.href,
                            PrinterName: nombreImpresora
                        }),
                        timeout: 25000
                    });
                } else {
                    const payload = Object.assign({}, ticketPayload, {
                        PrinterName: nombreImpresora
                    });
                    response = await fetchWithTimeout(PRINT_AGENT, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-PRINT-TOKEN": PRINT_TOKEN
                        },
                        body: JSON.stringify(payload),
                        timeout: 45000
                    });
                }

                let data = null;
                const raw = await response.text();
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    data = {
                        ok: response.ok,
                        mensaje: raw
                    };
                }

                if (!response.ok || !(data.ok === true || data.success === true)) {
                    throw new Error(data.error || data.mensaje || "No se pudo imprimir con el agente local.");
                }

                window.alert(data.mensaje || ("Ticket enviado a: " + nombreImpresora));
            } catch (error) {
                window.alert("Error al imprimir ticket: " + (error.message || "No se pudo conectar con el agente local de impresión."));
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                }
            }
        };

        window.enviarTicketCorreo = async function() {
            const correoSugerido = <?= json_encode($clienteEmail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || "";
            const correo = window.prompt("Correo del cliente", correoSugerido);
            if (correo === null) {
                return;
            }

            const correoFinal = correo.trim();
            if (!correoFinal) {
                window.alert("Debes capturar un correo para enviar el ticket.");
                return;
            }

            const btn = document.querySelector(".btn[onclick=\"enviarTicketCorreo()\"]");
            const textoOriginal = btn ? btn.innerHTML : "";
            if (btn) {
                btn.disabled = true;
                btn.textContent = "Enviando...";
            }

            try {
                const formData = new FormData();
                formData.append("idcredito", <?= (int)($cobro['credito']['idcredito'] ?? 0) ?>);
                formData.append("historial", <?= json_encode($historialCsv, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
                formData.append("correo", correoFinal);

                const response = await fetch("/proyecto-residencia/public/creditos/enviar-ticket", {
                    method: "POST",
                    body: formData,
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.error || "No se pudo enviar el ticket.");
                }

                window.alert("Ticket enviado correctamente a: " + (data.correo || correoFinal));
            } catch (error) {
                window.alert("Error al enviar ticket: " + (error.message || "Error desconocido"));
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                }
            }
        };
    </script>
</body>

</html>