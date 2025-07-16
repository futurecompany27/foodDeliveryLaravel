<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        return [
            'code' => [
                'required', 'min:4', 'regex:/^[A-Za-z0-9]+$/', 'unique:coupons,code,' . $this->id,
            ],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'max_discount' => [
                'required_if:discount_type,percentage',
                'nullable',
                'numeric',
                'gt:0',
            ],
            'min_order_amount' => ['nullable', 'numeric', 'gte:0'],
            'usage_limit' => ['nullable', 'integer', 'gt:0'],
            'per_user_limit' => ['nullable', 'integer', 'gt:0'],
            'first_time_only' => ['boolean'],
            'one_time_per_user' => ['boolean'],
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages()
    {
        return [
            'code.unique' => 'Coupon code already exists.',
            'max_discount.required_if' => 'Max discount is required for percentage coupons.',
            'end_date.after' => 'End date must be after start date.',
        ];
    }
}
