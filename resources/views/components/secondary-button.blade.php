<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2 rounded-2xl bg-white border border-slate-200 font-semibold text-xs text-slate-700 uppercase tracking-[0.18em] shadow-sm transition duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
