<?php

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

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

            $nombreImpresora = $this->obtenerNombreImpresoraActiva();
            $connector = new WindowsPrintConnector($nombreImpresora);
            $printer = new Printer($connector);

            // Helpers
            $linea = function ($izq, $der = "") {
                $izq = substr($izq, 0, 22);
                $der = substr($der, 0, 10);
                return str_pad($izq, 22) . str_pad($der, 10, " ", STR_PAD_LEFT) . "\n";
            };

            $envolver = function ($texto, $ancho = 32) {
                $limpio = trim(preg_replace('/\s+/', ' ', (string)$texto));
                if ($limpio === '') {
                    return [];
                }
                return explode("\n", wordwrap($limpio, $ancho, "\n", true));
            };

            $center = function ($text) {
                $text = substr($text, 0, 32);
                $spaces = floor((32 - strlen($text)) / 2);
                return str_repeat(" ", $spaces) . $text . "\n";
            };

            $sep = function () {
                return str_repeat("-", 32) . "\n";
            };

            // Datos
            $nombreEmpresa = $empresa['nombre_empresa'] ?? 'GestionPro';
            $representante = $empresa['representante_legal'] ?? '';
            $rfc = $empresa['rfc'] ?? '';
            $direccion = $empresa['direccion'] ?? '';
            $telefono = $empresa['telefono'] ?? '';

            $numeroRecibo = $cobro['numero_recibo'] ?? 'N/A';
            $fecha = $this->formatearFecha($cobro['fecha'] ?? date('Y-m-d'));
            $cliente = $cobro['cliente']['nombre'] ?? 'N/A';
            $cobrador = $cobro['cobrador'] ?? 'N/A';

            $pagos = $cobro['pagos'] ?? [];
            $montoPagado = (float)($cobro['resumen']['total_cobrado'] ?? 0);
            $capital = (float)($cobro['resumen']['total_programado'] ?? 0);
            $interes = (float)($cobro['resumen']['total_moratorio'] ?? 0);
            $saldoRestante = (float)($cobro['credito']['saldo_pendiente_momento'] ?? ($cobro['credito']['saldo_pendiente'] ?? 0));

            // ======= IMPRESIÓN =======

            // Encabezado
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text($center($nombreEmpresa));
            if ($representante) $printer->text($center("Rep: $representante"));
            if ($rfc) $printer->text($center("RFC: $rfc"));
            if ($direccion) $printer->text($center($direccion));
            if ($telefono) $printer->text($center("Tel: $telefono"));

            $printer->text($sep());
            $printer->text($center("RECIBO DE PAGO"));
            $printer->text($sep());

            // Datos generales
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($linea("Recibo:", $numeroRecibo));
            $printer->text($linea("Fecha:", $fecha));
            $printer->text("Cliente:\n");
            foreach ($envolver($cliente, 32) as $lineaCliente) {
                $printer->text($lineaCliente . "\n");
            }
            $printer->text("Cobrador:\n");
            foreach ($envolver($cobrador, 32) as $lineaCobrador) {
                $printer->text($lineaCobrador . "\n");
            }

            $printer->text($sep());

            // Pagos
            $printer->text("Letras cobradas:\n");

            foreach ($pagos as $pago) {
                $numeroPago = $pago['numero_pago'] ?? 0;
                $fechaPago = $this->formatearFecha($pago['fecha_programada'] ?? '');
                $monto = number_format((float)($pago['monto_pagado'] ?? 0), 2);

                $texto = "L#$numeroPago $fechaPago";
                $printer->text($linea($texto, "$$monto"));
            }

            $printer->text($sep());

            // Totales
            $printer->text($linea("Monto pagado:", "$" . $this->money($montoPagado)));
            $printer->text($linea("Capital:", "$" . $this->money($capital)));
            $printer->text($linea("Interes:", "$" . $this->money($interes)));

            $printer->text($sep());

            $printer->text($linea("Saldo restante:", "$" . $this->money($saldoRestante)));

            $printer->text($sep());

            // Footer
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Gracias por su pago\n\n");

            $printer->cut();
            $printer->close();

            return ['ok' => true];
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

                window.imprimir = async function () {
                    const btn = document.querySelector(".btn-print");
                    const textoOriginal = btn ? btn.innerHTML : "";

                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = "Imprimiendo...";
                    }

                    try {
                        const formData = new FormData();
                        formData.append("idcredito", ' . (int)$idCredito . ');
                        formData.append("historial", ' . json_encode($historialCsv, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ');

                        const response = await fetch("/proyecto-residencia/public/creditos/imprimir-ticket", {
                            method: "POST",
                            body: formData,
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.error || "No se pudo imprimir en la impresora térmica.");
                        }

                        window.alert(data.mensaje || "Ticket enviado a impresora térmica.");
                    } catch (error) {
                        window.alert("Error al imprimir ticket: " + (error.message || "Error desconocido"));
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
