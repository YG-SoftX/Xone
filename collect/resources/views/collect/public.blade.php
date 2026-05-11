<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form->title }} — YG Collect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; font-family: 'Inter', system-ui, sans-serif; }
        .brand-border { border-top: 8px solid #9333ea; }
    </style>
</head>
<body class="py-12 px-4 sm:px-6">

    <div class="max-w-2xl mx-auto">
        
        @if(session('success'))
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center mb-6 brand-border">
                <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-6 text-purple-600 text-2xl">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="text-2xl font-normal text-gray-900 mb-2">Response Submitted</h2>
                <p class="text-gray-500 mb-8">{{ session('success') }}</p>
                <a href="" class="text-sm font-bold text-purple-600 hover:underline">Submit another response</a>
            </div>
        @else

            <form action="{{ route('collect.submit', $form->slug) }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 brand-border">
                    <h1 class="text-3xl font-normal text-gray-900 mb-2">{{ $form->title }}</h1>
                    @if($form->description)
                        <p class="text-gray-600 text-sm leading-relaxed">{{ $form->description }}</p>
                    @endif
                    
                    @if($form->show_branding)
                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
                            <i class="fas fa-shield-alt text-purple-500"></i> Sovereign Data Collection
                        </div>
                    @endif
                </div>

                @foreach($form->fields as $index => $field)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                        <label class="block text-base font-bold text-gray-900 mb-4">
                            {{ $field['label'] }}
                            @if($field['required']) <span class="text-red-500 ml-1">*</span> @endif
                        </label>

                        @if($field['type'] === 'text')
                            <input type="text" name="field_{{ $index }}" {{ $field['required'] ? 'required' : '' }}
                                class="w-full px-4 py-3 border-b border-gray-300 focus:border-purple-600 outline-none transition-colors bg-transparent text-gray-900" 
                                placeholder="Your answer">
                        
                        @elseif($field['type'] === 'textarea')
                            <textarea name="field_{{ $index }}" rows="3" {{ $field['required'] ? 'required' : '' }}
                                class="w-full px-4 py-3 border-b border-gray-300 focus:border-purple-600 outline-none transition-colors bg-transparent text-gray-900 resize-none" 
                                placeholder="Your answer"></textarea>

                        @elseif($field['type'] === 'radio')
                            <div class="space-y-3">
                                @foreach($field['options'] as $optIndex => $option)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="radio" name="field_{{ $index }}" value="{{ $option }}" {{ $field['required'] && $optIndex === 0 ? 'required' : '' }}
                                            class="w-5 h-5 text-purple-600 border-gray-300 focus:ring-purple-500">
                                        <span class="text-gray-700 group-hover:text-gray-900">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>

                        @elseif($field['type'] === 'checkbox')
                            <div class="space-y-3">
                                @foreach($field['options'] as $optIndex => $option)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" name="field_{{ $index }}[]" value="{{ $option }}"
                                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                        <span class="text-gray-700 group-hover:text-gray-900">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>

                        @elseif($field['type'] === 'select')
                            <select name="field_{{ $index }}" {{ $field['required'] ? 'required' : '' }}
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                <option value="">Choose...</option>
                                @foreach($field['options'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>

                        @elseif($field['type'] === 'date')
                            <input type="date" name="field_{{ $index }}" {{ $field['required'] ? 'required' : '' }}
                                class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-gray-900">
                        @endif
                    </div>
                @endforeach

                <div class="flex items-center justify-between pt-4">
                    <button type="submit" class="px-8 py-3 bg-purple-600 text-white rounded-full text-sm font-bold uppercase tracking-widest shadow-md hover:bg-purple-700 transition-all">
                        Submit
                    </button>
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Never submit passwords</span>
                </div>
            </form>
            
        @endif

        @if($form->show_branding)
            <div class="mt-12 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                Powered by <span class="text-purple-600">YG Collect</span>
            </div>
        @endif

    </div>
</body>
</html>
