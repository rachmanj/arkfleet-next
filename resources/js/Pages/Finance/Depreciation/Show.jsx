import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Space, Tag, Typography, message } from 'antd';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function DepreciationShow() {
    const { run, journalPreview, sapPostingEnabled, flash, auth } = usePage().props;
    const canPost = auth?.permissions?.includes('sap.post');

    if (flash?.success) {
        message.success(flash.success);
    }
    if (flash?.error) {
        message.error(flash.error);
    }

    const columns = [
        { title: 'Unit', render: (_, r) => r.fixed_asset?.equipment?.unit_code },
        { title: 'Book', dataIndex: 'book_type' },
        { title: 'Period', dataIndex: 'period_date' },
        {
            title: 'Amount',
            dataIndex: 'depreciation_amount',
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
            <Head title={`Depreciation ${run.period_year}-${run.period_month}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Run {run.period_year}-{String(run.period_month).padStart(2, '0')}{' '}
                        <Tag>{run.status}</Tag>
                    </Typography.Title>
                    <Space>
                        {canPost && run.status === 'draft' && (
                            <Button
                                onClick={() => router.post(`/depreciation/runs/${run.id}/confirm`)}
                            >
                                Confirm for SAP
                            </Button>
                        )}
                        {canPost && run.status === 'confirmed' && sapPostingEnabled && (
                            <Button
                                type="primary"
                                onClick={() => router.post(`/depreciation/runs/${run.id}/post-sap`)}
                            >
                                Post to SAP
                            </Button>
                        )}
                    </Space>
                </Space>

                <Space>
                    <Typography.Text>Book total: {Number(run.total_book_depreciation).toLocaleString()}</Typography.Text>
                    <Typography.Text>Tax total: {Number(run.total_tax_depreciation).toLocaleString()}</Typography.Text>
                    <Typography.Text>Entries: {run.entry_count}</Typography.Text>
                </Space>

                {journalPreview?.total_debit > 0 && (
                    <Typography.Paragraph type="secondary">
                        SAP journal preview: {journalPreview.line_pairs} line pairs, total{' '}
                        {Number(journalPreview.total_debit).toLocaleString()} IDR
                    </Typography.Paragraph>
                )}

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={run.entries ?? []}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 20 }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
