<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_writingfestivals_templates_programPDF(&$ciniki, $tnid, $args) {

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {    
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the festival
    //
    $strsql = "SELECT ciniki_writingfestivals.id, "
        . "ciniki_writingfestivals.name, "
        . "ciniki_writingfestivals.permalink, "
        . "ciniki_writingfestivals.start_date, "
        . "ciniki_writingfestivals.end_date, "
        . "ciniki_writingfestivals.primary_image_id, "
        . "ciniki_writingfestivals.description, "
        . "ciniki_writingfestivals.document_logo_id, "
        . "ciniki_writingfestivals.document_header_msg, "
        . "ciniki_writingfestivals.document_footer_msg "
        . "FROM ciniki_writingfestivals "
        . "WHERE ciniki_writingfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_writingfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.111', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.112', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.adjudicator1_id, "
        . "sections.adjudicator2_id, "
        . "sections.adjudicator3_id, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "IFNULL(class1.name, '') AS class1_name, "
        . "IFNULL(class2.name, '') AS class2_name, "
        . "IFNULL(class3.name, '') AS class3_name, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
//        . "'' AS title "
        . "registrations.title "
        . "FROM ciniki_writingfestival_schedule_sections AS sections "
        . "LEFT JOIN ciniki_writingfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class2 ON ("
            . "timeslots.class3_id = class2.id " 
            . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_registrations AS registrations ON ("
            . "(timeslots.class1_id = registrations.class_id "  
                . "OR timeslots.class2_id = registrations.class_id "
                . "OR timeslots.class3_id = registrations.class_id "
                . ") "
            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
        $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
    }
    $strsql .= "ORDER BY divisions.division_date, division_id, slot_time, registrations.public_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator1_id', 'adjudicator2_id', 'adjudicator3_id')),
        array('container'=>'divisions', 'fname'=>'division_id', 'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address')),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name')),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['sections']) ) {
        $sections = $rc['sections'];
    } else {
        $sections = array();
    }

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $left_margin = 12;
        public $right_margin = 12;
        public $top_margin = 12;
        public $footer_margin = 12;
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $tenant_details = array();
        public $fw = 116;

        public function Header() {
/*            $this->Ln(8);
            $this->SetFont('times', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell($fw-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('times', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell($fw-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('times', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell($fw-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6); */
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 10);
           // $this->Cell($this->fw, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->Cell($this->fw, 10, 'Page ' . $this->pageNo(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'STATEMENT', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = '';

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Program');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(0);

    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);

    $filename = 'schedule';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(20, 2, 94);
    $w2 = array(7, 109);
    $fw = 116;
    foreach($sections as $section) {
        //
        // Add the adjudicator(s)
        //
        if( isset($section['adjudicator1_id']) && $section['adjudicator1_id'] > 0 ) {
            $strsql = "SELECT c.display_name AS name, "
                . "c.primary_image_id, "
                . "c.full_bio "
                . "FROM ciniki_writingfestival_adjudicators AS a "
                . "LEFT JOIN ciniki_customers AS c ON ("
                    . "a.customer_id = c.id "
                    . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE a.id = '" . ciniki_core_dbQuote($ciniki, $section['adjudicator1_id']) . "' "
                . "AND a.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'customer');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']['full_bio']) && $rc['customer']['full_bio'] != '' ) {
                $bio = $rc['customer']['full_bio'];
                $pdf->AddPage();
                
                //
                // Add Title
                //
                $pdf->SetFont('', 'B', '16');
                $pdf->Cell($fw, 10, $section['name'], 0, 0, 'C', 0);
                $pdf->Ln(7);
                $pdf->SetFont('', '', '14');
                $pdf->Cell($fw, 10, 'Adjudicator ' . $rc['customer']['name'], 0, 'B', 'C', 0);
                $pdf->Ln(12);

                //
                // Add image
                //
                if( isset($rc['customer']['primary_image_id']) && $rc['customer']['primary_image_id'] > 0 ) {
                    $rc = ciniki_images_loadImage($ciniki, $tnid, $rc['customer']['primary_image_id'], 'original');
                    if( $rc['stat'] == 'ok' ) {
                        $image = $rc['image'];
                        $height = $image->getImageHeight();
                        $width = $image->getImageWidth();
                        $image_ratio = $width/$height;
                        $img_width = 53; 
                        $h = ($height/$width) * $img_width;
                        $y = $pdf->getY();
                        $pdf->Image('@'.$image->getImageBlob(), ($fw/2) + $pdf->left_margin + 3, $y, $img_width, 0, 'JPEG', '', 'TL', 2, '150');
                        $pdf->setPageRegions(array(array('page'=>'', 'xt'=>($fw/2) + $pdf->left_margin, 'yt'=>$y, 'xb'=>($fw/2) + $pdf->left_margin, 'yb'=>$y+$h+2, 'side'=>'R')));
                        $pdf->setY($y-2.5);
                    }
                }

                //
                // Add full bio
                //
                $pdf->SetFont('', '', 11);
                $pdf->MultiCell($fw, 10, $bio, 0, 'J', false, 1, '', '', true, 0, false, true, 0, 'T', false);
            }
        }

        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'] . ' Schedule';
        if( isset($args['schedulesection_id']) ) {
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $section['name']) . '_schedule';
        }

        //
        // Output the divisions
        //
        $newpage = 'yes';
        foreach($section['divisions'] as $division) {
            $pdf->AddPage();
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) ) {
                continue;
            }
            //
            // Check if enough room
            //
            $lh = 9;
            $address = '';
            if( $division['address'] != '' ) {
                $s_height = $pdf->getStringHeight($fw, $division['address']);
                $address = $division['address'];
            } else {
                $s_height = 0;
            }
            if( $pdf->getY() > $pdf->getPageHeight() - $lh - 40 - $s_height) {
                $pdf->AddPage();
                $newpage = 'yes';
            } elseif( $newpage == 'no' ) {
                $pdf->Ln();
            }
            $newpage = 'no';

            $pdf->SetFont('', 'B', '14');
            if( $pdf->getStringWidth($division['date'] . ' - ' . $division['name'], '', 'B', 14) > $fw ) {
                $pdf->MultiCell($fw, 10, $division['date'] . "\n" . $division['name'], 0, 'C', 0);
            } else {
                $pdf->Cell($fw, 10, $division['date'] . ' - ' . $division['name'], 0, 0, 'C', 0);
                $pdf->Ln(8);
            }
            $pdf->SetFont('', '', '12');
            if( $address != '' ) {
                $pdf->MultiCell($fw, $lh, $address, 0, 'C', 0, 2);
                $pdf->Ln(2);
            }
            $fill = 1;
            
            //
            // Output the timeslots
            //
            $fill = 0;
            $border = 'T';
            foreach($division['timeslots'] as $timeslot) {
                $name = $timeslot['name'];
                $description = $timeslot['description'];
                if( $timeslot['class1_id'] > 0 ) {  
                    if( $name == '' && $timeslot['class1_name'] != '' ) {
                        $name = $timeslot['class1_name'];
                    }
                    if( isset($timeslot['registrations']) && count($timeslot['registrations']) > 0 ) {
                        if( $description != '' ) {
                            $description .= "\n\n";
                        }
                        foreach($timeslot['registrations'] as $reg) {
                            $description .= ($description != '' ? "\n" : '') . $reg['name'] . ($reg['title'] != '' ? ' - ' . $reg['title'] : '');
                        }
                    }
                }

                if( $description != '' ) {
                    $d_height = $pdf->getStringHeight($w2[1], $description);
                    if( $pdf->getY() > $pdf->getPageHeight() - 40 - $d_height) {
                        $pdf->AddPage();
                        $pdf->SetFont('', 'B', '12');
                        // $pdf->Cell($fw, 10, $division['name'] . ' - ' . $division['date'] . ' (continued...)', 0, 0, 'L', 0);
                        if( $pdf->getStringWidth($division['date'] . ' - ' . $division['name'] . ' (continued...)', '', 'B', 12) > $fw ) {
                            $pdf->MultiCell($fw, 10, $division['date'] . "\n" . $division['name'] . ' (continued...)', 0, 'C', 0);
                        } else {
                            $pdf->Cell($fw, 10, $division['date'] . ' - ' . $division['name'] . ' (continued...)', 0, 0, 'C', 0);
                            $pdf->Ln(8);
                        }
                        $pdf->SetFont('', '', '12');
                        if( $address != '' ) {
                            $pdf->MultiCell($fw, $lh, $address, 0, 'C', 0, 1);
//                            $pdf->Ln($lh);
                        }
                    }
                }
                
                $pdf->SetFont('', 'B');
                $pdf->MultiCell($w[0], $lh, $timeslot['time'], $border, 'R', 0, 0);
//                $pdf->Cell($w[0], $lh, $timeslot['time'], $border, 0, 'R', 0);
//                $pdf->Cell($w[1], $lh, '', $border, 0, 'R', 0);
                $pdf->MultiCell($w[1], $lh, '', $border, 'R', 0, 0);
                $n_height = $pdf->getStringHeight($w[2], $name);
                $pdf->MultiCell($w[2], $n_height, $name, $border, 'L', 0, 1);
                $pdf->SetFont('', '');
//                $pdf->Ln($lh);
    
                if( $description != '' ) {
                    $pdf->writeHTMLCell($w2[0], $d_height, '', '', '', '', 0, false, true, 'L', 1);
                    $pdf->writeHTMLCell($w2[1], $d_height, '', '', preg_replace("/\n/", "<br/>", $description), '', 0, false, true, 'L', 1);
                    $pdf->Ln();
                }
                $pdf->Ln(5);

                $fill=!$fill;
                $border = 'T';
            }
            $pdf->Cell($w[0]+$w[1]+$w[2], 1, '', 'T', 0, 'R', 0);
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
