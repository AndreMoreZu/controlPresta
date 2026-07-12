<x-app-layout>
    <x-slot name="header">Usuarios</x-slot>

    <div class="list-head">
        <div></div>
        <a href="{{ route('usuarios.create') }}" class="btn-new">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo usuario
        </a>
    </div>

    <div class="panel">
        @foreach ($usuarios as $user)
            <div class="user-row">
                <div class="av">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</div>
                <div class="user-info">
                    <b>{{ $user->name }}</b>
                    <div class="user-email">{{ $user->email }}</div>
                </div>
                @if ($user->id === auth()->id())
                    <span class="tag ok" style="font-size: 11px; padding: 2px 8px;">Yo</span>
                @endif
                <form method="POST" action="{{ route('usuarios.destroy', $user) }}"
                      onsubmit="return confirm('¿Eliminar a {{ $user->name }}?\nEsta acción no se puede deshacer.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-del-user"
                            @disabled($user->id === auth()->id() || $usuarios->count() === 1)>
                        Eliminar
                    </button>
                </form>
            </div>
        @endforeach
    </div>

</x-app-layout>
