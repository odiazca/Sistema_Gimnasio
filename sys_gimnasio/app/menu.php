<?php
/** Definición del menú vertical y de los módulos del sistema */
return [
    'Principal' => [
        'dashboard'    => ['Dashboard', 'bi-grid-1x2-fill', 'url' => 'dashboard'],
    ],
    'Gestión de socios' => [
        'socios'       => ['Socios', 'bi-people-fill', 'url' => 'socios', 'desc' => 'Registro, ficha completa, foto, estado, historial y búsqueda de socios.',
                           'features' => ['Alta rápida con foto y datos de emergencia', 'Ficha con historial de pagos y asistencias', 'Estados: activo, inactivo, suspendido', 'Importación / exportación a Excel', 'Carnet con código QR']],
        'membresias'   => ['Planes y membresías', 'bi-award-fill', 'url' => 'membresias', 'desc' => 'Planes, precios, inscripciones, renovaciones y congelamientos.',
                           'features' => ['Planes diario, mensual, trimestral, anual, pareja', 'Renovación en un clic', 'Descuentos y promociones', 'Alertas de vencimiento']],
        'pagos'        => ['Pagos y cobranzas', 'bi-credit-card-2-front-fill', 'url' => 'pagos', 'desc' => 'Cobros de membresías, deudas, comprobantes y métodos de pago.',
                           'features' => ['Efectivo, tarjeta, Yape, Plin, transferencia', 'Pagos parciales y deudas pendientes', 'Boleta / ticket imprimible', 'Historial por socio', 'Anulaciones con auditoría']],
        'asistencia'   => ['Control de asistencia', 'bi-door-open-fill', 'url' => 'asistencia', 'desc' => 'Check-in por DNI, QR o huella, con validación de membresía vigente.',
                           'features' => ['Ingreso por DNI / QR / huella', 'Bloqueo automático de membresías vencidas', 'Aforo en tiempo real', 'Reporte de frecuencia por socio', 'Pantalla de bienvenida para recepción']],
    ],
    'Entrenamiento' => [
        'clases'       => ['Clases y horarios', 'bi-calendar-week-fill', 'url' => 'clases', 'desc' => 'Programación semanal, cupos y reservas de clases grupales.',
                           'features' => ['Calendario semanal por sala', 'Reserva y lista de espera', 'Control de cupo', 'Asistencia por clase']],
        'entrenadores' => ['Entrenadores', 'bi-person-badge-fill', 'url' => 'entrenadores', 'desc' => 'Personal, especialidades, horarios y comisiones.',
                           'features' => ['Ficha del entrenador', 'Asignación de socios', 'Comisión por clases personales', 'Disponibilidad']],
        'rutinas'      => ['Rutinas', 'bi-clipboard2-pulse-fill', 'url' => 'rutinas', 'desc' => 'Biblioteca de ejercicios y rutinas personalizadas por socio.',
                           'features' => ['Catálogo de ejercicios con video', 'Rutinas por objetivo y nivel', 'Asignación y seguimiento', 'Impresión / envío por WhatsApp']],
        'evaluaciones' => ['Evaluaciones físicas', 'bi-activity', 'url' => 'evaluaciones', 'desc' => 'Peso, IMC, % de grasa y medidas; gráficos de progreso.',
                           'features' => ['Medidas corporales y IMC automático', 'Comparativo antes / después', 'Gráfico de evolución', 'Metas por socio']],
    ],
    'Negocio' => [
        'ventas'       => ['Punto de venta', 'bi-cart-fill', 'url' => 'ventas', 'desc' => 'Venta de suplementos, bebidas y accesorios con ticket.',
                           'features' => ['Venta rápida con buscador y código', 'Venta a socio o cliente libre', 'Ticket imprimible', 'Cierre de caja por turno']],
        'inventario'   => ['Inventario', 'bi-box-seam-fill', 'url' => 'inventario', 'desc' => 'Productos, stock, compras, proveedores y alertas de stock mínimo.',
                           'features' => ['Kardex de movimientos', 'Compras a proveedores', 'Alerta de stock mínimo', 'Inventario valorizado']],
        'caja'         => ['Caja', 'bi-cash-coin', 'url' => 'caja', 'desc' => 'Apertura y cierre de caja, ingresos y egresos por turno.',
                           'features' => ['Apertura / cierre por usuario', 'Arqueo de caja', 'Movimientos manuales', 'Reporte de diferencias']],
        'gastos'       => ['Gastos', 'bi-receipt-cutoff', 'url' => 'gastos', 'desc' => 'Registro de gastos operativos, fijos y de personal.',
                           'features' => ['Categorías de gasto', 'Gastos recurrentes', 'Adjuntar comprobante', 'Utilidad neta mensual']],
        'reportes'     => ['Reportes', 'bi-bar-chart-line-fill', 'url' => 'reportes', 'desc' => 'Reportes financieros, de socios, asistencia y ventas con exportación.',
                           'features' => ['Ingresos por período, plan y método', 'Retención y deserción de socios', 'Horas pico de asistencia', 'Exportar a PDF / Excel']],
    ],
    'Comunicación' => [
        'crm'          => ['CRM', 'bi-bullseye', 'url' => 'crm', 'desc' => 'Embudo de prospectos, seguimiento, retención y campañas.',
                           'features' => ['Embudo Kanban de prospectos', 'Ficha 360° del socio', 'Riesgo de abandono', 'Campañas por WhatsApp']],
        'notificaciones' => ['Notificaciones', 'bi-megaphone-fill', 'url' => 'notificaciones', 'desc' => 'Recordatorios de vencimiento, cumpleaños y campañas por WhatsApp y correo.',
                           'features' => ['Aviso de membresía por vencer', 'Saludo de cumpleaños automático', 'Campañas segmentadas', 'Plantillas de mensajes']],
    ],
    'Inteligencia' => [
        'asistente'    => ['Asistente IA', 'bi-stars', 'url' => 'asistente', 'desc' => 'Reportes en lenguaje natural.', 'features' => []],
        'chat'         => ['Chat IA', 'bi-chat-dots-fill', 'url' => 'chat', 'desc' => 'Conversa con tus datos.', 'features' => []],
    ],
    'Administración' => [
        'usuarios'     => ['Usuarios y roles', 'bi-shield-lock-fill', 'url' => 'usuarios', 'desc' => 'Usuarios del sistema, roles y permisos por módulo.',
                           'features' => ['Roles: administrador, recepción, entrenador, contador', 'Permisos por módulo', 'Bitácora de acciones', 'Cambio de contraseña']],
        'anulaciones'  => ['Anulaciones', 'bi-slash-circle-fill', 'url' => 'anulaciones', 'desc' => 'Pagos y ventas anulados.', 'features' => []],
        'sistema'      => ['Respaldo y sistema', 'bi-shield-check', 'url' => 'sistema', 'desc' => 'Copias de seguridad, restauración y reinicio para una empresa nueva.', 'features' => []],
        'configuracion'=> ['Configuración', 'bi-gear-fill', 'url' => 'configuracion', 'desc' => 'Datos de la empresa, logo, moneda y formatos, aforo y apariencia.',
                           'features' => ['Datos y logo del negocio', 'Aforo máximo y horarios', 'Días de aviso de vencimiento', 'Moneda y separadores']],
    ],
];
