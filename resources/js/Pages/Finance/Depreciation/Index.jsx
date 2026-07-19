import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, InputNumber, Space, Tag, Typography, message } from 'antd';
import { PlayCircleOutlined } from '@ant-design/icons';
import { useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

const statusColors = {
    draft: 'default',
    confirmed: 'processing',
    posted: 'success',
};

export default function DepreciationIndex() {
    const { runs, sapPostingEnabled, flash } = usePage().props;
    const [form] = Form.useForm();
    const [open, setOpen] = useState(false);

    if (flash?.success) {
        message.success(flash.success);
    }
    if (flash?.error) {
        message.error(flash.error);
    }

    const submitRun = () => {
        form.validateFields().then((values) => {
            router.post('/depreciation/run', values, {
                onSuccess: () => setOpen(false),
            });
        });
    };

    const columns = [
        {
            title: 'Period',
            render: (_, r) => `${r.period_year}-${String(r.period_month).padStart(2, '0')}`,
        },
        { title: 'Scope', dataIndex: 'book_scope' },
        {
            title: 'Book Total',
            dataIndex: 'total_book_depreciation',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Tax Total',
            dataIndex: 'total_tax_depreciation',
            render: (v) => Number(v).toLocaleString(),
        },
        { title: 'Entries', dataIndex: 'entry_count' },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (v) => <Tag color={statusColors[v]}>{v}</Tag>,
        },
        { title: 'Run By', render: (_, r) => r.runner?.name ?? '—' },
        {
            title: 'Actions',
            render: (_, record) => (
                <Button type="link" onClick={() => router.get(`/depreciation/runs/${record.id}`)}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Depreciation" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Depreciation Runs
                    </Typography.Title>
                    <Space>
                        <Button onClick={() => router.get('/depreciation/deferred-tax')}>Deferred Tax Report</Button>
                        <Button type="primary" icon={<PlayCircleOutlined />} onClick={() => setOpen(true)}>
                            Run Depreciation
                        </Button>
                    </Space>
                </Space>

                {!sapPostingEnabled && (
                    <Typography.Text type="secondary">
                        SAP journal posting is disabled until UAT sign-off (SAP_DEPRECIATION_POSTING_ENABLED).
                    </Typography.Text>
                )}

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={runs.data}
                    search={false}
                    options={false}
                    pagination={{
                        current: runs.current_page,
                        pageSize: runs.per_page,
                        total: runs.total,
                    }}
                />
            </Space>

            <Form form={form} layout="inline" style={{ display: 'none' }} />

            {open && (
                <div
                    style={{
                        position: 'fixed',
                        inset: 0,
                        background: 'rgba(0,0,0,0.45)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        zIndex: 1000,
                    }}
                    onClick={() => setOpen(false)}
                >
                    <div
                        style={{ background: '#141414', padding: 24, borderRadius: 8, minWidth: 360 }}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <Typography.Title level={5}>Run Depreciation</Typography.Title>
                        <Form
                            form={form}
                            layout="vertical"
                            initialValues={{
                                period_year: new Date().getFullYear(),
                                period_month: new Date().getMonth() + 1,
                                book_scope: 'all',
                            }}
                        >
                            <Form.Item name="period_year" label="Year" rules={[{ required: true }]}>
                                <InputNumber style={{ width: '100%' }} min={2000} max={2100} />
                            </Form.Item>
                            <Form.Item name="period_month" label="Month" rules={[{ required: true }]}>
                                <InputNumber style={{ width: '100%' }} min={1} max={12} />
                            </Form.Item>
                            <Form.Item name="book_scope" label="Book Scope">
                                <select
                                    style={{ width: '100%', padding: 8 }}
                                    onChange={(e) => form.setFieldValue('book_scope', e.target.value)}
                                    defaultValue="all"
                                >
                                    <option value="all">All (Book + Tax)</option>
                                    <option value="book">Book only</option>
                                    <option value="tax">Tax only</option>
                                </select>
                            </Form.Item>
                            <Space>
                                <Button onClick={() => setOpen(false)}>Cancel</Button>
                                <Button type="primary" onClick={submitRun}>
                                    Run
                                </Button>
                            </Space>
                        </Form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
