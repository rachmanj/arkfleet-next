import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, Input, Modal, Select, Space, Tag, Typography } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

function expiryTag(date) {
    if (!date) return <Tag>—</Tag>;
    const expiry = new Date(date);
    const today = new Date();
    if (expiry < today) return <Tag color="red">Expired</Tag>;
    const days = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
    if (days <= 30) return <Tag color="orange">{date} ({days}d)</Tag>;
    return <Tag color="green">{date}</Tag>;
}

export default function DocumentsIndex() {
    const { documents, documentTypes, equipmentOptions, filters } = usePage().props;
    const [form] = Form.useForm();
    const [extendForm] = Form.useForm();
    const [open, setOpen] = useState(false);
    const [extendOpen, setExtendOpen] = useState(false);
    const [selected, setSelected] = useState(null);

    const columns = [
        { title: 'Unit Code', render: (_, r) => r.equipment?.unit_code },
        { title: 'Type', render: (_, r) => r.document_type?.name },
        { title: 'Document No', dataIndex: 'document_number' },
        { title: 'Issued', dataIndex: 'issued_date' },
        { title: 'Expiry', dataIndex: 'expiry_date', render: (v) => expiryTag(v) },
        { title: 'Extended', dataIndex: 'extend_count' },
        {
            title: 'Actions',
            render: (_, record) => (
                <Space>
                    <Button
                        type="link"
                        onClick={() => {
                            setSelected(record);
                            extendForm.setFieldsValue({ extend_days: 30 });
                            setExtendOpen(true);
                        }}
                    >
                        Extend
                    </Button>
                    <Button danger type="link" onClick={() => router.delete(`/documents/${record.id}`)}>
                        Delete
                    </Button>
                </Space>
            ),
        },
    ];

    const submit = (values) => {
        const formData = new FormData();
        Object.entries(values).forEach(([key, value]) => {
            if (value !== undefined && value !== null && key !== 'file') {
                formData.append(key, value);
            }
        });
        if (values.file?.[0]?.originFileObj) {
            formData.append('file', values.file[0].originFileObj);
        }
        router.post('/documents', formData, {
            forceFormData: true,
            onSuccess: () => setOpen(false),
        });
    };

    const extend = (values) => {
        router.post(`/documents/${selected.id}/extend`, values, {
            onSuccess: () => setExtendOpen(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Documents" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Equipment Documents
                    </Typography.Title>
                    <Button type="primary" icon={<PlusOutlined />} onClick={() => { form.resetFields(); setOpen(true); }}>
                        Add Document
                    </Button>
                </Space>

                <Space wrap>
                    <Input.Search
                        placeholder="Search"
                        defaultValue={filters?.search}
                        onSearch={(value) =>
                            router.get('/documents', { ...filters, search: value || undefined }, { preserveState: true })
                        }
                        allowClear
                        style={{ width: 260 }}
                    />
                    <Select
                        allowClear
                        placeholder="Status filter"
                        style={{ width: 180 }}
                        defaultValue={filters?.status}
                        options={[
                            { value: 'expiring', label: 'Expiring (30d)' },
                            { value: 'expired', label: 'Expired' },
                        ]}
                        onChange={(value) =>
                            router.get('/documents', { ...filters, status: value || undefined }, { preserveState: true })
                        }
                    />
                </Space>

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
                            router.get('/documents', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>

            <Modal title="Add Document" open={open} onCancel={() => setOpen(false)} onOk={() => form.submit()} width={640}>
                <Form form={form} layout="vertical" onFinish={submit}>
                    <Form.Item name="equipment_id" label="Equipment" rules={[{ required: true }]}>
                        <Select
                            showSearch
                            optionFilterProp="label"
                            options={equipmentOptions.map((e) => ({
                                value: e.id,
                                label: e.unit_code,
                            }))}
                        />
                    </Form.Item>
                    <Form.Item name="document_type_id" label="Document Type" rules={[{ required: true }]}>
                        <Select options={documentTypes.map((t) => ({ value: t.id, label: t.name }))} />
                    </Form.Item>
                    <Form.Item name="document_number" label="Document Number">
                        <Input />
                    </Form.Item>
                    <Form.Item name="issued_date" label="Issued Date">
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="expiry_date" label="Expiry Date">
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="notes" label="Notes">
                        <Input.TextArea rows={2} />
                    </Form.Item>
                    <Form.Item name="file" label="Attachment" valuePropName="fileList" getValueFromEvent={(e) => e?.fileList}>
                        <input type="file" />
                    </Form.Item>
                </Form>
            </Modal>

            <Modal title="Extend Expiry" open={extendOpen} onCancel={() => setExtendOpen(false)} onOk={() => extendForm.submit()}>
                <Form form={extendForm} layout="vertical" onFinish={extend}>
                    <Form.Item name="extend_days" label="Extend by (days)" rules={[{ required: true }]}>
                        <Input type="number" min={1} />
                    </Form.Item>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    );
}
