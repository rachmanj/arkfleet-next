import { Head, Link } from '@inertiajs/react';
import { Card, Col, Row, Typography } from 'antd';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

const reports = [
    {
        title: 'Expiring Documents',
        description: 'Documents expiring within a configurable window.',
        href: '/reports/expiring-documents',
    },
    {
        title: 'IPA Summary',
        description: 'Internal plant assignment transfer history.',
        href: '/reports/ipa-summary',
    },
    {
        title: 'Active Equipment Status',
        description: 'Current fleet register by project and status.',
        href: '/reports/active-equipment',
    },
    {
        title: 'AI Natural Language Query',
        description: 'Ask questions in plain language — guarded read-only queries.',
        href: '/reports/ai-nlq',
    },
];

export default function ReportsIndex() {
    return (
        <AuthenticatedLayout>
            <Head title="Reports" />

            <Typography.Title level={4}>Reports</Typography.Title>

            <Row gutter={[16, 16]}>
                {reports.map((report) => (
                    <Col xs={24} md={8} key={report.href}>
                        <Card title={report.title} extra={<Link href={report.href}>Open</Link>}>
                            {report.description}
                        </Card>
                    </Col>
                ))}
            </Row>
        </AuthenticatedLayout>
    );
}
