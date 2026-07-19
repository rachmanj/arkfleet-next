import { Head, router, usePage } from '@inertiajs/react';
import { Button, Card, Col, DatePicker, Form, Input, Row, Select, Space, Typography } from 'antd';
import dayjs from 'dayjs';
import AuthenticatedLayout from '../../../Layouts/AuthenticatedLayout';

export default function MovingsCreate() {
    const { projects, departments, suggestedIpaNo } = usePage().props;
    const [form] = Form.useForm();

    const submit = (values) => {
        router.post('/movings', {
            ...values,
            ipa_date: values.ipa_date.format('YYYY-MM-DD'),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create IPA" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Typography.Title level={4} style={{ margin: 0 }}>
                    Create New IPA
                </Typography.Title>

                <Card>
                    <Form
                        form={form}
                        layout="vertical"
                        onFinish={submit}
                        initialValues={{
                            ipa_no: suggestedIpaNo,
                            ipa_date: dayjs(),
                        }}
                    >
                        <Row gutter={16}>
                            <Col xs={24} md={12}>
                                <Form.Item name="ipa_no" label="IPA No" rules={[{ required: true, max: 30 }]}>
                                    <Input placeholder={suggestedIpaNo} />
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
                            Save &amp; Add Equipment
                        </Button>
                    </Form>
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
