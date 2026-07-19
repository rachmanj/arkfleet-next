import { Head, Link, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import {
    Alert,
    Button,
    Card,
    Col,
    Descriptions,
    Empty,
    Form,
    Image,
    Input,
    InputNumber,
    Modal,
    Row,
    Select,
    Space,
    Spin,
    Table,
    Tabs,
    Tag,
    Typography,
    Upload,
    message,
} from 'antd';
import {
    ArrowLeftOutlined,
    DeleteOutlined,
    EditOutlined,
    FileOutlined,
    PlusOutlined,
    ReloadOutlined,
    UploadOutlined,
} from '@ant-design/icons';
import { useCallback, useEffect, useMemo, useState } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';
import { resolveStatusTags } from '../../../utils/equipmentStatus';
import { usePermissions } from '../../../hooks/usePermissions';
import HistoryTab from './HmKm/HistoryTab';

const LEGAL_CODES = ['BPKB', 'STNK'];
const ACQUISITION_CODES = ['PO'];
const INSURANCE_CODES = ['INSURANCE'];

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatAmount(value) {
    if (value == null || value === '') {
        return '—';
    }

    return Number(value).toLocaleString('id-ID');
}

function fileUrl(path) {
    return path ? `/storage/${path}` : null;
}

function filterDocuments(documents, codes, excludeCodes = []) {
    return (documents ?? []).filter((doc) => {
        const code = doc.document_type?.code;

        if (excludeCodes.includes(code)) {
            return false;
        }

        if (codes) {
            return codes.includes(code);
        }

        return !ACQUISITION_CODES.includes(code)
            && !LEGAL_CODES.includes(code)
            && !INSURANCE_CODES.includes(code);
    });
}

function DocumentFileLink({ record }) {
    const url = fileUrl(record.file_path);

    if (!url) {
        return <span>{record.document_number ?? '—'}</span>;
    }

    return (
        <Space>
            <span>{record.document_number ?? '—'}</span>
            <Button type="link" size="small" icon={<FileOutlined />} href={url} target="_blank" rel="noreferrer">
                File
            </Button>
        </Space>
    );
}

function PayreqTab({ equipmentId, payreqEnabled }) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [rows, setRows] = useState([]);
    const [total, setTotal] = useState('0');

    const loadPayreq = useCallback(async () => {
        if (!payreqEnabled) {
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/equipment/${equipmentId}/payreq-summary`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();

            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Payreq service returned an error');
            }

            const typeSums = [...(payload.data?.type_sums ?? [])].sort((a, b) => a.type.localeCompare(b.type));
            setRows(
                typeSums.map((item) => ({
                    key: item.type,
                    type: item.type.charAt(0).toUpperCase() + item.type.slice(1),
                    amount: item.total_amount,
                })),
            );
            setTotal(payload.data?.grand_total ?? '0');
        } catch (fetchError) {
            setRows([]);
            setTotal('0');
            setError(fetchError.message || 'Could not connect to Payreq service');
        } finally {
            setLoading(false);
        }
    }, [equipmentId, payreqEnabled]);

    useEffect(() => {
        if (payreqEnabled) {
            loadPayreq();
        }
    }, [loadPayreq, payreqEnabled]);

    if (!payreqEnabled) {
        return (
            <Alert
                type="info"
                showIcon
                message="Payreq not configured"
                description="Set PAYREQ_API_URL in .env to enable payment requisition summary for this unit."
            />
        );
    }

    return (
        <Card
            title="Payment Requisition Summary"
            extra={
                <Button icon={<ReloadOutlined />} onClick={loadPayreq} loading={loading}>
                    Refresh
                </Button>
            }
        >
            <Spin spinning={loading}>
                {error ? (
                    <Alert type="error" showIcon message={error} style={{ marginBottom: 16 }} />
                ) : null}
                <Table
                    rowKey="key"
                    pagination={false}
                    dataSource={rows}
                    locale={{ emptyText: 'No payment requisition data found' }}
                    columns={[
                        { title: 'Type', dataIndex: 'type' },
                        { title: 'Amount', dataIndex: 'amount', align: 'right' },
                    ]}
                    summary={() => (
                        <Table.Summary.Row>
                            <Table.Summary.Cell index={0}>
                                <strong>Total</strong>
                            </Table.Summary.Cell>
                            <Table.Summary.Cell index={1} align="right">
                                <strong>{total}</strong>
                            </Table.Summary.Cell>
                        </Table.Summary.Row>
                    )}
                />
            </Spin>
        </Card>
    );
}

export default function EquipmentShow() {
    const { equipment, movingLines, projects, departments, payreqEnabled, flash, latestHmReading, latestKmReading } = usePage().props;
    const { can } = usePermissions();
    const [editOpen, setEditOpen] = useState(false);
    const [historyOpen, setHistoryOpen] = useState(false);
    const [extendOpen, setExtendOpen] = useState(false);
    const [extendingDoc, setExtendingDoc] = useState(null);
    const [editForm] = Form.useForm();
    const [historyForm] = Form.useForm();
    const [extendForm] = Form.useForm();

    if (flash?.success) {
        message.success(flash.success);
    }

    if (flash?.error) {
        message.error(flash.error);
    }

    const documents = equipment.documents ?? [];
    const acquisitionDocs = useMemo(() => filterDocuments(documents, ACQUISITION_CODES), [documents]);
    const legalDocs = useMemo(() => filterDocuments(documents, LEGAL_CODES), [documents]);
    const insuranceDocs = useMemo(() => filterDocuments(documents, INSURANCE_CODES), [documents]);
    const otherDocs = useMemo(() => filterDocuments(documents, null), [documents]);

    const openEdit = () => {
        editForm.setFieldsValue(equipment);
        setEditOpen(true);
    };

    const submitEdit = () => {
        editForm.validateFields().then((values) => {
            router.put(`/equipment/${equipment.id}`, values, {
                preserveScroll: true,
                onSuccess: () => setEditOpen(false),
            });
        });
    };

    const uploadPhoto = ({ file }) => {
        const formData = new FormData();
        formData.append('file', file);
        router.post(`/equipment/${equipment.id}/photos`, formData, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const deletePhoto = (photoId) => {
        router.delete(`/equipment/photos/${photoId}`, { preserveScroll: true });
    };

    const openHistoryModal = () => {
        historyForm.resetFields();
        historyForm.setFieldsValue({ date: new Date().toISOString().slice(0, 10) });
        setHistoryOpen(true);
    };

    const submitHistory = () => {
        historyForm.validateFields().then((values) => {
            router.post(`/equipment/${equipment.id}/unit-no-history`, values, {
                preserveScroll: true,
                onSuccess: () => setHistoryOpen(false),
            });
        });
    };

    const openExtend = (doc) => {
        setExtendingDoc(doc);
        extendForm.setFieldsValue({ extend_days: 30 });
        setExtendOpen(true);
    };

    const submitExtend = () => {
        extendForm.validateFields().then((values) => {
            router.post(`/documents/${extendingDoc.id}/extend`, values, {
                preserveScroll: true,
                onSuccess: () => setExtendOpen(false),
            });
        });
    };

    const movingColumns = [
        {
            title: 'Transfer No',
            render: (_, record) => (
                <Link href={`/movings/transfers/${record.transfer?.id}`}>
                    {record.transfer?.transfer_number ?? '—'}
                </Link>
            ),
        },
        {
            title: 'Transferred At',
            render: (_, record) => formatDate(record.transfer?.transferred_at),
        },
        { title: 'From Project', dataIndex: 'from_project_code' },
        { title: 'To Project', dataIndex: 'to_project_code' },
        {
            title: 'From Dept',
            render: (_, record) => record.from_department?.department_name ?? '—',
        },
        {
            title: 'To Dept',
            render: (_, record) => record.to_department?.department_name ?? '—',
        },
    ];

    const acquisitionColumns = [
        {
            title: 'Document No',
            render: (_, record) => <DocumentFileLink record={record} />,
        },
        {
            title: 'Type',
            render: (_, record) => record.document_type?.name ?? '—',
        },
        {
            title: 'Issued',
            dataIndex: 'issued_date',
            render: formatDate,
        },
        {
            title: 'Amount',
            dataIndex: 'amount',
            render: formatAmount,
        },
    ];

    const legalColumns = [
        {
            title: 'Document No',
            render: (_, record) => <DocumentFileLink record={record} />,
        },
        {
            title: 'Type',
            render: (_, record) => record.document_type?.name ?? '—',
        },
        {
            title: 'Issued',
            dataIndex: 'issued_date',
            render: formatDate,
        },
        {
            title: 'Expiry',
            render: (_, record) => formatDate(record.expiry_date ?? record.due_date),
        },
        {
            title: 'Amount',
            dataIndex: 'amount',
            render: formatAmount,
        },
        {
            title: 'Actions',
            render: (_, record) => (
                <Button type="link" onClick={() => openExtend(record)}>
                    Extend
                </Button>
            ),
        },
    ];

    const insuranceColumns = [
        {
            title: 'Document No',
            render: (_, record) => <DocumentFileLink record={record} />,
        },
        {
            title: 'Supplier',
            render: (_, record) => record.supplier?.name ?? '—',
        },
        {
            title: 'Issued',
            dataIndex: 'issued_date',
            render: formatDate,
        },
        {
            title: 'Due',
            dataIndex: 'due_date',
            render: formatDate,
        },
        {
            title: 'Premium',
            dataIndex: 'amount',
            render: formatAmount,
        },
    ];

    const otherColumns = [
        {
            title: 'Document No',
            render: (_, record) => <DocumentFileLink record={record} />,
        },
        {
            title: 'Type',
            render: (_, record) => record.document_type?.name ?? '—',
        },
        {
            title: 'Supplier',
            render: (_, record) => record.supplier?.name ?? '—',
        },
        {
            title: 'Issued',
            dataIndex: 'issued_date',
            render: formatDate,
        },
        {
            title: 'Due',
            dataIndex: 'due_date',
            render: formatDate,
        },
        {
            title: 'Amount',
            dataIndex: 'amount',
            render: formatAmount,
        },
    ];

    const historyColumns = [
        { title: 'Date', dataIndex: 'date', render: formatDate },
        { title: 'Old Unit Code', dataIndex: 'old_unit_code' },
        { title: 'New Unit Code', dataIndex: 'new_unit_code' },
        { title: 'Remarks', dataIndex: 'remarks', render: (value) => value ?? '—' },
        {
            title: 'Recorded By',
            render: (_, record) => record.creator?.name ?? '—',
        },
    ];

    const tabItems = [
        {
            key: 'movings',
            label: 'Movings',
            children: (
                <ProTable
                    rowKey="id"
                    columns={movingColumns}
                    dataSource={movingLines}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 10 }}
                />
            ),
        },
        {
            key: 'acquisitions',
            label: 'Acquisitions',
            children: (
                <ProTable
                    rowKey="id"
                    columns={acquisitionColumns}
                    dataSource={acquisitionDocs}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 10 }}
                />
            ),
        },
        {
            key: 'legal',
            label: 'Legal',
            children: (
                <ProTable
                    rowKey="id"
                    columns={legalColumns}
                    dataSource={legalDocs}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 10 }}
                />
            ),
        },
        {
            key: 'insurance',
            label: 'Insurance',
            children: (
                <ProTable
                    rowKey="id"
                    columns={insuranceColumns}
                    dataSource={insuranceDocs}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 10 }}
                />
            ),
        },
        {
            key: 'others',
            label: 'Others',
            children: (
                <ProTable
                    rowKey="id"
                    columns={otherColumns}
                    dataSource={otherDocs}
                    search={false}
                    options={false}
                    pagination={{ pageSize: 10 }}
                />
            ),
        },
        {
            key: 'photos',
            label: 'Photos',
            children: (
                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                    <Upload accept="image/*" showUploadList={false} customRequest={uploadPhoto}>
                        <Button icon={<UploadOutlined />}>Upload Photo</Button>
                    </Upload>
                    {(equipment.photos ?? []).length === 0 ? (
                        <Empty description="No photos uploaded" />
                    ) : (
                        <Row gutter={[16, 16]}>
                            {(equipment.photos ?? []).map((photo) => (
                                <Col key={photo.id} xs={24} sm={12} md={8} lg={6}>
                                    <Card
                                        size="small"
                                        cover={<Image src={fileUrl(photo.file_path)} alt={photo.description ?? 'Equipment photo'} />}
                                        actions={[
                                            <Button
                                                key="delete"
                                                type="text"
                                                danger
                                                icon={<DeleteOutlined />}
                                                onClick={() => deletePhoto(photo.id)}
                                            />,
                                        ]}
                                    >
                                        <Typography.Text type="secondary">
                                            {photo.description ?? photo.uploader?.name ?? 'Photo'}
                                        </Typography.Text>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    )}
                </Space>
            ),
        },
        {
            key: 'changes',
            label: 'Changes',
            children: (
                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                    <Button type="primary" icon={<PlusOutlined />} onClick={openHistoryModal}>
                        Add Change Record
                    </Button>
                    <ProTable
                        rowKey="id"
                        columns={historyColumns}
                        dataSource={equipment.unit_no_histories ?? []}
                        search={false}
                        options={false}
                        pagination={{ pageSize: 10 }}
                    />
                </Space>
            ),
        },
        {
            key: 'payreq',
            label: 'Payreq',
            children: <PayreqTab equipmentId={equipment.id} payreqEnabled={payreqEnabled} />,
        },
        ...(can('hm-km.view')
            ? [
                  {
                      key: 'hm-km',
                      label: 'HM/KM History',
                      children: (
                          <HistoryTab
                              equipment={equipment}
                              latestHmReading={latestHmReading}
                              latestKmReading={latestKmReading}
                          />
                      ),
                  },
              ]
            : []),
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Equipment ${equipment.unit_code}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }} wrap>
                    <Space direction="vertical" size={4}>
                        <Typography.Title level={4} style={{ margin: 0 }}>
                            {equipment.unit_code}
                        </Typography.Title>
                        <Typography.Text type="secondary">{equipment.description ?? '—'}</Typography.Text>
                        <Space>{resolveStatusTags(equipment)}</Space>
                    </Space>
                    <Space>
                        <Button icon={<EditOutlined />} onClick={openEdit}>
                            Edit
                        </Button>
                        <Link href="/equipment">
                            <Button icon={<ArrowLeftOutlined />}>Back to Equipment List</Button>
                        </Link>
                    </Space>
                </Space>

                <Card title="Equipment Information">
                    <Descriptions column={{ xs: 1, md: 2 }}>
                        <Descriptions.Item label="Unit Code">{equipment.unit_code ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Description">{equipment.description ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Model">{equipment.unit_model?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Manufacture">{equipment.manufacture?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Plant Type">{equipment.plant_type?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Plant Group">{equipment.plant_group?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Asset Category">{equipment.asset_category?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Unit Status">{equipment.unitstatus?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Supplier">{equipment.supplier?.name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Department">{equipment.department?.department_name ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Project">
                            {equipment.project ? `${equipment.project.code} — ${equipment.project.name}` : equipment.project_code ?? '—'}
                        </Descriptions.Item>
                        <Descriptions.Item label="Serial No">{equipment.serial_no ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Chassis No">{equipment.chasis_no ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Engine Model">{equipment.engine_model ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Machine No">{equipment.machine_no ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Nomor Polisi">{equipment.nomor_polisi ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Bahan Bakar">{equipment.bahan_bakar ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Warna">{equipment.warna ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Capacity">{equipment.capacity != null ? Number(equipment.capacity).toLocaleString() : '—'}</Descriptions.Item>
                        <Descriptions.Item label="Acquisition Cost">{formatAmount(equipment.acquisition_cost)}</Descriptions.Item>
                        <Descriptions.Item label="Acquisition Date">{formatDate(equipment.acquisition_date)}</Descriptions.Item>
                        <Descriptions.Item label="In-Service Date">{formatDate(equipment.in_service_date)}</Descriptions.Item>
                        <Descriptions.Item label="Salvage Value">{formatAmount(equipment.salvage_value)}</Descriptions.Item>
                        <Descriptions.Item label="Useful Life (months)">{equipment.useful_life_months ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="RFU">
                            <Tag color={equipment.is_rfu ? 'success' : 'default'}>{equipment.is_rfu ? 'Yes' : 'No'}</Tag>
                        </Descriptions.Item>
                        <Descriptions.Item label="Active">
                            <Tag color={equipment.is_active ? 'success' : 'default'}>{equipment.is_active ? 'Yes' : 'No'}</Tag>
                        </Descriptions.Item>
                        <Descriptions.Item label="Remarks" span={2}>
                            {equipment.remarks ?? '—'}
                        </Descriptions.Item>
                    </Descriptions>
                </Card>

                <Card>
                    <Tabs items={tabItems} />
                </Card>
            </Space>

            <Modal title="Edit Equipment" open={editOpen} onCancel={() => setEditOpen(false)} onOk={submitEdit} width={800} destroyOnClose>
                <Form form={editForm} layout="vertical">
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

            <Modal title="Add Unit Code Change" open={historyOpen} onCancel={() => setHistoryOpen(false)} onOk={submitHistory} destroyOnClose>
                <Form form={historyForm} layout="vertical">
                    <Form.Item name="date" label="Date" rules={[{ required: true }]}>
                        <Input type="date" />
                    </Form.Item>
                    <Form.Item name="new_unit_code" label="New Unit Code" rules={[{ required: true }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item name="remarks" label="Remarks">
                        <Input.TextArea rows={3} />
                    </Form.Item>
                </Form>
            </Modal>

            <Modal title="Extend Document" open={extendOpen} onCancel={() => setExtendOpen(false)} onOk={submitExtend} destroyOnClose>
                <Form form={extendForm} layout="vertical">
                    <Form.Item name="extend_days" label="Extend Days" rules={[{ required: true }]}>
                        <InputNumber style={{ width: '100%' }} min={1} max={3650} />
                    </Form.Item>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    );
}
