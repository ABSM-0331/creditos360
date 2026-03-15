<?php
function configurarCredito($tipo){
    switch ($tipo) {
        case 'diario':
            return ['pagos'=>35,'interes'=>22.5,'moratorio'=>35,'modo'=>'fijo','intervalo'=>'P1D'];
        case 'semanal':
            return ['pagos'=>12,'interes'=>50,'moratorio'=>125,'modo'=>'fijo','intervalo'=>'P7D'];
        case 'mensual':
            return ['pagos'=>3,'interes'=>50,'moratorio'=>800,'modo'=>'flexible','intervalo'=>'P1M'];
    }
}

$tipo = $_POST['tipo'] ?? 'diario';
$config = configurarCredito($tipo);

/* 👉 VALORES SIEMPRE TOMADOS DEL FORM */
$monto       = $_POST['monto'] ?? 10000;
$pagos       = $_POST['pagos'] ?? $config['pagos'];
$interes     = $_POST['interes'] ?? $config['interes'];
$moratorio   = $_POST['moratorio'] ?? $config['moratorio'];
$fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');

$simular = isset($_POST['simular']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Simulador de Créditos</title>
<style>
body{
    font-family:'Segoe UI',Arial;
    background:#eef1f5;
    margin:0;
}

/* CONTENEDOR */
.contenedor{
    max-width:1100px;
    margin:30px auto;
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

/* GRID PRINCIPAL */
.grid{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:22px 18px; /* más aire */
    align-items:end;
}

/* LABELS */
label{
    font-size:13px;
    font-weight:600;
    color:#374151;
    margin-bottom:6px;
    display:block;
}

/* INPUTS */
input, select{
    padding:10px 12px;
    font-size:14px;
    width:90%;
    border-radius:6px;
    border:1px solid #d1d5db;
    background:#fff;
    transition:.2s;
}

input:focus, select:focus{
    outline:none;
    border-color:#2563eb;
    box-shadow:0 0 0 2px rgba(37,99,235,.15);
}

/* BOTÓN */
.btn{
    grid-column:span 6;
    padding:14px;
    background:#2563eb;
    color:#fff;
    font-size:15px;
    font-weight:600;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin-top:10px;
}

.btn:hover{
    background:#1e40af;
}

/* RESUMEN */
.resumen{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-top:30px;
}

.box{
    background:#B5EDFF; /* f9fafb*/
    padding:18px;
    border-radius:10px;
    text-align:center;
    box-shadow:0 4px 10px rgba(0,0,0,.04);
}

.box b{
    display:block;
    font-size:14px;
    color:#374151;
    margin-bottom:6px;
}

.box{
    font-size:18px;
    font-weight:600;
    color:#111827;
}

/* TABLA */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:30px;
    font-size:13px;
}

th,td{
    border:1px solid #e5e7eb;
    padding:8px;
    text-align:center;
}

th{
    background:#f3f4f6;
    font-weight:600;
}

tfoot td{
    font-weight:bold;
    background:#e5e7eb;
}

/* 📱 RESPONSIVE */
@media (max-width: 900px){

    .grid{
        grid-template-columns:1fr;
    }

    .btn{
        grid-column:1;
    }

    .resumen{
        grid-template-columns:1fr;
    }

    table{
        font-size:12px;
    }
}


</style>

</head>

<body>
<div class="contenedor">
<h2>Simulador de Crédito</h2> <button type="button" onclick="toggleDark()" style="float:right;margin-bottom:10px">
    🌙 Modo oscuro
</button>


<form method="POST">
<div class="grid">

    <div>
        <label>Monto</label>
        <input type="number" step="0.01" name="monto" value="<?= $monto ?>">
    </div>

    <div>
        <label>Tipo</label>
        <select name="tipo" onchange="actualizarConfig()">
            <option value="diario" <?= $tipo=='diario'?'selected':'' ?>>Diario</option>
            <option value="semanal" <?= $tipo=='semanal'?'selected':'' ?>>Semanal</option>
            <option value="mensual" <?= $tipo=='mensual'?'selected':'' ?>>Mensual</option>
        </select>
    </div>

    <div>
        <label>Pagos</label>
        <input type="number" id="pagos" name="pagos" value="<?= $pagos ?>" >
    </div>

    <div>
        <label>Interés %</label>
        <input type="number" step="0.01" id="interes" name="interes" value="<?= $interes ?>" >
    </div>

    <div>
        <label>Moratorio</label>
        <input type="number" id="moratorio" name="moratorio" value="<?= $moratorio ?>" >
    </div>

    <?php if ($tipo != 'mensual'): ?>
    <div>
        <label>Fecha inicio</label>
        <input type="date" name="fecha_inicio" value="<?= $fechaInicio ?>">
    </div>
    <?php endif; ?>

    <button class="btn" name="simular">Simular crédito</button>
	
</div>
</form>

<?php

switch ($tipo) {
    case 'diario':
        $labelPago = 'Pago diario';
        break;
    case 'semanal':
        $labelPago = 'Pago semanal';
        break;
    case 'mensual':
        $labelPago = 'Pago mensual';
        break;
    default:
        $labelPago = 'Pago';
}


if ($simular){

    if ($config['modo'] == 'fijo') {

        $montoInteres = round($monto * ($interes/100),2);
        $totalPagar  = round($monto + $montoInteres,2);

        $capitalBase = round($monto / $pagos,2);
        $interesBase = round($montoInteres / $pagos,2);

        $saldo = $monto;
        $fecha = new DateTime($fechaInicio);

        echo "<div class='resumen'>
        <div class='box'><b>Capital</b><br>$".number_format($monto,2)."</div>
        <div class='box'><b>Interés</b><br>$".number_format($montoInteres,2)."</div>
        <div class='box'><b>Total</b><br>$".number_format($totalPagar,2)."</div>
        <div class='box'><b>$labelPago</b><br>$".number_format($totalPagar/$pagos,2)."</div>
      </div>";


        echo "<table>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Saldo inicial</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Pago</th>
                    <th>Saldo final</th>
                </tr>";

        for ($i=1; $i<=$pagos; $i++){

            $saldoInicial = $saldo;

            if ($i == $pagos){
                $capital = $saldo;
                $interesPago = $montoInteres - ($interesBase * ($pagos-1));
            } else {
                $capital = $capitalBase;
                $interesPago = $interesBase;
            }

            $pago = round($capital + $interesPago,2);
            $saldo -= $capital;

            echo "<tr>
                    <td>$i</td>
                    <td>".$fecha->format('d/m/Y')."</td>
                    <td>$".number_format($saldoInicial,2)."</td>
                    <td>$".number_format($capital,2)."</td>
                    <td>$".number_format($interesPago,2)."</td>
                    <td>$".number_format($pago,2)."</td>
                    <td>$".number_format($saldo,2)."</td>
                  </tr>";

            $fecha->add(new DateInterval($config['intervalo']));
        }

        echo "</table>";

    } else {

        $interesMes = round($monto * ($interes/100),2);

        echo "<div class='resumen'>
                <div class='box'><b>Saldo</b><br>$".number_format($monto,2)."</div>
                <div class='box'><b>Interés mensual</b><br>$".number_format($interesMes,2)."</div>
                <div class='box'><b>Pago mínimo</b><br>$".number_format($interesMes,2)."</div>
                <div class='box'><b>Modo</b><br>Flexible</div>
              </div>";

        echo "<p>💡 En crédito mensual el cliente puede pagar solo interés o abonar a capital.
              El cálculo se hace nuevamente después de cada pago.</p>";
    }
}
?>

</div>

<script>
const configuraciones = {
    diario:   {pagos:35, interes:22.5, moratorio:35},
    semanal:  {pagos:12, interes:50,   moratorio:125},
    mensual:  {pagos:3,  interes:50,   moratorio:800}
};

function actualizarConfig(){
    const tipo = document.querySelector('select[name="tipo"]').value;
    document.getElementById('pagos').value = configuraciones[tipo].pagos;
    document.getElementById('interes').value = configuraciones[tipo].interes;
    document.getElementById('moratorio').value = configuraciones[tipo].moratorio;
}
</script>

</body>
</html>
