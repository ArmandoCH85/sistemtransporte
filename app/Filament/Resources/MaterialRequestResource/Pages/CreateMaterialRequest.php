<?php

namespace App\Filament\Resources\MaterialRequestResource\Pages;

use App\Filament\Resources\MaterialRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class CreateMaterialRequest extends CreateRecord
{
    protected static string $resource = MaterialRequestResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $solicitud = $this->record;

        // 1. Obtener todos los usuarios con rol 'transportista'
        $transportistas = User::role('transportista')->get();

        // 2. Obtener todos los usuarios con rol 'super_admin'
        $superAdmins = User::role('super_admin')->get();

        // 3. Enviar correo a cada transportista
        foreach ($transportistas as $transportista) {
            $details = [
                'title' => 'Nueva Solicitud de Material',
                'content' => '
                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                    <h2 style="color: #2d3748; margin-bottom: 20px;">🚚 Nueva Solicitud #'.$solicitud->id.'</h2>

                    <p style="color: #4a5568; margin-bottom: 20px;">
                        Se ha registrado una nueva solicitud que requiere su atención. Por favor, revise los detalles y confirme o rechace la solicitud.
                    </p>

                    <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="color: #4a5568; margin-bottom: 10px;">
                            <i>📅 Fecha y Hora:</i><br>
                            <span style="margin-left: 20px;">'.$solicitud->created_at->format('d/m/Y H:i').'</span>
                        </p>

                        <div style="margin-top: 20px;">
                            <h3 style="color: #2d3748; margin-bottom: 10px;">📍 Dirección de Recogida</h3>
                            <p style="margin-left: 20px; color: #4a5568;">
                                '.$solicitud->pickup_address.'<br>
                                <strong>Contacto:</strong> '.$solicitud->pickup_contact.'
                                <strong>Telefono:</strong> '.$solicitud->pickup_phone.'
                            </p>
                        </div>

                        <div style="margin-top: 20px;">
                            <h3 style="color: #2d3748; margin-bottom: 10px;">🏁 Dirección de Entrega</h3>
                            <p style="margin-left: 20px; color: #4a5568;">
                                '.$solicitud->delivery_address.'<br>
                                <strong>Contacto:</strong> '.$solicitud->delivery_contact.'
                                <strong>Telefono:</strong> '.$solicitud->delivery_phone.'
                            </p>
                        </div>

                        <div style="margin-top: 20px;">
                            <h3 style="color: #2d3748; margin-bottom: 10px;">📦 Descripción del Material</h3>
                            <p style="margin-left: 20px; color: #4a5568; background-color: #f7fafc; padding: 10px; border-radius: 5px;">
                                '.$solicitud->material_description.'
                            </p>
                        </div>
                    </div>

                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                        Este es un correo automático, por favor no responder directamente.
                    </p>
                </div>
                ',
            ];

            Mail::send('emails.test', ['data' => $details], function($message) use ($transportista, $details, $solicitud) {
                $message->to($transportista->email)
                        ->subject("Nueva Solicitud #{$solicitud->id} - Requiere su atención");
            });
        }

        // 4. Enviar correo a cada super_admin
        foreach ($superAdmins as $admin) {
            $details = [
                'title' => 'Nueva Solicitud de Material Registrada',
                'content' => '
                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
                    <h2 style="color: #2d3748; margin-bottom: 20px;">👥 Supervisión de Nueva Solicitud #'.$solicitud->id.'</h2>

                    <p style="color: #4a5568; margin-bottom: 20px;">
                        Se ha registrado una nueva solicitud en el sistema que requiere supervisión.
                    </p>

                    <div style="background-color: white; padding: 20px; border-radius: 8px;">
                        <p style="color: #4a5568; margin-bottom: 10px;">
                            <i>📅 Fecha y Hora:</i><br>
                            <span style="margin-left: 20px;">'.$solicitud->created_at->format('d/m/Y H:i').'</span>
                        </p>

                        <div style="margin-top: 20px;">
                            <h3 style="color: #2d3748; margin-bottom: 10px;">📍 Detalles de la Solicitud</h3>
                            <p style="margin-left: 20px; color: #4a5568;">
                                <strong>Origen:</strong> '.$solicitud->pickup_address.'<br>
                                <strong>Destino:</strong> '.$solicitud->delivery_address.'<br>
                                <strong>Material:</strong> '.$solicitud->material_description.'
                            </p>
                        </div>
                    </div>

                    <p style="color: #718096; font-size: 0.9em; text-align: center; margin-top: 20px;">
                        Este es un correo automático, por favor no responder directamente.
                    </p>
                </div>
                ',
            ];

            Mail::send('emails.test', ['data' => $details], function($message) use ($admin, $details, $solicitud) {
                $message->to($admin->email)
                        ->subject("Nueva Solicitud #{$solicitud->id} - Supervisión Requerida");
            });
        }

        // 5. Mostrar notificación al usuario que creó la solicitud
        Notification::make()
            ->success()
            ->title('¡Solicitud Registrada!')
            ->body('Su solicitud se ha registrado con éxito. Estaremos en contacto pronto.')
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('entendido')
                    ->label('Entendido')
                    ->button()
                    ->color('primary')
                    ->close()
                    ->action(fn () => $this->redirect(MaterialRequestResource::getUrl('index')))
            ])
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['fields_completed'] = true;
        return $data;
    }
}


