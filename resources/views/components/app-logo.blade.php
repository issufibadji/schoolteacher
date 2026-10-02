{{-- Logo do sistema (config app_logo, campo Mídia). Sem logo, o ícone padrão. Vai dentro da caixinha da marca. --}}
@props(['iconClass' => 'w-5 h-5 text-white'])

@if ($logo = config_app_media('app_logo'))
    <img src="{{ $logo }}" alt="{{ config('app.name') }}" class="w-full h-full object-contain p-1">
@else
    <x-heroicon-s-sparkles class="{{ $iconClass }}" />
@endif
