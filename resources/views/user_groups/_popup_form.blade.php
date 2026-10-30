<form data-request="onSavePopupForm" data-request-flash data-popup-load-indicator>
    <input type="hidden" name="form_record_id" value="{{ $group->id }}" />

    <div class="dialog-header">
        <h4 class="dialog-title">
            Edit Group
        </h4>
        <button type="button" class="btn-close" data-dismiss="popup" aria-label="Close"></button>
    </div>

    <div class="dialog-body">
        {{ $formWidget }}
    </div>

    <div class="dialog-footer">
        <button type="submit" class="btn-solid theme-primary">
            Save
        </button>
        <button type="button" class="btn-solid theme-secondary" data-dismiss="popup">
            Cancel
        </button>
    </div>
</form>
