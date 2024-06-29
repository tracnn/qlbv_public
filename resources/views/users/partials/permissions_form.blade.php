<!-- Assuming you have a form here -->
<div class="row">
    @foreach ($permissions as $index => $permission)
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="permissions[]" id="permissions_{{ $permission->id }}" value="{{ $permission->id }}"
                {{ in_array($permission->id, $userPermissions) ? 'checked' : '' }}>
                <label class="form-check-label" for="permissions_{{ $permission->id }}">
                    {{ $permission->display_name }}
                </label>
            </div>
        </div>
        <!-- Break to a new row every two permissions -->
        @if (($index + 1) % 2 == 0)
            </div><div class="row">
        @endif
    @endforeach
</div>