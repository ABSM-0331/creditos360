<?php

date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json; charset=utf-8');

// require_once '../conexion.php'; // si usas datos reales de BD, mantenlo

function numeroALetras($numero)
{
    $formatter = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);
    $parteEntera = floor($numero);
    $centavos = round(($numero - $parteEntera) * 100);
    $letras = strtoupper($formatter->format($parteEntera));
    return $letras . " PESOS " . str_pad($centavos, 2, "0", STR_PAD_LEFT) . "/100 M.N.";
}

// Parámetros del front
$impresora = $_REQUEST['impresora'] ?? '';
$ruta_img  = $_REQUEST['ruta_img'] ?? '';

// Datos del comercio (pon los reales desde tu sesión/BD)
$comercioNombre    = "Restaurante de Ejemplo";
$comercioDireccion = "Av. Principal 123";
$municipio         = "Tekax";
$estado            = "Yucatán";
$comercioTelefono  = "9971118771";

$idpedido    = 1001;
$fechaPedido = date("d/m/Y H:i");

// Tus productos
$productos = [
    ["nombre" => "TACO AL PASTOR T. MAIZ", "cantidad" => 2, "precio" => 15.00],
    ["nombre" => "AGUA DE JAMAICA",        "cantidad" => 1, "precio" => 10.00],
    ["nombre" => "QUESADILLA",             "cantidad" => 3, "precio" => 20.00]
];

// ---------- Formateo de columnas (opcional, 58mm ≈ 32 cols, 80mm ≈ 42) ----------
$cols = 32; // cambia a 42 si tu papel es 80mm
function col($text, $width)
{
    $t = mb_strimwidth($text, 0, $width, "", "UTF-8"); // recorta con multibyte
    $len = mb_strwidth($t, "UTF-8");
    if ($len < $width) $t .= str_repeat(' ', $width - $len);
    return $t;
}
function lineaItem($nombre, $cant, $precio, $cols = 32)
{
    $izq = col($nombre, $cols); // primera línea con el nombre completo (wrap aparte)
    $importe = $cant * $precio;
    $detalle = sprintf("$%0.2f  x%2d  $%0.2f", $precio, $cant, $importe);
    // Ajusta a una línea de detalle
    return [$izq, $detalle];
}

// ---------- Construir líneas ----------
$lines = [];
$lines[] = $comercioDireccion;
$lines[] = "{$municipio}, {$estado}";
$lines[] = "Tel: {$comercioTelefono}";
$lines[] = "";
$lines[] = "Pedido No. {$idpedido}";
$lines[] = $fechaPedido;
$lines[] = str_repeat('-', $cols);

$total = 0;
foreach ($productos as $p) {
    $importe = $p['cantidad'] * $p['precio'];
    $total  += $importe;

    // partir nombre largo en múltiples líneas de $cols caracteres aprox.
    $nombre = $p['nombre'];
    $nombre_wrapped = wordwrap($nombre, $cols, "\n", true);
    foreach (explode("\n", $nombre_wrapped) as $parte) {
        $lines[] = $parte;
    }
    // línea de detalle
    $lines[] = sprintf(" $%5.2f     x%2d     $%6.2f", $p["precio"], $p["cantidad"], $importe);
}

$lines[] = str_repeat('-', $cols);
$lines[] = sprintf("TOTAL: $%0.2f", $total);
$lines[] = str_repeat('-', $cols);
$lines[] = "TOTAL: $" . number_format($total, 2);
$lines[] = numeroALetras($total);
$lines[] = "";
$lines[] = "¡Gracias por su compra!";
$lines[] = "Type: Print Tester";

// ---------- Logo en base64 (para GDI) ----------
/*
$logoBase64 = null;
if (!empty($ruta_img)) {
    $abs = realpath(__DIR__ . '/' . $ruta_img);
    if ($abs && file_exists($abs)) {
        $bin = file_get_contents($abs);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($bin);
    }
}*/

// ---------- Logo en base64 (para GDI) ----------
$logoBase64 = null;
if (!empty($ruta_img)) {
    // Verificar si es una ruta absoluta
    if (file_exists($ruta_img)) {
        $ruta_absoluta = $ruta_img;
    }
    // Si no, intentar como ruta relativa desde el directorio actual
    else {
        $ruta_absoluta = realpath(__DIR__ . '/' . $ruta_img);
    }

    if ($ruta_absoluta && file_exists($ruta_absoluta)) {
        $bin = file_get_contents($ruta_absoluta);
        // Determinar el tipo MIME automáticamente
        $mime = mime_content_type($ruta_absoluta);
        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($bin);
    }
}

// $url = "https://i.postimg.cc/4Nb1p3n5/crediox-logo-opcion1.png";

// $bin = file_get_contents($url);
// $mime = "image/png";

// $base64 = 'data:' . $mime . ';base64,' . base64_encode($bin);

// ---------- Ancho del papel ----------
// 58mm ≈ 220 px, 80mm ≈ 300 px (a 96 dpi aprox). Ajusta según tu impresora.
$paperWidthPx = 200; // si 58mm, usa 220


echo json_encode([
    "PrinterName" => $impresora,
    "Title"       => $comercioNombre,
    "Lines"       => $lines,
    "Cut"         => true,            // en GDI no corta; en RAW sí
    "LogoBase64"  => $logoBase64,     // el agente GDI ya lo dibuja
    "PaperWidthPx" => $paperWidthPx,   // el agente ajusta el ancho
    "FontName"    => "Consolas",
    "FontSize"    => 7
], JSON_UNESCAPED_UNICODE);
