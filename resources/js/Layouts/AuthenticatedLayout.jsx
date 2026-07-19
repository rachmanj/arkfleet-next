import { useEffect, useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { ProLayout } from '@ant-design/pro-components';
import {
    KeyOutlined,
    LogoutOutlined,
    MoonOutlined,
    SunOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { Avatar, Dropdown, Space, Typography } from 'antd';
import { buildMenuRoutes, resolveSelectedKeys } from '../config/menuConfig.jsx';
import { usePermissions } from '../hooks/usePermissions';
import { useTheme } from '../contexts/ThemeContext';

export default function AuthenticatedLayout({ children }) {
    const { auth, app } = usePage().props;
    const { url } = usePage();
    const { can } = usePermissions();
    const { isDark, toggleTheme } = useTheme();
    const [collapsed, setCollapsed] = useState(false);

    const pathname = useMemo(() => new URL(url, window.location.origin).pathname, [url]);
    const menuRoutes = useMemo(() => buildMenuRoutes(can), [can]);
    const { selectedKeys, openKeys: routeOpenKeys } = useMemo(
        () => resolveSelectedKeys(pathname),
        [pathname],
    );
    const [openKeys, setOpenKeys] = useState(routeOpenKeys);

    useEffect(() => {
        setOpenKeys((current) => {
            const merged = new Set([...current, ...routeOpenKeys]);

            return [...merged];
        });
    }, [routeOpenKeys]);

    const userMenuItems = [
        {
            key: 'change-password',
            icon: <KeyOutlined />,
            label: <Link href="/change-password">Change Password</Link>,
        },
        { type: 'divider' },
        {
            key: 'logout',
            icon: <LogoutOutlined />,
            label: 'Sign Out',
            danger: true,
            onClick: () => router.post('/logout'),
        },
    ];

    return (
        <ProLayout
            title={app?.name ?? 'ArkFleet'}
            logo={false}
            layout="mix"
            fixSiderbar
            collapsed={collapsed}
            onCollapse={setCollapsed}
            selectedKeys={selectedKeys}
            route={{ routes: menuRoutes }}
            location={{ pathname }}
            menuProps={{
                openKeys,
                onOpenChange: setOpenKeys,
            }}
            menuItemRender={(item, dom) => {
                if (!item.path) {
                    return dom;
                }

                return <Link href={item.path}>{dom}</Link>;
            }}
            subMenuItemRender={(item, dom) => {
                if (!item.path) {
                    return dom;
                }

                return <Link href={item.path}>{dom}</Link>;
            }}
            actionsRender={() => [
                <button
                    key="theme"
                    type="button"
                    onClick={toggleTheme}
                    style={{
                        border: 'none',
                        background: 'transparent',
                        cursor: 'pointer',
                        padding: '0 8px',
                        color: 'inherit',
                    }}
                    title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
                >
                    {isDark ? <SunOutlined /> : <MoonOutlined />}
                </button>,
                <Dropdown key="user" menu={{ items: userMenuItems }} placement="bottomRight" trigger={['click']}>
                    <Space style={{ cursor: 'pointer', paddingRight: 16 }}>
                        <Avatar size="small" icon={<UserOutlined />} />
                        <Typography.Text>{auth.user?.name}</Typography.Text>
                    </Space>
                </Dropdown>,
            ]}
            token={{
                header: {
                    heightLayoutHeader: 56,
                },
            }}
        >
            <div style={{ minHeight: 'calc(100vh - 56px)', padding: 24 }}>{children}</div>
        </ProLayout>
    );
}
