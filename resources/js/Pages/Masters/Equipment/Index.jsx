import { Head, Link, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Col, Form, Input, InputNumber, Modal, Row, Select, Space, Tag, Typography, message } from 'antd';
import { PlusOutlined, SyncOutlined, ToolOutlined } from '@ant-design/icons';
import { useEffect, useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';
import { resolveStatusTags } from '../../../utils/equipmentStatus';

function formatMoney(value) {
    const num = Number(value);
    return value != null && value !== '' && !Number.isNaN(num) ? num.toLocaleString() : '—';
}

function parseActiveFilter(value) {
    if (value === undefined || value === null || value === '') {
        return undefined;
    }

    return value === true || value === 1 || value === '1';
}

function buildFilterParams(values) {
    const params = {};

    Object.entries(values).forEach(([key, value]) => {
        if (key === 'is_active') {
            if (value === true || value === false) {
                params[key] = value;
            }

            return;
        }

        if (value !== undefined && value !== null && value !== '') {
            params[key] = value;
        }
    });

    return params;
}

export default function EquipmentIndex() {
    const { equipment, filters, projects, departments, rfuCandidates, bdCandidates, flash } = usePage().props;
    const [form] = Form.useForm();
    const [filterForm] = Form.useForm();
    const [rfuForm] = Form.useForm();
    const [bdForm] = Form.useForm();
    const [open, setOpen] = useState(false);
    const [rfuOpen, setRfuOpen] = useState(false);
    const [bdOpen, setBdOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    useEffect(() => {
        filterForm.setFieldsValue({
            unit_code: filters.unit_code ?? undefined,
            description: filters.description ?? undefined,
            project_code: filters.project_code ?? undefined,
            acquisition_cost_min: filters.acquisition_cost_min ?? undefined,
            acquisition_cost_max: filters.acquisition_cost_max ?? undefined,
            is_active: parseActiveFilter(filters.is_active),
            status: filters.status ?? undefined,
        });
    }, [filterForm, filters]);

    useEffect(() => {
        if (flash?.success) {
            message.success(flash.success);
        }

        if (flash?.error) {
            message.error(flash.error);
        }
    }, [flash]);

    const applyFilters = (values) => {
        router.get('/equipment', buildFilterParams(values), { preserveState: true });
    };

    const resetFilters = () => {
        filterForm.resetFields();
        router.get('/equipment', {}, { preserveState: true });
    };

    const openCreate = () => {
        setEditing(null);
        form.resetFields();
        form.setFieldsValue({ is_active: true });
        setOpen(true);
    };

    const openEdit = (record) => {
        setEditing(record);
        form.setFieldsValue(record);
        setOpen(true);
    };

    const openRfuModal = () => {
        rfuForm.resetFields();
        setRfuOpen(true);
    };

    const openBdModal = () => {
        bdForm.resetFields();
        setBdOpen(true);
    };

    const submit = () => {
        form.validateFields().then((values) => {
            if (editing) {
                router.put(`/equipment/${editing.id}`, values, {
                    preserveScroll: true,
                    onSuccess: () => setOpen(false),
                });
            } else {
                router.post('/equipment', values, {
                    preserveScroll: true,
                    onSuccess: () => setOpen(false),
                });
            }
        });
    };

    const submitRfu = () => {
        rfuForm.validateFields().then((values) => {
            router.post('/equipment/update-rfu', values, {
                preserveScroll: true,
                onSuccess: () => setRfuOpen(false),
            });
        });
    };

    const submitBd = () => {
        bdForm.validateFields().then((values) => {
            router.post('/equipment/update-bd', values, {
                preserveScroll: true,
                onSuccess: () => setBdOpen(false),
            });
        });
    };

    const columns = [
        {
            title: '#',
            key: 'index',
            width: 60,
            render: (_, __, index) => (equipment.current_page - 1) * equipment.per_page + index + 1,
        },
        { title: 'Unit Code', dataIndex: 'unit_code', key: 'unit_code' },
        { title: 'Description', dataIndex: 'description', key: 'description', ellipsis: true },
        { title: 'Project', dataIndex: 'project_code', key: 'project_code' },
        {
            title: 'Acquisition Cost',
            dataIndex: 'acquisition_cost',
            render: formatMoney,
        },
        {
            title: 'Status',
            key: 'status',
            render: (_, record) => <Space size={4}>{resolveStatusTags(record)}</Space>,
        },
        {
            title: 'Active',
            dataIndex: 'is_active',
            render: (value) => <Tag color={value ? 'green' : 'default'}>{value ? 'Yes' : 'No'}</Tag>,
        },
        {
            title: 'Actions',
            render: (_, record) => (
                <Space>
                    <Link href={`/equipment/${record.id}`}>
                        <Button type="link">View</Button>
                    </Link>
                    <Button type="link" onClick={() => openEdit(record)}>
                        Edit
                    </Button>
                </Space>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Equipment" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between', flexWrap: 'wrap' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Equipment Register
                    </Typography.Title>
                    <Space wrap>
                        <Button icon={<SyncOutlined />} onClick={openRfuModal}>
                            Update RFU Units
                        </Button>
                        <Button icon={<ToolOutlined />} onClick={openBdModal}>
                            Update B/D Units
                        </Button>
                        <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                            Add Equipment
                        </Button>
                    </Space>
                </Space>

                <Card size="small" title="Filters">
                    <Form form={filterForm} layout="vertical" onFinish={applyFilters}>
                        <Row gutter={[16, 0]}>
                            <Col xs={24} sm={12} md={8} lg={6}>
                                <Form.Item name="unit_code" label="Unit Code">
                                    <Input allowClear placeholder="Contains…" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} sm={12} md={8} lg={6}>
                                <Form.Item name="description" label="Description">
                                    <Input allowClear placeholder="Contains…" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} sm={12} md={8} lg={6}>
                                <Form.Item name="project_code" label="Project">
                                    <Select
                                        allowClear
                                        showSearch
                                        placeholder="All projects"
                                        optionFilterProp="label"
                                        options={projects.map((p) => ({
                                            value: p.code,
                                            label: `${p.code} — ${p.name}`,
                                        }))}
                                    />
                                </Form.Item>
                            </Col>
                            <Col xs={24} sm={12} md={8} lg={6}>
                                <Form.Item name="status" label="Status">
                                    <Select
                                        allowClear
                                        placeholder="All"
                                        options={[
                                            { value: 'rfu', label: 'RFU' },
                                            { value: 'bd', label: 'B/D' },
                                        ]}
                                    />
                                </Form.Item>
                            </Col>
                            <Col xs={24} sm={12} md={8} lg={6}>
                                <Form.Item name="is_active" label="Active">
                                    <Select
                                        allowClear
                                        placeholder="All"
                                        options={[
                                            { value: true, label: 'Yes' },
                                            { value: false, label: 'No' },
                                        ]}
                                    />
                                </Form.Item>
                            </Col>
                            <Col xs={24} sm={12} md={8} lg={6}>
                                <Form.Item name="acquisition_cost_min" label="Acquisition Cost (min)">
                                    <InputNumber style={{ width: '100%' }} min={0} placeholder="Min" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} sm={12} md={8} lg={6}>
                                <Form.Item name="acquisition_cost_max" label="Acquisition Cost (max)">
                                    <InputNumber style={{ width: '100%' }} min={0} placeholder="Max" />
                                </Form.Item>
                            </Col>
                        </Row>
                        <Space>
                            <Button type="primary" htmlType="submit">
                                Apply Filters
                            </Button>
                            <Button onClick={resetFilters}>Reset</Button>
                        </Space>
                    </Form>
                </Card>

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
                            router.get('/equipment', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>

            <Modal
                title="Update to RFU"
                open={rfuOpen}
                onCancel={() => setRfuOpen(false)}
                onOk={submitRfu}
                okText="Save"
                width={640}
                destroyOnClose
            >
                <Form form={rfuForm} layout="vertical">
                    <Form.Item
                        name="equipments"
                        label="Select equipments to update to RFU"
                        rules={[{ required: true, message: 'Select at least one equipment' }]}
                    >
                        <Select
                            mode="multiple"
                            allowClear
                            showSearch
                            placeholder="Select equipments"
                            optionFilterProp="label"
                            options={rfuCandidates ?? []}
                        />
                    </Form.Item>
                </Form>
            </Modal>

            <Modal
                title="Update to B/D"
                open={bdOpen}
                onCancel={() => setBdOpen(false)}
                onOk={submitBd}
                okText="Save"
                width={640}
                destroyOnClose
            >
                <Form form={bdForm} layout="vertical">
                    <Form.Item
                        name="equipments"
                        label="Select equipments to update to B/D"
                        rules={[{ required: true, message: 'Select at least one equipment' }]}
                    >
                        <Select
                            mode="multiple"
                            allowClear
                            showSearch
                            placeholder="Select equipments"
                            optionFilterProp="label"
                            options={bdCandidates ?? []}
                        />
                    </Form.Item>
                </Form>
            </Modal>

            <Modal
                title={editing ? 'Edit Equipment' : 'Add Equipment'}
                open={open}
                onCancel={() => setOpen(false)}
                onOk={submit}
                width={720}
                destroyOnClose
            >
                <Form form={form} layout="vertical">
                    <Form.Item name="unit_code" label="Unit Code" rules={[{ required: true }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item name="description" label="Description">
                        <Input.TextArea rows={2} />
                    </Form.Item>
                    <Form.Item name="serial_no" label="Serial No">
                        <Input />
                    </Form.Item>
                    <Form.Item name="chasis_no" label="Chassis No">
                        <Input />
                    </Form.Item>
                    <Form.Item name="engine_model" label="Engine Model">
                        <Input />
                    </Form.Item>
                    <Form.Item name="machine_no" label="Machine No">
                        <Input />
                    </Form.Item>
                    <Form.Item name="nomor_polisi" label="Nomor Polisi">
                        <Input />
                    </Form.Item>
                    <Form.Item name="bahan_bakar" label="Bahan Bakar">
                        <Input />
                    </Form.Item>
                    <Form.Item name="warna" label="Warna">
                        <Input />
                    </Form.Item>
                    <Form.Item name="capacity" label="Capacity">
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="remarks" label="Remarks">
                        <Input.TextArea rows={2} />
                    </Form.Item>
                    <Form.Item name="department_id" label="Department">
                        <Select
                            allowClear
                            options={departments.map((d) => ({ value: d.id, label: d.department_name }))}
                        />
                    </Form.Item>
                    <Form.Item name="project_code" label="Project">
                        <Select
                            allowClear
                            showSearch
                            optionFilterProp="label"
                            options={projects.map((p) => ({ value: p.code, label: `${p.code} — ${p.name}` }))}
                        />
                    </Form.Item>
                    <Form.Item name="acquisition_cost" label="Acquisition Cost">
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="acquisition_date" label="Acquisition Date">
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="in_service_date" label="In-Service Date">
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="salvage_value" label="Salvage Value">
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="useful_life_months" label="Useful Life (months)">
                        <InputNumber style={{ width: '100%' }} min={1} />
                    </Form.Item>
                    <Form.Item name="is_rfu" label="RFU">
                        <Select
                            options={[
                                { value: true, label: 'Yes' },
                                { value: false, label: 'No' },
                            ]}
                        />
                    </Form.Item>
                    <Form.Item name="is_active" label="Active">
                        <Select
                            options={[
                                { value: true, label: 'Yes' },
                                { value: false, label: 'No' },
                            ]}
                        />
                    </Form.Item>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    );
}
