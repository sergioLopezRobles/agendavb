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

        // 2. OBTENEMOS LOS NEGOCIOS DEL USUARIO (PARA EL COMBOBOX DEL MODAL)
        $negocios = DB::table('negocios')->where('id_usuario', $idUsuario)->get();

        // 3. OBTENEMOS LOS TICKETS DE ESTE USUARIO CON JOIN PARA TRAER LOS NOMBRES
        $tickets = DB::table('ticket_soporte_usuarios_negocios as t')
            ->join('negocios as n', 't.id_negocio', '=', 'n.id')
            ->leftJoin('prioridad_ticket_soporte_usuarios_negocios as p', 't.id_prioridad', '=', 'p.id')
            ->join('estado_ticket_soporte_usuarios_negocios as e', 't.id_estado', '=', 'e.id')
            ->where('t.id_usuario', $idUsuario)
            ->select(
                't.id',
                't.asunto',
                't.id_estado',
                't.id_prioridad',
                'n.nombre as negocio_nombre',
                'p.descripcion as prioridad_nombre',
                'e.descripcion as estado_nombre',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y') as fecha")
            )
            ->orderBy('t.created_at', 'desc')
            ->get();

        return response()->json([
            'valid' => true,
            'tickets' => $tickets,
            'prioridades' => $prioridades,
            'estados' => $estados,
            'negocios' => $negocios
        ]);
    }

    public function store(Request $request)
    {
        try {
            // verificamos que el negocio pertenezca al usuario
            $negocio = DB::table('negocios')
                ->where('id', $request->id_negocio)
                ->where('id_usuario', Auth::id())
                ->first();

            if (!$negocio) {
                return response()->json(['valid' => false, 'message' => 'Negocio no válido.']);
            }

            // generamos id aleatorio tipo tkt-xxxx (4 caracteres alfanumericos mayusculas)
            // usamos un ciclo para garantizar que nunca se repita en la base de datos
            do {
                $idGenerado = 'TKT-' . strtoupper(Str::random(4));
                $existe = DB::table('ticket_soporte_usuarios_negocios')->where('id', $idGenerado)->exists();
            } while ($existe);

            DB::table('ticket_soporte_usuarios_negocios')->insert([
                'id' => $idGenerado,
                'id_usuario' => Auth::id(),
                'id_negocio' => $request->id_negocio,
                'asunto' => $request->asunto,
                'id_prioridad' => null, // se guarda como nulo o por definir
                'id_estado' => '1', // por defecto 1 = pendiente
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
}
