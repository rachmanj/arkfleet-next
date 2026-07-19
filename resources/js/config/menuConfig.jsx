import {
    BankOutlined,
    DashboardOutlined,
    DatabaseOutlined,
    FileTextOutlined,
    KeyOutlined,
    SettingOutlined,
    SwapOutlined,
    TeamOutlined,
    ToolOutlined,
} from '@ant-design/icons';

export const menuConfig = [
    {
        key: 'dashboard',
        path: '/',
        name: 'Dashboard',
        icon: <DashboardOutlined />,
        permission: 'view',
    },
    {
        key: 'masters',
        name: 'Master Data',
        icon: <DatabaseOutlined />,
        permission: 'view',
        children: [
            { key: 'projects', path: '/masters/projects', name: 'Projects', permission: 'view' },
            { key: 'departments', path: '/masters/departments', name: 'Departments', permission: 'view' },
            { key: 'business-partners', path: '/masters/business-partners', name: 'Business Partners', permission: 'view' },
        ],
    },
    {
        key: 'operations',
        name: 'Operations',
        icon: <SwapOutlined />,
        permission: 'view',
        children: [
            { key: 'movings', path: '/movings', name: 'Movings / IPA', permission: 'view' },
            { key: 'equipment', path: '/equipment', name: 'Equipment', permission: 'view' },
            { key: 'documents', path: '/documents', name: 'Documents', permission: 'view' },
        ],
    },
    {
        key: 'finance',
        name: 'Finance',
        icon: <BankOutlined />,
        permission: 'view',
        children: [
            { key: 'loans', path: '/loans', name: 'Loans', permission: 'view' },
            { key: 'fixed-assets', path: '/fixed-assets', name: 'Fixed Assets', permission: 'view' },
            { key: 'depreciation', path: '/depreciation', name: 'Depreciation', permission: 'view' },
        ],
    },
    {
        key: 'reports',
        path: '/reports',
        name: 'Reports',
        icon: <FileTextOutlined />,
        permission: 'view',
    },
    {
        key: 'sap',
        name: 'SAP Integration',
        icon: <TeamOutlined />,
        permission: 'sync',
        children: [
            { key: 'sap-sync', path: '/sap/sync', name: 'Sync Runs', permission: 'sync' },
            { key: 'sap-posting', path: '/sap/posting-logs', name: 'Posting Logs', permission: 'sap.post' },
        ],
    },
    {
        key: 'settings',
        name: 'Settings',
        icon: <SettingOutlined />,
        children: [
            { key: 'change-password', path: '/change-password', name: 'Change Password', icon: <KeyOutlined /> },
            { key: 'api-keys', path: '/settings/api-keys', name: 'API Keys', permission: 'view', icon: <ToolOutlined /> },
        ],
    },
];

function filterMenuByPermission(items, can) {
    return items
        .filter((item) => !item.permission || can(item.permission))
        .map((item) => {
            if (!item.children) {
                return item;
            }

            const children = filterMenuByPermission(item.children, can);

            if (children.length === 0) {
                return null;
            }

            return { ...item, children };
        })
        .filter(Boolean);
}

export function buildMenuRoutes(can) {
    return filterMenuByPermission(menuConfig, can);
}

export function resolveSelectedKeys(pathname) {
    if (pathname === '/' || pathname === '/dashboard') {
        return { selectedKeys: ['dashboard'], openKeys: [] };
    }

    if (pathname.startsWith('/change-password')) {
        return { selectedKeys: ['change-password'], openKeys: ['settings'] };
    }

    if (pathname.startsWith('/settings/api-keys')) {
        return { selectedKeys: ['api-keys'], openKeys: ['settings'] };
    }

    if (pathname.startsWith('/movings')) {
        return { selectedKeys: ['movings'], openKeys: ['operations'] };
    }

    if (pathname.startsWith('/documents')) {
        return { selectedKeys: ['documents'], openKeys: ['operations'] };
    }

    if (pathname.startsWith('/reports/ai-nlq')) {
        return { selectedKeys: ['reports'], openKeys: [] };
    }

    if (pathname.startsWith('/reports')) {
        return { selectedKeys: ['reports'], openKeys: [] };
    }

    if (pathname.startsWith('/fixed-assets')) {
        return { selectedKeys: ['fixed-assets'], openKeys: ['finance'] };
    }

    if (pathname.startsWith('/depreciation')) {
        return { selectedKeys: ['depreciation'], openKeys: ['finance'] };
    }

    if (pathname.startsWith('/loans')) {
        return { selectedKeys: ['loans'], openKeys: ['finance'] };
    }

    if (pathname.startsWith('/equipment')) {
        return { selectedKeys: ['equipment'], openKeys: ['operations'] };
    }

    if (pathname.startsWith('/masters')) {
        const segment = pathname.split('/')[2] ?? 'projects';
        return { selectedKeys: [segment], openKeys: ['masters'] };
    }

    if (pathname.startsWith('/sap')) {
        const segment = pathname.includes('posting') ? 'sap-posting' : 'sap-sync';
        return { selectedKeys: [segment], openKeys: ['sap'] };
    }

    const topLevel = pathname.split('/').filter(Boolean)[0] ?? 'dashboard';

    return { selectedKeys: [topLevel], openKeys: [] };
}
