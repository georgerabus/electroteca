import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
};

type UsersPageProps = {
    users: User[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Users', href: '#' },
];

export default function AdminUsers({ users }: UsersPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin - Users" />

            <div className="mx-auto max-w-7xl p-4 md:p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Users Management
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Manage system users
                    </p>
                </div>

                <div className="rounded-lg border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-left font-medium">
                                        ID
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Email
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Verified
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Joined
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map((user) => (
                                    <tr key={user.id} className="border-b">
                                        <td className="px-4 py-3">{user.id}</td>
                                        <td className="px-4 py-3 font-medium">
                                            {user.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            {user.email}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                                                    user.email_verified_at
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                                }`}
                                            >
                                                {user.email_verified_at
                                                    ? 'Verified'
                                                    : 'Pending'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            {new Date(
                                                user.created_at,
                                            ).toLocaleDateString()}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`/admin/users/${user.id}/dashboard`}
                                                className="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                View Dashboard
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
