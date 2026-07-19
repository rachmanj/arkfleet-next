import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Input, Space, Switch, Tag, Typography, message } from 'antd';
import { SyncOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';
import { usePermissions } from '../../../hooks/usePermissions';

export default function DepartmentsIndex() {
    const { departments, filters, flash } = usePage().props;
    const { can } = usePermissions();

    if (flash?.success) {
        message.success(flash.success);
    }

    const columns = [
        { title: 'Name', dataIndex: 'department_name', key: 'department_name' },
        { title: 'Acronym', dataIndex: 'akronim', key: 'akronim' },
        { title: 'SAP Code', dataIndex: 'sap_code', key: 'sap_code' },
        {
            title: 'Parent',
            dataIndex: ['parent', 'department_name'],
            render: (_, record) => record.parent?.department_name ?? '—',
        },
        {
            title: 'Active',
            dataIndex: 'is_active',
            render: (value) => <Tag color={value ? 'green' : 'default'}>{value ? 'Yes' : 'No'}</Tag>,
        },
        {
            title: 'Selectable',
            dataIndex: 'is_selectable',
            render: (value, record) =>
                can('manage-visibility') ? (
                    <Switch
                        checked={value}
                        onChange={() =>
                            router.patch(`/masters/departments/${record.id}/visibility`, {}, { preserveScroll: true })
                        }
                    />
                ) : (
                    <Tag color={value ? 'blue' : 'default'}>{value ? 'Yes' : 'No'}</Tag>
                ),
        },
        {
            title: 'Last Synced',
            dataIndex: 'synced_at',
            render: (value) => (value ? new Date(value).toLocaleString() : '—'),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Departments" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Departments
                    </Typography.Title>
                    {can('sync') && (
                        <Button
                            type="primary"
                            icon={<SyncOutlined />}
                            onClick={() => router.post('/masters/departments/sync', {}, { preserveScroll: true })}
                        >
                            Sync from SAP
                        </Button>
                    )}
                </Space>

                <Input.Search
                    placeholder="Search name or SAP code"
                    defaultValue={filters.search}
                    onSearch={(value) =>
                        router.get('/masters/departments', { search: value || undefined }, { preserveState: true })
                    }
                    allowClear
                    style={{ maxWidth: 360 }}
                />

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={departments.data}
                    search={false}
                    options={false}
                    pagination={{
                        current: departments.current_page,
                        pageSize: departments.per_page,
                        total: departments.total,
                        onChange: (page) =>
                            router.get('/masters/departments', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
