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
    public function generarHtmlCobro(array $cobro, bool $modoInteractivo = true): string
    {
        $empresa = ['nombre_empresa' => 'GestionPro'];
        if (class_exists('EmpresaService')) {
            try {
                $empresa = array_merge($empresa, (new EmpresaService())->obtenerDatos());
            } catch (Throwable $e) {
                // Continuar con datos por defecto.
            }
        }

        return $this->construirHtmlRecibo($cobro, $empresa, $modoInteractivo);
    }

    private function construirHtmlRecibo(array $cobro, array $empresa, bool $modoInteractivo = true): string
    {
        $view = __DIR__ . '/../views/recibos/ticket.php';
        if (!is_file($view)) {
            throw new Exception('No se encontró la vista de ticket.');
        }

        $payloadImpresion = $this->construirPayloadImpresion($cobro, $empresa);
        $impresorasDisponibles = $modoInteractivo ? $this->obtenerImpresorasUsuario() : [];
        $impresoraPredeterminada = $this->determinarImpresoraPredeterminada($impresorasDisponibles);
        $clienteEmail = trim((string)($cobro['cliente']['email'] ?? ''));
        $historialCsv = implode(',', array_values(array_unique(array_filter(array_map(
            static fn($pago) => (int)($pago['idhistorial'] ?? 0),
            is_array($cobro['pagos'] ?? null) ? $cobro['pagos'] : []
        )))));

        ob_start();
        require $view;
        return (string)ob_get_clean();
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
            $montoPagadoItem = (float)($pago['monto_pagado'] ?? 0);
            $moratorioPago = (float)($pago['recargo_moratorio'] ?? 0);
            $valorLetra = (float)($pago['monto_programado'] ?? max(0, $montoPagadoItem - $moratorioPago));
            $totalConMoratorio = $valorLetra + $moratorioPago;

            $lineasImpresion[] = $linea('Letra #' . $numeroPago . ' ' . $fechaPago, '$' . $this->money($valorLetra), $anchoTicket);

            if ($moratorioPago > 0) {
                $lineasImpresion[] = $linea('  Moratorio:', '$' . $this->money($moratorioPago), $anchoTicket);
                $lineasImpresion[] = $linea('  Total letra #' . $numeroPago . ':', '$' . $this->money($totalConMoratorio), $anchoTicket);
            }
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
            'FontSize' => 7,
        ];
    }

    private function obtenerImpresorasUsuario(): array
    {
        if (!class_exists('ImpresorasService')) {
            return [];
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            return [];
        }

        try {
            $impresoras = (new ImpresorasService())->obtenerRegistradas($usuarioId);
            return is_array($impresoras) ? array_values($impresoras) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function determinarImpresoraPredeterminada(array $impresoras): string
    {
        foreach ($impresoras as $impresora) {
            if ((int)($impresora['activa'] ?? 0) === 1) {
                $nombre = trim((string)($impresora['nombre'] ?? ''));
                if ($nombre !== '') {
                    return $nombre;
                }
            }
        }

        foreach ($impresoras as $impresora) {
            $nombre = trim((string)($impresora['nombre'] ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        return '';
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
