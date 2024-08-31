<?php
//
// Description
// ===========
// This method will produce a PDF of the teachers registrations by parent.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_writingfestivals_templates_teacherRegistrationsPDF(&$ciniki, $tnid, $args) {

    //
    // Make sure festival_id was passed in
    //
    if( !isset($args['festival_id']) || $args['festival_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.147', 'msg'=>'No festival specified'));
    }

    //
    // Make sure teacher_customer_id was passed in
    //
    if( (!isset($args['teacher_customer_id']) || $args['teacher_customer_id'] <= 0) 
        && (!isset($args['billing_customer_id']) || $args['billing_customer_id'] <= 0) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.148', 'msg'=>'No teacher specified'));
    }

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
    // Check if the registrations are passed in args
    //
    if( !isset($args['registrations']) ) {
        //
        // Load the customers registrations
        //
        $strsql = "SELECT r.id, r.uuid, "
            . "r.teacher_customer_id, r.billing_customer_id, r.rtype, r.status, r.status AS status_text, "
            . "r.display_name, r.public_name, "
            . "r.competitor1_id, x.name, x.parent, r.competitor2_id, r.competitor3_id, r.competitor4_id, r.competitor5_id, "
            . "r.class_id, r.timeslot_id, r.title, r.word_count, r.fee, r.payment_type, r.notes, "
            . "c.code AS class_code, c.name AS class_name, c.flags AS class_flags "
            . "FROM ciniki_writingfestival_registrations AS r "
            . "LEFT JOIN ciniki_writingfestival_classes AS c ON ("
                . "r.class_id = c.id "
                . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_writingfestival_competitors AS x ON ("
                . "r.competitor1_id = x.id "
                . "AND x.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE r.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
        if( isset($args['teacher_customer_id']) ) {
            $strsql .= "AND r.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' ";
        } else {
            $strsql .= "AND r.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['billing_customer_id']) . "' ";
        }
        $strsql .= "AND r.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY r.status, r.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'status_text',
                    'display_name', 'public_name', 'competitor1_id', 'parent', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id', 'class_id', 'timeslot_id', 'title', 'word_count', 
                    'fee', 'payment_type', 'notes',
                    'class_code', 'class_name', 'class_flags'),
                'maps'=>array('status_text'=>$maps['registration']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.184', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
    } else {
        $registrations = $args['registrations'];
    }

    //
    // Get the parents
    //
    $parents = array();
    foreach($registrations as $rid => $reg) {
        $registrations[$rid]['fee_display'] = '$' . number_format($reg['fee'], 2);
        if( $reg['parent'] != '' ) {
            if( !isset($parents[$reg['parent']]) ) {
                $parents[$reg['parent']] = array(
                    'name' => $reg['parent'],
                    'num_registrations' => 0,
                    'total_fees' => 0,
                    'competitors' => array(),
                    'registrations' => array(),
                    );
            }
            $parents[$reg['parent']]['registrations'][] = $reg;
            $parents[$reg['parent']]['num_registrations'] += 1;
            $parents[$reg['parent']]['total_fees'] += $reg['fee'];
            if( $reg['competitor1_id'] > 0 ) {
                $parents[$reg['parent']]['competitors'][] = $reg['competitor1_id'];
            }
            if( $reg['competitor2_id'] > 0 ) {
                $parents[$reg['parent']]['competitors'][] = $reg['competitor2_id'];
            }
            if( $reg['competitor3_id'] > 0 ) {
                $parents[$reg['parent']]['competitors'][] = $reg['competitor3_id'];
            }
        }
    }

    //
    // Check if the competitor information passed in
    //
    if( !isset($args['competitors']) ) {
        $strsql = "SELECT c.id, c.uuid, "
            . "c.name, c.parent, c.address, c.city, c.province, c.postal, "
            . "c.phone_home, c.phone_cell, c.email, c.age, c.notes "
            . "FROM ciniki_writingfestival_competitors AS c "
            . "WHERE c.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
        if( isset($args['teacher_customer_id']) ) {
            $strsql .= "AND c.teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['teacher_customer_id']) . "' ";
        } else {
            $strsql .= "AND c.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['billing_customer_id']) . "' ";
        }
        $strsql .= "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'competitors', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'name', 'parent', 'address', 'city', 'province', 'postal', 
                    'phone_home', 'phone_cell', 'email', 'age', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.125', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
        }
        $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();
    } else {
        $competitors = $args['competitors'];
    }

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.107', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.108', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

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
        public $fill = 0;

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $img_width = 0;
            if( $this->header_image != null ) {
                error_log('image');
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
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, 'Page ' . $this->getPageNumGroupAlias() . ' / ' . $this->getPageGroupAlias(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        } 
        public function labelValue($w1, $label, $w2, $value) {
            $lh = 12;
            $border = 'TLRB';
            $lh = $this->getStringHeight($w2, $value);
            $this->SetFont('times', 'B', 12);
            //$this->MultiCell($w1, $lh, $label, $border, 'R', $this->fill, 0);
            $this->MultiCell($w1, $lh, $label, $border, 'R', 1, 0);
            $this->SetFont('times', '', 12);
//            $this->MultiCell($w2, $lh, $value, $border, 'L', $this->fill, 1);
            $this->MultiCell($w2, $lh, $value, $border, 'L', 0, 1);
            $this->fill = !$this->fill;
        }
        public function labelValue2($w1, $l1, $w2, $v1, $w3, $l2, $w4, $v2) {
            $lh = 12;
            $border = 'TLRB';
            $lh = $this->getStringHeight($w2, $v1);
            $lh2 = $this->getStringHeight($w4, $v2);
            if( $lh2 > $lh ) {
                $lh = $lh2;
            }
            $this->SetFont('times', 'B', 12);
            $this->MultiCell($w1, $lh, $l1, $border, 'R', 1, 0);
            $this->SetFont('times', '', 12);
            $this->MultiCell($w2, $lh, $v1, $border, 'L', 0, 0);
            $this->SetFont('times', 'B', 12);
            $this->MultiCell($w3, $lh, $l2, $border, 'R', 1, 0);
            $this->SetFont('times', '', 12);
            $this->MultiCell($w4, $lh, $v2, $border, 'L', 0, 1);
            $this->fill = !$this->fill;
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
    $pdf->footer_msg = $festival['document_footer_msg'];

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
        error_log('logo');
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
    $pdf->SetTitle($festival['name'] . ' - Registrations');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('times', 'B', 12);
    $pdf->SetCellPadding(1.5);

    // add a page
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(232);
    $pdf->SetDrawColor(200);
    $pdf->SetLineWidth(0.15);

    $filename = 'registrations';

    //
    // Go through the sections, divisions and classes
    //
    $c = array(35, 55, 35, 55);
    $r = array(50, 100, 30);
    $nw = array(20, 160);
    $lh = 6;
    $border = '';
    foreach($parents as $parent) {
        $lh = 12;
        $pdf->header_sub_title = $parent['name'];
        $pdf->startPageGroup();
        $pdf->AddPage();

        $competitor_ids = array_unique($parent['competitors']);
        
        //
        // List the parents competitors
        //
        $pdf->Ln(1);
        $pdf->SetFillColor(232);
        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(180, 8, 'Competitors', 'B', 0, 'L', 0);
        $pdf->Ln();
        $pdf->SetFont('times', '', 12);
        foreach($competitors as $competitor) {
            if( !in_array($competitor['id'], $competitor_ids) ) {
                continue;
            }
            if( $pdf->getY() > $pdf->getPageHeight() - 70 ) {
                $pdf->AddPage();
                $pdf->SetFont('times', 'B', 14);
                $pdf->Cell(180, 8, 'Competitors (continued...)', 'B', 0, 'L', 0);
                $pdf->Ln();
                $pdf->SetFont('times', '', 12);
            }
            $address = $competitor['address'];
            $address .= $competitor['city'] != '' ? ($address != '' ? ', ' : '') . $competitor['city'] : '';
            $address .= $competitor['province'] != '' ? ($address != '' ? ', ' : '') . $competitor['province'] : '';
            $address .= $competitor['postal'] != '' ? ($address != '' ? ', ' : '') . $competitor['postal'] : '';
   
            $pdf->fill = 0;
            $pdf->labelValue2($c[0], 'Competitor:', $c[1], $competitor['name'], $c[2], 'Parent:', $c[3], $competitor['parent']); 
            $pdf->labelValue2($c[0], 'Home Phone:', $c[1], $competitor['phone_home'], $c[2], 'Cell Phone:', $c[3], $competitor['phone_cell']); 
            $pdf->labelValue($c[0], 'Address:', $c[1] + $c[2] + $c[3], $address); 
            $pdf->labelValue($c[0], 'Email:', $c[1], $competitor['email'], $c[2], 'Age:', $c[3], $competitor['age']); 
            $pdf->labelValue($c[0], 'Study/Level:', $c[1]+$c[2]+$c[3], $competitor['study_level']); 
            $pdf->labelValue($c[0], 'Notes:', $c[1]+$c[2]+$c[3], $competitor['notes']); 
            $pdf->Ln(5);
        }
        $pdf->Ln();

        //
        // List the registrations
        //
        if( $pdf->getY() > $pdf->getPageHeight() - 50 ) {
            $pdf->AddPage();
        }
        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(180, 8, 'Registrations', 'B', 0, 'L', 0);
        $pdf->Ln();
        $pdf->SetFont('times', 'B', 12);
        $pdf->SetFillColor(224);
        $border = 1;
        $pdf->Cell($r[0], $lh-3, 'Competitor', $border, 0, 'L', 1);
        $pdf->Cell($r[1], $lh-3, 'Class', $border, 0, 'L', 1);
        $pdf->Cell($r[2], $lh-3, 'Competitor', $border, 0, 'R', 1);
        $pdf->Ln();
        $pdf->SetFont('times', '', 12);
        $pdf->SetFillColor(242);
        $fill = 1;
        $border = 1;
        $total = 0;
        foreach($parent['registrations'] as $registration) {
            $description = $registration['class_code'] . ' - ' . $registration['class_name'];
            if( $registration['title'] != '' ) {
                $description .= "\n" . $registration['title'];
            }
            $lh = $pdf->getStringHeight($r[1], $description);

            if( $pdf->getY() > $pdf->getPageHeight() - 30 - $lh ) {
                $pdf->AddPage();
                $pdf->SetFont('times', 'B', 14);
                $pdf->Cell(180, 8, 'Registrations (continued...)', 'B', 0, 'L', 0);
                $pdf->Ln();
                $pdf->SetFont('times', 'B', 12);
                $pdf->SetFillColor(224);
                $border = 1;
                $pdf->Cell($r[0], $lh-3, 'Competitor', $border, 0, 'L', 1);
                $pdf->Cell($r[1], $lh-3, 'Class', $border, 0, 'L', 1);
                $pdf->Cell($r[2], $lh-3, 'Competitor', $border, 0, 'R', 1);
                $pdf->Ln();
                $pdf->SetFont('times', '', 12);
                $pdf->SetFillColor(242);
            }
            $pdf->MultiCell($r[0], $lh, $registration['display_name'], $border, 'L', $fill, 0);
            $pdf->MultiCell($r[1], $lh, $description, $border, 'L', $fill, 0);
            $pdf->MultiCell($r[2], $lh, $registration['fee_display'], $border, 'R', $fill, 1);
            $total += $registration['fee'];

            $fill = !$fill;
        }
        $lh = $pdf->getStringHeight($r[1], 'Total');
        $pdf->SetFont('times', 'B', 12);
        $pdf->MultiCell($r[0]+$r[1], $lh, 'Total', $border, 'R', $fill, 0);
        $pdf->MultiCell($r[2], $lh, '$' . number_format($total, 2), $border, 'R', $fill, 1);
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>$filename . '.pdf');
}
?>
