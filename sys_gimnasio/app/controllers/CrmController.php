<?php
require_once ROOT_PATH . '/app/models/Crm.php';
require_once ROOT_PATH . '/app/models/Socio.php';

class CrmController extends Controller
{
    private function init(): void { $this->requireAuth(); Crm::ensure(); }

    private function nav(string $t): array
    {
        return ['crm_tab' => $t, 'crm_tareas' => Crm::conteoTareas()['vencidas'] + Crm::conteoTareas()['hoy']];
    }
    private function checkJson(): void
    {
        $tok = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !Auth::verifyCsrf($tok)) $this->json(['ok' => false, 'error' => 'Sesión expirada. Recarga la página.'], 419);
    }
    private function fechaHora(string $v): ?string
    {
        $v = trim($v); if ($v === '') return null;
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $v) ?: DateTime::createFromFormat('Y-m-d H:i:s', $v) ?: DateTime::createFromFormat('Y-m-d', $v);
        return $d ? $d->format('Y-m-d H:i:s') : null;
    }
    private function usuarios(): array { return Database::all('SELECT id, nombre FROM usuarios WHERE estado = 1 ORDER BY nombre'); }

    /* ---- Panel ---- */
    public function index(): void
    {
        $this->init();
        $riesgo = Crm::riesgo(30);
        $tareas = array_slice(Crm::tareas('pendientes'), 0, 6);
        $this->view('crm/index', $this->nav('panel') + [
            'title' => 'CRM', 'active' => 'crm', 'r' => Crm::resumen(), 'embudo' => Crm::embudo(), 'fuentes' => Crm::porFuente(), 'tend' => Crm::tendencia(),
            'motivos' => Crm::motivosPerdida(), 'tareas' => $tareas, 'cnt' => Crm::conteoTareas(), 'riesgo' => array_slice($riesgo, 0, 6),
            'n_riesgo' => count(array_filter($riesgo, fn($x) => $x['nivel'] === 'alto')), 'charts' => null,
        ]);
    }

    /* ---- Embudo (kanban) ---- */
    public function embudo(): void
    {
        $this->init();
        $f = ['q' => $this->in('q'), 'fuente' => isset(Crm::FUENTES[$this->in('fuente')]) ? $this->in('fuente') : '', 'resp' => (int)$this->in('resp'), 'cerrados_desde' => date('Y-m-d', strtotime('-30 days'))];
        $this->view('crm/embudo', $this->nav('embudo') + ['title' => 'Embudo de prospectos', 'active' => 'crm', 'leads' => Crm::leads($f), 'f' => $f, 'usuarios' => $this->usuarios(), 'embudo' => Crm::embudo()]);
    }

    public function guardarLead(): void
    {
        $this->init(); $this->requirePost('crm/embudo');
        $id = (int)($_POST['id'] ?? 0);
        $d = ['nombres' => trim((string)($_POST['nombres'] ?? '')), 'apellidos' => trim((string)($_POST['apellidos'] ?? '')), 'telefono' => preg_replace('/[^\d+ ]/', '', (string)($_POST['telefono'] ?? '')),
              'email' => trim((string)($_POST['email'] ?? '')), 'fuente' => isset(Crm::FUENTES[$_POST['fuente'] ?? '']) ? $_POST['fuente'] : 'otro', 'interes' => trim((string)($_POST['interes'] ?? '')),
              'valor' => max(0, min(99999, (float)($_POST['valor'] ?? 0))), 'responsable_id' => (int)($_POST['responsable_id'] ?? 0) ?: (int)Auth::user()['id'], 'notas' => trim((string)($_POST['notas'] ?? ''))];
        $back = (string)($_POST['volver'] ?? '') === 'lead' && $id ? 'crm/lead/' . $id : 'crm/embudo';
        if ($d['nombres'] === '') { $this->flash('danger', 'Escribe al menos el nombre del prospecto.'); $this->redirect($back); }
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) { $this->flash('danger', 'El correo no es válido.'); $this->redirect($back); }
        if ($id && !Crm::lead($id)) { $this->flash('danger', 'El prospecto no existe.'); $this->redirect('crm/embudo'); }
        $nuevo = !$id; $id = Crm::guardarLead($d, $id);
        if ($nuevo) Crm::actividad('nota', 'Prospecto registrado (' . (Crm::FUENTES[$d['fuente']][0]) . ').', $id, null, null, (int)Auth::user()['id']);
        $this->flash('success', $nuevo ? 'Prospecto agregado al embudo.' : 'Prospecto actualizado.');
        $this->redirect($nuevo ? 'crm/embudo' : $back);
    }

    /** JSON: mover prospecto de etapa (arrastrar o botones) */
    public function mover(): void
    {
        $this->init(); $this->checkJson();
        $id = (int)($_POST['id'] ?? 0); $etapa = (string)($_POST['etapa'] ?? '');
        $l = Crm::lead($id);
        if (!$l || !isset(Crm::ETAPAS[$etapa])) $this->json(['ok' => false, 'error' => 'Datos inválidos.'], 422);
        if ($etapa === 'ganado' && !$l['socio_id']) $this->json(['ok' => true, 'convertir' => url('socios/crear?lead=' . $id)]);
        if ($l['etapa'] === $etapa) $this->json(['ok' => true]);
        $motivo = in_array($_POST['motivo'] ?? '', Crm::MOTIVOS, true) ? $_POST['motivo'] : null;
        Crm::mover($id, $etapa, $motivo);
        Crm::actividad('nota', 'Etapa: ' . Crm::ETAPAS[$l['etapa']][0] . ' → ' . Crm::ETAPAS[$etapa][0] . ($etapa === 'perdido' ? ' (' . ($motivo ?: 'Otro') . ')' : ''), $id, null, null, (int)Auth::user()['id']);
        $this->json(['ok' => true, 'embudo' => Crm::embudo()]);
    }

    public function lead($id = 0): void
    {
        $this->init();
        $l = Crm::lead((int)$id);
        if (!$l) { $this->flash('danger', 'El prospecto no existe.'); $this->redirect('crm/embudo'); }
        $this->view('crm/lead', $this->nav('embudo') + ['title' => 'Prospecto', 'active' => 'crm', 'l' => $l, 'acts' => Crm::actividadesDe('lead', (int)$id), 'usuarios' => $this->usuarios()]);
    }

    public function eliminarLead($id = 0): void
    {
        $this->init(); $this->requirePost('crm/embudo');
        Database::exec('DELETE FROM crm_actividades WHERE lead_id = ?', [(int)$id]);
        Database::exec('DELETE FROM crm_leads WHERE id = ?', [(int)$id]);
        Bitacora::log('crm_lead', 'Eliminó el prospecto #' . (int)$id);
        $this->flash('success', 'Prospecto eliminado.');
        $this->redirect('crm/embudo');
    }

    /* ---- Actividades / tareas ---- */
    public function guardarActividad(): void
    {
        $this->init(); $this->requirePost('crm');
        $lead = (int)($_POST['lead_id'] ?? 0) ?: null; $socio = (int)($_POST['socio_id'] ?? 0) ?: null;
        $back = $lead ? 'crm/lead/' . $lead : ($socio ? 'crm/socio/' . $socio : 'crm/tareas');
        $det = trim((string)($_POST['detalle'] ?? ''));
        if ($det === '' || (!$lead && !$socio)) { $this->flash('danger', 'Escribe el detalle de la actividad.'); $this->redirect($back); }
        $prog = $this->fechaHora((string)($_POST['programada'] ?? ''));
        Crm::actividad((string)($_POST['tipo'] ?? 'nota'), $det, $lead, $socio, $prog, (int)Auth::user()['id']);
        $this->flash('success', $prog ? 'Seguimiento programado para el ' . date('d/m/Y H:i', strtotime($prog)) . '.' : 'Actividad registrada.');
        $this->redirect($back);
    }

    public function completar(): void
    {
        $this->init(); $this->checkJson();
        $this->json(['ok' => Crm::completar((int)($_POST['id'] ?? 0)), 'cnt' => Crm::conteoTareas()]);
    }

    public function tareas(): void
    {
        $this->init();
        $est = $this->in('est', 'pendientes') === 'hechas' ? 'hechas' : 'pendientes';
        $this->view('crm/tareas', $this->nav('tareas') + ['title' => 'Tareas y seguimientos', 'active' => 'crm', 'est' => $est, 'rows' => Crm::tareas($est), 'cnt' => Crm::conteoTareas()]);
    }

    /* ---- Retención ---- */
    public function retencion(): void
    {
        $this->init();
        $niv = in_array($this->in('nivel'), ['alto', 'medio'], true) ? $this->in('nivel') : '';
        $todos = Crm::riesgo(30);
        $rows = $niv ? array_values(array_filter($todos, fn($x) => $x['nivel'] === $niv)) : $todos;
        $tpl = Crm::plantilla('riesgo');
        foreach ($rows as &$r) { $r['wa'] = $r['telefono'] ? wa_link($r['telefono'], Crm::mensaje($tpl, $r)) : ''; } unset($r);
        $this->view('crm/retencion', $this->nav('retencion') + [
            'title' => 'Retención de socios', 'active' => 'crm', 'rows' => $rows, 'nivel' => $niv,
            'n_alto' => count(array_filter($todos, fn($x) => $x['nivel'] === 'alto')), 'n_medio' => count(array_filter($todos, fn($x) => $x['nivel'] === 'medio')),
            'ingreso_riesgo' => array_sum(array_map(fn($x) => $x['nivel'] === 'alto' ? 1 : 0, $todos)),
        ]);
    }

    /* ---- Campañas ---- */
    public function campanas(): void
    {
        $this->init();
        $seg = $this->in('seg'); $cnt = []; $dest = []; $tpl = '';
        foreach (array_keys(Crm::SEGS) as $k) $cnt[$k] = $k === $seg ? 0 : count(Crm::segmento($k));
        if (isset(Crm::SEGS[$seg])) { $dest = Crm::segmento($seg); $cnt[$seg] = count($dest); $tpl = Crm::plantilla($seg); }
        else $seg = '';
        foreach ($dest as &$d) $d['preview'] = Crm::mensaje($tpl, $d); unset($d);
        $this->view('crm/campanas', $this->nav('campanas') + ['title' => 'Campañas', 'active' => 'crm', 'seg' => $seg, 'cnt' => $cnt, 'dest' => $dest, 'tpl' => $tpl, 'hist' => Crm::campanas()]);
    }

    public function crearCampana(): void
    {
        $this->init(); $this->requirePost('crm/campanas');
        $seg = (string)($_POST['seg'] ?? ''); if (!isset(Crm::SEGS[$seg])) $this->redirect('crm/campanas');
        $tpl = trim((string)($_POST['mensaje'] ?? ''));
        if ($tpl === '' || mb_strlen($tpl) > 700) { $this->flash('danger', 'El mensaje debe tener entre 1 y 700 caracteres.'); $this->redirect('crm/campanas?seg=' . $seg); }
        $sel = array_map('strval', (array)($_POST['dest'] ?? []));
        $dest = array_values(array_filter(Crm::segmento($seg), fn($d) => in_array($d['tipo'] . ':' . $d['id'], $sel, true) && preg_replace('/\D/', '', (string)$d['telefono']) !== ''));
        if (!$dest) { $this->flash('danger', 'Selecciona al menos un destinatario con teléfono.'); $this->redirect('crm/campanas?seg=' . $seg); }
        $nombre = trim((string)($_POST['nombre'] ?? '')) ?: Crm::SEGS[$seg][0] . ' · ' . date('d/m/Y');
        $cid = Crm::crearCampana(mb_substr($nombre, 0, 120), $seg, $tpl, $dest, (int)Auth::user()['id']);
        Bitacora::log('crm_campana', 'Creó la campaña «' . $nombre . '» (' . count($dest) . ' destinatarios)');
        $this->flash('success', 'Campaña creada con ' . count($dest) . ' destinatarios. Envía los mensajes uno a uno.');
        $this->redirect('crm/campana/' . $cid);
    }

    public function campana($id = 0): void
    {
        $this->init();
        $c = Database::row('SELECT * FROM crm_campanas WHERE id = ?', [(int)$id]);
        if (!$c) { $this->flash('danger', 'La campaña no existe.'); $this->redirect('crm/campanas'); }
        $env = Database::all('SELECT * FROM crm_envios WHERE campana_id = ? ORDER BY enviado_en IS NOT NULL, id', [(int)$id]);
        $this->view('crm/campana', $this->nav('campanas') + ['title' => 'Campaña', 'active' => 'crm', 'c' => $c, 'env' => $env]);
    }

    public function marcarEnvio(): void
    {
        $this->init(); $this->checkJson();
        $this->json(['ok' => Crm::marcarEnvio((int)($_POST['id'] ?? 0), (int)Auth::user()['id'])]);
    }

    public function eliminarCampana($id = 0): void
    {
        $this->init(); $this->requirePost('crm/campanas');
        Database::exec('DELETE FROM crm_campanas WHERE id = ?', [(int)$id]);
        $this->flash('success', 'Campaña eliminada.'); $this->redirect('crm/campanas');
    }

    /* ---- Ficha 360° del socio ---- */
    public function socio($id = 0): void
    {
        $this->init();
        $s = Socio::find((int)$id);
        if (!$s) { $this->flash('danger', 'El socio no existe.'); $this->redirect('socios'); }
        $row = null;
        foreach (Crm::riesgo(0) as $r) if ((int)$r['id'] === (int)$id) { $row = $r; break; }
        $this->view('crm/socio', $this->nav('retencion') + ['title' => 'CRM · ' . $s['nombres'], 'active' => 'crm', 's' => $s, 'riesgo' => $row, 'linea' => Crm::linea((int)$id), 'rfm' => Crm::rfm((int)$id),
            'pend' => array_values(array_filter(Crm::actividadesDe('socio', (int)$id), fn($a) => !$a['completada']))]);
    }
}
