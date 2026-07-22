@extends('layouts.app')

@section('title', 'Edit Cabang')

@section('content')
    <header
        class="page-header d-md-flex align-items-start
            justify-content-between gap-3"
    >
        <div>
            <h1 class="page-title">
                Edit Cabang
            </h1>

            <p class="page-description">
                Perbarui identitas dan konfigurasi lokasi cabang
                {{ $branch->code }}.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a
                href="{{ route('branches.index') }}"
                class="btn btn-outline-secondary"
            >
                Daftar Cabang
            </a>

            <a
                href="{{ route('branches.show', $branch) }}"
                class="btn btn-outline-primary"
            >
                Detail Cabang
            </a>
        </div>
    </header>

    <section
        class="content-card p-3 p-md-4"
        aria-labelledby="edit-branch-heading"
    >
        <div class="border-bottom pb-3 mb-4">
            <h2
                id="edit-branch-heading"
                class="h5 fw-bold mb-1"
            >
                Formulir Perubahan Cabang
            </h2>

            <p class="text-secondary small mb-0">
                Kolom dengan tanda
                <span class="text-danger">*</span>
                wajib diisi.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('branches.update', $branch) }}"
        >
            @csrf
            @method('PUT')

            @include('branches._form', [
                'branch' => $branch,
            ])

            <div
                class="d-flex flex-column flex-sm-row
                    justify-content-end gap-2 border-top mt-4 pt-4"
            >
                <a
                    href="{{ route('branches.show', $branch) }}"
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