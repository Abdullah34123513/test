<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Deployment Log
        </x-slot>
        
        <x-slot name="description">
            Output from the deployment script.
        </x-slot>

        <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-sm overflow-auto h-96 whitespace-pre-wrap">
            @if($output)
                {{ $output }}
            @else
                <span class="text-gray-500">Ready to deploy. Click the button above to start.</span>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
