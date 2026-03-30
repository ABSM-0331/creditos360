<?php
class ImpresorasRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DBC::get();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS impresoras (
            idimpresora INT(11) NOT NULL AUTO_INCREMENT,
            usuario_id INT(11) NOT NULL,
            nombre VARCHAR(150) NOT NULL,
            activa TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (idimpresora),
            KEY idx_impresora_usuario (usuario_id),
            UNIQUE KEY uk_impresora_usuario_nombre (usuario_id, nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->exec($sql);

        // Migracion para instalaciones existentes sin columna usuario_id.
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM impresoras LIKE 'usuario_id'");
            $column = $stmt ? $stmt->fetch() : false;
            if (!$column) {
                $this->db->exec("ALTER TABLE impresoras ADD COLUMN usuario_id INT(11) NOT NULL DEFAULT 0 AFTER idimpresora");
            }
        } catch (Throwable $e) {
            // Ignorar para no interrumpir flujo en caso de permisos restringidos.
        }

        // Migracion de indice unico antiguo por nombre global.
        try {
            $this->db->exec("ALTER TABLE impresoras DROP INDEX uk_impresora_nombre");
        } catch (Throwable $e) {
            // Si no existe, continuar.
        }

        try {
            $this->db->exec("ALTER TABLE impresoras ADD UNIQUE KEY uk_impresora_usuario_nombre (usuario_id, nombre)");
        } catch (Throwable $e) {
            // Si ya existe o hay conflicto, continuar.
        }

        try {
            $this->db->exec("ALTER TABLE impresoras ADD KEY idx_impresora_usuario (usuario_id)");
        } catch (Throwable $e) {
            // Si ya existe, continuar.
        }
    }

    public function obtenerTodas(int $usuarioId): array
    {
        $stmt = $this->db->prepare("SELECT idimpresora, nombre, activa, created_at, updated_at FROM impresoras WHERE usuario_id = :usuario_id ORDER BY activa DESC, nombre ASC");
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function obtenerActiva(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("SELECT idimpresora, nombre, activa FROM impresoras WHERE usuario_id = :usuario_id AND activa = 1 LIMIT 1");
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function agregar(string $nombre, int $usuarioId): int
    {
        $stmt = $this->db->prepare("INSERT INTO impresoras (usuario_id, nombre, activa) VALUES (:usuario_id, :nombre, 0)");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nombre' => $nombre,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function eliminar(int $idImpresora, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM impresoras WHERE idimpresora = :idimpresora AND usuario_id = :usuario_id");
        return $stmt->execute([
            ':idimpresora' => $idImpresora,
            ':usuario_id' => $usuarioId,
        ]);
    }

    public function activar(int $idImpresora, int $usuarioId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmtReset = $this->db->prepare("UPDATE impresoras SET activa = 0 WHERE usuario_id = :usuario_id");
            $stmtReset->execute([':usuario_id' => $usuarioId]);

            $stmt = $this->db->prepare("UPDATE impresoras SET activa = 1 WHERE idimpresora = :idimpresora AND usuario_id = :usuario_id");
            $stmt->execute([
                ':idimpresora' => $idImpresora,
                ':usuario_id' => $usuarioId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('No se encontró la impresora seleccionada para este usuario');
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function existePorNombre(string $nombre, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM impresoras WHERE usuario_id = :usuario_id AND nombre = :nombre");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nombre' => $nombre,
        ]);
        return ((int)($stmt->fetch()['total'] ?? 0)) > 0;
    }
}
