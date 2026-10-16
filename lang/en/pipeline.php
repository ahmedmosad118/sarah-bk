<?php

return [
    'system' => 'System',

    // Site visit
    'schedule_site_visit_title' => 'Schedule First Site Inspection',
    'schedule_site_visit_desc' => 'No site visits recorded yet for this opportunity. The next logical step is to schedule an inspection to verify real site conditions.',
    'schedule_site_visit_action' => '+ Schedule Site Visit',

    // Measurement
    'create_measurement_title' => 'Prepare Quantity Takeoff & Measurements',
    'create_measurement_desc' => 'Site visit completed. The next step is translating observations and dimensions into an engineering quantity takeoff.',
    'create_measurement_action' => '+ Create Measurement',
    'approve_measurement_title' => 'Review & Approve Engineering Measurement',
    'approve_measurement_desc' => 'Measurement is currently under technical review. It must be approved as the single source of truth for quantities.',
    'approve_measurement_action' => 'Review & Approve Measurement',
    'submit_measurement_title' => 'Submit Measurement for Technical Review',
    'submit_measurement_desc' => 'Measurement is in Draft status. The next step is reviewing dimensions and deductions, then submitting for review.',
    'submit_measurement_action' => 'Review & Submit Measurement',

    // Scope
    'create_scope_title' => 'Prepare Scope of Work (SOW)',
    'create_scope_desc' => 'Measurement approved. The next step is defining work packages, specifications, inclusions, and exclusions based on approved quantities.',
    'create_scope_action' => '+ Prepare Scope of Work',
    'approve_scope_title' => 'Review & Approve Scope of Work',
    'approve_scope_desc' => 'Scope of work document is under engineering review. It must be approved to lock technical specifications.',
    'approve_scope_action' => 'Review & Approve Scope',
    'submit_scope_title' => 'Complete & Submit Scope for Review',
    'submit_scope_desc' => 'Scope of work is currently in Draft status. Link items and submit for engineering approval.',
    'submit_scope_action' => 'Complete & Submit Scope',

    // BOQ
    'create_boq_title' => 'Prepare Bill of Quantities (BOQ)',
    'create_boq_stale_title' => 'Prepare Bill of Quantities for New Approved Scope',
    'create_boq_desc' => 'Scope of work approved. The next step is generating the official pricing-ready Bill of Quantities from approved scope items and quantities.',
    'create_boq_stale_desc' => 'A new revision of the scope of work (V:scope_version) has been approved. The current approved BOQ (#:boq_number) references an older scope; a new BOQ must be generated.',
    'create_boq_action' => '+ Create Bill of Quantities',
    'submit_boq_title' => 'Complete & Submit BOQ for Review',
    'submit_boq_desc' => 'Current BOQ is in Draft status. Review generated items and quantities, then submit for engineering review.',
    'submit_boq_action' => 'Review & Submit BOQ',
    'approve_boq_title' => 'Review & Approve Bill of Quantities',
    'approve_boq_desc' => 'BOQ is under engineering review. Approve it to establish the locked quantity baseline for cost estimation.',
    'approve_boq_action' => 'Review & Approve BOQ',

    // Estimation
    'create_estimation_title' => 'Prepare Cost & Pricing Study (Estimation)',
    'create_estimation_stale_title' => 'Prepare Cost Study for New Approved BOQ',
    'create_estimation_desc' => 'BOQ approved and quantities frozen. The next step is cost estimation (direct costs + overhead) to establish internal project costs.',
    'create_estimation_stale_desc' => 'A new revision of the BOQ (#:boq_number) has been approved. The current estimation (#:estimation_number) references an older BOQ; prepare an updated cost study.',
    'create_estimation_action' => '+ Prepare Cost Estimation',
    'submit_estimation_title' => 'Complete & Submit Cost Study for Review',
    'submit_estimation_desc' => 'Estimation is in Draft status. Complete direct cost rates and overhead percentage, then submit for management review.',
    'submit_estimation_action' => 'Review & Submit Estimation',
    'approve_estimation_title' => 'Review & Approve Cost Estimation',
    'approve_estimation_desc' => 'Estimation is under administrative and financial audit. Approve it to establish the baseline for the commercial quotation.',
    'approve_estimation_action' => 'Review & Approve Estimation',

    // Quotation
    'create_quotation_title' => 'Prepare Client Quotation',
    'create_quotation_stale_title' => 'Prepare Updated Quotation for New Cost Study',
    'create_quotation_desc' => 'Cost estimation approved. The next step is applying target profit margins, discounts, and generating the client quotation.',
    'create_quotation_stale_desc' => 'A new cost study (#:estimation_number) has been approved. The current quotation (#:quotation_number) was based on older costs; generate an updated quotation.',
    'create_quotation_action' => '+ Create Quotation',
    'submit_quotation_title' => 'Complete & Submit Quotation for Review',
    'submit_quotation_desc' => 'Quotation is in Draft status. Review markup margins, discounts, and commercial terms, then submit for internal review.',
    'submit_quotation_action' => 'Review & Submit Quotation',
    'approve_quotation_title' => 'Review & Approve Quotation Internally',
    'approve_quotation_desc' => 'Quotation is under commercial review. Approve it internally before sending officially to the client.',
    'approve_quotation_action' => 'Review & Approve Quotation',
    'send_quotation_title' => 'Send Approved Quotation to Client',
    'send_quotation_desc' => 'Quotation approved internally. Export and officially send to the client for evaluation.',
    'send_quotation_action' => 'Send Quotation to Client',
    'record_response_title' => 'Record Client Response on Quotation',
    'record_response_desc' => 'Quotation sent to client. Record official client feedback (acceptance or rejection) and negotiation notes.',
    'record_response_action' => 'Record Client Response',

    // Contract & Terminal
    'ready_for_contract_title' => 'Client Accepted! Ready for Contract',
    'ready_for_contract_desc' => 'Client has officially approved the quotation! Commercial negotiations are complete; next step is contract drafting.',
    'quotation_rejected_or_expired_title' => 'Quotation Requires Review or Renegotiation',
    'quotation_rejected_or_expired_desc' => 'Quotation status with client is :status. You can issue a new revision or renegotiate terms.',
    'quotation_rejected_or_expired_action' => 'View Quotations',
];
