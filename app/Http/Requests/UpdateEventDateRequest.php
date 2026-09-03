<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventDateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Super;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'event_date' => ['nullable', 'date_format:Y-m-d'],
            'benefits' => ['sometimes', 'array:eyebrow,title,items'],
            'benefits.eyebrow' => ['required_with:benefits', 'string', 'max:80'],
            'benefits.title' => ['required_with:benefits', 'string', 'max:150'],
            'benefits.items' => ['required_with:benefits', 'array', 'size:4'],
            'benefits.items.*.title' => ['required_with:benefits.items', 'string', 'max:120'],
            'benefits.items.*.description' => ['required_with:benefits.items', 'string', 'max:500'],
            'agenda' => ['sometimes', 'array:eyebrow,title,items'],
            'agenda.eyebrow' => ['required_with:agenda', 'string', 'max:80'],
            'agenda.title' => ['required_with:agenda', 'string', 'max:150'],
            'agenda.items' => ['required_with:agenda', 'array', 'min:1', 'max:20'],
            'agenda.items.*.time' => ['required_with:agenda.items', 'string', 'max:20'],
            'agenda.items.*.title' => ['required_with:agenda.items', 'string', 'max:150'],
            'teachers' => ['sometimes', 'array:eyebrow,title,description,items'],
            'teachers.eyebrow' => ['required_with:teachers', 'string', 'max:80'],
            'teachers.title' => ['required_with:teachers', 'string', 'max:150'],
            'teachers.description' => ['required_with:teachers', 'string', 'max:500'],
            'teachers.items' => ['required_with:teachers', 'array', 'min:1', 'max:20'],
            'teachers.items.*.name' => ['required_with:teachers.items', 'string', 'max:120'],
            'teachers.items.*.subject' => ['required_with:teachers.items', 'string', 'max:80'],
            'teachers.items.*.photo_path' => ['nullable', 'string', 'max:255'],
            'teachers.items.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'seo' => ['sometimes', 'array:site_title,description,share_title,share_description,image_path,image'],
            'seo.site_title' => ['required_with:seo', 'string', 'max:120'],
            'seo.description' => ['required_with:seo', 'string', 'max:500'],
            'seo.share_title' => ['required_with:seo', 'string', 'max:120'],
            'seo.share_description' => ['required_with:seo', 'string', 'max:500'],
            'seo.image_path' => ['nullable', 'string', 'max:255'],
            'seo.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'landing' => ['sometimes', 'array:hero,audience,faq,reservation'],
            'landing.hero' => ['required_with:landing', 'array:brand_title,brand_subtitle,cta_label,eyebrow,title,description,date_label,capacity_label,capacity_value,cost_label,cost_value'],
            'landing.hero.*' => ['required_with:landing.hero', 'string', 'max:1000'],
            'landing.audience' => ['required_with:landing', 'array:eyebrow,title,items'],
            'landing.audience.eyebrow' => ['required_with:landing.audience', 'string', 'max:150'],
            'landing.audience.title' => ['required_with:landing.audience', 'string', 'max:250'],
            'landing.audience.items' => ['required_with:landing.audience', 'array', 'min:1', 'max:12'],
            'landing.audience.items.*.title' => ['required_with:landing.audience.items', 'string', 'max:150'],
            'landing.audience.items.*.description' => ['required_with:landing.audience.items', 'string', 'max:500'],
            'landing.faq' => ['required_with:landing', 'array:eyebrow,title,items'],
            'landing.faq.eyebrow' => ['required_with:landing.faq', 'string', 'max:150'],
            'landing.faq.title' => ['required_with:landing.faq', 'string', 'max:250'],
            'landing.faq.items' => ['required_with:landing.faq', 'array', 'min:1', 'max:15'],
            'landing.faq.items.*.question' => ['required_with:landing.faq.items', 'string', 'max:300'],
            'landing.faq.items.*.answer' => ['required_with:landing.faq.items', 'string', 'max:1000'],
            'landing.reservation' => ['required_with:landing', 'array:title,description,cta_label'],
            'landing.reservation.title' => ['required_with:landing.reservation', 'string', 'max:150'],
            'landing.reservation.description' => ['required_with:landing.reservation', 'string', 'max:500'],
            'landing.reservation.cta_label' => ['required_with:landing.reservation', 'string', 'max:80'],
            'shsms_template' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Za-z0-9_-]+$/', 'max:100'],
            'shsms_otp_template' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Za-z0-9_-]+$/', 'max:100'],
        ];
    }
}
