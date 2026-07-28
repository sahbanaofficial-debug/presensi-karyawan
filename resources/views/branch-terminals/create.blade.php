@extends('layouts.app')

@section('title', 'Daftarkan Terminal Cabang')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a
            href="{{ route('branch-terminals.index') }}"
            class="text-decoration-none"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Kembali ke Terminal Cabang
        </a>

        <h1 class="h3 mt-3 mb-1">
            Daftarkan Terminal Cabang
        </h1>

        <p class="text-secondary mb-0">
            Sistem akan menghasilkan kode aktivasi sekali pakai
            dengan masa berlaku 15 menit.
        </p>
    </div>

    <div class="row">
        <div class="col-xl-8">
            @if ($errors->has('terminal'))
                <div class="alert alert-danger">
                    {{ $errors->first('terminal') }}
                </div>
            @endif

            @if ($branches->isEmpty())
                <div class="alert alert-warning">
                    Tidak ada cabang aktif dengan konfigurasi
                    geofence lengkap. Lengkapi data cabang sebelum
                    mendaftarkan terminal.
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form
                        method="POST"
                        action="{{
                            route('branch-terminals.store')
                        }}"
                    >
                        @csrf

                        <div class="mb-3">
                            <label
                                for="branch_id"
                                class="form-label"
                            >
                                Cabang
                            </label>

                            <select
                                id="branch_id"
                                name="branch_id"
                                class="form-select
                                    @error('branch_id')
                                        is-invalid
                                    @enderror"
                                required
                                @disabled($branches->isEmpty())
                            >
                                <option value="">
                                    Pilih cabang
                                </option>

                                @foreach ($branches as $branch)
                                    <option
                                        value="{{ $branch->id }}"
                                        @selected(
                                            (string) old('branch_id')
                                            === (string) $branch->id
                                        )
                                    >
                                        {{ $branch->code }} â€”
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('branch_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label
                                for="name"
                                class="form-label"
                            >
                                Nama Terminal
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control
                                    @error('name')
                                        is-invalid
                                    @enderror"
                                value="{{ old('name') }}"
                                maxlength="100"
                                placeholder="Contoh: Terminal Lobby Utama"
                                required
                            >

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Gunakan nama yang menunjukkan lokasi
                                fisik perangkat.
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                @disabled($branches->isEmpty())
                            >
                                Daftarkan dan Buat Kode
                            </button>

                            <a
                                href="{{
                                    route(
                                        'branch-terminals.index'
                                    )
                                }}"
                                class="btn btn-outline-secondary"
                            >
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection