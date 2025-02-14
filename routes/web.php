<?php

use Illuminate\Support\Facades\Route;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


Route::get('/', function () {
    return view('welcome');
});


Route::get('enviar-correo', function () {
    $details = [
        'title'   => 'Success',
        'content' => 'This is an email testing using Laravel-Brevo',
    ];

    // Registro antes de enviar
    Log::info('Iniciando envío de correo', ['details' => $details]);

    try {
        Mail::to('armando.correah@gmail.com')->send(new \App\Mail\TestMail($details));

    } catch (\Exception $e) {
        // Registro del error
        Log::error('Error al enviar el correo', [
            'error' => $e->getMessage(),
        ]);
        return 'Error al enviar el correo: ' . $e->getMessage();
    }

    return 'Email sent at ' . now();
});
