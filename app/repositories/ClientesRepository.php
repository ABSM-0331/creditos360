<?php
class ClientesRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DBC::get();
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT
                    p.idpersona AS idcliente,
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

                WHERE p.idrol = 2
                ORDER BY p.ap_paterno, p.ap_materno, p.nombres";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $idPersona): ?array
    {
        $sql = "SELECT
                    p.idpersona AS idcliente,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.email,
                    p.telefono,
                    p.sexo,
                    p.edad
                FROM personas p
                WHERE p.idpersona = ? AND p.idrol = 2";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idPersona]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function crearCliente(array $data): int
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
                "edad" => $data["edad"],
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

            // =========================
            // 1. Insertar en PERSONAS
            // =========================
            $sqlPersona = "INSERT INTO personas (
                ap_paterno, ap_materno, nombres,
                email, telefono, sexo, fecha_nacimiento,
                edad,
                curp, clave_elector, foto_ruta,
                dom_calle, dom_numero, dom_cruz1, dom_cruz2,
                dom_colonia, dom_cp, idestado, idmunicipio, dom_referencia,
                idrol, activo, created_at
            ) VALUES (
                :ap_paterno, :ap_materno, :nombres,
                :email, :telefono, :sexo, :fecha_nacimiento,
                :edad,
                :curp, :clave_elector, :foto_ruta,
                :dom_calle, :dom_numero, :dom_cruz1, :dom_cruz2,
                :dom_colonia, :dom_cp, :idestado, :idmunicipio, :dom_referencia,
                2, 1, NOW()
            )";

            $stmtPersona = $this->db->prepare($sqlPersona);
            $stmtPersona->execute($dataPersona);

            // Obtener ID generado
            $idPersona = (int)$this->db->lastInsertId();

            $this->db->commit();

            return $idPersona;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
