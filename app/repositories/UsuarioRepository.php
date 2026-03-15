<?php
class UsuarioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DBC::get();
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $sql = "SELECT u.*, p.idrol FROM usuarios u
                JOIN personas p ON u.idpersona = p.idpersona
                WHERE u.username = :correo LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['correo' => $correo]);

        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT 
                    u.idusuario, 
                    u.username, 
                    u.idpersona,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.email,
                    p.telefono,
                    CASE p.idrol 
                        WHEN 2 THEN 'Cliente'
                        WHEN 3 THEN 'Cobratario'
                        ELSE 'Otro'
                    END AS rol,
                    u.created_at
                FROM usuarios u
                JOIN personas p ON u.idpersona = p.idpersona
                ORDER BY p.ap_paterno, p.ap_materno, p.nombres";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT 
                    u.idusuario, 
                    u.username, 
                    u.idpersona,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.email,
                    CASE p.idrol 
                        WHEN 2 THEN 'Cliente'
                        WHEN 3 THEN 'Cobratario'
                        ELSE 'Otro'
                    END AS rol,
                    p.idrol
                FROM usuarios u
                JOIN personas p ON u.idpersona = p.idpersona
                WHERE u.idusuario = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerCobratarios(): array
    {
        $sql = "SELECT 
                    p.idpersona AS idcobratario,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.email,
                    p.telefono
                FROM personas p
                WHERE p.idrol = 3
                ORDER BY p.ap_paterno, p.ap_materno, p.nombres";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function obtenerCobratarioPorId(int $idpersona): ?array
    {
        $sql = "SELECT 
                    p.idpersona AS idcobratario,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.email,
                    p.telefono
                FROM personas p
                WHERE p.idpersona = ? AND p.idrol = 3";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idpersona]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerPersonasSinUsuario(): array
    {
        $sql = "SELECT 
                    p.idpersona,
                    CONCAT(p.ap_paterno, ' ', p.ap_materno, ' ', p.nombres) AS nombre,
                    p.email,
                    p.telefono,
                    CASE p.idrol 
                        WHEN 2 THEN 'Cliente'
                        WHEN 3 THEN 'Cobratario'
                        ELSE 'Otro'
                    END AS rol,
                    p.idrol
                FROM personas p
                LEFT JOIN usuarios u ON u.idpersona = p.idpersona
                WHERE u.idusuario IS NULL
                AND p.idrol IN (2, 3)
                ORDER BY p.ap_paterno, p.ap_materno, p.nombres";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function crearUsuario(array $data): int
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO usuarios (username, password, idpersona, created_at)
                    VALUES (:username, :password, :idpersona, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'username' => $data['username'],
                'password' => password_hash($data['contrasena'], PASSWORD_BCRYPT),
                'idpersona' => $data['idpersona']
            ]);

            $idusuario = (int)$this->db->lastInsertId();
            $this->db->commit();

            return $idusuario;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizarUsuario(int $id, array $data): void
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE usuarios SET username = :username";
            $params = ['username' => $data['username'], 'id' => $id];

            if (!empty($data['contrasena'])) {
                $sql .= ", password = :password";
                $params['password'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);
            }

            $sql .= " WHERE idusuario = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
