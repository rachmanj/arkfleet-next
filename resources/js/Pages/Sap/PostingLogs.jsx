import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Space, Tag, Typography } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function PostingLogs() {
    const { logs } = usePage().props;

    const columns = [
        { title: 'Document Type', dataIndex: 'document_type' },
        { title: 'Idempotency Key', dataIndex: 'idempotency_key', ellipsis: true },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={value === 'success' ? 'green' : 'red'}>{value}</Tag>,
        },
        { title: 'DocEntry', dataIndex: 'doc_entry' },
        { title: 'DocNum', dataIndex: 'doc_num' },
        {
            title: 'Posted At',
            dataIndex: 'posted_at',
            render: (value) => (value ? new Date(value).toLocaleString() : '—'),
        },
        {
            title: 'Posted By',
            render: (_, record) => record.posted_by?.name ?? '—',
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="SAP Posting Logs" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Typography.Title level={4} style={{ margin: 0 }}>
                    SAP Posting Logs
                </Typography.Title>

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={logs.data}
                    search={false}
                    options={false}
                    pagination={{
                        current: logs.current_page,
                        pageSize: logs.per_page,
                        total: logs.total,
                        onChange: (page) => router.get('/sap/posting-logs', { page }, { preserveState: true }),
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
