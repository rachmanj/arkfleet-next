import { Layout, Menu } from 'antd';
import {
    DashboardOutlined,
    SettingOutlined,
} from '@ant-design/icons';
import { Link, usePage } from '@inertiajs/react';

const { Sider } = Layout;

const menuItems = [
    {
        key: 'dashboard',
        icon: <DashboardOutlined />,
        label: <Link href="/">Dashboard</Link>,
    },
    {
        key: 'settings',
        icon: <SettingOutlined />,
        label: 'Settings',
        children: [
            {
                key: 'change-password',
                label: <Link href="/change-password">Change Password</Link>,
            },
        ],
    },
];

export default function AppSidebar({ collapsed }) {
    const { url } = usePage();

    const selectedKey = url.startsWith('/change-password')
        ? 'change-password'
        : 'dashboard';

    const openKeys = url.startsWith('/change-password') ? ['settings'] : [];

    return (
        <Sider
            collapsible
            collapsed={collapsed}
            trigger={null}
            width={240}
            style={{
                overflow: 'auto',
                height: '100vh',
                position: 'fixed',
                left: 0,
                top: 0,
                bottom: 0,
            }}
        >
            <div
                style={{
                    height: 64,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontWeight: 700,
                    fontSize: collapsed ? 14 : 18,
                    letterSpacing: 0.5,
                    color: '#fff',
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                }}
            >
                {collapsed ? 'AF' : 'ArkFleet'}
            </div>
            <Menu
                theme="dark"
                mode="inline"
                selectedKeys={[selectedKey]}
                defaultOpenKeys={openKeys}
                items={menuItems}
            />
        </Sider>
    );
}
