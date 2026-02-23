<?php

namespace App\Exports;

use App\Models\MaterialRequest;
use App\Models\TransporterWorkLog;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransportRequestsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $records;

    protected array $workLogCache = [];

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
            'currentTransporter.transporter',
        ])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha de Solicitud',
            'Solicitante',
            'Categoria',
            'Area de Origen',
            'Estado',
            'Transportista',
            'Fecha de Asignacion',
            'Inicio Jornada',
            'Fin Jornada',
            'Direccion de Recogida',
            'Ubicacion de Recogida',
            'Contacto Recogida',
            'Telefono Recogida',
            'Direccion de Entrega',
            'Ubicacion de Entrega',
            'Contacto Entrega',
            'Telefono Entrega',
            'Descripcion del Material',
            'Comentarios',
            'Observacion Transportista',
            'Motivo de Reprogramacion',
            'Nueva Fecha (Reprogramacion)',
            'Fecha de Finalizacion',
            'Tiempo de Servicio (Horas)',
        ];
    }

    public function map($request): array
    {
        $serviceTime = null;
        $completionDateFormatted = 'N/A';
        $assignmentDateFormatted = 'N/A';
        $workdayStart = 'Sin cierre';
        $workdayEnd = 'Sin cierre';
        $transporterObservation = 'Sin observacion';

        if ($request->current_status === MaterialRequest::STATUS_COMPLETED) {
            $completionDateFormatted = Carbon::parse($request->updated_at)->format('d/m/Y H:i');
        }

        if ($request->currentTransporter) {
            $assignmentDateFormatted = Carbon::parse($request->currentTransporter->assignment_date)->format('d/m/Y H:i');
            $transporterObservation = filled($request->currentTransporter->comments)
                ? $request->currentTransporter->comments
                : 'Sin observacion';

            $workLog = $this->resolveWorkLogForRequest($request);

            if ($workLog) {
                $workdayStart = Carbon::parse($workLog->started_at)->format('d/m/Y H:i');
                $workdayEnd = Carbon::parse($workLog->ended_at)->format('d/m/Y H:i');
            }
        }

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
            $assignmentDateFormatted,
            $workdayStart,
            $workdayEnd,
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
            $transporterObservation,
            $request->reschedule_comments ?? 'N/A',
            $request->rescheduled_date ? Carbon::parse($request->rescheduled_date)->format('d/m/Y H:i') : 'N/A',
            $completionDateFormatted,
            $serviceTime ?? 'N/A',
        ];
    }

    protected function resolveWorkLog(?int $transporterId, $assignmentDate): ?TransporterWorkLog
    {
        if (! $transporterId || ! $assignmentDate) {
            return null;
        }

        $workDate = Carbon::parse($assignmentDate)->toDateString();
        $cacheKey = $transporterId . '|' . $workDate;

        if (! array_key_exists($cacheKey, $this->workLogCache)) {
            $this->workLogCache[$cacheKey] = TransporterWorkLog::query()
                ->where('transporter_id', $transporterId)
                ->whereDate('work_date', $workDate)
                ->first();
        }

        return $this->workLogCache[$cacheKey];
    }

    protected function resolveWorkLogForRequest($request): ?TransporterWorkLog
    {
        if (! $request->currentTransporter || ! $request->currentTransporter->transporter_id) {
            return null;
        }

        $transporterId = (int) $request->currentTransporter->transporter_id;
        $candidateDates = [];

        if (
            in_array($request->current_status, [MaterialRequest::STATUS_COMPLETED, MaterialRequest::STATUS_FAILED], true)
            && $request->updated_at
        ) {
            $candidateDates[] = Carbon::parse($request->updated_at);
        }

        if ($request->currentTransporter->assignment_date) {
            $assignmentDate = Carbon::parse($request->currentTransporter->assignment_date);
            $candidateDates[] = $assignmentDate;
            $candidateDates[] = $assignmentDate->copy()->addDay();
        }

        $seenDates = [];
        foreach ($candidateDates as $date) {
            $dateKey = $date->toDateString();
            if (isset($seenDates[$dateKey])) {
                continue;
            }

            $seenDates[$dateKey] = true;
            $workLog = $this->resolveWorkLog($transporterId, $date);

            if ($workLog) {
                return $workLog;
            }
        }

        return null;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
