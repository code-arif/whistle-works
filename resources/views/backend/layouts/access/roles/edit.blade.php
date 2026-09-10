@extends('backend.app', ['title' => 'Update Role'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h1 class="page-title">Role</h1>
                    <p class="text-muted mb-0" style="font-size: 13px;">Update role details and permissions</p>
                </div>
                <ol class="breadcrumb mb-0 py-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Access</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Role</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Update</li>
                </ol>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">

                    <div class="tab-content">
                        <div class="tab-pane active show" id="editProfile">
                            <div class="card">
                                <div class="card-body border-0">
                                    <form class="form form-horizontal" action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Role Name</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ $role->name }}">
                                            @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="guard_name" class="form-label">Guard Name</label>
                                            <select class="form-control @error('guard_name') is-invalid @enderror" id="guard_name" name="guard_name">
                                                <option value="web" {{ $role->guard_name == 'web' ? 'selected' : '' }}>Web</option>
                                                <option value="api" {{ $role->guard_name == 'api' ? 'selected' : '' }}>API</option>
                                            </select>
                                            @error('guard_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="permissions" class="form-label">Permissions</label>
                                            @foreach ($permissions as $permission)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="permission-{{ $permission->id }}" @if($role->hasPermissionTo($permission->name, $permission->guard_name)) checked @endif />
                                                <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>

                                        <button type="submit" class="submit btn btn-primary">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection
@push('scripts')

@endpush