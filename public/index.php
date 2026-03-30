<?php
require_once __DIR__ . '/../vendor/autoload.php';
$lifetime = 60 * 60 * 24 * 7;
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
]);
session_start();

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../app/';
    $paths = [
        'controllers/',
        'services/',
        'repositories/',
        'config/',
    ];

    foreach ($paths as $path) {
        $file = $baseDir . $path . $class . '.php'; // ← punto agregado
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/proyecto-residencia/public';

if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

$uri = trim($uri, '/');
// var_dump($uri); // Agrega esta línea para depurar el URI
// die();
switch ($uri) {
    case '':
    case 'login':
        (new AuthController())->mostrarLogin();
        break;
    case 'auth/login':
        (new AuthController())->login();
        break;
    case 'dashboard':
        (new DashboardController())->index();
        break;
    case 'dashboard-cliente':
        (new DashboardController())->cliente();
        break;
    case 'dashboard-cobratario':
        (new DashboardController())->cobratario();
        break;
    case 'clientes':
        (new ClientesController())->mostrarCatalogoClientes();
        break;
    case 'nuevo-cliente':
        (new ClientesController())->vistaCrearCliente();
        break;
    case 'crear-cliente':
        (new ClientesController())->crearCliente();
        break;
    case 'cobratarios':
        (new CobratarioController())->index();
        break;
    case 'nuevo-cobratario':
        (new CobratarioController())->vistaCrearCobratario();
        break;
    case 'crear-cobratario':
        (new CobratarioController())->crearCobratario();
        break;
    case 'usuarios':
        (new UsuariosController())->index();
        break;
    case 'empresa':
        (new EmpresaController())->index();
        break;
    case 'empresa/guardar':
        (new EmpresaController())->guardar();
        break;
    case 'impresoras':
        (new ImpresorasController())->index();
        break;
    case 'impresoras/guardar':
        (new ImpresorasController())->guardar();
        break;
    case 'impresoras/eliminar':
        (new ImpresorasController())->eliminar();
        break;
    case 'impresoras/activar':
        (new ImpresorasController())->activar();
        break;
    case 'nuevo-usuario':
        (new UsuariosController())->vistaCrear();
        break;
    case 'crear-usuario':
        (new UsuariosController())->crear();
        break;
    case 'editar-usuario':
        (new UsuariosController())->vistaEditar();
        break;
    case 'actualizar-usuario':
        (new UsuariosController())->actualizar();
        break;
    case 'creditos':
        (new CreditosController())->index();
        break;
    case 'creditos/guardar':
        (new CreditosController())->guardar();
        break;
    case 'creditos/tipos/guardar':
        (new CreditosController())->guardarTipo();
        break;
    case 'creditos/tipos/actualizar':
        (new CreditosController())->actualizarTipo();
        break;
    case 'creditos/tipos/eliminar':
        (new CreditosController())->eliminarTipo();
        break;
    case 'creditos/obtener':
        (new CreditosController())->obtener();
        break;
    case 'creditos/cobrar':
        (new CreditosController())->cobrar();
        break;
    case 'creditos/recibo':
        (new CreditosController())->recibo();
        break;
    case 'creditos/ver-ticket':
        (new CreditosController())->verTicket();
        break;
    case 'creditos/enviar-ticket':
        (new CreditosController())->enviarTicket();
        break;
    case 'creditos/imprimir-ticket':
        (new CreditosController())->imprimirTicketTermica();
        break;
    case 'api/estados':
        (new UbicacionesController())->obtenerEstados();
        break;
    case 'api/municipios':
        (new UbicacionesController())->obtenerMunicipios();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
