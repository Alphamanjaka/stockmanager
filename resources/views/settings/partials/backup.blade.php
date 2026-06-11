<div class="card shadow-sm border-warning">
    <div class="card-header bg-warning bg-opacity-10 fw-bold text-dark">
        <i class="fas fa-shield-alt me-2"></i>Maintenance Area
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h6 class="alert-heading"><i class="fas fa-database me-2"></i>Database backup</h6>
            <p class="mb-2 mt-1 small">
                This action will generate a full SQL file of your current database. The file will be stored securely on the server.
            </p>
            <button type="button" class="btn btn-dark backup-action-btn" data-action="{{ route('admin.settings.backup') }}">
                <i class="fas fa-play-circle me-2"></i>Run new backup
            </button>
            <p class="mb-0 mt-2 small text-muted"><i class="fas fa-clock me-1"></i> The task runs in the background. Refresh the page after a few seconds.</p>
        </div>

        @if (isset($backups) && count($backups) > 0)
            <h6 class="text-muted mb-3 mt-4">Available backups</h6>
            <div class="table-responsive">
                <table class="table table-hover border align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>File</th>
                            <th>Size</th>
                            <th>Created at</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td>
                                    <i class="fas fa-file-archive text-warning me-2"></i>
                                    <span class="fw-medium">{{ $backup['name'] }}</span>
                                </td>
                                <td>{{ $backup['size'] }}</td>
                                <td>{{ $backup['date'] }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.settings.download-backup', ['path' => $backup['path']]) }}" class="btn btn-outline-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-success backup-action-btn" title="Verify integrity" data-action="{{ route('admin.settings.verify-backup') }}" data-path="{{ $backup['path'] }}">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger backup-action-btn" title="Delete" data-action="{{ route('admin.settings.delete-backup') }}" data-method="DELETE" data-path="{{ $backup['path'] }}" data-confirm="Are you sure you want to permanently delete this backup?">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p>Aucune sauvegarde disponible pour le moment.</p>
            </div>
        @endif
    </div>
</div>