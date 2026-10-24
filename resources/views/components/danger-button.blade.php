<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white tracking-wide hover:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500/20 shadow-sm transition-all']) }}>
    {{ $slot }}
</button>
