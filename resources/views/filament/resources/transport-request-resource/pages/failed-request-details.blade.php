<div class="space-y-6 p-4">
    <div class="bg-danger-50 border border-danger-200 rounded-lg p-4 mb-4">
        <h3 class="text-lg font-medium text-danger-800 mb-2">Motivo por el que no se realizó</h3>
        <p class="text-danger-700">{{ $motivo }}</p>
    </div>

    <div class="space-y-4">
        <h3 class="text-lg font-medium">Detalles de la solicitud</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">ID</p>
                <p class="font-medium">{{ $record->id }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Fecha</p>
                <p class="font-medium">{{ $record->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Solicitante</p>
                <p class="font-medium">{{ $record->requester->name ?? 'No disponible' }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Material</p>
                <p class="font-medium">{{ $record->material_description }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Origen</p>
                <p class="font-medium">{{ MaterialRequestTransport::LOCATIONS[$record->pickup_location] ?? $record->pickup_location }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Destino</p>
                <p class="font-medium">{{ MaterialRequestTransport::LOCATIONS[$record->delivery_location] ?? $record->delivery_location }}</p>
            </div>
        </div>
    </div>

    @if($imageUrl)
    <div class="space-y-2">
        <h3 class="text-lg font-medium">Evidencia fotográfica</h3>
        <div class="border rounded-lg overflow-hidden">
            <img src="{{ $imageUrl }}" alt="Evidencia de fallo" class="w-full object-contain max-h-80">
        </div>
    </div>
    @endif
</div>
