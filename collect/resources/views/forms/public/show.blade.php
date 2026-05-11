<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form->title }} | YG Collect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="https://account.ygxone.com/manifest.json">
    <link rel="apple-touch-icon" href="https://pay.ygxone.com/assets/images/logo-icon.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .glass {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-input {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 16px;
            width: 100%;
            outline: none;
            transition: all 0.3s;
            color: #1e293b;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6 pl-[70px]">
    <x-ecosystem-nav />
    <div class="max-w-2xl w-full">
        <div class="text-center mb-10">
            <div
                class="inline-block px-4 py-1 rounded-full bg-blue-50 text-blue-600 text-sm font-semibold mb-4 border border-blue-100">
                YG Collect Platform
            </div>
            <h1 class="text-4xl font-bold mb-2 gradient-text">{{ $form->title }}</h1>
            <p class="text-slate-500">{{ $form->project->name ?? 'Data Collection Project' }}</p>
        </div>

        @if(session('success'))
            <div class="glass p-8 rounded-3xl text-center mb-10">
                <div
                    class="w-16 h-16 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-green-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold mb-2">Success!</h2>
                <p class="text-slate-600">{{ session('success') }}</p>
                <button onclick="window.location.reload()" class="mt-6 text-blue-600 font-semibold hover:underline">Submit
                    another response</button>
            </div>
        @else
            <form action="{{ route('forms.public.submit', $form->id) }}" method="POST"
                class="glass p-8 md:p-10 rounded-3xl space-y-8">
                @csrf

                @if($form->schema)
                    @foreach($form->schema as $block)
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-700">
                                {{ $block['data']['label'] }}
                                @if($block['data']['required'] ?? false) <span class="text-red-500">*</span> @endif
                            </label>

                            @if($block['type'] === 'text_input')
                                <input type="text" name="{{ $block['data']['name'] }}" class="form-input" placeholder="Type here..."
                                    {{ ($block['data']['required'] ?? false) ? 'required' : '' }}>
                            @elseif($block['type'] === 'textarea')
                                <textarea name="{{ $block['data']['name'] }}" class="form-input" rows="4"
                                    placeholder="Share your thoughts..."
                                    {{ ($block['data']['required'] ?? false) ? 'required' : '' }}></textarea>
                            @elseif($block['type'] === 'select')
                                <select name="{{ $block['data']['name'] }}" class="form-input appearance-none"
                                    {{ ($block['data']['required'] ?? false) ? 'required' : '' }}>
                                    <option value="" disabled selected>Select an option</option>
                                    @foreach($block['data']['options'] as $option)
                                        <option value="{{ $option['option_value'] }}">{{ $option['option_label'] }}</option>
                                    @endforeach
                                </select>
                            @elseif($block['type'] === 'gps')
                                <div class="relative">
                                    <input type="text" id="gps-{{ $block['data']['name'] }}" name="{{ $block['data']['name'] }}"
                                        class="form-input pr-12" placeholder="Detecting location..." readonly>
                                    <button type="button" onclick="getLocation('gps-{{ $block['data']['name'] }}')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-600 hover:text-blue-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                            @elseif($block['type'] === 'photo')
                                <div class="flex items-center justify-center w-full">
                                    <label
                                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-200 rounded-2xl cursor-pointer hover:border-blue-400 bg-slate-50 transition-all">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-3 text-slate-400" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <p class="text-sm text-slate-500">Capture or upload photo</p>
                                        </div>
                                        <input type="file" name="{{ $block['data']['name'] }}" class="hidden" accept="image/*"
                                            capture="environment">
                                    </label>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
                @if(empty($form->schema))
                    <div class="text-center py-10 text-slate-500">
                        This form has no fields yet.
                    </div>
                @endif

                <div class="pt-4">
                    <button type="submit" class="w-full btn-primary text-white shadow-lg">
                        Submit Response
                    </button>
                </div>
            </form>
        @endif

        <footer class="mt-12 text-center text-slate-400 text-sm">
            Powered by <span class="font-bold text-slate-500">YG Collect</span> Ecosystem
        </footer>
    </div>

    @if($form->settings['e2e_enabled'] ?? false)
        <div id="e2e-modal"
            class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-[20000] p-6 hidden">
            <div class="bg-white p-8 rounded-3xl max-w-sm w-full shadow-2xl text-center">
                <div
                    class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">End-to-End Encryption</h3>
                <p class="text-sm text-slate-500 mb-6">Enter the master key to encrypt your data before submission.</p>
                <input type="password" id="master-key" class="form-input mb-4 text-center"
                    placeholder="Encryption Password">
                <button onclick="processEncryption()" class="w-full btn-primary text-white">Encrypt & Submit</button>
            </div>
        </div>
    @endif

    <script>
        const e2eEnabled = {{ ($form->settings['e2e_enabled'] ?? false) ? 'true' : 'false' }};
        const mainForm = document.querySelector('form');

        if (e2eEnabled) {
            mainForm.addEventListener('submit', function (e) {
                e.preventDefault();
                document.getElementById('e2e-modal').classList.remove('hidden');
            });
        }

        async function processEncryption() {
            const password = document.getElementById('master-key').value;
            if (!password) return alert("Please enter a password.");

            const formData = new FormData(mainForm);
            const data = {};
            formData.forEach((value, key) => {
                if (key !== '_token') data[key] = value;
            });

            // Encrypt data object
            const encryptedData = await encryptData(JSON.stringify(data), password);

            // Create new form to submit
            const submissionForm = document.createElement('form');
            submissionForm.method = 'POST';
            submissionForm.action = mainForm.action;

            const tokenInput = document.querySelector('input[name="_token"]').cloneNode();
            submissionForm.appendChild(tokenInput);

            const dataInput = document.createElement('input');
            dataInput.type = 'hidden';
            dataInput.name = 'e2e_blob';
            dataInput.value = encryptedData;
            submissionForm.appendChild(dataInput);

            document.body.appendChild(submissionForm);
            submissionForm.submit();
        }

        async function encryptData(text, password) {
            const encoder = new TextEncoder();
            const data = encoder.encode(text);
            const pwdData = encoder.encode(password);

            const hash = await crypto.subtle.digest('SHA-256', pwdData);
            const key = await crypto.subtle.importKey('raw', hash, { name: 'AES-GCM' }, false, ['encrypt']);

            const iv = crypto.getRandomValues(new Uint8Array(12));
            const encrypted = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, key, data);

            // Combine IV and Encrypted Data
            const combined = new Uint8Array(iv.length + encrypted.byteLength);
            combined.set(iv);
            combined.set(new Uint8Array(encrypted), iv.length);

            return btoa(String.fromCharCode.apply(null, combined));
        }

        function getLocation(id) {
            const input = document.getElementById(id);
            if (navigator.geolocation) {
                input.placeholder = "Locating...";
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        input.value = `${position.coords.latitude}, ${position.coords.longitude}`;
                    },
                    (error) => {
                        alert("Error detecting location: " + error.message);
                        input.placeholder = "Location detection failed";
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }
    </script>
</body>

</html>