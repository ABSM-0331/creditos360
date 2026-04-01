<?php

class CreditosRepository
{
    private PDO $db;
    private ?array $tiposCreditoCache = null;

    public function __construct()
    {
        $this->db = DBC::get();
        $this->asegurarCatalogoTiposCredito();
        $this->asegurarColumnaGrupoCobro();
        $this->asegurarTriggerSaldoCredito();
        $this->normalizarSaldosCreditos();
    }

    /**
     * Guardar un crédito completo con todos sus pagos
     */
    public function guardarCredito($datos)
    {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();

            $esMensualFlexible = $this->esTipoFlexible((string)($datos['tipo'] ?? ''));
            $esQuincenal = $this->esTipoQuincenal((string)($datos['tipo'] ?? ''));

            // 1. Calcular datos del crédito
            if ($esQuincenal && !$esMensualFlexible) {
                $mesesDuracion = $datos['pagos'] / 2;
                $montoInteres = ($datos['monto'] * ($datos['interes'] / 100)) * $mesesDuracion;
            } else {
                $montoInteres = $datos['monto'] * ($datos['interes'] / 100);
            }
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

    private function esTipoQuincenal(string $tipo): bool
    {
        return strtolower(trim($tipo)) === 'quincenal';
    }

    /**
     * Obtener objeto DateInterval según el tipo de crédito
     */
    private function obtenerIntervalo($tipo)
    {
        $tipoData = $this->obtenerTipoCreditoPorClave((string)$tipo);
        if ($tipoData && (int)($tipoData['es_flexible'] ?? 0) === 1) {
            return new DateInterval('P1M');
        }

        $dias = (int)($tipoData['dias_intervalo'] ?? 1);
        if ($dias <= 0) {
            $dias = 1;
        }

        return new DateInterval('P' . $dias . 'D');
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
                    c.moratorio AS moratorio_base
                FROM pagos_credito pc
                INNER JOIN creditos c ON c.idcredito = pc.idcredito
                WHERE pc.idcredito = ?
                ORDER BY pc.numero_pago";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idCredito]);

        $pagos = $stmt->fetchAll();
        $hoy = $this->fechaHoyOperativa();

        foreach ($pagos as &$pago) {
            $recargoMoratorio = $this->calcularRecargoMoratorio(
                (string)($pago['tipo'] ?? 'diario'),
                (float)($pago['moratorio_base'] ?? 0),
                (string)($pago['fecha_programada'] ?? ''),
                $hoy,
                (string)($pago['estado'] ?? 'pendiente')
            );

            $pago['recargo_moratorio'] = $recargoMoratorio;
            $pago['monto_cobro_actual'] = round((float)($pago['monto_programado'] ?? 0) + $recargoMoratorio, 2);
        }
        unset($pago);

        return $pagos;
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

            $hoy = $this->fechaHoyOperativa();
            $montoCobro = 0.0;
            $pagosAnticipados = [];
            $abonoCapital = max(0, round($abonoCapital, 2));

            $esMensualFlexible = count($pagos) === 1 && $this->esTipoFlexible((string)($pagos[0]['tipo'] ?? ''));
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

                $recargoMoratorio = $this->calcularRecargoMoratorio(
                    (string)($pago['tipo'] ?? 'diario'),
                    (float)($pago['moratorio'] ?? 0),
                    (string)($pago['fecha_programada'] ?? ''),
                    $hoy,
                    (string)($pago['estado'] ?? 'pendiente')
                );
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
            $grupoCobro = $this->generarGrupoCobroId();
            $sqlHistorial = "INSERT INTO historial_pagos (
                                idpago,
                                idcredito,
                                fecha_pago,
                                monto_pagado,
                                interes_moratorio,
                                idusuario_cobrador,
                                grupo_cobro,
                                metodo_pago,
                                observaciones
                            ) VALUES (
                                :idpago,
                                :idcredito,
                                :fecha_pago,
                                :monto_pagado,
                                :interes_moratorio,
                                :idusuario_cobrador,
                                :grupo_cobro,
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
                $recargoMoratorio = $this->calcularRecargoMoratorio(
                    (string)($pago['tipo'] ?? 'diario'),
                    (float)($pago['moratorio'] ?? 0),
                    (string)($pago['fecha_programada'] ?? ''),
                    $hoy,
                    (string)($pago['estado'] ?? 'pendiente')
                );
                $montoPago = (float)$pago['monto_programado'] + $recargoMoratorio;
                $esAnticipado = (new DateTime($pago['fecha_programada'])) > $hoy;

                $abonoCapitalPago = 0.0;
                if ($this->esTipoFlexible((string)($pago['tipo'] ?? ''))) {
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
                    ':grupo_cobro' => $grupoCobro,
                    ':metodo_pago' => $metodoPago,
                    ':observaciones' => $esAnticipado
                        ? 'Cobro anticipado registrado por cobratario'
                        : ($this->esTipoFlexible((string)($pago['tipo'] ?? '')) && $abonoCapitalPago > 0
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
                'grupo_cobro' => $grupoCobro,
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
        $sql = "SELECT hp.idhistorial, hp.grupo_cobro
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

        $grupoCobro = trim((string)($resultado['grupo_cobro'] ?? ''));
        if ($grupoCobro !== '') {
            $idsRelacionados = $this->obtenerIdsHistorialPorGrupoCobro($grupoCobro, $idCredito);
            if (!empty($idsRelacionados)) {
                return $this->obtenerDatosReciboCobro($idsRelacionados, $idCredito);
            }
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
                    c.total_pagos,
                    c.tipo,
                    c.interes,
                    c.cantidad_pagos,
                    c.fecha_inicio,
                    cr.numero_pago,
                    cr.monto_programado,
                    cr.interes_programado,
                    cr.saldo_vivo,
                    cr.fecha_programada,
                    p.idpersona,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre_completo,
                    p.curp AS numero_cedula,
                    p.email AS cliente_email,
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
                'saldo_vivo' => (float)$resultado['saldo_vivo'],
                'fue_vencida' => $fueVencida,
                'recargo_moratorio' => round($recargoMoratorio, 2),
            ];
        }

        $saldoRestanteMomento = 0.0;
        $corteHistorialId = max(array_map(static fn($p) => (int)($p['idhistorial'] ?? 0), $pagos));
        $esTipoFlexible = $this->esTipoFlexible((string)($primero['tipo'] ?? ''));

        if ($esTipoFlexible) {
            $ultimoPago = end($pagos) ?: [];
            $saldoVivoAntes = (float)($ultimoPago['saldo_vivo'] ?? 0);
            $montoPagadoUltimo = (float)($ultimoPago['monto_pagado'] ?? 0);
            $montoProgramadoUltimo = (float)($ultimoPago['monto_programado'] ?? 0);
            $moratorioUltimo = (float)($ultimoPago['recargo_moratorio'] ?? 0);
            $abonoCapitalUltimo = max(0, $montoPagadoUltimo - $montoProgramadoUltimo - $moratorioUltimo);
            $saldoRestanteMomento = max(0, round($saldoVivoAntes - $abonoCapitalUltimo, 2));
        } else {
            $totalOriginal = (float)($primero['total_pagos'] ?? 0);
            // Para saldo historico de creditos no flexibles, no descontar moratorio del saldo del credito.
            // El moratorio es un recargo de cobranza, no amortizacion del total programado del credito.
            $stmtAcumulado = $this->db->prepare("SELECT COALESCE(SUM(monto_pagado - COALESCE(interes_moratorio, 0)), 0) AS total_amortizado_hasta_corte FROM historial_pagos WHERE idcredito = :idcredito AND idhistorial <= :idhistorial_corte");
            $stmtAcumulado->execute([
                ':idcredito' => (int)$primero['idcredito'],
                ':idhistorial_corte' => $corteHistorialId,
            ]);
            $totalAmortizadoHastaCorte = (float)($stmtAcumulado->fetch()['total_amortizado_hasta_corte'] ?? 0);
            $saldoRestanteMomento = max(0, round($totalOriginal - $totalAmortizadoHastaCorte, 2));
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
                    'email' => $primero['cliente_email'] ?? '',
                    'telefono' => $primero['telefono'],
                    'ciudad' => $primero['ciudad'] ?? 'N/A',
                ],
                'credito' => [
                    'idcredito' => $primero['idcredito'],
                    'monto_original' => $primero['monto'],
                    'saldo_pendiente' => (float)$primero['saldo_pendiente'],
                    'saldo_pendiente_momento' => $saldoRestanteMomento,
                    'tipo' => ucfirst($primero['tipo']),
                    'pagos_totales' => $primero['cantidad_pagos'],
                    'interes' => $primero['interes'],
                ],
                'pagos' => $pagos,
                'historial_ids' => array_values(array_map(static fn($pago) => (int)$pago['idhistorial'], $pagos)),
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

    private function calcularRecargoMoratorio(string $tipoCredito, float $moratorioUnitario, string $fechaProgramada, DateTime $hoy, string $estadoPago): float
    {
        if (strtolower(trim($estadoPago)) === 'pagado') {
            return 0.0;
        }

        if ($moratorioUnitario <= 0 || trim($fechaProgramada) === '') {
            return 0.0;
        }

        try {
            $fecha = new DateTime($fechaProgramada);
            $fecha->setTime(0, 0, 0);

            $hoySinHora = clone $hoy;
            $hoySinHora->setTime(0, 0, 0);

            if ($fecha >= $hoySinHora) {
                return 0.0;
            }

            $periodos = $this->calcularPeriodosVencidos($tipoCredito, $fecha, $hoySinHora);
            return round($moratorioUnitario * $periodos, 2);
        } catch (Throwable $e) {
            return 0.0;
        }
    }

    private function calcularPeriodosVencidos(string $tipoCredito, DateTime $fechaProgramada, DateTime $hoy): int
    {
        $diasDiferencia = (int)$fechaProgramada->diff($hoy)->days;

        if ($this->esTipoFlexible($tipoCredito)) {
            $anios = (int)$hoy->format('Y') - (int)$fechaProgramada->format('Y');
            $meses = (int)$hoy->format('n') - (int)$fechaProgramada->format('n');
            $totalMeses = ($anios * 12) + $meses;

            if ((int)$hoy->format('j') < (int)$fechaProgramada->format('j')) {
                $totalMeses -= 1;
            }

            return max(0, $totalMeses);
        }

        $tipoData = $this->obtenerTipoCreditoPorClave($tipoCredito);
        $diasIntervalo = (int)($tipoData['dias_intervalo'] ?? 1);
        if ($diasIntervalo <= 0) {
            $diasIntervalo = 1;
        }

        return max(0, intdiv($diasDiferencia, $diasIntervalo));
    }

    public function obtenerTiposCredito(bool $soloActivos = true): array
    {
        $sql = "SELECT idtipo, tipo, cantidad_pagos, interes_default, dias_intervalo, es_flexible, activo
                FROM tipos_credito";

        $params = [];
        if ($soloActivos) {
            $sql .= " WHERE activo = 1";
        }

        $sql .= " ORDER BY tipo ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerTipoCreditoPorClave(string $tipo): ?array
    {
        $tipo = strtolower(trim($tipo));
        if ($tipo === '') {
            return null;
        }

        if ($this->tiposCreditoCache === null) {
            $this->tiposCreditoCache = [];
            $tipos = $this->obtenerTiposCredito(false);
            foreach ($tipos as $item) {
                $clave = strtolower((string)($item['tipo'] ?? ''));
                if ($clave !== '') {
                    $this->tiposCreditoCache[$clave] = $item;
                }
            }
        }

        return $this->tiposCreditoCache[$tipo] ?? null;
    }

    public function obtenerTipoCreditoPorId(int $idTipo): ?array
    {
        $stmt = $this->db->prepare("SELECT idtipo, tipo, cantidad_pagos, interes_default, dias_intervalo, es_flexible, activo FROM tipos_credito WHERE idtipo = :idtipo LIMIT 1");
        $stmt->execute([':idtipo' => $idTipo]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function crearTipoCredito(array $datos): int
    {
        $stmt = $this->db->prepare("INSERT INTO tipos_credito (tipo, cantidad_pagos, interes_default, dias_intervalo, es_flexible, activo)
                                   VALUES (:tipo, :cantidad_pagos, :interes_default, :dias_intervalo, 0, 1)");
        $stmt->execute([
            ':tipo' => strtolower(trim((string)$datos['tipo'])),
            ':cantidad_pagos' => (int)$datos['cantidad_pagos'],
            ':interes_default' => (float)$datos['interes_default'],
            ':dias_intervalo' => max(1, (int)($datos['dias_intervalo'] ?? 1)),
        ]);

        $this->tiposCreditoCache = null;
        return (int)$this->db->lastInsertId();
    }

    public function actualizarTipoCredito(int $idTipo, array $datos): bool
    {
        $tipoActual = $this->obtenerTipoCreditoPorId($idTipo);
        if (!$tipoActual) {
            throw new Exception('Tipo de crédito no encontrado');
        }

        $tipoNuevo = strtolower(trim((string)$datos['tipo']));
        $esFlexible = (int)($tipoActual['es_flexible'] ?? 0) === 1;

        if ($esFlexible && $tipoNuevo !== (string)$tipoActual['tipo']) {
            throw new Exception('No puedes cambiar la clave de un tipo flexible');
        }

        $stmt = $this->db->prepare("UPDATE tipos_credito
                                    SET tipo = :tipo,
                                        cantidad_pagos = :cantidad_pagos,
                                        interes_default = :interes_default,
                                        dias_intervalo = :dias_intervalo,
                                        activo = :activo
                                    WHERE idtipo = :idtipo");

        $ok = $stmt->execute([
            ':tipo' => $tipoNuevo,
            ':cantidad_pagos' => (int)$datos['cantidad_pagos'],
            ':interes_default' => (float)$datos['interes_default'],
            ':dias_intervalo' => $esFlexible ? 30 : max(1, (int)($datos['dias_intervalo'] ?? 1)),
            ':activo' => isset($datos['activo']) ? (int)$datos['activo'] : 1,
            ':idtipo' => $idTipo,
        ]);

        if ($ok && (string)$tipoActual['tipo'] !== $tipoNuevo) {
            $stmtCreditos = $this->db->prepare("UPDATE creditos SET tipo = :tipo_nuevo WHERE tipo = :tipo_actual");
            $stmtCreditos->execute([
                ':tipo_nuevo' => $tipoNuevo,
                ':tipo_actual' => (string)$tipoActual['tipo'],
            ]);
        }

        $this->tiposCreditoCache = null;
        return $ok;
    }

    public function eliminarTipoCredito(int $idTipo): bool
    {
        $tipo = $this->obtenerTipoCreditoPorId($idTipo);
        if (!$tipo) {
            throw new Exception('Tipo de crédito no encontrado');
        }

        if ((int)($tipo['es_flexible'] ?? 0) === 1) {
            throw new Exception('No puedes eliminar un tipo flexible del sistema');
        }

        $stmtUso = $this->db->prepare("SELECT COUNT(*) AS total FROM creditos WHERE tipo = :tipo");
        $stmtUso->execute([':tipo' => (string)$tipo['tipo']]);
        $total = (int)($stmtUso->fetch()['total'] ?? 0);
        if ($total > 0) {
            throw new Exception('No puedes eliminar este tipo porque ya está asignado a créditos existentes');
        }

        $stmt = $this->db->prepare("DELETE FROM tipos_credito WHERE idtipo = :idtipo");
        $ok = $stmt->execute([':idtipo' => $idTipo]);
        $this->tiposCreditoCache = null;
        return $ok;
    }

    public function obtenerConfiguracionesTiposCredito(): array
    {
        $tipos = $this->obtenerTiposCredito(true);
        $configuraciones = [];

        foreach ($tipos as $tipo) {
            $clave = strtolower((string)$tipo['tipo']);
            $esFlexible = (int)($tipo['es_flexible'] ?? 0) === 1;
            $dias = max(1, (int)($tipo['dias_intervalo'] ?? 1));

            $configuraciones[$clave] = [
                'pagos' => (int)$tipo['cantidad_pagos'],
                'interes' => (float)$tipo['interes_default'],
                'moratorio' => 35,
                'modo' => $esFlexible ? 'flexible' : 'fijo',
                'intervalo' => $esFlexible ? 'P1M' : ('P' . $dias . 'D'),
                'dias_intervalo' => $dias,
                'es_flexible' => $esFlexible,
            ];
        }

        return $configuraciones;
    }

    private function esTipoFlexible(string $tipo): bool
    {
        $tipoData = $this->obtenerTipoCreditoPorClave($tipo);
        return (int)($tipoData['es_flexible'] ?? 0) === 1;
    }

    private function fechaHoyOperativa(): DateTime
    {
        try {
            $timezone = (string)(getenv('APP_TIMEZONE') ?: 'America/Mexico_City');
            $hoy = new DateTime('now', new DateTimeZone($timezone));
            $hoy->setTime(0, 0, 0);
            return $hoy;
        } catch (Throwable $e) {
            return new DateTime('today');
        }
    }

    private function obtenerIdsHistorialPorGrupoCobro(string $grupoCobro, int $idCredito): array
    {
        $sql = "SELECT hp.idhistorial
                FROM historial_pagos hp
                WHERE hp.grupo_cobro = :grupo_cobro AND hp.idcredito = :idcredito
                ORDER BY hp.idhistorial ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':grupo_cobro' => $grupoCobro,
            ':idcredito' => $idCredito,
        ]);

        $rows = $stmt->fetchAll();
        return array_values(array_map(static fn($row) => (int)$row['idhistorial'], $rows));
    }

    private function generarGrupoCobroId(): string
    {
        try {
            return 'GC' . date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
        } catch (Throwable $e) {
            return 'GC' . str_replace('.', '', (string)microtime(true));
        }
    }

    private function asegurarColumnaGrupoCobro(): void
    {
        try {
            $sqlExiste = "SELECT COUNT(*) AS total
                          FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE()
                            AND TABLE_NAME = 'historial_pagos'
                            AND COLUMN_NAME = 'grupo_cobro'";
            $stmtExiste = $this->db->query($sqlExiste);
            $existe = (int)($stmtExiste->fetch()['total'] ?? 0) > 0;

            if (!$existe) {
                $this->db->exec("ALTER TABLE historial_pagos
                    ADD COLUMN grupo_cobro VARCHAR(40) NULL AFTER idusuario_cobrador,
                    ADD INDEX idx_historial_grupo_cobro (grupo_cobro)");
            }
        } catch (Throwable $e) {
            // No bloquear el flujo principal si la migración automática no se puede aplicar.
        }
    }

    private function asegurarTriggerSaldoCredito(): void
    {
        try {
            $sqlTrigger = "SELECT ACTION_STATEMENT
                                                     FROM INFORMATION_SCHEMA.TRIGGERS
                                                     WHERE TRIGGER_SCHEMA = DATABASE()
                                                         AND TRIGGER_NAME = 'actualizar_saldo_credito'
                                                     LIMIT 1";
            $stmtTrigger = $this->db->query($sqlTrigger);
            $triggerActual = (string)(($stmtTrigger ? $stmtTrigger->fetch()['ACTION_STATEMENT'] : '') ?? '');

            if (
                $triggerActual !== ''
                && strpos($triggerActual, 'interes_moratorio') !== false
                && strpos($triggerActual, 'v_descuento_saldo') !== false
            ) {
                return;
            }

            $this->db->exec("DROP TRIGGER IF EXISTS actualizar_saldo_credito");

            $sql = "
                CREATE TRIGGER actualizar_saldo_credito
                AFTER INSERT ON historial_pagos
                FOR EACH ROW
                BEGIN
                    DECLARE v_tipo VARCHAR(20) DEFAULT 'diario';
                    DECLARE v_monto_programado DECIMAL(10,2) DEFAULT 0.00;
                    DECLARE v_moratorio DECIMAL(10,2) DEFAULT 0.00;
                    DECLARE v_descuento_saldo DECIMAL(10,2) DEFAULT 0.00;

                    SELECT c.tipo, COALESCE(pc.monto_programado, 0)
                    INTO v_tipo, v_monto_programado
                    FROM creditos c
                    LEFT JOIN pagos_credito pc ON pc.idpago = NEW.idpago
                    WHERE c.idcredito = NEW.idcredito
                    LIMIT 1;

                    SET v_moratorio = COALESCE(NEW.interes_moratorio, 0);

                    IF v_tipo = 'mensual' THEN
                        SET v_descuento_saldo = GREATEST(NEW.monto_pagado - v_monto_programado - v_moratorio, 0);
                    ELSE
                        SET v_descuento_saldo = GREATEST(NEW.monto_pagado - v_moratorio, 0);
                    END IF;

                    UPDATE creditos
                    SET
                        total_pagado = total_pagado + NEW.monto_pagado,
                        saldo_pendiente = GREATEST(saldo_pendiente - v_descuento_saldo, 0)
                    WHERE idcredito = NEW.idcredito;
                END
            ";

            $this->db->exec($sql);
        } catch (Throwable $e) {
            // No bloquear el flujo principal si no hay permisos para crear triggers.
        }
    }

    private function asegurarCatalogoTiposCredito(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS tipos_credito (
                idtipo INT(11) NOT NULL AUTO_INCREMENT,
                tipo VARCHAR(50) NOT NULL,
                cantidad_pagos INT(11) NOT NULL,
                interes_default DECIMAL(5,2) NOT NULL,
                dias_intervalo INT(11) NOT NULL DEFAULT 1,
                es_flexible TINYINT(1) NOT NULL DEFAULT 0,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (idtipo),
                UNIQUE KEY uk_tipo_credito_tipo (tipo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

            $sqlExisteCol = "SELECT COUNT(*) AS total
                            FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE()
                              AND TABLE_NAME = 'tipos_credito'
                              AND COLUMN_NAME = 'es_flexible'";
            $stmtCol = $this->db->query($sqlExisteCol);
            $existeCol = (int)($stmtCol->fetch()['total'] ?? 0) > 0;
            if (!$existeCol) {
                $this->db->exec("ALTER TABLE tipos_credito ADD COLUMN es_flexible TINYINT(1) NOT NULL DEFAULT 0 AFTER dias_intervalo");
            }

            $stmtCount = $this->db->query("SELECT COUNT(*) AS total FROM tipos_credito");
            $total = (int)($stmtCount->fetch()['total'] ?? 0);

            if ($total === 0) {
                $stmtSeed = $this->db->prepare("INSERT INTO tipos_credito (tipo, cantidad_pagos, interes_default, dias_intervalo, es_flexible, activo)
                                                VALUES (:tipo, :cantidad_pagos, :interes_default, :dias_intervalo, :es_flexible, 1)");

                $semillas = [
                    ['tipo' => 'diario', 'cantidad_pagos' => 35, 'interes_default' => 22.5, 'dias_intervalo' => 1, 'es_flexible' => 0],
                    ['tipo' => 'semanal', 'cantidad_pagos' => 12, 'interes_default' => 50, 'dias_intervalo' => 7, 'es_flexible' => 0],
                    ['tipo' => 'quincenal', 'cantidad_pagos' => 6, 'interes_default' => 30, 'dias_intervalo' => 15, 'es_flexible' => 0],
                    ['tipo' => 'mensual', 'cantidad_pagos' => 3, 'interes_default' => 50, 'dias_intervalo' => 30, 'es_flexible' => 1],
                ];

                foreach ($semillas as $seed) {
                    $stmtSeed->execute([
                        ':tipo' => $seed['tipo'],
                        ':cantidad_pagos' => $seed['cantidad_pagos'],
                        ':interes_default' => $seed['interes_default'],
                        ':dias_intervalo' => $seed['dias_intervalo'],
                        ':es_flexible' => $seed['es_flexible'],
                    ]);
                }
            } else {
                $this->asegurarTipoCreditoBase('diario', 35, 22.5, 1, false);
                $this->asegurarTipoCreditoBase('semanal', 12, 50, 7, false);
                $this->asegurarTipoCreditoBase('quincenal', 6, 30, 15, false);
                $this->asegurarTipoCreditoBase('mensual', 3, 50, 30, true);
            }

            $sqlTipoColumna = "SELECT DATA_TYPE
                               FROM INFORMATION_SCHEMA.COLUMNS
                               WHERE TABLE_SCHEMA = DATABASE()
                                 AND TABLE_NAME = 'creditos'
                                 AND COLUMN_NAME = 'tipo'
                               LIMIT 1";
            $stmtTipo = $this->db->query($sqlTipoColumna);
            $dataType = strtolower((string)(($stmtTipo ? $stmtTipo->fetch()['DATA_TYPE'] : '') ?? ''));
            if ($dataType === 'enum') {
                $this->db->exec("ALTER TABLE creditos MODIFY tipo VARCHAR(50) NOT NULL");
            }
        } catch (Throwable $e) {
            // No bloquear el flujo principal si la migración automática no se puede aplicar.
        }
    }

    private function asegurarTipoCreditoBase(string $tipo, int $cantidadPagos, float $interes, int $diasIntervalo, bool $esFlexible): void
    {
        $stmt = $this->db->prepare("SELECT idtipo FROM tipos_credito WHERE tipo = :tipo LIMIT 1");
        $stmt->execute([':tipo' => $tipo]);
        $existe = $stmt->fetch();

        if (!$existe) {
            $stmtInsert = $this->db->prepare("INSERT INTO tipos_credito (tipo, cantidad_pagos, interes_default, dias_intervalo, es_flexible, activo)
                                             VALUES (:tipo, :cantidad_pagos, :interes_default, :dias_intervalo, :es_flexible, 1)");
            $stmtInsert->execute([
                ':tipo' => $tipo,
                ':cantidad_pagos' => $cantidadPagos,
                ':interes_default' => $interes,
                ':dias_intervalo' => $diasIntervalo,
                ':es_flexible' => $esFlexible ? 1 : 0,
            ]);
            return;
        }

        if ($esFlexible) {
            $stmtUpdate = $this->db->prepare("UPDATE tipos_credito
                                              SET es_flexible = 1,
                                                  dias_intervalo = :dias_intervalo,
                                                  activo = 1
                                              WHERE tipo = :tipo");
            $stmtUpdate->execute([
                ':tipo' => $tipo,
                ':dias_intervalo' => $diasIntervalo,
            ]);
        }
    }

    private function normalizarSaldosCreditos(): void
    {
        try {
            $sqlNoMensual = "
                UPDATE creditos c
                LEFT JOIN (
                    SELECT
                        hp.idcredito,
                        SUM(GREATEST(hp.monto_pagado - COALESCE(hp.interes_moratorio, 0), 0)) AS base_pagada
                    FROM historial_pagos hp
                    GROUP BY hp.idcredito
                ) pagos ON pagos.idcredito = c.idcredito
                SET
                    c.saldo_pendiente = GREATEST(c.total_pagos - COALESCE(pagos.base_pagada, 0), 0),
                    c.estado = CASE
                        WHEN GREATEST(c.total_pagos - COALESCE(pagos.base_pagada, 0), 0) <= 0 THEN 'completado'
                        ELSE c.estado
                    END
                                WHERE c.tipo <> 'mensual'
                                    AND c.saldo_pendiente < 0
            ";
            $this->db->exec($sqlNoMensual);

            $sqlMensual = "
                UPDATE creditos
                SET saldo_pendiente = GREATEST(saldo_pendiente, 0)
                                WHERE tipo = 'mensual'
                                    AND saldo_pendiente < 0
            ";
            $this->db->exec($sqlMensual);
        } catch (Throwable $e) {
            // No bloquear el flujo principal si la normalización no se puede aplicar.
        }
    }
}
