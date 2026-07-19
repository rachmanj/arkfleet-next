import { router } from '@inertiajs/react';
import { Form, Input, InputNumber, Modal, Select, DatePicker } from 'antd';
import dayjs from 'dayjs';
import { useEffect } from 'react';

export default function AddReadingModal({ open, onCancel, equipmentId, onSuccess }) {
    const [form] = Form.useForm();

    useEffect(() => {
        if (open) {
            form.resetFields();
            form.setFieldsValue({
                reading_date: dayjs(),
                reading_type: 'hm',
            });
        }
    }, [form, open]);

    const handleOk = () => {
        form.validateFields().then((values) => {
            router.post(
                `/equipment/${equipmentId}/hm-km`,
                {
                    reading_type: values.reading_type,
                    reading_value: values.reading_value,
                    reading_date: values.reading_date.format('YYYY-MM-DD'),
                    notes: values.notes,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        onSuccess?.();
                    },
                },
            );
        });
    };

    return (
        <Modal
            title="Add HM/KM Reading"
            open={open}
            onCancel={onCancel}
            onOk={handleOk}
            destroyOnClose
        >
            <Form form={form} layout="vertical">
                <Form.Item name="reading_type" label="Type" rules={[{ required: true }]}>
                    <Select
                        options={[
                            { value: 'hm', label: 'HM (Hours Meter)' },
                            { value: 'km', label: 'KM (Kilometers)' },
                        ]}
                    />
                </Form.Item>
                <Form.Item
                    name="reading_value"
                    label="Value"
                    rules={[
                        { required: true, message: 'Value is required' },
                        { type: 'number', min: 0, message: 'Value must be zero or greater' },
                    ]}
                >
                    <InputNumber style={{ width: '100%' }} min={0} precision={2} />
                </Form.Item>
                <Form.Item name="reading_date" label="Date" rules={[{ required: true }]}>
                    <DatePicker
                        style={{ width: '100%' }}
                        format="YYYY-MM-DD"
                        disabledDate={(current) => current && current > dayjs().endOf('day')}
                    />
                </Form.Item>
                <Form.Item name="notes" label="Notes">
                    <Input.TextArea rows={3} maxLength={500} showCount />
                </Form.Item>
            </Form>
        </Modal>
    );
}
