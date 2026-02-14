<div class="modal fade" id="multi-delete-modal" tabindex="-1" aria-labelledby="multiDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="multiDeleteModalLabel">{{ __('Confirm Delete') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">{{ __('Are you sure you want to delete the selected items? This action cannot be undone.') }}</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-danger multi-delete-btn">{{ __('Delete') }}</button>
      </div>
    </div>
  </div>
</div>
