@extends('layouts.app')

@section('title', 'Tambah Cabang')

@section('content')
    <header class="page-header">
        <h1 class="page-title">
            Tambah Cabang
        </h1>

        <p class="page-description">
            Masukkan identitas dan konfigurasi lokasi cabang.
        </p>
    </header>

    <section class="content-card p-4">
        <form
            method="POST"
            action="{{ route('branches.store') }}"
        >
            @csrf

            @include('branches._form')

            <div class="d-flex justify-content-end gap-2 border-top mt-4 pt-4">
                <a
                    href="{{ route('branches.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Cabang
                </button>
            </div>
        </form>
    </section>
@endsection