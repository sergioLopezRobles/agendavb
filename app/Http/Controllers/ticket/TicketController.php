<?php

namespace App\Http\Controllers\ticket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index()
    {
        $idUsuario = Auth::id();

        // 1. OBTENEMOS CATALOGOS
        $prioridades = DB::table('prioridad_ticket_soporte_usuarios_negocios')->get();
        $estados = DB::table('estado_ticket_soporte_usuarios_negocios')->get();
        $preguntas = DB::table('preguntas_frecuentes_ticket')->get(); // --> CARGAMOS PREGUNTAS

        // 2. OBTENEMOS LOS NEGOCIOS DEL USUARIO
        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        // 3. OBTENEMOS LOS TICKETS DE ESTE USUARIO CON JOIN PARA TRAER NOMBRES Y PREGUNTAS
        $tickets = DB::table('ticket_soporte_usuarios_negocios as t')
            ->join('negocios as n', 't.id_negocio', '=', 'n.id')
            ->leftJoin('prioridad_ticket_soporte_usuarios_negocios as p', 't.id_prioridad', '=', 'p.id')
            ->join('estado_ticket_soporte_usuarios_negocios as e', 't.id_estado', '=', 'e.id')
            ->leftJoin('preguntas_frecuentes_ticket as f', 't.id_pregunta', '=', 'f.id') // --> JOIN PREGUNTA
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
            'valid' => true,
            'tickets' => $tickets,
            'prioridades' => $prioridades,
            'estados' => $estados,
            'negocios' => $negocios,
            'preguntas' => $preguntas
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

            DB::table('ticket_soporte_usuarios_negocios')->insert([
                'id' => $idGenerado,
                'id_usuario' => Auth::id(),
                'id_negocio' => $request->id_negocio,
                'id_pregunta' => $request->id_pregunta, // --> GUARDAMOS LA PREGUNTA SELECCIONADA
                'asunto' => $request->asunto, // --> GUARDAMOS EL ASUNTO QUE ENVÍA VUE
                'id_prioridad' => null,
                'id_estado' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return response()->json([
                'valid' => true,
                'message' => 'Ticket creado correctamente con ID: ' . $idGenerado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error al crear el ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::table('ticket_soporte_usuarios_negocios')
                ->where('id', $id)
                ->update([
                    'id_prioridad' => $request->id_prioridad,
                    'id_estado' => $request->id_estado,
                    'updated_at' => Carbon::now()
                ]);

            return response()->json([
                'valid' => true,
                'message' => 'Ticket actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error al actualizar el ticket: ' . $e->getMessage()
            ], 500);
        }
    }
}
