<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-ember text-white hover:bg-ember/80']) }}>
    {{ $slot }}
</button>
