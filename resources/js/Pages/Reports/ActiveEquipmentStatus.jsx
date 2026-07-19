import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Select, Space, Typography } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function ActiveEquipmentStatusReport() {
    const { equipment, unitstatuses, filters } = usePage().props;

    const columns = [
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Model', render: (_, r) => r.unit_model?.name ?? '—' },
        { title: 'Project', dataIndex: 'project_code' },
        { title: 'Department', render: (_, r) => r.department?.department_name ?? '—' },
        { title: 'Status', render: (_, r) => r.unitstatus?.name ?? '—' },
    ];

    const query = new URLSearchParams(filters ?? {}).toString();

    return (
        <AuthenticatedLayout>
            <Head title="Active Equipment Status" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Active Equipment Status
                    </Typography.Title>
                    <Space>
                        <Button icon={<DownloadOutlined />} href={`/reports/active-equipment/export/excel?${query}`}>
                            Excel
                        </Button>
                        <Button icon={<DownloadOutlined />} href={`/reports/active-equipment/export/pdf?${query}`}>
                            PDF
                        </Button>
                    </Space>
                </Space>

                <Select
                    allowClear
                    placeholder="Filter by status"
                    style={{ width: 220 }}
                    defaultValue={filters?.unitstatus_id}
                    options={unitstatuses.map((s) => ({ value: s.id, label: s.name }))}
                    onChange={(value) =>
                        router.get(
                            '/reports/active-equipment',
                            { ...filters, unitstatus_id: value || undefined },
                            { preserveState: true },
                        )
                    }
                />

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={equipment.data}
                    search={false}
                    options={false}
                    pagination={{
                        current: equipment.current_page,
                        pageSize: equipment.per_page,
                        total: equipment.total,
                        onChange: (page) =>
                            router.get('/reports/active-equipment', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>
        </AuthenticatedLayout>
    );
}
