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
    // Measurements (Phase 7)
    'measurement_created_success' => 'Measurement created successfully',
    'measurement_updated_success' => 'Measurement updated successfully',
    'measurement_deleted_success' => 'Measurement deleted successfully',
    'measurement_submitted_review_success' => 'Measurement submitted for engineering review successfully',
    'measurement_approved_success' => 'Measurement approved as authoritative technical truth',
    'measurement_revision_created_success' => 'Measurement revision created successfully',
    'site_visit_rooms_imported_success' => 'Site visit room spaces imported successfully',
    'measurement_requires_opportunity' => 'Measurement requires a valid commercial opportunity link.',
    'opportunity_customer_mismatch' => 'The selected opportunity belongs to a different customer than the site visit.',
    'approved_measurement_cannot_be_edited' => 'Approved measurements cannot be edited. Please create a revision instead.',
    'approved_measurement_cannot_be_deleted' => 'Approved measurements cannot be deleted to preserve technical quantity history.',
    'only_draft_can_be_submitted_for_review' => 'Only draft measurements can be submitted for review.',
    'measurement_cannot_be_approved_in_current_state' => 'Measurement cannot be approved in its current state.',
    'cannot_approve_empty_measurement' => 'Cannot approve an empty measurement with no item lines.',
    'measurement_items_missing_required_info' => 'All measurement items must have room name and item name specified.',
    'dxf_imported_success' => 'Room geometry and areas imported from DXF file successfully as draft measurement.',
    'dxf_invalid_format' => 'Invalid or corrupt DXF file. Only valid DXF ASCII files are supported.',
    'dxf_no_rooms_found' => 'No closed room polylines were found on the selected layer.',
    'site_visit_belongs_to_another_opportunity' => 'The site visit is already linked to another opportunity.',
    'measurement_unit_type_mismatch' => 'The specified unit is incompatible with the measurement type.',

    // Settings
    'settings_saved_success' => 'Settings saved successfully',
];
