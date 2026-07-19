import { Head, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Space, Tag, Typography } from 'antd';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function FixedAssetSchedule() {
    const { asset, entries } = usePage().props;

    const columns = [
        { title: 'Period', dataIndex: 'period_date' },
        {
            title: 'Book',
            dataIndex: 'book_type',
            render: (v) => <Tag color={v === 'book' ? 'blue' : 'orange'}>{v}</Tag>,
        },
        {
            title: 'Opening NBV',
            dataIndex: 'opening_nbv',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Depreciation',
            dataIndex: 'depreciation_amount',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Accumulated',
            dataIndex: 'accumulated_depreciation',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Closing NBV',
            dataIndex: 'closing_nbv',
            render: (v) => Number(v).toLocaleString(),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Schedule — ${asset.equipment?.unit_code}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Typography.Title level={4} style={{ margin: 0 }}>
                    Depreciation Schedule — {asset.equipment?.unit_code} ({asset.asset_class?.name})
                </Typography.Title>

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={entries}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 24 }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
