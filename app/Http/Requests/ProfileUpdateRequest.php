<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user()->role === 'member') {
            if ($this->exists('province_id') && !$this->filled('province_id')) {
                $this->merge(['city_id' => null, 'dojo_id' => null, 'district' => null]);
            } elseif ($this->exists('city_id') && !$this->filled('city_id')) {
                $this->merge(['dojo_id' => null, 'district' => null]);
            }
        }
        if ($this->filled('email')) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
        if ($this->filled('whatsapp')) {
            $phone = preg_replace('/[^0-9]/', '', $this->whatsapp);
            $this->merge(['whatsapp' => str_starts_with($phone, '62') ? '0'.substr($phone, 2) : $phone]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = $this->user()->role === 'member';
        $provinceId = $member ? $this->input('province_id', $this->user()->province_id) : $this->user()->province_id;
        $cityId = $member ? $this->input('city_id', $this->user()->city_id) : $this->user()->city_id;
        $rules = [
            'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'name' => ['required', 'string', 'max:255'],
            'district' => ['sometimes', 'nullable', 'string', 'max:150'],
            'first_coach' => ['sometimes', 'nullable', 'string', 'max:255'],
            'parent_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{8,16}$/', Rule::unique('users', 'whatsapp')->ignore($this->user()->id)],
            'dojo_id' => ['sometimes', 'nullable', 'integer', Rule::exists('dojos', 'id')->where('city_id', $cityId)],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
        if ($member) {
            $rules['province_id'] = ['sometimes', 'nullable', 'required_with:city_id', 'integer', 'exists:provinces,id'];
            $rules['city_id'] = ['sometimes', 'nullable', 'required_with:dojo_id,district', 'integer', Rule::exists('cities', 'id')->where('province_id', $provinceId)];
            $rules['belt_level_id'] = ['sometimes', 'nullable', 'integer', Rule::exists('belt_levels', 'id')->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->user()->belt_level_id))];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'Pasfoto harus berupa gambar.',
            'photo.mimes' => 'Gunakan pasfoto berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran pasfoto maksimal 2 MB.',
            'photo.dimensions' => 'Dimensi pasfoto maksimal 6000 × 6000 piksel.',
            'photo.uploaded' => 'Pasfoto gagal diunggah. Periksa ukuran berkas lalu coba lagi.',
        ];
    }
}
