@php
    use chillerlan\QRCode\QRCode;
    use chillerlan\QRCode\QROptions;

    $url = route('forms.public.show', $record->id);
    
    $options = new QROptions([
        'version'    => 5,
        'outputType' => QRCode::OUTPUT_MARKUP_SVG,
        'eccLevel'   => QRCode::ECC_L,
        'svgViewBoxSize' => 512,
        'addQuietzone' => true,
    ]);

    $qrcode = (new QRCode($options))->render($url);
@endphp

<div class="flex flex-col items-center justify-center space-y-4 p-6">
    <div class="p-4 bg-white rounded-2xl shadow-inner">
        {!! $qrcode !!}
    </div>
    
    <div class="text-center">
        <p class="text-sm text-slate-500 mb-2">Scan this QR code to open the form</p>
        <code class="text-xs bg-slate-100 p-2 rounded block break-all text-blue-600 font-mono">
            {{ $url }}
        </code>
    </div>

    <div class="flex space-x-2">
        <x-filament::button 
            color="info" 
            icon="heroicon-m-clipboard" 
            onclick="navigator.clipboard.writeText('{{ $url }}'); $wire.notify('success', 'URL copied to clipboard!')">
            Copy URL
        </x-filament::button>
    </div>
</div>

<style>
    svg {
        width: 256px;
        height: 256px;
    }
</style>
