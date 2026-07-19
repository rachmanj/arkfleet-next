import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Space, Tag, Typography } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function SyncRuns() {
    const { runs } = usePage().props;

    const columns = [
        { title: 'Entity', dataIndex: 'entity_type', key: 'entity_type' },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => (
                <Tag color={value === 'completed' ? 'green' : value === 'failed' ? 'red' : 'processing'}>
                    {value}
                </Tag>
            ),
        },
        { title: 'Created', dataIndex: 'created_count' },
        { title: 'Updated', dataIndex: 'updated_count' },
        { title: 'Failed', dataIndex: 'failed_count' },
        {
            title: 'Started',
            dataIndex: 'started_at',
            render: (value) => new Date(value).toLocaleString(),
        },
        {
            title: 'Finished',
            dataIndex: 'finished_at',
            render: (value) => (value ? new Date(value).toLocaleString() : '—'),
        },
        {
            title: 'Triggered By',
            render: (_, record) => record.triggered_by?.name ?? 'Scheduler',
        },
        { title: 'Error', dataIndex: 'error_summary', ellipsis: true },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="SAP Sync Runs" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Typography.Title level={4} style={{ margin: 0 }}>
                    SAP Sync Runs
                </Typography.Title>

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
                        onChange: (page) => router.get('/sap/sync', { page }, { preserveState: true }),
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
