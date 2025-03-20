<?php

namespace App\Exports;

use App\Models\MaterialRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TransportRequestsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $records;

    public function __construct($records = null)
    {
        $this->records = $records;
    }

    public function collection()
    {
        if ($this->records) {
            return $this->records;
        }

        return MaterialRequest::with([
            'requester',
            'materialCategory',
            'origin',
            'currentTransporter.transporter'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha de Solicitud',
            'Solicitante',
            'Categoría',
            'Área de Origen',
            'Estado',
            'Transportista',
            'Fecha de Asignación',
            'Dirección de Recogida',
            'Ubicación de Recogida',
            'Contacto Recogida',
            'Teléfono Recogida',
            'Dirección de Entrega',
            'Ubicación de Entrega',
            'Contacto Entrega',
            'Teléfono Entrega',
            'Descripción del Material',
            'Comentarios',
            'Motivo de Reprogramación',
            'Nueva Fecha (Reprogramación)',
            'Tiempo de Servicio (Horas)'
        ];
    }

    public function map($request): array
    {
        $serviceTime = null;
        if ($request->current_status === MaterialRequest::STATUS_COMPLETED && $request->currentTransporter) {
            $assignmentDate = Carbon::parse($request->currentTransporter->assignment_date);
            $completionDate = Carbon::parse($request->updated_at);
            $serviceTime = $assignmentDate->diffInHours($completionDate);
        }

        $pickupLocation = isset($request->pickup_location) && isset(MaterialRequest::LOCATIONS[$request->pickup_location])
            ? MaterialRequest::LOCATIONS[$request->pickup_location]
            : 'No especificado';

        $deliveryLocation = isset($request->delivery_location) && isset(MaterialRequest::LOCATIONS[$request->delivery_location])
            ? MaterialRequest::LOCATIONS[$request->delivery_location]
            : 'No especificado';

        return [
            $request->id,
            Carbon::parse($request->created_at)->format('d/m/Y H:i'),
            $request->requester->name ?? 'N/A',
            $request->materialCategory->name ?? 'N/A',
            $request->origin->name ?? 'N/A',
            MaterialRequest::getStatuses()[$request->current_status] ?? $request->current_status,
            $request->currentTransporter->transporter->name ?? 'No asignado',
            $request->currentTransporter ? Carbon::parse($request->currentTransporter->assignment_date)->format('d/m/Y H:i') : 'N/A',
            $request->pickup_address,
            $pickupLocation,
            $request->pickup_contact,
            $request->pickup_phone,
            $request->delivery_address,
            $deliveryLocation,
            $request->delivery_contact,
            $request->delivery_phone,
            $request->material_description,
            $request->comments,
            $request->reschedule_comments ?? 'N/A',
            $request->rescheduled_date ? Carbon::parse($request->rescheduled_date)->format('d/m/Y H:i') : 'N/A',
            $serviceTime ?? 'N/A'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
