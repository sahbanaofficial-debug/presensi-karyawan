@extends('layouts.app')

@section('title', 'Tambah Karyawan')

@section('content')
    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Tambah Karyawan
            </h1>

            <p class="page-description">
                Tambahkan profil karyawan dan akun untuk mengakses
                sistem presensi.
            </p>
        </div>

        <div class="mt-3 mt-md-0">
            <a
                href="{{ route('employees.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Karyawan
            </a>
        </div>
    </header>

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="create-employee-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="create-employee-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Karyawan Baru
            </h2>

            <p class="small text-secondary mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi.
            </p>
        </div>

        @if ($branches->isEmpty())
            <div
                class="alert alert-warning"
                role="alert"
            >
                <div class="fw-semibold mb-1">
                    Cabang aktif tidak tersedia.
                </div>

                <div>
                    Karyawan baru belum dapat ditambahkan karena
                    tidak ada cabang aktif yang dapat dipilih.
                </div>

                <a
                    href="{{ route('branches.index') }}"
                    class="btn btn-sm btn-outline-dark mt-3"
                >
                    Kelola Cabang
                </a>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('employees.store') }}"
        >
            @csrf

            @include('employees._form', [
                'branches' => $branches,
            ])

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2
                    border-top mt-4 pt-4"
            >
                <a
                    href="{{ route('employees.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                    @disabled($branches->isEmpty())
                >
                    Simpan Karyawan
                </button>
            </div>
        </form>
    </section>
@endsection