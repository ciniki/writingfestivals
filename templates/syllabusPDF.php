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
function ciniki_writingfestivals_templates_syllabusPDF(&$ciniki, $tnid, $args) {

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
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
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
        . "WHERE ciniki_writingfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_writingfestivals.id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.37', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.38', 'msg'=>'Unable to find Festival'));
    }
    $festival = $rc['festivals'][0];

    //
    // Load the sections, categories and classes
    //
    $strsql = "SELECT ciniki_writingfestival_classes.id, "
        . "ciniki_writingfestival_classes.festival_id, "
        . "ciniki_writingfestival_classes.category_id, "
        . "ciniki_writingfestival_sections.id AS section_id, "
        . "ciniki_writingfestival_sections.name AS section_name, "
        . "ciniki_writingfestival_sections.synopsis AS section_synopsis, "
        . "ciniki_writingfestival_sections.description AS section_description, "
        . "ciniki_writingfestival_categories.id AS category_id, "
        . "ciniki_writingfestival_categories.name AS category_name, "
        . "ciniki_writingfestival_categories.synopsis AS category_synopsis, "
        . "ciniki_writingfestival_categories.description AS category_description, "
        . "ciniki_writingfestival_classes.code, "
        . "ciniki_writingfestival_classes.name, "
        . "ciniki_writingfestival_classes.permalink, "
        . "ciniki_writingfestival_classes.sequence, "
        . "ciniki_writingfestival_classes.flags, "
        . "ciniki_writingfestival_classes.fee "
        . "FROM ciniki_writingfestival_sections, ciniki_writingfestival_categories, ciniki_writingfestival_classes "
        . "WHERE ciniki_writingfestival_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_writingfestival_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . (isset($args['section_id']) ? "AND ciniki_writingfestival_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' " : "")
        . "AND ciniki_writingfestival_sections.id = ciniki_writingfestival_categories.section_id "
        . "AND ciniki_writingfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_writingfestival_categories.id = ciniki_writingfestival_classes.category_id "
        . "AND ciniki_writingfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_writingfestival_sections.sequence, ciniki_writingfestival_sections.name, "
            . "ciniki_writingfestival_categories.sequence, ciniki_writingfestival_categories.name, "
            . "ciniki_writingfestival_classes.sequence, ciniki_writingfestival_classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('name'=>'section_name', 'synopsis'=>'section_synopsis', 'description'=>'section_description')),
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee')),
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
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(90, 10, $this->footer_msg, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
    $pdf->SetTitle($festival['name'] . ' - Syllabus');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height+5, $pdf->right_margin);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(1);

    // add a page
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Go through the sections, categories and classes
    //
    $w = array(30, 120, 30);
    foreach($sections as $section) {
        //
        // Start a new section
        //
        $pdf->header_sub_title = $section['name'] . ' Syllabus';
        $pdf->AddPage();

        //
        // Output the categories
        //
        $newpage = 'yes';
        foreach($section['categories'] as $category) {
            //
            // Check if enough room
            //
            $lh = 9;
            $description = '';
            if( $category['description'] != '' ) {
                $s_height = $pdf->getStringHeight(180, $category['description']);
                $description = $category['description'];
            } elseif( $category['synopsis'] != '' ) {
                $s_height = $pdf->getStringHeight(180, $category['synopsis']);
                $description = $category['synopsis'];
            } else {
                $s_height = 0;
            }
            if( $pdf->getY() > $pdf->getPageHeight() - (count($category['classes']) * $lh) - 50 - $s_height) {
                $pdf->AddPage();
                $newpage = 'yes';
            } elseif( $newpage == 'no' ) {
                $pdf->Ln();
            }
            $newpage = 'no';

            $pdf->SetFont('', 'B', '18');
            $pdf->Cell(180, 10, $category['name'], 0, 0, 'L', 0);
            $pdf->Ln(10);
            $pdf->SetFont('', '', '12');
            if( $description != '' ) {
                $pdf->MultiCell(180, $lh, $description, 0, 'L', 0, 2);
                $pdf->Ln(2);
            }
            $fill = 1;
            $pdf->Cell($w[0], $lh, '', 0, 0, 'L', $fill);
            $pdf->Cell($w[1], $lh, '', 0, 0, 'L', $fill);
            $pdf->Cell($w[2], $lh, 'Fee', 0, 0, 'C', $fill);
            $pdf->Ln();
            
            //
            // Output the classes
            //
            $fill = 0;
            foreach($category['classes'] as $class) {
                $pdf->Cell($w[0], $lh, $class['code'], 0, 0, 'L', $fill);
                $pdf->Cell($w[1], $lh, $class['name'], 0, 0, 'L', $fill);
                $pdf->Cell($w[2], $lh, numfmt_format_currency($intl_currency_fmt, $class['fee'], $intl_currency), 0, 0, 'C', $fill);
                $pdf->Ln($lh);
                $fill=!$fill;
            }
        }
    }


    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
