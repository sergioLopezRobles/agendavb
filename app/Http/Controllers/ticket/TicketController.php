<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\RegistraMovimientos;

class TicketController extends Controller
{
    use RegistraMovimientos;

    public function index()
    {
        $idUsuario = Auth::id();

        $prioridades = DB::table('prioridad_ticket_soporte_usuarios_negocios')->get();
        $estados     = DB::table('estado_ticket_soporte_usuarios_negocios')->get();
        $preguntas   = DB::table('preguntas_frecuentes_ticket')->get();
        $negocios    = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        $tickets = DB::table('ticket_soporte_usuarios_negocios as t')
            ->join('negocios as n', 't.id_negocio', '=', 'n.id')
            ->leftJoin('prioridad_ticket_soporte_usuarios_negocios as p', 't.id_prioridad', '=', 'p.id')
            ->join('estado_ticket_soporte_usuarios_negocios as e', 't.id_estado', '=', 'e.id')
            ->leftJoin('preguntas_frecuentes_ticket as f', 't.id_pregunta', '=', 'f.id')
            ->where('t.id_usuario', $idUsuario)
            ->select(
                't.id',
                't.asunto',
                't.id_estado',
                't.id_prioridad',
                't.id_pregunta',
                'n.nombre as negocio_nombre',
                'p.descripcion as prioridad_nombre',
                'e.descripcion as estado_nombre',
                'f.pregunta as pregunta_nombre',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y') as fecha")
            )
            ->orderBy('t.created_at', 'desc')
            ->get();

        return response()->json([
            'valid'       => true,
            'tickets'     => $tickets,
            'prioridades' => $prioridades,
            'estados'     => $estados,
            'negocios'    => $negocios,
            'preguntas'   => $preguntas
        ]);
    }

    // --- FUNCIÓN EXCLUSIVA PARA EL ADMINISTRADOR ---
    public function indexAdmin()
    {
        $prioridades = DB::table('prioridad_ticket_soporte_usuarios_negocios')->get();
        $estados     = DB::table('estado_ticket_soporte_usuarios_negocios')->get();

        // 1. Agregamos el JOIN a users (u) y seleccionamos sus datos
        $tickets = DB::table('ticket_soporte_usuarios_negocios as t')
            ->join('negocios as n', 't.id_negocio', '=', 'n.id')
            ->join('users as u', 't.id_usuario', '=', 'u.id') // <-- NUEVO JOIN
            ->leftJoin('prioridad_ticket_soporte_usuarios_negocios as p', 't.id_prioridad', '=', 'p.id')
            ->join('estado_ticket_soporte_usuarios_negocios as e', 't.id_estado', '=', 'e.id')
            ->leftJoin('preguntas_frecuentes_ticket as f', 't.id_pregunta', '=', 'f.id')
            ->select(
                't.id',
                't.asunto',
                't.id_estado',
                't.id_prioridad',
                'n.nombre as negocio_nombre',
                'u.name as dueno_nombre',       
                'u.email as dueno_email',
                'u.telefono as dueno_telefono',
                'p.descripcion as prioridad_nombre',
                'e.descripcion as estado_nombre',
                'f.pregunta as pregunta_nombre',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y') as fecha")
            )
            ->orderBy('t.created_at', 'desc')
            ->get();

        return response()->json([
            'valid'       => true,
            'tickets'     => $tickets,
            'prioridades' => $prioridades,
            'estados'     => $estados
        ]);
    }

    public function store(Request $request)
    {
        try {
            $negocio = DB::table('negocios')
                ->where('id', $request->id_negocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json(['valid' => false, 'message' => 'Negocio no válido.']);
            }

            do {
                $idGenerado = 'TKT-' . strtoupper(Str::random(4));
                $existe = DB::table('ticket_soporte_usuarios_negocios')->where('id', $idGenerado)->exists();
            } while ($existe);

            DB::transaction(function () use ($idGenerado, $request){

                DB::table('ticket_soporte_usuarios_negocios')->insert([
                    'id'          => $idGenerado,
                    'id_usuario'  => Auth::id(),
                    'id_negocio'  => $request->id_negocio,
                    'id_pregunta' => $request->id_pregunta,
                    'asunto'      => $request->asunto,
                    'id_prioridad'=> null,
                    'id_estado'   => '1',
                    'created_at'  => Carbon::now(),
                    'updated_at'  => Carbon::now()
                ]);

                // ── LOG: ticket creado ────────────────────────────────────────────
                $this->guardarLog('tickets', 'crear', $idGenerado, [
                    'asunto'      => $request->asunto,
                    'id_negocio'  => $request->id_negocio,
                    'id_pregunta' => $request->id_pregunta
                ]);
            });

            return response()->json([
                'valid'   => true,
                'message' => 'Ticket creado correctamente con ID: ' . $idGenerado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error al crear el ticket: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'valid'   => false,
                'message' => 'Error al crear el ticket 2: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($request, $id) {

                // Buscamos el ticket y el nombre del nuevo estado para el mensaje
                $ticket = DB::table('ticket_soporte_usuarios_negocios')->where('id', $id)->first();
                $estado = DB::table('estado_ticket_soporte_usuarios_negocios')
                    ->where('id', $request->id_estado)
                    ->first();

                // Actualizamos el ticket
                DB::table('ticket_soporte_usuarios_negocios')
                    ->where('id', $id)
                    ->update([
                        'id_prioridad' => $request->id_prioridad,
                        'id_estado'    => $request->id_estado,
                        'updated_at'   => Carbon::now()
                    ]);

                //CREAR NOTIFICACIÓN PARA EL DUEÑO
                DB::table('notificaciones')->insert([
                    'id_usuario' => $ticket->id_usuario,
                    'titulo'     => 'Actualización de Ticket',
                    'mensaje'    => "Un administrador ajustó el estado de tu ticket #{$id} a: " . ($estado->descripcion ?? 'Actualizado'),
                    'leida'      => false,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $this->guardarLog('tickets', 'editar', $id, [
                    'id_prioridad' => $request->id_prioridad,
                    'id_estado'    => $request->id_estado
                ]);
            });

            return response()->json(['valid' => true, 'message' => 'Ticket actualizado y dueño notificado']);
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 2. Nueva función para que el Dueño descargue sus avisos
    public function obtenerNotificaciones()
    {
        $notificaciones = DB::table('notificaciones')
            ->where('id_usuario', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10) // Solo las últimas 10
            ->get();

        return response()->json([
            'valid' => true,
            'notificaciones' => $notificaciones,
            'sin_leer' => $notificaciones->where('leida', false)->count()
        ]);
    }

    // 3. Función para limpiar el puntito rojo
    public function marcarNotificacionesLeidas()
    {
        DB::table('notificaciones')
            ->where('id_usuario', Auth::id())
            ->update(['leida' => true]);

        return response()->json(['valid' => true]);
    }
}
