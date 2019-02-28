<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_objects($ciniki) {
    
    $objects = array();
    $objects['festival'] = array(
        'name'=>'Festival',
        'o_name'=>'festival',
        'o_container'=>'festivals',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestivals',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'start_date'=>array('name'=>'Start'),
            'end_date'=>array('name'=>'End'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('name'=>'Flags', 'default'=>'0'),
            'earlybird_date'=>array('name'=>'Earlybird End Date', 'default'=>''),
            'primary_image_id'=>array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'document_logo_id'=>array('name'=>'Document Header Logo', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'document_header_msg'=>array('name'=>'Document Header Message', 'default'=>''),
            'document_footer_msg'=>array('name'=>'Document Footer Message', 'default'=>''),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['customer'] = array(
        'name' => 'Customer',
        'sync' => 'yes',
        'o_name' => 'customer',
        'o_container' => 'customers',
        'table' => 'ciniki_writingfestival_customers',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'ctype' => array('name'=>'Type', 'default'=>'0'),
            ),
        'history_table' => 'ciniki_writingfestival_history',
        );
    $objects['adjudicator'] = array(
        'name'=>'Adjudicator',
        'o_name'=>'adjudicator',
        'o_container'=>'adjudicators',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_adjudicators',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['section'] = array(
        'name'=>'Section',
        'o_name'=>'section',
        'o_container'=>'sections',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_sections',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'primary_image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['category'] = array(
        'name'=>'Category',
        'o_name'=>'category',
        'o_container'=>'categories',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_categories',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'section_id'=>array('name'=>'Section', 'ref'=>'ciniki.writingfestivals.section'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'primary_image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['class'] = array(
        'name'=>'Class',
        'o_name'=>'class',
        'o_container'=>'classes',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_classes',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'category_id'=>array('name'=>'Category', 'ref'=>'ciniki.writingfestivals.category'),
            'code'=>array('name'=>'Code'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'earlybird_fee'=>array('name'=>'Earlybird Fee', 'type'=>'currency', 'default'=>'0'),
            'fee'=>array('name'=>'Fee', 'type'=>'currency', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['file'] = array(
        'name'=>'File',
        'o_name'=>'file',
        'o_container'=>'files',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_files',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'extension'=>array('name'=>'Extension'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'webflags'=>array('name'=>'Options', 'default'=>'0'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'org_filename'=>array('name'=>'Original Filename', 'default'=>''),
            'publish_date'=>array('name'=>'Publish Date', 'default'=>''),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['competitor'] = array(
        'name'=>'Competitor',
        'o_name'=>'competitor',
        'o_container'=>'competitors',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_competitors',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'billing_customer_id'=>array('name'=>'Billing Customer', 'ref'=>'ciniki.customers.customer', 'default'=>0),
            'name'=>array('name'=>'Name'),
            'public_name'=>array('name'=>'Public Name', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'parent'=>array('name'=>'Parent'),
            'address'=>array('name'=>'Address', 'default'=>''),
            'city'=>array('name'=>'City', 'default'=>''),
            'province'=>array('name'=>'Province', 'default'=>''),
            'postal'=>array('name'=>'Postal Code', 'default'=>''),
            'phone_home'=>array('name'=>'Home Phone', 'default'=>''),
            'phone_cell'=>array('name'=>'Cell Phone', 'default'=>''),
            'email'=>array('name'=>'Email', 'default'=>''),
            'age'=>array('name'=>'Age', 'default'=>''),
//            'study_level'=>array('name'=>'Study/Level', 'default'=>''),
//            'instrument'=>array('name'=>'Instrument', 'default'=>''),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['registration'] = array(
        'name'=>'Registration',
        'o_name'=>'registration',
        'o_container'=>'registrations',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_registrations',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'teacher_customer_id'=>array('name'=>'Teacher', 'ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'billing_customer_id'=>array('name'=>'Billing', 'ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'rtype'=>array('name'=>'Type'),
            'status'=>array('name'=>'Status'),
            'invoice_id'=>array('name'=>'Status', 'default'=>'0'),
            'display_name'=>array('name'=>'Name', 'default'=>''),
            'public_name'=>array('name'=>'Name', 'default'=>''),
            'competitor1_id'=>array('name'=>'Competitor 1', 'ref'=>'ciniki.writingfestivals.competitor', 'default'=>'0'),
            'competitor2_id'=>array('name'=>'Competitor 2', 'ref'=>'ciniki.writingfestivals.competitor', 'default'=>'0'),
            'competitor3_id'=>array('name'=>'Competitor 3', 'ref'=>'ciniki.writingfestivals.competitor', 'default'=>'0'),
            'competitor4_id'=>array('name'=>'Competitor 4', 'ref'=>'ciniki.writingfestivals.competitor', 'default'=>'0'),
            'competitor5_id'=>array('name'=>'Competitor 5', 'ref'=>'ciniki.writingfestivals.competitor', 'default'=>'0'),
            'class_id'=>array('name'=>'Class', 'ref'=>'ciniki.writingfestivals.class'),
            'timeslot_id'=>array('name'=>'Timeslot', 'ref'=>'ciniki.writingfestivals.scheduletimeslot', 'default'=>'0'),
            'title'=>array('name'=>'Title', 'default'=>''),
            'word_count'=>array('name'=>'Word Count', 'default'=>''),
            'fee'=>array('name'=>'Fee', 'type'=>'currency', 'default'=>'0'),
            'payment_type'=>array('name'=>'Payment Type', 'default'=>'0'),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['schedulesection'] = array(
        'name'=>'Schedule Section',
        'o_name'=>'schedulesection',
        'o_container'=>'schedulesections',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_schedule_sections',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'name'=>array('name'=>'Name'),
            'adjudicator1_id'=>array('name'=>'First Adjudicator', 'id'=>'ciniki.writingfestivals.adjudicator', 'default'=>'0'),
            'adjudicator2_id'=>array('name'=>'Second Adjudicator', 'id'=>'ciniki.writingfestivals.adjudicator', 'default'=>'0'),
            'adjudicator3_id'=>array('name'=>'Third Adjudicator', 'id'=>'ciniki.writingfestivals.adjudicator', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['scheduledivision'] = array(
        'name'=>'Schedule Division',
        'o_name'=>'scheduledivision',
        'o_container'=>'scheduledivisions',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_schedule_divisions',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'ssection_id'=>array('name'=>'Section', 'ref'=>'ciniki.writingfestivals.schedulesection'),
            'name'=>array('name'=>'Name'),
            'division_date'=>array('name'=>'Date'),
            'address'=>array('name'=>'Address', 'default'=>''),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['scheduletimeslot'] = array(
        'name'=>'Schedule Time Slot',
        'o_name'=>'scheduletimeslot',
        'o_container'=>'scheduletimeslot',
        'sync'=>'yes',
        'table'=>'ciniki_writingfestival_schedule_timeslots',
        'fields'=>array(
            'festival_id'=>array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'sdivision_id'=>array('name'=>'Division', 'ref'=>'ciniki.writingfestivals.scheduledivision'),
            'slot_time'=>array('name'=>'Time'),
            'class1_id'=>array('name'=>'Class', 'ref'=>'ciniki.writingfestivals.class'),
            'class2_id'=>array('name'=>'Class', 'ref'=>'ciniki.writingfestivals.class', 'default'=>'0'),
            'class3_id'=>array('name'=>'Class', 'ref'=>'ciniki.writingfestivals.class', 'default'=>'0'),
            'name'=>array('name'=>'Name'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            ),
        'history_table'=>'ciniki_writingfestival_history',
        );
    $objects['setting'] = array(
        'name' => 'Setting',
        'sync' => 'yes',
        'o_name' => 'setting',
        'o_container' => 'settings',
        'table' => 'ciniki_writingfestival_settings',
        'fields' => array(
            'festival_id' => array('name'=>'Festival', 'ref'=>'ciniki.writingfestivals.festival'),
            'detail_key' => array('name'=>'Key'),
            'detail_value' => array('name'=>'Value', 'default'=>''),
            ),
        'history_table' => 'ciniki_writingfestival_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
