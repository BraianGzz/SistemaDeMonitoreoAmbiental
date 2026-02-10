<?php
/**
 * ARCHIVO: eliminar_registro.php
 * 
 * OBJETIVO: Eliminar un registro de la base de datos
 * 
 * METODO: DELETE
 * PARAMETROS (JSON):
 * - id: ID del registro a eliminar
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistemamonitoreo";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexion
if ($conn->connect_error) {
    die(json_encode([
        "status" => "error", 
        "message" => "Conexion fallida: " . $conn->connect_error
    ]));
}

//OBTENER DATOS DEL BODY
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar que se recibio el ID
if (!isset($data['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Falta el parametro ID"
    ]);
    exit;
}

$id = intval($data['id']);

//ELIMINAR REGISTRO
$sql = "DELETE FROM sensor WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Registro eliminado correctamente"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No se encontro el registro"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al eliminar: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>