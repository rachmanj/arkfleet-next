import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { ProConfigProvider } from '@ant-design/pro-components';
import { enUSIntl } from '@ant-design/pro-provider';
import { ConfigProvider, theme } from 'antd';
import enUS from 'antd/locale/en_US';

const ThemeContext = createContext(null);

const STORAGE_KEY = 'arkfleet-theme';

export function ThemeProvider({ children }) {
    const [mode, setMode] = useState(() => {
        if (typeof window === 'undefined') {
            return 'dark';
        }

        return localStorage.getItem(STORAGE_KEY) || 'dark';
    });

    useEffect(() => {
        localStorage.setItem(STORAGE_KEY, mode);
        document.documentElement.setAttribute('data-theme', mode);
    }, [mode]);

    const toggleTheme = () => {
        setMode((current) => (current === 'dark' ? 'light' : 'dark'));
    };

    const value = useMemo(
        () => ({
            mode,
            isDark: mode === 'dark',
            toggleTheme,
            setMode,
        }),
        [mode],
    );

    return (
        <ThemeContext.Provider value={value}>
            <ConfigProvider
                locale={enUS}
                theme={{
                    algorithm: mode === 'dark' ? theme.darkAlgorithm : theme.defaultAlgorithm,
                    token: {
                        colorPrimary: '#1677ff',
                        borderRadius: 6,
                    },
                }}
            >
                <ProConfigProvider intl={enUSIntl}>{children}</ProConfigProvider>
            </ConfigProvider>
        </ThemeContext.Provider>
    );
}

export function useTheme() {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useTheme must be used within ThemeProvider');
    }

    return context;
}
