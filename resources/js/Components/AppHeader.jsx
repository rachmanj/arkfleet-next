import { Link, router, usePage } from '@inertiajs/react';
import { Avatar, Button, Dropdown, Layout, Space, Typography } from 'antd';
import {
    KeyOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    MoonOutlined,
    SunOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { useTheme } from '../contexts/ThemeContext';

const { Header } = Layout;

export default function AppHeader({ collapsed, onToggleCollapsed }) {
    const { auth } = usePage().props;
    const { isDark, toggleTheme } = useTheme();

    const userMenuItems = [
        {
            key: 'change-password',
            icon: <KeyOutlined />,
            label: <Link href="/change-password">Change Password</Link>,
        },
        {
            type: 'divider',
        },
        {
            key: 'logout',
            icon: <LogoutOutlined />,
            label: 'Sign Out',
            danger: true,
            onClick: () => router.post('/logout'),
        },
    ];

    return (
        <Header
            style={{
                padding: '0 24px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                position: 'sticky',
                top: 0,
                zIndex: 10,
            }}
        >
            <Space>
                <Button
                    type="text"
                    icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
                    onClick={onToggleCollapsed}
                />
                <Typography.Title level={5} style={{ margin: 0 }}>
                    {auth.user?.name}
                </Typography.Title>
            </Space>

            <Space size="middle">
                <Button
                    type="text"
                    icon={isDark ? <SunOutlined /> : <MoonOutlined />}
                    onClick={toggleTheme}
                    title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
                />
                <Dropdown menu={{ items: userMenuItems }} placement="bottomRight" trigger={['click']}>
                    <Space style={{ cursor: 'pointer' }}>
                        <Avatar icon={<UserOutlined />} />
                        <Typography.Text>{auth.user?.name}</Typography.Text>
                    </Space>
                </Dropdown>
            </Space>
        </Header>
    );
}
