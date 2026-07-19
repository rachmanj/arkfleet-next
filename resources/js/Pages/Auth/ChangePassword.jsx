import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Form, Input } from 'antd';
import { LockOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function ChangePassword() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = () => {
        put('/change-password', {
            onSuccess: () => reset('current_password', 'password', 'password_confirmation'),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Change Password" />

            <Card title="Change Password" style={{ maxWidth: 520 }}>
                <Form layout="vertical" onFinish={submit} requiredMark={false}>
                    <Form.Item
                        label="Current Password"
                        validateStatus={errors.current_password ? 'error' : ''}
                        help={errors.current_password}
                    >
                        <Input.Password
                            prefix={<LockOutlined />}
                            value={data.current_password}
                            onChange={(event) => setData('current_password', event.target.value)}
                            autoComplete="current-password"
                        />
                    </Form.Item>

                    <Form.Item
                        label="New Password"
                        validateStatus={errors.password ? 'error' : ''}
                        help={errors.password}
                    >
                        <Input.Password
                            prefix={<LockOutlined />}
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            autoComplete="new-password"
                        />
                    </Form.Item>

                    <Form.Item
                        label="Confirm New Password"
                        validateStatus={errors.password_confirmation ? 'error' : ''}
                        help={errors.password_confirmation}
                    >
                        <Input.Password
                            prefix={<LockOutlined />}
                            value={data.password_confirmation}
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            autoComplete="new-password"
                        />
                    </Form.Item>

                    <Button type="primary" htmlType="submit" loading={processing}>
                        Update Password
                    </Button>
                </Form>
            </Card>
        </AuthenticatedLayout>
    );
}
