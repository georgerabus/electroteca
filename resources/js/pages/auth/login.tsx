import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import PasswordResetOtpController from '@/actions/App/Http/Controllers/Auth/PasswordResetOtpController';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const [showEmailReset, setShowEmailReset] = useState<boolean>(false);
    const [otpEmail, setOtpEmail] = useState<string>('');
    const csrfToken =
        typeof document !== 'undefined'
            ? document
                  .querySelector('meta[name=\"csrf-token\"]')
                  ?.getAttribute('content') ?? ''
            : '';

    return (
        <AuthLayout
            title="Log in to your account"
            description="Enter your email and password below to log in"
        >
            <Head title="Log in" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            Forgot password?
                                        </TextLink>
                                    )}
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <span className="w-full border-t" />
                            </div>
                            <div className="relative flex justify-center text-xs uppercase">
                                <span className="bg-background px-2 text-muted-foreground">
                                    Or continue with
                                </span>
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <Link
                                href="/auth/google"
                                className="flex items-center justify-center rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent transition"
                            >
                                <svg className="h-5 w-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                            </Link>
                            <Link
                                href="/auth/facebook"
                                className="flex items-center justify-center rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent transition"
                            >
                                <svg className="h-5 w-5" fill="#1877F2" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </Link>
                            <Link
                                href="/auth/apple"
                                className="flex items-center justify-center rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent transition"
                            >
                                <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                                </svg>
                            </Link>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Don't have an account?{' '}
                            <TextLink href={register()} tabIndex={5}>
                                Sign up
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            <div className="mt-6 rounded-lg border bg-muted/40 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                        <p className="text-sm font-medium">
                            Reset with email code
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Send a 6-digit code to your email and reset your password without leaving this page.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setShowEmailReset(!showEmailReset)}
                    >
                        {showEmailReset ? 'Hide' : 'Reset'}
                    </Button>
                </div>

                {showEmailReset && (
                    <div className="mt-4 grid gap-4">
                        <Form
                            {...PasswordResetOtpController.send.form()}
                            className="grid gap-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <div className="grid gap-2">
                                        <Label htmlFor="otp_email">
                                            Email address
                                        </Label>
                                        <Input
                                            id="otp_email"
                                            type="email"
                                            name="email"
                                            required
                                            autoComplete="email"
                                            placeholder="email@example.com"
                                            value={otpEmail}
                                            onChange={(event) =>
                                                setOtpEmail(
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        Send code
                                    </Button>
                                </>
                            )}
                        </Form>

                        <Form
                            {...PasswordResetOtpController.reset.form()}
                            className="grid gap-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <div className="grid gap-2">
                                        <Label htmlFor="otp_email_reset">
                                            Email address
                                        </Label>
                                        <Input
                                            id="otp_email_reset"
                                            type="email"
                                            name="email"
                                            required
                                            autoComplete="email"
                                            placeholder="email@example.com"
                                            value={otpEmail}
                                            onChange={(event) =>
                                                setOtpEmail(
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="otp_code">
                                            Email code
                                        </Label>
                                        <Input
                                            id="otp_code"
                                            name="code"
                                            required
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            placeholder="6-digit code"
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="otp_new_password">
                                            New password
                                        </Label>
                                        <Input
                                            id="otp_new_password"
                                            type="password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                            placeholder="New password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="otp_password_confirmation">
                                            Confirm password
                                        </Label>
                                        <Input
                                            id="otp_password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            placeholder="Confirm password"
                                        />
                                        <InputError
                                            message={errors.password_confirmation}
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                        data-test="email-otp-reset-button"
                                    >
                                        {processing && <Spinner />}
                                        Reset password with email code
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                )}
            </div>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}
