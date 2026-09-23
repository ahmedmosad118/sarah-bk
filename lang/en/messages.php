<?php

return [
    'unauthorized' => 'Unauthorized',
    'permission_denied' => 'You do not have the required permission (:permission).',
    'permissions_denied_any' => 'You do not have sufficient permissions to perform this action (:permissions).',
    'validation_error' => 'The given data was invalid.',
    'server_error' => 'An internal server error occurred. Please try again later.',
    
    // Tenancy & Provisioning
    'tenant_inactive' => 'Company account / domain is currently suspended. Please contact technical support.',
    'tenant_not_found' => 'Unable to identify tenant company domain or slug. Please provide valid domain or X-Tenant-Slug header.',
    'tenant_provisioned_success' => 'Company tenant and owner account successfully provisioned',
    'tenant_provision_failed' => 'Tenant provisioning failed: :error',
    'tenant_identifier_required' => 'Tenant identifier is required',

    // User Management
    'user_cannot_deactivate_owner' => 'Cannot deactivate the sole system owner.',
    'user_status_updated' => 'User account successfully :status',
    'user_status_active' => 'activated',
    'user_status_inactive' => 'deactivated',

    // Job Titles
    'job_title_cannot_delete_has_users' => 'Cannot delete job titles that are assigned to active users. You may deactivate them instead.',

    // Roles
    'role_owner_cannot_rename' => 'Cannot rename the system Owner role.',
    'role_system_cannot_delete' => 'Cannot delete system core roles.',
    'role_cannot_delete_has_users' => 'Cannot delete role with assigned users. Reassign users first.',
    'role_created_success' => 'Role created successfully',
    'role_updated_success' => 'Role and permissions updated successfully',
    'role_deleted_success' => 'Role deleted successfully',
    'role_save_failed' => 'Error saving role: :error',
    'role_update_failed' => 'Error updating role: :error',

    // Leads & Commercial
    'lead_converted_success' => 'Lead converted successfully',
    'lead_assigned_success' => 'Lead assigned successfully',
    'lead_qualified_success' => 'Lead qualified and updated successfully',
    'lead_already_converted' => 'This lead has already been converted to an opportunity and cannot be converted again.',
    'lead_not_qualified_for_conversion' => 'The lead must be qualified before converting to an opportunity.',

    // Opportunities
    'opportunity_created_success' => 'Opportunity created successfully',
    'opportunity_updated_success' => 'Opportunity updated successfully',
    'opportunity_deleted_success' => 'Opportunity deleted successfully',
    'opportunity_assigned_success' => 'Opportunity assigned successfully',
    'opportunity_stage_updated_success' => 'Opportunity stage updated successfully',
    'opportunity_converted_success' => 'Lead converted and Opportunity created successfully',

    // Site Visits (Phase 6)
    'site_visit_created_success' => 'Site visit scheduled and created successfully',
    'site_visit_updated_success' => 'Site visit details updated successfully',
    'site_visit_deleted_success' => 'Site visit deleted successfully',
    'site_visit_assigned_success' => 'Engineer assigned to site visit successfully',
    'site_visit_scheduled_success' => 'Site visit scheduled successfully',
    'site_visit_completed_success' => 'Site visit completed and assessment recorded successfully',
    'site_visit_cancelled_success' => 'Site visit cancelled successfully',
    'site_visit_photos_uploaded_success' => 'Site photos uploaded successfully',

    // Settings
    'settings_saved_success' => 'Settings saved successfully',
];
