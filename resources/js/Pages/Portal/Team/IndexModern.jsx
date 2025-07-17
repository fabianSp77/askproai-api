import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Badge } from '../../../components/ui/badge';
import { Switch } from '../../../components/ui/switch';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '../../../components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '../../../components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../../../components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '../../../components/ui/sheet';
import { 
    Users, 
    Mail, 
    Phone, 
    Edit, 
    Trash2, 
    CheckCircle, 
    XCircle, 
    Plus, 
    RefreshCw, 
    Shield, 
    MapPin, 
    Calendar, 
    Clock, 
    UserPlus, 
    Key,
    User,
    Loader2,
    AlertTriangle,
    Building,
    Settings,
    ChevronRight
} from 'lucide-react';
import { useAuth } from '../../../hooks/useAuth';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const TeamIndex = () => {
    const { } = useAuth();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [stats, setStats] = useState({
        total: 0,
        active: 0,
        inactive: 0,
        admins: 0
    });
    const [branches, setBranches] = useState([]);
    const [permissions, setPermissions] = useState([]);
    const [selectedUser, setSelectedUser] = useState(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [inviteDialogOpen, setInviteDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [permissionDialogOpen, setPermissionDialogOpen] = useState(false);
    const [selectedPermissions, setSelectedPermissions] = useState([]);
    const [saving, setSaving] = useState(false);
    
    // Form states
    const [inviteData, setInviteData] = useState({
        name: '',
        email: '',
        branch_id: '',
        role: 'staff',
        permissions: []
    });
    
    const [editData, setEditData] = useState({
        name: '',
        email: '',
        phone: '',
        branch_id: '',
        role: 'staff'
    });

    useEffect(() => {
        fetchUsers();
        fetchFilterOptions();
    }, [search]);

    const fetchUsers = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (search) {
                params.append('search', search);
            }

            const response = await axiosInstance.get(`/team?`);

            if (!response.data) throw new Error('Failed to fetch team members');

            const data = await response.data;
            setUsers(data.users || []);
            setStats(data.stats || {
                total: data.users?.length || 0,
                active: data.users?.filter(u => u.active).length || 0,
                inactive: data.users?.filter(u => !u.active).length || 0,
                admins: data.users?.filter(u => u.role === 'admin').length || 0
            });
        } catch (error) {
            // Silently handle error - will show empty state
        } finally {
            setLoading(false);
        }
    };

    const fetchFilterOptions = async () => {
        try {
            const response = await axiosInstance.get('/team/filters');

            if (!response.data) throw new Error('Failed to fetch filters');

            const data = await response.data;
            setBranches(data.branches || []);
            setPermissions(data.permissions || []);
        } catch (error) {
            // Silently handle filters error
        }
    };

    const handleInvite = async () => {
        setSaving(true);
        try {
            const response = await axiosInstance.get('/team/invite');

            if (!response.data) throw new Error('Failed to send invite');

            setInviteDialogOpen(false);
            setInviteData({
                name: '',
                email: '',
                branch_id: '',
                role: 'staff',
                permissions: []
            });
            fetchUsers();
        } catch (error) {
            // Invite failed - could show toast notification
        } finally {
            setSaving(false);
        }
    };

    const handleUpdateUser = async () => {
        if (!selectedUser) return;
        
        setSaving(true);
        try {
            const response = await axiosInstance.get(`/team/`);

            if (!response.data) throw new Error('Failed to update user');

            setEditDialogOpen(false);
            fetchUsers();
        } catch (error) {
            // Update failed - could show toast notification
        } finally {
            setSaving(false);
        }
    };

    const handleToggleStatus = async (userId, active) => {
        try {
            const response = await axiosInstance.put(`/team/${userId}`, { is_active: !active });

            if (!response.data) throw new Error('Failed to toggle status');

            fetchUsers();
        } catch (error) {
            // Status toggle failed - could show toast notification
        }
    };

    const handleUpdatePermissions = async () => {
        if (!selectedUser) return;
        
        setSaving(true);
        try {
            const response = await axiosInstance.put(`/team/${selectedUser.id}`, { permissions });

            if (!response.data) throw new Error('Failed to update permissions');

            setPermissionDialogOpen(false);
            fetchUsers();
        } catch (error) {
            // Permission update failed - could show toast notification
        } finally {
            setSaving(false);
        }
    };

    const handleDeleteUser = async (userId) => {
        try {
            const response = await axiosInstance.get(`/team/`);

            if (!response.data) throw new Error('Failed to delete user');

            fetchUsers();
        } catch (error) {
            // Deletion failed - could show toast notification
        }
    };

    const openUserDetails = (user) => {
        setSelectedUser(user);
        setSheetOpen(true);
    };

    const openEditDialog = (user) => {
        setSelectedUser(user);
        setEditData({
            name: user.name || '',
            email: user.email || '',
            phone: user.phone || '',
            branch_id: user.branch_id || '',
            role: user.role || 'staff'
        });
        setEditDialogOpen(true);
    };

    const openPermissionDialog = (user) => {
        setSelectedUser(user);
        setSelectedPermissions(user.permissions?.map(p => p.id) || []);
        setPermissionDialogOpen(true);
    };

    const getRoleBadge = (role) => {
        switch (role) {
            case 'admin':
                return <Badge className="bg-purple-100 text-purple-800">Admin</Badge>;
            case 'manager':
                return <Badge className="bg-blue-100 text-blue-800">Manager</Badge>;
            default:
                return <Badge variant="secondary">Mitarbeiter</Badge>;
        }
    };

    const getStatusBadge = (active) => {
        return active ? (
            <Badge className="bg-green-100 text-green-800">
                <CheckCircle className="h-3 w-3 mr-1" />
                Aktiv
            </Badge>
        ) : (
            <Badge variant="destructive">
                <XCircle className="h-3 w-3 mr-1" />
                Inaktiv
            </Badge>
        );
    };

    return (
        <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Team</h1>
                        <p className="text-muted-foreground">Verwalten Sie Ihre Teammitglieder und Berechtigungen</p>
                    </div>
                    <Button onClick={() => setInviteDialogOpen(true)}>
                        <UserPlus className="h-4 w-4 mr-2" />
                        Mitglied einladen
                    </Button>
                </div>

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aktiv</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Inaktiv</CardTitle>
                            <XCircle className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.inactive}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Admins</CardTitle>
                            <Shield className="h-4 w-4 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.admins}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="flex-1">
                                <Input
                                    placeholder="Suchen Sie nach Name, E-Mail oder Telefon..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="max-w-sm"
                                />
                            </div>
                            <Button 
                                variant="outline" 
                                size="icon"
                                onClick={fetchUsers}
                            >
                                <RefreshCw className="h-4 w-4" />
                            </Button>
                        </div>
                    </CardHeader>
                </Card>

                {/* Team Table */}
                <Card>
                    <CardContent className="p-0">
                        {loading ? (
                            <div className="flex items-center justify-center p-8">
                                <Loader2 className="h-8 w-8 animate-spin" />
                            </div>
                        ) : users.length === 0 ? (
                            <div className="text-center p-8">
                                <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                <p className="text-muted-foreground">Keine Teammitglieder gefunden</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Mitglied</TableHead>
                                        <TableHead>Filiale</TableHead>
                                        <TableHead>Rolle</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Beigetreten</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <div 
                                                    className="flex items-center gap-3 cursor-pointer"
                                                    onClick={() => openUserDetails(user)}
                                                >
                                                    <div className="h-10 w-10 rounded-full bg-muted flex items-center justify-center">
                                                        <User className="h-5 w-5" />
                                                    </div>
                                                    <div>
                                                        <div className="font-medium">{user.name}</div>
                                                        <div className="text-sm text-muted-foreground">{user.email}</div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Building className="h-4 w-4 text-muted-foreground" />
                                                    {user.branch?.name || 'Alle Filialen'}
                                                </div>
                                            </TableCell>
                                            <TableCell>{getRoleBadge(user.role)}</TableCell>
                                            <TableCell>{getStatusBadge(user.active)}</TableCell>
                                            <TableCell>
                                                {dayjs(user.created_at).format('DD.MM.YYYY')}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openEditDialog(user)}
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => openPermissionDialog(user)}
                                                    >
                                                        <Key className="h-4 w-4" />
                                                    </Button>
                                                    <Switch
                                                        checked={user.active}
                                                        onCheckedChange={(checked) => handleToggleStatus(user.id, checked)}
                                                    />
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* User Details Sheet */}
                <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                    <SheetContent>
                        <SheetHeader>
                            <SheetTitle>Mitarbeiterdetails</SheetTitle>
                            <SheetDescription>
                                Detaillierte Informationen über das Teammitglied
                            </SheetDescription>
                        </SheetHeader>
                        {selectedUser && (
                            <div className="mt-6 space-y-6">
                                <div className="space-y-4">
                                    <div>
                                        <Label className="text-muted-foreground">Name</Label>
                                        <p className="text-lg font-medium">{selectedUser.name}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">E-Mail</Label>
                                        <p className="flex items-center gap-2">
                                            <Mail className="h-4 w-4" />
                                            {selectedUser.email}
                                        </p>
                                    </div>
                                    {selectedUser.phone && (
                                        <div>
                                            <Label className="text-muted-foreground">Telefon</Label>
                                            <p className="flex items-center gap-2">
                                                <Phone className="h-4 w-4" />
                                                {selectedUser.phone}
                                            </p>
                                        </div>
                                    )}
                                    <div>
                                        <Label className="text-muted-foreground">Filiale</Label>
                                        <p className="flex items-center gap-2">
                                            <Building className="h-4 w-4" />
                                            {selectedUser.branch?.name || 'Alle Filialen'}
                                        </p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Rolle</Label>
                                        <div className="mt-1">{getRoleBadge(selectedUser.role)}</div>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Status</Label>
                                        <div className="mt-1">{getStatusBadge(selectedUser.active)}</div>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground">Beigetreten</Label>
                                        <p>{dayjs(selectedUser.created_at).format('DD. MMMM YYYY')}</p>
                                    </div>
                                    {selectedUser.last_login_at && (
                                        <div>
                                            <Label className="text-muted-foreground">Letzter Login</Label>
                                            <p>{dayjs(selectedUser.last_login_at).format('DD. MMMM YYYY HH:mm')}</p>
                                        </div>
                                    )}
                                </div>

                                {selectedUser.permissions && selectedUser.permissions.length > 0 && (
                                    <div>
                                        <Label className="text-muted-foreground mb-2 block">Berechtigungen</Label>
                                        <div className="space-y-2">
                                            {selectedUser.permissions.map((permission) => (
                                                <Badge key={permission.id} variant="secondary">
                                                    {permission.display_name}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </SheetContent>
                </Sheet>

                {/* Invite Dialog */}
                <Dialog open={inviteDialogOpen} onOpenChange={setInviteDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Teammitglied einladen</DialogTitle>
                            <DialogDescription>
                                Senden Sie eine Einladung an ein neues Teammitglied
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="invite-name">Name</Label>
                                <Input
                                    id="invite-name"
                                    value={inviteData.name}
                                    onChange={(e) => setInviteData({ ...inviteData, name: e.target.value })}
                                    placeholder="Max Mustermann"
                                />
                            </div>
                            <div>
                                <Label htmlFor="invite-email">E-Mail</Label>
                                <Input
                                    id="invite-email"
                                    type="email"
                                    value={inviteData.email}
                                    onChange={(e) => setInviteData({ ...inviteData, email: e.target.value })}
                                    placeholder="max@beispiel.de"
                                />
                            </div>
                            <div>
                                <Label htmlFor="invite-branch">Filiale</Label>
                                <Select
                                    value={inviteData.branch_id}
                                    onValueChange={(value) => setInviteData({ ...inviteData, branch_id: value })}
                                >
                                    <SelectTrigger id="invite-branch">
                                        <SelectValue placeholder="Filiale wählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Alle Filialen</SelectItem>
                                        {branches.map((branch) => (
                                            <SelectItem key={branch.id} value={branch.id}>
                                                {branch.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="invite-role">Rolle</Label>
                                <Select
                                    value={inviteData.role}
                                    onValueChange={(value) => setInviteData({ ...inviteData, role: value })}
                                >
                                    <SelectTrigger id="invite-role">
                                        <SelectValue placeholder="Rolle wählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="staff">Mitarbeiter</SelectItem>
                                        <SelectItem value="manager">Manager</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setInviteDialogOpen(false)}>
                                Abbrechen
                            </Button>
                            <Button onClick={handleInvite} disabled={saving}>
                                {saving && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                Einladung senden
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Edit Dialog */}
                <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Mitarbeiter bearbeiten</DialogTitle>
                            <DialogDescription>
                                Aktualisieren Sie die Mitarbeiterinformationen
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="edit-name">Name</Label>
                                <Input
                                    id="edit-name"
                                    value={editData.name}
                                    onChange={(e) => setEditData({ ...editData, name: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label htmlFor="edit-email">E-Mail</Label>
                                <Input
                                    id="edit-email"
                                    type="email"
                                    value={editData.email}
                                    onChange={(e) => setEditData({ ...editData, email: e.target.value })}
                                />
                            </div>
                            <div>
                                <Label htmlFor="edit-phone">Telefon</Label>
                                <Input
                                    id="edit-phone"
                                    value={editData.phone}
                                    onChange={(e) => setEditData({ ...editData, phone: e.target.value })}
                                    placeholder="+49 123 456789"
                                />
                            </div>
                            <div>
                                <Label htmlFor="edit-branch">Filiale</Label>
                                <Select
                                    value={editData.branch_id}
                                    onValueChange={(value) => setEditData({ ...editData, branch_id: value })}
                                >
                                    <SelectTrigger id="edit-branch">
                                        <SelectValue placeholder="Filiale wählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Alle Filialen</SelectItem>
                                        {branches.map((branch) => (
                                            <SelectItem key={branch.id} value={branch.id}>
                                                {branch.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="edit-role">Rolle</Label>
                                <Select
                                    value={editData.role}
                                    onValueChange={(value) => setEditData({ ...editData, role: value })}
                                >
                                    <SelectTrigger id="edit-role">
                                        <SelectValue placeholder="Rolle wählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="staff">Mitarbeiter</SelectItem>
                                        <SelectItem value="manager">Manager</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setEditDialogOpen(false)}>
                                Abbrechen
                            </Button>
                            <Button onClick={handleUpdateUser} disabled={saving}>
                                {saving && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                Speichern
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Permission Dialog */}
                <Dialog open={permissionDialogOpen} onOpenChange={setPermissionDialogOpen}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Berechtigungen verwalten</DialogTitle>
                            <DialogDescription>
                                Wählen Sie die Berechtigungen für {selectedUser?.name}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 max-h-[400px] overflow-y-auto">
                            {permissions.map((group) => (
                                <div key={group.name} className="space-y-2">
                                    <h4 className="font-medium">{group.display_name}</h4>
                                    <div className="space-y-2">
                                        {group.permissions.map((permission) => (
                                            <div key={permission.id} className="flex items-center space-x-2">
                                                <input
                                                    type="checkbox"
                                                    id={`perm-${permission.id}`}
                                                    checked={selectedPermissions.includes(permission.id)}
                                                    onChange={(e) => {
                                                        if (e.target.checked) {
                                                            setSelectedPermissions([...selectedPermissions, permission.id]);
                                                        } else {
                                                            setSelectedPermissions(selectedPermissions.filter(p => p !== permission.id));
                                                        }
                                                    }}
                                                    className="rounded border-gray-300"
                                                />
                                                <Label 
                                                    htmlFor={`perm-${permission.id}`}
                                                    className="text-sm font-normal cursor-pointer"
                                                >
                                                    {permission.display_name}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setPermissionDialogOpen(false)}>
                                Abbrechen
                            </Button>
                            <Button onClick={handleUpdatePermissions} disabled={saving}>
                                {saving && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                Berechtigungen speichern
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
    );
};

export default TeamIndex;