import { Head, router, usePage } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Alert, Button, Card, Input, Space, Typography, message } from 'antd';
import { useState } from 'react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function NlqReport() {
    const { catalog, openRouterConfigured, nlqEnabled, flash } = usePage().props;
    const [question, setQuestion] = useState('');
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(flash?.nlqResult ?? null);

    if (flash?.error) {
        message.error(flash.error);
    }

    const ask = () => {
        if (!question.trim()) {
            return;
        }

        setLoading(true);
        router.post(
            '/reports/ai-nlq',
            { question },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    setResult(page.props.flash?.nlqResult ?? null);
                    setLoading(false);
                },
                onError: () => setLoading(false),
                onFinish: () => setLoading(false),
            },
        );
    };

    const columns =
        result?.columns?.map((col) => ({
            title: col,
            dataIndex: col,
            render: (v) => (v == null ? '—' : String(v)),
        })) ?? [];

    return (
        <AuthenticatedLayout>
            <Head title="AI Natural Language Query" />

            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <Typography.Title level={4} style={{ margin: 0 }}>
                    AI Natural Language Query
                </Typography.Title>

                {!nlqEnabled && <Alert type="warning" message="NLQ is disabled in configuration." showIcon />}

                {!openRouterConfigured && (
                    <Alert
                        type="info"
                        message="Set OPENROUTER_API_KEY in .env to enable AI-powered query translation."
                        showIcon
                    />
                )}

                <Card title="Ask a question" size="small">
                    <Space.Compact style={{ width: '100%' }}>
                        <Input
                            placeholder="e.g. Show active equipment on project 000H"
                            value={question}
                            onChange={(e) => setQuestion(e.target.value)}
                            onPressEnter={ask}
                            disabled={!nlqEnabled}
                        />
                        <Button type="primary" onClick={ask} loading={loading} disabled={!nlqEnabled}>
                            Ask
                        </Button>
                    </Space.Compact>
                </Card>

                <Card title="Available data sources" size="small">
                    <ul>
                        {catalog?.map((item) => (
                            <li key={item.source}>
                                <Typography.Text strong>{item.label}</Typography.Text> —{' '}
                                {item.columns?.join(', ')}
                            </li>
                        ))}
                    </ul>
                </Card>

                {result && (
                    <Card
                        title={`Results (${result.count} rows) — ${result.spec?.source}`}
                        extra={
                            <Typography.Text type="secondary" code>
                                {JSON.stringify(result.spec)}
                            </Typography.Text>
                        }
                    >
                        <ProTable
                            rowKey={(_, idx) => idx}
                            columns={columns}
                            dataSource={result.rows}
                            search={false}
                            options={false}
                            pagination={{ pageSize: 20 }}
                        />
                    </Card>
                )}
            </Space>
        </AuthenticatedLayout>
    );
}
