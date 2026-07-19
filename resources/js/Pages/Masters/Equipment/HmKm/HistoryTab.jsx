import { router } from '@inertiajs/react';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Col, Modal, Row, Select, Space, Tag, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import { usePermissions } from '../../../../hooks/usePermissions';
import AddReadingModal from './AddReadingModal';

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

function formatReadingValue(value, type) {
    if (value == null || value === '') {
        return '—';
    }

    const formatted = Number(value).toLocaleString('en-US', {
        minimumFractionDigits: type === 'hm' ? 2 : 0,
        maximumFractionDigits: 2,
    });

    return type === 'hm' ? `${formatted} hrs` : `${formatted} km`;
}

export default function HistoryTab({ equipment, latestHmReading, latestKmReading }) {
    const { can } = usePermissions();
    const [typeFilter, setTypeFilter] = useState('all');
    const [addReadingModalOpen, setAddReadingModalOpen] = useState(false);

    const readings = equipment.hm_km_readings ?? [];

    const filteredData = useMemo(() => {
        if (typeFilter === 'all') {
            return readings;
        }

        return readings.filter((row) => row.reading_type === typeFilter);
    }, [readings, typeFilter]);

    const latestHm = latestHmReading ?? equipment.latest_hm_reading;
    const latestKm = latestKmReading ?? equipment.latest_km_reading;

    const deleteReading = (readingId) => {
        Modal.confirm({
            title: 'Delete reading?',
            content: 'This reading will be soft-deleted.',
            okText: 'Delete',
            okType: 'danger',
            onOk: () =>
                router.delete(`/equipment/hm-km/${readingId}`, {
                    preserveScroll: true,
                }),
        });
    };

    const columns = [
        {
            title: 'Date',
            dataIndex: 'reading_date',
            render: formatDate,
        },
        {
            title: 'Type',
            dataIndex: 'reading_type',
            render: (value) => <Tag color={value === 'hm' ? 'blue' : 'green'}>{value?.toUpperCase()}</Tag>,
        },
        {
            title: 'Value',
            render: (_, record) => formatReadingValue(record.reading_value, record.reading_type),
        },
        {
            title: 'Source',
            dataIndex: 'source',
            render: (value) => (value === 'upload' ? 'Upload' : 'Manual'),
        },
        {
            title: 'Uploaded By',
            render: (_, record) => record.uploader?.name ?? '—',
        },
        {
            title: 'Notes',
            dataIndex: 'notes',
            ellipsis: true,
            render: (value) => value ?? '—',
        },
        {
            title: 'Actions',
            render: (_, record) =>
                can('hm-km.delete') ? (
                    <Button
                        type="text"
                        danger
                        icon={<DeleteOutlined />}
                        onClick={() => deleteReading(record.id)}
                    />
                ) : null,
        },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Row gutter={[16, 16]}>
                <Col xs={24} md={12}>
                    <Card size="small" title="Latest HM">
                        {latestHm ? (
                            <Typography.Text>
                                {formatReadingValue(latestHm.reading_value, 'hm')} ({formatDate(latestHm.reading_date)})
                            </Typography.Text>
                        ) : (
                            <Typography.Text type="secondary">No HM readings</Typography.Text>
                        )}
                    </Card>
                </Col>
                <Col xs={24} md={12}>
                    <Card size="small" title="Latest KM">
                        {latestKm ? (
                            <Typography.Text>
                                {formatReadingValue(latestKm.reading_value, 'km')} ({formatDate(latestKm.reading_date)})
                            </Typography.Text>
                        ) : (
                            <Typography.Text type="secondary">No KM readings</Typography.Text>
                        )}
                    </Card>
                </Col>
            </Row>

            <Space style={{ width: '100%', justifyContent: 'space-between' }} wrap>
                <Select
                    value={typeFilter}
                    onChange={setTypeFilter}
                    style={{ width: 160 }}
                    options={[
                        { value: 'all', label: 'All Types' },
                        { value: 'hm', label: 'HM' },
                        { value: 'km', label: 'KM' },
                    ]}
                />
                {can('hm-km.manual') ? (
                    <Button type="primary" icon={<PlusOutlined />} onClick={() => setAddReadingModalOpen(true)}>
                        Add Reading
                    </Button>
                ) : null}
            </Space>

            <ProTable
                rowKey="id"
                columns={columns}
                dataSource={filteredData}
                search={false}
                options={false}
                pagination={{ pageSize: 20 }}
            />

            <AddReadingModal
                open={addReadingModalOpen}
                onCancel={() => setAddReadingModalOpen(false)}
                equipmentId={equipment.id}
                onSuccess={() => setAddReadingModalOpen(false)}
            />
        </Space>
    );
}
