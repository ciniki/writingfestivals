<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_winnerProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.229', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.230', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.231', 'msg'=>"No festival specified"));
    }

    $winner_permalink = $request['uri_split'][$request['cur_uri_pos']];

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $s['thumbnail-format'];
        if( isset($s['thumbnail-padding-color']) && $s['thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $s['thumbnail-padding-color'];
        } 
    }

    //
    // Get the list of winners for the festival
    //
    $strsql = "SELECT winners.id, "
        . "winners.permalink, "
        . "winners.category, "
        . "winners.award, "
        . "winners.title, "
        . "winners.author, "
        . "winners.permalink, "
        . "winners.image_id, "
        . "winners.synopsis, "
        . "winners.intro, "
        . "winners.content "
        . "FROM ciniki_writingfestival_winners AS winners "
        . "WHERE winners.permalink = '" . ciniki_core_dbQuote($ciniki, $winner_permalink) . "' "
        . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND winners.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "ORDER BY category, sequence, award, title, author "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'winner');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.232', 'msg'=>'Unable to load winner', 'err'=>$rc['err']));
    }
    if( !isset($rc['winner']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.233', 'msg'=>'Unable to find requested winner'));
    }
    $winner = $rc['winner'];
    
    $request['breadcrumbs'][] = array(
        'page-class' => 'page-winner',
        'url' => $request['page']['page'] . '/' . $winner_permalink,
        );

    //
    // Add the title block
    //
    $blocks[] = array(
        'type' => 'title', 
        'title' => $winner['category'] . ($winner['category'] != '' ? ' - ' : '') . $winner['award'],
        );

    $blocks[] = array(
        'type' => 'asideimage',
        'image-id' => $winner['image_id'],
        );

    $content = '';
    if( isset($winner['intro']) && $winner['intro'] != '' ) {
        $blocks[] = array(
            'type' => 'text', 
            'content' => $winner['intro'],
        );
    }

    $blocks[] = array(
        'type' => 'title',
        'level' => 2,
        'title' => $winner['title'],
        'subtitle' => 'by ' . $winner['author'],
        );

    $blocks[] = array(
        'type' => 'text', 
        'content' => $winner['content'],
    );

    return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes');
}
?>
