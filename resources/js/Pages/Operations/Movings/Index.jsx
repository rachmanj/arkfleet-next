import { Head, Link, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import {
    Button,
    Card,
    Col,
    Collapse,
    DatePicker,
    Form,
    Input,
    Row,
    Select,
    Space,
    Tag,
    Typography,
    message,
    Modal,
} from 'antd';
import {
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    FilePdfOutlined,
    PlusOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { useEffect } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

const STATUS_COLORS = {
    DRAFT: 'gold',
    SUBMITTED: 'blue',
    APPROVED: 'green',
};

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return dayjs(value).format('DD MMM YYYY');
}

function buildFilterParams(values) {
    const params = {};

    Object.entries(values).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }

        if (key === 'date_range' && Array.isArray(value) && value.length === 2) {
            params.date_from = value[0].format('YYYY-MM-DD');
            params.date_to = value[1].format('YYYY-MM-DD');

            return;
        }

        params[key] = value;
    });

    return params;
}

export default function MovingsIndex() {
    const { transfers, projects, filters, flash } = usePage().props;
    const [filterForm] = Form.useForm();

    useEffect(() => {
        if (flash?.success) {
            message.success(flash.success);
        }

        if (flash?.error) {
            message.error(flash.error);
        }
    }, [flash]);

    useEffect(() => {
        filterForm.setFieldsValue({
            ipa_no: filters?.ipa_no ?? undefined,
            date_range:
                filters?.date_from && filters?.date_to
                    ? [dayjs(filters.date_from), dayjs(filters.date_to)]
                    : undefined,
            from_project_code: filters?.from_project_code ?? undefined,
            to_project_code: filters?.to_project_code ?? undefined,
            status: filters?.status ?? undefined,
            unit_code: filters?.unit_code ?? undefined,
        });
    }, [filterForm, filters]);

    const applyFilters = (extra = {}) => {
        const values = filterForm.getFieldsValue();
        router.get('/movings', { ...buildFilterParams(values), search: filters?.search, ...extra }, { preserveState: true });
    };

    const resetFilters = () => {
        filterForm.resetFields();
        router.get('/movings');
    };

    const confirmDelete = (record) => {
        Modal.confirm({
            title: 'Delete draft IPA?',
            content: `This will permanently delete ${record.ipa_no}.`,
            okText: 'Delete',
            okType: 'danger',
            onOk: () => router.delete(`/movings/${record.id}`),
        });
    };

    const columns = [
        { title: 'IPA No', dataIndex: 'ipa_no' },
        { title: 'IPA Date', dataIndex: 'ipa_date', render: (value) => formatDate(value) },
        {
            title: 'From Project',
            render: (_, record) =>
                record.from_project_code
                    ? `${record.from_project_code}${record.from_project?.name ? ` — ${record.from_project.name}` : ''}`
                    : '—',
        },
        {
            title: 'To Project',
            render: (_, record) =>
                record.to_project_code
                    ? `${record.to_project_code}${record.to_project?.name ? ` — ${record.to_project.name}` : ''}`
                    : '—',
        },
        { title: 'Equipment Count', dataIndex: 'line_count' },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (status) => <Tag color={STATUS_COLORS[status] ?? 'default'}>{status}</Tag>,
        },
        { title: 'Created By', render: (_, record) => record.user?.name ?? '—' },
        {
            title: 'Actions',
            render: (_, record) => (
                <Space>
                    <Button
                        type="text"
                        icon={<EyeOutlined />}
                        title="View"
                        onClick={() => router.get(`/movings/${record.id}/show`)}
                    />
                    {record.status === 'DRAFT' && (
                        <Button
                            type="text"
                            icon={<EditOutlined />}
                            title="Edit"
                            onClick={() => router.get(`/movings/${record.id}/edit`)}
                        />
                    )}
                    <Button
                        type="text"
                        icon={<FilePdfOutlined />}
                        title="Print PDF"
                        href={`/movings/${record.id}/pdf`}
                    />
                    {record.status === 'DRAFT' && (
                        <Button
                            type="text"
                            danger
                            icon={<DeleteOutlined />}
                            title="Delete"
                            onClick={() => confirmDelete(record)}
                        />
                    )}
                </Space>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Movings / IPA" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Row justify="space-between" align="middle" gutter={[16, 16]}>
                    <Col>
                        <Typography.Title level={4} style={{ margin: 0 }}>
                            Movings / IPA
                        </Typography.Title>
                    </Col>
                    <Col>
                        <Link href="/movings/create">
                            <Button type="primary" icon={<PlusOutlined />}>
                                Create New IPA
                            </Button>
                        </Link>
                    </Col>
                </Row>

                <Card>
                    <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                        <Input.Search
                            placeholder="Quick search IPA no, project, unit, notes..."
                            defaultValue={filters?.search}
                            onSearch={(value) =>
                                router.get(
                                    '/movings',
                                    { ...filters, search: value || undefined, page: undefined },
                                    { preserveState: true },
                                )
                            }
                            allowClear
                        />

                        <Collapse
                            items={[
                                {
                                    key: 'advanced',
                                    label: 'Advanced Filters',
                                    children: (
                                        <Form form={filterForm} layout="vertical" onFinish={() => applyFilters({ page: undefined })}>
                                            <Row gutter={16}>
                                                <Col xs={24} md={8}>
                                                    <Form.Item name="ipa_no" label="IPA No">
                                                        <Input allowClear />
                                                    </Form.Item>
                                                </Col>
                                                <Col xs={24} md={8}>
                                                    <Form.Item name="date_range" label="IPA Date Range">
                                                        <DatePicker.RangePicker style={{ width: '100%' }} />
                                                    </Form.Item>
                                                </Col>
                                                <Col xs={24} md={8}>
                                                    <Form.Item name="unit_code" label="Unit Code">
                                                        <Input allowClear />
                                                    </Form.Item>
                                                </Col>
                                                <Col xs={24} md={8}>
                                                    <Form.Item name="from_project_code" label="From Project">
                                                        <Select
                                                            allowClear
                                                            showSearch
                                                            optionFilterProp="label"
                                                            options={projects.map((project) => ({
                                                                value: project.code,
                                                                label: `${project.code} — ${project.name}`,
                                                            }))}
                                                        />
                                                    </Form.Item>
                                                </Col>
                                                <Col xs={24} md={8}>
                                                    <Form.Item name="to_project_code" label="To Project">
                                                        <Select
                                                            allowClear
                                                            showSearch
                                                            optionFilterProp="label"
                                                            options={projects.map((project) => ({
                                                                value: project.code,
                                                                label: `${project.code} — ${project.name}`,
                                                            }))}
                                                        />
                                                    </Form.Item>
                                                </Col>
                                                <Col xs={24} md={8}>
                                                    <Form.Item name="status" label="Status">
                                                        <Select
                                                            allowClear
                                                            mode="multiple"
                                                            options={[
                                                                { value: 'DRAFT', label: 'DRAFT' },
                                                                { value: 'SUBMITTED', label: 'SUBMITTED' },
                                                                { value: 'APPROVED', label: 'APPROVED' },
                                                            ]}
                                                        />
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
                                    ),
                                },
                            ]}
                        />
                    </Space>
                </Card>

                <Card>
                    <ProTable
                        rowKey="id"
                        columns={columns}
                        dataSource={transfers.data}
                        search={false}
                        options={false}
                        pagination={{
                            current: transfers.current_page,
                            pageSize: transfers.per_page,
                            total: transfers.total,
                            onChange: (page) => applyFilters({ page }),
                        }}
                    />
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
