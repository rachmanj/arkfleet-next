import { Head, Link, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Col, Descriptions, Input, Row, Space, Tag, Typography } from 'antd';
import { EditOutlined, PlusOutlined, SendOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

function formatDate(value) {
    return value ? dayjs(value).format('DD MMM YYYY') : '—';
}

export default function MovingsAddEquipment() {
    const { moving, cartItems, availableEquipment, filters } = usePage().props;

    const equipmentColumns = [
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Description', dataIndex: 'description' },
        { title: 'Project', dataIndex: 'project_code' },
        { title: 'Department', render: (_, record) => record.department?.department_name ?? '—' },
        {
            title: 'Action',
            render: (_, record) => (
                <Button
                    size="small"
                    icon={<PlusOutlined />}
                    onClick={() =>
                        router.post(
                            `/movings/${moving.id}/cart`,
                            { equipment_id: record.id },
                            { preserveScroll: true },
                        )
                    }
                >
                    Add to cart
                </Button>
            ),
        },
    ];

    const cartColumns = [
        { title: 'Unit Code', render: (_, record) => record.equipment?.unit_code },
        { title: 'Description', render: (_, record) => record.equipment?.description ?? '—' },
        { title: 'To Project', dataIndex: 'to_project_code' },
        { title: 'To Department', render: (_, record) => record.to_department?.department_name ?? '—' },
        {
            title: 'Action',
            render: (_, record) => (
                <Button
                    danger
                    size="small"
                    onClick={() =>
                        router.delete(`/movings/${moving.id}/cart/${record.id}`, { preserveScroll: true })
                    }
                >
                    Remove
                </Button>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Add Equipment — ${moving.ipa_no}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ justifyContent: 'space-between', width: '100%' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Add Equipment — {moving.ipa_no}
                    </Typography.Title>
                    <Link href="/movings">Back to list</Link>
                </Space>

                <Card
                    title="IPA Summary"
                    extra={
                        <Link href={`/movings/${moving.id}/edit`}>
                            <Button size="small" icon={<EditOutlined />}>
                                Edit header
                            </Button>
                        </Link>
                    }
                >
                    <Descriptions column={{ xs: 1, sm: 2, md: 3 }} size="small">
                        <Descriptions.Item label="IPA No">{moving.ipa_no}</Descriptions.Item>
                        <Descriptions.Item label="IPA Date">{formatDate(moving.ipa_date)}</Descriptions.Item>
                        <Descriptions.Item label="Status">
                            <Tag color="gold">DRAFT</Tag>
                        </Descriptions.Item>
                        <Descriptions.Item label="From Project">{moving.from_project_code ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="To Project">{moving.to_project_code ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="From Department">
                            {moving.from_department?.department_name ?? '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="To Department">
                            {moving.to_department?.department_name ?? '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="Kepada Yth." span={2}>
                            {[moving.tujuan_row_1, moving.tujuan_row_2].filter(Boolean).join(' / ') || '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="CC" span={3}>
                            {[moving.cc_row_1, moving.cc_row_2, moving.cc_row_3].filter(Boolean).join(' / ') || '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="Remarks" span={3}>
                            {moving.notes ?? '—'}
                        </Descriptions.Item>
                    </Descriptions>
                </Card>

                <Row gutter={16}>
                    <Col xs={24} lg={14}>
                        <Card title="Available Equipment">
                            <Input.Search
                                placeholder="Search unit code or description"
                                defaultValue={filters?.search}
                                onSearch={(value) =>
                                    router.get(
                                        `/movings/${moving.id}/equipment`,
                                        { search: value || undefined },
                                        { preserveState: true },
                                    )
                                }
                                style={{ marginBottom: 16, maxWidth: 320 }}
                                allowClear
                            />
                            <ProTable
                                rowKey="id"
                                columns={equipmentColumns}
                                dataSource={availableEquipment.data}
                                search={false}
                                options={false}
                                pagination={{
                                    current: availableEquipment.current_page,
                                    pageSize: availableEquipment.per_page,
                                    total: availableEquipment.total,
                                    onChange: (page) =>
                                        router.get(
                                            `/movings/${moving.id}/equipment`,
                                            { ...filters, page },
                                            { preserveState: true },
                                        ),
                                }}
                            />
                        </Card>
                    </Col>

                    <Col xs={24} lg={10}>
                        <Card title={`Cart (${cartItems.length})`}>
                            <ProTable
                                rowKey="id"
                                columns={cartColumns}
                                dataSource={cartItems}
                                search={false}
                                options={false}
                                pagination={false}
                            />

                            <Button
                                type="primary"
                                icon={<SendOutlined />}
                                block
                                style={{ marginTop: 16 }}
                                disabled={cartItems.length === 0}
                                onClick={() => router.post(`/movings/${moving.id}/submit`)}
                            >
                                Submit IPA
                            </Button>
                        </Card>
                    </Col>
                </Row>
            </Space>
        </AuthenticatedLayout>
    );
}
