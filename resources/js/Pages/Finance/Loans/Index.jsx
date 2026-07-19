import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, Input, InputNumber, Modal, Select, Space, Tag, Typography, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

const statusColors = {
    draft: 'default',
    active: 'processing',
    locked: 'warning',
    completed: 'success',
};

export default function LoansIndex() {
    const { loans, filters, vendors, departments, projects, defaults, flash } = usePage().props;
    const [form] = Form.useForm();
    const [open, setOpen] = useState(false);

    if (flash?.success) {
        message.success(flash.success);
    }

    const columns = [
        { title: 'Contract', dataIndex: 'contract_number' },
        { title: 'Vendor', dataIndex: 'vendor_card_code' },
        {
            title: 'Principal',
            dataIndex: 'principal_amount',
            render: (v) => Number(v).toLocaleString(),
        },
        { title: 'Term', dataIndex: 'term_months', render: (v) => `${v} mo` },
        { title: 'Installments', dataIndex: 'installments_count' },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (v) => <Tag color={statusColors[v]}>{v}</Tag>,
        },
        {
            title: 'Actions',
            render: (_, record) => (
                <Button type="link" onClick={() => router.get(`/loans/${record.id}`)}>
                    Open
                </Button>
            ),
        },
    ];

    const submit = () => {
        form.validateFields().then((values) => {
            router.post('/loans', values, {
                onSuccess: () => setOpen(false),
            });
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Loans" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Loan Administration
                    </Typography.Title>
                    <Button type="primary" icon={<PlusOutlined />} onClick={() => setOpen(true)}>
                        New Loan
                    </Button>
                </Space>

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={loans.data}
                    search={false}
                    options={false}
                    toolBarRender={() => [
                        <Input.Search
                            key="search"
                            placeholder="Search contract or vendor"
                            defaultValue={filters?.search}
                            onSearch={(value) =>
                                router.get('/loans', { search: value || undefined }, { preserveState: true })
                            }
                            allowClear
                        />,
                    ]}
                    pagination={{
                        current: loans.current_page,
                        pageSize: loans.per_page,
                        total: loans.total,
                        onChange: (page) =>
                            router.get('/loans', { ...filters, page }, { preserveState: true }),
                    }}
                />
            </Space>

            <Modal title="New Loan" open={open} onCancel={() => setOpen(false)} onOk={submit} width={640}>
                <Form
                    form={form}
                    layout="vertical"
                    initialValues={{
                        currency: defaults?.currency ?? 'IDR',
                        principal_gl: defaults?.principal_gl,
                        interest_gl: defaults?.interest_gl,
                        tax_code: defaults?.tax_code,
                        term_months: 12,
                    }}
                >
                    <Form.Item name="vendor_card_code" label="Vendor (CardCode)" rules={[{ required: true }]}>
                        <Select
                            showSearch
                            optionFilterProp="label"
                            options={vendors?.map((v) => ({
                                value: v.card_code,
                                label: `${v.card_code} — ${v.card_name}`,
                            }))}
                        />
                    </Form.Item>
                    <Form.Item name="contract_number" label="Contract Number" rules={[{ required: true }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item name="principal_amount" label="Principal Amount" rules={[{ required: true }]}>
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="term_months" label="Term (months)" rules={[{ required: true }]}>
                        <InputNumber style={{ width: '100%' }} min={1} />
                    </Form.Item>
                    <Form.Item name="interest_rate" label="Interest Rate (%)">
                        <InputNumber style={{ width: '100%' }} min={0} step={0.01} />
                    </Form.Item>
                    <Form.Item name="department_id" label="Department">
                        <Select
                            allowClear
                            options={departments?.map((d) => ({ value: d.id, label: d.department_name }))}
                        />
                    </Form.Item>
                    <Form.Item name="project_code" label="Project">
                        <Select
                            allowClear
                            options={projects?.map((p) => ({ value: p.code, label: `${p.code} — ${p.name}` }))}
                        />
                    </Form.Item>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    );
}
