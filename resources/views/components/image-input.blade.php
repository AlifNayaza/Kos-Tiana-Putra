{{--
    Input foto dengan pratinjau untuk form modal berbasis Alpine.
    - form        : nama objek state Alpine (mis. "roomForm"); wajib punya image_url, has_image, preview, remove_image.
    - placeholder : ekspresi JS yang menghasilkan URL gambar default.
    - open        : nama variabel Alpine penanda modal terbuka (berkas dikosongkan tiap modal dibuka).
--}}
@props(['form', 'placeholder', 'open' => 'modalOpen', 'label' => 'Foto'])

<div>
    <label class="block text-xs font-semibold text-ink mb-1">{{ $label }} <span class="font-normal text-ink-faint">(opsional)</span></label>
    <div class="flex items-start gap-3">
        <div class="w-20 h-20 rounded-xl overflow-hidden border border-line bg-canvas flex-shrink-0">
            <img :src="{{ $form }}.preview || ({{ $form }}.has_image && !{{ $form }}.remove_image ? {{ $form }}.image_url : {{ $placeholder }})"
                 alt="Pratinjau foto" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0 space-y-1.5">
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   x-init="$watch('{{ $open }}', isOpen => { if (isOpen) $el.value = '' })"
                   @change="
                       const file = $event.target.files[0];
                       if ({{ $form }}.preview) URL.revokeObjectURL({{ $form }}.preview);
                       {{ $form }}.preview = file ? URL.createObjectURL(file) : null;
                       if (file) {{ $form }}.remove_image = false;
                   "
                   class="block w-full text-xs text-ink-soft file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-wash file:text-brand hover:file:bg-line file:cursor-pointer">
            <p class="text-2xs text-ink-faint">JPG, PNG, atau WEBP, maksimal 2 MB. Tanpa foto, gambar default yang dipakai.</p>
            <label x-show="{{ $form }}.has_image && !{{ $form }}.preview" class="inline-flex items-center gap-1.5 text-2xs font-semibold text-alert cursor-pointer select-none">
                <input type="checkbox" name="remove_image" value="1" x-model="{{ $form }}.remove_image" class="rounded border-line text-alert focus:ring-alert-wash">
                Hapus foto, pakai gambar default
            </label>
        </div>
    </div>
    <x-field-error name="image" />
</div>
