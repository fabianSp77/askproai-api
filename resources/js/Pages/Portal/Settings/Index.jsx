import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { Switch } from '../../../components/ui/switch';
import { Badge } from '../../../components/ui/badge';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { 
    User, 
    Lock, 
    Bell, 
    Building, 
    Shield, 
    Eye, 
    EyeOff,
    Save,
    Mail,
    Phone,
    MapPin,
    Globe,
    Calendar,
    DollarSign,
    CreditCard,
    FileText,
    AlertTriangle,
    Check,
    X,
    Languages,
    Palette,
    Smartphone,
    Monitor,
    Moon,
    Sun,
    Volume2,
    VolumeX,
    Loader2
} from 'lucide-react';
import { useAuth } from '../../../hooks/useAuth';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import CallNotificationSettings from '../../../components/Portal/Settings/CallNotificationSettings';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const SettingsIndex = () => {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [activeTab, setActiveTab] = useState('profile');
    const [successMessage, setSuccessMessage] = useState('');
    const [errorMessage, setErrorMessage] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [show2FASetup, setShow2FASetup] = useState(false);
    const [qrCode, setQrCode] = useState('');
    const [twoFactorCode, setTwoFactorCode] = useState('');
    
    // Profile data
    const [profileData, setProfileData] = useState({
        name: '',
        email: '',
        phone: '',
        position: '',
        department: ''
    });
    
    // Password data
    const [passwordData, setPasswordData] = useState({
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
    });
    
    // Company data
    const [companyData, setCompanyData] = useState({
        name: '',
        email: '',
        phone: '',
        street: '',
        city: '',
        postal_code: '',
        country: 'DE',
        website: '',
        tax_id: '',
        timezone: 'Europe/Berlin'
    });
    
    // Notification preferences
    const [notificationPreferences, setNotificationPreferences] = useState({
        email_new_call: true,
        email_new_appointment: true,
        email_appointment_reminder: true,
        email_daily_summary: false,
        email_weekly_report: true,
        push_new_call: true,
        push_new_appointment: true,
        push_appointment_reminder: true,
        sms_appointment_reminder: false,
        sms_appointment_confirmation: false
    });
    
    // Appearance preferences
    const [appearancePreferences, setAppearancePreferences] = useState({
        theme: 'light',
        language: 'de',
        date_format: 'DD.MM.YYYY',
        time_format: '24h',
        sound_notifications: true,
        desktop_notifications: true
    });
    
    // Two-factor authentication
    const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);

    useEffect(() => {
        fetchSettings();
    }, []);

    const fetchSettings = async () => {
        try {
            setLoading(true);
            
            // Fetch profile
            try {
                const profileResult = await axiosInstance.get('/settings/profile');
                if (profileResult.data.user) {
                    setProfileData({
                        name: profileResult.data.user.name || '',
                        email: profileResult.data.user.email || '',
                        phone: profileResult.data.user.phone || '',
                        position: profileResult.data.user.position || '',
                        department: profileResult.data.user.department || ''
                    });
                    setTwoFactorEnabled(profileResult.data.user.two_factor_enabled || false);
                }
            } catch (error) {
                setErrorMessage('Fehler beim Laden der Profileinstellungen');
            }
            
            // Fetch company
            try {
                const companyResult = await axiosInstance.get('/settings/company');
                if (companyResult.data.company) {
                    setCompanyData({
                        name: companyResult.data.company.name || '',
                        email: companyResult.data.company.email || '',
                        phone: companyResult.data.company.phone || '',
                        street: companyResult.data.company.street || companyResult.data.company.address || '',
                        city: companyResult.data.company.city || '',
                        postal_code: companyResult.data.company.postal_code || '',
                        country: companyResult.data.company.country || 'DE',
                        website: companyResult.data.company.website || '',
                        tax_id: companyResult.data.company.tax_id || '',
                        timezone: companyResult.data.company.timezone || 'Europe/Berlin'
                    });
                }
            } catch (error) {
                if (error.response?.status === 404) {
                    // Handle case where user has no company
                    setErrorMessage('Keine Firma mit diesem Benutzer verknüpft');
                } else {
                    setErrorMessage('Fehler beim Laden der Firmeneinstellungen');
                }
            }
            
            // Fetch preferences
            try {
                const preferencesResult = await axiosInstance.get('/user/preferences');
                setNotificationPreferences(preferencesResult.data.notifications || notificationPreferences);
                setAppearancePreferences(preferencesResult.data.appearance || appearancePreferences);
            } catch (error) {
                // Preferences might not exist, that's okay
            }
            
        } catch (error) {
            setErrorMessage('Fehler beim Laden der Einstellungen');
        } finally {
            setLoading(false);
        }
    };

    const handleProfileUpdate = async () => {
        try {
            setSaving(true);
            setErrorMessage('');
            setSuccessMessage('');
            
            await axiosInstance.put('/settings/profile', profileData);
            setSuccessMessage('Profil erfolgreich aktualisiert');
            setTimeout(() => setSuccessMessage(''), 3000);
        } catch (error) {
            setErrorMessage(error.response?.data?.message || 'Fehler beim Aktualisieren des Profils');
        } finally {
            setSaving(false);
        }
    };

    const handlePasswordUpdate = async () => {
        try {
            setSaving(true);
            setErrorMessage('');
            setSuccessMessage('');
            
            if (passwordData.new_password !== passwordData.new_password_confirmation) {
                setErrorMessage('Die neuen Passwörter stimmen nicht überein');
                setSaving(false);
                return;
            }
            
            await axiosInstance.put('/settings/password', passwordData);
            setSuccessMessage('Passwort erfolgreich geändert');
            setPasswordData({
                current_password: '',
                new_password: '',
                new_password_confirmation: ''
            });
            setTimeout(() => setSuccessMessage(''), 3000);
        } catch (error) {
            setErrorMessage(error.response?.data?.message || 'Fehler beim Ändern des Passworts');
        } finally {
            setSaving(false);
        }
    };

    const handleCompanyUpdate = async () => {
        try {
            setSaving(true);
            setErrorMessage('');
            setSuccessMessage('');
            
            await axiosInstance.put('/settings/company', companyData);
            setSuccessMessage('Firmendaten erfolgreich aktualisiert');
            setTimeout(() => setSuccessMessage(''), 3000);
        } catch (error) {
            setErrorMessage(error.response?.data?.message || 'Fehler beim Aktualisieren der Firmendaten');
        } finally {
            setSaving(false);
        }
    };

    const handleNotificationUpdate = async () => {
        try {
            setSaving(true);
            setErrorMessage('');
            setSuccessMessage('');
            
            await axiosInstance.put('/settings/notifications', { notifications: notificationPreferences });
            setSuccessMessage('Benachrichtigungseinstellungen erfolgreich aktualisiert');
            setTimeout(() => setSuccessMessage(''), 3000);
        } catch (error) {
            setErrorMessage(error.response?.data?.message || 'Fehler beim Aktualisieren der Benachrichtigungen');
        } finally {
            setSaving(false);
        }
    };

    const handleEnable2FA = async () => {
        try {
            setErrorMessage('');
            
            const response = await axiosInstance.post('/settings/2fa/enable');
            setQrCode(response.data.qr_code);
            setShow2FASetup(true);
        } catch (error) {
            setErrorMessage(error.response?.data?.message || 'Fehler beim Aktivieren der Zwei-Faktor-Authentifizierung');
        }
    };

    const handleConfirm2FA = async () => {
        try {
            setSaving(true);
            setErrorMessage('');
            
            await axiosInstance.post('/settings/2fa/confirm', { code: twoFactorCode });
            setTwoFactorEnabled(true);
            setShow2FASetup(false);
            setQrCode('');
            setTwoFactorCode('');
            setSuccessMessage('Zwei-Faktor-Authentifizierung erfolgreich aktiviert');
            setTimeout(() => setSuccessMessage(''), 3000);
        } catch (error) {
            setErrorMessage(error.response?.data?.message || 'Ungültiger Code');
        } finally {
            setSaving(false);
        }
    };

    const handleDisable2FA = async () => {
        if (!confirm('Sind Sie sicher, dass Sie die Zwei-Faktor-Authentifizierung deaktivieren möchten?')) {
            return;
        }
        
        try {
            setSaving(true);
            setErrorMessage('');
            
            await axiosInstance.post('/settings/2fa/disable');
            setTwoFactorEnabled(false);
            setSuccessMessage('Zwei-Faktor-Authentifizierung erfolgreich deaktiviert');
            setTimeout(() => setSuccessMessage(''), 3000);
        } catch (error) {
            setErrorMessage(error.response?.data?.message || 'Fehler beim Deaktivieren der Zwei-Faktor-Authentifizierung');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-96">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold">Einstellungen</h1>
                    <p className="text-muted-foreground mt-1">
                        Verwalten Sie Ihre persönlichen und Firmeneinstellungen
                    </p>
                </div>

                {/* Success/Error Messages */}
                {successMessage && (
                    <Alert className="border-green-500 bg-green-50">
                        <Check className="h-4 w-4 text-green-600" />
                        <AlertDescription className="text-green-800">
                            {successMessage}
                        </AlertDescription>
                    </Alert>
                )}

                {errorMessage && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                            {errorMessage}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Settings Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                    <TabsList className="grid w-full grid-cols-5">
                        <TabsTrigger value="profile">
                            <User className="h-4 w-4 mr-2" />
                            Profil
                        </TabsTrigger>
                        <TabsTrigger value="security">
                            <Lock className="h-4 w-4 mr-2" />
                            Sicherheit
                        </TabsTrigger>
                        <TabsTrigger value="company">
                            <Building className="h-4 w-4 mr-2" />
                            Firma
                        </TabsTrigger>
                        <TabsTrigger value="notifications">
                            <Bell className="h-4 w-4 mr-2" />
                            Benachrichtigungen
                        </TabsTrigger>
                        <TabsTrigger value="appearance">
                            <Palette className="h-4 w-4 mr-2" />
                            Darstellung
                        </TabsTrigger>
                    </TabsList>

                    {/* Profile Tab */}
                    <TabsContent value="profile" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Persönliche Informationen</CardTitle>
                                <CardDescription>
                                    Aktualisieren Sie Ihre persönlichen Daten
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            value={profileData.name}
                                            onChange={(e) => setProfileData({...profileData, name: e.target.value})}
                                            placeholder="Max Mustermann"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">E-Mail</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={profileData.email}
                                            onChange={(e) => setProfileData({...profileData, email: e.target.value})}
                                            placeholder="max@example.com"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Telefon</Label>
                                        <Input
                                            id="phone"
                                            value={profileData.phone}
                                            onChange={(e) => setProfileData({...profileData, phone: e.target.value})}
                                            placeholder="+49 123 456789"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="position">Position</Label>
                                        <Input
                                            id="position"
                                            value={profileData.position}
                                            onChange={(e) => setProfileData({...profileData, position: e.target.value})}
                                            placeholder="Geschäftsführer"
                                        />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="department">Abteilung</Label>
                                    <Input
                                        id="department"
                                        value={profileData.department}
                                        onChange={(e) => setProfileData({...profileData, department: e.target.value})}
                                        placeholder="Vertrieb"
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <Button onClick={handleProfileUpdate} disabled={saving}>
                                        {saving ? (
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        ) : (
                                            <Save className="h-4 w-4 mr-2" />
                                        )}
                                        Änderungen speichern
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Security Tab */}
                    <TabsContent value="security" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Passwort ändern</CardTitle>
                                <CardDescription>
                                    Aktualisieren Sie Ihr Passwort regelmäßig für bessere Sicherheit
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="current_password">Aktuelles Passwort</Label>
                                    <div className="relative">
                                        <Input
                                            id="current_password"
                                            type={showPassword ? "text" : "password"}
                                            value={passwordData.current_password}
                                            onChange={(e) => setPasswordData({...passwordData, current_password: e.target.value})}
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                                            onClick={() => setShowPassword(!showPassword)}
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </Button>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="new_password">Neues Passwort</Label>
                                    <Input
                                        id="new_password"
                                        type="password"
                                        value={passwordData.new_password}
                                        onChange={(e) => setPasswordData({...passwordData, new_password: e.target.value})}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="new_password_confirmation">Neues Passwort bestätigen</Label>
                                    <Input
                                        id="new_password_confirmation"
                                        type="password"
                                        value={passwordData.new_password_confirmation}
                                        onChange={(e) => setPasswordData({...passwordData, new_password_confirmation: e.target.value})}
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <Button onClick={handlePasswordUpdate} disabled={saving}>
                                        {saving ? (
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        ) : (
                                            <Lock className="h-4 w-4 mr-2" />
                                        )}
                                        Passwort ändern
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Zwei-Faktor-Authentifizierung</CardTitle>
                                <CardDescription>
                                    Erhöhen Sie die Sicherheit Ihres Kontos mit 2FA
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {!twoFactorEnabled ? (
                                    <div className="space-y-4">
                                        <p className="text-sm text-muted-foreground">
                                            Die Zwei-Faktor-Authentifizierung fügt eine zusätzliche Sicherheitsebene hinzu.
                                        </p>
                                        <Button onClick={handleEnable2FA}>
                                            <Shield className="h-4 w-4 mr-2" />
                                            2FA aktivieren
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <div className="flex items-center gap-2">
                                            <Check className="h-5 w-5 text-green-600" />
                                            <span className="text-green-600 font-medium">
                                                Zwei-Faktor-Authentifizierung ist aktiviert
                                            </span>
                                        </div>
                                        <Button variant="destructive" onClick={handleDisable2FA} disabled={saving}>
                                            {saving ? (
                                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                            ) : (
                                                <X className="h-4 w-4 mr-2" />
                                            )}
                                            2FA deaktivieren
                                        </Button>
                                    </div>
                                )}

                                {show2FASetup && (
                                    <div className="mt-6 space-y-4 border-t pt-4">
                                        <h4 className="font-medium">2FA einrichten</h4>
                                        <div className="space-y-4">
                                            <div className="flex justify-center">
                                                <img src={qrCode} alt="2FA QR Code" className="w-48 h-48" />
                                            </div>
                                            <p className="text-sm text-center text-muted-foreground">
                                                Scannen Sie diesen QR-Code mit Ihrer Authenticator-App
                                            </p>
                                            <div className="space-y-2">
                                                <Label htmlFor="2fa_code">Bestätigungscode</Label>
                                                <Input
                                                    id="2fa_code"
                                                    value={twoFactorCode}
                                                    onChange={(e) => setTwoFactorCode(e.target.value)}
                                                    placeholder="123456"
                                                    maxLength={6}
                                                />
                                            </div>
                                            <div className="flex gap-3">
                                                <Button variant="outline" onClick={() => {
                                                    setShow2FASetup(false);
                                                    setQrCode('');
                                                    setTwoFactorCode('');
                                                }}>
                                                    Abbrechen
                                                </Button>
                                                <Button onClick={handleConfirm2FA} disabled={saving || twoFactorCode.length !== 6}>
                                                    {saving ? (
                                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                                    ) : null}
                                                    Bestätigen
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Company Tab */}
                    <TabsContent value="company" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Firmendaten</CardTitle>
                                <CardDescription>
                                    Verwalten Sie Ihre Firmeninformationen
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="company_name">Firmenname</Label>
                                        <Input
                                            id="company_name"
                                            value={companyData.name}
                                            onChange={(e) => setCompanyData({...companyData, name: e.target.value})}
                                            placeholder="Beispiel GmbH"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="company_email">E-Mail</Label>
                                        <Input
                                            id="company_email"
                                            type="email"
                                            value={companyData.email}
                                            onChange={(e) => setCompanyData({...companyData, email: e.target.value})}
                                            placeholder="info@example.com"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="company_phone">Telefon</Label>
                                        <Input
                                            id="company_phone"
                                            value={companyData.phone}
                                            onChange={(e) => setCompanyData({...companyData, phone: e.target.value})}
                                            placeholder="+49 123 456789"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="website">Website</Label>
                                        <Input
                                            id="website"
                                            value={companyData.website}
                                            onChange={(e) => setCompanyData({...companyData, website: e.target.value})}
                                            placeholder="www.example.com"
                                        />
                                    </div>
                                </div>
                                
                                <div className="space-y-2">
                                    <Label htmlFor="street">Straße</Label>
                                    <Input
                                        id="street"
                                        value={companyData.street}
                                        onChange={(e) => setCompanyData({...companyData, street: e.target.value})}
                                        placeholder="Musterstraße 123"
                                    />
                                </div>
                                
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="postal_code">PLZ</Label>
                                        <Input
                                            id="postal_code"
                                            value={companyData.postal_code}
                                            onChange={(e) => setCompanyData({...companyData, postal_code: e.target.value})}
                                            placeholder="12345"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="city">Stadt</Label>
                                        <Input
                                            id="city"
                                            value={companyData.city}
                                            onChange={(e) => setCompanyData({...companyData, city: e.target.value})}
                                            placeholder="Berlin"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="country">Land</Label>
                                        <Input
                                            id="country"
                                            value={companyData.country}
                                            onChange={(e) => setCompanyData({...companyData, country: e.target.value})}
                                            placeholder="DE"
                                        />
                                    </div>
                                </div>
                                
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="tax_id">Steuernummer</Label>
                                        <Input
                                            id="tax_id"
                                            value={companyData.tax_id}
                                            onChange={(e) => setCompanyData({...companyData, tax_id: e.target.value})}
                                            placeholder="DE123456789"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="timezone">Zeitzone</Label>
                                        <Input
                                            id="timezone"
                                            value={companyData.timezone}
                                            onChange={(e) => setCompanyData({...companyData, timezone: e.target.value})}
                                            placeholder="Europe/Berlin"
                                        />
                                    </div>
                                </div>
                                
                                <div className="flex justify-end">
                                    <Button onClick={handleCompanyUpdate} disabled={saving}>
                                        {saving ? (
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        ) : (
                                            <Save className="h-4 w-4 mr-2" />
                                        )}
                                        Änderungen speichern
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Notifications Tab */}
                    <TabsContent value="notifications" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>E-Mail-Benachrichtigungen</CardTitle>
                                <CardDescription>
                                    Wählen Sie, welche E-Mail-Benachrichtigungen Sie erhalten möchten
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Neue Anrufe</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Benachrichtigung bei neuen eingehenden Anrufen
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.email_new_call}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, email_new_call: checked})
                                            }
                                        />
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Neue Termine</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Benachrichtigung bei neuen Terminbuchungen
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.email_new_appointment}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, email_new_appointment: checked})
                                            }
                                        />
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Terminerinnerungen</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Erinnerung an bevorstehende Termine
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.email_appointment_reminder}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, email_appointment_reminder: checked})
                                            }
                                        />
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Tägliche Zusammenfassung</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Tägliche Übersicht Ihrer Aktivitäten
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.email_daily_summary}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, email_daily_summary: checked})
                                            }
                                        />
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Wöchentlicher Bericht</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Wöchentliche Leistungsübersicht
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.email_weekly_report}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, email_weekly_report: checked})
                                            }
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Push-Benachrichtigungen</CardTitle>
                                <CardDescription>
                                    Echtzeitbenachrichtigungen in Ihrem Browser
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Neue Anrufe</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Sofortbenachrichtigung bei eingehenden Anrufen
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.push_new_call}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, push_new_call: checked})
                                            }
                                        />
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Neue Termine</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Sofortbenachrichtigung bei Terminbuchungen
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.push_new_appointment}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, push_new_appointment: checked})
                                            }
                                        />
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Terminerinnerungen</Label>
                                            <p className="text-sm text-muted-foreground">
                                                Push-Erinnerung vor Terminen
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.push_appointment_reminder}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, push_appointment_reminder: checked})
                                            }
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>SMS-Benachrichtigungen</CardTitle>
                                <CardDescription>
                                    SMS-Benachrichtigungen für wichtige Ereignisse
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Alert>
                                    <CreditCard className="h-4 w-4" />
                                    <AlertDescription>
                                        SMS-Benachrichtigungen sind kostenpflichtig (0,09€ pro SMS)
                                    </AlertDescription>
                                </Alert>
                                
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Terminerinnerungen</Label>
                                            <p className="text-sm text-muted-foreground">
                                                SMS-Erinnerung 24h vor Terminen
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.sms_appointment_reminder}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, sms_appointment_reminder: checked})
                                            }
                                        />
                                    </div>
                                    
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-0.5">
                                            <Label>Terminbestätigungen</Label>
                                            <p className="text-sm text-muted-foreground">
                                                SMS-Bestätigung nach Terminbuchung
                                            </p>
                                        </div>
                                        <Switch
                                            checked={notificationPreferences.sms_appointment_confirmation}
                                            onCheckedChange={(checked) => 
                                                setNotificationPreferences({...notificationPreferences, sms_appointment_confirmation: checked})
                                            }
                                        />
                                    </div>
                                </div>
                                
                                <div className="flex justify-end">
                                    <Button onClick={handleNotificationUpdate} disabled={saving}>
                                        {saving ? (
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        ) : (
                                            <Save className="h-4 w-4 mr-2" />
                                        )}
                                        Einstellungen speichern
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                        
                        {/* Call Notification Settings Component */}
                        <CallNotificationSettings />
                    </TabsContent>

                    {/* Appearance Tab */}
                    <TabsContent value="appearance" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Darstellung</CardTitle>
                                <CardDescription>
                                    Passen Sie das Erscheinungsbild der Anwendung an
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label>Farbschema</Label>
                                        <div className="grid grid-cols-3 gap-3">
                                            <Button
                                                variant={appearancePreferences.theme === 'light' ? 'default' : 'outline'}
                                                onClick={() => setAppearancePreferences({...appearancePreferences, theme: 'light'})}
                                                className="w-full"
                                            >
                                                <Sun className="h-4 w-4 mr-2" />
                                                Hell
                                            </Button>
                                            <Button
                                                variant={appearancePreferences.theme === 'dark' ? 'default' : 'outline'}
                                                onClick={() => setAppearancePreferences({...appearancePreferences, theme: 'dark'})}
                                                className="w-full"
                                            >
                                                <Moon className="h-4 w-4 mr-2" />
                                                Dunkel
                                            </Button>
                                            <Button
                                                variant={appearancePreferences.theme === 'system' ? 'default' : 'outline'}
                                                onClick={() => setAppearancePreferences({...appearancePreferences, theme: 'system'})}
                                                className="w-full"
                                            >
                                                <Monitor className="h-4 w-4 mr-2" />
                                                System
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Sprache</Label>
                                        <div className="grid grid-cols-2 gap-3">
                                            <Button
                                                variant={appearancePreferences.language === 'de' ? 'default' : 'outline'}
                                                onClick={() => setAppearancePreferences({...appearancePreferences, language: 'de'})}
                                                className="w-full"
                                            >
                                                <Languages className="h-4 w-4 mr-2" />
                                                Deutsch
                                            </Button>
                                            <Button
                                                variant={appearancePreferences.language === 'en' ? 'default' : 'outline'}
                                                onClick={() => setAppearancePreferences({...appearancePreferences, language: 'en'})}
                                                className="w-full"
                                            >
                                                <Languages className="h-4 w-4 mr-2" />
                                                English
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>Datumsformat</Label>
                                            <select
                                                className="w-full h-10 px-3 rounded-md border border-input bg-background text-sm"
                                                value={appearancePreferences.date_format}
                                                onChange={(e) => setAppearancePreferences({...appearancePreferences, date_format: e.target.value})}
                                            >
                                                <option value="DD.MM.YYYY">31.12.2024</option>
                                                <option value="DD/MM/YYYY">31/12/2024</option>
                                                <option value="MM/DD/YYYY">12/31/2024</option>
                                                <option value="YYYY-MM-DD">2024-12-31</option>
                                            </select>
                                        </div>
                                        
                                        <div className="space-y-2">
                                            <Label>Zeitformat</Label>
                                            <select
                                                className="w-full h-10 px-3 rounded-md border border-input bg-background text-sm"
                                                value={appearancePreferences.time_format}
                                                onChange={(e) => setAppearancePreferences({...appearancePreferences, time_format: e.target.value})}
                                            >
                                                <option value="24h">24 Stunden (14:30)</option>
                                                <option value="12h">12 Stunden (2:30 PM)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between">
                                            <div className="space-y-0.5">
                                                <Label>Ton-Benachrichtigungen</Label>
                                                <p className="text-sm text-muted-foreground">
                                                    Ton bei neuen Benachrichtigungen abspielen
                                                </p>
                                            </div>
                                            <Switch
                                                checked={appearancePreferences.sound_notifications}
                                                onCheckedChange={(checked) => 
                                                    setAppearancePreferences({...appearancePreferences, sound_notifications: checked})
                                                }
                                            />
                                        </div>
                                        
                                        <div className="flex items-center justify-between">
                                            <div className="space-y-0.5">
                                                <Label>Desktop-Benachrichtigungen</Label>
                                                <p className="text-sm text-muted-foreground">
                                                    Browser-Benachrichtigungen anzeigen
                                                </p>
                                            </div>
                                            <Switch
                                                checked={appearancePreferences.desktop_notifications}
                                                onCheckedChange={(checked) => 
                                                    setAppearancePreferences({...appearancePreferences, desktop_notifications: checked})
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="flex justify-end">
                                    <Button disabled={saving}>
                                        {saving ? (
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        ) : (
                                            <Save className="h-4 w-4 mr-2" />
                                        )}
                                        Einstellungen speichern
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
    );
};

export default SettingsIndex;