<!-- Assuming you have a form here -->
<div class="row">
    @foreach ($roles as $index => $role)
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="roles[]" id="roles_{{ $role->id }}" value="{{ $role->id }}"
                {{ in_array($role->id, $userRoles) ? 'checked' : '' }}>
                <label class="form-check-label" for="roles_{{ $role->id }}">
                    {{ $role->display_name }}
                </label>
            </div>
        </div>
        <!-- Break to a new row every two roles -->
        @if (($index + 1) % 2 == 0)
            </div><div class="row">
        @endif
    @endforeach
</div>