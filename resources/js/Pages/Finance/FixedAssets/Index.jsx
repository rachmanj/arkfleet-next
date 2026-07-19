import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, Input, InputNumber, Modal, Select, Space, Tag, Typography, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

const statusColors = {
    active: 'green',
    fully_depreciated: 'gold',
    disposed: 'red',
};

export default function FixedAssetsIndex() {
    const { assets, assetClasses, equipmentOptions, filters, flash } = usePage().props;
    const [form] = Form.useForm();
    const [disposeForm] = Form.useForm();
    const [open, setOpen] = useState(false);
    const [disposeOpen, setDisposeOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [disposing, setDisposing] = useState(null);

    if (flash?.success) {
        message.success(flash.success);
    }

    const openCreate = () => {
        setEditing(null);
        form.resetFields();
        setOpen(true);
    };

    const openEdit = (record) => {
        setEditing(record);
        form.setFieldsValue(record);
        setOpen(true);
    };

    const openDispose = (record) => {
        setDisposing(record);
        disposeForm.resetFields();
        disposeForm.setFieldsValue({ disposal_type: 'sale' });
        setDisposeOpen(true);
    };

    const submit = () => {
        form.validateFields().then((values) => {
            const equipment = equipmentOptions.find((e) => e.id === values.equipment_id);
            const payload = {
                ...values,
                acquisition_cost: values.acquisition_cost ?? equipment?.acquisition_cost,
                acquisition_date: values.acquisition_date ?? equipment?.acquisition_date,
                in_service_date: values.in_service_date ?? equipment?.in_service_date,
                salvage_value: values.salvage_value ?? equipment?.salvage_value,
            };

            if (editing) {
                router.put(`/fixed-assets/${editing.id}`, payload, {
                    preserveScroll: true,
                    onSuccess: () => setOpen(false),
                });
            } else {
                router.post('/fixed-assets', payload, {
                    preserveScroll: true,
                    onSuccess: () => setOpen(false),
                });
            }
        });
    };

    const submitDispose = () => {
        disposeForm.validateFields().then((values) => {
            router.post(`/fixed-assets/${disposing.id}/dispose`, values, {
                preserveScroll: true,
                onSuccess: () => setDisposeOpen(false),
            });
        });
    };

    const columns = [
        { title: 'Unit Code', render: (_, r) => r.equipment?.unit_code },
        { title: 'Asset Class', render: (_, r) => r.asset_class?.name },
        {
            title: 'Acquisition Cost',
            dataIndex: 'acquisition_cost',
            render: (v) => Number(v).toLocaleString(),
        },
        { title: 'In Service', dataIndex: 'in_service_date' },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (v) => <Tag color={statusColors[v] ?? 'default'}>{v}</Tag>,
        },
        {
            title: 'Actions',
            render: (_, record) => (
                <Space>
                    <Button type="link" onClick={() => router.get(`/fixed-assets/${record.id}/schedule`)}>
                        Schedule
                    </Button>
                    <Button type="link" onClick={() => openEdit(record)}>
                        Edit
                    </Button>
                    {record.status === 'active' && (
                        <Button type="link" danger onClick={() => openDispose(record)}>
                            Dispose
                        </Button>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Fixed Assets" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Fixed Assets
                    </Typography.Title>
                    <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                        Capitalize Asset
                    </Button>
                </Space>

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={assets.data}
                    search={false}
                    options={false}
                    toolBarRender={() => [
                        <Input.Search
                            key="search"
                            placeholder="Search unit no"
                            defaultValue={filters?.search}
                            onSearch={(value) =>
                                router.get('/fixed-assets', { search: value || undefined }, { preserveState: true })
                            }
                            allowClear
                        />,
                    ]}
                    pagination={{
                        current: assets.current_page,
                        pageSize: assets.per_page,
                        total: assets.total,
                        onChange: (page) =>
                            router.get('/fixed-assets', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>

            <Modal
                title={editing ? 'Edit Fixed Asset' : 'Capitalize Fixed Asset'}
                open={open}
                onCancel={() => setOpen(false)}
                onOk={submit}
                width={640}
            >
                <Form form={form} layout="vertical">
                    {!editing && (
                        <Form.Item name="equipment_id" label="Equipment" rules={[{ required: true }]}>
                            <Select
                                showSearch
                                optionFilterProp="label"
                                options={equipmentOptions.map((e) => ({
                                    value: e.id,
                                    label: e.unit_code,
                                }))}
                                onChange={(id) => {
                                    const eq = equipmentOptions.find((e) => e.id === id);
                                    if (eq) {
                                        form.setFieldsValue({
                                            acquisition_cost: eq.acquisition_cost,
                                            acquisition_date: eq.acquisition_date,
                                            in_service_date: eq.in_service_date,
                                            salvage_value: eq.salvage_value,
                                        });
                                    }
                                }}
                            />
                        </Form.Item>
                    )}
                    <Form.Item name="asset_class_id" label="Asset Class" rules={[{ required: true }]}>
                        <Select
                            options={assetClasses.map((c) => ({ value: c.id, label: `${c.code} — ${c.name}` }))}
                        />
                    </Form.Item>
                    <Form.Item name="acquisition_cost" label="Acquisition Cost" rules={[{ required: true }]}>
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="in_service_date" label="In Service Date" rules={[{ required: true }]}>
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="salvage_value" label="Salvage Value">
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                </Form>
            </Modal>

            <Modal
                title={`Dispose ${disposing?.equipment?.unit_code ?? 'Asset'}`}
                open={disposeOpen}
                onCancel={() => setDisposeOpen(false)}
                onOk={submitDispose}
            >
                <Form form={disposeForm} layout="vertical">
                    <Form.Item name="disposal_date" label="Disposal Date" rules={[{ required: true }]}>
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="disposal_type" label="Type" rules={[{ required: true }]}>
                        <Select
                            options={[
                                { value: 'sale', label: 'Sale' },
                                { value: 'scrap', label: 'Scrap' },
                                { value: 'writeoff', label: 'Write-off' },
                            ]}
                        />
                    </Form.Item>
                    <Form.Item name="proceeds" label="Proceeds">
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="notes" label="Notes">
                        <Input.TextArea rows={3} />
                    </Form.Item>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    );
}
