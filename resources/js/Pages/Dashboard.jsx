import { Head, Link, usePage } from '@inertiajs/react';
import { Alert, Card, Col, List, Row, Space, Statistic, Tag, Typography } from 'antd';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

export default function Dashboard() {
    const { auth, stats, expiringAlerts } = usePage().props;

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Typography.Title level={4} style={{ margin: 0 }}>
                    Welcome back, {auth.user?.name}
                </Typography.Title>

                <Row gutter={16}>
                    <Col xs={24} sm={8}>
                        <Card>
                            <Statistic title="Expiring in 30 days" value={stats?.expiring_documents ?? 0} />
                        </Card>
                    </Col>
                    <Col xs={24} sm={8}>
                        <Card>
                            <Statistic title="Expired documents" value={stats?.expired_documents ?? 0} valueStyle={{ color: '#cf1322' }} />
                        </Card>
                    </Col>
                    <Col xs={24} sm={8}>
                        <Card>
                            <Statistic title="IPA transfers this month" value={stats?.ipa_transfers_this_month ?? 0} />
                        </Card>
                    </Col>
                </Row>

                {(expiringAlerts?.length ?? 0) > 0 && (
                    <Card title="Document expiry alerts">
                        <List
                            dataSource={expiringAlerts}
                            renderItem={(item) => (
                                <List.Item>
                                    <Space>
                                        <Tag color="orange">{item.expiry_date}</Tag>
                                        <strong>{item.equipment?.unit_code}</strong>
                                        <span>{item.document_type?.name}</span>
                                        <span>{item.document_number}</span>
                                    </Space>
                                </List.Item>
                            )}
                        />
                        <Link href="/reports/expiring-documents">View all expiring documents</Link>
                    </Card>
                )}

                {(stats?.expired_documents ?? 0) > 0 && (
                    <Alert
                        type="error"
                        showIcon
                        message={`${stats.expired_documents} document(s) have already expired.`}
                        action={<Link href="/documents?status=expired">Review</Link>}
                    />
                )}
            </Space>
        </AuthenticatedLayout>
    );
}
