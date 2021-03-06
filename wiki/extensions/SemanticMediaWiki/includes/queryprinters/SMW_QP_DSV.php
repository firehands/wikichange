<?php

/**
 * Result printer to print results in UNIX-style DSV (deliminter separated value) format.
 * 
 * @file SMW_QP_DSV.php
 * @ingroup SMWQuery
 * @since 1.5.7
 *
 * @licence GNU GPL v3
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * Based on the SMWCsvResultPrinter class.
 */
class SMWDSVResultPrinter extends SMWResultPrinter {
	
	protected $separator = ':';
	protected $fileName = 'result.dsv';
	
	protected function readParameters( $params, $outputmode ) {
		SMWResultPrinter::readParameters( $params, $outputmode );
		
		if ( array_key_exists( 'separator', $this->m_params ) && $this->m_params['separator'] != '\\' ) {
			$this->separator = trim( $this->m_params['separator'] );
		// Also support 'sep' as alias, since this is the param name for the CSV format.
		} elseif ( array_key_exists( 'sep', $this->m_params ) && $this->m_params['sep'] != '\\' ) {
			$this->separator = trim( $this->m_params['sep'] );
		}
		
		if ( isset( $this->m_params['filename'] ) ) {
			$this->fileName = str_replace( ' ', '_', $this->m_params['filename'] );
		}	
	}

	public function getMimeType( $res ) {
		return 'text/dsv';
	}

	public function getFileName( $res ) {
		return $this->fileName;
	}

	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_dsv' );
	}

	protected function getResultText( /* SMWQueryResult */ $res, $outputmode ) {
		if ( $outputmode == SMW_OUTPUT_FILE ) { // Make the DSV file.
			return $this->getResultFileContents( $res );
		}
		else { // Create a link pointing to the DSV file.
			return $this->getLinkToFile( $res, $outputmode );
		}
	}
	
	/**
	 * Returns the query result in DSV.
	 * 
	 * @since 1.5.7
	 *  
	 * @param SMWQueryResult $res
	 * 
	 * @return string
	 */	
	protected function getResultFileContents( SMWQueryResult $res ) {
		$lines = array();
		
		if ( $this->mShowHeaders ) {
			$headerItems = array();
			
			foreach ( $res->getPrintRequests() as $pr ) {
				$headerItems[] = $pr->getLabel();
			}
			
			$lines[] = $this->getDSVLine( $headerItems );
		}
		
		// Loop over the result objects (pages).
		while ( $row = $res->getNext() ) {
			$rowItems = array();
			
			// Loop over their fields (properties).
			foreach ( $row as $field ) {
				$itemSegments = array();
				
				// Loop over all values for the property.
				while ( ( $object = $field->getNextObject() ) !== false ) {
					$itemSegments[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
				} 
				
				// Join all values into a single string, separating them with comma's.
				$rowItems[] = implode( ',', $itemSegments );
			}
			
			$lines[] = $this->getDSVLine( $rowItems );
		}

		return implode( "\n", $lines );	
	}
	
	/**
	 * Returns a single DSV line.
	 * 
	 * @since 1.5.7
	 *  
	 * @param array $fields
	 * 
	 * @return string
	 */		
	protected function getDSVLine( array $fields ) {
		return implode( $this->separator, array_map( array( $this, 'encodeDSV' ), $fields ) );
	}
	
	/**
	 * Encodes a single DSV.
	 * 
	 * @since 1.5.7
	 *  
	 * @param string $value
	 * 
	 * @return string
	 */
	protected function encodeDSV( $value ) {
		// TODO
		// \nnn or \onnn or \0nnn for the character with octal value nnn
		// \xnn for the character with hexadecimal value nn
		// \dnnn for the character with decimal value nnn
		// \unnnn for a hexadecimal Unicode literal.
		return str_replace(
			array( '\n', '\r', '\t', '\b', '\f', '\\', $this->separator ),
			array( "\n", "\r", "\t", "\b", "\f", '\\\\', "\\$this->separator" ),
			$value
		);
	}
	
	/**
	 * Returns html for a link to a query that returns the DSV file.
	 * 
	 * @since 1.5.7
	 *  
	 * @param SMWQueryResult $res
	 * @param $outputmode
	 * 
	 * @return string
	 */		
	protected function getLinkToFile( SMWQueryResult $res, $outputmode ) {
		if ( $this->getSearchLabel( $outputmode ) ) {
			$label = $this->getSearchLabel( $outputmode );
		} else {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$label = wfMsgForContent( 'smw_dsv_link' );
		}

		$link = $res->getQueryLink( $label );
		$link->setParameter( 'dsv', 'format' );
		$link->setParameter( $this->separator, 'sep' );
		
		if ( array_key_exists( 'mainlabel', $this->m_params ) ) {
			$link->setParameter( $this->m_params['mainlabel'], 'mainlabel' );
		}
		
		$link->setParameter( $this->mShowHeaders ? 'show' : 'hide', 'headers' );
			
		if ( array_key_exists( 'limit', $this->m_params ) ) {
			$link->setParameter( $this->m_params['limit'], 'limit' );
		} else { // Use a reasonable default limit
			$link->setParameter( 100, 'limit' );
		}

		// yes, our code can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); 
		return $link->getText( $outputmode, $this->mLinker );
	}

	public function getParameters() {
		$params = parent::exportFormatParameters();
		
		$params[] = array( 'name' => 'separator', 'type' => 'string', 'description' => wfMsg( 'smw-paramdesc-dsv-separator' ), 'default' => $this->separator );
		$params[] = array( 'name' => 'filename', 'type' => 'string', 'description' => wfMsg( 'smw-paramdesc-dsv-filename' ), 'default' => $this->fileName );
		
		return $params;
	}

}
