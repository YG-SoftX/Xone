<!-- Google-Style Unified Search Bar with Keyboard Intelligence & Domain Filtering -->
<div x-data="googleSearch()" class="relative w-full max-w-2xl mx-auto">
    <!-- The Persistent Bar -->
    <div class="relative flex items-center bg-[#f1f3f4] rounded-lg focus-within:bg-white focus-within:shadow-md focus-within:ring-1 focus-within:ring-gray-200 transition-all duration-200 px-4 py-2">
        <i class="fas fa-search text-gray-500 mr-3"></i>
        <input 
            type="text" 
            x-model="query"
            @input.debounce.300ms="fetchResults"
            @focus="isOpen = true"
            @keydown.escape="closeSearch"
            @keydown.arrow-down.prevent="navigateDown"
            @keydown.arrow-up.prevent="navigateUp"
            @keydown.enter.prevent="selectResult"
            placeholder="Search your YG empire" 
            class="bg-transparent border-none outline-none w-full text-base text-gray-700 placeholder-gray-500"
        >
        <div x-show="loading" class="ml-2">
            <i class="fas fa-circle-notch fa-spin text-blue-600 text-sm"></i>
        </div>
    </div>

    <!-- The Results Dropdown -->
    <div x-show="isOpen && (results.length > 0 || query.length > 0)" 
         x-cloak
         @click.away="closeSearch"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-b-xl shadow-2xl z-[100] max-h-[500px] overflow-y-auto">
        
        <!-- Quick Filters -->
        <div class="flex gap-2 p-3 border-b border-gray-100 overflow-x-auto bg-gray-50/50 scrollbar-hide">
            <template x-for="filter in filters" :key="filter.value">
                <button @click="selectedFilter = filter.value; fetchResults()"
                        :class="selectedFilter === filter.value ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200'"
                        class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest border transition-all whitespace-nowrap shadow-sm"
                        x-text="filter.label">
                </button>
            </template>
        </div>

        <!-- Results List -->
        <div class="divide-y divide-gray-50">
            <template x-for="(result, index) in results" :key="result.id">
                <a :href="result.action_url" 
                   :class="selectedIndex === index ? 'bg-blue-50 border-l-4 border-blue-600' : 'hover:bg-gray-50 border-l-4 border-transparent'"
                   class="flex items-start gap-4 p-4 transition-all group"
                   @mouseenter="selectedIndex = index">
                    <div :class="getServiceColor(result.service)" class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm">
                        <i :class="getServiceIcon(result.service)" class="fas text-white"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start mb-0.5">
                            <h4 class="text-sm font-semibold text-gray-900 truncate" x-text="result.title"></h4>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter" x-text="formatDate(result.created_at)"></span>
                        </div>
                        <p class="text-xs text-gray-500 line-clamp-1" x-text="result.snippet"></p>
                    </div>
                </a>
            </template>

            <!-- No Results -->
            <div x-show="!loading && query.length > 0 && results.length === 0" class="p-10 text-center">
                <i class="fas fa-search text-gray-200 text-4xl mb-4"></i>
                <p class="text-sm text-gray-400 font-medium">No matches found in <span class="font-bold text-gray-600" x-text="selectedFilter"></span> for "<span x-text="query"></span>"</p>
            </div>
        </div>
    </div>
</div>

<script>
function googleSearch() {
    return {
        isOpen: false,
        query: '',
        loading: false,
        results: [],
        selectedIndex: -1,
        selectedFilter: 'all',
        filters: [
            { value: 'all', label: 'All Results' },
            { value: 'pdf', label: '📄 PDFs' },
            { value: 'xcel', label: '📊 Spreadsheets' },
            { value: 'doc', label: '📝 Documents' },
            { value: 'mail', label: 'Mail' },
            { value: 'staff', label: 'People' }
        ],
        async fetchResults() {
            if (this.query.length < 2) {
                this.results = [];
                this.selectedIndex = -1;
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(`/api/unified-search/search?q=${encodeURIComponent(this.query)}&filter=${this.selectedFilter}`);
                const data = await response.json();
                this.results = data.success ? data.data.results : [];
                this.selectedIndex = -1;
            } catch (e) {
                console.error('Search failed');
            } finally {
                this.loading = false;
            }
        },
        navigateDown() {
            if (this.results.length > 0) {
                this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
            }
        },
        navigateUp() {
            if (this.results.length > 0) {
                this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
            }
        },
        selectResult() {
            if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                window.location.href = this.results[this.selectedIndex].action_url;
            }
        },
        closeSearch() {
            this.isOpen = false;
            this.selectedIndex = -1;
        },
        getServiceIcon(service) {
            const icons = { mail: 'fa-envelope', pay: 'fa-wallet', drive: 'fa-folder', staff: 'fa-user' };
            return icons[service] || 'fa-cube';
        },
        getServiceColor(service) {
            const colors = { mail: 'bg-red-500', pay: 'bg-purple-500', drive: 'bg-blue-500', staff: 'bg-indigo-500' };
            return colors[service] || 'bg-gray-400';
        },
        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString([], { month: 'short', day: 'numeric' });
        }
    }
}
</script>
