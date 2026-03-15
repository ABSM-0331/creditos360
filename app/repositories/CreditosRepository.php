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
                ':total_pagos' => $totalPagar,
                ':saldo_pendiente' => $totalPagar
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
                ORDER BY c.fecha_creacion DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todos los pagos de un crédito
     */
    public function obtenerPagosCredito($idCredito)
    {
        $sql = "SELECT * FROM pagos_credito WHERE idcredito = ? ORDER BY numero_pago";
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
    public function cobrarPagoCobratario(int $idPago, int $idCredito, int $idCobratario, float $montoRecibido, ?int $idUsuarioCobrador = null): array
    {
        try {
            $this->db->beginTransaction();

            $sqlPago = "SELECT pc.*, c.idcobratario, c.estado AS estado_credito
                        FROM pagos_credito pc
                        INNER JOIN creditos c ON c.idcredito = pc.idcredito
                        WHERE pc.idpago = :idpago AND pc.idcredito = :idcredito
                        FOR UPDATE";
            $stmtPago = $this->db->prepare($sqlPago);
            $stmtPago->execute([
                ':idpago' => $idPago,
                ':idcredito' => $idCredito,
            ]);

            $pago = $stmtPago->fetch();
            if (!$pago) {
                throw new Exception('Pago no encontrado');
            }

            if ((int)$pago['idcobratario'] !== $idCobratario) {
                throw new Exception('No tienes permiso para cobrar este crédito');
            }

            if (($pago['estado'] ?? '') === 'pagado') {
                throw new Exception('Este pago ya fue cobrado');
            }

            $hoy = new DateTime('today');
            $fechaProgramada = new DateTime($pago['fecha_programada']);
            if ($fechaProgramada > $hoy) {
                throw new Exception('Solo puedes cobrar pagos correspondientes a la fecha actual o vencidos');
            }

            $montoCobro = (float)$pago['monto_programado'];
            if ($montoRecibido < $montoCobro) {
                throw new Exception('El monto recibido es menor al pago correspondiente');
            }

            $cambio = $montoRecibido - $montoCobro;

            $sqlUpdatePago = "UPDATE pagos_credito
                              SET estado = 'pagado', fecha_pago_real = :fecha_pago_real, monto_pagado = :monto_pagado
                              WHERE idpago = :idpago";
            $stmtUpdatePago = $this->db->prepare($sqlUpdatePago);
            $stmtUpdatePago->execute([
                ':fecha_pago_real' => $hoy->format('Y-m-d'),
                ':monto_pagado' => $montoCobro,
                ':idpago' => $idPago,
            ]);

            $sqlHistorial = "INSERT INTO historial_pagos (
                                idpago,
                                idcredito,
                                fecha_pago,
                                monto_pagado,
                                idusuario_cobrador,
                                metodo_pago,
                                observaciones
                            ) VALUES (
                                :idpago,
                                :idcredito,
                                :fecha_pago,
                                :monto_pagado,
                                :idusuario_cobrador,
                                'efectivo',
                                :observaciones
                            )";
            $stmtHistorial = $this->db->prepare($sqlHistorial);
            $stmtHistorial->execute([
                ':idpago' => $idPago,
                ':idcredito' => $idCredito,
                ':fecha_pago' => $hoy->format('Y-m-d'),
                ':monto_pagado' => $montoCobro,
                ':idusuario_cobrador' => $idUsuarioCobrador,
                ':observaciones' => 'Cobro registrado por cobratario',
            ]);

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
                'mensaje' => 'Cobro registrado correctamente',
                'monto_cobrado' => $montoCobro,
                'cambio' => $cambio,
                'idpago' => $idPago,
                'idcredito' => $idCredito,
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
    public function obtenerDatosReciboCobro($idPago, $idCredito): array
    {
        $sql = "SELECT 
                    hp.idhistorial,
                    hp.fecha_pago,
                    hp.monto_pagado,
                    hp.metodo_pago,
                    u.username,
                    c.idcredito,
                    c.monto,
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
                LEFT JOIN municipios m ON m.idmunicipio = p.idmunicipio
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

        // Calcular cambio
        $montoProgramado = (float)$resultado['monto_programado'];
        $montoPagado = (float)$resultado['monto_pagado'];
        $cambio = 0; // El cambio se debe calcular en el controlador si es necesario

        return [
            'success' => true,
            'cobro' => [
                'idhistorial' => $resultado['idhistorial'],
                'fecha' => $resultado['fecha_pago'],
                'numero_recibo' => 'RCP-' . $resultado['idhistorial'] . date('Ymd', strtotime($resultado['fecha_pago'])),
                'cliente' => [
                    'nombre' => $resultado['nombre_completo'],
                    'cedula' => $resultado['numero_cedula'],
                    'telefono' => $resultado['telefono'],
                    'ciudad' => $resultado['ciudad'] ?? 'N/A',
                ],
                'credito' => [
                    'idcredito' => $resultado['idcredito'],
                    'monto_original' => $resultado['monto'],
                    'tipo' => ucfirst($resultado['tipo']),
                    'pagos_totales' => $resultado['cantidad_pagos'],
                    'interes' => $resultado['interes'],
                ],
                'pago' => [
                    'numero_pago' => $resultado['numero_pago'],
                    'monto_programado' => $montoProgramado,
                    'monto_pagado' => $montoPagado,
                    'fecha_programada' => $resultado['fecha_programada'],
                    'interes_programado' => $resultado['interes_programado'],
                ],
                'cobrador' => $resultado['username'],
                'metodo_pago' => ucfirst($resultado['metodo_pago']),
            ]
        ];
    }
}
