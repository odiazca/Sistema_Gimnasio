<?php
/** Datos de demostración realistas, relativos a la fecha de instalación */
function seed_demo(PDO $pdo): void
{
    mt_srand(2026);
    $today = new DateTime('today');
    $now   = new DateTime();
    $pick  = fn(array $a) => $a[mt_rand(0, count($a) - 1)];

    $pdo->beginTransaction();

    // Planes
    $planes = [
        ['Pase Diario',  'Acceso por un día',                    1,   15,  '#94a3b8'],
        ['Mensual',      'Acceso total 30 días',                 30,  100, '#ff5a1f'],
        ['Trimestral',   '90 días + 1 evaluación física',        90,  270, '#f59e0b'],
        ['Semestral',    '180 días + rutina personalizada',      180, 480, '#10b981'],
        ['Anual',        '365 días + 2 clases personales',       365, 800, '#6366f1'],
        ['Pareja Mensual','Dos personas, 30 días',               30,  180, '#ec4899'],
    ];
    $st = $pdo->prepare('INSERT INTO planes (nombre,descripcion,duracion_dias,precio,color) VALUES (?,?,?,?,?)');
    foreach ($planes as $p) $st->execute($p);
    // Pesos de selección (excluye pase diario de las cadenas de renovación)
    $planPool = [2,2,2,2,2,3,3,3,4,4,5,6,6];
    $planInfo = [];
    foreach ($pdo->query('SELECT * FROM planes') as $r) $planInfo[$r['id']] = $r;

    // Entrenadores
    $ents = [
        ['Carlos','Mendoza','Musculación','987654321'], ['Lucía','Paredes','Funcional / HIIT','987654322'],
        ['Jorge','Quispe','Crossfit','987654323'], ['Andrea','Salazar','Yoga y Pilates','987654324'],
        ['Diego','Ramos','Spinning','987654325'], ['Valeria','Torres','Zumba / Baile','987654326'],
    ];
    $st = $pdo->prepare('INSERT INTO entrenadores (nombres,apellidos,especialidad,telefono) VALUES (?,?,?,?)');
    foreach ($ents as $e) $st->execute($e);

    // Clases y horarios
    $clases = [
        ['Spinning','Ciclismo indoor de alta intensidad',5,25,'#ff5a1f'],
        ['Yoga','Flexibilidad y respiración',4,20,'#10b981'],
        ['Zumba','Baile fitness',6,30,'#ec4899'],
        ['Crossfit','Entrenamiento funcional',3,18,'#6366f1'],
        ['HIIT','Intervalos de alta intensidad',2,22,'#f59e0b'],
        ['Pilates','Core y postura',4,15,'#06b6d4'],
    ];
    $st = $pdo->prepare('INSERT INTO clases (nombre,descripcion,entrenador_id,cupo,color) VALUES (?,?,?,?,?)');
    $hs = $pdo->prepare('INSERT INTO horarios_clase (clase_id,dia_semana,hora_inicio,hora_fin,sala) VALUES (?,?,?,?,?)');
    foreach ($clases as $i => $c) {
        $st->execute($c);
        $cid = $i + 1;
        $slots = [['06:30','07:30'], ['18:00','19:00'], ['19:30','20:30'], ['09:00','10:00']];
        foreach ([1,2,3,4,5,6] as $d) {
            if (($d + $i) % 2 === 0) { $s = $slots[($d + $i) % 4]; $hs->execute([$cid,$d,$s[0],$s[1], $i % 2 ? 'Sala B' : 'Sala A']); }
        }
        $s = $slots[$i % 4]; $hs->execute([$cid, (($i + 2) % 6) + 1, $s[0], $s[1], 'Sala A']);
    }

    // Socios
    $nombresM = ['Luis','Carlos','Jorge','Miguel','José','Diego','Renzo','Fabrizio','Andrés','Marco','Sebastián','Piero','Gonzalo','Hugo','Álvaro','Bruno','Kevin','Alonso','Franco','Rodrigo'];
    $nombresF = ['María','Lucía','Valeria','Camila','Andrea','Daniela','Fiorella','Ximena','Paola','Gabriela','Mariana','Rosa','Carla','Alejandra','Silvana','Jimena','Nicole','Karen','Milagros','Pamela'];
    $apell = ['Quispe','Flores','Rojas','Vargas','Mendoza','Torres','Castillo','Huamán','Ramírez','García','Chávez','Sánchez','Díaz','Condori','Paredes','Salazar','Ríos','Medina','Cruz','Gutiérrez','Soto','Ponce','Alvarado','Espinoza','Valdivia'];
    $objetivos = ['Perder peso','Ganar masa muscular','Tonificar','Salud y bienestar','Rendimiento deportivo','Rehabilitación'];
    $distritos = ['Miraflores','San Isidro','Surco','La Molina','San Borja','Jesús María','Lince','Magdalena','Comas','SJL','Callao'];

    $total = 190;
    $stS  = $pdo->prepare('INSERT INTO socios (codigo,dni,nombres,apellidos,fecha_nacimiento,genero,telefono,email,direccion,contacto_emergencia,telefono_emergencia,objetivo,fecha_registro,estado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stM  = $pdo->prepare('INSERT INTO membresias (socio_id,plan_id,fecha_inicio,fecha_fin,precio,descuento,estado,usuario_id) VALUES (?,?,?,?,?,?,?,1)');
    $stP  = $pdo->prepare('INSERT INTO pagos (membresia_id,socio_id,monto,metodo,concepto,fecha_pago,usuario_id) VALUES (?,?,?,?,?,?,?)');
    $metodos = ['efectivo','efectivo','yape','yape','plin','tarjeta','transferencia'];

    $activePeriods = []; // socio_id => [[start,end],...]

    for ($i = 1; $i <= $total; $i++) {
        $fem = mt_rand(0, 100) < 46;
        $nom = $fem ? $pick($nombresF) : $pick($nombresM);
        $ap  = $pick($apell) . ' ' . $pick($apell);
        // Fecha de registro: más densidad reciente (crecimiento)
        $daysAgo = (int)(pow(mt_rand(0, 1000) / 1000, 0.7) * 420);
        $join = (clone $today)->modify("-{$daysAgo} days");
        $birth = (clone $today)->modify('-' . mt_rand(16 * 365, 55 * 365) . ' days');
        // Algunos cumpleaños cercanos para el demo
        if ($i % 37 === 0) { $birth = (clone $today)->modify('-' . (mt_rand(20, 40)) . ' years'); }
        if ($i % 41 === 0) { $birth = (clone $today)->modify('-' . (mt_rand(20, 40)) . ' years')->modify('+' . mt_rand(1, 5) . ' days'); }
        $dni = (string)mt_rand(10000000, 79999999);
        $tel = '9' . mt_rand(10000000, 99999999);
        $mail = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $nom . '.' . explode(' ', $ap)[0])) . $i . '@mail.com';
        $mail = preg_replace('/[^a-z0-9.@]/', '', $mail);
        $stS->execute([
            sprintf('SOC-%04d', $i), $dni, $nom, $ap, $birth->format('Y-m-d'), $fem ? 'F' : 'M', $tel, $mail,
            'Av. ' . $pick($apell) . ' ' . mt_rand(100, 999) . ', ' . $pick($distritos),
            $pick($apell) . ' ' . $pick($apell), '9' . mt_rand(10000000, 99999999),
            $pick($objetivos), $join->format('Y-m-d'), 'activo',
        ]);
        $sid = (int)$pdo->lastInsertId();

        // Cadena de membresías
        $start = clone $join;
        $planId = $pick($planPool);
        $retention = mt_rand(55, 92) / 100;
        $periods = [];
        while ($start <= $today) {
            $pl = $planInfo[$planId];
            $end = (clone $start)->modify('+' . ($pl['duracion_dias'] - 1) . ' days');
            $disc = (mt_rand(0, 100) < 10) ? round($pl['precio'] * 0.1, 2) : 0;
            $estado = $end >= $today ? 'activa' : 'vencida';
            $stM->execute([$sid, $planId, $start->format('Y-m-d'), $end->format('Y-m-d'), $pl['precio'], $disc, $estado]);
            $mid = (int)$pdo->lastInsertId();
            $hora = sprintf('%02d:%02d:00', mt_rand(8, 20), mt_rand(0, 59));
            $monto = $pl['precio'] - $disc;
            if ($estado === 'activa' && $pl['duracion_dias'] > 1 && mt_rand(0, 100) < 9) $monto = round($monto * 0.6, 2); // pago parcial → deuda
            $stP->execute([$mid, $sid, $monto, $pick($metodos), 'Membresía ' . $pl['nombre'], $start->format('Y-m-d') . ' ' . $hora, mt_rand(1, 2)]);
            $periods[] = [clone $start, clone $end];
            if (mt_rand(0, 100) / 100 > $retention) break;           // abandona
            $start = (clone $end)->modify('+' . mt_rand(1, 6) . ' days');
            if (mt_rand(0, 100) < 25) $planId = $pick($planPool);
        }
        $activePeriods[$sid] = $periods;
        $last = end($periods)[1];
        $diff = (int)$last->diff($today)->format('%r%a');
        if ($last < $today && $diff > 45) $pdo->exec("UPDATE socios SET estado='inactivo' WHERE id=$sid");
    }

    // Asistencias últimos 30 días
    $stA = $pdo->prepare('INSERT INTO asistencias (socio_id,ingreso,salida,metodo) VALUES (?,?,?,?)');
    $horasPeso = [6=>6,7=>9,8=>8,9=>5,10=>3,11=>3,12=>4,13=>3,14=>2,15=>3,16=>5,17=>9,18=>12,19=>13,20=>9,21=>4];
    $bag = [];
    foreach ($horasPeso as $h => $w) for ($k = 0; $k < $w; $k++) $bag[] = $h;
    for ($d = 30; $d >= 0; $d--) {
        $day = (clone $today)->modify("-{$d} days");
        $dow = (int)$day->format('N');
        $prob = $dow === 7 ? 0.14 : ($dow === 6 ? 0.28 : 0.42);
        foreach ($activePeriods as $sid => $periods) {
            $in = false;
            foreach ($periods as [$s, $e]) { if ($day >= $s && $day <= $e) { $in = true; break; } }
            if (!$in || mt_rand(0, 1000) / 1000 > $prob) continue;
            $h = $pick($bag); $m = mt_rand(0, 59);
            $ing = (clone $day)->setTime($h, $m);
            if ($ing > $now) continue;
            $sal = (clone $ing)->modify('+' . mt_rand(45, 110) . ' minutes');
            $salS = $sal <= $now ? $sal->format('Y-m-d H:i:s') : null;
            $stA->execute([$sid, $ing->format('Y-m-d H:i:s'), $salS, $pick(['dni','dni','qr','huella','manual'])]);
        }
    }

    // Productos
    $prods = [
        ['P001','Agua mineral 625ml','Bebidas',1.0,2.5,80,20], ['P002','Bebida isotónica 500ml','Bebidas',2.2,4.5,60,15],
        ['P003','Proteína Whey 2lb','Suplementos',95,150,12,4], ['P004','Creatina 300g','Suplementos',60,95,9,4],
        ['P005','Pre-entreno 30 serv.','Suplementos',70,110,3,4],   ['P006','Barra proteica','Snacks',3,6.5,45,15],
        ['P007','Shaker 700ml','Accesorios',6,15,18,6],            ['P008','Guantes de entrenamiento','Accesorios',14,30,10,5],
        ['P009','Toalla deportiva','Accesorios',8,20,14,5],        ['P010','Cinturón lumbar','Accesorios',35,65,2,3],
        ['P011','BCAA 200g','Suplementos',45,75,7,4],              ['P012','Polo FitCore','Ropa',15,35,25,8],
    ];
    $st = $pdo->prepare('INSERT INTO productos (codigo,nombre,categoria,precio_compra,precio_venta,stock,stock_minimo) VALUES (?,?,?,?,?,?,?)');
    foreach ($prods as $p) $st->execute($p);

    // Ventas últimos 12 meses
    $stV = $pdo->prepare('INSERT INTO ventas (socio_id,total,metodo,fecha,usuario_id) VALUES (?,?,?,?,?)');
    $stD = $pdo->prepare('INSERT INTO venta_detalle (venta_id,producto_id,cantidad,precio) VALUES (?,?,?,?)');
    for ($d = 365; $d >= 0; $d--) {
        $day = (clone $today)->modify("-{$d} days");
        $n = mt_rand(0, (int)(2 + (365 - $d) / 90));
        for ($k = 0; $k < $n; $k++) {
            $items = mt_rand(1, 3); $tot = 0; $lines = [];
            for ($j = 0; $j < $items; $j++) {
                $pr = $prods[mt_rand(0, 11)]; $q = mt_rand(1, 2); $tot += $pr[4] * $q;
                $lines[] = [array_search($pr, $prods) + 1, $q, $pr[4]];
            }
            $f = (clone $day)->setTime(mt_rand(7, 21), mt_rand(0, 59));
            if ($f > $now) continue;
            $stV->execute([null, $tot, $pick($metodos), $f->format('Y-m-d H:i:s'), mt_rand(1, 2)]);
            $vid = (int)$pdo->lastInsertId();
            foreach ($lines as $l) $stD->execute([$vid, $l[0], $l[1], $l[2]]);
        }
    }

    // Gastos mensuales
    $stG = $pdo->prepare('INSERT INTO gastos (concepto,categoria,monto,fecha,usuario_id) VALUES (?,?,?,?,1)');
    for ($m = 12; $m >= 0; $m--) {
        $base = (clone $today)->modify('first day of this month')->modify("-{$m} months");
        $fix = [
            ['Alquiler del local','Fijos',3800], ['Planilla y entrenadores','Personal',4200], ['Luz y agua','Servicios',720 + mt_rand(-80, 120)],
            ['Internet y telefonía','Servicios',190], ['Limpieza e insumos','Operativos',350 + mt_rand(-40, 60)],
            ['Marketing y redes','Marketing',400 + mt_rand(0, 200)], ['Mantenimiento de máquinas','Operativos',mt_rand(0, 1) ? 450 : 150],
        ];
        foreach ($fix as $g) {
            $off = $m === 0 ? mt_rand(0, max(0, (int)$today->format('j') - 1)) : mt_rand(0, 25);
            $f = (clone $base)->modify('+' . $off . ' days');
            if ($f > $today) continue;
            $stG->execute([$g[0], $g[1], $g[2], $f->format('Y-m-d')]);
        }
    }

    // Reservas de clases de hoy
    $dow = (int)$today->format('N');
    $hrs = $pdo->query("SELECT id FROM horarios_clase WHERE dia_semana=$dow")->fetchAll(PDO::FETCH_COLUMN);
    $stR = $pdo->prepare('INSERT INTO reservas (horario_id,socio_id,fecha) VALUES (?,?,?)');
    foreach ($hrs as $hid) {
        $n = mt_rand(6, 16);
        for ($k = 0; $k < $n; $k++) $stR->execute([$hid, mt_rand(1, $total), $today->format('Y-m-d')]);
    }

    // Evaluaciones físicas de demostración (24 socios, 2-5 controles mensuales)
    $stE = $pdo->prepare('INSERT INTO evaluaciones (socio_id,fecha,peso,talla,grasa,cintura,pecho,brazo,pierna,notas) VALUES (?,?,?,?,?,?,?,?,?,?)');
    for ($sid = 1; $sid <= min(24, $total); $sid++) {
        $n = mt_rand(2, 5); $talla = mt_rand(155, 188) / 100; $peso = mt_rand(580, 950) / 10; $gr = mt_rand(160, 340) / 10;
        $cin = mt_rand(70, 105); $pec = mt_rand(88, 112); $bra = mt_rand(27, 38); $pie = mt_rand(48, 64);
        for ($k = $n - 1; $k >= 0; $k--) {
            $f = (clone $today)->modify('-' . ($k * 30 + mt_rand(0, 6)) . ' days')->format('Y-m-d');
            $stE->execute([$sid, $f, round($peso, 1), $talla, round($gr, 1), $cin, $pec, $bra, $pie, $k === $n - 1 ? 'Evaluación inicial' : null]);
            $peso -= mt_rand(0, 15) / 10; $gr -= mt_rand(0, 12) / 10; $cin -= mt_rand(0, 2); $bra += mt_rand(0, 1) * 0.5;
        }
    }

    $pdo->commit();
}
