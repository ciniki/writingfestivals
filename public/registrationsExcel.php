<?php
//
// Description
// -----------
// This method will return the excel export of registrations.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
//
// Returns
// -------
//
function ciniki_writingfestivals_registrationsExcel($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.registrationsExcel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.title, "
        . "registrations.fee AS reg_fee, "
        . "registrations.word_count, "
        . "registrations.payment_type, "
        . "registrations.notes AS reg_notes, "
        . "registrations.teacher_customer_id, "
        . "competitors.id AS competitor_id, "
        . "competitors.name AS competitor_name, "
        . "competitors.parent, "
        . "competitors.address, "
        . "competitors.city, "
        . "competitors.province, "
        . "competitors.postal, "
        . "competitors.phone_home, "
        . "competitors.phone_cell, "
        . "competitors.email, "
        . "competitors.age AS competitor_age, "
        . "competitors.notes "
        . "FROM ciniki_writingfestival_sections AS sections "
        . "LEFT JOIN ciniki_writingfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_competitors AS competitors ON ("
            . "("
                . "registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.id, registrations.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'section_id', 'teacher_customer_id', 
                'display_name', 'class_code', 'class_name', 'title', 'fee'=>'reg_fee', 'word_count', 'payment_type', 'notes'=>'reg_notes'),
            ),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('id'=>'competitor_id', 'name'=>'competitor_name', 'parent', 'address', 'city', 'province', 'postal', 
                'phone_home', 'phone_cell', 'email', 'age'=>'competitor_age', 'notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections = $rc['sections'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');

    //
    // Export to excel
    //
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    $teachers = array();

    $num = 0;
    foreach($sections as $section) {
        if( !isset($section['registrations']) || count($section['registrations']) == 0 ) {
            continue;
        }
        if( $num > 0 ) {
            $objPHPExcelWorksheet = $objPHPExcel->createSheet($num);
        }
        $objPHPExcelWorksheet->setTitle($section['name']);

        $col = 0;
        $row = 1;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Name', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Class Code', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Class Name', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Title', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Fee', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Type', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Teacher Phone', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Notes', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 2', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 3', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 4', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Competitor 5', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Home', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cell', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);

        $objPHPExcelWorksheet->getStyle('A1:AG1')->getFont()->setBold(true);

        $row++;

        foreach($section['registrations'] as $registration) {

            $registration['teacher_name'] = '';
            $registration['teacher_phone'] = '';
            $registration['teacher_email'] = '';
            if( $registration['teacher_customer_id'] > 0 ) {
                if( isset($teachers[$registration['teacher_customer_id']]) ) {
                    $registration['teacher_name'] = $teachers[$registration['teacher_customer_id']]['teacher_name'];
                    $registration['teacher_phone'] = $teachers[$registration['teacher_customer_id']]['teacher_phone'];
                    $registration['teacher_email'] = $teachers[$registration['teacher_customer_id']]['teacher_email'];
                } else {
                    $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
                        array('customer_id'=>$registration['teacher_customer_id'], 'phones'=>'yes', 'emails'=>'yes'));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    if( isset($rc['customer']) ) {
                        $registration['teacher_name'] = $rc['customer']['display_name'];
                        if( isset($rc['customer']['phones']) ) {
                            foreach($rc['customer']['phones'] as $phone) {
                                $registration['teacher_phone'] .= ($registration['teacher_phone'] != '' ? ', ' : '') . $phone['phone_number'];
                            }
                        }
                        if( isset($rc['customer']['emails']) ) {
                            foreach($rc['customer']['emails'] as $email) {
                                $registration['teacher_email'] .= ($registration['teacher_email'] != '' ? ', ' : '') . $email['email']['address'];
                            }
                        }

                        $teachers[$registration['teacher_customer_id']] = array(
                            'teacher_name'=>$registration['teacher_name'],
                            'teacher_phone'=>$registration['teacher_phone'],
                            'teacher_email'=>$registration['teacher_email'],
                            );
                    }
                }
            }

            $col = 0;
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['display_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['class_code'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['class_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['title'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['fee'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['payment_type'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['teacher_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['teacher_email'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $registration['teacher_phone'], false);
            $notes = $registration['notes'];
            if( isset($registration['competitors']) ) {
                foreach($registration['competitors'] as $competitor) {
                    if( $competitor['notes'] != '' ) {
                        $notes .= ($notes != '' ? '  ' : '') . $competitor['notes'];
                    }
                }
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $notes, false);
            
            
            if( isset($registration['competitors']) ) {
                foreach($registration['competitors'] as $competitor) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['name'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['parent'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['address'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['phone_home'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['phone_cell'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $competitor['email'], false);
                }
            }
 
            $row++;
        }

        for($i = 0; $i< 26; $i++) {
            $objPHPExcelWorksheet->getColumnDimension(chr($i+65))->setAutoSize(true);
        }
        $objPHPExcelWorksheet->freezePaneByColumnAndRow(0, 2);
        $num++;
    }

    //
    // Output the excel file
    //
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="writingfestival_registrations.xls"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
