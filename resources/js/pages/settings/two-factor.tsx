import HeadingSmall from '@/components/heading-small';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { disable, enable, show } from '@/routes/two-factor';
import {
    sendEmailOtp,
    verifyEmailOtp,
    disableEmailOtp,
} from '@/actions/App/Http/Controllers/Settings/TwoFactorAuthenticationController';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { ShieldBan, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

interface TwoFactorProps {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    emailTwoFactorEnabled?: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Two-Factor Authentication',
        href: show.url(),
    },
];

export default function TwoFactor({
    requiresConfirmation = false,
    twoFactorEnabled = false,
    emailTwoFactorEnabled = false,
}: TwoFactorProps) {
    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    const [showEmailVerify, setShowEmailVerify] = useState<boolean>(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Two-Factor Authentication" />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Two-Factor Authentication"
                        description="Manage your two-factor authentication settings"
                    />
                    {twoFactorEnabled ? (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <Badge variant="default">Enabled</Badge>
                            <p className="text-muted-foreground">
                                With two-factor authentication enabled, you will
                                be prompted for a secure, random pin during
                                login, which you can retrieve from the
                                TOTP-supported application on your phone.
                            </p>

                            <TwoFactorRecoveryCodes
                                recoveryCodesList={recoveryCodesList}
                                fetchRecoveryCodes={fetchRecoveryCodes}
                                errors={errors}
                            />

                            <div className="relative inline">
                                <Form {...disable.form()}>
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <ShieldBan /> Disable 2FA
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        </div>
                    ) : (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <Badge variant="destructive">Disabled</Badge>
                            <p className="text-muted-foreground">
                                When you enable two-factor authentication, you
                                will be prompted for a secure pin during login.
                                This pin can be retrieved from a TOTP-supported
                                application on your phone.
                            </p>

                            <div>
                                {hasSetupData ? (
                                    <Button
                                        onClick={() => setShowSetupModal(true)}
                                    >
                                        <ShieldCheck />
                                        Continue Setup
                                    </Button>
                                ) : (
                                    <Form
                                        {...enable.form()}
                                        onSuccess={() =>
                                            setShowSetupModal(true)
                                        }
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                <ShieldCheck />
                                                Enable 2FA
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Email-based 2FA controls */}
                    <div className="pt-6 border-t">
                        <HeadingSmall
                            title="Email Two-Factor"
                            description="Receive a one-time code via email during login."
                        />

                        <div className="flex flex-col items-start space-y-3">
                            <p className="text-muted-foreground">
                                You can receive a one-time code by email as an alternative
                                to an authenticator app.
                            </p>

                            <div className="flex items-center space-x-2">
                                {/* Show "Send email code" only when email 2FA is NOT enabled */}
                                {!emailTwoFactorEnabled && (
                                    <Form
                                        {...sendEmailOtp.form()}
                                        onSuccess={() => setShowEmailVerify(true)}
                                    >
                                        {({ processing }) => (
                                            <Button type="submit" disabled={processing}>
                                                Send email code
                                            </Button>
                                        )}
                                    </Form>
                                )}

                                {/* Show "Disable email 2FA" only when email 2FA IS enabled */}
                                {emailTwoFactorEnabled && (
                                    <Form {...disableEmailOtp.form()}>
                                        {({ processing }) => (
                                            <Button
                                                variant="destructive"
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Disable email 2FA
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>

                            {showEmailVerify && !emailTwoFactorEnabled && (
                                <div className="mt-4">
                                    <Form
                                        {...verifyEmailOtp.form()}
                                        className="flex items-center space-x-2"
                                    >
                                        {({ processing }) => (
                                            <>
                                                <input
                                                    name="code"
                                                    placeholder="Enter code"
                                                    className="input"
                                                />
                                                <Button type="submit" disabled={processing}>
                                                    Verify code
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                </div>
                            )}
                        </div>
                    </div>

                    <TwoFactorSetupModal
                        isOpen={showSetupModal}
                        onClose={() => setShowSetupModal(false)}
                        requiresConfirmation={requiresConfirmation}
                        twoFactorEnabled={twoFactorEnabled}
                        qrCodeSvg={qrCodeSvg}
                        manualSetupKey={manualSetupKey}
                        clearSetupData={clearSetupData}
                        fetchSetupData={fetchSetupData}
                        errors={errors}
                    />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
