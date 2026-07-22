<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBranchRequest extends FormRequest
{
    /**
     * Hanya HRD yang dapat menambahkan data cabang.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') === true;
    }

    /**
     * Normalisasi data sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(
                trim((string) $this->input('code'))
            ),
            'name' => trim(
                (string) $this->input('name')
            ),
            'address' => trim(
                (string) $this->input('address')
            ),
            'latitude' => $this->nullableNumber('latitude'),
            'longitude' => $this->nullableNumber('longitude'),
            'status' => strtolower(
                trim((string) $this->input('status'))
            ),
        ]);
    }

    /**
     * Aturan validasi penambahan cabang.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'bail',
                'required',
                'string',
                'max:20',
                Rule::unique('branches', 'code'),
            ],
            'name' => [
                'bail',
                'required',
                'string',
                'max:100',
            ],
            'address' => [
                'bail',
                'required',
                'string',
                'max:65535',
            ],
            'latitude' => [
                'nullable',
                'required_with:longitude',
                'numeric',
                'between:-90,90',
                'decimal:0,8',
            ],
            'longitude' => [
                'nullable',
                'required_with:latitude',
                'numeric',
                'between:-180,180',
                'decimal:0,8',
            ],
            'geofence_radius' => [
                'bail',
                'required',
                'numeric',
                'gt:0',
                'max:999999.99',
                'decimal:0,2',
            ],
            'maximum_accuracy' => [
                'bail',
                'required',
                'numeric',
                'gt:0',
                'max:999999.99',
                'decimal:0,2',
            ],
            'status' => [
                'bail',
                'required',
                'string',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }

    /**
     * Pesan validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode cabang wajib diisi.',
            'code.max' => 'Kode cabang maksimal 20 karakter.',
            'code.unique' => 'Kode cabang sudah digunakan.',

            'name.required' => 'Nama cabang wajib diisi.',
            'name.max' => 'Nama cabang maksimal 100 karakter.',

            'address.required' => 'Alamat cabang wajib diisi.',
            'address.max' => 'Alamat cabang terlalu panjang.',

            'latitude.required_with' => 'Latitude wajib diisi apabila longitude diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus berada pada rentang -90 sampai 90.',
            'latitude.decimal' => 'Latitude maksimal menggunakan delapan angka desimal.',

            'longitude.required_with' => 'Longitude wajib diisi apabila latitude diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus berada pada rentang -180 sampai 180.',
            'longitude.decimal' => 'Longitude maksimal menggunakan delapan angka desimal.',

            'geofence_radius.required' => 'Radius geofence wajib diisi.',
            'geofence_radius.numeric' => 'Radius geofence harus berupa angka.',
            'geofence_radius.gt' => 'Radius geofence harus lebih besar dari 0 meter.',
            'geofence_radius.max' => 'Radius geofence melebihi kapasitas penyimpanan.',
            'geofence_radius.decimal' => 'Radius geofence maksimal menggunakan dua angka desimal.',

            'maximum_accuracy.required' => 'Batas akurasi lokasi wajib diisi.',
            'maximum_accuracy.numeric' => 'Batas akurasi lokasi harus berupa angka.',
            'maximum_accuracy.gt' => 'Batas akurasi lokasi harus lebih besar dari 0 meter.',
            'maximum_accuracy.max' => 'Batas akurasi lokasi melebihi kapasitas penyimpanan.',
            'maximum_accuracy.decimal' => 'Batas akurasi lokasi maksimal menggunakan dua angka desimal.',

            'status.required' => 'Status cabang wajib dipilih.',
            'status.in' => 'Status cabang harus active atau inactive.',
        ];
    }

    /**
     * Mengubah input kosong menjadi null.
     */
    private function nullableNumber(string $field): mixed
    {
        $value = $this->input($field);

        if ($value === null) {
            return null;
        }

        if (
            is_string($value)
            && trim($value) === ''
        ) {
            return null;
        }

        return $value;
    }
}
