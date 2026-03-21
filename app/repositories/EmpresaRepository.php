<?php
class EmpresaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DBC::get();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS datos_empresa (
            id INT NOT NULL PRIMARY KEY,
            nombre_empresa VARCHAR(150) NULL,
            direccion VARCHAR(255) NULL,
            correo VARCHAR(120) NULL,
            representante_legal VARCHAR(150) NULL,
            rfc VARCHAR(13) NULL,
            telefono VARCHAR(20) NULL,
            logo_ruta VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->exec($sql);
    }

    public function obtenerDatos(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM datos_empresa WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $empresa = $stmt->fetch();

        if (!$empresa) {
            return [
                'id' => 1,
                'nombre_empresa' => '',
                'direccion' => '',
                'correo' => '',
                'representante_legal' => '',
                'rfc' => '',
                'telefono' => '',
                'logo_ruta' => null,
            ];
        }

        return $empresa;
    }

    public function guardarDatos(array $data): void
    {
        $sql = "INSERT INTO datos_empresa
                (id, nombre_empresa, direccion, correo, representante_legal, rfc, telefono, logo_ruta)
                VALUES
                (1, :nombre_empresa, :direccion, :correo, :representante_legal, :rfc, :telefono, :logo_ruta)
                ON DUPLICATE KEY UPDATE
                    nombre_empresa = VALUES(nombre_empresa),
                    direccion = VALUES(direccion),
                    correo = VALUES(correo),
                    representante_legal = VALUES(representante_legal),
                    rfc = VALUES(rfc),
                    telefono = VALUES(telefono),
                    logo_ruta = VALUES(logo_ruta)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nombre_empresa' => $data['nombre_empresa'],
            'direccion' => $data['direccion'],
            'correo' => $data['correo'],
            'representante_legal' => $data['representante_legal'],
            'rfc' => $data['rfc'],
            'telefono' => $data['telefono'],
            'logo_ruta' => $data['logo_ruta'],
        ]);
    }
}
