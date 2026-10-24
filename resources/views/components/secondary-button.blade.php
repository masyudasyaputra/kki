<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 tracking-wide shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400/20 disabled:opacity-25 transition-all']) }}>
    {{ $slot }}
</button>
