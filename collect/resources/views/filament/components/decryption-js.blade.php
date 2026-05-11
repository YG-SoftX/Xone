<div id="decrypted-result" class="hidden mt-4 p-4 bg-green-50 rounded-xl border border-green-100">
    <h4 class="text-sm font-bold text-green-800 mb-2">🔓 Decrypted Content</h4>
    <div id="decrypted-json" class="font-mono text-xs text-green-700 whitespace-pre-wrap"></div>
</div>

<script>
    async function handleAdminDecryption() {
        const passwordInput = document.querySelector('input[id*="decryption_key"]');
        const password = passwordInput ? passwordInput.value : '';
        const blobContent = document.querySelector('.fi-fo-placeholder-content').innerText;

        if (!password) {
            alert("Please enter the Master Key.");
            return;
        }

        try {
            const decrypted = await decryptData(blobContent, password);
            const resultDiv = document.getElementById('decrypted-result');
            const jsonDiv = document.getElementById('decrypted-json');
            
            jsonDiv.innerText = JSON.stringify(JSON.parse(decrypted), null, 4);
            resultDiv.classList.remove('hidden');
        } catch (e) {
            alert("Decryption failed. Incorrect Master Key or corrupted data.");
            console.error(e);
        }
    }

    async function decryptData(encoded, password) {
        const combined = new Uint8Array(atob(encoded).split("").map(c => c.charCodeAt(0)));
        const iv = combined.slice(0, 12);
        const data = combined.slice(12);

        const encoder = new TextEncoder();
        const pwdData = encoder.encode(password);
        const hash = await crypto.subtle.digest('SHA-256', pwdData);
        const key = await crypto.subtle.importKey('raw', hash, {name: 'AES-GCM'}, false, ['decrypt']);

        const decrypted = await crypto.subtle.decrypt({name: 'AES-GCM', iv: iv}, key, data);
        return new TextDecoder().decode(decrypted);
    }
</script>
