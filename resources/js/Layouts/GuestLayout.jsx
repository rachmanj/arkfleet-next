import { Layout, Typography } from 'antd';
import { useTheme } from '../contexts/ThemeContext';

const { Content } = Layout;

export default function GuestLayout({ children, title = 'Welcome' }) {
    const { isDark } = useTheme();

    return (
        <Layout
            style={{
                minHeight: '100vh',
                background: isDark
                    ? 'linear-gradient(135deg, #141414 0%, #1f1f1f 50%, #111a2c 100%)'
                    : 'linear-gradient(135deg, #f0f5ff 0%, #ffffff 50%, #e6f4ff 100%)',
            }}
        >
            <Content
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: 24,
                }}
            >
                <Typography.Title level={2} style={{ marginBottom: 32 }}>
                    ArkFleet
                </Typography.Title>
                {children}
            </Content>
        </Layout>
    );
}
