import React from 'react';
import { Button, message } from 'antd';
import { BugOutlined } from '@ant-design/icons';
import adminAxios from '../../services/adminAxios';

const DebugTestButton = () => {
    const testAPI = async () => {
        console.log('🔍 Starting API Test...');
        
        try {
            // Test 1: Simple GET request
            console.log('Test 1: GET /dashboard/stats');
            const response1 = await adminAxios.get('/dashboard/stats');
            console.log('✅ Response 1:', response1.data);
            message.success('Dashboard Stats: OK');
        } catch (error) {
            console.error('❌ Test 1 failed:', error);
            message.error(`Dashboard Stats failed: ${error.message}`);
        }
        
        try {
            // Test 2: Companies endpoint
            console.log('Test 2: GET /companies');
            const response2 = await adminAxios.get('/companies?page=1&per_page=5');
            console.log('✅ Response 2:', response2.data);
            message.success(`Companies: ${response2.data.data?.length || 0} loaded`);
        } catch (error) {
            console.error('❌ Test 2 failed:', error);
            message.error(`Companies failed: ${error.message}`);
        }
        
        try {
            // Test 3: Auth check
            console.log('Test 3: GET /auth/user');
            const response3 = await adminAxios.get('/auth/user');
            console.log('✅ Response 3:', response3.data);
            message.success(`Logged in as: ${response3.data.name || 'Unknown'}`);
        } catch (error) {
            console.error('❌ Test 3 failed:', error);
            message.error(`Auth check failed: ${error.message}`);
        }
        
        // Check CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('🔐 CSRF Token:', csrfToken ? 'Found' : 'NOT FOUND!');
        
        // Check cookies
        console.log('🍪 Cookies:', document.cookie);
    };
    
    return (
        <Button 
            type="primary" 
            danger 
            icon={<BugOutlined />}
            onClick={testAPI}
            style={{ 
                position: 'fixed', 
                bottom: 20, 
                right: 20, 
                zIndex: 1000 
            }}
        >
            Debug API
        </Button>
    );
};

export default DebugTestButton;