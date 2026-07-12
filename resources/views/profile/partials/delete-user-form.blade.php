<section>
    <h3 style="color:var(--red);">Eliminar cuenta</h3>
    <p style="font-size:13px; color:var(--muted); margin-bottom:16px;">
        Una vez eliminada, todos los datos de tu cuenta serán borrados permanentemente.
        Descargá cualquier información que necesités conservar antes de continuar.
    </p>

    <button type="button" class="btn-del-user"
            data-bs-toggle="modal" data-bs-target="#modalEliminarCuenta">
        Eliminar mi cuenta
    </button>

    <div class="modal fade" id="modalEliminarCuenta" tabindex="-1"
         @if($errors->userDeletion->isNotEmpty()) data-bs-show="true" @endif>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')
                    <div class="modal-body" style="padding:24px;">
                        <h5 style="margin-bottom:8px;">¿Estás seguro?</h5>
                        <p style="font-size:13.5px; color:var(--muted); margin-bottom:20px;">
                            Esta acción no se puede deshacer. Ingresá tu contraseña para confirmar.
                        </p>
                        <div class="form-field" style="margin-bottom:0;">
                            <label for="del-password">Contraseña</label>
                            <div class="ctrl">
                                <input id="del-password" name="password" type="password"
                                       placeholder="Tu contraseña actual"
                                       autocomplete="current-password">
                            </div>
                            <x-input-error class="field-error" :messages="$errors->userDeletion->get('password')" />
                        </div>
                    </div>
                    <div class="modal-footer" style="gap:8px; padding:16px 24px;">
                        <button type="button" class="btn-sin-icono" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-del-user">Eliminar cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($errors->userDeletion->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('modalEliminarCuenta')).show();
            });
        </script>
    @endif
</section>
