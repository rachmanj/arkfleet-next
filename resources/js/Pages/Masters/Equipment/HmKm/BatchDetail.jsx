import { Head, Link, usePage } from '@inertiajs/react';
import { Button, Card, Col, Descriptions, Row, Space, Table, Tag, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import AuthenticatedLayout from '../../../../Layouts/AuthenticatedLayout';

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function HmKmBatchDetail() {
    const { batch } = usePage().props;
    const errors = batch.errors ?? [];

    const errorColumns = [
        { title: 'Row', dataIndex: 'row', width: 80 },
        {
            title: 'Level',
            dataIndex: 'level',
            width: 100,
            render: (value) => (
                <Tag color={value === 'warning' ? 'warning' : 'error'}>{value === 'warning' ? 'Warning' : 'Error'}</Tag>
            ),
        },
        { title: 'Message', dataIndex: 'message' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Batch ${batch.batch_id}`} />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Space style={{ width: '100%', justifyContent: 'space-between' }} wrap>
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        Upload Batch Detail
                    </Typography.Title>
                    <Link href="/equipment/hm-km/batches">
                        <Button icon={<ArrowLeftOutlined />}>Back to Batches</Button>
                    </Link>
                </Space>

                <Row gutter={[16, 16]}>
                    <Col xs={24} sm={12} md={6}>
                        <Card size="small">
                            <Typography.Text type="secondary">Rows Total</Typography.Text>
                            <Typography.Title level={3} style={{ margin: '8px 0 0' }}>
                                {batch.rows_total}
                            </Typography.Title>
                        </Card>
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Card size="small">
                            <Typography.Text type="secondary">Imported</Typography.Text>
                            <Typography.Title level={3} style={{ margin: '8px 0 0' }}>
                                {batch.rows_imported}
                            </Typography.Title>
                        </Card>
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Card size="small">
                            <Typography.Text type="secondary">Skipped</Typography.Text>
                            <Typography.Title level={3} style={{ margin: '8px 0 0' }}>
                                {batch.rows_skipped}
                            </Typography.Title>
                        </Card>
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Card size="small">
                            <Typography.Text type="secondary">Errors</Typography.Text>
                            <Typography.Title level={3} style={{ margin: '8px 0 0' }}>
                                {batch.rows_errored}
                            </Typography.Title>
                        </Card>
                    </Col>
                </Row>

                <Card title="Batch Information">
                    <Descriptions column={{ xs: 1, md: 2 }}>
                        <Descriptions.Item label="Batch ID">{batch.batch_id}</Descriptions.Item>
                        <Descriptions.Item label="Filename">{batch.original_filename}</Descriptions.Item>
                        <Descriptions.Item label="Uploaded At">{formatDate(batch.created_at)}</Descriptions.Item>
                        <Descriptions.Item label="Uploaded By">{batch.uploader?.name ?? '—'}</Descriptions.Item>
                    </Descriptions>
                </Card>

                <Card title="Row Errors & Warnings">
                    <Table
                        rowKey={(record, index) => `${record.row}-${index}`}
                        columns={errorColumns}
                        dataSource={errors}
                        pagination={{ pageSize: 20 }}
                        expandable={{
                            expandedRowRender: (record) => record.message,
                            rowExpandable: (record) => Boolean(record.message),
                        }}
                        locale={{ emptyText: 'No errors recorded for this batch' }}
                    />
                </Card>
            </Space>
        </AuthenticatedLayout>
    );
}
