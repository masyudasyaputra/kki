<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-slate-900 border border-transparent rounded-lg font-semibold text-xs text-white tracking-wide hover:bg-slate-800 active:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-900/20 shadow-sm transition-all']) }}>
    {{ $slot }}
</button>
