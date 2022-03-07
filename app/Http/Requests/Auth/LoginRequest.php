<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

    }


    public function authenticate()
    {
        $this->ensureIsNotRateLimited();


        $email_exist = Auth::attempt(['email' => $this->input('email'), 'password' => $this->input('password')], $this->boolean('remember'));


        $username_exist = Auth::attempt(['username' => $this->input('username'), 'password' => $this->input('password')], $this->boolean('remember'));


        if (!$email_exist && !$username_exist)

        {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
        if(auth()->user()->role_id==4){
            Auth::logout(); 
        }
    

        RateLimiter::clear($this->throttleKey());
    }

    #validar usuario prisionero
    

    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 2)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }


    public function throttleKey()
    {
        return Str::lower($this->input('email')).'|'.$this->ip();
    }
}
