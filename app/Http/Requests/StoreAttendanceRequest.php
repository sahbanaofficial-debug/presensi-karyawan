<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use JsonException;

final class StoreAttendanceRequest extends FormRequest
{
    /**
     * Hanya karyawan aktif dengan profil aktif
     * yang dapat mengirim transaksi presensi.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->role === 'employee'
            && $user->status === 'active'
            && $user->hasActiveEmployeeProfile();
    }

    /**
     * Mengambil public ID dan token dari payload QR.
     */
    protected function prepareForValidation(): void
    {
        $rawQrPayload = $this->input('qr_payload');

        $normalizedQrPayload = is_string($rawQrPayload)
            ? trim($rawQrPayload)
            : $rawQrPayload;

        $decodedPayload = $this->decodeQrPayload(
            $normalizedQrPayload
        );

        $session = null;
        $token = null;

        if ($decodedPayload !== null) {
            $sessionValue =
                $decodedPayload['session'] ?? null;

            $tokenValue =
                $decodedPayload['token'] ?? null;

            if (is_string($sessionValue)) {
                $session = strtolower(
                    trim($sessionValue)
                );
            }

            if (is_string($tokenValue)) {
                $token = trim($tokenValue);
            }
        }

        $this->merge([
            'qr_payload' => $normalizedQrPayload,

            'session' => $session,

            'token' => $token,

            'latitude' => $this->normalizeNumericInput(
                'latitude'
            ),

            'longitude' => $this->normalizeNumericInput(
                'longitude'
            ),

            'accuracy' => $this->normalizeNumericInput(
                'accuracy'
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /*
             * Nilai asli hasil pemindaian QR.
             */
            'qr_payload' => [
                'bail',
                'required',
                'string',
                'max:512',
            ],

            /*
             * Hasil ekstraksi dari qr_payload.
             *
             * Tidak menggunakan aturan exists agar controller
             * dapat menangani sesi yang tidak ditemukan dan
             * menyimpan log penolakan secara terstruktur.
             */
            'session' => [
                'bail',
                'required',
                'string',
                'uuid',
            ],

            'token' => [
                'bail',
                'required',
                'string',
                'regex:/^\d{6}$/',
            ],

            /*
             * Data lokasi dari Geolocation API.
             */
            'latitude' => [
                'bail',
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'bail',
                'required',
                'numeric',
                'between:-180,180',
            ],

            'accuracy' => [
                'bail',
                'required',
                'numeric',
                'gt:0',
                'max:999999.99',
            ],

            /*
             * Semua nilai berikut harus ditentukan server.
             */
            'id' => [
                'prohibited',
            ],

            'public_id' => [
                'prohibited',
            ],

            'employee_id' => [
                'prohibited',
            ],

            'attendance_session_id' => [
                'prohibited',
            ],

            'employee_schedule_id' => [
                'prohibited',
            ],

            'branch_id' => [
                'prohibited',
            ],

            'attendance_type' => [
                'prohibited',
            ],

            'attendance_date' => [
                'prohibited',
            ],

            'attendance_time' => [
                'prohibited',
            ],

            'distance' => [
                'prohibited',
            ],

            'geofence_radius' => [
                'prohibited',
            ],

            'attendance_status' => [
                'prohibited',
            ],

            'punctuality_status' => [
                'prohibited',
            ],

            'validation_status' => [
                'prohibited',
            ],

            'status' => [
                'prohibited',
            ],

            'created_by' => [
                'prohibited',
            ],
        ];
    }

    /**
     * Memeriksa struktur internal payload QR.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                $decodedPayload =
                    $this->decodeQrPayload(
                        $this->input('qr_payload')
                    );

                if ($decodedPayload === null) {
                    $validator->errors()->add(
                        'qr_payload',
                        'QR Code tidak memiliki format yang valid.'
                    );

                    return;
                }

                $allowedKeys = [
                    'session',
                    'token',
                ];

                $unexpectedKeys = array_diff(
                    array_keys($decodedPayload),
                    $allowedKeys
                );

                if ($unexpectedKeys !== []) {
                    $validator->errors()->add(
                        'qr_payload',
                        'QR Code memuat data yang tidak diperbolehkan.'
                    );
                }
            }
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qr_payload.required' => 'QR Code wajib dipindai.',

            'qr_payload.string' => 'Data QR Code tidak valid.',

            'qr_payload.max' => 'Data QR Code melebihi batas ukuran.',

            'session.required' => 'Identitas sesi tidak ditemukan pada QR Code.',

            'session.uuid' => 'Identitas sesi pada QR Code tidak valid.',

            'token.required' => 'Token TOTP tidak ditemukan pada QR Code.',

            'token.regex' => 'Token TOTP harus terdiri dari enam angka.',

            'latitude.required' => 'Latitude lokasi wajib tersedia.',

            'latitude.numeric' => 'Latitude harus berupa angka.',

            'latitude.between' => 'Latitude harus berada pada rentang -90 sampai 90.',

            'longitude.required' => 'Longitude lokasi wajib tersedia.',

            'longitude.numeric' => 'Longitude harus berupa angka.',

            'longitude.between' => 'Longitude harus berada pada rentang -180 sampai 180.',

            'accuracy.required' => 'Nilai akurasi lokasi wajib tersedia.',

            'accuracy.numeric' => 'Nilai akurasi lokasi harus berupa angka.',

            'accuracy.gt' => 'Nilai akurasi lokasi harus lebih besar dari nol.',

            'accuracy.max' => 'Nilai akurasi lokasi melebihi kapasitas penyimpanan.',

            '*.prohibited' => 'Permintaan memuat data yang hanya boleh ditentukan oleh server.',
        ];
    }

    /**
     * Public ID sesi hasil pemindaian QR.
     */
    public function sessionPublicId(): string
    {
        $validated = $this->validated();

        return (string) $validated['session'];
    }

    /**
     * Token TOTP hasil pemindaian QR.
     */
    public function totpToken(): string
    {
        $validated = $this->validated();

        return (string) $validated['token'];
    }

    /**
     * Data lokasi yang telah lulus validasi struktur.
     *
     * @return array{
     *     latitude: float,
     *     longitude: float,
     *     accuracy: float
     * }
     */
    public function coordinates(): array
    {
        $validated = $this->validated();

        return [
            'latitude' => (float) $validated['latitude'],

            'longitude' => (float) $validated['longitude'],

            'accuracy' => (float) $validated['accuracy'],
        ];
    }

    /**
     * Hash payload untuk validation log.
     *
     * Token asli tidak perlu disimpan di dalam log.
     */
    public function payloadReference(): string
    {
        $validated = $this->validated();

        return hash(
            'sha256',
            (string) $validated['qr_payload']
        );
    }

    /**
     * Mengubah JSON QR menjadi array.
     *
     * @return array<string, mixed>|null
     */
    private function decodeQrPayload(
        mixed $rawPayload
    ): ?array {
        if (
            ! is_string($rawPayload)
            || trim($rawPayload) === ''
        ) {
            return null;
        }

        try {
            $decodedPayload = json_decode(
                trim($rawPayload),
                true,
                4,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decodedPayload)) {
            return null;
        }

        return $decodedPayload;
    }

    /**
     * Membersihkan nilai angka tanpa mengubahnya
     * sebelum proses validasi.
     */
    private function normalizeNumericInput(
        string $field
    ): mixed {
        $value = $this->input($field);

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}
