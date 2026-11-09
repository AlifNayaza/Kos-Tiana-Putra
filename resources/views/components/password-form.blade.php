<form action="{{ route('profil.password') }}" method="POST" class="space-y-3">
    @csrf
    @method('PUT')

    @if(auth()->user()->must_change_password)
        <div class="p-3 rounded-xl bg-wait-wash border border-wait text-xs text-ink">
            Anda masuk dengan kata sandi sementara. Buat kata sandi baru untuk melanjutkan.
        </div>
    @endif

    <div>
        <label for="current_password" class="block text-xs font-semibold text-ink-soft mb-1">Kata sandi saat ini</label>
        <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
               class="w-full text-xs px-3.5 py-2.5 border border-line rounded-xl text-ink focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
        @error('current_password')<p class="text-2xs text-alert mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password" class="block text-xs font-semibold text-ink-soft mb-1">Kata sandi baru</label>
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"
               class="w-full text-xs px-3.5 py-2.5 border border-line rounded-xl text-ink focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
        <span class="text-2xs text-ink-faint mt-1 block">Minimal 8 karakter, kombinasi huruf dan angka.</span>
        @error('password')<p class="text-2xs text-alert mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-xs font-semibold text-ink-soft mb-1">Ulangi kata sandi baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
               class="w-full text-xs px-3.5 py-2.5 border border-line rounded-xl text-ink focus:outline-none focus:ring-2 focus:ring-brand-wash focus:border-brand bg-surface">
    </div>

    <button type="submit" class="w-full h-11 min-h-[44px] rounded-xl bg-brand hover:bg-brand-deep text-surface font-semibold text-xs transition-colors">
        Simpan Kata Sandi Baru
    </button>
</form>
