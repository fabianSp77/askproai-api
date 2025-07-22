import React, { useState } from 'react';
import { Form, Input, Button, Card, message, Typography, Alert } from 'antd';
import { UserOutlined, LockOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const { Title } = Typography;

function Login({ csrfToken }) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const navigate = useNavigate();

    const onFinish = async (values) => {
        setLoading(true);
        setError(null);
        
        console.log('Login attempt with:', values);
        
        try {
            const response = await axios.post('/api/auth/portal/login', {
                email: values.email,
                password: values.password,
                device_name: 'web'
            });

            console.log('Login response:', response);

            if (response.data.token) {
                // Store token
                localStorage.setItem('auth_token', response.data.token);
                
                // Store user data
                localStorage.setItem('portal_user', JSON.stringify(response.data.user));
                
                // Set axios default header
                axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
                
                message.success('Login erfolgreich!');
                
                // Redirect to dashboard
                window.location.href = '/business';
            }
        } catch (error) {
            console.error('Login error:', error);
            console.error('Error response:', error.response);
            
            let errorMsg = 'Login fehlgeschlagen';
            
            if (error.response) {
                // Log full error details
                console.error('Status:', error.response.status);
                console.error('Data:', error.response.data);
                
                if (error.response.data?.errors) {
                    // Validation errors
                    const errors = error.response.data.errors;
                    errorMsg = Object.values(errors).flat().join(', ');
                } else if (error.response.data?.message) {
                    errorMsg = error.response.data.message;
                }
            }
            
            setError(errorMsg);
            message.error(errorMsg);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div style={{ 
            minHeight: '100vh', 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center',
            background: '#f0f2f5'
        }}>
            <Card style={{ width: 400 }}>
                <div style={{ textAlign: 'center', marginBottom: 24 }}>
                    <Title level={2}>Business Portal</Title>
                    <p>Melden Sie sich an, um auf Ihr Unternehmenskonto zuzugreifen</p>
                </div>

                {error && (
                    <Alert
                        message="Login Fehler"
                        description={error}
                        type="error"
                        showIcon
                        closable
                        onClose={() => setError(null)}
                        style={{ marginBottom: 16 }}
                    />
                )}

                <Form
                    name="login"
                    onFinish={onFinish}
                    autoComplete="off"
                    layout="vertical"
                    initialValues={{
                        email: 'demo@askproai.de',
                        password: 'demo123'
                    }}
                >
                    <Form.Item
                        name="email"
                        label="E-Mail-Adresse"
                        rules={[
                            { required: true, message: 'Bitte geben Sie Ihre E-Mail ein!' },
                            { type: 'email', message: 'Bitte geben Sie eine gültige E-Mail ein!' }
                        ]}
                    >
                        <Input 
                            prefix={<UserOutlined />} 
                            placeholder="demo@askproai.de" 
                            size="large"
                        />
                    </Form.Item>

                    <Form.Item
                        name="password"
                        label="Passwort"
                        rules={[{ required: true, message: 'Bitte geben Sie Ihr Passwort ein!' }]}
                    >
                        <Input.Password
                            prefix={<LockOutlined />}
                            placeholder="demo123"
                            size="large"
                        />
                    </Form.Item>

                    <Form.Item>
                        <Button 
                            type="primary" 
                            htmlType="submit" 
                            loading={loading}
                            size="large"
                            block
                        >
                            Anmelden
                        </Button>
                    </Form.Item>
                </Form>

                <div style={{ textAlign: 'center', marginTop: 16 }}>
                    <a href="/business/register">Noch kein Konto? Jetzt registrieren</a>
                </div>
                
                <div style={{ 
                    marginTop: 24, 
                    padding: 16, 
                    background: '#f5f5f5', 
                    borderRadius: 4,
                    fontSize: 12,
                    color: '#666'
                }}>
                    <strong>Debug Info:</strong><br/>
                    API Endpoint: /api/auth/portal/login<br/>
                    Test Credentials: demo@askproai.de / demo123
                </div>
            </Card>
        </div>
    );
}

export default Login;