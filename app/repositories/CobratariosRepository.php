<?php
class CobratariosRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DBC::get();
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT
                    p.idpersona AS idcobratario,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.curp,
                    p.telefono,
                    p.sexo,
                    p.edad,
                    e.nombre AS estado,
                    m.nombre AS municipio,

                    -- campos ocultos
                    p.email,
                    p.clave_elector,
                    p.fecha_nacimiento,
                    p.foto_ruta,
                    p.dom_calle,
                    p.dom_numero,
                    p.dom_colonia,
                    p.dom_cp,
                    p.idestado,
                    p.idmunicipio,
                    p.dom_referencia

                FROM personas p
                LEFT JOIN usuarios u   ON u.idpersona = p.idpersona
                JOIN estados e    ON e.idestado = p.idestado
                JOIN municipios m ON m.idmunicipio = p.idmunicipio

                WHERE p.idrol = 3
                ORDER BY p.ap_paterno, p.ap_materno, p.nombres";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function obtenerTodosConEstadisticas(): array
    {
        $sql = "SELECT
                    p.idpersona AS idcobratario,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.curp,
                    p.telefono,
                    p.sexo,
                    p.edad,
                    e.nombre AS estado,
                    m.nombre AS municipio,
                    p.email,
                    p.clave_elector,
                    p.fecha_nacimiento,
                    p.foto_ruta,
                    p.dom_calle,
                    p.dom_numero,
                    p.dom_colonia,
                    p.dom_cp,
                    p.idestado,
                    p.idmunicipio,
                    p.dom_referencia,
                    COALESCE(cred.total_clientes, 0) AS clientes_asignados,
                    COALESCE(cred.total_creditos, 0) AS creditos_asignados,
                    COALESCE(cob.total_cobrado, 0) AS total_cobrado
                FROM personas p
                LEFT JOIN usuarios u ON u.idpersona = p.idpersona
                JOIN estados e ON e.idestado = p.idestado
                JOIN municipios m ON m.idmunicipio = p.idmunicipio
                LEFT JOIN (
                    SELECT
                        idcobratario,
                        COUNT(*) AS total_creditos,
                        COUNT(DISTINCT idcliente) AS total_clientes
                    FROM creditos
                    GROUP BY idcobratario
                ) cred ON cred.idcobratario = p.idpersona
                LEFT JOIN (
                    SELECT
                        c.idcobratario,
                        COALESCE(SUM(hp.monto_pagado), 0) AS total_cobrado
                    FROM historial_pagos hp
                    INNER JOIN creditos c ON c.idcredito = hp.idcredito
                    GROUP BY c.idcobratario
                ) cob ON cob.idcobratario = p.idpersona
                WHERE p.idrol = 3
                ORDER BY p.ap_paterno, p.ap_materno, p.nombres";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function crearCobratario(array $data): int
    {
        try {
            $dataPersona = [
                "ap_paterno" => $data["ap_paterno"],
                "ap_materno" => $data["ap_materno"],
                "nombres" => $data["nombres"],
                "email" => $data["email"],
                "telefono" => $data["telefono"],
                "sexo" => $data["sexo"],
                "fecha_nacimiento" => $data["fecha_nacimiento"],
                "curp" => $data["curp"],
                "clave_elector" => $data["clave_elector"],
                "foto_ruta" => $data["foto_ruta"],
                "dom_calle" => $data["dom_calle"],
                "dom_numero" => $data["dom_numero"],
                "dom_cruz1" => $data["dom_cruz1"],
                "dom_cruz2" => $data["dom_cruz2"],
                "dom_colonia" => $data["dom_colonia"],
                "dom_cp" => $data["dom_cp"],
                "idestado" => $data["idestado"],
                "idmunicipio" => $data["idmunicipio"],
                "dom_referencia" => $data["dom_referencia"],
            ];

            $this->db->beginTransaction();

            // 1. Insertar en PERSONAS
            $sqlPersona = "INSERT INTO personas (
                ap_paterno, ap_materno, nombres,
                email, telefono, sexo, fecha_nacimiento,
                curp, clave_elector, foto_ruta,
                dom_calle, dom_numero, dom_cruz1, dom_cruz2,
                dom_colonia, dom_cp, idestado, idmunicipio, dom_referencia,
                idrol, activo, created_at
            ) VALUES (
                :ap_paterno, :ap_materno, :nombres,
                :email, :telefono, :sexo, :fecha_nacimiento,
                :curp, :clave_elector, :foto_ruta,
                :dom_calle, :dom_numero, :dom_cruz1, :dom_cruz2,
                :dom_colonia, :dom_cp, :idestado, :idmunicipio, :dom_referencia,
                3, 1, NOW()
            )";

            $stmtPersona = $this->db->prepare($sqlPersona);
            $stmtPersona->execute($dataPersona);

            $idPersona = (int)$this->db->lastInsertId();

            $this->db->commit();

            return $idPersona;
        } catch (Throwable $e) {
            var_dump($e);
            $this->db->rollBack();
            throw $e;
        }
    }
}
