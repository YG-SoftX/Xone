<!-- Google-Style Ecosystem Walkthrough Component -->
<div x-data="welcomeWalkthrough()" 
     x-show="active" 
     x-cloak
     class="fixed inset-0 z-[1000] overflow-hidden">
    
    <!-- Dark Backdrop -->
    <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>

    <!-- Step 1: The Launcher -->
    <div x-show="step === 1" x-transition class="absolute top-20 right-20 w-80 bg-white rounded-3xl shadow-2xl p-8 animate-fade-in">
        <div class="flex items-center gap-4 mb-4 text-[#1a73e8]">
            <i class="fas fa-th text-2xl"></i>
            <h3 class="text-lg font-bold">The Imperial Hub</h3>
        </div>
        <p class="text-sm text-gray-600 leading-relaxed mb-6">
            Access all 16 nodes of your empire from this grid. Launch Mail, Drive, Pay, and more with a single click.
        </p>
        <div class="flex justify-between items-center">
            <button @click="skip" class="text-xs font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest">Skip</button>
            <button @click="next" class="bg-[#1a73e8] text-white px-6 py-2 rounded-full text-xs font-bold shadow-md hover:bg-blue-700">Next</button>
        </div>
    </div>

    <!-- Step 2: Global Search -->
    <div x-show="step === 2" x-transition class="absolute top-20 left-1/2 -translate-x-1/2 w-[500px] bg-white rounded-3xl shadow-2xl p-8 animate-fade-in">
        <div class="flex items-center gap-4 mb-4 text-[#1a73e8]">
            <i class="fas fa-search text-2xl"></i>
            <h3 class="text-lg font-bold">Universal Gateway</h3>
        </div>
        <p class="text-sm text-gray-600 leading-relaxed mb-6">
            Search your entire empire instantly. Find emails, files, staff members, and financial records from this single, predictive bar.
        </p>
        <div class="flex justify-between items-center">
            <button @click="skip" class="text-xs font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest">Skip</button>
            <div class="flex gap-4">
                <button @click="prev" class="text-xs font-bold text-[#1a73e8] hover:underline uppercase tracking-widest">Back</button>
                <button @click="next" class="bg-[#1a73e8] text-white px-6 py-2 rounded-full text-xs font-bold shadow-md hover:bg-blue-700">Next</button>
            </div>
        </div>
    </div>

    <!-- Step 3: Security & Identity -->
    <div x-show="step === 3" x-transition class="absolute top-20 right-4 w-80 bg-white rounded-3xl shadow-2xl p-8 animate-fade-in">
        <div class="flex items-center gap-4 mb-4 text-[#1a73e8]">
            <i class="fas fa-user-shield text-2xl"></i>
            <h3 class="text-lg font-bold">Sovereign Identity</h3>
        </div>
        <p class="text-sm text-gray-600 leading-relaxed mb-6">
            Your account is protected by Imperial Guard. Manage your profile, security, and privacy settings from your unified profile card.
        </p>
        <div class="flex justify-between items-center">
            <div class="flex gap-4">
                <button @click="prev" class="text-xs font-bold text-[#1a73e8] hover:underline uppercase tracking-widest">Back</button>
            </div>
            <button @click="finish" class="bg-green-600 text-white px-8 py-2 rounded-full text-xs font-bold shadow-md hover:bg-green-700">Got it!</button>
        </div>
    </div>

</div>

<script>
function welcomeWalkthrough() {
    return {
        active: false,
        step: 1,
        init() {
            // Check if user has seen walkthrough in localStorage
            if (!localStorage.getItem('yg_walkthrough_seen')) {
                setTimeout(() => {
                    this.active = true;
                }, 2000);
            }
        },
        next() {
            if (this.step < 3) this.step++;
        },
        prev() {
            if (this.step > 1) this.step--;
        },
        skip() {
            this.finish();
        },
        finish() {
            this.active = false;
            localStorage.setItem('yg_walkthrough_seen', 'true');
        }
    }
}
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in { animation: fade-in 0.3s ease-out; }
</style>
