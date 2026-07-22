<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BranchController extends Controller
{
    private const ITEMS_PER_PAGE = 10;

    /**
     * Menampilkan daftar cabang.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->query('search', '')
        );

        $branches = Branch::query()
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(
                        function ($searchQuery) use ($search): void {
                            $searchQuery
                                ->where(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'address',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('code')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        return view('branches.index', [
            'branches' => $branches,
            'search' => $search,
        ]);
    }

    /**
     * Menampilkan formulir penambahan cabang.
     */
    public function create(): View
    {
        return view('branches.create');
    }

    /**
     * Menyimpan data cabang baru.
     */
    public function store(
        StoreBranchRequest $request
    ): RedirectResponse {
        $branch = Branch::query()->create(
            $request->validated()
        );

        return redirect()
            ->route('branches.show', $branch)
            ->with(
                'success',
                'Data cabang berhasil ditambahkan.'
            );
    }

    /**
     * Menampilkan detail cabang.
     */
    public function show(Branch $branch): View
    {
        $branch->loadCount([
            'employees',
            'attendanceSessions',
            'attendances',
        ]);

        return view('branches.show', [
            'branch' => $branch,
            'canBeDeleted' => $this->canBeDeleted($branch),
        ]);
    }

    /**
     * Menampilkan formulir perubahan cabang.
     */
    public function edit(Branch $branch): View
    {
        return view('branches.edit', [
            'branch' => $branch,
        ]);
    }

    /**
     * Memperbarui data cabang.
     */
    public function update(
        UpdateBranchRequest $request,
        Branch $branch
    ): RedirectResponse {
        $branch->update(
            $request->validated()
        );

        return redirect()
            ->route('branches.show', $branch)
            ->with(
                'success',
                'Data cabang berhasil diperbarui.'
            );
    }

    /**
     * Menghapus cabang yang belum digunakan.
     */
    public function destroy(
        Branch $branch
    ): RedirectResponse {
        if (! $this->canBeDeleted($branch)) {
            return redirect()
                ->route('branches.show', $branch)
                ->with(
                    'error',
                    'Cabang tidak dapat dihapus karena masih memiliki data karyawan, sesi presensi, atau presensi. Ubah status cabang menjadi inactive.'
                );
        }

        try {
            $branch->delete();
        } catch (QueryException) {
            return redirect()
                ->route('branches.show', $branch)
                ->with(
                    'error',
                    'Cabang tidak dapat dihapus karena masih digunakan oleh data lain. Ubah status cabang menjadi inactive.'
                );
        }

        return redirect()
            ->route('branches.index')
            ->with(
                'success',
                'Data cabang berhasil dihapus.'
            );
    }

    /**
     * Memeriksa apakah cabang aman untuk dihapus.
     */
    private function canBeDeleted(
        Branch $branch
    ): bool {
        return ! $branch->employees()->exists()
            && ! $branch->attendanceSessions()->exists()
            && ! $branch->attendances()->exists();
    }
}