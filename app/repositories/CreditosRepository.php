<?php

class CreditosRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DBC::get();
    }

    /**
     * Guardar un crédito completo con todos sus pagos
     */
    public function guardarCredito($datos)
    {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();

            $esMensualFlexible = ($datos['tipo'] ?? '') === 'mensual';

            // 1. Calcular datos del crédito
            $montoInteres = $datos['monto'] * ($datos['interes'] / 100);
            $totalPagar = $datos['monto'] + $montoInteres;
            $capitalBase = $datos['monto'] / $datos['pagos'];
            $interesBase = $montoInteres / $datos['pagos'];

            // Calcular fecha final
            $fechaInicio = new DateTime($datos['fecha_inicio']);
            $fechaFin = clone $fechaInicio;

            // Determinar intervalo para agregar a la fecha
            $intervalo = $this->obtenerIntervalo($datos['tipo']);
            for ($i = 1; $i < $datos['pagos']; $i++) {
                $fechaFin->add($intervalo);
            }

            // 2. Insertar en tabla creditos
            $sqlCredito = "INSERT INTO creditos (
                idcliente, 
                idcobratario, 
                monto, 
                tipo, 
                interes, 
                moratorio, 
                cantidad_pagos, 
                fecha_inicio, 
                fecha_fin, 
                total_pagos, 
                saldo_pendiente, 
                estado
            ) VALUES (:idcliente, :idcobratario, :monto, :tipo, :interes, :moratorio, :cantidad_pagos, :fecha_inicio, :fecha_fin, :total_pagos, :saldo_pendiente, 'activo')";

            $stmtCredito = $this->db->prepare($sqlCredito);
            $stmtCredito->execute([
                ':idcliente' => $datos['idcliente'],
                ':idcobratario' => $datos['idcobratario'] ?? null,
                ':monto' => $datos['monto'],
                ':tipo' => $datos['tipo'],
                ':interes' => $datos['interes'],
                ':moratorio' => $datos['moratorio'],
                ':cantidad_pagos' => $datos['pagos'],
                ':fecha_inicio' => $datos['fecha_inicio'],
                ':fecha_fin' => $fechaFin->format('Y-m-d'),
                ':total_pagos' => $esMensualFlexible ? $datos['monto'] : $totalPagar,
                ':saldo_pendiente' => $esMensualFlexible ? $datos['monto'] : $totalPagar
            ]);

            $idCredito = (int)$this->db->lastInsertId();

            // 3. Insertar pagos programados
            $sqlPago = "INSERT INTO pagos_credito (
                idcredito,
                numero_pago,
                fecha_programada,
                capital_programado,
                interes_programado,
                monto_programado,
                saldo_vivo,
                estado
            ) VALUES (:idcredito, :numero_pago, :fecha_programada, :capital_programado, :interes_programado, :monto_programado, :saldo_vivo, 'pendiente')";

            $stmtPago = $this->db->prepare($sqlPago);

            $fechaPago = clone $fechaInicio;
            $saldo = $datos['monto'];

            if ($esMensualFlexible) {
                $interesPeriodo = round($saldo * ($datos['interes'] / 100), 2);
                $stmtPago->execute([
                    ':idcredito' => $idCredito,
                    ':numero_pago' => 1,
                    ':fecha_programada' => $fechaPago->format('Y-m-d'),
                    ':capital_programado' => 0,
                    ':interes_programado' => $interesPeriodo,
                    ':monto_programado' => $interesPeriodo,
                    ':saldo_vivo' => $saldo
                ]);

                // Confirmar transacción
                $this->db->commit();

                return [
                    'success' => true,
                    'idcredito' => $idCredito,
                    'mensaje' => 'Crédito guardado exitosamente'
                ];
            }

            // Generar cada pago
            for ($i = 1; $i <= $datos['pagos']; $i++) {
                // Calcular capital e interés para este pago
                if ($i == $datos['pagos']) {
                    // Último pago: ajustar para cuadrar exacto
                    $capital = $saldo;
                    $interesPago = $montoInteres - ($interesBase * ($datos['pagos'] - 1));
                } else {
                    $capital = $capitalBase;
                    $interesPago = $interesBase;
                }

                $montoPago = $capital + $interesPago;
                $saldo -= $capital;

                // Obtener fecha de pago
                $fechaPagoStr = $fechaPago->format('Y-m-d');

                $stmtPago->execute([
                    ':idcredito' => $idCredito,
                    ':numero_pago' => $i,
                    ':fecha_programada' => $fechaPagoStr,
                    ':capital_programado' => $capital,
                    ':interes_programado' => $interesPago,
                    ':monto_programado' => $montoPago,
                    ':saldo_vivo' => max(0, $saldo)
                ]);

                // Avanzar a la siguiente fecha de pago
                $fechaPago->add($intervalo);
            }

            // Confirmar transacción
            $this->db->commit();

            return [
                'success' => true,
                'idcredito' => $idCredito,
                'mensaje' => 'Crédito guardado exitosamente'
            ];
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();

            return [
                'success' => false,
                'mensaje' => 'Error al guardar crédito: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener objeto DateInterval según el tipo de crédito
     */
    private function obtenerIntervalo($tipo)
    {
        switch ($tipo) {
            case 'diario':
                return new DateInterval('P1D');
            case 'semanal':
                return new DateInterval('P7D');
            case 'mensual':
                return new DateInterval('P1M');
            default:
                return new DateInterval('P1D');
        }
    }

    /**
     * Obtener un crédito por ID con sus pagos
     */
    public function obtenerCredito($idCredito)
    {
        $sql = "SELECT 
                    c.*,
                    CONCAT(cli.ap_paterno, ' ', cli.ap_materno, ' ', cli.nombres) AS cliente,
                    CONCAT(cob.ap_paterno, ' ', cob.ap_materno, ' ', cob.nombres) AS cobratario
                FROM creditos c
                JOIN personas cli ON c.idcliente = cli.idpersona
                JOIN personas cob ON c.idcobratario = cob.idpersona
                WHERE c.idcredito = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCredito]);

        return $stmt->fetch();
    }

    /**
     * Obtener todos los créditos con información de cliente y cobratario
     */
    public function obtenerTodos()
    {
        $sql = "SELECT 
                    c.idcredito,
                    c.monto,
                    c.saldo_pendiente,
                    c.tipo,
                    c.interes,
                    c.cantidad_pagos,
                    c.fecha_inicio,
                    c.fecha_fin,
                    c.total_pagos,
                    c.total_pagado,
                    c.saldo_pendiente,
                    c.estado,
                    c.fecha_creacion,
                    CONCAT(cli.ap_paterno, ' ', cli.ap_materno, ' ', cli.nombres) AS cliente,
                    CONCAT(cob.ap_paterno, ' ', cob.ap_materno, ' ', cob.nombres) AS cobratario
                FROM creditos c
                JOIN personas cli ON c.idcliente = cli.idpersona
                JOIN personas cob ON c.idcobratario = cob.idpersona
                ORDER BY c.idcredito DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todos los pagos de un crédito
     */
    public function obtenerPagosCredito($idCredito)
    {
        $sql = "SELECT 
                    pc.idpago,
                    pc.idcredito,
                    c.tipo,
                    c.interes,
                    pc.numero_pago,
                    pc.fecha_programada,
                    pc.capital_programado,
                    pc.interes_programado,
                    pc.monto_programado,
                    pc.saldo_vivo,
                    pc.fecha_pago_real,
                    pc.monto_pagado,
                    CASE
                        WHEN pc.estado = 'pagado' THEN 'pagado'
                        WHEN pc.fecha_programada < CURDATE() THEN 'vencido'
                        ELSE pc.estado
                    END AS estado,
                    CASE
                        WHEN pc.estado <> 'pagado' AND pc.fecha_programada < CURDATE() THEN c.moratorio
                        ELSE 0
                    END AS recargo_moratorio,
                    (pc.monto_programado + CASE
                        WHEN pc.estado <> 'pagado' AND pc.fecha_programada < CURDATE() THEN c.moratorio
                        ELSE 0
                    END) AS monto_cobro_actual
                FROM pagos_credito pc
                INNER JOIN creditos c ON c.idcredito = pc.idcredito
                WHERE pc.idcredito = ?
                ORDER BY pc.numero_pago";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCredito]);

        return $stmt->fetchAll();
    }

    /**
     * Obtener créditos de un cliente
     */
    public function obtenerCreditosCliente($idCliente)
    {
        $sql = "SELECT c.*, 
                CONCAT(cl.nombres, ' ', cl.ap_paterno, ' ', cl.ap_materno) as cliente,
                CONCAT(cb.nombres, ' ', cb.ap_paterno, ' ', cb.ap_materno) as cobratario
                FROM creditos c
                JOIN personas cl ON c.idcliente = cl.idpersona
                LEFT JOIN personas cb ON c.idcobratario = cb.idpersona
                WHERE c.idcliente = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCliente]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene el crédito activo no liquidado de un cliente, si existe
     */
    public function obtenerCreditoActivoCliente(int $idCliente)
    {
        $sql = "SELECT 
                    c.idcredito,
                    c.monto,
                    c.saldo_pendiente,
                    c.estado,
                    c.fecha_inicio,
                    c.fecha_fin
                FROM creditos c
                WHERE c.idcliente = :idcliente
                  AND c.estado = 'activo'
                  AND c.saldo_pendiente > 0
                ORDER BY c.fecha_creacion DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idcliente' => $idCliente]);

        return $stmt->fetch();
    }

    /**
     * Obtener créditos asignados a un cobratario
     */
    public function obtenerCreditosCobratario($idCobratario)
    {
        $sql = "SELECT c.*, 
                CONCAT(cl.nombres, ' ', cl.ap_paterno, ' ', cl.ap_materno) as cliente
                FROM creditos c
                JOIN personas cl ON c.idcliente = cl.idpersona
                WHERE c.idcobratario = ?
                ORDER BY c.fecha_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCobratario]);

        return $stmt->fetchAll();
    }

    /**
     * Obtener total cobrado real por cobratario (desde historial de pagos)
     */
    public function obtenerTotalCobradoCobratario($idCobratario): float
    {
        $sql = "SELECT COALESCE(SUM(hp.monto_pagado), 0) AS total_cobrado
                FROM historial_pagos hp
                INNER JOIN creditos c ON c.idcredito = hp.idcredito
                WHERE c.idcobratario = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCobratario]);
        $row = $stmt->fetch();

        return (float)($row['total_cobrado'] ?? 0);
    }

    /**
     * Registrar cobro de una cuota para cobratario
     */
    public function cobrarPagoCobratario(int $idPago, int $idCredito, int $idCobratario, float $montoRecibido, ?int $idUsuarioCobrador = null, bool $confirmarAnticipado = false, bool $esAdmin = false, string $metodoPago = 'efectivo'): array
    {
        return $this->cobrarPagosCobratario(
            [$idPago],
            $idCredito,
            $idCobratario,
            $montoRecibido,
            $idUsuarioCobrador,
            $confirmarAnticipado,
            $esAdmin,
            0.0,
            $metodoPago
        );
    }

    /**
     * Registrar cobro de una o varias cuotas para cobratario
     */
    public function cobrarPagosCobratario(array $idPagos, int $idCredito, int $idCobratario, float $montoRecibido, ?int $idUsuarioCobrador = null, bool $confirmarAnticipado = false, bool $esAdmin = false, float $abonoCapital = 0.0, string $metodoPago = 'efectivo'): array
    {
        try {
            $idPagos = array_values(array_unique(array_filter(array_map('intval', $idPagos))));
            if (empty($idPagos)) {
                throw new Exception('Debes seleccionar al menos una letra para cobrar');
            }

            $metodoPago = strtolower(trim($metodoPago));
            $metodosPermitidos = ['efectivo', 'transferencia', 'tarjeta_debito', 'tarjeta_credito'];
            if (!in_array($metodoPago, $metodosPermitidos, true)) {
                throw new Exception('Método de pago inválido');
            }

            $this->db->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($idPagos), '?'));
            $sqlPago = "SELECT pc.*, c.idcobratario, c.estado AS estado_credito, c.moratorio, c.tipo, c.interes
                        FROM pagos_credito pc
                        INNER JOIN creditos c ON c.idcredito = pc.idcredito
                        WHERE pc.idcredito = ? AND pc.idpago IN ($placeholders)
                        ORDER BY pc.numero_pago
                        FOR UPDATE";
            $stmtPago = $this->db->prepare($sqlPago);
            $stmtPago->execute(array_merge([$idCredito], $idPagos));

            $pagos = $stmtPago->fetchAll();
            if (count($pagos) !== count($idPagos)) {
                throw new Exception('Una o más letras seleccionadas no fueron encontradas');
            }

            $hoy = new DateTime('today');
            $montoCobro = 0.0;
            $pagosAnticipados = [];
            $abonoCapital = max(0, round($abonoCapital, 2));

            $esMensualFlexible = count($pagos) === 1 && (($pagos[0]['tipo'] ?? '') === 'mensual');
            if ($esMensualFlexible && count($idPagos) > 1) {
                throw new Exception('Para crédito mensual flexible solo puedes cobrar una letra por operación');
            }

            foreach ($pagos as $pago) {
                if (!$esAdmin && (int)$pago['idcobratario'] !== $idCobratario) {
                    throw new Exception('No tienes permiso para cobrar este crédito');
                }

                if (($pago['estado'] ?? '') === 'pagado') {
                    throw new Exception('La letra #' . (int)$pago['numero_pago'] . ' ya fue cobrada');
                }

                $fechaProgramada = new DateTime($pago['fecha_programada']);
                if ($fechaProgramada > $hoy) {
                    $pagosAnticipados[] = (int)$pago['numero_pago'];
                }

                $esVencido = $fechaProgramada < $hoy;
                $recargoMoratorio = $esVencido ? (float)($pago['moratorio'] ?? 0) : 0.0;
                $montoBase = (float)$pago['monto_programado'] + $recargoMoratorio;
                if ($esMensualFlexible) {
                    $montoBase += $abonoCapital;
                }
                $montoCobro += $montoBase;
            }

            if (!empty($pagosAnticipados) && !$confirmarAnticipado) {
                throw new Exception('Debes confirmar el cobro anticipado de las letras seleccionadas');
            }

            if ($montoRecibido < $montoCobro) {
                throw new Exception('El monto recibido es menor al total de las letras seleccionadas');
            }

            $cambio = $montoRecibido - $montoCobro;

            $sqlUpdatePago = "UPDATE pagos_credito
                              SET estado = 'pagado', fecha_pago_real = :fecha_pago_real, monto_pagado = :monto_pagado
                              WHERE idpago = :idpago";
            $stmtUpdatePago = $this->db->prepare($sqlUpdatePago);
            $sqlHistorial = "INSERT INTO historial_pagos (
                                idpago,
                                idcredito,
                                fecha_pago,
                                monto_pagado,
                                interes_moratorio,
                                idusuario_cobrador,
                                metodo_pago,
                                observaciones
                            ) VALUES (
                                :idpago,
                                :idcredito,
                                :fecha_pago,
                                :monto_pagado,
                                :interes_moratorio,
                                :idusuario_cobrador,
                                :metodo_pago,
                                :observaciones
                            )";
            $stmtHistorial = $this->db->prepare($sqlHistorial);
            $recibos = [];
            $historialIds = [];
            $pagosCobrados = [];
            $saldoCapitalRestante = null;
            $ultimoNumeroPago = null;
            $interesMensual = null;

            foreach ($pagos as $pago) {
                $fechaProgramada = new DateTime($pago['fecha_programada']);
                $esVencido = $fechaProgramada < $hoy;
                $recargoMoratorio = $esVencido ? (float)($pago['moratorio'] ?? 0) : 0.0;
                $montoPago = (float)$pago['monto_programado'] + $recargoMoratorio;
                $esAnticipado = (new DateTime($pago['fecha_programada'])) > $hoy;

                $abonoCapitalPago = 0.0;
                if (($pago['tipo'] ?? '') === 'mensual') {
                    $abonoCapitalPago = $abonoCapital;
                    $montoPago += $abonoCapitalPago;

                    $saldoCapitalActual = (float)$pago['saldo_vivo'];
                    $saldoCapitalRestante = max(0, round($saldoCapitalActual - $abonoCapitalPago, 2));
                    $ultimoNumeroPago = (int)$pago['numero_pago'];
                    $interesMensual = (float)$pago['interes'];
                }

                $stmtUpdatePago->execute([
                    ':fecha_pago_real' => $hoy->format('Y-m-d'),
                    ':monto_pagado' => $montoPago,
                    ':idpago' => (int)$pago['idpago'],
                ]);

                $stmtHistorial->execute([
                    ':idpago' => (int)$pago['idpago'],
                    ':idcredito' => $idCredito,
                    ':fecha_pago' => $hoy->format('Y-m-d'),
                    ':monto_pagado' => $montoPago,
                    ':interes_moratorio' => $recargoMoratorio,
                    ':idusuario_cobrador' => $idUsuarioCobrador,
                    ':metodo_pago' => $metodoPago,
                    ':observaciones' => $esAnticipado
                        ? 'Cobro anticipado registrado por cobratario'
                        : (($pago['tipo'] ?? '') === 'mensual' && $abonoCapitalPago > 0
                            ? 'Cobro mensual con abono a capital'
                            : 'Cobro registrado por cobratario'),
                ]);
                $idHistorial = (int)$this->db->lastInsertId();

                $pagosCobrados[] = [
                    'idpago' => (int)$pago['idpago'],
                    'numero_pago' => (int)$pago['numero_pago'],
                    'monto' => $montoPago,
                    'moratorio' => $recargoMoratorio,
                    'abono_capital' => $abonoCapitalPago,
                ];
                $historialIds[] = $idHistorial;
                $recibos[] = [
                    'idpago' => (int)$pago['idpago'],
                    'idhistorial' => $idHistorial,
                    'idcredito' => (int)$idCredito,
                ];
            }

            if ($esMensualFlexible) {
                $sqlActualizaSaldo = "UPDATE creditos SET saldo_pendiente = :saldo WHERE idcredito = :idcredito";
                $stmtActualizaSaldo = $this->db->prepare($sqlActualizaSaldo);
                $stmtActualizaSaldo->execute([
                    ':saldo' => $saldoCapitalRestante,
                    ':idcredito' => $idCredito,
                ]);

                if ($saldoCapitalRestante <= 0) {
                    $sqlEstado = "UPDATE creditos SET estado = 'completado' WHERE idcredito = :idcredito";
                    $stmtEstado = $this->db->prepare($sqlEstado);
                    $stmtEstado->execute([':idcredito' => $idCredito]);
                } else {
                    $sqlNuevoPago = "INSERT INTO pagos_credito (
                                        idcredito,
                                        numero_pago,
                                        fecha_programada,
                                        capital_programado,
                                        interes_programado,
                                        monto_programado,
                                        saldo_vivo,
                                        estado
                                    ) VALUES (
                                        :idcredito,
                                        :numero_pago,
                                        :fecha_programada,
                                        :capital_programado,
                                        :interes_programado,
                                        :monto_programado,
                                        :saldo_vivo,
                                        'pendiente'
                                    )";
                    $stmtNuevoPago = $this->db->prepare($sqlNuevoPago);
                    $fechaSiguiente = (clone $hoy)->add(new DateInterval('P1M'));
                    $interesSiguiente = round($saldoCapitalRestante * ($interesMensual / 100), 2);
                    $stmtNuevoPago->execute([
                        ':idcredito' => $idCredito,
                        ':numero_pago' => ((int)$ultimoNumeroPago) + 1,
                        ':fecha_programada' => $fechaSiguiente->format('Y-m-d'),
                        ':capital_programado' => 0,
                        ':interes_programado' => $interesSiguiente,
                        ':monto_programado' => $interesSiguiente,
                        ':saldo_vivo' => $saldoCapitalRestante,
                    ]);
                }
            }

            $sqlPendientes = "SELECT COUNT(*) AS pendientes
                              FROM pagos_credito
                              WHERE idcredito = :idcredito AND estado <> 'pagado'";
            $stmtPendientes = $this->db->prepare($sqlPendientes);
            $stmtPendientes->execute([':idcredito' => $idCredito]);
            $pendientes = (int)($stmtPendientes->fetch()['pendientes'] ?? 0);

            if ($pendientes === 0) {
                $sqlEstado = "UPDATE creditos SET estado = 'completado' WHERE idcredito = :idcredito";
                $stmtEstado = $this->db->prepare($sqlEstado);
                $stmtEstado->execute([':idcredito' => $idCredito]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'mensaje' => count($pagosCobrados) > 1
                    ? 'Cobros registrados correctamente'
                    : 'Cobro registrado correctamente',
                'monto_cobrado' => round($montoCobro, 2),
                'cambio' => $cambio,
                'idpago' => count($pagosCobrados) === 1 ? $pagosCobrados[0]['idpago'] : null,
                'idcredito' => $idCredito,
                'pagos_cobrados' => $pagosCobrados,
                'recibos' => $recibos,
                'historial_ids' => $historialIds,
                'cobro_anticipado' => !empty($pagosAnticipados),
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'mensaje' => $e->getMessage(),
            ];
        }
    }

    /**
     * Actualizar estado de un crédito
     */
    public function actualizarEstadoCredito($idCredito, $nuevoEstado)
    {
        $sql = "UPDATE creditos SET estado = ? WHERE idcredito = ?";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$nuevoEstado, $idCredito]);
    }

    /**
     * Obtener todos los datos para generar recibo de cobro
     */
    public function obtenerDatosReciboCobroPorPago(int $idPago, int $idCredito): array
    {
        $sql = "SELECT hp.idhistorial
                FROM historial_pagos hp
                INNER JOIN pagos_credito cr ON hp.idpago = cr.idpago
                INNER JOIN creditos c ON c.idcredito = cr.idcredito
                WHERE cr.idpago = :idpago AND c.idcredito = :idcredito
                ORDER BY hp.idhistorial DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':idpago' => $idPago,
            ':idcredito' => $idCredito,
        ]);

        $resultado = $stmt->fetch();
        if (!$resultado) {
            return ['success' => false, 'error' => 'Registro de cobro no encontrado'];
        }

        return $this->obtenerDatosReciboCobro([(int)$resultado['idhistorial']], $idCredito);
    }

    public function obtenerDatosReciboCobro(array $historialIds, int $idCredito): array
    {
        $historialIds = array_values(array_unique(array_filter(array_map('intval', $historialIds))));
        if (empty($historialIds) || $idCredito <= 0) {
            return ['success' => false, 'error' => 'Datos inválidos para generar recibo'];
        }

        $placeholders = implode(',', array_fill(0, count($historialIds), '?'));
        $sql = "SELECT 
                    hp.idhistorial,
                    hp.fecha_pago,
                    hp.monto_pagado,
                    hp.interes_moratorio,
                    hp.metodo_pago,
                    COALESCE(
                        CONCAT(pcob.ap_paterno, ' ', pcob.ap_materno, ' ', pcob.nombres),
                        u.username
                    ) AS cobrador_nombre,
                    c.idcredito,
                    c.monto,
                    c.saldo_pendiente,
                    c.tipo,
                    c.interes,
                    c.cantidad_pagos,
                    c.fecha_inicio,
                    cr.numero_pago,
                    cr.monto_programado,
                    cr.interes_programado,
                    cr.fecha_programada,
                    p.idpersona,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre_completo,
                    p.curp AS numero_cedula,
                    p.telefono,
                    CONCAT(p.dom_calle, ' ', p.dom_numero) AS direccion,
                    m.nombre AS ciudad
                FROM historial_pagos hp
                INNER JOIN pagos_credito cr ON hp.idpago = cr.idpago
                INNER JOIN creditos c ON c.idcredito = cr.idcredito
                INNER JOIN personas p ON p.idpersona = c.idcliente
                INNER JOIN usuarios u ON u.idusuario = hp.idusuario_cobrador
                LEFT JOIN personas pcob ON pcob.idpersona = u.idpersona
                LEFT JOIN municipios m ON m.idmunicipio = p.idmunicipio
                WHERE hp.idhistorial IN ($placeholders) AND c.idcredito = ?
                ORDER BY cr.numero_pago ASC, hp.idhistorial ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($historialIds, [$idCredito]));

        $resultados = $stmt->fetchAll();
        if (empty($resultados)) {
            return ['success' => false, 'error' => 'Registro de cobro no encontrado'];
        }

        $primero = $resultados[0];
        $totalProgramado = 0.0;
        $totalCobrado = 0.0;
        $totalMoratorio = 0.0;
        $pagos = [];

        foreach ($resultados as $resultado) {
            $montoProgramado = (float)$resultado['monto_programado'];
            $montoPagado = (float)$resultado['monto_pagado'];
            $fechaProgramada = new DateTime($resultado['fecha_programada']);
            $fechaPago = new DateTime($resultado['fecha_pago']);
            $recargoMoratorio = isset($resultado['interes_moratorio'])
                ? (float)$resultado['interes_moratorio']
                : max(0, $montoPagado - $montoProgramado);
            $fueVencida = ($fechaProgramada < $fechaPago) || $recargoMoratorio > 0;

            $totalProgramado += $montoProgramado;
            $totalCobrado += $montoPagado;
            $totalMoratorio += $recargoMoratorio;

            $pagos[] = [
                'idhistorial' => (int)$resultado['idhistorial'],
                'numero_pago' => $resultado['numero_pago'],
                'monto_programado' => $montoProgramado,
                'monto_pagado' => $montoPagado,
                'fecha_programada' => $resultado['fecha_programada'],
                'interes_programado' => (float)$resultado['interes_programado'],
                'fue_vencida' => $fueVencida,
                'recargo_moratorio' => round($recargoMoratorio, 2),
            ];
        }

        $numeroBase = count($resultados) > 1
            ? ($resultados[0]['idhistorial'] . '-' . end($resultados)['idhistorial'])
            : $resultados[0]['idhistorial'];

        return [
            'success' => true,
            'cobro' => [
                'idhistorial' => (int)$primero['idhistorial'],
                'fecha' => $primero['fecha_pago'],
                'numero_recibo' => 'RCP-' . $numeroBase . date('Ymd', strtotime($primero['fecha_pago'])),
                'cliente' => [
                    'nombre' => $primero['nombre_completo'],
                    'cedula' => $primero['numero_cedula'],
                    'telefono' => $primero['telefono'],
                    'ciudad' => $primero['ciudad'] ?? 'N/A',
                ],
                'credito' => [
                    'idcredito' => $primero['idcredito'],
                    'monto_original' => $primero['monto'],
                    'saldo_pendiente' => (float)$primero['saldo_pendiente'],
                    'tipo' => ucfirst($primero['tipo']),
                    'pagos_totales' => $primero['cantidad_pagos'],
                    'interes' => $primero['interes'],
                ],
                'pagos' => $pagos,
                'resumen' => [
                    'cantidad_pagos_cobrados' => count($pagos),
                    'total_programado' => round($totalProgramado, 2),
                    'total_moratorio' => round($totalMoratorio, 2),
                    'total_cobrado' => round($totalCobrado, 2),
                ],
                'cobrador' => $primero['cobrador_nombre'],
                'metodo_pago' => $this->formatearMetodoPago((string)$primero['metodo_pago']),
            ]
        ];
    }

    private function formatearMetodoPago(string $metodo): string
    {
        $mapa = [
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'tarjeta_debito' => 'Tarjeta de debito',
            'tarjeta_credito' => 'Tarjeta de credito',
        ];

        $metodoNormalizado = strtolower(trim($metodo));
        return $mapa[$metodoNormalizado] ?? ucfirst($metodoNormalizado);
    }
}
