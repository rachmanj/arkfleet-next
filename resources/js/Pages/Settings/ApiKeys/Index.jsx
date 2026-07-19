import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Form, Input, Modal, Space, Typography, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function ApiKeysIndex() {
    const { tokens, apiDocs, flash } = usePage().props;
    const [open, setOpen] = useState(false);
    const [tokenModal, setTokenModal] = useState(false);
    const [form] = Form.useForm();

    if (flash?.success) {
        message.success(flash.success);
    }
    if (flash?.newToken) {
        setTokenModal(true);
    }

    const createToken = () => {
        form.validateFields().then((values) => {
            router.post('/settings/api-keys', values, {
                preserveScroll: true,
                onSuccess: () => {
                    setOpen(false);
                    form.resetFields();
                },
            });
        });
    };

    const columns = [
        { title: 'Name', dataIndex: 'name' },
        {
            title: 'Abilities',
            dataIndex: 'abilities',
            render: (v) => (Array.isArray(v) ? v.join(', ') : '—'),
        },
        { title: 'Last Used', dataIndex: 'last_used_at', render: (v) => v ?? 'Never' },
        { title: 'Created', dataIndex: 'created_at' },
        {
            title: 'Actions',
            render: (_, record) => (
                <Button
                    type="link"
                    danger
                    onClick={() => router.delete(`/settings/api-keys/${record.id}`)}
                >
                    Revoke
                </Button>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="API Keys" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        API Keys
                    </Typography.Title>
                    <Button type="primary" icon={<PlusOutlined />} onClick={() => setOpen(true)}>
                        Create Token
                    </Button>
                </Space>

                <Card title="API Documentation" size="small">
                    <Typography.Paragraph>
                        Base URL: <Typography.Text code>{apiDocs?.base_url}</Typography.Text>
                    </Typography.Paragraph>
                    <Typography.Paragraph>
                        Auth: <Typography.Text code>{apiDocs?.auth}</Typography.Text>
                    </Typography.Paragraph>
                    <ul>
                        {apiDocs?.endpoints?.map((endpoint) => (
                            <li key={endpoint}>
                                <Typography.Text code>{endpoint}</Typography.Text>
                            </li>
                        ))}
                    </ul>
                    <Typography.Text type="secondary">Rate limit: 60 requests/minute per token.</Typography.Text>
                </Card>

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={tokens}
                    search={false}
                    options={false}
                    pagination={false}
                />
            </Space>

            <Modal title="Create API Token" open={open} onCancel={() => setOpen(false)} onOk={createToken}>
                <Form form={form} layout="vertical">
                    <Form.Item name="name" label="Token Name" rules={[{ required: true }]}>
                        <Input placeholder="e.g. Integration server" />
                    </Form.Item>
                </Form>
            </Modal>

            <Modal
                title="Copy Your API Token"
                open={tokenModal}
                onCancel={() => setTokenModal(false)}
                footer={[
                    <Button key="close" type="primary" onClick={() => setTokenModal(false)}>
                        Done
                    </Button>,
                ]}
            >
                <Typography.Paragraph type="warning">
                    Store this token securely. It will not be shown again.
                </Typography.Paragraph>
                <Input.TextArea rows={3} value={flash?.newToken ?? ''} readOnly />
            </Modal>
        </AuthenticatedLayout>
    );
}
