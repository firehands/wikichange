<?php
/**
 * CSV export for SMW Queries
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer class for generating CSV output
 * @author Nathan R. Yergler
 * @author Markus Krötzsch
 * @ingroup SMWQuery
 */
class SMWCsvResultPrinter extends SMWResultPrinter {
	protected $m_sep;

	protected function readParameters( $params, $outputmode ) {
		SMWResultPrinter::readParameters( $params, $outputmode );
		if ( array_key_exists( 'sep', $params ) ) {
			$this->m_sep = str_replace( '_', ' ', $params['sep'] );
		} else {
			$this->m_sep = ',';
		}
	}

	public function getMimeType( $res ) {
		return 'text/csv';
	}

	public function getFileName( $res ) {
		return 'result.csv';
	}

	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES:SMWQuery::MODE_NONE;
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_csv' );
	}

	protected function getResultText( $res, $outputmode ) {
		$result = '';
		
		if ( $outputmode == SMW_OUTPUT_FILE ) { // make CSV file
			$csv = fopen( 'php://temp', 'r+' );
			
			if ( $this->mShowHeaders ) {
				$header_items = array();
				
				foreach ( $res->getPrintRequests() as $pr ) {
					$header_items[] = $pr->getLabel();
				}
				
				fputcsv( $csv, $header_items, $this->m_sep );
			}
			
			while ( $row = $res->getNext() ) {
				$row_items = array();
				
				foreach ( $row as $field ) {
					$growing = array();
					
					while ( ( $object = $field->getNextObject() ) !== false ) {
						$growing[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
					} 
					
					$row_items[] = implode( ',', $growing );
				}
				
				fputcsv( $csv, $row_items, $this->m_sep );
			}

			rewind( $csv );
			$result .= stream_get_contents( $csv );
		} else { // just make link to feed
			if ( $this->getSearchLabel( $outputmode ) ) {
				$label = $this->getSearchLabel( $outputmode );
			} else {
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$label = wfMsgForContent( 'smw_csv_link' );
			}

			$link = $res->getQueryLink( $label );
			$link->setParameter( 'csv', 'format' );
			$link->setParameter( $this->m_sep, 'sep' );
			
			if ( array_key_exists( 'mainlabel', $this->m_params ) ) {
				$link->setParameter( $this->m_params['mainlabel'], 'mainlabel' );
			}
				
			$link->setParameter( $this->mShowHeaders ? 'show' : 'hide', 'headers' );
			
			if ( array_key_exists( 'limit', $this->m_params ) ) {
				$link->setParameter( $this->m_params['limit'], 'limit' );
			} else { // use a reasonable default limit
				$link->setParameter( 100, 'limit' );
			}
			
			$result .= $link->getText( $outputmode, $this->mLinker );
			$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
		}
		return $result;
	}

	public function getParameters() {
		$params = parent::exportFormatParameters();
		
		$params[] = array( 'name' => 'sep', 'type' => 'string', 'description' => wfMsg( 'smw-paramdesc-csv-sep' ) );
		
		return $params;
	}

}
