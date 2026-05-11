@extends('admin.master-layout')

@section('title', 'Theme & Brand Customization')

@section('content')
<div class="space-y-6">
    <!-- Service Tabs -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Theme & Brand Customization</h3>
                <div class="flex gap-2">
                    @foreach(['account' => 'YG Account', 'mail' => 'YG Mail', 'docx' => 'YG DocX', 'xcel' => 'YG Xcel'] as $key => $label)
                    <a href="{{ route('admin.theme.index', ['service' => $key]) }}" 
                        class="px-3 py-1.5 text-sm rounded-lg {{ request('service', 'account') === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('theme.update', $theme->id ?? 0) }}" class="p-6 space-y-8">
            @csrf

            <!-- Color Palette -->
            <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    Color Palette (Google-style light theme)
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    @php
                        $colors = $theme->colors ?? [];
                        $defaultColors = [
                            'primary' => '#4285F4',
                            'secondary' => '#34A853',
                            'background' => '#FFFFFF',
                            'surface' => '#F8F9FA',
                            'text' => '#202124',
                            'accent' => '#EA4335'
                        ];
                    @endphp
                    @foreach(['primary' => 'Primary (Buttons/Links)', 'secondary' => 'Secondary (Success)', 'background' => 'Background', 'surface' => 'Surface (Cards)', 'text' => 'Text Color', 'accent' => 'Accent (Errors)'] as $key => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="colors[{{ $key }}]" value="{{ $colors[$key] ?? $defaultColors[$key] }}" 
                                class="w-10 h-10 border rounded cursor-pointer">
                            <input type="text" name="colors_text[{{ $key }}]" value="{{ $colors[$key] ?? $defaultColors[$key] }}" 
                                class="flex-1 text-sm border rounded px-2 py-1.5 font-mono">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Typography -->
            <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"></path></svg>
                    Typography
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Heading Font</label>
                        <select name="fonts[heading_font]" class="w-full border rounded-lg px-3 py-2">
                            @foreach(['Google Sans', 'Roboto', 'Inter', 'Poppins', 'Open Sans', 'Lato', 'System Default'] as $font)
                            <option value="{{ $font }}" {{ ($theme->fonts['heading_font'] ?? 'Google Sans') === $font ? 'selected' : '' }}>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Body Font</label>
                        <select name="fonts[body_font]" class="w-full border rounded-lg px-3 py-2">
                            @foreach(['Roboto', 'Google Sans', 'Inter', 'Open Sans', 'Lato', 'System Default'] as $font)
                            <option value="{{ $font }}" {{ ($theme->fonts['body_font'] ?? 'Roboto') === $font ? 'selected' : '' }}>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Logos & Branding -->
            <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Logos & Branding
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach(['favicon' => 'Favicon (32x32)', 'header_logo' => 'Header Logo (Light BG)', 'header_logo_dark' => 'Header Logo (Dark BG)', 'app_icon' => 'App Icon (512x512)'] as $key => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        @if(!empty($theme->logos[$key]))
                            <img src="{{ $theme->logos[$key] }}" alt="{{ $label }}" class="h-10 mb-2 border rounded">
                        @endif
                        <input type="text" name="logos[{{ $key }}]" value="{{ $theme->logos[$key] ?? '' }}" 
                            placeholder="https://example.com/logo.png" 
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        <p class="text-xs text-gray-500 mt-1">Enter image URL or upload via media manager</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Design Settings -->
            <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4">Design Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Border Radius (px)</label>
                        <input type="number" name="settings[border_radius]" value="{{ $theme->settings['border_radius'] ?? 8 }}" 
                            min="0" max="32" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Theme Name</label>
                        <input type="text" name="name" value="{{ $theme->name ?? 'Custom' }}" 
                            class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ $theme->is_active ?? false ? 'checked' : '' }} 
                            class="rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">Set as active theme for this service</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-4 border-t">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    Save Theme
                </button>
                @if($theme->id)
                <a href="{{ route('theme.preview', $theme->id) }}" target="_blank" class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Preview
                </a>
                <button type="button" onclick="if(confirm('Reset to defaults?')){document.getElementById('reset-form-{{ $theme->id }}').submit()}" 
                    class="px-4 py-2.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 font-medium">
                    Reset to Defaults
                </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Quick Presets -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Presets (Google-style)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="applyPreset('google-light')" class="border rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition-all text-left">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-blue-500"></div>
                    <span class="font-medium">Google Light</span>
                </div>
                <p class="text-xs text-gray-500">Clean white background, blue primary, Google Sans font</p>
            </button>
            <button onclick="applyPreset('material')" class="border rounded-lg p-4 hover:border-purple-500 hover:shadow-md transition-all text-left">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-purple-600"></div>
                    <span class="font-medium">Material Design</span>
                </div>
                <p class="text-xs text-gray-500">Purple primary, rounded cards, Roboto font, subtle shadows</p>
            </button>
            <button onclick="applyPreset('minimal')" class="border rounded-lg p-4 hover:border-gray-500 hover:shadow-md transition-all text-left">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-gray-700"></div>
                    <span class="font-medium">Minimal</span>
                </div>
                <p class="text-xs text-gray-500">No shadows, sharp corners, Inter font, high contrast</p>
            </button>
        </div>
    </div>
</div>

<script>
function applyPreset(preset) {
    const presets = {
        'google-light': { colors: { primary: '#4285F4', secondary: '#34A853', background: '#FFFFFF', surface: '#F8F9FA', text: '#202124', accent: '#EA4335' }, fonts: { heading_font: 'Google Sans', body_font: 'Roboto' } },
        'material': { colors: { primary: '#6200EE', secondary: '#03DAC6', background: '#FAFAFA', surface: '#FFFFFF', text: '#000000', accent: '#B00020' }, fonts: { heading_font: 'Roboto', body_font: 'Roboto' } },
        'minimal': { colors: { primary: '#1A73E8', secondary: '#5F6368', background: '#FFFFFF', surface: '#FFFFFF', text: '#202124', accent: '#D93025' }, fonts: { heading_font: 'Inter', body_font: 'Inter' } }
    };
    const p = presets[preset];
    if (!p) return;
    Object.keys(p.colors).forEach(k => {
        const colorInput = document.querySelector(`input[name="colors[${k}]"]`);
        const textInput = document.querySelector(`input[name="colors_text[${k}]"]`);
        if (colorInput) colorInput.value = p.colors[k];
        if (textInput) textInput.value = p.colors[k];
    });
    if (p.fonts.heading_font) document.querySelector('select[name="fonts[heading_font]"]').value = p.fonts.heading_font;
    if (p.fonts.body_font) document.querySelector('select[name="fonts[body_font]"]').value = p.fonts.body_font;
}
</script>

<form id="reset-form-{{ $theme->id ?? 0 }}" method="POST" action="{{ route('theme.reset', $theme->id ?? 0) }}" class="hidden">@csrf</form>
@endsection
