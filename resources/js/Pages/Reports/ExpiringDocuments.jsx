import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Input, Space, Typography } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function ExpiringDocumentsReport() {
    const { documents, days } = usePage().props;

    const columns = [
        { title: 'Unit Code', render: (_, r) => r.equipment?.unit_code },
        { title: 'Type', render: (_, r) => r.document_type?.name },
        { title: 'Document No', dataIndex: 'document_number' },
        { title: 'Issued', dataIndex: 'issued_date' },
        { title: 'Expiry', dataIndex: 'expiry_date' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Expiring Documents Report" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Expiring Documents ({days} days)
                    </Typography.Title>
                    <Space>
                        <Button icon={<DownloadOutlined />} href={`/reports/expiring-documents/export/excel?days=${days}`}>
                            Excel
                        </Button>
                        <Button icon={<DownloadOutlined />} href={`/reports/expiring-documents/export/pdf?days=${days}`}>
                            PDF
                        </Button>
                    </Space>
                </Space>

                <Input
                    type="number"
                    defaultValue={days}
                    addonBefore="Days ahead"
                    style={{ width: 200 }}
                    onBlur={(e) =>
                        router.get('/reports/expiring-documents', { days: e.target.value }, { preserveState: true })
                    }
                />

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={documents.data}
                    search={false}
                    options={false}
                    pagination={{
                        current: documents.current_page,
                        pageSize: documents.per_page,
                        total: documents.total,
                        onChange: (page) =>
                            router.get('/reports/expiring-documents', { days, page }, { preserveState: true }),
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
