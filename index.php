
<?php
/**
 * Fichero: api_users_by_role.php
 * Endpoint para obtener usuarios según su rol.
 */

// Incluir la configuración de forma segura
// El @ suprime errores si el fichero no existe, lo manejamos nosotros.
if (!@include_once __DIR__ . '/../config.php') {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error interno del servidor: Fichero de configuración no encontrado.']);
    exit;
}

/**
 * Establece una conexión segura a la base de datos.
 *
 * @return mysqli|null El objeto de conexión o null si falla.
 */
function get_db_connection(): ?mysqli {
    mysqli_report(MYSQLI_REPORT_OFF);
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        // En un entorno de producción, registrar el error en un log.
        // error_log("Error de conexión a la base de datos: " . $conn->connect_error);
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Devuelve una respuesta JSON estandarizada y termina la ejecución.
 *
 * @param int $statusCode Código de estado HTTP.
 * @param array $data El payload a codificar en JSON.
 */
function send_json_response(int $statusCode, array $data): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Función principal que maneja la lógica de la petición.
 */
function main(): void {
    // 1. Validar que el parámetro 'role' existe.
    if (!isset($_GET['role']) || empty(trim($_GET['role']))) {
        send_json_response(400, ['error' => 'El parámetro "role" es requerido.']);
    }
    $role = trim($_GET['role']);

    // 2. Conectarse a la base de datos.
    $conn = get_db_connection();
    if (!$conn) {
        send_json_response(500, ['error' => 'No se pudo establecer conexión con la base de datos.']);
    }

    $stmt = null; // Inicializar para que esté disponible en el bloque finally
    try {
        // 3. Usar consultas preparadas para prevenir Inyección SQL.
        $sql = "SELECT id, username, firstname, lastname, email FROM users WHERE role = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Error en la preparación de la consulta SQL.');
        }
        
        // 4. Vincular el parámetro de forma segura.
        $stmt->bind_param("s", $role);
        
        // 5. Ejecutar la consulta.
        $stmt->execute();
        
        // 6. Obtener los resultados.
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        // 7. Enviar respuesta exitosa.
        send_json_response(200, $users);
        
    } catch (Exception $e) {
        // En producción, registrar el mensaje de $e en un log.
        // error_log($e->getMessage());
        send_json_response(500, ['error' => 'Ocurrió un error al procesar su solicitud.']);
        
    } finally {
        // 8. Cerrar siempre la conexión y el statement.
        if ($stmt) {
            $stmt->close();
        }
        if ($conn) {
            $conn->close();
        }
    }
}

// --- Punto de Entrada del Script ---
main();