<?php

// Fix for StaffResource 403 error
// In app/Filament/Admin/Resources/StaffResource.php
// Add or update the canViewAny method:
/*
public static function canViewAny(): bool
{
    return true; // Or implement proper authorization logic
}
*/

