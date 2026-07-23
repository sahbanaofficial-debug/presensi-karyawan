<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ScheduleSwapRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class DecideScheduleSwapRequest extends FormRequest
{
    /**
     * Hanya HRD yang dapat memberikan keputusan.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') === true;
    }

    /**
     * Menormalisasi nilai keputusan.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'decision' => Str::lower(
                trim(
                    (string) $this->input('decision')
                )
            ),
        ]);
    }

    /**
     * Aturan validasi keputusan pertukaran jadwal.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => [
                'bail',
                'required',
                'string',
                Rule::in([
                    'approved',
                    'rejected',
                ]),
            ],
        ];
    }

    /**
     * Memastikan permohonan belum pernah diputuskan.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                if (
                    $validator
                        ->errors()
                        ->has('decision')
                ) {
                    return;
                }

                $scheduleSwapRequest =
                    $this->routeScheduleSwapRequest();

                if ($scheduleSwapRequest === null) {
                    return;
                }

                $currentStatus = Str::lower(
                    trim(
                        (string) $scheduleSwapRequest->status
                    )
                );

                if ($currentStatus !== 'pending') {
                    $validator->errors()->add(
                        'decision',
                        'Permohonan pertukaran jadwal sudah pernah diputuskan.'
                    );
                }
            }
        );
    }

    /**
     * Pesan validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan pertukaran jadwal wajib dipilih.',

            'decision.string' => 'Keputusan pertukaran jadwal tidak valid.',

            'decision.in' => 'Keputusan harus berupa disetujui atau ditolak.',
        ];
    }

    /**
     * Nama atribut untuk pesan validasi bawaan.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'decision' => 'keputusan pertukaran jadwal',
        ];
    }

    /**
     * Mengambil model dari parameter route
     * {schedule_swap_request}.
     */
    private function routeScheduleSwapRequest(): ?ScheduleSwapRequest
    {
        $scheduleSwapRequest = $this->route(
            'schedule_swap_request'
        );

        if (
            $scheduleSwapRequest
            instanceof ScheduleSwapRequest
        ) {
            return $scheduleSwapRequest;
        }

        if (
            is_numeric($scheduleSwapRequest)
            && (int) $scheduleSwapRequest > 0
        ) {
            return ScheduleSwapRequest::query()->find(
                (int) $scheduleSwapRequest
            );
        }

        return null;
    }
}
