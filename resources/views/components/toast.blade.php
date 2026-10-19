<!-- Flash & Toast Notification Component (Alpine.js) -->
<div x-data="{
    notifications: [],
    add(message, type = 'success') {
        const id = Date.now();
        this.notifications.push({ id, message, type });
        setTimeout(() => this.remove(id), 5000);
    },
    remove(id) {
        this.notifications = this.notifications.filter(n => n.id !== id);
    }
}" 
x-init="
    @if(session('success')) add('{{ addslashes(session('success')) }}', 'success'); @endif
    @if(session('error')) add('{{ addslashes(session('error')) }}', 'error'); @endif
    @if(session('warning')) add('{{ addslashes(session('warning')) }}', 'warning'); @endif
    @if(session('info')) add('{{ addslashes(session('info')) }}', 'info'); @endif
    @if(isset($errors) && $errors->any()) add('{{ addslashes($errors->first()) }}', 'error'); @endif
"
class="fixed top-4 right-4 z-50 flex flex-col space-y-2 pointer-events-none max-w-sm w-full px-4 sm:px-0">
    <template x-for="item in notifications" :key="item.id">
        <div x-show="true" 
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto w-full bg-surface rounded-xl shadow-lg border p-4 flex items-start space-x-3"
             :class="{
                'border-line bg-surface text-ink': item.type === 'success',
                'border-alert bg-alert-wash text-ink': item.type === 'error',
                'border-wait bg-wait-wash text-ink': item.type === 'warning',
                'border-brand bg-brand-wash text-ink': item.type === 'info'
             }">
            
            <!-- Icon -->
            <div class="flex-shrink-0 mt-0.5">
                <template x-if="item.type === 'success'">
                    <svg class="w-5 h-5 text-done" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                <template x-if="item.type === 'error'">
                    <svg class="w-5 h-5 text-alert" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </template>
                <template x-if="item.type === 'warning'">
                    <svg class="w-5 h-5 text-wait" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                <template x-if="item.type === 'info'">
                    <svg class="w-5 h-5 text-brand" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </template>
            </div>

            <!-- Content -->
            <div class="flex-1 text-sm font-medium leading-relaxed" x-text="item.message"></div>

            <!-- Close button -->
            <button @click="remove(item.id)" type="button" class="flex-shrink-0 text-ink-faint hover:text-ink transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
