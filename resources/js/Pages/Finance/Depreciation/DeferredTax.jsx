import { Head, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Space, Typography } from 'antd';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function DeferredTaxReport() {
    const { rows, totalDeferredTax } = usePage().props;

    const columns = [
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Asset Class', dataIndex: 'asset_class' },
        {
            title: 'Book Accum.',
            dataIndex: 'book_accumulated',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Tax Accum.',
            dataIndex: 'tax_accumulated',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Temp. Difference',
            dataIndex: 'temporary_difference',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Tax Rate',
            dataIndex: 'tax_rate',
            render: (v) => `${(Number(v) * 100).toFixed(0)}%`,
        },
        {
            title: 'Deferred Tax',
            dataIndex: 'deferred_tax',
            render: (v) => Number(v).toLocaleString(),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Deferred Tax Report" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Deferred Tax — Temporary Differences
                    </Typography.Title>
                    <Typography.Text strong>
                        Total deferred tax: {Number(totalDeferredTax).toLocaleString()}
                    </Typography.Text>
                </Space>

                <Typography.Paragraph type="secondary">
                    Deferred tax = (tax accumulated depreciation − book accumulated depreciation) × tax rate
                </Typography.Paragraph>

                <ProTable
                    rowKey="fixed_asset_id"
                    columns={columns}
                    dataSource={rows}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 20 }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
