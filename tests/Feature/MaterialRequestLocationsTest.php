<?php

use App\Models\MaterialRequest;

it('defines material request locations with full labels and expected order', function (): void {
    expect(MaterialRequest::getLocations())->toBe([
        'surco' => 'Sede Surco - Av. el polo 461',
        'san_isidro' => 'Medicentro San Isidro - Av. Paseo de la república 3058',
        'alto_caral' => 'Alto Caral - Av. Deonisio Derteano 150',
        'lima_ambulatoria' => 'Ambulatorio Lima - Av Garcilazo 1420',
        'lima_hospitalaria' => 'Hospitalario Lima - Av. Washington 1470',
        'san_borja_ambulatoria' => 'Ambulatorio SSB - Av. Guardia civil 421',
        'san_borja_hospitalaria' => 'Hospitalario SSB - Av. Guardia Civil 43',
        'plata_malpartida_ssb' => 'Plata Malpartida SSB - Av. Guardia civil 373',
        'playa_malpartida_343' => 'Playa malpartida 343',
        'torre_hospitalaria_385' => 'Torre Hospitalaria 385',
        'torre_ambulatoria_421' => 'Torre Ambulatoria 421',
    ]);
});
