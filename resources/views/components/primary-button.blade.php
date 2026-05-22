<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 rounded-2xl bg-blue-600 border border-transparent font-semibold text-xs text-white uppercase tracking-[0.18em] shadow-sm transition duration-150 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
