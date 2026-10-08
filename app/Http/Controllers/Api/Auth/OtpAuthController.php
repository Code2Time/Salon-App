<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\CurrentUserResource;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\Otp\OtpSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpAuthController extends Controller
{
    public function request(RequestOtpRequest $request, OtpSender $sender): JsonResponse
    {
        $mobile = $request->validated('mobile');
        $code = (string) config('otp.fixed_code');

        $challenge = DB::transaction(function () use ($mobile, $code, $request) {
            OtpChallenge::query()->where('mobile', $mobile)->whereNull('consumed_at')->delete();

            return OtpChallenge::create([
                'mobile' => $mobile,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes((int) config('otp.expires_minutes')),
                'requested_ip' => $request->ip(),
            ]);
        });

        try {
            $sender->send($mobile, $code);
        } catch (\Throwable $exception) {
            $challenge->delete();
            throw $exception;
        }

        return response()->json(['message' => 'کد ورود ارسال شد.']);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $challenge = OtpChallenge::query()
            ->where('mobile', $data['mobile'])
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $challenge || $challenge->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'کد ورود منقضی شده است؛ کد جدید بگیرید.']);
        }
        if ($challenge->attempts >= (int) config('otp.max_attempts')) {
            throw ValidationException::withMessages(['code' => 'تعداد تلاش مجاز تمام شده است؛ کد جدید بگیرید.']);
        }
        if (! Hash::check($data['code'], $challenge->code_hash)) {
            $challenge->increment('attempts');
            throw ValidationException::withMessages(['code' => 'کد ورود صحیح نیست.']);
        }

        $user = DB::transaction(function () use ($data, $challenge) {
            $user = User::query()->where('mobile', $data['mobile'])->first();

            if (! $user && $data['mode'] !== 'register') {
                throw ValidationException::withMessages(['mobile' => 'حسابی با این شماره پیدا نشد؛ ابتدا ثبت‌نام کنید.']);
            }
            if ($user && $data['mode'] === 'register') {
                throw ValidationException::withMessages(['mobile' => 'این شماره قبلاً ثبت شده است؛ وارد حساب شوید.']);
            }
            if (! $user) {
                $firstName = trim($data['first_name']);
                $lastName = trim($data['last_name']);
                $user = User::create([
                    'name' => $firstName.' '.$lastName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'mobile' => $data['mobile'],
                    'mobile_verified_at' => now(),
                    'is_active' => true,
                ]);
            }
            if (! $user->is_active) {
                throw ValidationException::withMessages(['mobile' => 'این حساب غیرفعال است.']);
            }

            $challenge->update(['consumed_at' => now()]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $user->load(['salons' => fn ($query) => $query->wherePivot('is_active', true)->where('salons.is_active', true)]);

        return response()->json([
            'message' => $data['mode'] === 'register' ? 'حساب شما ساخته شد.' : 'با موفقیت وارد شدید.',
            'user' => new CurrentUserResource($user),
        ]);
    }
}
