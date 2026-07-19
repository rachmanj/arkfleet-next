import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Input, Space, Typography } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function IpaSummaryReport() {
    const { transfers, filters } = usePage().props;

    const columns = [
        { title: 'Transfer No', dataIndex: 'transfer_number' },
        { title: 'Date', dataIndex: 'transferred_at', render: (v) => new Date(v).toLocaleString() },
        { title: 'User', render: (_, r) => r.user?.name },
        { title: 'From Project', dataIndex: 'from_project_code' },
        { title: 'To Project', dataIndex: 'to_project_code' },
        { title: 'To Department', render: (_, r) => r.to_department?.department_name ?? '—' },
        { title: 'Lines', dataIndex: 'line_count' },
    ];

    const query = new URLSearchParams(filters ?? {}).toString();

    return (
        <AuthenticatedLayout>
            <Head title="IPA Summary Report" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        IPA Transfer Summary
                    </Typography.Title>
                    <Space>
                        <Button icon={<DownloadOutlined />} href={`/reports/ipa-summary/export/excel?${query}`}>
                            Excel
                        </Button>
                        <Button icon={<DownloadOutlined />} href={`/reports/ipa-summary/export/pdf?${query}`}>
                            PDF
                        </Button>
                    </Space>
                </Space>

                <Space>
                    <Input
                        type="date"
                        defaultValue={filters?.from}
                        onChange={(e) =>
                            router.get(
                                '/reports/ipa-summary',
                                { ...filters, from: e.target.value || undefined },
                                { preserveState: true },
                            )
                        }
                    />
                    <Input
                        type="date"
                        defaultValue={filters?.to}
                        onChange={(e) =>
                            router.get(
                                '/reports/ipa-summary',
                                { ...filters, to: e.target.value || undefined },
                                { preserveState: true },
                            )
                        }
                    />
                </Space>

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={transfers.data}
                    search={false}
                    options={false}
                    pagination={{
                        current: transfers.current_page,
                        pageSize: transfers.per_page,
                        total: transfers.total,
                        onChange: (page) =>
                            router.get('/reports/ipa-summary', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
