import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Checkbox, Form, Input, Typography } from 'antd';
import { LockOutlined, UserOutlined } from '@ant-design/icons';
import GuestLayout from '../../Layouts/GuestLayout';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const submit = () => {
        post('/login');
    };

    return (
        <GuestLayout>
            <Head title="Login" />

            <Card
                title="Sign in to your account"
                style={{ width: '100%', maxWidth: 420 }}
                styles={{ body: { paddingTop: 8 } }}
            >
                <Typography.Paragraph type="secondary" style={{ marginBottom: 24 }}>
                    Use your username or email address to continue.
                </Typography.Paragraph>

                <Form layout="vertical" onFinish={submit} requiredMark={false}>
                    <Form.Item
                        label="Username or Email"
                        validateStatus={errors.login ? 'error' : ''}
                        help={errors.login}
                    >
                        <Input
                            prefix={<UserOutlined />}
                            size="large"
                            value={data.login}
                            onChange={(event) => setData('login', event.target.value)}
                            placeholder="username or email@example.com"
                            autoComplete="username"
                            autoFocus
                        />
                    </Form.Item>

                    <Form.Item
                        label="Password"
                        validateStatus={errors.password ? 'error' : ''}
                        help={errors.password}
                    >
                        <Input.Password
                            prefix={<LockOutlined />}
                            size="large"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            placeholder="Enter your password"
                            autoComplete="current-password"
                        />
                    </Form.Item>

                    <Form.Item>
                        <Checkbox
                            checked={data.remember}
                            onChange={(event) => setData('remember', event.target.checked)}
                        >
                            Remember me
                        </Checkbox>
                    </Form.Item>

                    <Button type="primary" htmlType="submit" size="large" block loading={processing}>
                        Sign In
                    </Button>
                </Form>
            </Card>
        </GuestLayout>
    );
}
