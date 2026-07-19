import { Head, Link, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Space, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../../../Layouts/AuthenticatedLayout';

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function HmKmBatches() {
    const { batches } = usePage().props;

    const columns = [
        {
            title: 'Date',
            dataIndex: 'created_at',
            render: formatDate,
        },
        {
            title: 'Filename',
            dataIndex: 'original_filename',
            render: (value, record) => (
                <Link href={`/equipment/hm-km/batches/${record.batch_id}`}>{value}</Link>
            ),
        },
        { title: 'Rows Total', dataIndex: 'rows_total', align: 'right' },
        { title: 'Imported', dataIndex: 'rows_imported', align: 'right' },
        { title: 'Skipped', dataIndex: 'rows_skipped', align: 'right' },
        { title: 'Errors', dataIndex: 'rows_errored', align: 'right' },
        {
            title: 'Uploaded By',
            render: (_, record) => record.uploader?.name ?? '—',
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="HM/KM Upload Batches" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }} wrap>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        HM/KM Upload Batches
                    </Typography.Title>
                    <Link href="/equipment/hm-km/upload">
                        <Button icon={<ArrowLeftOutlined />}>Back to Upload</Button>
                    </Link>
                </Space>

                <ProTable
                    rowKey="batch_id"
                    columns={columns}
                    dataSource={batches.data}
                    search={false}
                    options={false}
                    onRow={(record) => ({
                        onClick: () => {
                            window.location.href = `/equipment/hm-km/batches/${record.batch_id}`;
                        },
                        style: { cursor: 'pointer' },
                    })}
                    pagination={{
                        current: batches.current_page,
                        pageSize: batches.per_page,
                        total: batches.total,
                        onChange: (page) => {
                            router.get('/equipment/hm-km/batches', { page }, { preserveState: true });
                        },
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
