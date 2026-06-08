@extends('layouts.app-back-office')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Color Management</h1>
            <a href="{{ route('admin.colors.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i> Add Color
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        {{-- card for search and filter section handled by javascript --}}
        {{-- add clear button handeled by controller --}}
        {{-- component for search and button clear must be horizontally centered --}}
        <div class="row mb-4">
            <div class="card shadow mb-4 w-50">
                {{-- title --}}
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Search Colors</h6>
                </div>

                <div class="card-body">
                    {{-- make 2 columns --}}
                    <div class="row">
                        <div class="col-md-10">
                            <form>
                                <div class="form-group">
                                    <input type="text" class="form-control" id="search" name="search"
                                        placeholder="Search colors...">
                                </div>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.colors.index') }}" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </div>
            </div>




            <div class="card shadow mb-4">
                {{-- card header --}}
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Color List</h6>
                </div>

                <!-- card body -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Preview</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr data-name="{{ strtolower($item->name) }}"
                                        data-code="{{ strtolower($item->code ?? '') }}">
                                        <td>{{ $item->id }}</td>
                                        <td><strong>{{ $item->name }}</strong></td>
                                        <td><code>{{ $item->code ?? 'N/A' }}</code></td>
                                        <td>
                                            @if ($item->code)
                                                <div
                                                    style="width: 30px; height: 30px; border-radius: 4px; background-color: {{ $item->code }}; border: 1px solid #ddd;">
                                                </div>
                                            @else
                                                <span class="text-muted small">Aucun</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.colors.edit', $item->id) }}"
                                                    class="btn btn-sm btn-info" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.colors.destroy', $item->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette couleur ?');"
                                                    style="display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No color found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endsection

    {{-- scripts for search colors  --}}
    @push('scripts')
        <script>
            document.getElementById('search').addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const rows = document.querySelectorAll('table tbody tr');

                rows.forEach(row => {
                    const name = row.getAttribute('data-name');
                    const code = row.getAttribute('data-code');

                    // On ignore les lignes qui n'ont pas les attributs de recherche (ex: ligne "Aucun résultat")
                    if (!name && !code) return;

                    if (name.includes(query) || (code && code.includes(query))) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        </script>
    @endpush
