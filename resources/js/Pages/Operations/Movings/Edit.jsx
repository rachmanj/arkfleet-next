import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button, Card, Col, DatePicker, Form, Input, Row, Select, Space, Typography } from 'antd';
import dayjs from 'dayjs';
import { useEffect } from 'react';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function MovingsEdit() {
    const { moving, projects, departments } = usePage().props;
    const [form] = Form.useForm();

    useEffect(() => {
        form.setFieldsValue({
            ipa_no: moving.ipa_no,
            ipa_date: moving.ipa_date ? dayjs(moving.ipa_date) : undefined,
            from_project_code: moving.from_project_code ?? undefined,
            to_project_code: moving.to_project_code ?? undefined,
            from_department_id: moving.from_department_id ?? undefined,
            to_department_id: moving.to_department_id ?? undefined,
            tujuan_row_1: moving.tujuan_row_1,
            tujuan_row_2: moving.tujuan_row_2 ?? undefined,
            cc_row_1: moving.cc_row_1,
            cc_row_2: moving.cc_row_2 ?? undefined,
            cc_row_3: moving.cc_row_3 ?? undefined,
            notes: moving.notes ?? undefined,
        });
    }, [form, moving]);

    const submit = (values) => {
        router.put(`/movings/${moving.id}`, {
            ...values,
            ipa_date: values.ipa_date.format('YYYY-MM-DD'),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${moving.ipa_no}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ justifyContent: 'space-between', width: '100%' }}>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Edit IPA — {moving.ipa_no}
                    </Typography.Title>
                    <Link href={`/movings/${moving.id}/equipment`}>Back to Add Equipment</Link>
                </Space>

                <Card>
                    <Form form={form} layout="vertical" onFinish={submit}>
                        <Row gutter={16}>
                            <Col xs={24} md={12}>
                                <Form.Item name="ipa_no" label="IPA No" rules={[{ required: true, max: 30 }]}>
                                    <Input />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item name="ipa_date" label="IPA Date" rules={[{ required: true }]}>
                                    <DatePicker style={{ width: '100%' }} />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
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
                            <Col xs={24} md={12}>
                                <Form.Item name="to_project_code" label="To Project" rules={[{ required: true }]}>
                                    <Select
                                        showSearch
                                        optionFilterProp="label"
                                        options={projects.map((project) => ({
                                            value: project.code,
                                            label: `${project.code} — ${project.name}`,
                                        }))}
                                    />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item name="from_department_id" label="From Department">
                                    <Select
                                        allowClear
                                        options={departments.map((department) => ({
                                            value: department.id,
                                            label: department.department_name,
                                        }))}
                                    />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item name="to_department_id" label="To Department">
                                    <Select
                                        allowClear
                                        options={departments.map((department) => ({
                                            value: department.id,
                                            label: department.department_name,
                                        }))}
                                    />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item name="tujuan_row_1" label="Kepada Yth. (line 1)" rules={[{ required: true, max: 255 }]}>
                                    <Input />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item name="tujuan_row_2" label="Kepada Yth. (line 2)">
                                    <Input />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item name="cc_row_1" label="CC (line 1)" rules={[{ required: true, max: 255 }]}>
                                    <Input />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item name="cc_row_2" label="CC (line 2)">
                                    <Input />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item name="cc_row_3" label="CC (line 3)">
                                    <Input />
                                </Form.Item>
                            </Col>
                            <Col span={24}>
                                <Form.Item name="notes" label="Remarks">
                                    <Input.TextArea rows={3} maxLength={1000} showCount />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Button type="primary" htmlType="submit">
                            Save Changes
                        </Button>
                    </Form>
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
