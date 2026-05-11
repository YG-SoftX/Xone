@extends('layouts.app')
@section('title', 'YG Collect — Form Builder')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-10" x-data="formBuilder()">
    
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('collect.index') }}" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all text-gray-500">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-[26px] font-normal text-gray-900 tracking-tight">Form Builder</h1>
                <p class="text-xs text-gray-500 uppercase tracking-widest font-bold">Drafting New Form</p>
            </div>
        </div>
        <button @click="submitForm" class="px-8 py-2.5 bg-brand text-white rounded-full text-xs font-bold uppercase tracking-widest shadow-md hover:bg-opacity-90 transition-all flex items-center gap-2">
            <i class="fas fa-save text-[10px]"></i> Save Form
        </button>
    </div>

    <!-- Hidden form for actual submission -->
    <form id="builder-form" action="{{ route('collect.store') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="title" x-model="title">
        <input type="hidden" name="description" x-model="description">
        <input type="hidden" name="is_public" value="1">
        <input type="hidden" name="show_branding" value="1">
        <input type="hidden" name="fields" id="fields-input">
    </form>

    <div class="google-card border-brand/20 mb-6 border-t-8 border-t-brand rounded-t-3xl">
        <input type="text" x-model="title" placeholder="Form Title" 
            class="w-full text-3xl font-normal text-gray-900 border-none outline-none focus:ring-0 p-0 mb-4 bg-transparent placeholder-gray-300">
        <input type="text" x-model="description" placeholder="Form Description" 
            class="w-full text-sm text-gray-600 border-none outline-none focus:ring-0 p-0 bg-transparent placeholder-gray-300">
    </div>

    <!-- Fields Container -->
    <div class="space-y-4" id="fields-container">
        <template x-for="(field, index) in fields" :key="index">
            <div class="google-card group relative">
                
                <!-- Field Drag Handle & Delete -->
                <div class="absolute -left-12 top-1/2 -translate-y-1/2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="w-8 h-8 bg-white rounded-full shadow-sm flex items-center justify-center text-gray-400 cursor-move hover:text-gray-900">
                        <i class="fas fa-grip-vertical"></i>
                    </button>
                    <button @click="removeField(index)" class="w-8 h-8 bg-white rounded-full shadow-sm flex items-center justify-center text-red-400 hover:text-red-600">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>

                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-8">
                        <input type="text" x-model="field.label" placeholder="Question" 
                            class="w-full px-4 py-3 bg-gray-50 border border-transparent rounded-xl text-base font-bold text-gray-900 focus:bg-white focus:border-gray-200 outline-none transition-all mb-4">
                        
                        <!-- Field Preview based on type -->
                        <div x-show="field.type === 'text'" class="px-4 py-3 border-b border-dashed border-gray-300 text-gray-400 text-sm w-2/3">Short answer text</div>
                        <div x-show="field.type === 'textarea'" class="px-4 py-3 border-b border-dashed border-gray-300 text-gray-400 text-sm w-full h-20">Long answer text</div>
                        
                        <div x-show="field.type === 'select' || field.type === 'radio' || field.type === 'checkbox'" class="space-y-2 pl-4">
                            <template x-for="(opt, optIdx) in field.options" :key="optIdx">
                                <div class="flex items-center gap-3">
                                    <i class="far fa-circle text-gray-300 text-xs" x-show="field.type === 'radio'"></i>
                                    <i class="far fa-square text-gray-300 text-xs" x-show="field.type === 'checkbox'"></i>
                                    <span class="text-gray-400 text-xs" x-show="field.type === 'select'">{{ optIdx + 1 }}.</span>
                                    <input type="text" x-model="field.options[optIdx]" class="border-b border-transparent hover:border-gray-300 focus:border-brand outline-none text-sm text-gray-700 bg-transparent py-1 transition-all" placeholder="Option">
                                    <button @click="removeOption(index, optIdx)" class="text-gray-300 hover:text-red-500"><i class="fas fa-times text-xs"></i></button>
                                </div>
                            </template>
                            <button @click="addOption(index)" class="text-xs text-brand font-bold uppercase tracking-widest mt-2 hover:underline">Add Option</button>
                        </div>
                    </div>

                    <div class="col-span-4 space-y-4">
                        <select x-model="field.type" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm text-gray-700 outline-none focus:ring-2 focus:ring-brand/20">
                            <option value="text"><i class="fas fa-minus"></i> Short Answer</option>
                            <option value="textarea"><i class="fas fa-align-left"></i> Paragraph</option>
                            <option value="radio"><i class="far fa-dot-circle"></i> Multiple Choice</option>
                            <option value="checkbox"><i class="far fa-check-square"></i> Checkboxes</option>
                            <option value="select"><i class="far fa-caret-square-down"></i> Dropdown</option>
                            <option value="date"><i class="far fa-calendar-alt"></i> Date</option>
                        </select>
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <span class="text-xs text-gray-500 font-bold">Required</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="field.required" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Add Field Button -->
    <div class="flex justify-center mt-8">
        <button @click="addField" class="w-14 h-14 bg-white border border-gray-200 text-gray-600 rounded-full flex items-center justify-center shadow-lg hover:text-brand hover:border-brand/30 transition-all">
            <i class="fas fa-plus text-xl"></i>
        </button>
    </div>

</div>

<!-- Minimal Alpine.js for builder logic -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('formBuilder', () => ({
        title: 'Untitled Form',
        description: '',
        fields: [
            { label: 'Question', type: 'text', required: false, options: ['Option 1'] }
        ],
        addField() {
            this.fields.push({ label: 'Question', type: 'text', required: false, options: ['Option 1'] });
        },
        removeField(index) {
            this.fields.splice(index, 1);
        },
        addOption(fieldIndex) {
            this.fields[fieldIndex].options.push(`Option ${this.fields[fieldIndex].options.length + 1}`);
        },
        removeOption(fieldIndex, optIndex) {
            this.fields[fieldIndex].options.splice(optIndex, 1);
        },
        submitForm() {
            document.getElementById('fields-input').value = JSON.stringify(this.fields);
            document.getElementById('builder-form').submit();
        }
    }))
})
</script>
@endsection
