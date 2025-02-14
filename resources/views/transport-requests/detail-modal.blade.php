@php
    use App\Models\MaterialRequest;
    use Carbon\Carbon;
@endphp

<div class="space-y-6 p-4">
    {{-- Encabezado con información principal --}}
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-6 h-6 text-primary-600"/>
                    Solicitud #{{ $request->id }}
                </h2>
                <p class="text-gray-600 flex items-center gap-2 mt-2">
                    <x-heroicon-o-calendar class="w-5 h-5"/>
                    {{ Carbon::parse($request->created_at)->format('d/m/Y H:i') }}
                </p>
            </div>
            <div class="space-y-2">
                <p class="flex items-center gap-2">
                    <x-heroicon-o-user class="w-5 h-5 text-gray-600"/>
                    <span class="font-semibold">Solicitante:</span>
                    <span class="text-gray-700">{{ $request->requester->name ?? 'N/A' }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <x-heroicon-o-cube class="w-5 h-5 text-gray-600"/>
                    <span class="font-semibold">Categoría:</span>
                    <span class="text-gray-700">{{ $request->materialCategory->name ?? 'N/A' }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <x-heroicon-o-building-office class="w-5 h-5 text-gray-600"/>
                    <span class="font-semibold">Área de Origen:</span>
                    <span class="text-gray-700">{{ $request->originArea->name ?? 'N/A' }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Estado Actual --}}
    <div class="bg-white rounded-lg p-4 border border-gray-200">
        <h3 class="font-bold text-lg mb-3 flex items-center gap-2 text-gray-900">
            <x-heroicon-o-check-circle class="w-6 h-6 text-primary-600"/>
            Estado Actual
        </h3>
        <div class="space-y-3">
            <span class="px-3 py-1 rounded-full text-sm font-medium
                {{ match($request->current_status) {
                    MaterialRequest::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
                    MaterialRequest::STATUS_ACCEPTED => 'bg-blue-100 text-blue-800',
                    MaterialRequest::STATUS_RESCHEDULED => 'bg-orange-100 text-orange-800',
                    MaterialRequest::STATUS_COMPLETED => 'bg-green-100 text-green-800',
                    MaterialRequest::STATUS_FAILED => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800'
                } }}">
                {{ MaterialRequest::getStatuses()[$request->current_status] ?? 'Desconocido' }}
            </span>

            @if($request->current_status === MaterialRequest::STATUS_RESCHEDULED)
                <div class="mt-3 space-y-2">
                    <div class="flex items-center gap-2 text-gray-700">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-500"/>
                        <span class="font-medium">Nueva Fecha y Hora:</span>
                        <span>{{ Carbon::parse($request->rescheduled_date)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex items-start gap-2 text-gray-700">
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 text-gray-500 mt-1"/>
                        <div>
                            <span class="font-medium">Motivo:</span>
                            <p class="mt-1">{{ $request->reschedule_comments }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Información de Material --}}
    <div class="bg-white rounded-lg p-4 border border-gray-200">
        <h3 class="font-bold text-lg mb-3 flex items-center gap-2 text-gray-900">
            <x-heroicon-o-cube class="w-6 h-6 text-primary-600"/>
            Detalles del Material
        </h3>
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-700">Descripción:</h4>
                <p class="mt-1 text-gray-600">{{ $request->material_description }}</p>
            </div>
            @if($request->comments)
            <div>
                <h4 class="font-medium text-gray-700">Comentarios Adicionales:</h4>
                <p class="mt-1 text-gray-600">{{ $request->comments }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Grid de Recogida y Entrega --}}
    <div class="grid md:grid-cols-2 gap-4">
        {{-- Información de Recogida --}}
        <div class="bg-white rounded-lg p-4 border border-gray-200">
            <h3 class="font-bold text-lg mb-3 flex items-center gap-2 text-gray-900">
                <x-heroicon-o-map-pin class="w-6 h-6 text-primary-600"/>
                Información de Recogida
            </h3>
            <div class="space-y-3 text-gray-700">
                <p class="flex items-center gap-2">
                    <x-heroicon-o-home class="w-5 h-5 text-gray-500"/>
                    <span class="font-medium">Dirección:</span>
                    <span class="text-gray-600">{{ $request->pickup_address }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <x-heroicon-o-user class="w-5 h-5 text-gray-500"/>
                    <span class="font-medium">Contacto:</span>
                    <span class="text-gray-600">{{ $request->pickup_contact }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <x-heroicon-o-phone class="w-5 h-5 text-gray-500"/>
                    <span class="font-medium">Teléfono:</span>
                    <span class="text-gray-600">{{ $request->pickup_phone }}</span>
                </p>
            </div>
        </div>

        {{-- Información de Entrega --}}
        <div class="bg-white rounded-lg p-4 border border-gray-200">
            <h3 class="font-bold text-lg mb-3 flex items-center gap-2 text-gray-900">
                <x-heroicon-o-flag class="w-6 h-6 text-primary-600"/>
                Información de Entrega
            </h3>
            <div class="space-y-3 text-gray-700">
                <p class="flex items-center gap-2">
                    <x-heroicon-o-home class="w-5 h-5 text-gray-500"/>
                    <span class="font-medium">Dirección:</span>
                    <span class="text-gray-600">{{ $request->delivery_address }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <x-heroicon-o-user class="w-5 h-5 text-gray-500"/>
                    <span class="font-medium">Contacto:</span>
                    <span class="text-gray-600">{{ $request->delivery_contact }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <x-heroicon-o-phone class="w-5 h-5 text-gray-500"/>
                    <span class="font-medium">Teléfono:</span>
                    <span class="text-gray-600">{{ $request->delivery_phone }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Información del Transporte --}}
    @if($request->currentTransporter)
    <div class="bg-white rounded-lg p-4 border border-gray-200">
        <h3 class="font-bold text-lg mb-3 flex items-center gap-2 text-gray-900">
            <x-heroicon-o-truck class="w-6 h-6 text-primary-600"/>
            Información del Transporte
        </h3>
        <div class="space-y-3 text-gray-700">
            <p class="flex items-center gap-2">
                <x-heroicon-o-user class="w-5 h-5 text-gray-500"/>
                <span class="font-medium">Transportista:</span>
                <span class="text-gray-600">{{ $request->currentTransporter->transporter->name ?? 'N/A' }}</span>
            </p>
            <p class="flex items-center gap-2">
                <x-heroicon-o-calendar class="w-5 h-5 text-gray-500"/>
                <span class="font-medium">Fecha de Asignación:</span>
                <span class="text-gray-600">{{ Carbon::parse($request->currentTransporter->assignment_date)->format('d/m/Y H:i') }}</span>
            </p>

            @if($request->current_status === MaterialRequest::STATUS_FAILED)
                {{-- Sección de Evidencia y Motivo --}}
                <div class="mt-4 space-y-4">
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <h4 class="font-semibold text-red-800 flex items-center gap-2 mb-2">
                            <x-heroicon-o-exclamation-circle class="w-5 h-5"/>
                            Motivo de No Realización
                        </h4>
                        <p class="text-red-700 ml-7">{{ $request->comments }}</p>
                    </div>

                    @if($request->evidence_image)
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h4 class="font-semibold text-gray-800 flex items-center gap-2 mb-3">
                                <x-heroicon-o-camera class="w-5 h-5"/>
                                Evidencia Fotográfica
                            </h4>
                            <div class="mt-2">
                                <img src="{{ Storage::url($request->evidence_image) }}"
                                     alt="Evidencia fotográfica"
                                     class="rounded-lg max-h-64 mx-auto shadow-lg">
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($request->currentTransporter->comments && $request->current_status !== MaterialRequest::STATUS_RESCHEDULED)
                <div class="mt-3 bg-gray-50 p-3 rounded-lg">
                    <p class="flex items-center gap-2">
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 text-gray-500"/>
                        <span class="font-semibold">Comentarios:</span>
                    </p>
                    <p class="mt-1 ml-7">{{ $request->currentTransporter->comments }}</p>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
