import { Head, Link, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Descriptions, Space, Tag, Typography } from 'antd';
import { CheckOutlined, FilePdfOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

const STATUS_COLORS = {
    DRAFT: 'gold',
    SUBMITTED: 'blue',
    APPROVED: 'green',
};

function formatDate(value) {
    return value ? dayjs(value).format('DD MMM YYYY') : '—';
}

function formatDateTime(value) {
    return value ? dayjs(value).format('DD MMM YYYY HH:mm') : '—';
}

export default function MovingsShow() {
    const { moving } = usePage().props;

    const columns = [
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Description', render: (_, record) => record.equipment?.description ?? '—' },
        { title: 'From Project', dataIndex: 'from_project_code' },
        { title: 'To Project', dataIndex: 'to_project_code' },
        { title: 'From Dept', render: (_, record) => record.from_department?.department_name ?? '—' },
        { title: 'To Dept', render: (_, record) => record.to_department?.department_name ?? '—' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={moving.ipa_no} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ justifyContent: 'space-between', width: '100%' }}>
                    <Space>
                        <Typography.Title level={4} style={{ margin: 0 }}>
                            {moving.ipa_no}
                        </Typography.Title>
                        <Tag color={STATUS_COLORS[moving.status] ?? 'default'}>{moving.status}</Tag>
                    </Space>
                    <Space>
                        {moving.status === 'SUBMITTED' && (
                            <Button
                                type="default"
                                icon={<CheckOutlined />}
                                onClick={() => router.post(`/movings/${moving.id}/approve`)}
                            >
                                Approve
                            </Button>
                        )}
                        <Button type="primary" icon={<FilePdfOutlined />} href={`/movings/${moving.id}/pdf`}>
                            Download PDF
                        </Button>
                        <Link href="/movings">Back to Movings</Link>
                    </Space>
                </Space>

                <Card>
                    <Descriptions column={2}>
                        <Descriptions.Item label="IPA No">{moving.ipa_no}</Descriptions.Item>
                        <Descriptions.Item label="IPA Date">{formatDate(moving.ipa_date)}</Descriptions.Item>
                        <Descriptions.Item label="Transferred at">
                            {formatDateTime(moving.transferred_at)}
                        </Descriptions.Item>
                        <Descriptions.Item label="Created by">{moving.user?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="From project">{moving.from_project_code ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="To project">{moving.to_project_code ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="From department">
                            {moving.from_department?.department_name ?? '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="To department">
                            {moving.to_department?.department_name ?? '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="Kepada Yth." span={2}>
                            {[moving.tujuan_row_1, moving.tujuan_row_2].filter(Boolean).join(' / ') || '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="CC" span={2}>
                            {[moving.cc_row_1, moving.cc_row_2, moving.cc_row_3].filter(Boolean).join(' / ') || '—'}
                        </Descriptions.Item>
                        {moving.approved_at && (
                            <>
                                <Descriptions.Item label="Approved by">
                                    {moving.approved_by?.name ?? '—'}
                                </Descriptions.Item>
                                <Descriptions.Item label="Approved at">
                                    {formatDateTime(moving.approved_at)}
                                </Descriptions.Item>
                            </>
                        )}
                        <Descriptions.Item label="Remarks" span={2}>
                            {moving.notes ?? '—'}
                        </Descriptions.Item>
                    </Descriptions>
                </Card>

                <Card title="Equipment Lines">
                    <ProTable
                        rowKey="id"
                        columns={columns}
                        dataSource={moving.lines}
                        search={false}
                        options={false}
                        pagination={false}
                    />
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
