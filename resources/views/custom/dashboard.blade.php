<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center p-8">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full">
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-4">
                ¡Bienvenido al Sistema de Gestión de Transportes!
            </h1>

            <div class="text-center space-y-2 mt-6">
                <p class="text-xl text-gray-700">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-gray-500">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
