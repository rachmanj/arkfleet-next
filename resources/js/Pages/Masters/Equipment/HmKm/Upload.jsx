import { Head, router, usePage } from '@inertiajs/react';
import {
    Alert,
    Button,
    Card,
    DatePicker,
    Form,
    Radio,
    Space,
    Typography,
    Upload,
    message,
} from 'antd';
import { InboxOutlined, UploadOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import AuthenticatedLayout from '../../../../Layouts/AuthenticatedLayout';
import { usePermissions } from '../../../../hooks/usePermissions';

const { Dragger } = Upload;

export default function HmKmUpload() {
    const { flash } = usePage().props;
    const { can } = usePermissions();
    const [form] = Form.useForm();
    const [fileList, setFileList] = useState([]);
    const [submitting, setSubmitting] = useState(false);
    const dateSource = Form.useWatch('date_source', form) ?? 'single';

    useEffect(() => {
        if (flash?.success) {
            message.success(flash.success);
        }

        if (flash?.error) {
            message.error(flash.error);
        }
    }, [flash]);

    if (!can('hm-km.upload')) {
        return (
            <AuthenticatedLayout>
                <Head title="Upload HM/KM" />
                <Alert type="error" showIcon message="You do not have permission to upload HM/KM readings." />
            </AuthenticatedLayout>
        );
    }

    const submitUpload = (values) => {
        if (fileList.length === 0) {
            message.error('Please select a file to upload.');
            return;
        }

        const formData = new FormData();
        formData.append('file', fileList[0]);
        formData.append('file_has_dates', values.date_source === 'row' ? '1' : '0');

        if (values.date_source === 'single' && values.reading_date) {
            formData.append('reading_date', values.reading_date.format('YYYY-MM-DD'));
        }

        setSubmitting(true);
        router.post('/equipment/hm-km/upload', formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => setSubmitting(false),
            onSuccess: () => {
                setFileList([]);
                form.resetFields(['reading_date']);
                form.setFieldsValue({ date_source: 'single' });
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Upload HM/KM" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }} wrap>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Upload HM/KM Readings
                    </Typography.Title>
                    <Button onClick={() => router.get('/equipment/hm-km/batches')}>View Upload History</Button>
                </Space>

                <Card title="Instructions">
                    <Space direction="vertical">
                        <Typography.Text>Supported formats: .xlsx, .xls (max 5 MB)</Typography.Text>
                        <Typography.Text>
                            Required columns: <strong>unit_code</strong> (or Unit Code / unit / unit_no) and at least one of{' '}
                            <strong>HM</strong> or <strong>KM</strong>.
                        </Typography.Text>
                        <Typography.Text type="secondary">
                            Each row may include both HM and KM values. Optional date column: date, reading_date, or tanggal.
                        </Typography.Text>
                    </Space>
                </Card>

                <Card>
                    <Form
                        form={form}
                        layout="vertical"
                        initialValues={{ date_source: 'single' }}
                        onFinish={submitUpload}
                    >
                        <Form.Item
                            name="date_source"
                            label="Date Source"
                            rules={[{ required: true }]}
                        >
                            <Radio.Group>
                                <Radio value="single">One date for all rows</Radio>
                                <Radio value="row">Each row has its own date</Radio>
                            </Radio.Group>
                        </Form.Item>

                        {dateSource === 'single' ? (
                            <Form.Item
                                name="reading_date"
                                label="Reading Date"
                                rules={[{ required: true, message: 'Reading date is required' }]}
                            >
                                <DatePicker
                                    style={{ width: '100%', maxWidth: 280 }}
                                    format="YYYY-MM-DD"
                                    disabledDate={(current) => current && current > dayjs().endOf('day')}
                                />
                            </Form.Item>
                        ) : null}

                        <Form.Item label="Excel File" required>
                            <Dragger
                                accept=".xlsx,.xls"
                                fileList={fileList}
                                maxCount={1}
                                beforeUpload={(file) => {
                                    setFileList([file]);
                                    return false;
                                }}
                                onRemove={() => {
                                    setFileList([]);
                                }}
                            >
                                <p className="ant-upload-drag-icon">
                                    <InboxOutlined />
                                </p>
                                <p className="ant-upload-text">Click or drag Excel file to upload</p>
                                <p className="ant-upload-hint">Only .xlsx and .xls files are accepted</p>
                            </Dragger>
                        </Form.Item>

                        <Space>
                            <Button type="primary" htmlType="submit" icon={<UploadOutlined />} loading={submitting}>
                                Upload
                            </Button>
                            <Button onClick={() => router.get('/equipment')}>Back to Equipment</Button>
                        </Space>
                    </Form>
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
