@extends('layouts.app')

@section('title', 'Terminal Cabang')

@section('content')
<div class="container-fluid px-0">
    <div
        class="d-flex flex-column flex-lg-row
            justify-content-between align-items-lg-center
            gap-3 mb-4"
    >
        <div>
            <h1 class="h3 mb-1">Terminal Cabang</h1>
            <p class="text-secondary mb-0">
                Kelola perangkat yang menampilkan QR presensi otomatis.
            </p>
        </div>

        <a
            href="{{ route('branch-terminals.create') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-plus-lg me-1"></i>
            Daftarkan Terminal
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form
                method="GET"
                action="{{ route('branch-terminals.index') }}"
                class="row g-3"
            >
                <div class="col-lg-5">
                    <label
                        for="search"
                        class="form-label"
                    >
                        Pencarian
                    </label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        class="form-control"
                        value="{{ $search }}"
                        placeholder="Nama, UUID, kode, atau nama cabang"
                    >
                </div>

                <div class="col-md-6 col-lg-3">
                    <label
                        for="branch_id"
                        class="form-label"
                    >
                        Cabang
                    </label>

                    <select
                        id="branch_id"
                        name="branch_id"
                        class="form-select"
                    >
                        <option value="">Semua cabang</option>

                        @foreach ($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                                @selected(
                                    $branchId === $branch->id
                                )
                            >
                                {{ $branch->code }} â€”
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label
                        for="status"
                        class="form-label"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select"
                    >
                        <option value="">Semua status</option>
                        <option
                            value="pending"
                            @selected($status === 'pending')
                        >
                            Pending
                        </option>
                        <option
                            value="active"
                            @selected($status === 'active')
                        >
                            Aktif
                        </option>
                        <option
                            value="revoked"
                            @selected($status === 'revoked')
                        >
                            Dicabut
                        </option>
                    </select>
                </div>

                <div
                    class="col-lg-2 d-flex
                        align-items-end gap-2"
                >
                    <button
                        type="submit"
                        class="btn btn-primary flex-grow-1"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('branch-terminals.index') }}"
                        class="btn btn-outline-secondary"
                        aria-label="Reset filter"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Terminal</th>
                            <th>Cabang</th>
                            <th>Status</th>
                            <th>Aktivasi / Aktivitas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($terminals as $terminal)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $terminal->name }}
                                    </div>

                                    <div
                                        class="small text-secondary
                                            font-monospace"
                                    >
                                        {{ $terminal->public_id }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $terminal->branch->code }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $terminal->branch->name }}
                                    </div>
                                </td>

                                <td>
                                    @if ($terminal->isPending())
                                        <span
                                            class="badge text-bg-warning"
                                        >
                                            Pending
                                        </span>
                                    @elseif ($terminal->isActive())
                                        <span
                                            class="badge text-bg-success"
                                        >
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="badge text-bg-secondary"
                                        >
                                            Dicabut
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($terminal->isPending())
                                        <div class="small">
                                            Kedaluwarsa:
                                            <strong>
                                                {{
                                                    $terminal
                                                        ->activation_expires_at
                                                        ?->format(
                                                            'd M Y H:i'
                                                        )
                                                    ?? '-'
                                                }}
                                            </strong>
                                        </div>
                                    @else
                                        <div class="small">
                                            Terakhir aktif:
                                            <strong>
                                                {{
                                                    $terminal
                                                        ->last_seen_at
                                                        ?->format(
                                                            'd M Y H:i'
                                                        )
                                                    ?? '-'
                                                }}
                                            </strong>
                                        </div>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <a
                                        href="{{
                                            route(
                                                'branch-terminals.show',
                                                $terminal
                                            )
                                        }}"
                                        class="btn btn-sm
                                            btn-outline-primary"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="text-center py-5
                                        text-secondary"
                                >
                                    Belum ada terminal yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($terminals->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $terminals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection