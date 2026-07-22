@extends('layouts.app')

@section('title', 'Detail Cabang')

@section('content')
    <header class="page-header">
        <h1 class="page-title">
            Detail Cabang
        </h1>

        <p class="page-description">
            Informasi cabang yang telah disimpan.
        </p>
    </header>

    <section class="content-card p-4">
        <dl class="row mb-4">
            <dt class="col-sm-4">
                Kode cabang
            </dt>

            <dd class="col-sm-8">
                {{ $branch->code }}
            </dd>

            <dt class="col-sm-4">
                Nama cabang
            </dt>

            <dd class="col-sm-8">
                {{ $branch->name }}
            </dd>

            <dt class="col-sm-4">
                Alamat
            </dt>

            <dd class="col-sm-8">
                {{ $branch->address }}
            </dd>

            <dt class="col-sm-4">
                Status
            </dt>

            <dd class="col-sm-8">
                {{ $branch->status === 'active' ? 'Aktif' : 'Tidak aktif' }}
            </dd>
        </dl>

        <div class="d-flex gap-2">
            <a
                href="{{ route('branches.index') }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>

            <a
                href="{{ route('branches.edit', $branch) }}"
                class="btn btn-primary"
            >
                Edit Cabang
            </a>
        </div>
    </section>
@endsection