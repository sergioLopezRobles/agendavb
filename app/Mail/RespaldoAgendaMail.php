<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RespaldoAgendaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $usuario;
    public $excelPath;
    public $nombreArchivo;

    public function __construct($usuario, $excelPath, $nombreArchivo)
    {
        $this->usuario = $usuario;
        $this->excelPath = $excelPath;
        $this->nombreArchivo = $nombreArchivo;
    }

    public function build()
    {
        return $this->subject('Respaldo de tu Agenda - Vista Boreal')
            ->view('emails.respaldo_agenda')
            ->attach($this->excelPath, [
                'as' => $this->nombreArchivo,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
