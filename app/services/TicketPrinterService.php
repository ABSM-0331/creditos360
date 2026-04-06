<?php

class TicketPrinterService
{
    public function imprimirCobro(array $cobro): array
    {
        try {
            $html = $this->generarHtmlCobro($cobro);

            return [
                'ok' => true,
                'html' => $html,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    public function imprimirTicket(array $cobro): array
    {
        try {
            $empresa = ['nombre_empresa' => 'GestionPro'];
            if (class_exists('EmpresaService')) {
                try {
                    $empresa = array_merge($empresa, (new EmpresaService())->obtenerDatos());
                } catch (Throwable $e) {
                }
            }

            return [
                'ok' => true,
                'payload' => $this->construirPayloadImpresion($cobro, $empresa),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    public function generarHtmlCobro(array $cobro): string
    {
        $empresa = ['nombre_empresa' => 'GestionPro'];
        if (class_exists('EmpresaService')) {
            try {
                $empresa = array_merge($empresa, (new EmpresaService())->obtenerDatos());
            } catch (Throwable $e) {
                // Continuar con datos por defecto.
            }
        }

        return $this->construirHtmlRecibo($cobro, $empresa);
    }

    private function construirHtmlRecibo(array $cobro, array $empresa): string
    {
        $nombreEmpresa = $this->esc((string)($empresa['nombre_empresa'] ?? 'GestionPro'));
        $representante = $this->esc((string)($empresa['representante_legal'] ?? ''));
        $rfc = $this->esc((string)($empresa['rfc'] ?? ''));
        $direccion = $this->esc((string)($empresa['direccion'] ?? ''));
        $telefono = $this->esc((string)($empresa['telefono'] ?? ''));
        $correo = $this->esc((string)($empresa['correo'] ?? ''));
        $logoRuta = trim((string)($empresa['logo_ruta'] ?? ''));
        $logoUrl = $logoRuta !== '' ? '/' . ltrim($logoRuta, '/') : '';

        $numeroRecibo = $this->esc((string)($cobro['numero_recibo'] ?? 'N/A'));
        $fecha = $this->esc($this->formatearFecha((string)($cobro['fecha'] ?? date('Y-m-d'))));
        $cliente = $this->esc((string)($cobro['cliente']['nombre'] ?? 'N/A'));
        $cobrador = $this->esc((string)($cobro['cobrador'] ?? 'N/A'));
        $clienteEmail = trim((string)($cobro['cliente']['email'] ?? ''));

        $pagos = is_array($cobro['pagos'] ?? null) ? $cobro['pagos'] : [];
        $primerPago = $pagos[0] ?? [];

        $montoPagado = (float)($cobro['resumen']['total_cobrado'] ?? ($primerPago['monto_pagado'] ?? 0));
        $capital = (float)($cobro['resumen']['total_programado'] ?? ($primerPago['monto_programado'] ?? 0));
        $interes = (float)($cobro['resumen']['total_moratorio'] ?? 0);
        $saldoRestante = (float)($cobro['credito']['saldo_pendiente_momento'] ?? ($cobro['credito']['saldo_pendiente'] ?? 0));
        $cantidadPagos = max(1, (int)($cobro['resumen']['cantidad_pagos_cobrados'] ?? count($pagos)));
        $idCredito = (int)($cobro['credito']['idcredito'] ?? 0);
        $historialIds = array_values(array_unique(array_filter(array_map(
            static fn($pago) => (int)($pago['idhistorial'] ?? 0),
            $pagos
        ))));
        $historialCsv = implode(',', $historialIds);

        $detallePagos = '';
        foreach ($pagos as $pago) {
            $numeroPago = (int)($pago['numero_pago'] ?? 0);
            $fechaPagoProgramada = $this->esc($this->formatearFecha((string)($pago['fecha_programada'] ?? '')));
            $montoPagoItem = (float)($pago['monto_pagado'] ?? 0);
            $detallePagos .= '<div class="row row-pago"><span>Letra #' . $numeroPago . ' (' . $fechaPagoProgramada . ')</span><span class="value">$' . $this->money($montoPagoItem) . '</span></div>';
        }

        $payloadImpresion = $this->construirPayloadImpresion($cobro, $empresa);

        $htmlLogo = $logoUrl !== ''
            ? '<img class="logo" src="' . $this->esc($logoUrl) . '" alt="Logo">'
            : '';

        return '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ticket de Cobro</title>
            <style>
                * { box-sizing: border-box; }
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
                .center { text-align: center; }
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
                .row-pago {
                    font-size: 11px;
                    color: #374151;
                }
                .row .value { font-weight: 700; }
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
                @media print {
                    body { background: #fff; padding: 0; }
                    .modal { border: none; border-radius: 0; padding: 0; max-width: 100%; }
                    .modal-header { display: none; }
                    .ticket { border: none; border-radius: 0; }
                    .actions { display: none; }
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
                    ' . $htmlLogo . '
                    <h1>' . $nombreEmpresa . '</h1>
                    ' . ($representante !== '' ? '<p>Rep: ' . $representante . '</p>' : '') . '
                    ' . ($rfc !== '' ? '<p>RFC: ' . $rfc . '</p>' : '') . '
                    ' . ($direccion !== '' ? '<p>' . $direccion . '</p>' : '') . '
                    ' . ($telefono !== '' ? '<p>Tel: ' . $telefono . '</p>' : '') . '
                    ' . ($correo !== '' ? '<p>' . $correo . '</p>' : '') . '
                </div>

                <div class="sep"></div>
                <div class="center subtitle">RECIBO DE PAGO</div>
                <div class="sep"></div>

            <div class="row"><span>Recibo:</span><span class="value">' . $numeroRecibo . '</span></div>
                <div class="row"><span>Fecha:</span><span>' . $fecha . '</span></div>
                <div class="row"><span>Cliente:</span><span>' . $cliente . '</span></div>
            <div class="row"><span>Cobratario:</span><span>' . $cobrador . '</span></div>

                <div class="sep"></div>

                <div class="row"><span>Letras cobradas:</span><span class="value">' . $cantidadPagos . '</span></div>
                ' . $detallePagos . '

                <div class="sep"></div>

                <div class="row"><span>Monto pagado:</span><span class="value">$' . $this->money($montoPagado) . '</span></div>
                <div class="row"><span>Capital:</span><span>$' . $this->money($capital) . '</span></div>
                <div class="row"><span>Interes moratorio:</span><span>$' . $this->money($interes) . '</span></div>

                <div class="sep"></div>

                <div class="row"><span>Saldo restante:</span><span class="value">$' . $this->money($saldoRestante) . '</span></div>

                <div class="sep"></div>
                <div class="footer">Gracias por su pago</div>

                <div class="actions">
                    <button class="btn btn-print" onclick="imprimir()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        Imprimir
                    </button>
                    <button class="btn" onclick="enviarTicketCorreo()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2L11 13"></path>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Enviar
                    </button>
                </div>
            </article>
            </section>

            <script>

                const PRINT_BASE = "http://127.0.0.1:9666";
                const PRINT_AGENT = PRINT_BASE + "/print";
                const PRINT_STATUS = PRINT_BASE + "/status";
                const PRINT_TOKEN = "secreto-123";
                const ticketPayload = ' . json_encode($payloadImpresion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';

                async function fetchWithTimeout(resource, options = {}) {
                    const { timeout = 30000, ...opts } = options;
                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), timeout);
                    try {
                        return await fetch(resource, { ...opts, signal: controller.signal });
                    } finally {
                        clearTimeout(timer);
                    }
                }

                async function detectarAgente() {
                    try {
                        const response = await fetchWithTimeout(PRINT_STATUS, { method: "GET", timeout: 2000 });
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

                window.imprimir = async function () {
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
                                    url: window.location.href
                                }),
                                timeout: 25000
                            });
                        } else {
                            response = await fetchWithTimeout(PRINT_AGENT, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-PRINT-TOKEN": PRINT_TOKEN
                                },
                                body: JSON.stringify(ticketPayload),
                                timeout: 45000
                            });
                        }

                        let data = null;
                        const raw = await response.text();
                        try {
                            data = JSON.parse(raw);
                        } catch (e) {
                            data = { ok: response.ok, mensaje: raw };
                        }

                        if (!response.ok || !(data.ok === true || data.success === true)) {
                            throw new Error(data.error || data.mensaje || "No se pudo imprimir con el agente local.");
                        }

                        window.alert(data.mensaje || "Ticket enviado a impresora.");
                    } catch (error) {
                        window.alert("Error al imprimir ticket: " + (error.message || "No se pudo conectar con el agente local de impresión."));
                    } finally {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = textoOriginal;
                        }
                    }
                }
                window.enviarTicketCorreo = async function () {
                    const correoSugerido = ' . json_encode($clienteEmail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' || "";
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
                        formData.append("idcredito", ' . (int)$idCredito . ');
                        formData.append("historial", ' . json_encode($historialCsv, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ');
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
                }
            </script>
        </body>
        </html>';
    }

    private function construirPayloadImpresion(array $cobro, array $empresa): array
    {
        $anchoTicket = 32;

        $linea = static function (string $izq, string $der = '', int $ancho = 32): string {
            $izq = trim(preg_replace('/\s+/', ' ', $izq));
            $der = trim(preg_replace('/\s+/', ' ', $der));

            if ($der === '') {
                return substr($izq, 0, $ancho);
            }

            $maxDer = min(10, $ancho - 4);
            $der = substr($der, 0, $maxDer);
            $maxIzq = max(1, $ancho - strlen($der));
            $izq = substr($izq, 0, $maxIzq);

            return str_pad($izq, $maxIzq, ' ', STR_PAD_RIGHT) . $der;
        };

        $envolver = static function (string $texto, int $ancho = 32): array {
            $texto = trim(preg_replace('/\s+/', ' ', $texto));
            if ($texto === '') {
                return [];
            }

            return explode("\n", wordwrap($texto, $ancho, "\n", true));
        };

        $centrar = static function (string $texto, int $ancho = 32): string {
            $texto = trim(preg_replace('/\s+/', ' ', $texto));
            if ($texto === '') {
                return '';
            }

            $texto = substr($texto, 0, $ancho);
            $espacios = (int)floor(($ancho - strlen($texto)) / 2);
            return str_repeat(' ', max(0, $espacios)) . $texto;
        };

        $pagos = is_array($cobro['pagos'] ?? null) ? $cobro['pagos'] : [];
        $primerPago = $pagos[0] ?? [];

        $montoPagado = (float)($cobro['resumen']['total_cobrado'] ?? ($primerPago['monto_pagado'] ?? 0));
        $capital = (float)($cobro['resumen']['total_programado'] ?? ($primerPago['monto_programado'] ?? 0));
        $interes = (float)($cobro['resumen']['total_moratorio'] ?? 0);
        $saldoRestante = (float)($cobro['credito']['saldo_pendiente_momento'] ?? ($cobro['credito']['saldo_pendiente'] ?? 0));
        $cantidadPagos = max(1, (int)($cobro['resumen']['cantidad_pagos_cobrados'] ?? count($pagos)));

        $nombreEmpresa = trim((string)($empresa['nombre_empresa'] ?? 'Ticket de Cobro'));
        $representante = trim((string)($empresa['representante_legal'] ?? ''));
        $rfc = trim((string)($empresa['rfc'] ?? ''));
        $direccion = trim((string)($empresa['direccion'] ?? ''));
        $telefono = trim((string)($empresa['telefono'] ?? ''));
        $correo = trim((string)($empresa['correo'] ?? ''));

        $lineasImpresion = [];
        $lineasImpresion[] = $centrar($nombreEmpresa, $anchoTicket);
        if ($representante !== '') {
            foreach ($envolver('Rep: ' . $representante, $anchoTicket) as $lineaRep) {
                $lineasImpresion[] = $lineaRep;
            }
        }
        if ($rfc !== '') {
            $lineasImpresion[] = $centrar('RFC: ' . $rfc, $anchoTicket);
        }
        if (!empty($empresa['direccion'])) {
            foreach ($envolver($direccion, $anchoTicket) as $lineaDireccion) {
                $lineasImpresion[] = $lineaDireccion;
            }
        }
        if (!empty($empresa['telefono'])) {
            $lineasImpresion[] = $centrar('Tel: ' . $telefono, $anchoTicket);
        }
        if ($correo !== '') {
            foreach ($envolver($correo, $anchoTicket) as $lineaCorreo) {
                $lineasImpresion[] = $centrar($lineaCorreo, $anchoTicket);
            }
        }
        $lineasImpresion[] = '';
        $lineasImpresion[] = str_repeat('-', $anchoTicket);
        $lineasImpresion[] = $centrar('RECIBO DE PAGO', $anchoTicket);
        $lineasImpresion[] = str_repeat('-', $anchoTicket);
        $lineasImpresion[] = $linea('Recibo:', (string)($cobro['numero_recibo'] ?? 'N/A'), $anchoTicket);
        $lineasImpresion[] = $linea('Fecha:', $this->formatearFecha((string)($cobro['fecha'] ?? date('Y-m-d'))), $anchoTicket);
        foreach ($envolver('Cliente: ' . (string)($cobro['cliente']['nombre'] ?? 'N/A'), $anchoTicket) as $lineaCliente) {
            $lineasImpresion[] = $lineaCliente;
        }
        foreach ($envolver('Cobratario: ' . (string)($cobro['cobrador'] ?? 'N/A'), $anchoTicket) as $lineaCobratario) {
            $lineasImpresion[] = $lineaCobratario;
        }
        $lineasImpresion[] = str_repeat('-', $anchoTicket);
        $lineasImpresion[] = $linea('Letras cobradas:', (string)$cantidadPagos, $anchoTicket);

        foreach ($pagos as $pago) {
            $numeroPago = (int)($pago['numero_pago'] ?? 0);
            $fechaPago = $this->formatearFecha((string)($pago['fecha_programada'] ?? ''));
            $montoItem = number_format((float)($pago['monto_pagado'] ?? 0), 2, '.', ',');
            $lineasImpresion[] = $linea('Letra #' . $numeroPago . ' ' . $fechaPago, '$' . $montoItem, $anchoTicket);
        }

        $lineasImpresion[] = str_repeat('-', $anchoTicket);
        $lineasImpresion[] = $linea('Monto pagado:', '$' . $this->money($montoPagado), $anchoTicket);
        $lineasImpresion[] = $linea('Capital:', '$' . $this->money($capital), $anchoTicket);
        $lineasImpresion[] = $linea('Interes moratorio:', '$' . $this->money($interes), $anchoTicket);
        $lineasImpresion[] = str_repeat('-', $anchoTicket);
        $lineasImpresion[] = $linea('Saldo restante:', '$' . $this->money($saldoRestante), $anchoTicket);
        $lineasImpresion[] = str_repeat('-', $anchoTicket);
        $lineasImpresion[] = $centrar('Gracias por su pago', $anchoTicket);

        $printerName = '';
        try {
            $printerName = $this->obtenerNombreImpresoraActiva();
        } catch (Throwable $e) {
            $printerName = '';
        }

        $logoBase64 = $this->resolverLogoBase64((string)($empresa['logo_ruta'] ?? ''));

        return [
            'PrinterName' => $printerName,
            'Title' => (string)($empresa['nombre_empresa'] ?? 'Ticket de Cobro'),
            'Lines' => $lineasImpresion,
            'Cut' => true,
            'LogoBase64' => $logoBase64,
            'PaperWidthPx' => 220,
            'FontName' => 'Consolas',
            'FontSize' => 8,
        ];
    }

    private function resolverLogoBase64(string $logoRuta): ?string
    {
        $logoRuta = trim($logoRuta);
        if ($logoRuta === '') {
            return null;
        }

        $rutaAbsoluta = __DIR__ . '/../../public/' . ltrim($logoRuta, '/');
        if (!is_file($rutaAbsoluta)) {
            return null;
        }

        $contenido = @file_get_contents($rutaAbsoluta);
        if ($contenido === false) {
            return null;
        }

        $mime = @mime_content_type($rutaAbsoluta);
        if (!is_string($mime) || $mime === '') {
            $mime = 'image/png';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }

    private function esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    private function formatearFecha(string $fecha): string
    {
        try {
            $date = new DateTime($fecha);
            return $date->format('d/m/Y');
        } catch (Throwable $e) {
            return $fecha;
        }
    }

    private function money(float $monto): string
    {
        return number_format($monto, 2, '.', ',');
    }

    private function obtenerNombreImpresoraActiva(): string
    {
        if (!class_exists('ImpresorasService')) {
            throw new Exception('No se encontró el módulo de impresoras.');
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            throw new Exception('No hay sesión activa para resolver la impresora del usuario.');
        }

        $activa = (new ImpresorasService())->obtenerActiva($usuarioId);
        $nombre = trim((string)($activa['nombre'] ?? ''));
        if ($nombre === '') {
            throw new Exception('No tienes una impresora activa registrada. Ve al módulo Impresoras y activa una.');
        }

        return $nombre;
    }
}
