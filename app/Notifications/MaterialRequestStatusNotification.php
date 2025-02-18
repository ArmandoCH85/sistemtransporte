<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\MaterialRequest;

class MaterialRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MaterialRequest $materialRequest
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $status = $this->getStatusBadge();

        return (new MailMessage)
            ->subject("Actualización de Solicitud #{$this->materialRequest->id}")
            ->greeting("Hola {$notifiable->name}")
            ->line("Tu solicitud de transporte ha sido actualizada.")
            ->line("Estado actual: {$status}")
            ->line("Descripción del material: {$this->materialRequest->material_description}")
            ->line($this->getStatusMessage())
            ->action('Ver Solicitud', url('/admin/requests/'.$this->materialRequest->id));
    }

    protected function getStatusBadge(): string
    {
        return match ($this->materialRequest->current_status) {
            MaterialRequest::STATUS_PENDING => '⚠️ Pendiente',
            MaterialRequest::STATUS_ACCEPTED => '🔄 En Proceso',
            MaterialRequest::STATUS_RESCHEDULED => '📅 Reprogramado',
            MaterialRequest::STATUS_COMPLETED => '✅ Finalizado',
            MaterialRequest::STATUS_FAILED => '❌ Fallido',
            default => '❓ Desconocido',
        };
    }

    protected function getStatusMessage(): string
    {
        return match ($this->materialRequest->current_status) {
            MaterialRequest::STATUS_PENDING => 'Tu solicitud está pendiente de asignación a un transportista.',
            MaterialRequest::STATUS_ACCEPTED => 'Un transportista ha aceptado tu solicitud y está en proceso.',
            MaterialRequest::STATUS_RESCHEDULED => 'Tu solicitud ha sido reprogramada por el transportista.',
            MaterialRequest::STATUS_COMPLETED => 'Tu solicitud ha sido completada exitosamente.',
            MaterialRequest::STATUS_FAILED => 'Tu solicitud no pudo ser completada.',
            default => 'Estado de la solicitud desconocido.',
        };
    }
}
