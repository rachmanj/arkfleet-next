import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Input, Select, Space, Tag, Typography, message } from 'antd';
import { SyncOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';
import { usePermissions } from '../../../hooks/usePermissions';

const cardTypeLabels = { C: 'Customer', S: 'Vendor', L: 'Lead' };

export default function BusinessPartnersIndex() {
    const { partners, filters, flash } = usePage().props;
    const { can } = usePermissions();

    if (flash?.success) {
        message.success(flash.success);
    }

    const columns = [
        { title: 'Card Code', dataIndex: 'card_code', key: 'card_code' },
        { title: 'Card Name', dataIndex: 'card_name', key: 'card_name' },
        {
            title: 'Type',
            dataIndex: 'card_type',
            render: (value) => <Tag>{cardTypeLabels[value] ?? value}</Tag>,
        },
        {
            title: 'Active',
            dataIndex: 'is_active',
            render: (value) => <Tag color={value ? 'green' : 'default'}>{value ? 'Yes' : 'No'}</Tag>,
        },
        { title: 'Tax ID', dataIndex: 'federal_tax_id', key: 'federal_tax_id' },
        { title: 'Currency', dataIndex: 'currency', key: 'currency' },
        {
            title: 'Last Synced',
            dataIndex: 'last_synced_at',
            render: (value) => (value ? new Date(value).toLocaleString() : '—'),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Business Partners" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Business Partners
                    </Typography.Title>
                    {can('sync') && (
                        <Button
                            type="primary"
                            icon={<SyncOutlined />}
                            onClick={() =>
                                router.post(
                                    '/masters/business-partners/sync',
                                    { card_type: filters.card_type },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Sync from SAP
                        </Button>
                    )}
                </Space>

                <Space wrap>
                    <Input.Search
                        placeholder="Search CardCode or name"
                        defaultValue={filters.search}
                        onSearch={(value) =>
                            router.get(
                                '/masters/business-partners',
                                { ...filters, search: value || undefined },
                                { preserveState: true },
                            )
                        }
                        allowClear
                        style={{ width: 280 }}
                    />
                    <Select
                        allowClear
                        placeholder="Card type"
                        style={{ width: 160 }}
                        defaultValue={filters.card_type}
                        options={[
                            { value: 'C', label: 'Customer' },
                            { value: 'S', label: 'Vendor' },
                            { value: 'L', label: 'Lead' },
                        ]}
                        onChange={(value) =>
                            router.get(
                                '/masters/business-partners',
                                { ...filters, card_type: value || undefined },
                                { preserveState: true },
                            )
                        }
                    />
                </Space>

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={partners.data}
                    search={false}
                    options={false}
                    pagination={{
                        current: partners.current_page,
                        pageSize: partners.per_page,
                        total: partners.total,
                        onChange: (page) =>
                            router.get('/masters/business-partners', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
