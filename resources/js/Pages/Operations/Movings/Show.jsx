import { Head, Link, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Descriptions, Space, Typography } from 'antd';
import { FilePdfOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function MovingsShow() {
    const { transfer } = usePage().props;

    const columns = [
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'From Project', dataIndex: 'from_project_code' },
        { title: 'To Project', dataIndex: 'to_project_code' },
        { title: 'From Dept', render: (_, r) => r.from_department?.department_name ?? '—' },
        { title: 'To Dept', render: (_, r) => r.to_department?.department_name ?? '—' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={transfer.transfer_number} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ justifyContent: 'space-between', width: '100%' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        {transfer.transfer_number}
                    </Typography.Title>
                    <Space>
                        <Button type="primary" icon={<FilePdfOutlined />} href={`/movings/transfers/${transfer.id}/pdf`}>
                            Download PDF
                        </Button>
                        <Link href="/movings">Back to Movings</Link>
                    </Space>
                </Space>

                <Card>
                    <Descriptions column={2}>
                        <Descriptions.Item label="Transferred at">
                            {new Date(transfer.transferred_at).toLocaleString()}
                        </Descriptions.Item>
                        <Descriptions.Item label="By">{transfer.user?.name}</Descriptions.Item>
                        <Descriptions.Item label="From project">{transfer.from_project_code ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="To project">{transfer.to_project_code ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="From department">
                            {transfer.from_department?.department_name ?? '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="To department">
                            {transfer.to_department?.department_name ?? '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="Notes" span={2}>
                            {transfer.notes ?? '—'}
                        </Descriptions.Item>
                    </Descriptions>
                </Card>

                <Card title="Transfer Lines">
                    <ProTable
                        rowKey="id"
                        columns={columns}
                        dataSource={transfer.lines}
                        search={false}
                        options={false}
                        pagination={false}
                    />
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
