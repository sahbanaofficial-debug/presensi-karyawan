@extends('layouts.app')

@section('title', 'Edit Karyawan')

@section('content')
    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Edit Karyawan
            </h1>

            <p class="page-description">
                Perbarui profil dan akun
                {{ $employee->full_name }}.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('employees.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Karyawan
            </a>

            <a
                href="{{ route('employees.show', $employee) }}"
                class="btn btn-outline-primary"
            >
                Detail Karyawan
            </a>
        </div>
    </header>

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="edit-employee-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="edit-employee-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Perubahan Karyawan
            </h2>

            <p class="small text-secondary mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi. Kosongkan kata sandi apabila tidak
                ingin mengubah kata sandi lama.
            </p>
        </div>

        @if (
            $employee->branch !== null
            && $employee->branch->status === 'inactive'
        )
            <div
                class="alert alert-warning"
                role="alert"
            >
                <div class="fw-semibold mb-1">
                    Cabang saat ini tidak aktif.
                </div>

                <div>
                    Karyawan masih dapat dipertahankan pada cabang
                    tersebut atau dipindahkan ke cabang aktif.
                </div>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('employees.update', $employee) }}"
        >
            @csrf
            @method('PUT')

            @include('employees._form', [
                'employee' => $employee,
                'branches' => $branches,
            ])

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2
                    border-top mt-4 pt-4"
            >
                <a
                    href="{{ route('employees.show', $employee) }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>
@endsection