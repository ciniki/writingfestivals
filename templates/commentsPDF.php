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
function ciniki_writingfestivals_templates_commentsPDF(&$ciniki, $tnid, $args) {

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

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.109', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.110', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load adjudicators
    //
    $strsql = "SELECT ciniki_writingfestival_adjudicators.id, "
        . "ciniki_writingfestival_adjudicators.festival_id, "
        . "ciniki_writingfestival_adjudicators.customer_id, "
        . "ciniki_customers.display_name "
        . "FROM ciniki_writingfestival_adjudicators "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_writingfestival_adjudicators.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_writingfestival_adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND ciniki_writingfestival_adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'customer_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.171', 'msg'=>'Unable to get adjudicator list', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    if( isset($args['registration_id']) && $args['registration_id'] > 0 ) {
        $strsql = "SELECT 1 AS section_id, "
            . "'' AS section_name, "
            . "1 AS division_id, "
            . "'' AS division_name, "
            . "1 AS timeslot_id, "
            . "'' AS timeslot_name, "
            . "0 AS class1_id, "
            . "0 AS class2_id, "
            . "0 AS class3_id, "
            . "'' AS class1_name, "
            . "'' AS class2_name, "
            . "'' AS class3_name, "
            . "'' AS description, "
            . "registrations.id AS reg_id, "
            . "registrations.display_name, "
            . "registrations.public_name, "
            . "registrations.title, "
            . "IFNULL(classes.name, '') AS class_name, "
            . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
            . "IFNULL(comments.id, 0) AS comment_id, "
            . "IFNULL(comments.adjudicator_id, 0) AS adjudicator_id, "
            . "IFNULL(comments.comments, '') AS comments, "
            . "IFNULL(comments.grade, '') AS grade, "
            . "IFNULL(comments.score, '') AS score "
            . "FROM ciniki_writingfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_writingfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_writingfestival_comments AS comments ON ("
                . "registrations.id = comments.registration_id "
                . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "";
    } else {
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "timeslots.id AS timeslot_id, "
            . "timeslots.name AS timeslot_name, "
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
            . "registrations.title, "
            . "IFNULL(classes.name, '') AS class_name, "
            . "IFNULL(registrations.competitor2_id, 0) AS competitor2_id, "
            . "IFNULL(comments.id, 0) AS comment_id, "
            . "IFNULL(comments.adjudicator_id, 0) AS adjudicator_id, "
            . "IFNULL(comments.comments, '') AS comments, "
            . "IFNULL(comments.grade, '') AS grade, "
            . "IFNULL(comments.score, '') AS score "
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
            . "LEFT JOIN ciniki_writingfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_writingfestival_comments AS comments ON ("
                . "registrations.id = comments.registration_id "
                . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        if( isset($args['schedulesection_id']) && $args['schedulesection_id'] > 0 ) {
            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' ";
        }
        $strsql .= "ORDER BY divisions.division_date, division_id, slot_time "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name')),
        array('container'=>'divisions', 'fname'=>'division_id', 'fields'=>array('id'=>'division_id', 'name'=>'division_name')),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name')),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title', 'class_name', 'competitor2_id')),
        array('container'=>'comments', 'fname'=>'comment_id', 'fields'=>array('id'=>'comment_id', 'adjudicator_id', 'comments', 'grade', 'score')),
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
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 15;
        public $header_image = null;
        public $header_title = '';
        public $header_sub_title = '';
        public $header_msg = '';
        public $header_height = 0;      // The height of the image and address
        public $footer_msg = '';
        public $tenant_details = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $img_width = 0;
            if( $this->header_image != null ) {
                $height = $this->header_image->getImageHeight();
                $width = $this->header_image->getImageWidth();
                $image_ratio = $width/$height;
                $img_width = 60;
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, 12, 0, $this->header_height-5, 'JPEG', '', 'L', 2, '150');
                }
            }

            $this->Ln(8);
            $this->SetFont('times', 'B', 20);
            if( $img_width > 0 ) {
                $this->Cell($img_width, 10, '', 0);
            }
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 12, $this->header_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(7);

            $this->SetFont('times', 'B', 14);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_sub_title, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);

            $this->SetFont('times', 'B', 12);
            $this->setX($this->left_margin + $img_width);
            $this->Cell(180-$img_width, 10, $this->header_msg, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln(6);
        }

        // Page footer
        public function Footer() {
/*            // Position at 15 mm from bottom
            $this->SetY(-40);
            $this->SetFont('helvetica', 'I', 12);
            $this->Cell(45, 12, "Adjudicator's Signature ", 0, false, 'L', 0, '', 0, false);
            $this->Cell(85, 12, "", 'B', false, 'L', 0, '', 0, false);
            $this->Cell(30, 12, "Level ", 0, false, 'R', 0, '', 0, false);
            $this->Cell(20, 12, "", 'B', false, 'L', 0, '', 0, false);
            $this->Ln(14);
            
            $this->SetTextColor(128);
            $this->SetFont('helvetica', 'I', 10);
            $this->Cell(180, 10, "Levels are as follows:", 0, false, 'L', 0, '', 0, false);
            $this->Ln(6);
            $this->SetFont('helvetica', 'BI', 10);
            $this->Cell(180, 10, "G = Gold (85 and above)  S = Silver (80-84)  B = Bronze (79 and under)", 0, false, 'L', 0, '', 0, false);
            $this->SetTextColor(0); */
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_title = $festival['name'];
    $pdf->header_sub_title = '';
    $pdf->header_msg = $festival['document_header_msg'];
    $pdf->footer_msg = '';

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
    }

    //
    // Load the header image
    //
    if( isset($festival['document_logo_id']) && $festival['document_logo_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, $festival['document_logo_id'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle($festival['name'] . ' - Comments');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);
// add a page $pdf->SetFillColor(246); $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetLineWidth(0.1);
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);

    $filename = 'comments';

    //
    // Go through the sections, divisions and classes
    //
    $w = array(35, 145);
    foreach($sections as $section) {
        //
        // Start a new section
        //
        $pdf->header_sub_title = "Adjudicator's Comments";
        if( isset($args['schedulesection_id']) ) {
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $section['name']) . '_comments';
        }

        //
        // Output the divisions
        //
        $newpage = 'yes';
        foreach($section['divisions'] as $division) {
            //
            // Skip empty divisions
            //
            if( !isset($division['timeslots']) ) {
                continue;
            }
            
            //
            // Output the timeslots
            //
            foreach($division['timeslots'] as $timeslot) {
                if( !isset($timeslot['registrations']) ) {
                    continue;
                }

                foreach($timeslot['registrations'] as $reg) {
                    foreach($reg['comments'] as $comment) {
                        $pdf->AddPage();
                        $border = 'T';
                        $lh = 8;
                        $pdf->SetFont('helvetica', '', 12);
                        $lh = $pdf->getNumLines($reg['class_name'], $w[1]) * 8;
                        $pdf->SetFont('helvetica', 'B', 12);
                        $pdf->MultiCell($w[0], $lh, 'Class: ', $border, 'R', 0, 0, '', '');
                        $pdf->SetFont('helvetica', '', 12);
                        $pdf->MultiCell($w[1], $lh, $reg['class_name'], $border, 'L', 0, 0, '', '');
                        $pdf->Ln($lh);
                        $pdf->SetFont('helvetica', 'B', 12);

                        $border = ($reg['title'] != '' ? '' : 'B');

                        $lh = $pdf->getNumLines($reg['name'], $w[1]) * 8;
                        if( $reg['competitor2_id'] > 0 ) {
                            $pdf->MultiCell($w[0], $lh, 'Participants: ', $border, 'R', 0, 0, '', '');
                        } else {
                            $pdf->MultiCell($w[0], $lh, 'Participant: ', $border, 'R', 0, 0, '', '');
                        }
                        $pdf->SetFont('helvetica', '', 12);
                        $pdf->MultiCell($w[1], $lh, $reg['name'], $border, 'L', 0, 0, '', '');
                        $pdf->Ln($lh);

                        if( $reg['title'] != '' ) {
                            $lh = ($pdf->getNumLines($reg['title'], $w[1]) * 6) + 3;
                            $border = 'B';
                            $pdf->SetFont('helvetica', 'B', 12);
                            $pdf->MultiCell($w[0], $lh, 'Title: ', $border, 'R', 0, 0, '', '');
                            $pdf->SetFont('helvetica', '', 12);
                            $pdf->MultiCell($w[1], $lh, $reg['title'], $border, 'L', 0, 0, '', '');
                            $pdf->Ln($lh);
                        }

                        if( isset($comment['comments']) && $comment['comments'] != '' ) {
                            $pdf->Ln(2);
                            $pdf->MultiCell($w[0] + $w[1], $lh, $comment['comments'], 0, 'L', 0, 1, '', '');
                        }

                        // Position at 15 mm from bottom
                        $pdf->SetDrawColor(50);
                        $pdf->SetY(-45);
                        $pdf->SetFont('helvetica', 'I', 12);
                        if( $comment['grade'] != '' && isset($adjudicators[$comment['adjudicator_id']]['name']) ) {
                            $pdf->Cell(45, 12, "            Adjudicator", 0, false, 'L', 0, '', 0, false);
                            $pdf->Cell(85, 12, $adjudicators[$comment['adjudicator_id']]['name'], 'B', false, 'L', 0, '', 0, false);
                        } else {
                            $pdf->Cell(45, 12, "Adjudicator's Signature ", 0, false, 'L', 0, '', 0, false);
                            $pdf->Cell(85, 12, "", 'B', false, 'L', 0, '', 0, false);
                        }
//                        $pdf->Cell(30, 12, "Mark ", 0, false, 'R', 0, '', 0, false);
//                        $pdf->Cell(20, 12, $comment['score'], 'B', false, 'L', 0, '', 0, false);
                        $pdf->Ln(14);
                        
                        $pdf->SetTextColor(128);
                        $pdf->SetFont('helvetica', 'I', 10);
//                        $pdf->Cell(180, 10, "Levels are as follows:", 0, false, 'L', 0, '', 0, false);
                        $pdf->Ln(6);
                        $pdf->SetFont('helvetica', 'BI', 10);
//                        $pdf->Cell(180, 10, "G = Gold (85 and above)  S = Silver (80-84)  B = Bronze (79 and under)", 0, false, 'L', 0, '', 0, false);
                        $pdf->SetTextColor(0);
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
