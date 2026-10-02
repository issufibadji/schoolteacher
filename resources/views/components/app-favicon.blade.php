{{-- Favicon do sistema (config app_favicon, campo Mídia). Sem mídia, o /favicon.ico padrão. --}}
<link rel="icon" href="{{ config_app_media('app_favicon') ?? asset('favicon.ico') }}">
