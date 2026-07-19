import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, Input, InputNumber, Modal, Space, Tag, Typography, Upload, message } from 'antd';
import { UploadOutlined } from '@ant-design/icons';
import { useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

const statusColors = {
    draft: 'default',
    confirmed: 'processing',
    posted: 'blue',
    paid: 'success',
};

export default function LoanShow() {
    const { loan, sapPostingEnabled, flash, auth } = usePage().props;
    const canPost = auth?.permissions?.includes('sap.post');
    const [editOpen, setEditOpen] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form] = Form.useForm();
    const [confirmForm] = Form.useForm();

    if (flash?.success) {
        message.success(flash.success);
    }
    if (flash?.error) {
        message.error(flash.error);
    }

    const latestDoc = loan.documents?.[0];
    const parsedInstallments = latestDoc?.parsed_data?.installments ?? [];

    const uploadPdf = ({ file }) => {
        const formData = new FormData();
        formData.append('file', file);
        router.post(`/loans/${loan.id}/documents`, formData, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const openEdit = (record) => {
        setEditing(record);
        form.setFieldsValue(record);
        setEditOpen(true);
    };

    const submitEdit = () => {
        form.validateFields().then((values) => {
            router.put(`/loans/${loan.id}/installments/${editing.id}`, values, {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
            });
        });
    };

    const openConfirmSchedule = () => {
        confirmForm.setFieldsValue({
            installments: parsedInstallments.length
                ? parsedInstallments
                : [{ installment_no: 1, principal_amount: 0, interest_amount: 0, due_date: null }],
        });
        setConfirmOpen(true);
    };

    const submitConfirmSchedule = () => {
        confirmForm.validateFields().then((values) => {
            router.post(`/loans/${loan.id}/confirm-schedule`, values, {
                preserveScroll: true,
                onSuccess: () => setConfirmOpen(false),
            });
        });
    };

    const columns = [
        { title: '#', render: (_, r) => `${r.installment_no} / ${r.total_installments}` },
        { title: 'Due', dataIndex: 'due_date' },
        {
            title: 'Principal',
            dataIndex: 'principal_amount',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Interest',
            dataIndex: 'interest_amount',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'Total',
            dataIndex: 'total_amount',
            render: (v) => Number(v).toLocaleString(),
        },
        {
            title: 'SAP Doc',
            render: (_, r) => (r.sap_doc_num ? `#${r.sap_doc_num}` : '—'),
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (v) => <Tag color={statusColors[v]}>{v}</Tag>,
        },
        {
            title: 'Actions',
            render: (_, record) => (
                <Space wrap>
                    {record.status === 'draft' && (
                        <>
                            <Button type="link" onClick={() => openEdit(record)}>
                                Edit
                            </Button>
                            <Button
                                type="link"
                                onClick={() =>
                                    router.post(`/loans/${loan.id}/installments/${record.id}/confirm`)
                                }
                            >
                                Confirm
                            </Button>
                        </>
                    )}
                    {canPost && record.status === 'confirmed' && sapPostingEnabled && (
                        <Button
                            type="link"
                            onClick={() =>
                                router.post(`/loans/${loan.id}/installments/${record.id}/post-ap`)
                            }
                        >
                            Post AP
                        </Button>
                    )}
                    {canPost && record.status === 'posted' && sapPostingEnabled && (
                        <Button
                            type="link"
                            onClick={() =>
                                router.post(`/loans/${loan.id}/installments/${record.id}/post-payment`)
                            }
                        >
                            Pay
                        </Button>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Loan ${loan.contract_number}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        {loan.contract_number} — {loan.vendor_card_code}
                        <Tag style={{ marginLeft: 8 }}>{loan.status}</Tag>
                    </Typography.Title>
                    <Button onClick={() => router.get('/loans')}>Back</Button>
                </Space>

                <Space>
                    <Typography.Text>
                        Principal: {Number(loan.principal_amount).toLocaleString()} {loan.currency}
                    </Typography.Text>
                    <Typography.Text>Term: {loan.term_months} months</Typography.Text>
                    <Typography.Text>
                        GL: {loan.principal_gl} / {loan.interest_gl} (Tax {loan.tax_code})
                    </Typography.Text>
                </Space>

                {!sapPostingEnabled && (
                    <Typography.Text type="secondary">
                        SAP loan posting disabled until UAT (SAP_LOAN_POSTING_ENABLED).
                    </Typography.Text>
                )}

                {loan.schedule_locked_at && (
                    <Typography.Text type="warning">Schedule locked after first SAP post.</Typography.Text>
                )}

                <Space>
                    <Upload accept=".pdf" showUploadList={false} customRequest={uploadPdf}>
                        <Button icon={<UploadOutlined />}>Upload Installment PDF</Button>
                    </Upload>
                    {!loan.schedule_locked_at && (
                        <Button onClick={openConfirmSchedule}>
                            {parsedInstallments.length ? 'Confirm Parsed Schedule' : 'Add Installments'}
                        </Button>
                    )}
                </Space>

                {latestDoc && (
                    <Typography.Paragraph type="secondary">
                        Last upload: {latestDoc.original_filename} — {latestDoc.parsed_data?.message ?? 'stored'}
                    </Typography.Paragraph>
                )}

                <ProTable
                    rowKey="id"
                    columns={columns}
                    dataSource={loan.installments ?? []}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 24 }}
                />
            </Space>

            <Modal title="Edit Installment" open={editOpen} onCancel={() => setEditOpen(false)} onOk={submitEdit}>
                <Form form={form} layout="vertical">
                    <Form.Item name="due_date" label="Due Date">
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="principal_amount" label="Principal">
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="interest_amount" label="Interest">
                        <InputNumber style={{ width: '100%' }} min={0} />
                    </Form.Item>
                    <Form.Item name="principal_gl" label="Principal G/L">
                        <Input />
                    </Form.Item>
                    <Form.Item name="interest_gl" label="Interest G/L">
                        <Input />
                    </Form.Item>
                    <Form.Item name="tax_code" label="Tax Code">
                        <Input />
                    </Form.Item>
                    <Form.Item name="vendor_ref_no" label="Vendor Ref No">
                        <Input />
                    </Form.Item>
                </Form>
            </Modal>

            <Modal
                title="Confirm Installment Schedule"
                open={confirmOpen}
                onCancel={() => setConfirmOpen(false)}
                onOk={submitConfirmSchedule}
                width={720}
            >
                <Form form={confirmForm} layout="vertical">
                    <Form.List name="installments">
                        {(fields) => (
                            <>
                                {fields.map((field) => (
                                    <Space key={field.key} align="start" style={{ display: 'flex', marginBottom: 8 }}>
                                        <Form.Item
                                            {...field}
                                            name={[field.name, 'installment_no']}
                                            rules={[{ required: true }]}
                                        >
                                            <InputNumber placeholder="#" min={1} />
                                        </Form.Item>
                                        <Form.Item {...field} name={[field.name, 'due_date']}>
                                            <Input type="date" placeholder="Due" />
                                        </Form.Item>
                                        <Form.Item
                                            {...field}
                                            name={[field.name, 'principal_amount']}
                                            rules={[{ required: true }]}
                                        >
                                            <InputNumber placeholder="Principal" min={0} />
                                        </Form.Item>
                                        <Form.Item
                                            {...field}
                                            name={[field.name, 'interest_amount']}
                                            rules={[{ required: true }]}
                                        >
                                            <InputNumber placeholder="Interest" min={0} />
                                        </Form.Item>
                                    </Space>
                                ))}
                            </>
                        )}
                    </Form.List>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    );
}
