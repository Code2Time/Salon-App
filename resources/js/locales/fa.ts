export const fa = {
    apiErrors: {
        unexpected: "خطای پیش‌بینی‌نشده‌ای رخ داد.",
        network: "ارتباط با سرور برقرار نشد. اتصال اینترنت یا وضعیت سرور را بررسی کنید.",
        unauthorized: "نشست شما معتبر نیست؛ لطفاً دوباره وارد شوید.",
        forbidden: "شما اجازه انجام این عملیات را ندارید.",
        csrfExpired: "اعتبار صفحه منقضی شده است؛ صفحه را تازه‌سازی و دوباره تلاش کنید.",
        validation: "اطلاعات واردشده معتبر نیست.",
        tooManyAttempts: "تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.",
        tooManyAttemptsWithWait: (seconds: number) =>
            `تعداد درخواست‌ها بیش از حد مجاز است. ${seconds} ثانیه دیگر دوباره تلاش کنید.`,
        server: "خطایی در سرور رخ داد. لطفاً کمی بعد دوباره تلاش کنید.",
    },
    serverMessages: {
        "Too Many Attempts.": "تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.",
        "Unauthenticated.": "نشست شما معتبر نیست؛ لطفاً دوباره وارد شوید.",
        "This action is unauthorized.": "شما اجازه انجام این عملیات را ندارید.",
        "CSRF token mismatch.": "اعتبار صفحه منقضی شده است؛ صفحه را تازه‌سازی و دوباره تلاش کنید.",
        "The given data was invalid.": "اطلاعات واردشده معتبر نیست.",
        "Server Error": "خطایی در سرور رخ داد. لطفاً کمی بعد دوباره تلاش کنید.",
    } satisfies Record<string, string>,
    auth: {
        otpSent: "کد ورود ارسال شد.",
        loginSucceeded: "با موفقیت وارد شدید.",
        registrationSucceeded: "حساب شما با موفقیت ساخته شد.",
    },
} as const;
