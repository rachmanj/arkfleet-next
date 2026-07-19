import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Col, Form, Input, Row, Select, Space, Typography } from 'antd';
import { PlusOutlined, SendOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function MovingsIndex() {
    const { cartItems, availableEquipment, transfers, projects, departments, filters } = usePage().props;
    const [submitForm] = Form.useForm();

    const equipmentColumns = [
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Unit Code', dataIndex: 'unit_code' },
        { title: 'Project', dataIndex: 'project_code' },
        { title: 'Department', render: (_, r) => r.department?.department_name ?? '—' },
        {
            title: 'Action',
            render: (_, record) => (
                <Button
                    size="small"
                    icon={<PlusOutlined />}
                    onClick={() =>
                        router.post('/movings/cart', { equipment_id: record.id }, { preserveScroll: true })
                    }
                >
                    Add to cart
                </Button>
            ),
        },
    ];

    const cartColumns = [
        { title: 'Unit Code', render: (_, r) => r.equipment?.unit_code },
        { title: 'To Project', dataIndex: 'to_project_code' },
        { title: 'To Department', render: (_, r) => r.to_department?.department_name ?? '—' },
        {
            title: 'Action',
            render: (_, record) => (
                <Button danger size="small" onClick={() => router.delete(`/movings/cart/${record.id}`, { preserveScroll: true })}>
                    Remove
                </Button>
            ),
        },
    ];

    const historyColumns = [
        { title: 'Transfer No', dataIndex: 'transfer_number' },
        { title: 'Date', dataIndex: 'transferred_at', render: (v) => new Date(v).toLocaleString() },
        { title: 'To Project', dataIndex: 'to_project_code' },
        { title: 'To Department', render: (_, r) => r.to_department?.department_name ?? '—' },
        { title: 'Lines', dataIndex: 'line_count' },
        {
            title: 'Action',
            render: (_, record) => (
                <Button type="link" onClick={() => router.get(`/movings/transfers/${record.id}`)}>
                    View
                </Button>
            ),
        },
    ];

    const submitTransfer = (values) => {
        router.post('/movings/submit', values);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Movings / IPA" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Typography.Title level={4} style={{ margin: 0 }}>
                    Movings / IPA Transfer
                </Typography.Title>

                <Row gutter={16}>
                    <Col xs={24} lg={14}>
                        <Card title="Available Equipment">
                            <Input.Search
                                placeholder="Search unit no"
                                defaultValue={filters?.search}
                                onSearch={(value) =>
                                    router.get('/movings', { search: value || undefined }, { preserveState: true })
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
                                        router.get('/movings', { ...filters, page }, { preserveState: true }),
                                }}
                            />
                        </Card>
                    </Col>

                    <Col xs={24} lg={10}>
                        <Card title={`Transfer Cart (${cartItems.length})`}>
                            <ProTable
                                rowKey="id"
                                columns={cartColumns}
                                dataSource={cartItems}
                                search={false}
                                options={false}
                                pagination={false}
                            />

                            {cartItems.length > 0 && (
                                <Form form={submitForm} layout="vertical" onFinish={submitTransfer} style={{ marginTop: 16 }}>
                                    <Form.Item name="to_project_code" label="To Project" rules={[{ required: true }]}>
                                        <Select
                                            showSearch
                                            optionFilterProp="label"
                                            options={projects.map((p) => ({
                                                value: p.code,
                                                label: `${p.code} — ${p.name}`,
                                            }))}
                                        />
                                    </Form.Item>
                                    <Form.Item name="to_department_id" label="To Department">
                                        <Select
                                            allowClear
                                            options={departments.map((d) => ({
                                                value: d.id,
                                                label: d.department_name,
                                            }))}
                                        />
                                    </Form.Item>
                                    <Form.Item name="notes" label="Notes">
                                        <Input.TextArea rows={2} />
                                    </Form.Item>
                                    <Button type="primary" htmlType="submit" icon={<SendOutlined />} block>
                                        Submit Transfer
                                    </Button>
                                </Form>
                            )}
                        </Card>
                    </Col>
                </Row>

                <Card title="Recent Transfers">
                    <ProTable
                        rowKey="id"
                        columns={historyColumns}
                        dataSource={transfers.data}
                        search={false}
                        options={false}
                        pagination={{
                            current: transfers.current_page,
                            pageSize: transfers.per_page,
                            total: transfers.total,
                            onChange: (page) =>
                                router.get('/movings', { transfers_page: page }, { preserveState: true }),
                        }}
                    />
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
