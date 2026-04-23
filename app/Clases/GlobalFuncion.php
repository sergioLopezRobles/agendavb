<?php

namespace App\Clases;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Stripe\PaymentIntent;
use Stripe\Stripe;

// Importaciones para el Excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class GlobalFuncion
{
    public function obtenerPlanCompleto($idPlan)
    {
        $plan = DB::table('planes')->where('id', $idPlan)->first();

        if (!$plan) {
            return null;
        }

        $caracteristicas = DB::table('caracteristicasplanes')
            ->where('id_plan', $idPlan)
            ->get();

        foreach ($caracteristicas as $c) {
            $plan->{$c->titulo} = $c->valor;
        }

        return $plan;
    }

    public function pagoUnicoStripe($emailEmisor, $idNegocio, $idServicio, $payment_method_id, $montoUnico)
    {
        // ... (Se queda exactamente igual tu código de Stripe) ...
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => ($montoUnico * 100),
                'currency' => 'mxn',
                'payment_method' => $payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'payment_method_types' => ['card'],
                'expand' => ['charges.data']
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $charge = $paymentIntent->charges->data[0] ?? null;

                DB::table('payments')->insert([
                    'id_negocio' => $idNegocio,
                    'id_servicio' => $idServicio,
                    'description' => 'Anticipo de cita',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'payment_method_id' => $paymentIntent->payment_method,
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                    'payer_email' => $emailEmisor,
                    'receipt_url' => $charge->receipt_url ?? null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                return ['valid' => true, 'message' => 'El pago fue aceptado'];
            }

            if ($paymentIntent->status === 'requires_action') {
                return ['valid' => false, 'requires_action' => true, 'client_secret' => $paymentIntent->client_secret];
            }

            return ['valid' => false, 'message' => 'El pago no pudo ser procesado'];

        } catch (\Stripe\Exception\CardException $e) {
            return ['valid' => false, 'message' => 'El pago fue rechazado: ' . $e->getError()->message];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Error general: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // NUEVA FUNCIÓN GLOBAL PARA GENERAR EL EXCEL
    // =========================================================================
    public function generarExcelAgenda($usuarioId)
    {
        $negocios = DB::table('negocios')->where('id_usuario', $usuarioId)->get();

        // Si no tiene negocios, devolvemos null para que el controlador lo maneje
        if ($negocios->isEmpty()) {
            return null;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $hoy = Carbon::now();
        $limite30Dias = Carbon::now()->addDays(30);

        $estiloCabecera = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0D6EFD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        foreach ($negocios as $negocio) {
            $hoja = $spreadsheet->createSheet();
            $hoja->setTitle(substr($negocio->nombre, 0, 30));

            $hoja->mergeCells('A1:H1');
            $hoja->setCellValue('A1', "AGENDA DE CITAS - " . strtoupper($negocio->nombre));
            $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $hoja->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $hoja->mergeCells('A2:H2');
            $hoja->setCellValue('A2', "Próximos 30 días (Generado el " . $hoy->format('d/m/Y') . ")");
            $hoja->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FF666666');
            $hoja->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $cabeceras = ['Fecha', 'Hora', 'Cliente', 'Teléfono', 'Servicio', 'Precio', 'Anticipo', 'Estado'];
            $columna = 'A';
            foreach ($cabeceras as $cabecera) {
                $hoja->setCellValue($columna . '4', $cabecera);
                $columna++;
            }
            $hoja->getStyle('A4:H4')->applyFromArray($estiloCabecera);

            $citas = DB::table('citas')
                ->leftJoin('servicios', 'citas.id_servicio', '=', 'servicios.id')
                ->select('citas.*', 'servicios.nombre as servicio_nombre')
                ->where('citas.id_negocio', $negocio->id)
                ->whereBetween('citas.fecha', [$hoy->toDateString(), $limite30Dias->toDateString()])
                ->orderBy('citas.fecha', 'asc')
                ->orderBy('citas.hora', 'asc')
                ->get();

            $fila = 5;
            foreach ($citas as $cita) {
                $estado = 'Pendiente';
                if ($cita->id_estado == '1') $estado = 'En proceso';
                if ($cita->id_estado == '2') $estado = 'Terminado';
                if ($cita->id_estado == '3') $estado = 'Cancelado';

                $precioBase = floatval($cita->total);
                $anticipoMonto = floatval($cita->anticipo);

                $hoja->setCellValue('A' . $fila, $cita->fecha);
                $hoja->setCellValue('B' . $fila, $cita->hora);
                $hoja->setCellValue('C' . $fila, $cita->cliente_nombre);
                $hoja->setCellValue('D' . $fila, $cita->cliente_telefono);
                $hoja->setCellValue('E' . $fila, $cita->servicio_nombre);
                $hoja->setCellValue('F' . $fila, '$' . number_format($precioBase, 2));
                $hoja->setCellValue('G' . $fila, '$' . number_format($anticipoMonto, 2));
                $hoja->setCellValue('H' . $fila, $estado);

                $hoja->getStyle("A{$fila}:B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $hoja->getStyle("D{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $hoja->getStyle("F{$fila}:H{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $hoja->getStyle("A{$fila}:H{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $fila++;
            }

            foreach (range('A', 'H') as $col) {
                $hoja->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Devolvemos el archivo Excel
        return $spreadsheet;
    }
}
